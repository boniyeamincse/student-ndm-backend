<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['division_name', 'district_name', 'upazila_name', 'union_name']);
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->constrained('upazilas')->nullOnDelete();
            $table->foreignId('union_id')->nullable()->constrained('unions')->nullOnDelete();
        });

        Schema::table('committees', function (Blueprint $table) {
            $table->dropIndex('idx_committee_location');
            $table->dropColumn(['division_name', 'district_name', 'upazila_name', 'union_name']);
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->constrained('upazilas')->nullOnDelete();
            $table->foreignId('union_id')->nullable()->constrained('unions')->nullOnDelete();
            
            $table->index(['division_id', 'district_id', 'upazila_id', 'union_id'], 'idx_committee_loc_id');
        });

        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropColumn(['division_name', 'district_name', 'upazila_name', 'union_name']);
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->constrained('upazilas')->nullOnDelete();
            $table->foreignId('union_id')->nullable()->constrained('unions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['upazila_id']);
            $table->dropForeign(['union_id']);
            $table->dropColumn(['division_id', 'district_id', 'upazila_id', 'union_id']);
            
            $table->string('division_name')->nullable();
            $table->string('district_name')->nullable();
            $table->string('upazila_name')->nullable();
            $table->string('union_name')->nullable();
        });

        Schema::table('committees', function (Blueprint $table) {
            $table->dropIndex('idx_committee_loc_id');
            $table->dropForeign(['division_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['upazila_id']);
            $table->dropForeign(['union_id']);
            $table->dropColumn(['division_id', 'district_id', 'upazila_id', 'union_id']);

            $table->string('division_name', 100)->nullable();
            $table->string('district_name', 100)->nullable();
            $table->string('upazila_name', 100)->nullable();
            $table->string('union_name', 100)->nullable();
            $table->index(['division_name', 'district_name', 'upazila_name', 'union_name'], 'idx_committee_location');
        });

        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['upazila_id']);
            $table->dropForeign(['union_id']);
            $table->dropColumn(['division_id', 'district_id', 'upazila_id', 'union_id']);

            $table->string('division_name')->nullable();
            $table->string('district_name')->nullable();
            $table->string('upazila_name')->nullable();
            $table->string('union_name')->nullable();
        });
    }
};
