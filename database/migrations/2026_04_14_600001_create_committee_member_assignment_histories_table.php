<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_member_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('committee_member_assignment_id');
            $table->string('action', 30);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('committee_member_assignment_id', 'fk_cmah_assignment')
                ->references('id')->on('committee_member_assignments')->cascadeOnDelete();
            $table->foreign('changed_by', 'fk_cmah_changed_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(['committee_member_assignment_id', 'created_at'], 'idx_cmah_assignment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_member_assignment_histories');
    }
};
