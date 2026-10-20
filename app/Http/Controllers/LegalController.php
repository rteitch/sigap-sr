<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function privacy(Request $request): Response
    {
        return Inertia::render('Legal/Privacy', [
            'updatedAt' => '2026-10-20',
        ]);
    }

    public function terms(Request $request): Response
    {
        return Inertia::render('Legal/Terms', [
            'updatedAt' => '2026-10-20',
        ]);
    }
}
