<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StorageController extends Controller
{
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048', // 2MB
            ]);

            // store in: storage/app/public/edutrail
            $path = $request->file('file')->store('edutrail', 'public');

            // because you ran: php artisan storage:link
            $publicUrl = asset('storage/' . $path);

            Log::info('StorageController@upload saved file', ['path' => $path, 'publicUrl' => $publicUrl]);

            return response()->json([
                // backwards-compatible top-level fields some frontends expect
                'path' => $path,
                'publicUrl' => $publicUrl,
                // primary API shape used across controllers
                'data' => [
                    'path' => $path,
                    'publicUrl' => $publicUrl,
                ],
                'error' => null,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('StorageController@upload validation failed', ['errors' => $e->errors()]);
            return response()->json([ 'data' => null, 'error' => ['message' => 'Invalid file uploaded', 'details' => $e->errors()] ], 422);
        } catch (\Throwable $e) {
            Log::error('StorageController@upload failed', ['message' => $e->getMessage()]);
            return response()->json([ 'data' => null, 'error' => ['message' => 'Upload failed', 'details' => $e->getMessage()] ], 500);
        }
    }

    public function publicUrl($bucket, Request $request)
    {
        $path = $request->query('path');
        if (!$path) {
            return response()->json([ 'data' => null, 'error' => ['message' => 'Missing path'] ], 400);
        }

        $publicUrl = asset('storage/' . $path);
        return response()->json([ 'data' => ['publicUrl' => $publicUrl], 'error' => null ]);
    }
}

