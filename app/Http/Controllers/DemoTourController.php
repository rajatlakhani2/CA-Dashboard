<?php

namespace App\Http\Controllers;

use App\Support\DemoWorkspace;
use Illuminate\Http\Request;

class DemoTourController extends Controller
{
    public function dismiss(Request $request)
    {
        $user = $request->user();
        abort_unless(DemoWorkspace::isDemoUser($user), 403);

        $user->forceFill(['demo_tour_completed_at' => now()])->save();
        $request->session()->put('demo_tour_dismissed', true);
        $request->session()->forget('demo_tour_pending');

        return response()->json(['ok' => true]);
    }

    public function complete(Request $request)
    {
        $user = $request->user();
        abort_unless(DemoWorkspace::isDemoUser($user), 403);

        $user->forceFill(['demo_tour_completed_at' => now()])->save();
        $request->session()->forget('demo_tour_pending');

        return response()->json(['ok' => true]);
    }

    public function reset(Request $request)
    {
        $user = $request->user();
        abort_unless(DemoWorkspace::isDemoUser($user), 403);

        $user->forceFill(['demo_tour_completed_at' => null])->save();
        $request->session()->forget('demo_tour_dismissed');
        $request->session()->put('demo_tour_pending', true);

        return redirect()->route('dashboard')->with('success', 'Product tour ready — welcome screen will appear now.');
    }
}
