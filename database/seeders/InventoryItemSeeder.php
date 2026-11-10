<?php

namespace Database\Seeders;

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use App\Enums\UserRole;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample inventory items used by the sample innovations' bills of materials.
 * Relies on the item category and unit seeders; safe to run repeatedly.
 */
class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $author = User::role(UserRole::SuperAdmin->value)->first() ?? User::query()->first();
        $categoryIds = ItemCategory::query()->whereNotNull('parent_id')->pluck('id', 'name');
        $unitIds = Unit::query()->pluck('id', 'symbol');

        foreach ($this->items() as $code => [$name, $category, $unit, $type, $brand, $onHand, $minimum, $cost]) {
            InventoryItem::query()->withTrashed()->updateOrCreate(
                ['item_code' => $code],
                [
                    'name' => $name,
                    'category_id' => $categoryIds[$category],
                    'default_unit_id' => $unitIds[$unit],
                    'item_type' => $type,
                    'brand' => $brand,
                    'quantity_on_hand' => $onHand,
                    'minimum_stock' => $minimum,
                    'unit_cost' => $cost,
                    'status' => InventoryItemStatus::Active,
                    'created_by' => $author?->id,
                ],
            );
        }
    }

    /**
     * Item code => [name, child category, unit symbol, type, brand, on hand, minimum stock, unit cost].
     * A few items sit at or below their minimum so the low-stock views have data.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: InventoryItemType, 4: string|null, 5: float|int, 6: float|int, 7: float|int}>
     */
    private function items(): array
    {
        return [
            'DEMO-ITM-001' => ['Jetson Orin Nano 8GB', 'Jetson', 'pc', InventoryItemType::Module, 'NVIDIA', 2, 3, 24990],
            'DEMO-ITM-002' => ['Raspberry Pi 5 8GB', 'Raspberry Pi', 'pc', InventoryItemType::Module, 'Raspberry Pi', 6, 4, 5490],
            'DEMO-ITM-003' => ['Intel NUC 13 Pro', 'Mini PC', 'pc', InventoryItemType::Equipment, 'Intel', 1, 1, 38500],
            'DEMO-ITM-004' => ['IMX477 HQ camera module', 'Cameras', 'pc', InventoryItemType::Electronic, 'Arducam', 4, 2, 3850],
            'DEMO-ITM-005' => ['Inductive proximity sensor M18', 'Sensors', 'pc', InventoryItemType::Electronic, 'Omron', 3, 10, 890],
            'DEMO-ITM-006' => ['HC-SR04 ultrasonic sensor', 'Sensors', 'pc', InventoryItemType::Electronic, null, 25, 10, 95],
            'DEMO-ITM-007' => ['ESP32-S3 DevKitC', 'Controllers', 'pc', InventoryItemType::Electronic, 'Espressif', 12, 8, 650],
            'DEMO-ITM-008' => ['24V 10A switching power supply', 'Power Supplies', 'pc', InventoryItemType::Electronic, 'Mean Well', 2, 4, 2150],
            'DEMO-ITM-009' => ['NEMA 23 stepper motor', 'Motors', 'pc', InventoryItemType::Mechanical, 'StepperOnline', 8, 4, 1750],
            'DEMO-ITM-010' => ['12V DC gear motor 60 RPM', 'Motors', 'pc', InventoryItemType::Mechanical, null, 10, 6, 780],
            'DEMO-ITM-011' => ['608ZZ ball bearing', 'Bearings', 'box', InventoryItemType::Mechanical, 'SKF', 1, 2, 450],
            'DEMO-ITM-012' => ['Flexible shaft coupling 8mm', 'Couplings', 'pc', InventoryItemType::Mechanical, null, 14, 6, 120],
            'DEMO-ITM-013' => ['Mild steel sheet 1.5mm', 'Steel', 'sheet', InventoryItemType::RawMaterial, null, 6, 4, 2600],
            'DEMO-ITM-014' => ['Aluminum extrusion 2020', 'Aluminum', 'm', InventoryItemType::RawMaterial, null, 18, 12, 310],
            'DEMO-ITM-015' => ['Clear acrylic sheet 3mm', 'Acrylic', 'sheet', InventoryItemType::RawMaterial, null, 5, 3, 1450],
            'DEMO-ITM-016' => ['50kg load cell with HX711 amplifier', 'Sensors', 'set', InventoryItemType::Electronic, null, 4, 4, 480],
            'DEMO-ITM-017' => ['30W monocrystalline solar panel', 'Power Supplies', 'pc', InventoryItemType::Electronic, null, 3, 2, 1850],
            'DEMO-ITM-018' => ['4-channel 5V relay module', 'Controllers', 'pc', InventoryItemType::Electronic, null, 9, 5, 260],
            'DEMO-ITM-019' => ['DHT22 temperature and humidity sensor', 'Sensors', 'pc', InventoryItemType::Electronic, 'Aosong', 2, 6, 320],
        ];
    }
}
