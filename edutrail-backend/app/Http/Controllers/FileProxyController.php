<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileProxyController extends Controller
{
    /**
     * Stream a file from storage/app/public/
     * Route: GET /api/files/serve/{filename}
     */
    public function serve($filename)
    {
        // Prevent directory traversal
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            return response()->json(['error' => 'Invalid file path'], 400);
        }

        $path = 'public/edutrail/' . $filename;

        if (!Storage::exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // Get MIME type
        $mimeType = Storage::mimeType($path);

        // Stream the file
        $stream = Storage::readStream($path);

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $mimeType ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . basename($filename) . '"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
