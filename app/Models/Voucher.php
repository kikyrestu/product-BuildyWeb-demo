<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount_amount',
        'min_transaction_amount',
        'valid_from',
        'valid_until',
        'quota',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'min_transaction_amount' => 'decimal:2',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }
}
