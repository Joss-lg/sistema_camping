<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LogiCamp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen grid place-items-center p-5 font-sans bg-[radial-gradient(circle_at_top_right,_#1e293b,_#0f172a)]">

    <div class="w-full max-w-[420px] bg-white border border-slate-200 rounded-[14px] p-6 shadow-xl">
        
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Iniciar sesión</h1>
        <p class="text-slate-500 mb-[18px]">Accede al sistema LogiCamp.</p>

        @if ($errors->any())
            <div class="mb-3 border border-red-200 bg-red-50 text-red-800 rounded-[10px] p-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            
            <div class="mb-[14px]">
                <label for="email" class="block text-[0.92rem] mb-1.5 text-slate-900 font-medium">
                    Correo
                </label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                    class="w-full border border-slate-200 rounded-[10px] px-3 py-2.5 text-[0.95rem] focus:outline-none focus:ring-2 focus:ring-green-600/20 focus:border-green-600 transition-all">
            </div>

            <div class="mb-[14px]">
                <label for="password" class="block text-[0.92rem] mb-1.5 text-slate-900 font-medium">
                    Contraseña
                </label>
                <input type="password" id="password" name="password" required
                    class="w-full border border-slate-200 rounded-[10px] px-3 py-2.5 text-[0.95rem] focus:outline-none focus:ring-2 focus:ring-green-600/20 focus:border-green-600 transition-all">
            </div>

            <button type="submit" 
                class="w-full border-0 rounded-[10px] py-[11px] px-3 bg-green-600 hover:bg-green-700 text-white font-bold cursor-pointer transition-colors shadow-sm active:scale-[0.98]">
                Ingresar
            </button>
        </form>
    </div>

</body>
</html>