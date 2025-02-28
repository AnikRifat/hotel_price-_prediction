<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingsSeeder extends Seeder
{
    public function run()
    {
        // Room types and base prices for Sayeman Hotel
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

        $totalRooms = 228;
        $startDate = Carbon::create(2023, 1, 1);
        $endDate = Carbon::create(2025, 2, 27);
        $data = [];
        $occupancyTracker = [];

        // Major holidays in Bangladesh (2023–2025)
        $holidays = [
            '2023-01-01', '2023-02-21', '2023-03-26', '2023-04-22', '2023-06-29', '2023-12-16', '2023-12-25',
            '2024-01-01', '2024-02-21', '2024-03-26', '2024-04-10', '2024-06-17', '2024-12-16', '2024-12-25',
            '2025-01-01', '2025-02-21', '2025-03-26',
        ];

        try {
            while ($startDate->lte($endDate)) {
                $date = $startDate->toDateString();
                $isWeekend = $startDate->dayOfWeekIso >= 5; // Friday or Saturday
                $isHoliday = in_array($date, $holidays);
                $isPeakSeason = $startDate->between(Carbon::create($startDate->year, 11, 1), Carbon::create($startDate->year + 1, 3, 31));
                $isMonsoon = $startDate->between(Carbon::create($startDate->year, 6, 1), Carbon::create($startDate->year, 9, 30));

                // Base booking count
                $baseBookings = $isPeakSeason ? rand(25, 35) : ($isMonsoon ? rand(10, 15) : rand(15, 20));
                $bookingCount = $isWeekend || $isHoliday ? min(40, $baseBookings + rand(5, 10)) : $baseBookings;

                // Limit bookings to available rooms
                $currentOccupancy = $occupancyTracker[$date] ?? 0;
                $availableRooms = max(0, $totalRooms - $currentOccupancy);
                $bookingCount = min($bookingCount, $availableRooms);

                for ($i = 0; $i < $bookingCount; $i++) {
                    $roomType = array_rand($roomTypes);
                    $basePrice = $roomTypes[$roomType];
                    $priceMultiplier = $isPeakSeason ? 1.2 : ($isMonsoon ? 0.8 : 1.0);
                    $priceMultiplier = $isWeekend || $isHoliday ? $priceMultiplier * 1.1 : $priceMultiplier;

                    $reservationDate = Carbon::parse($date)->subDays(rand(1, 30));
                    $checkInDate = Carbon::parse($date);
                    $stayDays = rand(1, 5);
                    $checkOutDate = $checkInDate->copy()->addDays($stayDays);
                    $salesPrice = round($basePrice * $stayDays * $priceMultiplier, 2);

                    // Calculate competitor average price
                    $mappedRoomTypes = $competitorMappings[$roomType];
                    $competitorPrices = DB::table('competitors_room_prices')
                        ->where('check_date', $checkInDate->toDateString())
                        ->whereIn('competitor_hotel_name', array_keys($mappedRoomTypes))
                        ->whereIn('room_type', array_values($mappedRoomTypes))
                        ->pluck('price')
                        ->toArray();
                    $avgCompPrice = !empty($competitorPrices) ? round(array_sum($competitorPrices) / count($competitorPrices), 2) : round($basePrice * 0.9, 2); // Fallback: 90% of base price

                    // Update occupancy
                    $occupancyTracker[$date] = ($occupancyTracker[$date] ?? 0) + 1;
                    $occupancy = min(100, round(($occupancyTracker[$date] / $totalRooms) * 100, 2));

                    $dayOfWeek = substr($checkInDate->format('l'), 0, 10);

                    $data[] = [
                        'room_type' => $roomType,
                        'status' => 'Confirmed',
                        'date_reservation' => $reservationDate->toDateString(),
                        'time_reservation' => sprintf('%02d:%02d:00', rand(8, 20), rand(0, 59)),
                        'days_of_week' => $dayOfWeek,
                        'check_in_date' => $checkInDate->toDateString(),
                        'check_out_date' => $checkOutDate->toDateString(),
                        'sales_price' => $salesPrice,
                        'd2_hotel_occupancy' => $occupancy,
                        'average_competitor_price' => $avgCompPrice,
                        'average_competitor_room_availability' => rand(10, 50),
                        'no_of_reservations' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($data) >= 1000) {
                        DB::table('bookings')->insert($data);
                        $data = [];
                    }
                }

                $startDate->addDay();
            }

            if (!empty($data)) {
                DB::table('bookings')->insert($data);
            }

            $this->command->info('BookingsSeeder completed successfully.');
        } catch (\Exception $e) {
            $this->command->error('Error in BookingsSeeder: ' . $e->getMessage());
            throw $e;
        }
    }
}
