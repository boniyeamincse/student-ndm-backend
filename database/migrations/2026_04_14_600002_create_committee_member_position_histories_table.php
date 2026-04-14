<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_member_position_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('committee_member_assignment_id');
            $table->unsignedBigInteger('old_position_id')->nullable();
            $table->unsignedBigInteger('new_position_id')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('committee_member_assignment_id', 'fk_cmph_assignment')
                ->references('id')->on('committee_member_assignments')->cascadeOnDelete();
            $table->foreign('old_position_id', 'fk_cmph_old_position')
                ->references('id')->on('positions')->nullOnDelete();
            $table->foreign('new_position_id', 'fk_cmph_new_position')
                ->references('id')->on('positions')->nullOnDelete();
            $table->foreign('changed_by', 'fk_cmph_changed_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(['committee_member_assignment_id', 'created_at'], 'idx_cmph_assignment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_member_position_histories');
    }
};
