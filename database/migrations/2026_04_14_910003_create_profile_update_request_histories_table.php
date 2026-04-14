<?php

use App\Enum\ProfileUpdateRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_update_request_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_update_request_id')
                ->constrained('profile_update_requests', 'id', 'pur_hist_req_fk')
                ->cascadeOnDelete();
            $table->enum('old_status', array_column(ProfileUpdateRequestStatus::cases(), 'value'))->nullable();
            $table->enum('new_status', array_column(ProfileUpdateRequestStatus::cases(), 'value'));
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users', 'id', 'pur_hist_changed_by_fk')
                ->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_update_request_histories');
    }
};
