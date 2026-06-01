<?php

namespace App\Http\Controllers;

use App\Services\CommandPaletteBuilder;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function palette(CommandPaletteBuilder $builder)
    {
        return response()->json($builder->defaults(auth()->user()));
    }

    public function globalSearch(Request $request, CommandPaletteBuilder $builder)
    {
        $query = trim((string) $request->input('query', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $grouped = $builder->search(auth()->user(), $query);
        $flat = [];

        foreach ($grouped as $group) {
            foreach ($group['items'] as $item) {
                $flat[] = $item;
            }
        }

        return response()->json($flat);
    }
}
