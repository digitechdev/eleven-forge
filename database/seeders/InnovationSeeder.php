<?php

namespace Database\Seeders;

use App\Enums\InnovationStatus;
use App\Enums\InnovationType;
use App\Enums\InnovationVisibility;
use App\Enums\UserRole;
use App\Models\Innovation;
use App\Models\InnovationCategory;
use App\Models\InventoryItem;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample innovations with bills of materials and technologies, for local and testing environments.
 * Relies on the innovation category, technology, and inventory item seeders; safe to run repeatedly.
 */
class InnovationSeeder extends Seeder
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
        $items = InventoryItem::query()->get(['id', 'item_code', 'default_unit_id'])->keyBy('item_code');
        $technologyIds = Technology::query()->pluck('id', 'name');
        $categoryIds = InnovationCategory::query()->pluck('id', 'slug');

        foreach ($this->innovations() as $code => $data) {
            $innovation = Innovation::query()->withTrashed()->updateOrCreate(
                ['innovation_code' => $code],
                [
                    ...collect($data)->except(['category', 'bom', 'technologies'])->all(),
                    'category_id' => $categoryIds[Str::slug($data['category'])],
                    'slug' => Str::slug($data['name']),
                    'created_by' => $author?->id,
                    'updated_by' => $author?->id,
                ],
            );

            foreach ($data['bom'] as $order => [$itemCode, $quantity, $usage]) {
                $item = $items[$itemCode];

                $innovation->innovationItems()->updateOrCreate(
                    ['inventory_item_id' => $item->id],
                    [
                        'unit_id' => $item->default_unit_id,
                        'quantity_required' => $quantity,
                        'usage_description' => $usage,
                        'sort_order' => $order,
                    ],
                );
            }

            foreach ($data['technologies'] as $name => [$version, $purpose]) {
                $innovation->innovationTechnologies()->updateOrCreate(
                    ['technology_id' => $technologyIds[$name]],
                    ['version' => $version, 'purpose' => $purpose],
                );
            }
        }
    }

    /**
     * Innovation code => attributes plus category name, BOM lines ([item code, quantity, usage]),
     * and technologies (name => [version, purpose]).
     *
     * @return array<string, array<string, mixed>>
     */
    private function innovations(): array
    {
        return [
            'DEMO-INV-001' => [
                'name' => 'SEB V4',
                'category' => 'Smart Machines',
                'type' => InnovationType::Hybrid,
                'tagline' => 'Smart sorting bin that recognises and separates recyclables on the spot.',
                'short_description' => 'An AI-powered smart bin combining computer vision, sensors, and a cloud dashboard.',
                'description' => 'SEB V4 is the fourth generation of our smart eco bin. A camera and edge AI model classify each item dropped into the bin and a motorised chute routes it into the correct compartment, while fill-level data streams to the SEB Portal.',
                'problem_statement' => 'Mixed waste contaminates recyclables, and manual sorting at the source is unreliable and slow.',
                'solution_description' => 'On-device object detection identifies plastic, metal, paper, and residual waste in under a second, and the bin reports fill levels so collection can be scheduled only when needed.',
                'key_features' => ['On-device YOLO object detection', 'Four-way motorised sorting chute', 'Ultrasonic fill-level monitoring', 'Live telemetry over MQTT'],
                'benefits' => ['Cleaner recycling streams', 'Fewer collection trips', 'Data for sustainability reporting'],
                'status' => InnovationStatus::Deployed,
                'visibility' => InnovationVisibility::Public,
                'is_featured' => true,
                'developed_at' => '2026-03-15',
                'bom' => [
                    ['DEMO-ITM-001', 1, 'Edge AI inference'],
                    ['DEMO-ITM-004', 1, 'Item recognition camera'],
                    ['DEMO-ITM-006', 4, 'Compartment fill-level sensing'],
                    ['DEMO-ITM-009', 2, 'Sorting chute actuation'],
                    ['DEMO-ITM-008', 1, 'Main power'],
                    ['DEMO-ITM-013', 4, 'Body and chute fabrication'],
                ],
                'technologies' => [
                    'Python' => ['3.12', 'Control software and sensor drivers'],
                    'YOLO' => ['v8', 'Waste classification model'],
                    'ONNX' => ['1.17', 'Optimised model runtime on the Jetson'],
                    'MQTT' => ['5.0', 'Telemetry to the SEB Portal'],
                ],
            ],
            'DEMO-INV-002' => [
                'name' => 'SEB Portal',
                'category' => 'Software Systems',
                'type' => InnovationType::Software,
                'tagline' => 'One dashboard for every smart bin in the fleet.',
                'short_description' => 'Web portal for monitoring bin fill levels, sorting accuracy, and collection schedules.',
                'description' => 'SEB Portal collects telemetry from deployed SEB units and turns it into fill-level maps, diversion reports, and collection schedules for facility teams.',
                'problem_statement' => 'Facility teams had no visibility into bin status and collected on fixed schedules regardless of need.',
                'solution_description' => 'Real-time dashboards and alerts show which bins need attention, and monthly reports quantify recycling diversion.',
                'key_features' => ['Live fleet map', 'Fill-level alerts', 'Diversion and accuracy reports', 'Role-based access for clients'],
                'benefits' => ['Optimised collection routes', 'Transparent reporting for clients'],
                'status' => InnovationStatus::Deployed,
                'visibility' => InnovationVisibility::Public,
                'is_featured' => true,
                'developed_at' => '2026-02-01',
                'bom' => [],
                'technologies' => [
                    'Laravel' => ['12', 'Web application and API'],
                    'PHP' => ['8.4', 'Application runtime'],
                    'MySQL' => ['8.0', 'Telemetry and reporting data'],
                    'AWS' => [null, 'Hosting and IoT ingestion'],
                    'MQTT' => ['5.0', 'Device telemetry ingestion'],
                ],
            ],
            'DEMO-INV-003' => [
                'name' => 'SEB Mini SRX',
                'category' => 'Smart Machines',
                'type' => InnovationType::Hardware,
                'tagline' => 'A compact desk-side sorting bin for offices.',
                'short_description' => 'A smaller, sensor-only version of SEB for offices and classrooms.',
                'description' => 'SEB Mini SRX uses inductive and ultrasonic sensors instead of a camera to separate metal and non-metal waste in a compact acrylic body.',
                'problem_statement' => 'The full SEB unit is too large and costly for small offices.',
                'solution_description' => 'A low-cost sensor array and a single gear motor deliver two-way sorting at a fraction of the footprint.',
                'key_features' => ['Two-way sorting', 'Clear acrylic body', 'USB-C powered controller'],
                'benefits' => ['Low unit cost', 'Fits under a desk'],
                'status' => InnovationStatus::Prototype,
                'visibility' => InnovationVisibility::Public,
                'is_featured' => false,
                'developed_at' => null,
                'bom' => [
                    ['DEMO-ITM-007', 1, 'Main controller'],
                    ['DEMO-ITM-005', 1, 'Metal detection'],
                    ['DEMO-ITM-006', 1, 'Fill-level sensing'],
                    ['DEMO-ITM-010', 1, 'Sorting flap'],
                    ['DEMO-ITM-015', 2, 'Enclosure'],
                ],
                'technologies' => [],
            ],
            'DEMO-INV-004' => [
                'name' => 'Vision Trainer XR',
                'category' => 'AI / Machine Learning',
                'type' => InnovationType::Software,
                'tagline' => 'Synthetic training images for our vision models.',
                'short_description' => 'Unity tool that renders labelled waste images to train detection models.',
                'description' => 'Vision Trainer XR generates thousands of labelled, randomised scenes of waste items so detection models can be retrained without manual photography.',
                'problem_statement' => 'Collecting and labelling real training photos takes weeks per model update.',
                'solution_description' => 'Procedurally generated scenes with automatic labels, exported directly in YOLO format.',
                'key_features' => ['Domain randomisation', 'Automatic YOLO labels', 'In-editor model preview with Sentis'],
                'benefits' => ['Faster model iterations'],
                'status' => InnovationStatus::Testing,
                'visibility' => InnovationVisibility::Internal,
                'is_featured' => false,
                'developed_at' => null,
                'bom' => [],
                'technologies' => [
                    'Unity' => ['6', 'Scene generation'],
                    'Sentis' => ['2.1', 'In-editor inference preview'],
                    'ONNX' => ['1.17', 'Model exchange format'],
                ],
            ],
            'DEMO-INV-005' => [
                'name' => 'Line Energy Monitor',
                'category' => 'IoT',
                'type' => InnovationType::Hybrid,
                'tagline' => 'Per-machine energy metering for the workshop.',
                'short_description' => 'ESP32-based meters that report machine energy use to a shared dashboard.',
                'description' => 'Clamp-on meters built around the ESP32 measure energy draw per machine and publish readings over MQTT for analysis.',
                'problem_statement' => 'Energy use could only be measured for the whole building, not per machine.',
                'solution_description' => 'Low-cost meters on each machine feed a dashboard that highlights idle consumption.',
                'key_features' => ['Per-machine readings', 'MQTT publishing', 'Idle-time detection'],
                'benefits' => ['Lower energy bills', 'Maintenance insights'],
                'status' => InnovationStatus::Development,
                'visibility' => InnovationVisibility::Internal,
                'is_featured' => false,
                'developed_at' => null,
                'bom' => [
                    ['DEMO-ITM-007', 4, 'Meter controller'],
                    ['DEMO-ITM-002', 1, 'Local gateway'],
                    ['DEMO-ITM-014', 2, 'Mounting rail'],
                ],
                'technologies' => [
                    'Python' => ['3.12', 'Gateway and analysis scripts'],
                    'MQTT' => ['5.0', 'Meter readings transport'],
                ],
            ],
            'DEMO-INV-006' => [
                'name' => 'Forge Assist',
                'category' => 'Internal Systems',
                'type' => InnovationType::Software,
                'tagline' => 'Ask the registry what a part is used in.',
                'short_description' => 'Concept for a chat assistant over the Eleven Forge registry.',
                'description' => null,
                'problem_statement' => 'Finding which innovations use a given part requires several searches.',
                'solution_description' => null,
                'key_features' => [],
                'benefits' => [],
                'status' => InnovationStatus::Concept,
                'visibility' => InnovationVisibility::Internal,
                'is_featured' => false,
                'developed_at' => null,
                'bom' => [],
                'technologies' => [
                    'Laravel' => ['12', 'Integration with Eleven Forge'],
                ],
            ],
            'DEMO-INV-007' => [
                'name' => 'LoadSense Scale',
                'category' => 'Automation',
                'type' => InnovationType::Hardware,
                'tagline' => 'Automatic weighing for every bag that leaves the line.',
                'short_description' => 'A rugged platform scale that logs the weight of each sorted bag.',
                'description' => 'LoadSense Scale is a steel platform scale with a load cell and ESP32 controller that records each bag of sorted material and prints a weight tag for collection.',
                'problem_statement' => 'Recovered material was weighed by hand and logged on paper, so totals were late and often wrong.',
                'solution_description' => 'Each placement is weighed and recorded automatically, giving accurate daily recovery totals by material.',
                'key_features' => ['Up to 50 kg capacity', 'Automatic tare', 'Per-material totals'],
                'benefits' => ['Accurate recovery figures', 'No manual logbooks'],
                'status' => InnovationStatus::Completed,
                'visibility' => InnovationVisibility::Public,
                'is_featured' => false,
                'developed_at' => '2026-05-20',
                'bom' => [
                    ['DEMO-ITM-016', 1, 'Weight measurement'],
                    ['DEMO-ITM-007', 1, 'Scale controller'],
                    ['DEMO-ITM-008', 1, 'Main power'],
                    ['DEMO-ITM-013', 2, 'Platform and frame'],
                ],
                'technologies' => [
                    'Python' => ['3.12', 'Calibration and logging tools'],
                ],
            ],
            'DEMO-INV-008' => [
                'name' => 'SolarCompact Bin',
                'category' => 'Sustainability',
                'type' => InnovationType::Hybrid,
                'tagline' => 'A sun-powered bin that compacts its own waste.',
                'short_description' => 'Solar-powered outdoor bin that compresses waste to hold up to five times more.',
                'description' => 'SolarCompact Bin uses a rooftop solar panel and a geared press to compact waste whenever the bin reaches a set fill level, then reports its status for collection.',
                'problem_statement' => 'Outdoor bins in parks and campuses overflow between collections.',
                'solution_description' => 'On-board compaction increases capacity, and fill alerts let crews collect only full bins.',
                'key_features' => ['Solar-powered compaction', 'Ultrasonic fill sensing', 'Collection alerts over MQTT'],
                'benefits' => ['Fewer overflowing bins', 'Fewer collection trips', 'Runs off-grid'],
                'status' => InnovationStatus::Prototype,
                'visibility' => InnovationVisibility::Public,
                'is_featured' => true,
                'developed_at' => null,
                'bom' => [
                    ['DEMO-ITM-017', 1, 'Power generation'],
                    ['DEMO-ITM-010', 2, 'Compaction press drive'],
                    ['DEMO-ITM-006', 1, 'Fill-level sensing'],
                    ['DEMO-ITM-007', 1, 'Main controller'],
                    ['DEMO-ITM-013', 5, 'Body and press fabrication'],
                ],
                'technologies' => [
                    'MQTT' => ['5.0', 'Fill and compaction telemetry'],
                    'Python' => ['3.12', 'Controller firmware tooling'],
                ],
            ],
            'DEMO-INV-009' => [
                'name' => 'Client Asset Tracker',
                'category' => 'Client Solutions',
                'type' => InnovationType::Software,
                'tagline' => 'Know where every deployed unit is and how it is doing.',
                'short_description' => 'Client-facing portal listing deployed equipment, service history, and warranty status.',
                'description' => 'Client Asset Tracker gives each client a view of the equipment we have installed for them, with service visits, warranty dates, and support requests in one place.',
                'problem_statement' => 'Clients emailed support to find out what was installed and when it was last serviced.',
                'solution_description' => 'A self-service portal shows every asset, its service log, and lets clients raise requests directly.',
                'key_features' => ['Asset register per client', 'Service history', 'Warranty reminders', 'Support requests'],
                'benefits' => ['Fewer support emails', 'Better client retention'],
                'status' => InnovationStatus::Deployed,
                'visibility' => InnovationVisibility::Public,
                'is_featured' => false,
                'developed_at' => '2026-06-10',
                'bom' => [],
                'technologies' => [
                    'Laravel' => ['12', 'Portal and API'],
                    'PHP' => ['8.4', 'Application runtime'],
                    'MySQL' => ['8.0', 'Asset and service records'],
                    'AWS' => [null, 'Hosting'],
                ],
            ],
            'DEMO-INV-010' => [
                'name' => 'Automated Parts Counter',
                'category' => 'Automation',
                'type' => InnovationType::Hybrid,
                'tagline' => 'Count small parts from a single photo.',
                'short_description' => 'Camera station that counts screws, bearings, and fittings for stock-taking.',
                'description' => 'Parts are spread on a backlit tray and a camera with an object detection model counts them in seconds, updating stock counts without manual tallying.',
                'problem_statement' => 'Counting small parts during stock-taking takes hours and is error-prone.',
                'solution_description' => 'A vision model counts parts from one photo and a stepper-driven tray separates overlapping pieces.',
                'key_features' => ['One-shot counting', 'Vibrating separation tray', 'Count history export'],
                'benefits' => ['Faster stock-taking', 'More accurate inventory'],
                'status' => InnovationStatus::Testing,
                'visibility' => InnovationVisibility::Internal,
                'is_featured' => false,
                'developed_at' => null,
                'bom' => [
                    ['DEMO-ITM-004', 1, 'Overhead camera'],
                    ['DEMO-ITM-002', 1, 'Inference and UI'],
                    ['DEMO-ITM-009', 1, 'Tray vibration drive'],
                    ['DEMO-ITM-015', 1, 'Backlit tray'],
                    ['DEMO-ITM-014', 3, 'Camera stand'],
                ],
                'technologies' => [
                    'Python' => ['3.12', 'Counting application'],
                    'YOLO' => ['v8', 'Part detection model'],
                    'ONNX' => ['1.17', 'Model runtime on the Raspberry Pi'],
                ],
            ],
            'DEMO-INV-011' => [
                'name' => 'Greenhouse Climate Node',
                'category' => 'IoT',
                'type' => InnovationType::Hybrid,
                'tagline' => 'Keeps a greenhouse at the right temperature and humidity on its own.',
                'short_description' => 'Sensor and relay node that monitors climate and switches fans and misters automatically.',
                'description' => 'Each node reads temperature and humidity, switches ventilation and misting through relays, and publishes readings to a cloud dashboard for growers.',
                'problem_statement' => 'Growers adjusted fans and misters by hand and lost crops during hot afternoons.',
                'solution_description' => 'Nodes react within seconds to climate changes and alert growers when conditions drift out of range.',
                'key_features' => ['Automatic fan and mister control', 'Threshold alerts', 'Cloud history charts'],
                'benefits' => ['Healthier crops', 'Less manual monitoring'],
                'status' => InnovationStatus::Completed,
                'visibility' => InnovationVisibility::Public,
                'is_featured' => true,
                'developed_at' => '2026-08-05',
                'bom' => [
                    ['DEMO-ITM-007', 1, 'Node controller'],
                    ['DEMO-ITM-019', 2, 'Climate sensing'],
                    ['DEMO-ITM-018', 1, 'Fan and mister switching'],
                    ['DEMO-ITM-008', 1, 'Main power'],
                ],
                'technologies' => [
                    'MQTT' => ['5.0', 'Sensor readings and commands'],
                    'AWS' => [null, 'Dashboard and alerting'],
                    'Python' => ['3.12', 'Alerting rules'],
                ],
            ],
            'DEMO-INV-012' => [
                'name' => 'Waste Insights API',
                'category' => 'Software Systems',
                'type' => InnovationType::Software,
                'tagline' => 'Recycling data for partners, through one API.',
                'short_description' => 'Public API that exposes aggregated recycling and diversion figures to partners.',
                'description' => 'Waste Insights API publishes anonymised, aggregated data from SEB deployments so municipalities and partners can build their own reports.',
                'problem_statement' => 'Partners requested the same recycling figures repeatedly as spreadsheets.',
                'solution_description' => 'A versioned, token-secured API serves aggregated figures on demand.',
                'key_features' => ['Versioned REST endpoints', 'Partner API tokens', 'Aggregated, anonymised data'],
                'benefits' => ['No manual data exports', 'New partnership opportunities'],
                'status' => InnovationStatus::Development,
                'visibility' => InnovationVisibility::Internal,
                'is_featured' => false,
                'developed_at' => null,
                'bom' => [],
                'technologies' => [
                    'Laravel' => ['12', 'API application'],
                    'PHP' => ['8.4', 'Application runtime'],
                    'MySQL' => ['8.0', 'Aggregated data store'],
                ],
            ],
        ];
    }
}
