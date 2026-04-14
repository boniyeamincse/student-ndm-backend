<?php

namespace Database\Seeders;

use App\Models\CommitteeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommitteeTypeSeeder extends Seeder
{
    private const DEFAULT_TYPES = [
        ['name' => 'Central', 'code' => 'CEN', 'hierarchy_order' => 1, 'description' => 'Top-level central committee.'],
        ['name' => 'Division', 'code' => 'DIV', 'hierarchy_order' => 2, 'description' => 'Division-level committee.'],
        ['name' => 'District', 'code' => 'DIS', 'hierarchy_order' => 3, 'description' => 'District-level committee.'],
        ['name' => 'Upazila', 'code' => 'UPA', 'hierarchy_order' => 4, 'description' => 'Upazila-level committee.'],
        ['name' => 'Union', 'code' => 'UNI', 'hierarchy_order' => 5, 'description' => 'Union-level committee.'],
    ];

    public function run(): void
    {
        foreach (self::DEFAULT_TYPES as $item) {
            CommitteeType::updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'code' => $item['code'],
                    'hierarchy_order' => $item['hierarchy_order'],
                    'description' => $item['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Committee types seeded: '.count(self::DEFAULT_TYPES));
    }
}
