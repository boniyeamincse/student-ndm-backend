<?php

use App\Enum\ProfileVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->string('language', 20)->nullable();
            $table->string('timezone', 100)->nullable();
            $table->boolean('notification_email_enabled')->default(true);
            $table->boolean('notification_sms_enabled')->default(false);
            $table->boolean('notification_push_enabled')->default(false);
            $table->enum('profile_visibility', array_column(ProfileVisibility::cases(), 'value'))->default(ProfileVisibility::MembersOnly->value);
            $table->boolean('show_email')->default(false);
            $table->boolean('show_phone')->default(false);
            $table->boolean('show_address')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_settings');
    }
};
