<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'Piece' => 'pc', 'Set' => 'set', 'Sheet' => 'sheet',
            'Meter' => 'm', 'Millimeter' => 'mm', 'Kilogram' => 'kg',
            'Liter' => 'L', 'Roll' => 'roll', 'Box' => 'box',
        ] as $name => $symbol) {
            Unit::firstOrCreate(['symbol' => $symbol], ['name' => $name]);
        }
    }
}
