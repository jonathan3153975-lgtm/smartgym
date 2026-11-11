<?php

namespace App\Models;

use Database\Factories\SaasPlanParameterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasPlanParameter extends Model
{
    /** @use HasFactory<SaasPlanParameterFactory> */
    use HasFactory;

    protected $fillable = [
        'saas_plan_id',
        'description',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }
}
