<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'additional_notes',
        'due_date',
        'due_time',
        'steps',
        'image_url',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'steps' => 'array', // ✅ makes steps always an array in PHP
    ];
}
