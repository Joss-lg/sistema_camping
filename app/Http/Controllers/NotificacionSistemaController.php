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
        
        $notificaciones = NotificacionSistema::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('role_id', $user->role_id);
            })
            ->where('estado', '!=', 'Archivada')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('notificaciones.index', compact('notificaciones'));
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
        
        if (!$esDelUsuario && !$esDelRol) {
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
        
        if (!$esDelUsuario && !$esDelRol) {
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
        
        $notificaciones = NotificacionSistema::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('role_id', $user->role_id);
            })
            ->where('estado', 'Archivada')
            ->orderBy('created_at', 'desc')
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
        
        if (!$esDelUsuario && !$esDelRol) {
            abort(403, 'No autorizado');
        }
        
        $notificacion->update([
            'estado' => 'Pendiente',
        ]);
        
        return redirect()->back()->with('ok', 'Notificación restaurada');
    }
}

