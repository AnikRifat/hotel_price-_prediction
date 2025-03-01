<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompetitorsRoomPricesSeeder extends Seeder
{
    public function run()
    {
        $startDate = Carbon::create(2024, 2, 20);
        $endDate = Carbon::create(2025, 2, 27);

        $competitors = [
            'Berjaya Times Square Hotel' => [
                'Super Deluxe Twin' => 385.53,
                'Super Deluxe King' => 400.00,
                'Infinity Sea View' => 450.00,
                'Junior Suite' => 600.00,
                'Panorama Ocean Suite' => 800.00,
            ],
            'JW Marriott Hotel Kuala Lumpur' => [
                'Super Deluxe Twin' => 692.51,
                'Super Deluxe King' => 720.00,
                'Infinity Sea View' => 850.00,
                'Junior Suite' => 1000.00,
                'Panorama Ocean Suite' => 1200.00,
            ],
            'Pullman Kuala Lumpur City Centre' => [
                'Super Deluxe Twin' => 466.04,
                'Super Deluxe King' => 480.00,
                'Infinity Sea View' => 550.00,
                'Junior Suite' => 700.00,
                'Panorama Ocean Suite' => 900.00,
            ],
            'Wyndham Grand Bangsar Kuala Lumpur' => [
                'Super Deluxe Twin' => 283.14,
                'Super Deluxe King' => 300.00,
                'Infinity Sea View' => 350.00,
                'Junior Suite' => 500.00,
                'Panorama Ocean Suite' => 650.00,
            ],
        ];

        $roomMappings = [
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

        $holidays = config('holidays'); // Mother Language Day within Feb 20–27
        $prices = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $date = $currentDate->toDateString();
            $isWeekend = $currentDate->dayOfWeekIso >= 5;
            $isHoliday = in_array($date, $holidays);

            $demandFactor = 1.0;
            if ($isWeekend) $demandFactor *= 1.1;
            if ($isHoliday) $demandFactor *= 1.2;

            foreach ($competitors as $hotel => $roomPrices) {
                foreach ($roomPrices as $yourRoom => $basePrice) {
                    $priceMultiplier = $demandFactor * (1 + (rand(-10, 10) / 100));
                    $dailyPrice = $basePrice * $priceMultiplier;

                    $prices[] = [
                        'check_date' => $date,
                        'competitor_hotel_name' => $hotel,
                        'room_type' => $roomMappings[$yourRoom][$hotel],
                        'price' => round($dailyPrice, 2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            $currentDate->addDay();
        }

        DB::table('competitors_room_prices')->insert($prices);
        $this->command->info('CompetitorsRoomPricesSeeder completed successfully.');
    }
}
