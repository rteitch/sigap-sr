<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('default_confidentiality')->default('normal');
            $table->boolean('is_positive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('case_number')->unique();
            $table->foreignId('category_id')->constrained('incident_categories')->restrictOnDelete();
            $table->unsignedTinyInteger('severity')->default(1);
            $table->timestamp('occurred_at');
            $table->string('location');
            $table->text('description');
            $table->text('initial_action')->nullable();
            $table->boolean('needs_medical')->default(false);
            $table->string('status')->default('baru')->index();
            $table->string('confidentiality')->default('normal')->index();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('sanction')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->boolean('is_revision')->default(false);
            $table->foreignId('revision_of')->nullable()->constrained('incidents')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'occurred_at']);
            $table->index(['school_id', 'severity']);
        });

        Schema::create('incident_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['incident_id', 'student_id', 'role']);
        });

        Schema::create('incident_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_done')->default(false);
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('done_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('incident_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('week');
            $table->date('due_at');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_followups');
        Schema::dropIfExists('incident_tasks');
        Schema::dropIfExists('incident_students');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('incident_categories');
    }
};
