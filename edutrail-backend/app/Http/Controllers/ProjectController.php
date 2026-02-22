<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): JsonResponse
    {
        // return projects for the authenticated user
        $user = Auth::user();

        if (!$user) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Unauthenticated'] ], 401);
        }

        $projects = Project::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

        return response()->json([ 'data' => ['projects' => $projects], 'error' => null ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Log incoming request for debugging when tracker doesn't update
        Log::info('ProjectController@store called', [
            'user_id' => $user?->id,
            'payload' => $request->all(),
            'has_file' => $request->hasFile('file'),
        ]);

        if (!$user) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Unauthenticated'] ], 401);
        }

        $data = $request->validate([
            'description' => ['nullable', 'string'],
            'additional_notes' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'due_time' => ['nullable'],
            'steps' => ['nullable', 'array'],
            'image_url' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,in_progress,finished'],
            'file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // If frontend submitted a file together with project, store it here
        if ($request->hasFile('file')) {
            try {
                $filePath = $request->file('file')->store('edutrail', 'public');
                $publicUrl = asset('storage/' . $filePath);
                Log::info('ProjectController@store saved attached file', ['path' => $filePath, 'publicUrl' => $publicUrl]);
                $data['image_url'] = $filePath;
                // also provide publicUrl to frontend consumer if they inspect response
                $data['image_public_url'] = $publicUrl;
            } catch (\Throwable $e) {
                Log::error('ProjectController@store file save failed', ['message' => $e->getMessage()]);
            }
        }

        $project = Project::create([
            'user_id' => $user->id,
            'description' => $data['description'] ?? null,
            'additional_notes' => $data['additional_notes'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'due_time' => $data['due_time'] ?? null,
            'steps' => $data['steps'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'status' => $data['status'] ?? 'in_progress',
        ]);

        $response = [ 'data' => ['project' => $project], 'error' => null ];
        if (isset($data['image_public_url'])) {
            $response['data']['image_public_url'] = $data['image_public_url'];
            $response['imagePublicUrl'] = $data['image_public_url'];
        }

        return response()->json($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Unauthenticated'] ], 401);
        }

        $project = Project::where('id', $id)->where('user_id', $user->id)->first();

        if (!$project) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Not found'] ], 404);
        }

        return response()->json([ 'data' => ['project' => $project], 'error' => null ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Unauthenticated'] ], 401);
        }

        $project = Project::where('id', $id)->where('user_id', $user->id)->first();
        if (!$project) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Not found'] ], 404);
        }

        $data = $request->validate([
            'description' => ['nullable', 'string'],
            'additional_notes' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'due_time' => ['nullable'],
            'steps' => ['nullable', 'array'],
            'image_url' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,in_progress,finished'],
        ]);

        $project->description = $data['description'] ?? $project->description;
        $project->additional_notes = $data['additional_notes'] ?? $project->additional_notes;
        $project->due_date = $data['due_date'] ?? $project->due_date;
        $project->due_time = $data['due_time'] ?? $project->due_time;
        $project->steps = $data['steps'] ?? $project->steps;
        $project->image_url = $data['image_url'] ?? $project->image_url;
        $project->status = $data['status'] ?? $project->status;

        $project->save();

        return response()->json([ 'data' => ['project' => $project], 'error' => null ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Unauthenticated'] ], 401);
        }

        $project = Project::where('id', $id)->where('user_id', $user->id)->first();
        if (!$project) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Not found'] ], 404);
        }

        $project->delete();

        return response()->json([ 'data' => ['success' => true], 'error' => null ]);
    }

    /**
     * Return count of projects for authenticated user (helper for tracker UI)
     */
    public function count(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Unauthenticated'] ], 401);
        }

        $count = Project::where('user_id', $user->id)->count();

        return response()->json([ 'data' => ['count' => $count], 'error' => null ]);
    }

    /**
     * Return the most recently created project for the authenticated user.
     */
    public function latest(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Unauthenticated'] ], 401);
        }

        $project = Project::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();

        return response()->json([ 'data' => ['project' => $project], 'error' => null ]);
    }

    /**
     * Return a small summary (count + latest) for the authenticated user.
     */
    public function summary(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Unauthenticated'] ], 401);
        }

        $count = Project::where('user_id', $user->id)->count();
        $latest = Project::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();

        return response()->json([ 'data' => ['count' => $count, 'latest' => $latest], 'error' => null ]);
    }
}
