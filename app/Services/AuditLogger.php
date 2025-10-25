<?php

namespace App\Services;

use App\Models\AuditLogs;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Tulis log audit terstandar.
     * @param string|null $periodId
     * @param string $entity
     * @param string|null $entityId
     * @param string $action
     * @param array|null $changes key => [old, new]
     * @param array|null $meta informasi tambahan opsional
     */
    public function log(?string $periodId, string $entity, ?string $entityId, string $action, ?array $changes = null, ?array $meta = null, ?string $actedBy = null): void
    {
        $actorId = $actedBy ?? (Auth::id() ?? ($meta['acted_by'] ?? null));

        $payload = [
            'period_id'   => $periodId,
            'entity'      => $entity,
            'entity_id'   => $entityId,
            'action'      => $action,
            'changes_json'=> $changes ? ($meta ? array_merge(['changes' => $changes], ['meta' => $meta]) : ['changes' => $changes]) : ($meta ?: null),
            'acted_by'    => $actorId,
            'acted_at'    => now(),
        ];

        AuditLogs::create($payload);
    }
}
