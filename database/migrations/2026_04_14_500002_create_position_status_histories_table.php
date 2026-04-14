<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->boolean('old_active_state')->nullable();
            $table->boolean('new_active_state');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['position_id', 'created_at'], 'idx_position_status_history');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_status_histories');
    }
};
