<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSchedule extends Model
{
    protected $fillable = [
        'class_id',
        'teacher_id',
        'person_id',
        'weekday',
        'start_time',
        'end_time',
        'vacancies',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'vacancies' => 'integer',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function getTeacherNameAttribute(): string
    {
        return $this->person?->name ?? $this->teacher?->name ?? 'Sem professor';
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    public function getWeekdayLabelAttribute(): string
    {
        return SchoolClass::WEEKDAYS[$this->weekday] ?? '—';
    }

    public function getTimeLabelAttribute(): string
    {
        $start = $this->start_time ? substr($this->start_time, 0, 5) : '—';

        if (! $this->end_time) {
            return $start;
        }

        return $start.' - '.substr($this->end_time, 0, 5);
    }

    public function getLabelAttribute(): string
    {
        return $this->weekday_label.' '.$this->time_label;
    }
}
