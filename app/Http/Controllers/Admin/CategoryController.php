<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IncidentCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = IncidentCategory::query()
            ->where('school_id', $request->user()->school_id)
            ->withCount('incidents')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (IncidentCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'default_confidentiality' => $category->default_confidentiality,
                'is_positive' => $category->is_positive,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
                'incidents_count' => $category->incidents_count,
            ]);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['school_id'] = $request->user()->school_id;
        $validated['sort_order'] ??= (int) IncidentCategory::where('school_id', $request->user()->school_id)->max('sort_order') + 1;

        $category = IncidentCategory::create($validated);
        AuditLogger::record('category.created', $category, 'Kategori insiden ditambahkan.');

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, IncidentCategory $category): RedirectResponse
    {
        abort_unless($category->school_id === $request->user()->school_id, 404);
        $category->update($this->validated($request));
        AuditLogger::record('category.updated', $category, 'Kategori insiden diperbarui.');

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'default_confidentiality' => ['required', Rule::in(array_keys(config('sigap.confidentialities')))],
            'is_positive' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
