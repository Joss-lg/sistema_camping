<?php

namespace App\Services;

use App\Models\NotificacionSistema;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificacionSistemaPatternService
{
    /**
     * @return Collection<int, User>
     */
    public function usuariosActivos(): Collection
    {
        $usuarios = User::query()
            ->with('role:id,slug,nombre')
            ->where('activo', true)
            ->get(['id', 'role_id']);

        if ($usuarios->isNotEmpty()) {
            return $usuarios;
        }

        // Fallback defensivo para entornos donde el flag activo no está correctamente sembrado.
        return User::query()
            ->with('role:id,slug,nombre')
            ->get(['id', 'role_id']);
    }

    /**
     * @param array<int, string> $rolesPermitidos
     * @return Collection<int, User>
     */
    public function usuariosActivosPorRoles(array $rolesPermitidos): Collection
    {
        $rolesNormalizados = collect($rolesPermitidos)
            ->map(fn (string $rol): string => PermisoService::normalizeRoleKey($rol))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($rolesNormalizados === []) {
            return $this->usuariosActivos();
        }

        return $this->usuariosActivos()
            ->filter(function (User $user) use ($rolesNormalizados): bool {
                $roleKey = PermisoService::normalizeRoleKey((string) ($user->role?->slug ?: $user->role?->nombre));

                return in_array($roleKey, $rolesNormalizados, true);
            })
            ->values();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function crear(array $payload): NotificacionSistema
    {
        $data = array_merge([
            'tipo' => 'Informativa',
            'prioridad' => 'Media',
            'estado' => 'Pendiente',
            'fecha_programada' => now(),
            'requiere_accion' => false,
            'metadata' => [],
        ], $payload);

        return NotificacionSistema::query()->create($data);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function crearSiNoExisteHoy(array $payload, ?string $metadataKey = null, mixed $metadataValue = null): ?NotificacionSistema
    {
        $modulo = (string) ($payload['modulo'] ?? '');

        if ($modulo === '') {
            return $this->crear($payload);
        }

        $query = NotificacionSistema::query()
            ->where('modulo', $modulo)
            ->whereDate('created_at', now()->toDateString());

        if (array_key_exists('tipo', $payload)) {
            $query->where('tipo', (string) $payload['tipo']);
        }

        if (array_key_exists('user_id', $payload)) {
            $query->where('user_id', $payload['user_id']);
        }

        if (array_key_exists('role_id', $payload)) {
            $query->where('role_id', $payload['role_id']);
        }

        if ($metadataKey !== null) {
            $query->where('metadata->' . $metadataKey, $metadataValue);
        }

        if ($query->exists()) {
            return null;
        }

        return $this->crear($payload);
    }
}
