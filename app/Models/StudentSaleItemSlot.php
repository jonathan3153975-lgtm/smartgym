<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSaleItemSlot extends Model
{
    protected $fillable = [
        'student_sale_item_id',
        'class_schedule_id',
        'weekday',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StudentSaleItem::class, 'student_sale_item_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function getWeekdayLabelAttribute(): string
    {
        return SchoolClass::WEEKDAYS[$this->weekday] ?? '—';
    }
}
