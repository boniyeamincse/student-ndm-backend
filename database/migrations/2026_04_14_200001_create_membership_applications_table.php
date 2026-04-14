<?php

use App\Enum\ApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module 02 — Migration 2/4
 * Depends on: members, users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('application_no')->unique();

            $table->string('full_name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();

            // At least one required — enforced at validation layer only
            $table->string('mobile', 20)->nullable();
            $table->string('email')->nullable();

            $table->string('photo')->nullable();
            $table->string('national_id')->nullable();
            $table->string('student_id')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('occupation')->nullable();
            $table->string('educational_institution')->nullable();
            $table->string('department')->nullable();
            $table->string('academic_year', 20)->nullable();
            $table->string('address_line')->nullable();
            $table->string('village_area')->nullable();
            $table->string('post_office')->nullable();
            $table->string('union_name')->nullable();
            $table->string('upazila_name')->nullable();
            $table->string('district_name')->nullable();
            $table->string('division_name')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();

            // Referral — FK to members (already created in migration 1/4)
            $table->foreignId('reference_member_id')
                  ->nullable()->constrained('members')->nullOnDelete();

            $table->string('desired_committee_level')->nullable();
            $table->unsignedBigInteger('desired_committee_id')->nullable();
            $table->text('motivation')->nullable();

            $table->enum('status', array_column(ApplicationStatus::cases(), 'value'))
                  ->default(ApplicationStatus::Pending->value);

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->text('remarks')->nullable();
            $table->string('source', 50)->nullable();
            $table->string('submitted_ip', 45)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Unique indexes — MySQL 8 allows multiple NULL values in UNIQUE indexes
            $table->unique('mobile', 'idx_app_unique_mobile');
            $table->unique('email', 'idx_app_unique_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_applications');
    }
};
