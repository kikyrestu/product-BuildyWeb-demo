<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'is_active',
        'show_until',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_until' => 'date',
        ];
    }
}
