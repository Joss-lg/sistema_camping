@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Encabezado --}}
    <div class="mb-6">
        <h1 class="text-3xl font-extrabold text-slate-800">Administración de Usuarios y Permisos</h1>
        <p class="text-slate-500 text-base">Registra nuevos usuarios y define su nivel de acceso por módulo.</p>
    </div>

    {{-- ALERTAS --}}
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg mb-5 shadow-sm">
            <span class="font-bold">Error:</span> {{ $errors->first() }}
        </div>
    @endif
    @if (session('ok'))
        <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg mb-5 shadow-sm">
            {{ session('ok') }}
        </div>
    @endif

    {{-- Formulario de Creación --}}
    <section class="bg-white border border-slate-200 rounded-xl p-6 mb-8 shadow-sm">
        <h2 class="text-xl font-bold text-slate-800 mb-4">Crear Nuevo Usuario</h2>
        <form method="POST" action="{{ route('permisos.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Nombre Completo</label>
                    <input name="nombre" type="text" value="{{ old('nombre') }}" required placeholder="Ej. Juan Pérez"
                        class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none transition-all">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Correo Electrónico</label>
                    <input name="email" type="email" value="{{ old('email') }}" required placeholder="usuario@gmail.com"
                        class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none transition-all">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Rol de Sistema</label>
                    <select name="rol" id="rol" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                        <option value="ADMIN" {{ old('rol') == 'ADMIN' ? 'selected' : '' }}>ADMIN</option>
                        <option value="ALMACEN" {{ old('rol') == 'ALMACEN' ? 'selected' : '' }}>ALMACEN</option>
                
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Contraseña</label>
                    <input name="password" type="password" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-600">Confirmar Contraseña</label>
                    <input name="password_confirmation" type="password" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                </div>
            </div>

            <div class="border-t border-slate-100 pt-5">
                <div class="flex flex-wrap justify-between items-center mb-4 gap-4">
                    <h3 class="text-base font-bold text-slate-800">Permisos por Módulo</h3>
                    {{-- BOTONES DE SELECCIÓN RÁPIDA (CREAR) --}}
                    <div class="flex gap-2">
                        <button type="button" onclick="bulkCheck('create-grid', true)" class="text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 py-1.5 px-3 rounded border border-slate-300 transition-all">
                            ✓ Marcar todos
                        </button>
                        <button type="button" onclick="bulkCheck('create-grid', false)" class="text-xs font-bold bg-slate-50 hover:bg-slate-100 text-slate-500 py-1.5 px-3 rounded border border-slate-200 transition-all">
                            ✕ Quitar todos
                        </button>
                    </div>
                </div>
                
                <div id="create-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach ($modulos as $modulo)
                        <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 shadow-sm">
                            <div class="font-bold text-slate-800 mb-2">{{ $modulo }}</div>
                            <label class="flex items-center gap-2 text-sm text-slate-600 mb-1.5 cursor-pointer">
                                <input type="checkbox" name="modulos[]" value="{{ $modulo }}" class="w-4 h-4 text-green-600 rounded"> Puede ver
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                                <input type="checkbox" name="puede_editar[]" value="{{ $modulo }}" class="w-4 h-4 text-green-600 rounded"> Puede editar
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 text-right">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-6 rounded-lg transition-colors shadow-md active:scale-95">
                    Registrar Usuario
                </button>
            </div>
        </form>
    </section>

    {{-- Tabla de Usuarios --}}
    <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <h2 class="text-xl font-bold text-slate-800 p-6 border-b border-slate-100">Usuarios Registrados</h2>

        {{-- Filtro por rol --}}
        <div class="px-6 pb-4">
            <label class="text-sm font-semibold text-slate-600 mr-4">Filtrar por rol:</label>
            <div class="inline-flex gap-2">
                <a href="{{ route('permisos.index') }}" class="px-3 py-1 text-xs font-bold rounded-lg transition-all {{ !$rolFiltro ? 'bg-green-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    Todos
                </a>
                <a href="{{ route('permisos.index', ['rol' => 'ADMIN']) }}" class="px-3 py-1 text-xs font-bold rounded-lg transition-all {{ $rolFiltro == 'ADMIN' ? 'bg-green-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    ADMIN
                </a>
                <a href="{{ route('permisos.index', ['rol' => 'ALMACEN']) }}" class="px-3 py-1 text-xs font-bold rounded-lg transition-all {{ $rolFiltro == 'ALMACEN' ? 'bg-green-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    ALMACEN
                </a>
                <a href="{{ route('permisos.index', ['rol' => 'PROVEEDOR']) }}" class="px-3 py-1 text-xs font-bold rounded-lg transition-all {{ $rolFiltro == 'PROVEEDOR' ? 'bg-green-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    PROVEEDOR
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b">ID</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b">Nombre</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b">Correo</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b">Rol</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b">Estado</th>
                        <th class="p-4 text-xs font-bold uppercase tracking-wider text-slate-500 border-b text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($registros as $registro)
                        <tr class="hover:bg-slate-50/50 transition-colors text-sm text-slate-700" data-user-id="{{ $registro['id'] }}" data-user-rol="{{ $registro['rol'] }}">
                            <td class="p-4 font-bold">#{{ $registro['id'] }}</td>
                            <td class="p-4">{{ $registro['nombre'] }}</td>
                            <td class="p-4">{{ $registro['email'] }}</td>
                            <td class="p-4">
                                <span class="bg-sky-100 text-sky-700 px-2.5 py-1 rounded-md text-xs font-bold uppercase">
                                    {{ $registro['rol'] }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase {{ $registro['estado'] == 'Activo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $registro['estado'] }}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex gap-2 justify-center">
                                    <button type="button" class="edit-user-btn bg-sky-500 hover:bg-sky-600 text-white font-bold py-1.5 px-4 rounded-lg text-xs transition-all shadow-sm active:scale-95"
                                        data-id="{{ $registro['id'] }}"
                                        data-nombre="{{ $registro['nombre'] }}"
                                        data-email="{{ $registro['email'] }}"
                                        data-rol="{{ $registro['rol'] }}"
                                        data-permisos='@json($registro["permisos"] ?? [])'>
                                        Editar
                                    </button>
                                    @if($registro['rol'] == 'ALMACEN')
                                        <button type="button" class="delete-user-btn bg-red-500 hover:bg-red-600 text-white font-bold py-1.5 px-4 rounded-lg text-xs transition-all shadow-sm active:scale-95"
                                            data-id="{{ $registro['id'] }}"
                                            data-nombre="{{ $registro['nombre'] }}">
                                            Eliminar
                                        </button>
                                    @else
                                        <form method="POST" action="{{ route('permisos.toggleEstado', $registro['id']) }}" style="display:inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1.5 px-4 rounded-lg text-xs transition-all shadow-sm active:scale-95">
                                                {{ $registro['estado'] == 'Activo' ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- MODAL DE ELIMINACIÓN --}}
<div id="deleteModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[1001] justify-center items-center p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl p-6 shadow-2xl shadow-black/20">
        <h2 class="text-xl font-bold text-slate-800 mb-4">Confirmar Eliminación</h2>
        <p class="text-slate-600 mb-6">¿Estás seguro de que deseas eliminar a <strong id="deleteUserName"></strong>? Esta acción no se puede deshacer.</p>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeDeleteModal()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2.5 px-6 rounded-lg transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-6 rounded-lg transition-colors shadow-md">
                    Eliminar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL --}}
<div id="editModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[1000] justify-center items-center p-4">
    <div class="bg-white w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl p-6 shadow-2xl shadow-black/20">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">Editar Usuario</h2>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-bold text-slate-700">Nombre Completo</label>
                    <input name="nombre" id="edit_nombre" type="text" required class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-bold text-slate-700">Correo Electrónico</label>
                    <input name="email" id="edit_email" type="email" required class="border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-bold text-slate-700">Rol</label>
                    <select name="rol" id="edit_rol" class="border border-slate-300 rounded-lg p-2.5 text-sm outline-none">
                        <option value="ADMIN">ADMIN</option>
                        <option value="ALMACEN">ALMACEN</option>
                        <option value="PROVEEDOR">PROVEEDOR</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-bold text-slate-700">Contraseña (opcional)</label>
                    <input name="password" type="password" placeholder="Mínimo 6 caracteres" class="border border-slate-300 rounded-lg p-2.5 text-sm outline-none">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-bold text-slate-700">Confirmar Contraseña</label>
                    <input name="password_confirmation" type="password" class="border border-slate-300 rounded-lg p-2.5 text-sm outline-none">
                </div>
            </div>

            <div class="flex flex-wrap justify-between items-center mt-6 mb-4 gap-4">
                <h3 class="text-base font-bold text-slate-800">Permisos Actualizados</h3>
                {{-- BOTONES DE SELECCIÓN RÁPIDA (MODAL) --}}
                <div class="flex gap-2">
                    <button type="button" onclick="bulkCheck('edit-grid', true)" class="text-xs font-bold bg-sky-50 hover:bg-sky-100 text-sky-700 py-1.5 px-3 rounded border border-sky-200 transition-all">
                        ✓ Seleccionar Todo
                    </button>
                    <button type="button" onclick="bulkCheck('edit-grid', false)" class="text-xs font-bold bg-slate-50 hover:bg-slate-100 text-slate-500 py-1.5 px-3 rounded border border-slate-200 transition-all">
                        ✕ Limpiar
                    </button>
                </div>
            </div>

            <div id="edit-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                @foreach ($modulos as $modulo)
                    <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 shadow-sm">
                        <div class="font-bold text-slate-800 mb-2">{{ $modulo }}</div>
                        <label class="flex items-center gap-2 text-sm text-slate-600 mb-1.5 cursor-pointer">
                            <input type="checkbox" name="modulos[]" value="{{ $modulo }}" class="check-ver w-4 h-4 text-sky-600 rounded"> Puede ver
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                            <input type="checkbox" name="puede_editar[]" value="{{ $modulo }}" class="check-editar w-4 h-4 text-sky-600 rounded"> Puede editar
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2.5 px-6 rounded-lg transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-6 rounded-lg transition-colors shadow-md">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    const editForm = document.getElementById('editForm');
    const deleteForm = document.getElementById('deleteForm');

    const permisosPredeterminados = @json($permisosPredeterminados);

    // Función para aplicar permisos predeterminados a un grid
    function aplicarPermisosPredeterminados(rol, gridId) {
        // Limpiar checkboxes
        document.querySelectorAll(`#${gridId} input[type="checkbox"]`).forEach(cb => cb.checked = false);

        if (permisosPredeterminados[rol]) {
            const modulos = permisosPredeterminados[rol].modulos;
            const editar = permisosPredeterminados[rol].puede_editar;

            modulos.forEach(modulo => {
                const verCheck = document.querySelector(`#${gridId} input[name="modulos[]"][value="${modulo}"]`);
                if (verCheck) verCheck.checked = true;
            });

            editar.forEach(modulo => {
                const editCheck = document.querySelector(`#${gridId} input[name="puede_editar[]"][value="${modulo}"]`);
                if (editCheck) editCheck.checked = true;
            });
        }
    }

    // Event listener para el select de creación
    document.getElementById('rol').addEventListener('change', function() {
        const rol = this.value;
        aplicarPermisosPredeterminados(rol, 'create-grid');
    });

    // Event listener para el select de edición
    document.getElementById('edit_rol').addEventListener('change', function() {
        const rol = this.value;
        aplicarPermisosPredeterminados(rol, 'edit-grid');
    });

    // Función principal para marcar/desmarcar todo por contenedor
    function bulkCheck(containerId, status) {
        const container = document.getElementById(containerId);
        const checkboxes = container.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = status);
    }

    // EDITAR USUARIO
    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            editForm.action = `/permisos/usuarios/${id}`;
            
            document.getElementById('edit_nombre').value = this.dataset.nombre;
            document.getElementById('edit_email').value = this.dataset.email;
            document.getElementById('edit_rol').value = this.dataset.rol;
            
            const permisos = JSON.parse(this.dataset.permisos);
            document.querySelectorAll('.check-ver, .check-editar').forEach(cb => cb.checked = false);
            
            permisos.forEach(p => {
                const moduloStr = p.modulo;
                const verCheck = document.querySelector(`#edit-grid .check-ver[value="${moduloStr}"]`);
                const editCheck = document.querySelector(`#edit-grid .check-editar[value="${moduloStr}"]`);
                if(verCheck) verCheck.checked = true;
                if(editCheck && p.puede_editar) editCheck.checked = true;
            });

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    // ELIMINAR USUARIO (con AJAX)
    document.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            const userRow = this.closest('tr');
            
            document.getElementById('deleteUserName').textContent = nombre;
            deleteForm.action = `/permisos/usuarios/${id}`;
            deleteForm.dataset.userId = id;
            deleteForm.dataset.userRowId = userRow?.getAttribute('data-user-id') || id;
            
            deleteModal.classList.remove('hidden');
            deleteModal.classList.add('flex');
        });
    });

    // Manejar submit del formulario de eliminación con AJAX
    deleteForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const url = this.action;
        const userId = this.dataset.userId;
        const userRowId = this.dataset.userRowId;
        
        console.log('Eliminar usuario ID:', userId, 'Row ID:', userRowId);
        
        // Obtener el token CSRF del formulario
        const tokenInput = this.querySelector('input[name="_token"]');
        const csrfToken = tokenInput ? tokenInput.value : '';
        
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Error response:', text);
                    throw new Error(`HTTP ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Delete success:', data);
            closeDeleteModal();
            
            // Remover fila de la tabla usando data-user-id
            const rowToDelete = document.querySelector(`tr[data-user-id="${userRowId}"]`);
            
            if (rowToDelete) {
                console.log('Found row to delete, animating...');
                rowToDelete.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                rowToDelete.style.opacity = '0';
                rowToDelete.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    console.log('Removing row from DOM');
                    rowToDelete.remove();
                }, 300);
            } else {
                console.warn('Row not found with ID:', userRowId);
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            closeDeleteModal();
            alert('Error al eliminar el usuario: ' + error.message);
        });
    });

    function closeModal() { 
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function closeDeleteModal() {
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
    }
    
    window.onclick = (e) => { 
        if(e.target == modal) closeModal();
        if(e.target == deleteModal) closeDeleteModal();
    }
</script>
@endsection