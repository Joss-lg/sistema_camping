<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'avatar',
        'telefono',
        'departamento',
        'activo',
        'ultimo_acceso',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acceso' => 'datetime',
            'activo' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // ============ RELATIONSHIPS - PURCHASING ============

    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'user_id');
    }

    // ============ RELATIONSHIPS - INVENTORY ============

    public function lotesInsumosRecepcion(): HasMany
    {
        return $this->hasMany(LoteInsumo::class, 'user_recepcion_id');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'user_id');
    }

    // ============ RELATIONSHIPS - PRODUCTION ============

    public function ordenesProduccion(): HasMany
    {
        return $this->hasMany(OrdenProduccion::class, 'user_id');
    }

    public function consumosMateriales(): HasMany
    {
        return $this->hasMany(ConsumoMaterial::class, 'user_id');
    }

    public function productosTerminadosResponsable(): HasMany
    {
        return $this->hasMany(ProductoTerminado::class, 'user_responsable_id');
    }

    public function productosTerminadosInspeccion(): HasMany
    {
        return $this->hasMany(ProductoTerminado::class, 'user_inspeccion_id');
    }

    public function trazabilidadRegistros(): HasMany
    {
        return $this->hasMany(TrazabilidadRegistro::class, 'user_id');
    }

    // ============ RELATIONSHIPS - PHASE 6 ============

    public function notificacionesSistema(): HasMany
    {
        return $this->hasMany(NotificacionSistema::class, 'user_id');
    }

    public function reportesGenerados(): HasMany
    {
        return $this->hasMany(ReporteGenerado::class, 'generado_por_user_id');
    }

    /**
     * Verifica permisos por modulo/accion usando el rol del usuario.
     */
    public function canCustom(string $modulo, string $accion): bool
    {
        if (! $this->exists || ! $this->role_id) {
            return false;
        }

        $field = $this->resolvePermissionField($accion);

        if ($field === null) {
            return false;
        }

        return Permission::query()
            ->where('role_id', $this->role_id)
            ->whereRaw('LOWER(modulo) = ?', [mb_strtolower($modulo)])
            ->where($field, true)
            ->exists();
    }

    /**
     * Soporte para Blade: $user->can('modulo', 'accion').
     * Para abilities normales de Gate mantiene el comportamiento nativo.
     */
    public function can($abilities, $arguments = []): bool
    {
        if (is_string($abilities) && is_string($arguments)) {
            return $this->canCustom($abilities, $arguments);
        }

        return parent::can($abilities, $arguments);
    }

    private function resolvePermissionField(string $accion): ?string
    {
        return match (mb_strtolower(trim($accion))) {
            'ver', 'view', 'viewany' => 'puede_ver',
            'crear', 'create' => 'puede_crear',
            'editar', 'update' => 'puede_editar',
            'eliminar', 'delete', 'forcedelete' => 'puede_eliminar',
            'aprobar', 'approve' => 'puede_aprobar',
            default => null,
        };
    }
}
