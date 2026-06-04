<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class PartnerDashboardController extends Controller
{
    /** Legacy URL — firm overview lives on the main dashboard (Firm tab). */
    public function index(): RedirectResponse
    {
        abort_unless(auth()->user()?->isPartner(), 403);

        return redirect()->route('dashboard', ['tab' => 'firm']);
    }
}
