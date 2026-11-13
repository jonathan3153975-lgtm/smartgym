<?php

namespace App\Support;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UnitPanelContext
{
    /**
     * @return Collection<int, Unit>
     */
    public static function units(): Collection
    {
        return TenantContext::current()
            ->unitsQuery()
            ->orderBy('name')
            ->get();
    }

    public static function unit(?Request $request = null): Unit
    {
        $request ??= request();
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para acessar o painel da unidade.');

        $vinculo = $tenant->vinculo();
        $units = self::units();

        if ($vinculo?->unit_id) {
            $unit = $units->firstWhere('id', (int) $vinculo->unit_id);

            abort_unless($unit, 403, 'Unidade não disponível.');

            $request->session()->put('unit_panel_unit_id', $unit->id);

            return $unit;
        }

        $requestedId = (int) $request->query('unit_id', (int) $request->session()->get('unit_panel_unit_id', 0));
        $unit = $units->firstWhere('id', $requestedId) ?? $units->first();

        abort_unless($unit, 403, 'Nenhuma unidade disponível para este usuário.');

        $request->session()->put('unit_panel_unit_id', $unit->id);

        return $unit;
    }
}
