<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('committee_no', 30)->unique();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->foreignId('committee_type_id')->constrained('committee_types');
            $table->foreignId('parent_id')->nullable()->constrained('committees')->nullOnDelete();
            $table->string('code', 30)->nullable();
            $table->string('division_name', 100)->nullable();
            $table->string('district_name', 100)->nullable();
            $table->string('upazila_name', 100)->nullable();
            $table->string('union_name', 100)->nullable();
            $table->string('address_line', 255)->nullable();
            $table->string('office_phone', 30)->nullable();
            $table->string('office_email', 150)->nullable();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 30)->default('active');
            $table->boolean('is_current')->default(true);
            $table->foreignId('formed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('formed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['committee_type_id', 'status'], 'idx_committee_type_status');
            $table->index(['division_name', 'district_name', 'upazila_name', 'union_name'], 'idx_committee_location');
            $table->index(['parent_id'], 'idx_committee_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committees');
    }
};
