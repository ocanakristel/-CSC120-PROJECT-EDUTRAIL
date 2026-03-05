<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'units',
        'description',
        'image_url',
    ];

    protected $casts = [
        'units' => 'integer',
    ];
}
