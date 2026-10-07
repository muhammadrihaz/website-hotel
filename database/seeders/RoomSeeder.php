<?php

namespace Database\Seeders;

use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Enums\OccupancyStatus;
use App\Domains\Room\Enums\OperationalStatus;
use App\Domains\Room\Models\Room;
use App\Domains\Room\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $types = collect([
            ['name' => 'Standard', 'code' => 'STD', 'description' => 'Kamar standar untuk operasional harian.', 'base_rate' => 250000, 'capacity_adult' => 2, 'capacity_child' => 1],
            ['name' => 'Superior', 'code' => 'SUP', 'description' => 'Kamar superior dengan ruang lebih lega.', 'base_rate' => 325000, 'capacity_adult' => 2, 'capacity_child' => 1],
            ['name' => 'Deluxe', 'code' => 'DLX', 'description' => 'Kamar deluxe dengan fasilitas tambahan.', 'base_rate' => 400000, 'capacity_adult' => 2, 'capacity_child' => 2],
        ])->mapWithKeys(function (array $data): array {
            $roomType = RoomType::query()->updateOrCreate(['code' => $data['code']], [...$data, 'is_active' => true]);

            return [$data['code'] => $roomType];
        });

        foreach ([1, 2, 3] as $floor) {
            foreach (range(1, 8) as $sequence) {
                $roomNumber = (string) (($floor * 100) + $sequence);
                $typeCode = match (true) {
                    $sequence <= 4 => 'STD',
                    $sequence <= 6 => 'SUP',
                    default => 'DLX',
                };
                $type = $types->get($typeCode);

                Room::query()->firstOrCreate(
                    ['room_number' => $roomNumber],
                    [
                        'floor' => $floor,
                        'room_type_id' => $type->id,
                        'occupancy_status' => OccupancyStatus::Vacant,
                        'housekeeping_status' => HousekeepingStatus::Ready,
                        'operational_status' => OperationalStatus::Available,
                        'base_rate' => $type->base_rate,
                        'capacity_adult' => $type->capacity_adult,
                        'capacity_child' => $type->capacity_child,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
