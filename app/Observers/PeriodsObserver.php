<?php

namespace App\Observers;

use App\Models\Periods;
use App\Services\AuditLogger;

class PeriodsObserver
{
    public function created(Periods $period): void
    {
        $logger = app(AuditLogger::class);
        $actorId = auth()->id() ?? $period->created_by ?? $period->updated_by;
        $changes = $this->wrapAsChanges(array_intersect_key($period->getAttributes(), array_flip($period->getFillable() ?: [])) ?: [
            'id' => $period->id,
            'name' => $period->name,
            'status' => $period->status,
            'start_date' => $period->start_date,
            'end_date' => $period->end_date,
        ]);
        $logger->log($period->id, 'periods', $period->id, 'create', $changes, null, $actorId);
    }

    public function updated(Periods $period): void
    {
        $logger = app(AuditLogger::class);
        $actorId = auth()->id() ?? $period->updated_by ?? $period->created_by;
        $dirty = $period->getChanges();
        // remove noise keys
        unset($dirty['updated_at']);
        if (empty($dirty)) {
            return;
        }
        $changes = [];
        foreach ($dirty as $key => $new) {
            $old = $period->getOriginal($key);
            $changes[$key] = [$old, $new];
        }
        $logger->log($period->id, 'periods', $period->id, 'update', $changes, null, $actorId);
    }

    public function deleted(Periods $period): void
    {
        $logger = app(AuditLogger::class);
        $actorId = auth()->id() ?? $period->updated_by ?? $period->created_by;
        $logger->log($period->id, 'periods', $period->id, 'delete', null, [
            'name' => $period->name,
            'status' => $period->status,
        ], $actorId);
    }

    private function wrapAsChanges(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $out[$k] = [null, $v];
        }
        return $out;
    }
}
