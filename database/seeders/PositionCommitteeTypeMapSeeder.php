<?php

namespace Database\Seeders;

use App\Enum\PositionScope;
use App\Models\CommitteeType;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionCommitteeTypeMapSeeder extends Seeder
{
    public function run(): void
    {
        $committeeTypeIds = CommitteeType::query()->pluck('id');

        if ($committeeTypeIds->isEmpty()) {
            $this->command->warn('Skipping PositionCommitteeTypeMapSeeder: no committee types found.');
            return;
        }

        $positions = Position::query()
            ->where('scope', PositionScope::CommitteeSpecific->value)
            ->get(['id']);

        foreach ($positions as $position) {
            $position->committeeTypes()->syncWithoutDetaching($committeeTypeIds->all());
        }

        $this->command->info('Committee-specific positions mapped to all committee types.');
    }
}