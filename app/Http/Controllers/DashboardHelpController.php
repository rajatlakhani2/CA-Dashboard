<?php

namespace App\Http\Controllers;

use App\Services\DashboardHelpChatService;
use Illuminate\Http\Request;

class DashboardHelpController extends Controller
{
    public function chat(Request $request, DashboardHelpChatService $help)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'page' => 'nullable|string|max:500',
        ]);

        return response()->json($help->reply(
            $request->user(),
            $validated['message'],
            $validated['page'] ?? null,
        ));
    }
}
