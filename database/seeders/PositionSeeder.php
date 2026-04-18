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
        ['name' => 'Executive President', 'code' => 'EXPR', 'short_name' => 'Exec. Pres.', 'hierarchy_rank' => 2, 'display_order' => 2, 'category' => PositionCategory::Leadership->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Senior Vice President', 'code' => 'SVP', 'short_name' => 'Sr. VP', 'hierarchy_rank' => 3, 'display_order' => 3, 'category' => PositionCategory::Leadership->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Vice President', 'code' => 'VP', 'short_name' => 'VP', 'hierarchy_rank' => 4, 'display_order' => 4, 'category' => PositionCategory::Leadership->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],

        ['name' => 'General Secretary', 'code' => 'GS', 'short_name' => 'GS', 'hierarchy_rank' => 5, 'display_order' => 5, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Senior Joint Secretary', 'code' => 'SJS', 'short_name' => 'Sr. JS', 'hierarchy_rank' => 6, 'display_order' => 6, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Joint Secretary', 'code' => 'JS', 'short_name' => 'JS', 'hierarchy_rank' => 7, 'display_order' => 7, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Organizing Secretary', 'code' => 'ORGS', 'short_name' => 'Org. Sec.', 'hierarchy_rank' => 8, 'display_order' => 8, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Deputy Organizing Secretary', 'code' => 'DORG', 'short_name' => 'Dep. Org.', 'hierarchy_rank' => 9, 'display_order' => 9, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],

        ['name' => 'Treasurer', 'code' => 'TR', 'short_name' => 'Treas.', 'hierarchy_rank' => 10, 'display_order' => 10, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Office Secretary', 'code' => 'OFS', 'short_name' => 'Office Sec.', 'hierarchy_rank' => 11, 'display_order' => 11, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],
        ['name' => 'Deputy Office Secretary', 'code' => 'DOFS', 'short_name' => 'Dep. Office', 'hierarchy_rank' => 12, 'display_order' => 12, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => true],

        ['name' => 'Publicity Secretary', 'code' => 'PUBS', 'short_name' => 'Pub. Sec.', 'hierarchy_rank' => 13, 'display_order' => 13, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Deputy Publicity Secretary', 'code' => 'DPUB', 'short_name' => 'Dep. Pub.', 'hierarchy_rank' => 14, 'display_order' => 14, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Media & Press Secretary', 'code' => 'MPS', 'short_name' => 'Media Sec.', 'hierarchy_rank' => 15, 'display_order' => 15, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'IT / ICT Affairs Secretary', 'code' => 'ICTS', 'short_name' => 'ICT Sec.', 'hierarchy_rank' => 16, 'display_order' => 16, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],

        ['name' => 'Membership Secretary', 'code' => 'MS', 'short_name' => 'Member Sec.', 'hierarchy_rank' => 17, 'display_order' => 17, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Training Affairs Secretary', 'code' => 'TAS', 'short_name' => 'Training Sec.', 'hierarchy_rank' => 18, 'display_order' => 18, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Research & Planning Secretary', 'code' => 'RPS', 'short_name' => 'Research Sec.', 'hierarchy_rank' => 19, 'display_order' => 19, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],

        ['name' => 'Legal Affairs Secretary', 'code' => 'LAS', 'short_name' => 'Legal Sec.', 'hierarchy_rank' => 20, 'display_order' => 20, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Disciplinary Affairs Secretary', 'code' => 'DIAS', 'short_name' => 'Disciplinary', 'hierarchy_rank' => 21, 'display_order' => 21, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],

        ['name' => 'International Affairs Secretary', 'code' => 'IAS', 'short_name' => 'Intl. Sec.', 'hierarchy_rank' => 22, 'display_order' => 22, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Expatriate Affairs Secretary', 'code' => 'EAS', 'short_name' => 'Expat. Sec.', 'hierarchy_rank' => 23, 'display_order' => 23, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],

        ['name' => 'Education Affairs Secretary', 'code' => 'EDAS', 'short_name' => 'Edu. Sec.', 'hierarchy_rank' => 24, 'display_order' => 24, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Health Affairs Secretary', 'code' => 'HAS', 'short_name' => 'Health Sec.', 'hierarchy_rank' => 25, 'display_order' => 25, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Youth Affairs Secretary', 'code' => 'YAS', 'short_name' => 'Youth Sec.', 'hierarchy_rank' => 26, 'display_order' => 26, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Student Affairs Secretary', 'code' => 'STAS', 'short_name' => 'Student Sec.', 'hierarchy_rank' => 27, 'display_order' => 27, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Women Affairs Secretary', 'code' => 'WAS', 'short_name' => 'Women Sec.', 'hierarchy_rank' => 28, 'display_order' => 28, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Labor Affairs Secretary', 'code' => 'LABS', 'short_name' => 'Labor Sec.', 'hierarchy_rank' => 29, 'display_order' => 29, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Cultural Affairs Secretary', 'code' => 'CAS', 'short_name' => 'Cultural Sec.', 'hierarchy_rank' => 30, 'display_order' => 30, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Religious Affairs Secretary', 'code' => 'RAS', 'short_name' => 'Religious Sec.', 'hierarchy_rank' => 31, 'display_order' => 31, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Environment & Climate Affairs Secretary', 'code' => 'ECAS', 'short_name' => 'Env. Sec.', 'hierarchy_rank' => 32, 'display_order' => 32, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],

        ['name' => 'Relief & Disaster Management Secretary', 'code' => 'RDMS', 'short_name' => 'Relief Sec.', 'hierarchy_rank' => 33, 'display_order' => 33, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Science & Technology Affairs Secretary', 'code' => 'STCS', 'short_name' => 'Sci-Tech Sec.', 'hierarchy_rank' => 34, 'display_order' => 34, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Agriculture Affairs Secretary', 'code' => 'AAS', 'short_name' => 'Agri. Sec.', 'hierarchy_rank' => 35, 'display_order' => 35, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Industry & Commerce Affairs Secretary', 'code' => 'ICAS', 'short_name' => 'Industry Sec.', 'hierarchy_rank' => 36, 'display_order' => 36, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],

        ['name' => 'Deputy Secretary', 'code' => 'DS', 'short_name' => 'Dep. Sec.', 'hierarchy_rank' => 37, 'display_order' => 37, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
        ['name' => 'Assistant Secretary', 'code' => 'AS', 'short_name' => 'Asst. Sec.', 'hierarchy_rank' => 38, 'display_order' => 38, 'category' => PositionCategory::Executive->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],

        ['name' => 'Executive Member', 'code' => 'EXM', 'short_name' => 'Exec. Member', 'hierarchy_rank' => 39, 'display_order' => 39, 'category' => PositionCategory::General->value, 'scope' => PositionScope::CommitteeSpecific->value, 'is_leadership' => false],
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
