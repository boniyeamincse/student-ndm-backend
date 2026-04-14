<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_member_assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('assignment_no', 30)->unique();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('committee_id')->constrained('committees')->cascadeOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('assignment_type', 30);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_leadership')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('appointed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 30)->default('active');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('member_id', 'idx_cma_member');
            $table->index('committee_id', 'idx_cma_committee');
            $table->index('position_id', 'idx_cma_position');
            $table->index('status', 'idx_cma_status');
            $table->index('is_active', 'idx_cma_is_active');
            $table->index('start_date', 'idx_cma_start_date');
            $table->index('end_date', 'idx_cma_end_date');
            $table->index(['member_id', 'committee_id', 'position_id', 'is_active'], 'idx_cma_dup_guard');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_member_assignments');
    }
};
