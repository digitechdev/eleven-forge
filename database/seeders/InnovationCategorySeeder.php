<?php

namespace Database\Seeders;

use App\Models\InnovationCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InnovationCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Smart Machines', 'Software Systems', 'AI / Machine Learning', 'IoT', 'Sustainability', 'Automation', 'Internal Systems', 'Client Solutions'] as $order => $name) {
            InnovationCategory::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $order],
            );
        }
    }
}
