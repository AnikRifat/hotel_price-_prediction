<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompetitorsRoomPricesSeeder extends Seeder
{
    public function run()
    {
        $competitors = [
            'Hotel The Cox Today' => [
                'Deluxe Twin Room' => [7000, 9000],
                'Deluxe King Room' => [7500, 9500],
                'Premier Sea View Room' => [10000, 12000],
                'Junior Suite' => [15000, 18000],
                'Presidential Suite' => [25000, 30000],
            ],
            'Long Beach Hotel' => [
                'Superior Twin Room' => [8000, 10000],
                'Superior King Room' => [8500, 10500],
                'Oceanfront Deluxe Room' => [12000, 14000],
                'Executive Suite' => [18000, 22000],
                'Grand Suite' => [28000, 35000],
            ],
            'Ocean Paradise Hotel' => [
                'Deluxe Twin Sea View' => [9000, 11000],
                'Deluxe King Sea View' => [9500, 11500],
                'Ocean View Deluxe' => [13000, 15000],
                'Junior Ocean Suite' => [20000, 24000],
                'Oceanfront Suite' => [30000, 38000],
            ],
            'Royal Tulip Sea Pearl' => [
                'Premier Twin Room' => [10000, 12000],
                'Premier King Room' => [10500, 12500],
                'Sea View Deluxe Room' => [14000, 16000],
                'Junior Suite Sea View' => [22000, 26000],
                'Royal Suite' => [35000, 45000],
            ],
        ];

        $holidays = [
            '2023-01-01', '2023-02-21', '2023-03-26', '2023-04-22', '2023-06-29', '2023-12-16', '2023-12-25',
            '2024-01-01', '2024-02-21', '2024-03-26', '2024-04-10', '2024-06-17', '2024-12-16', '2024-12-25',
            '2025-01-01', '2025-02-21', '2025-03-26',
        ];
        $startDate = Carbon::create(2023, 1, 1);
        $endDate = Carbon::create(2025, 2, 27);
        $data = [];

        try {
            while ($startDate->lte($endDate)) {
                $date = $startDate->toDateString();
                $isWeekend = $startDate->dayOfWeekIso >= 5;
                $isHoliday = in_array($date, $holidays);
                $isPeakSeason = $startDate->between(Carbon::create($startDate->year, 11, 1), Carbon::create($startDate->year + 1, 3, 31));
                $isMonsoon = $startDate->between(Carbon::create($startDate->year, 6, 1), Carbon::create($startDate->year, 9, 30));

                $seasonMultiplier = $isPeakSeason ? 1.2 : ($isMonsoon ? 0.8 : 1.0);
                $specialDayMultiplier = ($isHoliday || $isWeekend) ? 1.1 : 1.0;

                foreach ($competitors as $hotel => $rooms) {
                    foreach ($rooms as $roomType => $priceRange) {
                        $basePrice = rand($priceRange[0], $priceRange[1]);
                        $randomVariation = rand(95, 105) / 100;
                        $price = round($basePrice * $seasonMultiplier * $specialDayMultiplier * $randomVariation, 2);

                        $data[] = [
                            'competitor_hotel_name' => $hotel,
                            'room_type' => $roomType,
                            'price' => $price,
                            'check_date' => $date,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (count($data) >= 1000) {
                    DB::table('competitors_room_prices')->insert($data);
                    $data = [];
                }
                $startDate->addDay();
            }

            if (!empty($data)) {
                DB::table('competitors_room_prices')->insert($data);
            }
        } catch (\Exception $e) {
            $this->command->error('Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
