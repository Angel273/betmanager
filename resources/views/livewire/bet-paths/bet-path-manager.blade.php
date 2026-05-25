<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Bet Path</h1>
            <p class="text-slate-400 text-sm mt-1">Calcula, visualiza y ejecuta rutas de retos de apuestas progresivos con reinversión.</p>
        </div>
        
        <!-- Section Navigation -->
        <div class="inline-flex rounded-xl bg-slate-900 p-1 border border-slate-800 self-start">
            <button type="button" wire:click="changeSection('active')" 
                class="px-4 py-2 rounded-lg text-xs font-bold transition duration-200 {{ $activeSection === 'active' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-list-check mr-1.5"></i> Retos Activos
            </button>
            <button type="button" wire:click="changeSection('create')" 
                class="px-4 py-2 rounded-lg text-xs font-bold transition duration-200 {{ $activeSection === 'create' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-calculator mr-1.5"></i> Crear Reto
            </button>
            <button type="button" wire:click="changeSection('history')" 
                class="px-4 py-2 rounded-lg text-xs font-bold transition duration-200 {{ $activeSection === 'history' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-clock-rotate-left mr-1.5"></i> Historial
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
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- SECTION: ACTIVE PATHS -->
    @if($activeSection === 'active')
        <div class="space-y-8">
            @forelse($activePaths as $path)
                @php
                    $percent = round(($path->current_step - 1) / $path->total_steps * 100);
                    $currentStepData = $path->steps->where('step_number', $path->current_step)->first();
                @endphp
                <div class="glassmorphism rounded-2xl p-6 relative">
                    <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

                    <!-- Header -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pb-4 mb-6 border-b border-slate-800/60">
                        <div>
                            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                                <i class="fa-solid fa-route text-indigo-400"></i>
                                <span>{{ $path->name }}</span>
                            </h2>
                            <p class="text-xs text-slate-400 mt-1">
                                Meta: <span class="text-emerald-400 font-bold">${{ number_format($path->target_amount, 2) }}</span> 
                                | Inicio: <span class="text-indigo-400 font-bold">${{ number_format($path->start_amount, 2) }}</span> 
                                | Reinversión: <span class="text-indigo-300 font-bold">{{ $path->reinvestment_rate }}%</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <span class="text-xs text-slate-400 block uppercase tracking-wider font-semibold">Progreso</span>
                                <span class="text-sm font-black text-indigo-400">Paso {{ $path->current_step }} de {{ $path->total_steps }}</span>
                            </div>
                            <button wire:click="deleteBetPath({{ $path->id }})" 
                                wire:confirm="¿Estás seguro de que deseas eliminar este Bet Path? (Se perderá todo el progreso)."
                                class="p-2 text-slate-500 hover:text-red-400 rounded-lg hover:bg-red-500/10 transition duration-200" title="Eliminar Reto">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-slate-900 rounded-full h-3 mb-8 overflow-hidden border border-slate-800">
                        <div class="bg-gradient-to-r from-indigo-500 to-violet-500 h-3 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                    </div>

                    <!-- Steps Dashboard Split -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left Column: Current Step Status -->
                        <div class="lg:col-span-1 space-y-6">
                            <div class="p-5 rounded-2xl bg-indigo-600/5 border border-indigo-500/15 space-y-4">
                                <div class="text-center pb-3 border-b border-indigo-500/10">
                                    <span class="text-[10px] font-bold tracking-widest text-indigo-400 uppercase">Paso Actual</span>
                                    <span class="block text-4xl font-extrabold text-white mt-1">#{{ $path->current_step }}</span>
                                </div>
                                
                                <div class="space-y-2.5 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Stake Proyectado:</span>
                                        <span class="font-bold text-white">${{ number_format($currentStepData->expected_stake, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Cuota Recomendada:</span>
                                        <span class="font-bold text-indigo-400">x{{ $currentStepData->calculated_odds }}</span>
                                    </div>
                                    <div class="flex justify-between border-t border-indigo-500/10 pt-2.5">
                                        <span class="text-slate-400 font-semibold">Pago Esperado:</span>
                                        <span class="font-bold text-emerald-400">${{ number_format($currentStepData->expected_payout, 2) }}</span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                @if(!$currentStepData->bet_id)
                                    <div class="mt-4 space-y-2">
                                        <!-- Convert step to bet -->
                                        <a href="{{ route('bets.register', ['bet_path_id' => $path->id, 'step' => $path->current_step, 'stake' => $currentStepData->expected_stake]) }}"
                                            class="w-full py-2.5 px-4 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 font-bold transition duration-200 flex items-center justify-center gap-2 border border-slate-700 text-xs">
                                            <i class="fa-solid fa-circle-plus text-slate-400"></i>
                                            <span>Registro Manual</span>
                                        </a>

                                        <!-- Suggest with IA -->
                                        <button type="button" wire:click="openSuggestionModal({{ $path->id }}, {{ $path->current_step }}, {{ $currentStepData->calculated_odds }}, {{ $currentStepData->expected_stake }})"
                                            class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/15 text-sm">
                                            <i class="fa-solid fa-wand-magic-sparkles animate-pulse"></i>
                                            <span>Sugerir con IA</span>
                                        </button>
                                    </div>
                                @else
                                    <!-- Settle Bet actions -->
                                    <div class="pt-2 border-t border-indigo-500/10 space-y-2">
                                        <span class="text-[10px] font-bold text-indigo-300 uppercase block mb-1">Apuesta Vinculada</span>
                                        <div class="p-3 rounded-lg bg-slate-900/60 border border-slate-800 text-xs flex justify-between items-center mb-3">
                                            <span class="text-slate-400">Estado:</span>
                                            <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30 uppercase tracking-wide">Pendiente</span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2">
                                            <button wire:click="settleLinkedBet({{ $currentStepData->bet->id }}, 'won')"
                                                class="py-2.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition duration-150 flex items-center justify-center gap-1.5">
                                                <i class="fa-solid fa-circle-check"></i> Ganada
                                            </button>
                                            <button wire:click="settleLinkedBet({{ $currentStepData->bet->id }}, 'lost')"
                                                class="py-2.5 px-3 rounded-lg bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition duration-150 flex items-center justify-center gap-1.5">
                                                <i class="fa-solid fa-circle-xmark"></i> Perdida
                                            </button>
                                        </div>
                                        
                                        <!-- Other settlements dropdown -->
                                        <div class="grid grid-cols-3 gap-1 pt-1">
                                            <button wire:click="settleLinkedBet({{ $currentStepData->bet->id }}, 'voided')"
                                                class="py-1.5 px-2 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-semibold transition duration-150">
                                                Void / Anular
                                            </button>
                                            <button wire:click="settleLinkedBet({{ $currentStepData->bet->id }}, 'half_won')"
                                                class="py-1.5 px-2 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-semibold transition duration-150">
                                                Mitad Ganada
                                            </button>
                                            <button wire:click="settleLinkedBet({{ $currentStepData->bet->id }}, 'half_lost')"
                                                class="py-1.5 px-2 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-semibold transition duration-150">
                                                Mitad Perdida
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Right Column: Full Steps Progress Table -->
                        <div class="lg:col-span-2">
                            <div class="rounded-2xl border border-slate-800/80 overflow-hidden bg-slate-900/30">
                                <div class="p-4 bg-slate-900/60 border-b border-slate-800 flex justify-between items-center">
                                    <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Tabla de Ruta</span>
                                    <span class="text-xs text-slate-400">Reversión: {{ $path->reinvestment_rate }}%</span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse text-xs">
                                        <thead>
                                            <tr class="border-b border-slate-800 text-slate-500 uppercase tracking-widest font-bold">
                                                <th class="py-2.5 px-4 text-center">Paso</th>
                                                <th class="py-2.5 px-4">Apuesta (Stake)</th>
                                                <th class="py-2.5 px-4">Cuota</th>
                                                <th class="py-2.5 px-4 text-center">Retorno (Payout)</th>
                                                <th class="py-2.5 px-4 text-center">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-800/40">
                                            @foreach($path->steps as $s)
                                                @php
                                                    $isCurrent = $s->step_number == $path->current_step;
                                                    $isPast = $s->step_number < $path->current_step;
                                                @endphp
                                                <tr class="transition duration-150 {{ $isCurrent ? 'bg-indigo-500/5 font-semibold text-white' : ($isPast ? 'text-slate-400' : 'text-slate-600') }}">
                                                    <td class="py-3 px-4 text-center">
                                                        <span class="inline-flex h-5 w-5 rounded-full items-center justify-center font-bold {{ $isCurrent ? 'bg-indigo-500 text-white' : ($isPast ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-600') }}">
                                                            {{ $s->step_number }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3 px-4">${{ number_format($s->expected_stake, 2) }}</td>
                                                    <td class="py-3 px-4">x{{ $s->calculated_odds }}</td>
                                                    <td class="py-3 px-4 text-center text-emerald-400/80">${{ number_format($s->expected_payout, 2) }}</td>
                                                    <td class="py-3 px-4 text-center">
                                                        @if($s->status === 'won')
                                                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20">Completado</span>
                                                        @elseif($s->status === 'lost')
                                                            <span class="px-2 py-0.5 rounded-full bg-red-500/10 text-red-400 font-bold border border-red-500/20">Fallido</span>
                                                        @elseif($isCurrent)
                                                            @if($s->bet_id)
                                                                <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 font-bold border border-amber-500/20">Apuesta Hecha</span>
                                                            @else
                                                                <span class="px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 font-bold border border-indigo-500/20 animate-pulse">Pendiente</span>
                                                            @endif
                                                        @else
                                                            <span class="text-slate-700">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="glassmorphism p-12 rounded-2xl relative text-center text-slate-400">
                    <div class="absolute inset-0 rounded-2xl border border-indigo-500/5 pointer-events-none"></div>
                    <i class="fa-solid fa-route text-5xl text-slate-700 mb-4 block"></i>
                    <h3 class="text-lg font-bold text-white mb-1">Sin retos activos</h3>
                    <p class="text-sm text-slate-500 max-w-sm mx-auto mb-6">No tienes ningún Bet Path en ejecución en este momento. ¡Calcula y crea uno nuevo para empezar!</p>
                    <button wire:click="changeSection('create')" class="py-3 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition duration-200">
                        Crear Nuevo Bet Path
                    </button>
                </div>
            @endforelse
        </div>
    @endif

    <!-- SECTION: CREATE BET PATH -->
    @if($activeSection === 'create')
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <!-- Parameters Form -->
            <div class="xl:col-span-1">
                <div class="glassmorphism p-6 rounded-2xl relative space-y-5">
                    <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-indigo-400"></i>
                        <span>Parámetros del Reto</span>
                    </h2>

                    <form wire:submit.prevent="createBetPath" class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre del Reto</label>
                            <input type="text" wire:model.defer="name" placeholder="Ej. Reto $2 a $16k"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                            @error('name') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Start Amount -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Monto Inicial ($)</label>
                            <input type="number" step="0.01" wire:model.live="start_amount" placeholder="2.00"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                            @error('start_amount') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Target Amount -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Monto Meta ($)</label>
                            <input type="number" step="0.01" wire:model.live="target_amount" placeholder="16000.00"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                            @error('target_amount') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Reinvestment Rate -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Porcentaje de Reinversión (%)</label>
                            <input type="number" step="1" wire:model.live="reinvestment_rate" placeholder="100"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                            <span class="block text-[10px] text-slate-500 mt-1">Ej. 100% reinvierte todo. 80% guarda el 20% de ganancia de cada paso en tu bolsillo.</span>
                            @error('reinvestment_rate') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Mode Switch -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Modalidad de Cálculo</label>
                            <select wire:model.live="mode"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                                <option value="steps">Calcular número de pasos (dada una cuota)</option>
                                <option value="odds">Calcular cuota recomendada (dado un número de pasos)</option>
                            </select>
                        </div>

                        <!-- Average Odds (if mode === steps) -->
                        @if($mode === 'steps')
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Cuota Promedio Proyectada</label>
                                <input type="number" step="0.01" wire:model.live="avg_odds" placeholder="Ej. 1.80"
                                    class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                                @error('avg_odds') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <!-- Num Steps (if mode === odds) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Número de Pasos Deseados</label>
                                <input type="number" step="1" wire:model.live="num_steps" placeholder="Ej. 10"
                                    class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                                @error('num_steps') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <button type="submit" @disabled(empty($previewSteps))
                            class="w-full py-3.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-extrabold text-base transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/10">
                            <i class="fa-solid fa-route"></i>
                            <span>Iniciar Bet Path</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Preview Column -->
            <div class="xl:col-span-2">
                <div class="glassmorphism p-6 rounded-2xl relative h-full flex flex-col justify-between">
                    <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

                    <div>
                        <h2 class="text-lg font-bold text-white mb-5 flex items-center gap-2">
                            <i class="fa-solid fa-eye text-indigo-400"></i>
                            <span>Vista Previa del Reto</span>
                        </h2>

                        @if(!empty($previewSteps))
                            <!-- Summary Row -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                                <div class="p-3 bg-slate-900/60 border border-slate-800 rounded-xl text-center">
                                    <span class="text-[10px] text-slate-400 block uppercase tracking-wider font-semibold">Total Pasos</span>
                                    <span class="text-lg font-black text-white">{{ $calculatedStepsCount }}</span>
                                </div>
                                <div class="p-3 bg-slate-900/60 border border-slate-800 rounded-xl text-center">
                                    <span class="text-[10px] text-slate-400 block uppercase tracking-wider font-semibold">Cuota / Odds</span>
                                    <span class="text-lg font-black text-indigo-400">x{{ $calculatedOdds }}</span>
                                </div>
                                <div class="p-3 bg-slate-900/60 border border-slate-800 rounded-xl text-center">
                                    <span class="text-[10px] text-slate-400 block uppercase tracking-wider font-semibold">Monto Meta</span>
                                    <span class="text-lg font-black text-emerald-400">${{ number_format($target_amount, 2) }}</span>
                                </div>
                                <div class="p-3 bg-slate-900/60 border border-slate-800 rounded-xl text-center">
                                    <span class="text-[10px] text-slate-400 block uppercase tracking-wider font-semibold">Reinvertido</span>
                                    <span class="text-lg font-black text-indigo-300">{{ $reinvestment_rate }}%</span>
                                </div>
                            </div>

                            <!-- Steps Table Preview -->
                            <div class="rounded-xl border border-slate-800 overflow-hidden">
                                <div class="overflow-x-auto max-h-[300px]">
                                    <table class="w-full text-left border-collapse text-xs">
                                        <thead>
                                            <tr class="bg-slate-900/60 border-b border-slate-800 text-slate-400 uppercase tracking-widest font-bold">
                                                <th class="py-2.5 px-4 text-center">Paso</th>
                                                <th class="py-2.5 px-4">Apuesta (Stake)</th>
                                                <th class="py-2.5 px-4">Cuota</th>
                                                <th class="py-2.5 px-4 text-center">Retorno (Payout)</th>
                                                <th class="py-2.5 px-4 text-center">Retirado / Bolsillo</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-800/40 text-slate-300">
                                            @foreach($previewSteps as $step)
                                                <tr class="hover:bg-slate-900/20 transition duration-150">
                                                    <td class="py-2.5 px-4 text-center">
                                                        <span class="inline-flex h-5 w-5 rounded-full bg-slate-800 text-slate-300 font-bold items-center justify-center">
                                                            {{ $step['step_number'] }}
                                                        </span>
                                                    </td>
                                                    <td class="py-2.5 px-4 font-semibold">${{ number_format($step['stake'], 2) }}</td>
                                                    <td class="py-2.5 px-4 text-indigo-400">x{{ $step['odds'] }}</td>
                                                    <td class="py-2.5 px-4 text-center text-emerald-400">${{ number_format($step['payout'], 2) }}</td>
                                                    <td class="py-2.5 px-4 text-center text-slate-400">
                                                        ${{ number_format($step['pocketed'], 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="py-16 text-center text-slate-600 flex flex-col items-center justify-center">
                                <i class="fa-solid fa-chart-line text-5xl mb-4 text-slate-800"></i>
                                <span>Ingresa los parámetros en el panel izquierdo para ver la proyección paso a paso de tu reto.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- SECTION: HISTORY -->
    @if($activeSection === 'history')
        <div class="glassmorphism p-6 rounded-2xl relative">
            <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

            <h2 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                <i class="fa-solid fa-box-archive text-indigo-400"></i>
                <span>Historial de Retos Bet Path</span>
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4">Nombre del Reto</th>
                            <th class="py-3 px-4">Inicio</th>
                            <th class="py-3 px-4">Meta</th>
                            <th class="py-3 px-4">Avance</th>
                            <th class="py-3 px-4">Monto Final</th>
                            <th class="py-3 px-4">Estado</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @forelse($historyPaths as $path)
                            <tr class="hover:bg-slate-900/10 transition duration-150">
                                <td class="py-4 px-4 font-bold text-white">{{ $path->name }}</td>
                                <td class="py-4 px-4 text-slate-400">${{ number_format($path->start_amount, 2) }}</td>
                                <td class="py-4 px-4 text-slate-400">${{ number_format($path->target_amount, 2) }}</td>
                                <td class="py-4 px-4 text-slate-400">
                                    Paso {{ $path->current_step }} de {{ $path->total_steps }}
                                </td>
                                <td class="py-4 px-4 text-slate-300 font-semibold">
                                    ${{ number_format($path->current_amount ?? 0, 2) }}
                                </td>
                                <td class="py-4 px-4">
                                    @if($path->status === 'completed')
                                        <span class="px-2.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 text-xs font-bold border border-emerald-500/20 uppercase tracking-wider">Completado</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded bg-red-500/10 text-red-400 text-xs font-bold border border-red-500/20 uppercase tracking-wider">Fallido</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <button wire:click="deleteBetPath({{ $path->id }})" 
                                        wire:confirm="¿Estás seguro de que deseas eliminar este registro histórico?"
                                        class="p-2 text-slate-500 hover:text-red-400 rounded-lg hover:bg-red-500/10 transition duration-200">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-500">
                                    <div class="text-3xl mb-3 text-slate-700">
                                        <i class="fa-solid fa-box-open"></i>
                                    </div>
                                    <span>No hay retos históricos en el registro.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 pt-4 border-t border-slate-800/40">
                {{ $historyPaths->links() }}
            </div>
        </div>
    @endif

    <!-- IA SUGGESTION STEP MODAL -->
    @if($showSuggestionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop with blur -->
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-md" wire:click="$set('showSuggestionModal', false)"></div>

            <!-- Modal Content Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-lg w-full relative z-10 shadow-2xl glassmorphism">
                <!-- Close Button -->
                <button type="button" wire:click="$set('showSuggestionModal', false)" class="absolute top-4 right-4 text-slate-500 hover:text-white transition duration-200">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>

                <!-- Modal Title -->
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles text-indigo-400"></i>
                    <span>Asistente de Apuestas con IA</span>
                </h3>

                @if($suggestingErrorMessage)
                    <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex gap-2 items-center">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ $suggestingErrorMessage }}</span>
                    </div>
                @endif

                <!-- Parameters display -->
                <div class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 mb-5 text-xs">
                    <div>
                        <span class="text-slate-500 block uppercase font-bold tracking-wider mb-0.5">Cuota Objetivo</span>
                        <span class="text-base font-black text-indigo-400">x{{ $suggestedStepOdds }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block uppercase font-bold tracking-wider mb-0.5">Monto de Inversión</span>
                        <span class="text-base font-black text-emerald-400">${{ number_format($suggestedStepStake, 2) }}</span>
                    </div>
                </div>

                <!-- Input preferences -->
                @if(!$suggestedStepData && !$suggestingLoading)
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Deporte Preferido</label>
                            <select wire:model.defer="suggestedStepSport"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl py-3 px-4 text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                                <option value="">Cualquiera</option>
                                <option value="Fútbol">Fútbol / Soccer</option>
                                <option value="Básquetbol">Básquetbol / NBA</option>
                                <option value="Béisbol">Béisbol / MLB</option>
                                <option value="Hockey sobre Hielo">Hockey sobre Hielo / NHL</option>
                                <option value="Tenis">Tenis</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Fecha del Partido</label>
                            <input type="date" wire:model.defer="suggestedStepDate" min="{{ date('Y-m-d') }}"
                                style="color-scheme: dark;"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl py-3 px-4 text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                            @error('suggestedStepDate') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <button type="button" wire:click="getStepSuggestions"
                            class="w-full py-3.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold text-sm transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/10">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>Buscar Combinación con IA</span>
                        </button>
                    </div>
                @endif

                <!-- Loading State -->
                @if($suggestingLoading)
                    <div class="flex flex-col items-center justify-center py-12 text-slate-400 space-y-4">
                        <div class="relative h-12 w-12">
                            <div class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></div>
                            <div class="relative inline-flex rounded-full h-12 w-12 bg-indigo-500 flex items-center justify-center text-white">
                                <i class="fa-solid fa-wand-magic-sparkles animate-spin"></i>
                            </div>
                        </div>
                        <span class="text-sm font-semibold tracking-wider animate-pulse text-indigo-300">Buscando apuestas reales en internet para llegar a cuota x{{ $suggestedStepOdds }}...</span>
                    </div>
                @endif

                <!-- Results Display -->
                @if($suggestedStepData && !$suggestingLoading)
                    <div class="space-y-4">
                        <!-- Strategy & Confidence Badge -->
                        <div class="flex justify-between items-center pb-2 border-b border-slate-850">
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                                Estrategia: <span class="text-indigo-400">{{ ($suggestedStepData['strategy'] ?? 'single') === 'parlay' ? 'Parlay Combinado' : 'Apuesta Individual' }}</span>
                            </span>
                            @if(isset($suggestedStepData['confidence_score']))
                                <span class="text-[10px] font-semibold text-slate-400">
                                    Confianza: <span class="text-emerald-400 font-bold">{{ $suggestedStepData['confidence_score'] }}%</span>
                                </span>
                            @endif
                        </div>

                        <!-- Selections List -->
                        <div class="space-y-3 max-h-[220px] overflow-y-auto pr-1">
                            @foreach($suggestedStepData['selections'] as $sel)
                                <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 flex justify-between items-center text-xs">
                                    <div>
                                        <span class="text-[9px] font-bold text-indigo-400 uppercase tracking-widest block">{{ $sel['sport'] ?? 'Otros' }} | {{ $sel['league'] ?? 'General' }}</span>
                                        <span class="font-bold text-white block mt-0.5">{{ $sel['home_team'] ?? 'N/A' }} vs {{ $sel['away_team'] ?? 'N/A' }}</span>
                                        <span class="text-slate-400 mt-1 block">Mercado: {{ $sel['market_name'] ?? 'Ganador' }} | Sel: <span class="text-indigo-300 font-medium">{{ $sel['selection'] ?? 'N/A' }}</span></span>
                                    </div>
                                    <div class="text-right pl-3 border-l border-slate-800">
                                        <span class="text-[9px] text-slate-500 uppercase block font-bold">Cuota</span>
                                        <span class="text-sm font-black text-emerald-400">x{{ $sel['odds'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Summary info -->
                        <div class="p-3 bg-indigo-500/5 rounded-xl border border-indigo-500/10 text-xs space-y-1">
                            @php
                                $combined = 1.00;
                                foreach($suggestedStepData['selections'] as $sel) {
                                    $combined *= floatval($sel['odds'] ?? 1.50);
                                }
                                $combined = round($combined, 2);
                            @endphp
                            <div class="flex justify-between items-center">
                                <span class="text-slate-400">Cuota Final Producida:</span>
                                <span class="font-black text-indigo-300">x{{ $combined }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-400 font-semibold">Pago Esperado:</span>
                                <span class="font-black text-emerald-400">${{ number_format($suggestedStepStake * $combined, 2) }}</span>
                            </div>
                        </div>

                        <!-- Analysis explanation -->
                        @if(isset($suggestedStepData['analysis']))
                            <p class="text-xs text-slate-400 leading-relaxed italic border-l-2 border-indigo-500/50 pl-3 py-1">
                                "{{ $suggestedStepData['analysis'] }}"
                            </p>
                        @endif

                        <!-- Actions -->
                        <div class="grid grid-cols-2 gap-4 pt-2">
                            <button type="button" wire:click="$set('showSuggestionModal', false)"
                                class="w-full py-3 px-4 rounded-xl border border-slate-800 hover:bg-slate-800/40 text-slate-400 hover:text-white font-bold transition duration-200 text-sm">
                                Rechazar
                            </button>
                            <button type="button" wire:click="acceptStepSuggestion"
                                class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold transition duration-200 text-sm flex items-center justify-center gap-1.5 shadow-lg shadow-emerald-600/10">
                                <i class="fa-solid fa-circle-check"></i> Aceptar y Registrar
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
