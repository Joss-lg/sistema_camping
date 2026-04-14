<?php

namespace App\Policies;

use App\Models\TrazabilidadEtapa;
use App\Models\User;
use App\Services\PermisoService;

class TrazabilidadPolicy
{
    public function approve(User $user, TrazabilidadEtapa $etapa): bool
    {
        return $this->aprobar($user, $etapa);
    }

    public function aprobar(User $user, TrazabilidadEtapa $etapa): bool
    {
        if (! $user->canCustom('Trazabilidad', 'aprobar')) {
            return false;
        }

        if ((int) ($etapa->responsable_id ?? 0) === (int) $user->id) {
            return true;
        }

        $areaEtapa = mb_strtolower(trim((string) (
            $etapa->etapaPlantilla?->tipo_etapa
            ?? $etapa->etapaPlantilla?->nombre
            ?? ''
        )));

        if ($areaEtapa === '') {
            return false;
        }

        $rolUsuario = mb_strtolower(PermisoService::normalizeRoleKey((string) ($user->role?->slug ?: $user->role?->nombre ?: '')));
        $departamentoUsuario = mb_strtolower(trim((string) ($user->departamento ?? '')));

        $areaNormalizada = $this->normalizarArea($areaEtapa);

        if ($areaNormalizada === '') {
            return false;
        }

        return str_contains($rolUsuario, $areaNormalizada)
            || str_contains($departamentoUsuario, $areaNormalizada);
    }

    private function normalizarArea(string $area): string
    {
        return match (true) {
            str_contains($area, 'materia prima'),
            str_contains($area, 'almacen') => 'almacen',

            str_contains($area, 'produccion'),
            str_contains($area, 'fabricacion') => 'produccion',

            str_contains($area, 'calidad'),
            str_contains($area, 'inspeccion') => 'calidad',

            str_contains($area, 'empaque') => 'empaque',

            str_contains($area, 'logistica'),
            str_contains($area, 'despacho') => 'logistica',

            default => $area,
        };
    }
}
