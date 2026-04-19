<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Division;
use App\Models\Union;
use App\Models\Upazila;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GeographySeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks for truncation
        Schema::disableForeignKeyConstraints();
        Union::truncate();
        Upazila::truncate();
        District::truncate();
        Division::truncate();
        Schema::enableForeignKeyConstraints();

        $path = database_path('data/bd-geo.json');
        if (!file_exists($path)) {
            $this->command->error("Geo data file not found at: $path");
            return;
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        if (!$data) {
            $this->command->error("Failed to decode geo JSON.");
            return;
        }

        $this->command->info('Seeding Geography data from JSON...');

        foreach ($data as $divData) {
            // Create Division
            Division::create([
                'id'      => $divData['id'],
                'name_en' => $divData['name'],
                'name_bn' => $divData['bn'] ?? $divData['name'],
            ]);

            if (isset($divData['districts'])) {
                foreach ($divData['districts'] as $distData) {
                    // Create District
                    District::create([
                        'id'          => $distData['id'],
                        'division_id' => $divData['id'],
                        'name_en'     => $distData['name'],
                        'name_bn'     => $distData['bn'] ?? $distData['name'],
                    ]);

                    if (isset($distData['upazilas'])) {
                        foreach ($distData['upazilas'] as $upzData) {
                            // Create Upazila
                            Upazila::create([
                                'id'          => $upzData['id'],
                                'district_id' => $distData['id'],
                                'name_en'     => $upzData['name'],
                                'name_bn'     => $upzData['bn'] ?? $upzData['name'],
                            ]);

                            if (isset($upzData['unions'])) {
                                $unions = [];
                                foreach ($upzData['unions'] as $unionData) {
                                    $unions[] = [
                                        'id'          => $unionData['id'],
                                        'upazila_id'  => $upzData['id'],
                                        'name_en'     => $unionData['name'],
                                        'name_bn'     => $unionData['bn'] ?? $unionData['name'],
                                        'created_at'  => now(),
                                        'updated_at'  => now(),
                                    ];
                                }
                                // Batch insert unions to improve performance
                                if (!empty($unions)) {
                                    DB::table('unions')->insert($unions);
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->command->info('Geography seeding completed successfully!');
    }
}
