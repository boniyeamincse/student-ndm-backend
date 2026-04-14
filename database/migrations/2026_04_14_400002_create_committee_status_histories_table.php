<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('committees')->cascadeOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['committee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_status_histories');
    }
};
