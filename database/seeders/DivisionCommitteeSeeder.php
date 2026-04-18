<?php

namespace Database\Seeders;

use App\Models\Committee;
use App\Models\CommitteeType;
use App\Models\Division;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DivisionCommitteeSeeder extends Seeder
{
    private function upsertCommitteeBySlug(string $slug, array $attributes): Committee
    {
        $committee = Committee::withTrashed()->firstOrNew(['slug' => $slug]);
        $committee->fill($attributes);
        $committee->save();

        if ($committee->trashed()) {
            $committee->restore();
        }

        return $committee;
    }

    public function run(): void
    {
        $centralType = CommitteeType::query()->where('slug', 'central')->first();
        $divisionType = CommitteeType::query()->where('slug', 'division')->first();

        if (! $centralType || ! $divisionType) {
            $this->command->warn('Skipping DivisionCommitteeSeeder: required committee types not found.');
            return;
        }

        $central = $this->upsertCommitteeBySlug('central-committee', [
            'committee_no' => 'COM-CEN-000',
            'name' => 'Central Committee',
            'committee_type_id' => $centralType->id,
            'code' => 'CEN',
            'status' => 'active',
            'is_current' => true,
        ]);

        $divisions = Division::query()->orderBy('id')->get(['id', 'name_en']);

        foreach ($divisions as $division) {
            $slug = Str::slug($division->name_en . ' Division Committee');
            $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $division->name_en), 0, 3));

            $this->upsertCommitteeBySlug($slug, [
                'committee_no' => sprintf('COM-DIV-%03d', (int) $division->id),
                'name' => sprintf('%s Division Committee', $division->name_en),
                'committee_type_id' => $divisionType->id,
                'parent_id' => $central->id,
                'code' => $code ?: 'DIV',
                'division_id' => $division->id,
                'status' => 'active',
                'is_current' => true,
            ]);
        }

        $this->command->info('Division committees seeded for all divisions.');
    }
}
