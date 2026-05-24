<x-layouts.app title="Iniciar Sesión - Bet Path">
    <div class="min-h-screen flex items-center justify-center relative overflow-hidden bg-slate-950 px-4">
        <!-- Background Glowing Blobs -->
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl -z-10 animate-pulse"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-violet-600/20 rounded-full blur-3xl -z-10 animate-pulse" style="animation-delay: 2s;"></div>

        <!-- Glassmorphism Card -->
        <div class="w-full max-w-md p-8 rounded-2xl glassmorphism shadow-2xl relative">
            
            <!-- Glow Border Effect -->
            <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

            <!-- Brand / Logo -->
            <div class="text-center mb-8">
                <div class="mx-auto h-16 w-16 rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-white shadow-xl shadow-indigo-500/30 mb-4 animate-bounce" style="animation-duration: 3s;">
                    <i class="fa-solid fa-dice-d20 text-3xl"></i>
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-white via-indigo-100 to-indigo-300 bg-clip-text text-transparent">
                    BET PATH
                </h1>
                <p class="text-slate-400 text-xs mt-2 uppercase tracking-widest font-semibold">
                    Gestor de Apuestas Inteligente
                </p>
            </div>

            <!-- Error Alerts -->
            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm flex gap-3 items-start">
                    <i class="fa-solid fa-circle-exclamation text-base mt-0.5 shrink-0"></i>
                    <div>
                        <span class="font-semibold block mb-0.5">Acceso no autorizado</span>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            <!-- Login Form / Button -->
            <div class="space-y-4">
                <a href="{{ route('auth.google') }}" class="w-full py-4 px-6 rounded-xl bg-white text-slate-900 font-bold hover:bg-slate-100 transition duration-300 shadow-lg shadow-white/5 flex items-center justify-center gap-3 group">
                    <!-- Google SVG Icon -->
                    <svg class="h-5 w-5 shrink-0 transition-transform group-hover:scale-110" viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg">
                        <g transform="matrix(1, 0, 0, 1, 0, 0)">
                            <path d="M21.35,11.1H12v2.7h5.38C16.88,15.86,14.72,17,12,17c-2.76,0-5-2.24-5-5s2.24-5,5-5c1.21,0,2.3,0.43,3.15,1.14l2.02-2.02C15.87,4.89,14.07,4.3,12,4.3c-4.25,0-7.7,3.45-7.7,7.7s3.45,7.7,7.7,7.7c4.08,0,7.3-2.9,7.3-7.7C19.3,11.75,19.35,11.42,21.35,11.1Z" fill="#EA4335" />
                            <path d="M12,4.3c2.07,0,3.87,0.59,5.17,1.82l2.02-2.02C17.89,2.82,15.11,2,12,2,7.75,2,4.3,5.45,4.3,9.7l3.22,2.48C8.24,9.66,9.94,4.3,12,4.3Z" fill="#EA4335" />
                            <path d="M19.3,12c0-0.32-0.05-0.65-0.12-0.9H12v2.7h5.38c-0.24,1.28-0.96,2.36-2.06,3.1l3.16,2.45C18.32,17.43,19.3,14.93,19.3,12Z" fill="#4285F4" />
                            <path d="M12,22c3.24,0,5.97-1.07,7.96-2.91l-3.16-2.45c-0.9,0.6-2.05,0.96-3.32,0.96-2.76,0-5-2.24-5-5L3.22,15.08C4.85,19.18,8.1,22,12,22Z" fill="#34A853" />
                            <path d="M4.3,9.7C3.96,10.68,3.78,11.73,3.78,12.8s0.18,2.12,0.52,3.1L8.8,13.42C8.75,13.12,8.72,12.82,8.72,12.5c0-1.12,0.3-2.17,0.82-3.1Z" fill="#FBBC05" />
                        </g>
                    </svg>
                    <span>Iniciar sesión con Google</span>
                </a>
            </div>

            <!-- Access restriction footer -->
            <div class="mt-8 pt-6 border-t border-slate-800/60 text-center text-xs text-slate-500">
                <i class="fa-solid fa-lock text-indigo-500/60 mr-1.5"></i>
                <span>Sistema de acceso privado. Solo correos autorizados por el administrador pueden iniciar sesión.</span>
            </div>

        </div>
    </div>
</x-layouts.app>
