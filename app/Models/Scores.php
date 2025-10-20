<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Scores extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'scores';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'period_id',
        'criterion_id',
        'candidate_id',
        'raw_value',
        'normalized_value',
        'evidence_url',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'raw_value' => 'decimal:4',
        'normalized_value' => 'decimal:8',
    ];

    public function period()
    {
        return $this->belongsTo(Periods::class, 'period_id');
    }

    public function criterion()
    {
        return $this->belongsTo(Criteria::class, 'criterion_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidates::class, 'candidate_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
