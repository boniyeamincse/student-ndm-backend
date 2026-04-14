<?php

namespace Database\Seeders;

use App\Models\PostCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostCategorySeeder extends Seeder
{
    private const DEFAULTS = [
        ['name' => 'News', 'color' => '#1d4ed8'],
        ['name' => 'Notice', 'color' => '#b45309'],
        ['name' => 'Statement', 'color' => '#0f766e'],
        ['name' => 'Campaign', 'color' => '#7c3aed'],
        ['name' => 'Event', 'color' => '#be123c'],
        ['name' => 'Press Release', 'color' => '#334155'],
        ['name' => 'Blog', 'color' => '#166534'],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $item) {
            PostCategory::firstOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'color' => $item['color'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Default post categories seeded.');
    }
}
