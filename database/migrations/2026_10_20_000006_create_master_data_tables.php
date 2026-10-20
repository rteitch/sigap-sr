<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 20);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });

        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 80);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'code']);
        });

        Schema::create('incident_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('level_id')->nullable()->after('level')->constrained('levels')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->after('academic_year')->constrained('academic_years')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('academic_year_id');
        });

        Schema::table('dorms', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('capacity');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->foreignId('incident_location_id')->nullable()->after('location')->constrained('incident_locations')->nullOnDelete();
        });

        $this->backfillExistingSchools();
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('incident_location_id');
        });

        Schema::table('dorms', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('level_id');
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropColumn('is_active');
        });

        Schema::dropIfExists('incident_locations');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('academic_years');
    }

    private function backfillExistingSchools(): void
    {
        $now = now();

        foreach (DB::table('schools')->orderBy('id')->get() as $school) {
            $settings = json_decode((string) ($school->settings ?? '{}'), true) ?: [];
            $yearName = trim((string) ($settings['academic_year'] ?? '')) ?: now()->format('Y').'/'.now()->addYear()->format('Y');

            $levelIds = [];
            foreach (array_values(config('sigap.levels', [])) as $index => $label) {
                $code = (string) $label;
                $levelIds[$code] = DB::table('levels')->insertGetId([
                    'school_id' => $school->id,
                    'code' => $code,
                    'name' => $code,
                    'sort_order' => $index,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $academicYearId = DB::table('academic_years')->insertGetId([
                'school_id' => $school->id,
                'name' => $yearName,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('classrooms')
                ->where('school_id', $school->id)
                ->orderBy('id')
                ->get()
                ->each(function (object $classroom) use ($levelIds, $academicYearId) {
                    DB::table('classrooms')->where('id', $classroom->id)->update([
                        'level_id' => $levelIds[$classroom->level] ?? reset($levelIds) ?: null,
                        'academic_year_id' => $academicYearId,
                        'is_active' => true,
                    ]);
                });

            foreach (array_values(config('sigap.locations', [])) as $index => $location) {
                DB::table('incident_locations')->insert([
                    'school_id' => $school->id,
                    'name' => $location,
                    'sort_order' => $index,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
