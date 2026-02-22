<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

class DebugController extends Controller
{
    /**
     * Return the last N lines of the laravel.log file for debugging.
     * Local use only. Do NOT expose in production.
     */
    public function recent(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['data' => null, 'error' => ['message' => 'Unauthenticated']], 401);
        }

        $lines = (int) $request->query('lines', 200);
        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            return response()->json(['data' => ['lines' => []], 'error' => null]);
        }

        // Read file efficiently from the end
        $fp = fopen($logPath, 'r');
        $buffer = '';
        $pos = -1;
        $lineCount = 0;
        $chunk = '';

        fseek($fp, 0, SEEK_END);
        $filesize = ftell($fp);

        while ($lineCount <= $lines && abs($pos) < $filesize) {
            $seek = max($pos - 1024, -$filesize);
            fseek($fp, $seek, SEEK_END);
            $chunk = fread($fp, abs($pos) - $seek + 1) . $chunk;
            $pos = $seek;
            $lineCount = substr_count($chunk, "\n");
        }

        fclose($fp);

        $allLines = explode("\n", trim($chunk));
        $recent = array_slice($allLines, -$lines);

        return response()->json(['data' => ['lines' => $recent], 'error' => null]);
    }

    /**
     * Create a project for a specific user (LOCAL environment only).
     */
    public function createProjectForUser(Request $request): JsonResponse
    {
        if (!app()->environment('local')) {
            return response()->json(['data' => null, 'error' => ['message' => 'Not allowed']], 403);
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'description' => ['nullable', 'string'],
            'additional_notes' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'due_time' => ['nullable'],
            'steps' => ['nullable', 'array'],
            'image_url' => ['nullable', 'string'],
        ]);

        $project = Project::create([
            'user_id' => $data['user_id'],
            'description' => $data['description'] ?? null,
            'additional_notes' => $data['additional_notes'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'due_time' => $data['due_time'] ?? null,
            'steps' => $data['steps'] ?? null,
            'image_url' => $data['image_url'] ?? null,
        ]);

        Log::info('DebugController created project for user', ['user_id' => $data['user_id'], 'project_id' => $project->id]);

        return response()->json(['data' => ['project' => $project], 'error' => null], 201);
    }

    /**
     * Return projects for a specific user id (LOCAL environment only).
     */
    public function listProjectsForUser(Request $request): JsonResponse
    {
        if (!app()->environment('local')) {
            return response()->json(['data' => null, 'error' => ['message' => 'Not allowed']], 403);
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        $projects = Project::where('user_id', $data['user_id'])->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => ['projects' => $projects], 'error' => null]);
    }
}
