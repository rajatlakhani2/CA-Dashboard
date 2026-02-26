<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = Activity::with('causer', 'subject')
            ->latest()
            ->paginate(50);

        return view('activity.index', compact('activities'));
    }
}
