<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $subjects = Subject::where('user_id', $user->id)->orderBy('name')->get();
        return response()->json(['data' => $subjects]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Log incoming payload for debugging
        Log::info('SubjectController@store called', [
            'user_id' => $user->id,
            'payload_keys' => array_keys($request->all()),
            'has_files' => ! empty($request->allFiles()),
        ]);

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'units' => ['nullable','integer','min:0'],
            'description' => ['nullable','string'],
            'image_url' => ['nullable','string'],
        ]);

        // handle uploaded file if present (frontend posts 'file' or 'image')
        if ($request->hasFile('file') || $request->hasFile('image')) {
            $file = $request->file('file') ?? $request->file('image');
            try {
                $path = $file->store('edutrail', 'public');
                // Extract just the filename for the proxy endpoint
                $filename = basename($path);
                $publicUrl = url('/api/files/serve/' . $filename);
                $data['image_url'] = $publicUrl;
                Log::info('SubjectController@store uploaded file', ['path' => $path, 'publicUrl' => $publicUrl]);
            } catch (\Throwable $e) {
                Log::warning('SubjectController@store file save failed: '.$e->getMessage());
            }
        }

        try {
            $subject = Subject::create(array_merge($data, ['user_id' => $user->id]));
            return response()->json(['data' => $subject], 201);
        } catch (\Exception $e) {
            Log::error('Subject store error: '.$e->getMessage());
            return response()->json(['message' => 'Failed to create subject.'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $subject = Subject::where('id', $id)->where('user_id', $user->id)->first();
        if (! $subject) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json(['data' => $subject]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $subject = Subject::where('id', $id)->where('user_id', $user->id)->first();
        if (! $subject) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes','required','string','max:255'],
            'units' => ['sometimes','nullable','integer','min:0'],
            'description' => ['sometimes','nullable','string'],
            'image_url' => ['sometimes','nullable','string'],
        ]);

        // handle uploaded file for update
        if ($request->hasFile('file') || $request->hasFile('image')) {
            $file = $request->file('file') ?? $request->file('image');
            try {
                $path = $file->store('edutrail', 'public');
                // Extract just the filename for the proxy endpoint
                $filename = basename($path);
                $publicUrl = url('/api/files/serve/' . $filename);
                $data['image_url'] = $publicUrl;
                Log::info('SubjectController@update uploaded file', ['path' => $path, 'publicUrl' => $publicUrl]);
            } catch (\Throwable $e) {
                Log::warning('SubjectController@update file save failed: '.$e->getMessage());
            }
        }

        try {
            $subject->update($data);
            return response()->json(['data' => $subject]);
        } catch (\Exception $e) {
            Log::error('Subject update error: '.$e->getMessage());
            return response()->json(['message' => 'Failed to update subject.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $subject = Subject::where('id', $id)->where('user_id', $user->id)->first();
        if (! $subject) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        try {
            $subject->delete();
            return response()->json(['message' => 'Deleted.']);
        } catch (\Exception $e) {
            Log::error('Subject delete error: '.$e->getMessage());
            return response()->json(['message' => 'Failed to delete subject.'], 500);
        }
    }
}
