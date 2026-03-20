<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionLog;
use InvalidArgumentException;

class OrderWorkflowService
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $allowedTransitions = [
        'antrean' => ['proses_cuci'],
        'proses_cuci' => ['proses_setrika', 'selesai'],
        'proses_setrika' => ['selesai'],
        'selesai' => ['diambil'],
        'diambil' => [],
    ];

    public function updateStatus(Transaction $transaction, string $newStatus, int $userId, ?string $note = null): Transaction
    {
        $currentStatus = $transaction->status;

        if (! array_key_exists($currentStatus, $this->allowedTransitions)) {
            throw new InvalidArgumentException('Current transaction status is not recognized.');
        }

        if (! in_array($newStatus, $this->allowedTransitions[$currentStatus], true)) {
            throw new InvalidArgumentException('Invalid status transition.');
        }

        $transaction->status = $newStatus;
        $transaction->save();

        TransactionLog::query()->create([
            'transaction_id' => $transaction->id,
            'user_id' => $userId,
            'action_type' => 'updated_status_to_'.$newStatus,
            'description' => $note ?: ('Status moved from '.$currentStatus.' to '.$newStatus),
        ]);

        return $transaction->refresh();
    }
}
