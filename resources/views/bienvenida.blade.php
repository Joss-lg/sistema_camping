@extends('layouts.app', ['hideSidebar' => true])

@section('content')
<style>
@keyframes fadeUp {
    0% {
        opacity: 0;
        transform: translateY(40px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fadeUp {
    animation: fadeUp 1s cubic-bezier(0.4,0,0.2,1) both;
}
.animate-fadeUp-delay-1 {
    animation-delay: 0.2s;
}
.animate-fadeUp-delay-2 {
    animation-delay: 0.4s;
}
.animate-fadeUp-delay-3 {
    animation-delay: 0.6s;
}
</style>
<div class="fixed inset-0 min-h-screen w-full flex flex-col justify-between overflow-hidden">
    <!-- Fondo absoluto -->
    <div class="fixed inset-0 w-full h-full bg-cover bg-center z-0" style="background-image: url('/imagenes/fondo.webp');"></div>
    <!-- Overlay oscuro -->
    <div class="fixed inset-0 w-full h-full bg-black bg-opacity-60 z-0"></div>
    <header class="bg-transparent shadow-none border-none z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-green-700 drop-shadow-lg animate-fadeUp animate-fadeUp-delay-1">LogiCamp</h1>
                <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-lg transition-colors duration-200 animate-fadeUp animate-fadeUp-delay-2">
                    Iniciar sesión
                </a>
            </div>    
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 z-10">
        <div class="max-w-4xl mx-auto text-center">
                            <div class="backdrop-blur-lg rounded-2xl shadow-2xl p-8 md:p-12 border border-white/30 animate-fadeUp animate-fadeUp-delay-3"
                                style="background: linear-gradient(135deg, rgba(20,20,20,0.65) 0%, rgba(40,40,40,0.65) 100%);">
                <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-6 drop-shadow-lg">
                    ¡Bienvenido a <span class="text-green-400">LogiCamp</span>!
                </h1>
                <p class="text-lg md:text-xl text-white leading-relaxed max-w-3xl mx-auto drop-shadow">
                    LogiCamp es un sistema integral de gestión logística y productiva para la manufactura de mochilas y equipo de camping.
                    Optimiza la cadena de suministro desde la adquisición de materia prima hasta el control de producto terminado,
                    garantizando eficiencia operativa y trazabilidad en cada etapa del proceso.
                </p>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-6 z-10 bg-opacity-90 w-full absolute bottom-0 left-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-300">&copy; 2026 LogiCamp. Todos los derechos reservados.</p>
        </div>
    </footer>
</div>
@endsection
