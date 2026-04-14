<?php

namespace Database\Seeders;

use App\Enum\PositionCategory;
use App\Enum\PositionScope;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    private const DEFAULT_POSITIONS = [
        ['name' => 'President', 'code' => 'PRES', 'short_name' => 'Pres.', 'hierarchy_rank' => 1, 'display_order' => 1, 'category' => PositionCategory::Leadership->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Vice President', 'code' => 'VP', 'short_name' => 'VP', 'hierarchy_rank' => 2, 'display_order' => 2, 'category' => PositionCategory::Leadership->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'General Secretary', 'code' => 'GS', 'short_name' => 'GS', 'hierarchy_rank' => 3, 'display_order' => 3, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Joint Secretary', 'code' => 'JS', 'short_name' => 'JS', 'hierarchy_rank' => 4, 'display_order' => 4, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Organizing Secretary', 'code' => 'OS', 'short_name' => 'Org. Sec.', 'hierarchy_rank' => 5, 'display_order' => 5, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Office Secretary', 'code' => 'OFS', 'short_name' => 'Office Sec.', 'hierarchy_rank' => 6, 'display_order' => 6, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Treasurer', 'code' => 'TR', 'short_name' => 'Treas.', 'hierarchy_rank' => 7, 'display_order' => 7, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Member', 'code' => 'MEM', 'short_name' => 'Member', 'hierarchy_rank' => 100, 'display_order' => 100, 'category' => PositionCategory::General->value, 'scope' => PositionScope::Global->value, 'is_leadership' => false],
    ];

    public function run(): void
    {
        foreach (self::DEFAULT_POSITIONS as $item) {
            Position::updateOrCreate(
                ['name' => $item['name']],
                [
                    'code' => $item['code'],
                    'short_name' => $item['short_name'],
                    'hierarchy_rank' => $item['hierarchy_rank'],
                    'display_order' => $item['display_order'],
                    'category' => $item['category'],
                    'scope' => $item['scope'],
                    'is_leadership' => $item['is_leadership'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Default positions seeded: '.count(self::DEFAULT_POSITIONS));
    }
}
