<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                <i class="fa-solid fa-wand-magic-sparkles text-indigo-400"></i>
                <span>Bet Finder con IA</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Busca y encuentra apuestas reales de alta probabilidad en internet usando IA y búsqueda en tiempo real.</p>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex gap-2 items-center">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errorMessage)
        <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex gap-2 items-center">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ $errorMessage }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
        <!-- Sidebar Filters -->
        <div class="xl:col-span-1">
            <div class="glassmorphism p-6 rounded-2xl relative space-y-5">
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-indigo-400"></i>
                    <span>Filtros de Búsqueda</span>
                </h2>

                <form wire:submit.prevent="search" class="space-y-4">
                    <!-- Sport -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Deporte</label>
                        <select wire:model.defer="sport"
                            class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                            <option value="">Cualquiera</option>
                            @foreach($sportsList as $s)
                                <option value="{{ $s->name }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Risk Level -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Riesgo IA Máximo</label>
                        <select wire:model.defer="risk"
                            class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                            <option value="segura">Segura (Bajo)</option>
                            <option value="moderada">Moderada (Medio)</option>
                            <option value="improbable">Improbable (Alto)</option>
                        </select>
                        @error('risk') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Min Odds -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Cuota Mínima</label>
                        <input type="number" step="0.01" wire:model.defer="minOdds" placeholder="Ej. 1.50"
                            class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                        @error('minOdds') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Max Odds -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Cuota Máxima</label>
                        <input type="number" step="0.01" wire:model.defer="maxOdds" placeholder="Ej. 3.00"
                            class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                        @error('maxOdds') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Time Range -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            <i class="fa-regular fa-clock mr-1"></i> Ventana de Tiempo
                        </label>
                        <select wire:model.defer="timeRangeHours"
                            class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                            <option value="3">Próximas 3 horas</option>
                            <option value="6">Próximas 6 horas</option>
                            <option value="12">Próximas 12 horas</option>
                            <option value="24" selected>Próximas 24 horas</option>
                            <option value="48">Próximas 48 horas</option>
                            <option value="72">Próximos 3 días</option>
                            <option value="168">Próxima semana</option>
                        </select>
                        @error('timeRangeHours') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Result Count -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            <i class="fa-solid fa-list-ol mr-1"></i> Número de Apuestas
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="range" wire:model.live="resultCount" min="1" max="10" step="1"
                                class="flex-1 accent-indigo-500 cursor-pointer">
                            <span class="text-indigo-400 font-black text-sm w-6 text-center">{{ $resultCount }}</span>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-600 mt-1 px-0.5">
                            <span>1</span><span>5</span><span>10</span>
                        </div>
                        @error('resultCount') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Search Button -->
                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-sm transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/10">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Buscar Apuestas</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Recommendations Results -->
        <div class="xl:col-span-3">
            <div class="glassmorphism p-6 rounded-2xl relative min-h-[400px] flex flex-col justify-between">
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

                <div class="w-full">
                    <h2 class="text-lg font-bold text-white mb-5 flex items-center gap-2">
                        <i class="fa-solid fa-square-poll-horizontal text-indigo-400"></i>
                        <span>Apuestas Recomendadas por la IA</span>
                    </h2>

                    <!-- Loading State -->
                    <div wire:loading.flex class="flex-col items-center justify-center py-20 text-slate-400 space-y-4">
                        <div class="relative h-12 w-12">
                            <div class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></div>
                            <div class="relative inline-flex rounded-full h-12 w-12 bg-indigo-500 flex items-center justify-center text-white">
                                <i class="fa-solid fa-wand-magic-sparkles animate-spin"></i>
                            </div>
                        </div>
                        <span class="text-sm font-semibold tracking-wider animate-pulse text-indigo-300">La IA está buscando y analizando partidos reales en internet...</span>
                    </div>

                    <!-- Suggestions List -->
                    <div wire:loading.remove>
                        @if(!empty($suggestions))
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach($suggestions as $index => $rec)
                                    <div class="glassmorphism p-5 rounded-xl border border-slate-800/80 flex flex-col justify-between hover:border-indigo-500/20 transition-all duration-300 relative group overflow-hidden">
                                        <!-- Gradient accent -->
                                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-500 opacity-50 group-hover:opacity-100 transition-opacity"></div>
                                        
                                        <div>
                                            <!-- Top Header -->
                                            <div class="flex justify-between items-start gap-2 mb-3">
                                                <div>
                                                    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest">{{ $rec['sport'] }} | {{ $rec['league'] }}</span>
                                                    <h3 class="text-base font-bold text-white mt-0.5">{{ $rec['home_team'] }} vs {{ $rec['away_team'] }}</h3>
                                                </div>
                                                <div class="flex flex-col items-end gap-1">
                                                    @if(($rec['risk'] ?? '') === 'segura')
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500/15 text-emerald-400 border border-emerald-500/25 uppercase">Segura</span>
                                                    @elseif(($rec['risk'] ?? '') === 'moderada')
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-500/15 text-amber-400 border border-amber-500/25 uppercase">Moderada</span>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-red-500/15 text-red-400 border border-red-500/25 uppercase">Improbable</span>
                                                    @endif
                                                    
                                                    @if(isset($rec['confidence_score']))
                                                        <span class="text-[10px] text-slate-400 font-medium">Confianza: <span class="text-indigo-400 font-bold">{{ $rec['confidence_score'] }}%</span></span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Market and Selection -->
                                            <div class="p-3 bg-slate-900/60 border border-slate-800/80 rounded-lg flex justify-between items-center mb-4">
                                                <div>
                                                    <span class="text-[10px] text-slate-500 block uppercase font-bold tracking-wider">Mercado</span>
                                                    <span class="text-sm font-semibold text-slate-300">{{ $rec['market_name'] }}</span>
                                                </div>
                                                <div class="text-center px-3 border-l border-slate-800">
                                                    <span class="text-[10px] text-slate-500 block uppercase font-bold tracking-wider">Selección</span>
                                                    <span class="text-sm font-bold text-white">{{ $rec['selection'] }}</span>
                                                </div>
                                                <div class="text-right pl-3 border-l border-slate-800">
                                                    <span class="text-[10px] text-slate-500 block uppercase font-bold tracking-wider">Cuota</span>
                                                    <span class="text-base font-black text-emerald-400">x{{ $rec['odds'] }}</span>
                                                </div>
                                            </div>

                                            <!-- Analysis Text -->
                                            <p class="text-xs text-slate-400 leading-relaxed mb-4 italic">
                                                "{{ $rec['analysis'] }}"
                                            </p>
                                        </div>

                                        <!-- Actions -->
                                        <div class="pt-3 border-t border-slate-800/60 flex justify-between items-center">
                                            <span class="text-[10px] text-slate-500 flex items-center gap-1">
                                                <i class="fa-regular fa-calendar"></i>
                                                {{ $rec['match_date'] }}
                                                @if(!empty($rec['match_time']))
                                                    &nbsp;<i class="fa-regular fa-clock"></i> {{ $rec['match_time'] }}
                                                @endif
                                            </span>
                                            <button type="button" wire:click="openRegisterModal({{ $index }})"
                                                class="py-2 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition duration-200 flex items-center gap-1.5 shadow-md shadow-indigo-600/10">
                                                <i class="fa-solid fa-plus"></i> Registrar Apuesta
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-20 text-center text-slate-500 flex flex-col items-center justify-center">
                                <i class="fa-solid fa-wand-magic-sparkles text-5xl mb-4 text-slate-700 animate-pulse"></i>
                                <span class="max-w-md">Ingresa tus filtros preferidos en el panel izquierdo y haz clic en "Buscar Apuestas" para que la IA escanee la web en busca de oportunidades de apuestas en tiempo real.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PRE-REGISTRATION MODAL -->
    @if($showRegisterModal && $selectedSuggestion)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop with blur -->
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-md" wire:click="closeRegisterModal"></div>

            <!-- Modal Content Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full relative z-10 shadow-2xl glassmorphism">
                <!-- Close Button -->
                <button type="button" wire:click="closeRegisterModal" class="absolute top-4 right-4 text-slate-500 hover:text-white transition duration-200">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>

                <!-- Modal Title -->
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-indigo-400"></i>
                    <span>Confirmar Registro</span>
                </h3>

                <!-- Selection summary -->
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 mb-5 space-y-2">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500">Partido:</span>
                        <span class="font-bold text-white">{{ $selectedSuggestion['home_team'] }} vs {{ $selectedSuggestion['away_team'] }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500">Mercado:</span>
                        <span class="font-bold text-slate-300">{{ $selectedSuggestion['market_name'] }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500">Selección:</span>
                        <span class="font-bold text-white">{{ $selectedSuggestion['selection'] }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs border-t border-slate-800/60 pt-2">
                        <span class="text-slate-500">Cuota:</span>
                        <span class="font-black text-emerald-400">x{{ $selectedSuggestion['odds'] }}</span>
                    </div>
                </div>

                <!-- Input form -->
                <form wire:submit.prevent="registerBet" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Monto a Apostar (Stake $)</label>
                        <input type="number" step="0.01" wire:model="stake" placeholder="100.00"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200 text-center text-lg font-bold">
                        @error('stake') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-between items-center p-3 bg-indigo-500/5 rounded-xl border border-indigo-500/10 text-xs">
                        <span class="text-indigo-300">Pago potencial proyectado:</span>
                        <span class="font-black text-emerald-400 text-sm">${{ number_format(($stake ?: 0) * $selectedSuggestion['odds'], 2) }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <button type="button" wire:click="closeRegisterModal"
                            class="w-full py-3 px-4 rounded-xl border border-slate-800 hover:bg-slate-800/50 text-slate-400 hover:text-white font-bold transition duration-200 text-sm">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold transition duration-200 text-sm flex items-center justify-center gap-1.5 shadow-lg shadow-emerald-600/10">
                            <i class="fa-solid fa-circle-check"></i> Registrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
