<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkspaceOnboardingController extends Controller
{
    public function dismiss(Request $request)
    {
        session(['onboarding_dismissed' => true]);

        return redirect()->route('dashboard');
    }
}
