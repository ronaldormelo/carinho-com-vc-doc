<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de domínio para categorias financeiras.
 */
class DomainFinancialCategory extends Model
{
    protected $table = 'domain_financial_category';
    public $timestamps = false;

    // Receitas
    public const SERVICE_REVENUE = 1;
    public const CANCELLATION_FEE = 2;
    public const LATE_FEE = 3;
    public const OTHER_REVENUE = 4;

    // Despesas
    public const CAREGIVER_PAYOUT = 10;
    public const GATEWAY_FEE = 11;
    public const TRANSFER_FEE = 12;
    public const REFUND_EXPENSE = 13;
    public const OPERATIONAL = 14;
    public const ADMINISTRATIVE = 15;
    public const TAX = 16;
    public const OTHER_EXPENSE = 17;

    /**
     * Verifica se é categoria de receita.
     */
    public function isRevenue(): bool
    {
        return $this->type === 'revenue';
    }

    /**
     * Verifica se é categoria de despesa.
     */
    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    /**
     * Obtém categoria por código.
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Scope para receitas.
     */
    public function scopeRevenues($query)
    {
        return $query->where('type', 'revenue');
    }

    /**
     * Scope para despesas.
     */
    public function scopeExpenses($query)
    {
        return $query->where('type', 'expense');
    }
}
