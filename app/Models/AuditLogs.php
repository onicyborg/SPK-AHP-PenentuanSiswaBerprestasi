<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLogs extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'audit_logs';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // acted_at is managed explicitly

    protected $fillable = [
        'period_id',
        'entity',
        'entity_id',
        'action',
        'changes_json',
        'acted_by',
        'acted_at',
    ];

    protected $casts = [
        'changes_json' => 'array',
        'acted_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(Periods::class, 'period_id');
    }

    public function actedBy()
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
