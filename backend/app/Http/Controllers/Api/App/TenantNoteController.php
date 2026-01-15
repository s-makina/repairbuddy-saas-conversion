<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\TenantNote;
use Illuminate\Http\Request;

class TenantNoteController extends Controller
{
    public function index()
    {
        return response()->json([
            'notes' => TenantNote::query()->orderBy('id', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ]);

        $note = TenantNote::query()->create($validated);

        return response()->json([
            'note' => $note,
        ], 201);
    }
}
