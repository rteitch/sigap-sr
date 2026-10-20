<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dorm_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('gender', 1);
            $table->date('birth_date')->nullable();
            $table->date('entry_date')->nullable();
            $table->string('status')->default('aktif')->index();
            $table->text('special_needs')->nullable();
            $table->unsignedTinyInteger('socioeconomic_desil')->nullable();
            $table->timestamp('consent_given_at')->nullable();
            $table->string('consent_scope')->nullable();
            $table->string('consent_document')->nullable();
            $table->timestamp('consent_withdrawn_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'classroom_id']);
            $table->index(['school_id', 'dorm_id']);
        });

        Schema::create('student_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->string('birth_place')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('family_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relation')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('can_pickup')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_contacts');
        Schema::dropIfExists('student_identities');
        Schema::dropIfExists('students');
    }
};
