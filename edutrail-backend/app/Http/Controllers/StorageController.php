<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StorageController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048', // 2MB
        ]);

        // store in: storage/app/public/edutrail
        $path = $request->file('file')->store('edutrail', 'public');

        // because you ran: php artisan storage:link
        $publicUrl = asset('storage/' . $path);

        return response()->json([
            'path' => $path,
            'publicUrl' => $publicUrl,
        ], 201);
    }
}

