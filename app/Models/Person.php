<?php

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'network_id',
        'unit_id',
        'person_type',
        'category',
        'name',
        'trade_name',
        'document',
        'document_raw',
        'state_registration',
        'birth_date',
        'legal_representative_name',
        'legal_representative_document',
        'email',
        'phone',
        'whatsapp',
        'zip_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isIndividual(): bool
    {
        return $this->person_type === 'individual';
    }

    public function isCompany(): bool
    {
        return $this->person_type === 'company';
    }

    public function isStaff(): bool
    {
        return $this->category === 'staff';
    }

    public function isClient(): bool
    {
        return $this->category === 'client';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getPersonTypeLabelAttribute(): string
    {
        return $this->person_type === 'company' ? 'Jurídica' : 'Física';
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->category === 'staff' ? 'Equipe' : 'Cliente';
    }

    public function getDocumentLabelAttribute(): string
    {
        return $this->person_type === 'company' ? 'CNPJ' : 'CPF';
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->person_type === 'company' && $this->trade_name) {
            return $this->trade_name;
        }

        return $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($searchQuery) use ($term) {
            $searchQuery->where('name', 'like', "%{$term}%")
                ->orWhere('trade_name', 'like', "%{$term}%")
                ->orWhere('document', 'like', "%{$term}%")
                ->orWhere('document_raw', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }
}
