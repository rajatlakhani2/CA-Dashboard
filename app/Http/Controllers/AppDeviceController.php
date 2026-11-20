<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use Illuminate\Http\Request;

/** The Android app registers its push-notification token here after sign-in. */
class AppDeviceController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['nullable', 'string', 'in:android,ios'],
            'app_version' => ['nullable', 'string', 'max:40'],
        ]);

        $user = $request->user();

        UserDevice::updateOrCreate(
            ['token_hash' => UserDevice::hashToken($data['token'])],
            [
                'token' => $data['token'],
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'platform' => $data['platform'] ?? 'android',
                'app_version' => $data['app_version'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['ok' => true]);
    }

    /** Called on sign-out so a shared phone stops getting this user's notifications. */
    public function destroy(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:500']]);

        UserDevice::where('token_hash', UserDevice::hashToken($data['token']))
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
