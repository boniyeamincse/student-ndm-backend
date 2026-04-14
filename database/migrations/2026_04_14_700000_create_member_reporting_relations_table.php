<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_reporting_relations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('relation_no', 30)->unique();
            $table->unsignedBigInteger('subordinate_assignment_id');
            $table->unsignedBigInteger('superior_assignment_id');
            $table->unsignedBigInteger('committee_id')->nullable();
            $table->string('relation_type', 30);
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subordinate_assignment_id', 'fk_mrr_sub_assignment')
                ->references('id')->on('committee_member_assignments')->cascadeOnDelete();
            $table->foreign('superior_assignment_id', 'fk_mrr_sup_assignment')
                ->references('id')->on('committee_member_assignments')->cascadeOnDelete();
            $table->foreign('committee_id', 'fk_mrr_committee')
                ->references('id')->on('committees')->nullOnDelete();
            $table->foreign('created_by', 'fk_mrr_created_by')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'fk_mrr_updated_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('subordinate_assignment_id', 'idx_mrr_sub_assignment');
            $table->index('superior_assignment_id', 'idx_mrr_sup_assignment');
            $table->index('committee_id', 'idx_mrr_committee');
            $table->index('relation_type', 'idx_mrr_relation_type');
            $table->index('is_primary', 'idx_mrr_is_primary');
            $table->index('is_active', 'idx_mrr_is_active');
            $table->index('start_date', 'idx_mrr_start_date');
            $table->index('end_date', 'idx_mrr_end_date');
            $table->index(['subordinate_assignment_id', 'is_active', 'relation_type', 'is_primary'], 'idx_mrr_primary_guard');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_reporting_relations');
    }
};
