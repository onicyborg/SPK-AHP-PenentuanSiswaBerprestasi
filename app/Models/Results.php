<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Results extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'results';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'period_id',
        'candidate_id',
        'final_score',
        'rank',
        'computed_at',
    ];

    protected $casts = [
        'final_score' => 'decimal:8',
        'rank' => 'integer',
        'computed_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(Periods::class, 'period_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidates::class, 'candidate_id');
    }
}
