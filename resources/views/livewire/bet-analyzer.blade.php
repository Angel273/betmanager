<div>
    @if($analyzingId)
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex flex-col items-center justify-center p-4">
            <div class="w-full max-w-sm p-8 rounded-2xl glassmorphism border border-indigo-500/20 text-center relative overflow-hidden shadow-2xl">
                <!-- Glowing background blob -->
                <div class="absolute -top-12 -left-12 w-32 h-32 bg-indigo-600/30 rounded-full blur-2xl -z-10 animate-pulse"></div>
                
                <!-- Rotating AI Icon -->
                <div class="relative inline-flex items-center justify-center h-16 w-16 bg-gradient-to-tr from-indigo-500 to-violet-500 rounded-2xl text-white shadow-xl shadow-indigo-500/25 mb-6">
                    <i class="fa-solid fa-robot text-3xl animate-bounce" style="animation-duration: 2s;"></i>
                    <div class="absolute inset-0 rounded-2xl border-4 border-indigo-200/20 border-t-white animate-spin"></div>
                </div>

                <h3 class="text-lg font-extrabold text-white mb-2">Conectando con Google AI Studio...</h3>
                <p class="text-slate-400 text-xs leading-relaxed max-w-xs mx-auto mb-4">
                    Investigando forma reciente de equipos, bajas importantes y cuotas para calcular el nivel de riesgo de tu apuesta.
                </p>
                
                <div class="flex items-center justify-center gap-1.5 text-[10px] font-bold text-indigo-400 uppercase tracking-widest">
                    <span class="h-2 w-2 rounded-full bg-indigo-400 animate-ping"></span>
                    <span>Gemini 3.1 Flash Lite está analizando</span>
                </div>
            </div>
        </div>
    @endif
</div>
