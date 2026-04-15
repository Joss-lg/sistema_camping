<?php

namespace App\Http\Controllers;

use App\Models\NotificacionSistema;
use Illuminate\Support\Facades\Auth;

class NotificacionSistemaController extends Controller
{
    /**
     * Listar notificaciones del usuario actual
     */
    public function index()
    {
        $user = Auth::user();

        abort_unless($user, 403);

        $notificaciones = NotificacionSistema::query()
            ->paraUsuario($user)
            ->noArchivadas()
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('notificaciones.index', compact('notificaciones'));
    }

    public function resumen()
    {
        $user = Auth::user();

        abort_unless($user, 403);

        return response()->json($this->buildSummaryPayload($user));
    }

    /**
     * Marcar una notificación como leída
     */
    public function marcarLeida($id)
    {
        $user = Auth::user();
        $notificacion = NotificacionSistema::findOrFail($id);

        // Verificar que la notificación pertenece al usuario o su rol
        $esDelUsuario = $notificacion->user_id === $user->id;
        $esDelRol = $notificacion->role_id === $user->role_id;

        if (! $esDelUsuario && ! $esDelRol) {
            abort(403, 'No autorizado');
        }

        $notificacion->update([
            'estado' => 'Leida',
            'fecha_leida' => now(),
        ]);

        return redirect()->back()->with('ok', 'Notificación marcada como leída');
    }

    /**
     * Archivar una notificación
     */
    public function archivar($id)
    {
        $user = Auth::user();
        $notificacion = NotificacionSistema::findOrFail($id);

        // Verificar que la notificación pertenece al usuario o su rol
        $esDelUsuario = $notificacion->user_id === $user->id;
        $esDelRol = $notificacion->role_id === $user->role_id;

        if (! $esDelUsuario && ! $esDelRol) {
            abort(403, 'No autorizado');
        }

        $notificacion->update([
            'estado' => 'Archivada',
        ]);

        return redirect()->back()->with('ok', 'Notificación archivada');
    }

    /**
     * Listar notificaciones archivadas del usuario actual
     */
    public function archivadas()
    {
        $user = Auth::user();

        abort_unless($user, 403);

        $notificaciones = NotificacionSistema::query()
            ->paraUsuario($user)
            ->where('estado', 'Archivada')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('notificaciones.archivadas', compact('notificaciones'));
    }

    /**
     * Restaurar una notificación archivada
     */
    public function restaurar($id)
    {
        $user = Auth::user();
        $notificacion = NotificacionSistema::findOrFail($id);

        // Verificar que la notificación pertenece al usuario o su rol
        $esDelUsuario = $notificacion->user_id === $user->id;
        $esDelRol = $notificacion->role_id === $user->role_id;

        if (! $esDelUsuario && ! $esDelRol) {
            abort(403, 'No autorizado');
        }

        $notificacion->update([
            'estado' => 'Pendiente',
        ]);

        return redirect()->back()->with('ok', 'Notificación restaurada');
    }

    private function buildSummaryPayload($user): array
    {
        $baseQuery = NotificacionSistema::query()
            ->paraUsuario($user)
            ->noArchivadas();

        $pendingCount = (clone $baseQuery)
            ->where('estado', 'Pendiente')
            ->count();

        $notifications = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (NotificacionSistema $notificacion): array {
                return [
                    'id' => $notificacion->id,
                    'titulo' => (string) $notificacion->titulo,
                    'mensaje' => (string) $notificacion->mensaje,
                    'estado' => (string) $notificacion->estado,
                    'modulo' => (string) $notificacion->modulo,
                    'prioridad' => (string) $notificacion->prioridad,
                    'url' => filled($notificacion->url_accion)
                        ? url((string) $notificacion->url_accion)
                        : route('notificaciones.index'),
                    'created_at' => optional($notificacion->created_at)?->format('d/m/Y H:i'),
                ];
            })
            ->values()
            ->all();

        return [
            'pending_count' => $pendingCount,
            'notifications' => $notifications,
        ];
    }
}
