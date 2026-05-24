<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Registrar Apuesta</h1>
            <p class="text-slate-400 text-sm mt-1">Registra tus apuestas y combínalas para analizarlas con inteligencia artificial o incluirlas en tus Bet Paths.</p>
        </div>
        
        <!-- Quick Switch -->
        <div class="inline-flex rounded-xl bg-slate-900 p-1 border border-slate-800 self-start">
            <button type="button" wire:click="$set('type', 'single')" 
                class="px-4 py-2 rounded-lg text-xs font-bold transition duration-200 {{ $type === 'single' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-ticket mr-1.5"></i> Individual
            </button>
            <button type="button" wire:click="$set('type', 'parlay')" 
                class="px-4 py-2 rounded-lg text-xs font-bold transition duration-200 {{ $type === 'parlay' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-tags mr-1.5"></i> Parlay / Combinada
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex gap-2 items-center">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex gap-2 items-center">
            <i class="fa-solid fa-circle-xmark text-red-400"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Alert for Bet Path -->
    @if($bet_path_id)
        <div class="mb-6 p-4 rounded-xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-300 text-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-lg bg-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                    <i class="fa-solid fa-route"></i>
                </div>
                <div>
                    <span class="font-bold">Vinculado al Bet Path:</span>
                    <span class="text-white">{{ $this->betPathName }}</span>
                </div>
            </div>
            <span class="text-xs px-2.5 py-1 rounded bg-indigo-500/20 font-bold border border-indigo-500/30">Monto sugerido: ${{ $stake }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- FORM LEFT COLUMN: SELECTIONS -->
        <div class="xl:col-span-2 space-y-6">
            
            <!-- Auto-fill with Ticket Screenshot -->
            <div class="glassmorphism rounded-2xl p-6 relative">
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-lg shrink-0 border border-amber-500/20">
                            <i class="fa-solid fa-robot animate-pulse"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">Auto-completar con Captura de Ticket</h3>
                            <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">
                                ¿Tienes una captura de tu ticket de apuestas? Súbela y Gemini extraerá el stake, la cuota y las selecciones automáticamente.
                            </p>
                        </div>
                    </div>
                    
                    <div class="relative shrink-0 self-start sm:self-auto">
                        <input type="file" wire:model="ticketImage" id="ticketImage" class="hidden" accept="image/*" />
                        <label for="ticketImage" class="py-2.5 px-4 rounded-xl bg-slate-900 border border-slate-800 hover:border-indigo-500/40 text-slate-300 hover:text-white text-xs font-bold transition duration-150 cursor-pointer flex items-center gap-2">
                            <i class="fa-solid fa-image text-indigo-400"></i>
                            <span>Seleccionar Imagen</span>
                        </label>
                    </div>
                </div>

                <!-- Livewire Loading Indicator -->
                <div wire:loading wire:target="ticketImage" class="mt-4 flex items-center gap-2 text-xs text-indigo-400 font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-brain animate-bounce"></i>
                    <span>Gemini 2.5 Flash está procesando tu ticket...</span>
                </div>
            </div>

            @foreach($selections as $index => $sel)
                <div class="glassmorphism rounded-2xl p-6 relative">
                    <!-- Glow Border -->
                    <div class="absolute inset-0 rounded-2xl border border-indigo-500/5 pointer-events-none"></div>

                    <!-- Selection Header -->
                    <div class="flex justify-between items-center pb-4 mb-4 border-b border-slate-800/60">
                        <span class="text-xs font-bold text-indigo-400 uppercase tracking-widest">
                            Selección #{{ $index + 1 }}
                        </span>
                        @if(count($selections) > 1)
                            <button type="button" wire:click="removeSelection({{ $index }})" 
                                class="text-xs text-red-400 hover:text-red-300 flex items-center gap-1.5 transition duration-150">
                                <i class="fa-solid fa-trash text-[10px]"></i>
                                <span>Remover</span>
                            </button>
                        @endif
                    </div>

                    <!-- Fields Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        
                        <!-- Sport -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Deporte</label>
                            <select wire:model.live="selections.{{ $index }}.sport_id" 
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-3 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition duration-200">
                                <option value="">Selecciona un deporte...</option>
                                @foreach($sports as $sport)
                                    <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                                @endforeach
                            </select>
                            @error("selections.{$index}.sport_id") <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- League -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Liga / Torneo</label>
                            <select wire:model.live="selections.{{ $index }}.league_id" 
                                @disabled(empty($sel['sport_id']))
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-3 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition duration-200 disabled:opacity-50">
                                <option value="">Selecciona una liga...</option>
                                @if(!empty($leagues[$index]))
                                    @foreach($leagues[$index] as $league)
                                        <option value="{{ $league->id }}">{{ $league->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error("selections.{$index}.league_id") <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Team Home (Local) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Equipo Local / A</label>
                            <div class="flex gap-2">
                                <select wire:model.live="selections.{{ $index }}.team_home_id" 
                                    @disabled(empty($sel['league_id']))
                                    class="flex-1 bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-3 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition duration-200 disabled:opacity-50">
                                    <option value="">Selecciona equipo local...</option>
                                    @if(!empty($teams[$index]))
                                        @foreach($teams[$index] as $team)
                                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @if(auth()->user()->is_admin && !empty($sel['league_id']))
                                    <button type="button" wire:click="openQuickTeamModal({{ $index }}, 'team_home_id')"
                                        class="px-3 rounded-xl bg-indigo-600/20 text-indigo-300 border border-indigo-500/20 hover:bg-indigo-600 hover:text-white transition duration-150 shrink-0"
                                        title="Agregar nuevo equipo local">
                                        <i class="fa-solid fa-plus text-xs"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Team Away (Visitante) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Equipo Visitante / B</label>
                            <div class="flex gap-2">
                                <select wire:model.live="selections.{{ $index }}.team_away_id" 
                                    @disabled(empty($sel['league_id']))
                                    class="flex-1 bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-3 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition duration-200 disabled:opacity-50">
                                    <option value="">Selecciona equipo visitante...</option>
                                    @if(!empty($teams[$index]))
                                        @foreach($teams[$index] as $team)
                                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @if(auth()->user()->is_admin && !empty($sel['league_id']))
                                    <button type="button" wire:click="openQuickTeamModal({{ $index }}, 'team_away_id')"
                                        class="px-3 rounded-xl bg-indigo-600/20 text-indigo-300 border border-indigo-500/20 hover:bg-indigo-600 hover:text-white transition duration-150 shrink-0"
                                        title="Agregar nuevo equipo visitante">
                                        <i class="fa-solid fa-plus text-xs"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Player (Opcional) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Jugador (Opcional)</label>
                            <select wire:model.live="selections.{{ $index }}.player_id" 
                                @disabled(empty($sel['team_home_id']))
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-3 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition duration-200 disabled:opacity-50">
                                <option value="">Selecciona un jugador...</option>
                                @if(!empty($players[$index]))
                                    @foreach($players[$index] as $player)
                                        <option value="{{ $player->id }}">{{ $player->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <!-- Market -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Mercado</label>
                            <input type="text" wire:model.live="selections.{{ $index }}.market_name" placeholder="Ej. Línea de Dinero, Más de 2.5"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-indigo-500 transition duration-200">
                            @error("selections.{$index}.market_name") <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Selection -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tu Selección</label>
                            <input type="text" wire:model.live="selections.{{ $index }}.selection" placeholder="Ej. Real Madrid, Más de 2.5"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-indigo-500 transition duration-200">
                            @error("selections.{$index}.selection") <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Odds -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Cuota / Odds</label>
                            <input type="number" step="0.01" wire:model.live="selections.{{ $index }}.odds" placeholder="Ej. 1.85"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-indigo-500 transition duration-200">
                            @error("selections.{$index}.odds") <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                    </div>
                </div>
            @endforeach

            <!-- Add Selection Button (For Parlay) -->
            @if($type === 'parlay')
                <button type="button" wire:click="addSelection" 
                    class="w-full py-4 rounded-xl border border-dashed border-slate-800 hover:border-indigo-500/50 hover:bg-indigo-600/5 text-slate-400 hover:text-white font-bold transition duration-200 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus-circle text-sm text-indigo-400"></i>
                    <span>Agregar Selección al Parlay</span>
                </button>
            @endif
        </div>

        <!-- RIGHT SIDEBAR COLUMN: SUMMARY & SUBMIT -->
        <div class="xl:col-span-1">
            <div class="glassmorphism rounded-2xl p-6 relative sticky top-6">
                <!-- Glow Border -->
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

                <h2 class="text-lg font-bold text-white mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-calculator text-indigo-400"></i>
                    <span>Resumen de Apuesta</span>
                </h2>

                <form wire:submit.prevent="saveBet" class="space-y-5">
                    
                    <!-- Stake -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Monto a Apostar ($)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                                <i class="fa-solid fa-dollar-sign"></i>
                            </div>
                            <input type="number" step="0.01" wire:model.live="stake" placeholder="0.00"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 pl-8 pr-4 text-white text-lg font-bold focus:outline-none focus:border-indigo-500 transition duration-200">
                        </div>
                        @error('stake') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Notas / Estrategia (Opcional)</label>
                        <textarea wire:model="notes" placeholder="Agrega anotaciones sobre la apuesta..." rows="3"
                            class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-3 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-indigo-500 transition duration-200"></textarea>
                    </div>

                    <!-- Calculated Calculations Card -->
                    <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 space-y-3">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-400">Tipo de Apuesta:</span>
                            <span class="font-bold text-indigo-400 uppercase">{{ $type === 'single' ? 'Individual' : 'Parlay (' . count($selections) . ' selecciones)' }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-400">Cuota Total:</span>
                            <span class="font-black text-white text-lg">x{{ $this->calculatedOdds }}</span>
                        </div>
                        <div class="border-t border-slate-800/60 pt-2 flex justify-between items-center">
                            <span class="text-xs text-slate-400 font-bold">Pago Potencial:</span>
                            <span class="font-black text-emerald-400 text-xl">${{ number_format($this->potentialPayout, 2) }}</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-4 px-6 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-extrabold text-base tracking-wide transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/10">
                        <i class="fa-solid fa-save"></i>
                        <span>Registrar Apuesta</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Add Team Modal -->
    @if($showQuickTeamModal)
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="w-full max-w-sm rounded-2xl glassmorphism p-6 relative">
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

                <div class="flex justify-between items-center pb-3 border-b border-slate-800 mb-5">
                    <h3 class="text-lg font-bold text-white">Agregar Nuevo Equipo</h3>
                    <button type="button" wire:click="$set('showQuickTeamModal', false)" class="text-slate-500 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form wire:submit.prevent="saveQuickTeam" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Liga / Torneo</label>
                        <input type="text" readonly value="{{ \App\Models\League::find($quickTeamLeagueId)?->name ?? 'N/A' }}"
                            class="w-full bg-slate-900/40 border border-slate-800/60 rounded-xl py-3 px-3 text-sm text-slate-500 focus:outline-none cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nombre del Equipo</label>
                        <input type="text" wire:model="quickTeamName" placeholder="Ej. Liverpool, Golden State Warriors"
                            class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 transition duration-200">
                        @error('quickTeamName') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-3">
                        <button type="button" wire:click="$set('showQuickTeamModal', false)"
                            class="py-2.5 px-4 rounded-xl border border-slate-800 text-slate-400 hover:text-white text-xs font-bold transition duration-150">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="py-2.5 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition duration-150 shadow-md shadow-indigo-600/10">
                            Guardar y Seleccionar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
