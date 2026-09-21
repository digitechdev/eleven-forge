<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use Illuminate\Database\Seeder;

class ItemCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'Electronics' => ['Sensors', 'Controllers', 'Cameras', 'Power Supplies'],
            'Mechanical' => ['Motors', 'Bearings', 'Couplings'],
            'Fabrication' => ['Steel', 'Aluminum', 'Acrylic'],
            'Computing' => ['Jetson', 'Raspberry Pi', 'Mini PC'],
        ] as $name => $children) {
            $parent = ItemCategory::firstOrCreate(['parent_id' => null, 'name' => $name]);

            foreach ($children as $child) {
                ItemCategory::firstOrCreate(['parent_id' => $parent->id, 'name' => $child]);
            }
        }
    }
}
