<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'master_service_id',
        'qty',
        'snapshot_data',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'snapshot_data' => 'array',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class, 'master_service_id');
    }
}
