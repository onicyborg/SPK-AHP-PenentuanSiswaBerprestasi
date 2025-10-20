<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Weights extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'weights';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'period_id',
        'node_id',
        'level',
        'weight',
        'cr_at_level',
        'computed_at',
    ];

    protected $casts = [
        'weight' => 'decimal:8',
        'cr_at_level' => 'decimal:8',
        'computed_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(Periods::class, 'period_id');
    }

    public function node()
    {
        return $this->belongsTo(Criteria::class, 'node_id');
    }
}
