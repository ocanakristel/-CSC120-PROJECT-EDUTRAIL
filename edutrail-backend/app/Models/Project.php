<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    /**
     * Mass assignable attributes
     *
     * @var array
     */
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

    /**
     * Casts
     */
    protected $casts = [
        'steps' => 'array',
    ];
}
