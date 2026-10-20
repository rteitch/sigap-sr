<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Services\AuditLogger;
use App\Services\BrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(Request $request, BrandingService $branding): Response
    {
        $school = $request->user()->school;
        $activeYear = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->first();
        $levels = Level::query()
            ->where('school_id', $school->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_active']);

        return Inertia::render('Admin/Settings/Index', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'npsn' => $school->npsn,
                'level' => $school->level,
                'address' => $school->address,
                'phone' => $school->phone,
                'small_group_threshold' => (int) ($school->setting('small_group_threshold') ?? config('sigap.small_group_threshold')),
                'missing_student_escalation_minutes' => (int) ($school->setting('missing_student_escalation_minutes') ?? config('sigap.missing_student_escalation_minutes')),
                'academic_year' => $activeYear?->name ?? $school->setting('academic_year'),
                'app_name' => $school->setting('app_name') ?? config('sigap.branding.app_name'),
                'tagline' => $school->setting('tagline') ?? config('sigap.branding.tagline'),
                'primary_color' => $school->setting('primary_color') ?? config('sigap.branding.primary_color'),
                'logo_url' => $branding->for($school)['logo_url'],
            ],
            'levels' => $levels,
            'developer' => config('sigap.developer'),
            'license' => config('sigap.license'),
            'version' => config('sigap.app_version'),
        ]);
    }

    public function update(Request $request, BrandingService $branding): RedirectResponse
    {
        $school = $request->user()->school;
        $levelCodes = Level::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->pluck('code')
            ->all();

        if ($levelCodes === []) {
            $levelCodes = array_keys(config('sigap.levels'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'level' => ['required', Rule::in($levelCodes)],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'small_group_threshold' => ['required', 'integer', 'between:2,20'],
            'missing_student_escalation_minutes' => ['required', 'integer', 'between:15,1440'],
            'app_name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:140'],
            'primary_color' => ['required', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['boolean'],
        ]);

        $settings = $school->settings ?? [];
        $oldLogo = $settings['logo_path'] ?? null;

        if ($request->boolean('remove_logo') && $oldLogo) {
            Storage::disk('branding')->delete($oldLogo);
            $settings['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding', 'branding');

            if ($path !== false) {
                if ($oldLogo) {
                    Storage::disk('branding')->delete($oldLogo);
                }
                $settings['logo_path'] = $path;
            }
        }

        $settings = array_merge($settings, [
            'small_group_threshold' => (int) $validated['small_group_threshold'],
            'missing_student_escalation_minutes' => (int) $validated['missing_student_escalation_minutes'],
            'app_name' => $validated['app_name'],
            'tagline' => $validated['tagline'] ?? null,
            'primary_color' => $branding->normalizeColor($validated['primary_color']),
        ]);

        $school->update([
            'name' => $validated['name'],
            'npsn' => $validated['npsn'] ?? null,
            'level' => $validated['level'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'settings' => $settings,
        ]);

        AuditLogger::record('setting.updated', $school, 'Pengaturan sekolah, branding, dan ambang perlindungan diperbarui.');

        return back()->with('success', 'Pengaturan sekolah berhasil diperbarui.');
    }
}
