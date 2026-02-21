<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['message' => 'Unauthenticated'], 401);

        return Assignment::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['message' => 'Unauthenticated'], 401);

        $validated = $request->validate([
            'description' => 'required|string',
            'additional_notes' => 'nullable|string',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'steps' => 'nullable',
            'image_url' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $steps = $request->input('steps', []);
        if (is_string($steps)) {
            $decoded = json_decode($steps, true);
            $steps = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($steps)) $steps = [];

        $assignment = Assignment::create([
            'user_id' => $userId,
            'description' => $validated['description'],
            'additional_notes' => $validated['additional_notes'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'due_time' => $validated['due_time'] ?? null,
            'steps' => $steps, // ✅ with casts, store as json automatically
            'image_url' => $validated['image_url'] ?? null,
            'status' => $validated['status'] ?? 'in_progress',
        ]);

        return response()->json($assignment, 201);
    }

    public function update(Request $request, $id)
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['message' => 'Unauthenticated'], 401);

        $assignment = Assignment::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $steps = $request->input('steps', null);
        if (is_string($steps)) {
            $decoded = json_decode($steps, true);
            $steps = is_array($decoded) ? $decoded : [];
        }

        $assignment->update([
            'description' => $request->input('description', $assignment->description),
            'additional_notes' => $request->input('additional_notes', $assignment->additional_notes),
            'due_date' => $request->input('due_date', $assignment->due_date),
            'due_time' => $request->input('due_time', $assignment->due_time),
            'steps' => $steps === null ? $assignment->steps : (is_array($steps) ? $steps : []),
            'image_url' => $request->input('image_url', $assignment->image_url),
            'status' => $request->input('status', $assignment->status),
        ]);

        return response()->json($assignment);
    }

    public function destroy($id)
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['message' => 'Unauthenticated'], 401);

        $assignment = Assignment::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $assignment->delete();

        return response()->json(['success' => true]);
    }
}
