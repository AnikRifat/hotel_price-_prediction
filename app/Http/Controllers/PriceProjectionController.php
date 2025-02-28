<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PriceProjectionController extends Controller
{
    public function getProjectionDashboard()
    {
        $projectionStart = Carbon::create(2025, 2, 28);
        $projectionEnd = Carbon::create(2025, 3, 29);
        $historicalStart = Carbon::create(2025, 1, 28);
        $historicalEnd = Carbon::create(2025, 2, 27);
        $recentStart = $historicalEnd->copy()->subDays(7);

        $roomTypes = [
            'Super Deluxe Twin' => 9000,
            'Super Deluxe King' => 10500,
            'Infinity Sea View' => 15000,
            'Junior Suite' => 21000,
            'Panorama Ocean Suite' => 36000,
        ];

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

        $historicalAverages = DB::table('bookings')
            ->select('room_type', DB::raw('AVG(price_per_day) as avg_price'))
            ->whereBetween('check_in_date', [$historicalStart, $historicalEnd])
            ->groupBy('room_type')
            ->pluck('avg_price', 'room_type')
            ->all();

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

            foreach ($roomTypes as $roomType => $basePrice) {
                $response = Http::get('http://localhost:5000/predict', [
                    'date' => $date,
                    'room_type' => $roomType,
                ]);
                $prophetData = $response->json();
                $projectedPrice = $prophetData['projected_price'] ?? $basePrice;
                $reason = $prophetData['reason'] ?? 'No specific reason available';

                $avgPrice = $historicalAverages[$roomType] ?? $basePrice;

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
                    'competitors' => $compData,
                    'avg_competitor_price' => $avgCompPrice,
                    'reason' => $reason,
                ];
            }
            $currentDate->addDay();
        }

        return view('price_projection_dashboard', ['projections' => $projections]);
    }
}
