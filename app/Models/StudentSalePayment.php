<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSalePayment extends Model
{
    public const METHOD_CASH = 'cash';

    public const METHOD_PIX = 'pix';

    public const METHOD_DEBIT = 'debit_card';

    public const METHOD_CREDIT = 'credit_card';

    public const METHOD_TRANSFER = 'transfer';

    public const METHODS = [
        self::METHOD_CASH => 'Dinheiro',
        self::METHOD_PIX => 'Pix',
        self::METHOD_DEBIT => 'Cartão de débito',
        self::METHOD_CREDIT => 'Cartão de crédito',
        self::METHOD_TRANSFER => 'Transferência',
    ];

    protected $fillable = [
        'student_sale_id',
        'method',
        'amount',
        'installments',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'installments' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(StudentSale::class, 'student_sale_id');
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
