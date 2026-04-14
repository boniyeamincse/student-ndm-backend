<?php

use App\Enum\MemberStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 02 — Migration 1/4
 * Members table created FIRST because membership_applications.reference_member_id references it.
 * The reverse FK (members.membership_application_id) is added in migration 4/4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')
                  ->nullable()->unique()
                  ->constrained('users')->nullOnDelete();

            // Plain integer — FK constraint added after membership_applications exists (migration 4/4)
            $table->unsignedBigInteger('membership_application_id')->nullable()->unique();

            $table->string('member_no')->unique();

            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('photo')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('educational_institution')->nullable();
            $table->string('department')->nullable();
            $table->string('academic_year', 20)->nullable();
            $table->string('occupation')->nullable();
            $table->string('address_line')->nullable();
            $table->string('village_area')->nullable();
            $table->string('post_office')->nullable();
            $table->string('union_name')->nullable();
            $table->string('upazila_name')->nullable();
            $table->string('district_name')->nullable();
            $table->string('division_name')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();

            $table->enum('status', array_column(MemberStatus::cases(), 'value'))
                  ->default(MemberStatus::Active->value);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
