<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PriceProjectionController extends Controller
{
    public function getProjectionDashboard()
    {
        // Define date ranges
        $projectionStart = Carbon::create(2025, 2, 28);
        $projectionEnd = Carbon::create(2025, 3, 29);
        $historicalStart = Carbon::create(2025, 1, 28);
        $historicalEnd = Carbon::create(2025, 2, 27);
        $recentStart = $historicalEnd->copy()->subDays(7);

        // Room types with base prices (in BDT)
        $roomTypes = [
            'Super Deluxe Twin' => 9000,
            'Super Deluxe King' => 10500,
            'Infinity Sea View' => 15000,
            'Junior Suite' => 21000,
            'Panorama Ocean Suite' => 36000,
        ];

        // Competitor room type mappings
        $competitorMappings = [
            'Super Deluxe Twin' => [
                'Hotel The Cox Today' => 'Deluxe Twin Room',
                'Long Beach Hotel' => 'Superior Twin Room',
                'Ocean Paradise Hotel' => 'Deluxe Twin Sea View',
                'Royal Tulip Sea Pearl' => 'Premier Twin Room',
            ],
            'Super Deluxe King' => [
                'Hotel The Cox Today' => 'Deluxe King Room',
                'Long Beach Hotel' => 'Superior King Room',
                'Ocean Paradise Hotel' => 'Deluxe King Sea View',
                'Royal Tulip Sea Pearl' => 'Premier King Room',
            ],
            'Infinity Sea View' => [
                'Hotel The Cox Today' => 'Premier Sea View Room',
                'Long Beach Hotel' => 'Oceanfront Deluxe Room',
                'Ocean Paradise Hotel' => 'Ocean View Deluxe',
                'Royal Tulip Sea Pearl' => 'Sea View Deluxe Room',
            ],
            'Junior Suite' => [
                'Hotel The Cox Today' => 'Junior Suite',
                'Long Beach Hotel' => 'Executive Suite',
                'Ocean Paradise Hotel' => 'Junior Ocean Suite',
                'Royal Tulip Sea Pearl' => 'Junior Suite Sea View',
            ],
            'Panorama Ocean Suite' => [
                'Hotel The Cox Today' => 'Presidential Suite',
                'Long Beach Hotel' => 'Grand Suite',
                'Ocean Paradise Hotel' => 'Oceanfront Suite',
                'Royal Tulip Sea Pearl' => 'Royal Suite',
            ],
        ];

        // Updated holiday list for 2025
        $holidays = [
            '2025-02-15', // Shab-e-Barat
            '2025-02-21', // International Mother Language Day
            '2025-03-26', // Independence Day
            '2025-03-28', // Shab-e-Qadar & Jumatul Bidah
            '2025-03-29', // Eid-ul-Fitr Day 1
            '2025-03-30', // Eid-ul-Fitr Day 2
            '2025-03-31', // Eid-ul-Fitr Day 3
        ];

        // Fetch historical averages using price_per_day
        $historicalAverages = DB::table('bookings')
            ->select('room_type', DB::raw('AVG(price_per_day) as avg_price'))
            ->whereBetween('check_in_date', [$historicalStart, $historicalEnd])
            ->groupBy('room_type')
            ->pluck('avg_price', 'room_type')
            ->all();

        // Fetch weighted average occupancy per room type
        $avgOccupancyPerRoom = DB::table('bookings')
            ->select('room_type', 'check_in_date', DB::raw('AVG(d2_hotel_occupancy) as avg_occupancy'))
            ->whereBetween('check_in_date', [$historicalStart, $historicalEnd])
            ->groupBy('room_type', 'check_in_date')
            ->get()
            ->groupBy('room_type')
            ->map(function ($group) use ($historicalEnd) {
                $weightedSum = 0;
                $totalWeight = 0;
                foreach ($group as $entry) {
                    $daysFromEnd = Carbon::parse($entry->check_in_date)->diffInDays($historicalEnd);
                    $weight = 1 / (1 + $daysFromEnd); // Recent dates weigh more
                    $weightedSum += $entry->avg_occupancy * $weight;
                    $totalWeight += $weight;
                }
                return $totalWeight > 0 ? $weightedSum / $totalWeight : 50; // Fallback to 50%
            })->all();

        // Fetch recent competitor prices
        $competitorPrices = DB::table('competitors_room_prices')
            ->select('competitor_hotel_name', 'room_type', DB::raw('AVG(price) as avg_price'))
            ->whereBetween('check_date', [$recentStart, $historicalEnd])
            ->groupBy('competitor_hotel_name', 'room_type')
            ->get()
            ->groupBy('competitor_hotel_name')
            ->mapWithKeys(function ($group) {
                return [$group->first()->competitor_hotel_name => $group->pluck('avg_price', 'room_type')->all()];
            })->all();

        $projections = [];
        $currentDate = $projectionStart->copy();

        while ($currentDate->lte($projectionEnd)) {
            $date = $currentDate->toDateString();
            $isWeekend = $currentDate->dayOfWeekIso >= 5;
            $isHoliday = in_array($date, $holidays);
            $isPeakSeason = true; // Feb 28 - Mar 29

            foreach ($roomTypes as $roomType => $basePrice) {
                // Fetch Prophet prediction
                $response = Http::get('http://localhost:5000/predict', [
                    'date' => $date,
                    'room_type' => $roomType,
                ]);
                // dd($response->json());
                $prophetData = $response->json();
                $projectedPrice = $prophetData['projected_price'] ?? $basePrice;

                // Historical average
                $avgPrice = $historicalAverages[$roomType] ?? $basePrice;

                // Calculate demand with refined logic
                $baseDemand = $avgOccupancyPerRoom[$roomType] ?? 50; // Weighted historical base
                $demand = $baseDemand;

                // Demand adjustments
                if ($isPeakSeason) {
                    $demand += 10; // Base increase for peak season
                }
                if ($isWeekend) {
                    $demand += 5;  // Moderate increase for weekends
                }
                if ($isHoliday) {
                    // Major holidays (e.g., Eid-ul-Fitr) get a bigger boost
                    if (in_array($date, ['2025-03-29', '2025-03-30', '2025-03-31'])) {
                        $demand += 20; // Eid-ul-Fitr significant boost
                    } else {
                        $demand += 10; // Other holidays moderate boost
                    }
                }

                // Cap demand at 100% and ensure non-negative
                $demand = max(0, min(100, $demand));
                $demandLevel = $demand < 40 ? 'Low' : ($demand > 70 ? 'High' : 'Medium');

                // Competitor prices
                $compData = [];
                $compPricesSum = 0;
                $compCount = 0;
                foreach ($competitorMappings[$roomType] as $hotel => $compRoomType) {
                    $compPrice = $competitorPrices[$hotel][$compRoomType] ?? ($avgPrice * 0.9);
                    $compData[] = [
                        'name' => $hotel,
                        'room_type' => $compRoomType,
                        'price' => round($compPrice, 2),
                    ];
                    $compPricesSum += $compPrice;
                    $compCount++;
                }
                $avgCompPrice = $compCount > 0 ? round($compPricesSum / $compCount, 2) : 0;

                $projections[$date][$roomType] = [
                    'projected_price' => round($projectedPrice, 2),
                    'avg_price' => round($avgPrice, 2),
                    'demand' => round($demand, 2),
                    'demand_level' => $demandLevel,
                    'competitors' => $compData,
                    'avg_competitor_price' => $avgCompPrice,
                ];
            }
            $currentDate->addDay();
        }

        return view('price_projection_dashboard', ['projections' => $projections]);
    }
}
