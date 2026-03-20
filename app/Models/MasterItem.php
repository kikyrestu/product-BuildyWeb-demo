<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'base_price',
        'unit',
        'is_percentage_modifier',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_percentage_modifier' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
