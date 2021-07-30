<?php

namespace Greensight\LaravelAuditing\Drivers;

use Greensight\LaravelAuditing\Contracts\Audit;
use Greensight\LaravelAuditing\Contracts\Auditable;
use Greensight\LaravelAuditing\Contracts\AuditDriver;
use Greensight\LaravelAuditing\Transactions\TransactionRegistry;
use Illuminate\Support\Facades\Config;

class Database implements AuditDriver
{
    private TransactionRegistry $transaction;

    public function __construct(TransactionRegistry $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * {@inheritdoc}
     */
    public function audit(Auditable $model): Audit
    {
        $fields = array_merge($model->toAudit(), $this->getTransactionAttributes());

        $implementation = Config::get('laravel-auditing.implementation', \Greensight\LaravelAuditing\Models\Audit::class);

        return call_user_func([$implementation, 'create'], $fields);
    }

    /**
     * {@inheritdoc}
     */
    public function prune(Auditable $model): bool
    {
        if (($threshold = $model->getAuditThreshold()) > 0) {
            $forRemoval = $model->audits()
                ->latest()
                ->get()
                ->slice($threshold)
                ->pluck('id');

            if (!$forRemoval->isEmpty()) {
                return $model->audits()
                    ->whereIn('id', $forRemoval)
                    ->delete() > 0;
            }
        }

        return false;
    }

    protected function getTransactionAttributes(): array
    {
        if (!$this->transaction->isActive()) {
            return [];
        }

        $attributes = [
            'transaction_uid' => $this->transaction->uid(),
            'transaction_time' => $this->transaction->timestamp(),
        ];

        if ($root = $this->transaction->rootEntity()) {
            $attributes['root_entity_type'] = $root->getMorphClass();
            $attributes['root_entity_id'] = $root->getKey();
        }

        return $attributes;
    }
}
