<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PriceProjectionController extends Controller
{
    public function getProjectionDashboard()
    {
        $startDate = Carbon::create(2025, 2, 28);
        $endDate = Carbon::create(2025, 3, 29);
        $roomTypes = ['Super Deluxe Twin', 'Super Deluxe King', 'Infinity Sea View', 'Junior Suite', 'Panorama Ocean Suite'];

        // Historical averages from bookings (in RM)
        $historicalAverages = DB::table('bookings')
            ->select('room_type', DB::raw('AVG(price_per_day) as avg_price'))
            ->whereBetween('check_in_date', [Carbon::create(2025, 1, 28), Carbon::create(2025, 2, 27)])
            ->groupBy('room_type')
            ->pluck('avg_price', 'room_type')
            ->all();

        // Competitor prices (Feb 20–27, 2025, in RM)
        $competitorPrices = DB::table('competitors_room_prices')
            ->select('competitor_hotel_name', 'room_type', DB::raw('AVG(price) as avg_price'))
            ->whereBetween('check_date', [Carbon::create(2025, 2, 20), Carbon::create(2025, 2, 27)])
            ->groupBy('competitor_hotel_name', 'room_type')
            ->get()
            ->groupBy('competitor_hotel_name')
            ->mapWithKeys(function ($group) {
                return [$group->first()->competitor_hotel_name => $group->pluck('avg_price', 'room_type')->all()];
            })->all();

        $competitorMappings = [
            'Super Deluxe Twin' => [
                'Berjaya Times Square Hotel' => 'Deluxe Twin Room',
                'JW Marriott Hotel Kuala Lumpur' => 'Deluxe Twin Room',
                'Pullman Kuala Lumpur City Centre' => 'Superior Twin Room',
                'Wyndham Grand Bangsar Kuala Lumpur' => 'Premier Twin Room',
            ],
            'Super Deluxe King' => [
                'Berjaya Times Square Hotel' => 'Deluxe King Room',
                'JW Marriott Hotel Kuala Lumpur' => 'Deluxe King Room',
                'Pullman Kuala Lumpur City Centre' => 'Superior King Room',
                'Wyndham Grand Bangsar Kuala Lumpur' => 'Premier King Room',
            ],
            'Infinity Sea View' => [
                'Berjaya Times Square Hotel' => 'Premier Sea View Room',
                'JW Marriott Hotel Kuala Lumpur' => 'Oceanfront Deluxe Room',
                'Pullman Kuala Lumpur City Centre' => 'Ocean View Deluxe',
                'Wyndham Grand Bangsar Kuala Lumpur' => 'Sea View Deluxe Room',
            ],
            'Junior Suite' => [
                'Berjaya Times Square Hotel' => 'Junior Suite',
                'JW Marriott Hotel Kuala Lumpur' => 'Executive Suite',
                'Pullman Kuala Lumpur City Centre' => 'Junior Ocean Suite',
                'Wyndham Grand Bangsar Kuala Lumpur' => 'Junior Suite Sea View',
            ],
            'Panorama Ocean Suite' => [
                'Berjaya Times Square Hotel' => 'Presidential Suite',
                'JW Marriott Hotel Kuala Lumpur' => 'Grand Suite',
                'Pullman Kuala Lumpur City Centre' => 'Oceanfront Suite',
                'Wyndham Grand Bangsar Kuala Lumpur' => 'Royal Suite',
            ],
        ];

        $projections = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $date = $currentDate->toDateString();
            foreach ($roomTypes as $roomType) {
                $response = Http::get('http://localhost:5000/predict', [
                    'date' => $date,
                    'room_type' => $roomType,
                ]);
                $prophetData = $response->json();
                $projectedPrice = $prophetData['projected_price'] ?? $roomTypes[$roomType];
                $reason = $prophetData['reason'] ?? 'No specific reason available';

                $avgPrice = $historicalAverages[$roomType] ?? $roomTypes[$roomType];

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
                    'projected_price' => $projectedPrice,
                    'avg_price' => $avgPrice,
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
