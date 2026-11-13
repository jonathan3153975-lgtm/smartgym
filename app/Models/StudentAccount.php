<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class StudentAccount extends Authenticatable
{
    use Notifiable;

    protected $table = 'student_accounts';

    protected $fillable = [
        'network_id',
        'student_id',
        'email',
        'password',
        'email_verified_at',
        'must_change_password',
        'last_login_at',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
