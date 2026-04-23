<?php

namespace Database\Seeders;

use App\Enum\PositionCategory;
use App\Enum\PositionScope;
use App\Models\Position;
use Illuminate\Database\Seeder;

class RequiredPoliticalPositionsSeeder extends Seeder
{
    private const REQUIRED_POSITIONS = [
        ['name' => 'President', 'bn_label' => 'সভাপতি', 'code' => 'PRES', 'short_name' => 'Pres.', 'hierarchy_rank' => 1, 'display_order' => 1, 'category' => PositionCategory::Leadership->value, 'is_leadership' => true],
        ['name' => 'Vice President', 'bn_label' => 'সহ-সভাপতি', 'code' => 'VP', 'short_name' => 'VP', 'hierarchy_rank' => 2, 'display_order' => 2, 'category' => PositionCategory::Leadership->value, 'is_leadership' => true],
        ['name' => 'General Secretary', 'bn_label' => 'সাধারণ সম্পাদক', 'code' => 'GS', 'short_name' => 'GS', 'hierarchy_rank' => 3, 'display_order' => 3, 'category' => PositionCategory::Leadership->value, 'is_leadership' => true],
        ['name' => 'Joint Secretary', 'bn_label' => 'যুগ্ম সম্পাদক', 'code' => 'JS', 'short_name' => 'JS', 'hierarchy_rank' => 4, 'display_order' => 4, 'category' => PositionCategory::Executive->value, 'is_leadership' => true],
        ['name' => 'Treasurer', 'bn_label' => 'কোষাধ্যক্ষ', 'code' => 'TR', 'short_name' => 'Treas.', 'hierarchy_rank' => 5, 'display_order' => 5, 'category' => PositionCategory::Executive->value, 'is_leadership' => true],
        ['name' => 'Deputy Treasurer', 'bn_label' => 'উপ-কোষাধ্যক্ষ', 'code' => 'DTR', 'short_name' => 'Dep. Treas.', 'hierarchy_rank' => 6, 'display_order' => 6, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Organizing Secretary', 'bn_label' => 'সাংগঠনিক সম্পাদক', 'code' => 'ORGS', 'short_name' => 'Org. Sec.', 'hierarchy_rank' => 7, 'display_order' => 7, 'category' => PositionCategory::Executive->value, 'is_leadership' => true],
        ['name' => 'Office Secretary', 'bn_label' => 'দফতর সম্পাদক', 'code' => 'OFS', 'short_name' => 'Office Sec.', 'hierarchy_rank' => 8, 'display_order' => 8, 'category' => PositionCategory::Executive->value, 'is_leadership' => true],
        ['name' => 'Publicity Secretary', 'bn_label' => 'প্রচার সম্পাদক', 'code' => 'PUBS', 'short_name' => 'Pub. Sec.', 'hierarchy_rank' => 9, 'display_order' => 9, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Legal Affairs Secretary', 'bn_label' => 'আইন বিষয়ক সম্পাদক', 'code' => 'LAS', 'short_name' => 'Legal Sec.', 'hierarchy_rank' => 10, 'display_order' => 10, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Education Affairs Secretary', 'bn_label' => 'শিক্ষা বিষয়ক সম্পাদক', 'code' => 'EDAS', 'short_name' => 'Edu. Sec.', 'hierarchy_rank' => 11, 'display_order' => 11, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Health Affairs Secretary', 'bn_label' => 'স্বাস্থ্য বিষয়ক সম্পাদক', 'code' => 'HAS', 'short_name' => 'Health Sec.', 'hierarchy_rank' => 12, 'display_order' => 12, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Agriculture Affairs Secretary', 'bn_label' => 'কৃষি বিষয়ক সম্পাদক', 'code' => 'AAS', 'short_name' => 'Agri. Sec.', 'hierarchy_rank' => 13, 'display_order' => 13, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Labor Affairs Secretary', 'bn_label' => 'শ্রম বিষয়ক সম্পাদক', 'code' => 'LABS', 'short_name' => 'Labor Sec.', 'hierarchy_rank' => 14, 'display_order' => 14, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Youth Affairs Secretary', 'bn_label' => 'যুব বিষয়ক সম্পাদক', 'code' => 'YAS', 'short_name' => 'Youth Sec.', 'hierarchy_rank' => 15, 'display_order' => 15, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Student Affairs Secretary', 'bn_label' => 'ছাত্র বিষয়ক সম্পাদক', 'code' => 'STAS', 'short_name' => 'Student Sec.', 'hierarchy_rank' => 16, 'display_order' => 16, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Women Affairs Secretary', 'bn_label' => 'মহিলা বিষয়ক সম্পাদক', 'code' => 'WAS', 'short_name' => 'Women Sec.', 'hierarchy_rank' => 17, 'display_order' => 17, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Religious Affairs Secretary', 'bn_label' => 'ধর্ম বিষয়ক সম্পাদক', 'code' => 'RAS', 'short_name' => 'Religious Sec.', 'hierarchy_rank' => 18, 'display_order' => 18, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Human Rights Secretary', 'bn_label' => 'মানবাধিকার বিষয়ক সম্পাদক', 'code' => 'HRS', 'short_name' => 'Rights Sec.', 'hierarchy_rank' => 19, 'display_order' => 19, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'International Affairs Secretary', 'bn_label' => 'আন্তর্জাতিক বিষয়ক সম্পাদক', 'code' => 'IAS', 'short_name' => 'Intl. Sec.', 'hierarchy_rank' => 20, 'display_order' => 20, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Information & Research Secretary', 'bn_label' => 'তথ্য ও গবেষণা বিষয়ক সম্পাদক', 'code' => 'IRS', 'short_name' => 'Info-Res Sec.', 'hierarchy_rank' => 21, 'display_order' => 21, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Relief & Rehabilitation Secretary', 'bn_label' => 'ত্রাণ ও পুনর্বাসন বিষয়ক সম্পাদক', 'code' => 'RRS', 'short_name' => 'Relief Sec.', 'hierarchy_rank' => 22, 'display_order' => 22, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Environment & Climate Secretary', 'bn_label' => 'পরিবেশ ও জলবায়ু বিষয়ক সম্পাদক', 'code' => 'ECS', 'short_name' => 'Env. Sec.', 'hierarchy_rank' => 23, 'display_order' => 23, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Science & Technology Secretary', 'bn_label' => 'বিজ্ঞান ও প্রযুক্তি বিষয়ক সম্পাদক', 'code' => 'STS', 'short_name' => 'Sci-Tech Sec.', 'hierarchy_rank' => 24, 'display_order' => 24, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Local Government Affairs Secretary', 'bn_label' => 'স্থানীয় সরকার বিষয়ক সম্পাদক', 'code' => 'LGAS', 'short_name' => 'LG Sec.', 'hierarchy_rank' => 25, 'display_order' => 25, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Assistant Secretary', 'bn_label' => 'সহ-সম্পাদক', 'code' => 'AS', 'short_name' => 'Asst. Sec.', 'hierarchy_rank' => 26, 'display_order' => 26, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Assistant Organizing Secretary', 'bn_label' => 'সহ-সাংগঠনিক সম্পাদক', 'code' => 'AOS', 'short_name' => 'Asst. Org.', 'hierarchy_rank' => 27, 'display_order' => 27, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Assistant Office Secretary', 'bn_label' => 'সহ-দফতর সম্পাদক', 'code' => 'AOFS', 'short_name' => 'Asst. Office', 'hierarchy_rank' => 28, 'display_order' => 28, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Assistant Publicity Secretary', 'bn_label' => 'সহ-প্রচার সম্পাদক', 'code' => 'APUB', 'short_name' => 'Asst. Pub.', 'hierarchy_rank' => 29, 'display_order' => 29, 'category' => PositionCategory::Executive->value, 'is_leadership' => false],
        ['name' => 'Executive Member', 'bn_label' => 'নির্বাহী সদস্য', 'code' => 'EXM', 'short_name' => 'Exec. Member', 'hierarchy_rank' => 30, 'display_order' => 30, 'category' => PositionCategory::General->value, 'is_leadership' => false],
    ];

    public function run(): void
    {
        foreach (self::REQUIRED_POSITIONS as $item) {
            Position::updateOrCreate(
                ['name' => $item['name']],
                [
                    'code' => $item['code'],
                    'short_name' => $item['short_name'],
                    'hierarchy_rank' => $item['hierarchy_rank'],
                    'display_order' => $item['display_order'],
                    'description' => 'বাংলা: '.$item['bn_label'],
                    'category' => $item['category'],
                    'scope' => PositionScope::CommitteeSpecific->value,
                    'is_leadership' => $item['is_leadership'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Required political positions synced: '.count(self::REQUIRED_POSITIONS));
    }
}
