<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Dorm;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\Level;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterDataApiController extends Controller
{
    public function academicYears(Request $request): JsonResponse
    {
        $rows = AcademicYear::query()
            ->where('school_id', $this->school($request)->id)
            ->orderByDesc('name')
            ->get()
            ->map(fn (AcademicYear $year) => [
                'id' => $year->id,
                'name' => $year->name,
                'start_date' => $year->start_date?->toDateString(),
                'end_date' => $year->end_date?->toDateString(),
                'is_active' => $year->is_active,
            ]);

        return $this->collection($rows);
    }

    public function levels(Request $request): JsonResponse
    {
        $rows = Level::query()
            ->where('school_id', $this->school($request)->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Level $level) => [
                'id' => $level->id,
                'code' => $level->code,
                'name' => $level->name,
                'sort_order' => $level->sort_order,
                'is_active' => $level->is_active,
            ]);

        return $this->collection($rows);
    }

    public function classrooms(Request $request): JsonResponse
    {
        $rows = Classroom::query()
            ->where('school_id', $this->school($request)->id)
            ->where('is_active', true)
            ->with(['levelInfo:id,code,name', 'academicYear:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Classroom $classroom) => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'level' => [
                    'id' => $classroom->levelInfo?->id,
                    'code' => $classroom->levelInfo?->code,
                    'name' => $classroom->levelInfo?->name,
                ],
                'academic_year' => $classroom->academicYear ? [
                    'id' => $classroom->academicYear->id,
                    'name' => $classroom->academicYear->name,
                ] : null,
                'is_active' => $classroom->is_active,
            ]);

        return $this->collection($rows);
    }

    public function dorms(Request $request): JsonResponse
    {
        $rows = Dorm::query()
            ->where('school_id', $this->school($request)->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Dorm $dorm) => [
                'id' => $dorm->id,
                'name' => $dorm->name,
                'gender' => $dorm->gender,
                'capacity' => $dorm->capacity,
                'is_active' => $dorm->is_active,
            ]);

        return $this->collection($rows);
    }

    public function categories(Request $request): JsonResponse
    {
        $school = $this->school($request);

        $rows = IncidentCategory::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('school_id')->orWhere('school_id', $school->id))
            ->orderBy('sort_order')
            ->get()
            ->map(fn (IncidentCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'is_positive' => (bool) $category->is_positive,
                'default_confidentiality' => $category->default_confidentiality,
            ]);

        return $this->collection($rows);
    }

    public function locations(Request $request): JsonResponse
    {
        $rows = IncidentLocation::query()
            ->where('school_id', $this->school($request)->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (IncidentLocation $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'sort_order' => $location->sort_order,
            ]);

        return $this->collection($rows);
    }

    private function school(Request $request): School
    {
        /** @var School $school */
        $school = $request->attributes->get('api_school');

        return $school;
    }

    private function collection($rows): JsonResponse
    {
        return response()->json([
            'data' => $rows->values(),
            'meta' => [
                'count' => $rows->count(),
                'generated_at' => now()->toIso8601String(),
            ],
        ])->header('Cache-Control', 'no-store');
    }
}
