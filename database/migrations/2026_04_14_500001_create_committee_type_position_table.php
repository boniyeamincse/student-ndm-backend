<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_type_position', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->foreignId('committee_type_id')->constrained('committee_types')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['position_id', 'committee_type_id'], 'uq_position_committee_type');
            $table->index(['committee_type_id'], 'idx_ctp_committee_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_type_position');
    }
};
