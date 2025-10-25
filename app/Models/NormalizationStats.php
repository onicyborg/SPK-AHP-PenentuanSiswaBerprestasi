<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Periods;
use App\Models\Criteria;
use App\Models\User;

class NormalizationStats extends Model
{
    use HasFactory;

    protected $table = 'normalization_stats';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'period_id',
        'criterion_id',
        'method',
        'params',
        'min_value',
        'max_value',
        'sum_value',
        'mean_value',
        'std_dev_value',
        'count_samples',
        'computed_at',
        'computed_by',
    ];

    protected $casts = [
        'params' => 'array',
        'min_value' => 'float',
        'max_value' => 'float',
        'sum_value' => 'float',
        'mean_value' => 'float',
        'std_dev_value' => 'float',
        'count_samples' => 'integer',
        'computed_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(Periods::class, 'period_id');
    }

    public function criterion()
    {
        return $this->belongsTo(Criteria::class, 'criterion_id');
    }

    public function computedBy()
    {
        return $this->belongsTo(User::class, 'computed_by');
    }
}
