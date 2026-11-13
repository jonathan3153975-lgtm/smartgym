<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use SoftDeletes;

    public const WEEKDAYS = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];

    protected $table = 'classes';

    protected $fillable = [
        'network_id',
        'unit_id',
        'modality_id',
        'teacher_id',
        'name',
        'weekday',
        'start_time',
        'end_time',
        'vacancies',
        'drop_in_price',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'vacancies' => 'integer',
        'drop_in_price' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(Modality::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getWeekdayLabelAttribute(): string
    {
        return self::WEEKDAYS[$this->weekday] ?? '—';
    }

    public function getTimeLabelAttribute(): string
    {
        $start = $this->start_time ? substr($this->start_time, 0, 5) : '—';

        if (! $this->end_time) {
            return $start;
        }

        return $start.' - '.substr($this->end_time, 0, 5);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
