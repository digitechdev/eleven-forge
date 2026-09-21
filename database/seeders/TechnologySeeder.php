<?php

namespace Database\Seeders;

use App\Models\Technology;
use Illuminate\Database\Seeder;

class TechnologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            'Laravel' => 'Framework', 'PHP' => 'Language', 'MySQL' => 'Database',
            'Python' => 'Language', 'YOLO' => 'AI / ML', 'ONNX' => 'AI / ML',
            'Unity' => 'Platform', 'Sentis' => 'Library', 'AWS' => 'Cloud',
            'MQTT' => 'Protocol',
        ] as $name => $category) {
            Technology::firstOrCreate(['name' => $name], ['category' => $category]);
        }
    }
}
