<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->unsignedInteger('total_points')->default(0);
            $table->string('current_rank', 50)->default('rookie');
            $table->timestamps();

            $table->index(['current_rank', 'total_points']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_points');
    }
};
