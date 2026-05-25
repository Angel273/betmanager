<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Dashboard</h1>
            <p class="text-slate-400 text-sm mt-1">Monitorea tus estadísticas, rendimiento, rachas e historial de apuestas.</p>
        </div>
        <a href="{{ route('bets.register') }}" class="py-3 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition duration-200 flex items-center gap-2 shadow-lg shadow-indigo-600/15">
            <i class="fa-solid fa-plus-circle"></i>
            <span>Nueva Apuesta</span>
        </a>
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

    <!-- Profile Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Stat Card: Net Profit -->
        <div class="glassmorphism p-6 rounded-2xl relative">
            <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Beneficio Neto</span>
                    <span class="text-3xl font-black {{ $stats['netProfit'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ $stats['netProfit'] >= 0 ? '+' : '' }}${{ number_format($stats['netProfit'], 2) }}
                    </span>
                </div>
                <div class="h-10 w-10 rounded-xl {{ $stats['netProfit'] >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' }} flex items-center justify-center text-lg shadow-inner">
                    <i class="fa-solid {{ $stats['netProfit'] >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                </div>
            </div>
            <div class="mt-4 text-xs text-slate-500">
                Inversión Total: <span class="text-slate-300 font-bold">${{ number_format($stats['totalStake'], 2) }}</span>
            </div>
        </div>

        <!-- Stat Card: Win Rate -->
        <div class="glassmorphism p-6 rounded-2xl relative">
            <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Tasa de Acierto</span>
                    <span class="text-3xl font-black text-white">
                        {{ $stats['winRate'] }}%
                    </span>
                </div>
                <div class="h-10 w-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-lg shadow-inner">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
            </div>
            <div class="mt-4 text-xs text-slate-500">
                Verdes: <span class="text-emerald-400 font-bold">{{ $stats['wonCount'] }}</span> | Rojos: <span class="text-red-400 font-bold">{{ $stats['lostCount'] }}</span>
            </div>
        </div>

        <!-- Stat Card: Yield -->
        <div class="glassmorphism p-6 rounded-2xl relative">
            <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Yield</span>
                    <span class="text-3xl font-black {{ $stats['yield'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ $stats['yield'] >= 0 ? '+' : '' }}{{ $stats['yield'] }}%
                    </span>
                </div>
                <div class="h-10 w-10 rounded-xl {{ $stats['yield'] >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' }} flex items-center justify-center text-lg shadow-inner">
                    <i class="fa-solid fa-percent"></i>
                </div>
            </div>
            <div class="mt-4 text-xs text-slate-500">
                Retorno promedio por moneda apostada
            </div>
        </div>

        <!-- Stat Card: Streak -->
        <div class="glassmorphism p-6 rounded-2xl relative">
            <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Racha Actual</span>
                    <span class="text-3xl font-black text-amber-400">
                        {{ $stats['streak'] }}
                    </span>
                </div>
                <div class="h-10 w-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-lg shadow-inner">
                    <i class="fa-solid fa-fire"></i>
                </div>
            </div>
            <div class="mt-4 text-xs text-slate-500">
                Últimas apuestas calificadas
            </div>
        </div>
    </div>

    <!-- Chart & Info Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Chart Column -->
        <div class="lg:col-span-2">
            <div class="glassmorphism p-6 rounded-2xl relative">
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>
                <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-indigo-400"></i>
                    <span>Evolución del Beneficio Acumulado</span>
                </h2>
                
                @if(count($chartData['series']) > 1)
                    <!-- Chart Wrapper -->
                    <div wire:ignore x-data="{
                        initChart() {
                            const data = @js($chartData);
                            const options = {
                                chart: {
                                    type: 'area',
                                    height: 280,
                                    background: 'transparent',
                                    toolbar: { show: false },
                                    foreColor: '#64748b'
                                },
                                colors: ['#6366f1'],
                                fill: {
                                    type: 'gradient',
                                    gradient: {
                                        shadeIntensity: 1,
                                        opacityFrom: 0.35,
                                        opacityTo: 0.05,
                                        stops: [0, 95, 100]
                                    }
                                },
                                stroke: {
                                    curve: 'smooth',
                                    width: 3
                                },
                                series: [{
                                    name: 'Beneficio Neto ($)',
                                    data: data.series
                                }],
                                xaxis: {
                                    categories: data.categories,
                                    axisBorder: { show: false },
                                    axisTicks: { show: false }
                                },
                                yaxis: {
                                    tickAmount: 5,
                                    labels: {
                                        formatter: function(val) {
                                            return '$' + val.toFixed(2);
                                        }
                                    }
                                },
                                grid: {
                                    borderColor: '#1e293b',
                                    strokeDashArray: 5,
                                    xaxis: { lines: { show: false } }
                                },
                                dataLabels: { enabled: false },
                                tooltip: {
                                    theme: 'dark',
                                    x: { show: true },
                                    y: {
                                        formatter: function(val) {
                                            return '$' + val.toFixed(2);
                                        }
                                    }
                                }
                            };
                            const chart = new ApexCharts(this.$refs.chart, options);
                            chart.render();
                        }
                    }" x-init="initChart()">
                        <div x-ref="chart"></div>
                    </div>
                @else
                    <div class="py-24 text-center text-slate-600">
                        <i class="fa-solid fa-chart-area text-5xl mb-4 text-slate-800"></i>
                        <span class="block">Se requiere calificar apuestas para graficar tu rendimiento.</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Info Widget Column (E.g. Profile details and quick advice) -->
        <div class="lg:col-span-1">
            <div class="glassmorphism p-6 rounded-2xl relative h-full flex flex-col justify-between">
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>
                
                <div>
                    <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-lightbulb text-indigo-400"></i>
                        <span>Consejos de Rendimiento</span>
                    </h2>
                    
                    <div class="space-y-4 text-sm text-slate-300">
                        <div class="p-4 rounded-xl bg-indigo-500/5 border border-indigo-500/10 flex gap-3 items-start">
                            <i class="fa-solid fa-circle-info text-indigo-400 mt-0.5 shrink-0"></i>
                            <p class="text-xs">
                                <strong>Controla el ROI:</strong> Apuesta con cuotas de valor entre 1.50 y 2.20 para estabilizar tu tasa de acierto y optimizar tu yield.
                            </p>
                        </div>
                        <div class="p-4 rounded-xl bg-amber-500/5 border border-amber-500/10 flex gap-3 items-start">
                            <i class="fa-solid fa-robot text-amber-400 mt-0.5 shrink-0"></i>
                            <p class="text-xs">
                                <strong>Análisis con IA:</strong> ¿Tienes dudas sobre un Parlay? Utiliza la opción de **Analizar con IA** para buscar probabilidades en tiempo real.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-800/60 text-xs text-slate-500 text-center flex items-center justify-center gap-2">
                    <i class="fa-solid fa-shield-halved text-indigo-400/50"></i>
                    <span>Tus datos y registros están guardados localmente.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Collapsible Bet Finder Section -->
    <div x-data="{ open: false }" class="glassmorphism rounded-2xl relative mb-8 overflow-hidden">
        <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>
        
        <!-- Toggle Header -->
        <button type="button" x-on:click="open = !open" 
            class="w-full p-6 flex justify-between items-center bg-slate-900/40 hover:bg-slate-900/60 transition duration-150 text-left">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Buscador de Apuestas IA (Búsqueda Rápida)</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Encuentra recomendaciones de apuestas usando búsqueda web en tiempo real sin salir de tu panel.</p>
                </div>
            </div>
            <div class="text-slate-400">
                <i class="fa-solid" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </div>
        </button>

        <!-- Collapsible Content -->
        <div x-show="open" x-transition class="p-6 border-t border-slate-800/60">
            @livewire('bets.bet-finder')
        </div>
    </div>

    <!-- Bets Log Table -->
    <div class="glassmorphism p-6 rounded-2xl relative">
        <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

        <!-- Filters and Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-receipt text-indigo-400"></i>
                <span>Historial de Apuestas</span>
            </h2>

            <div class="flex flex-wrap gap-3 w-full md:w-auto">
                <!-- Status Filter -->
                <select wire:model.live="filterStatus"
                    class="bg-slate-900/80 border border-slate-800 rounded-xl py-2 px-4 text-xs text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                    <option value="">Todos los Estados</option>
                    <option value="pending">Pendientes</option>
                    <option value="won">Ganadas</option>
                    <option value="lost">Perdidas</option>
                    <option value="voided">Anuladas (Void)</option>
                    <option value="half_won">Mitad Ganada</option>
                    <option value="half_lost">Mitad Perdida</option>
                </select>

                <!-- Sport Filter -->
                <select wire:model.live="filterSport"
                    class="bg-slate-900/80 border border-slate-800 rounded-xl py-2 px-4 text-xs text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                    <option value="">Todos los Deportes</option>
                    @foreach($sportsList as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Bets List -->
        <div class="space-y-4">
            @forelse($bets as $bet)
                <div wire:key="bet-card-{{ $bet->id }}" class="p-5 rounded-2xl bg-slate-900/40 border border-slate-800/80 hover:border-slate-800 transition duration-150 relative">
                    
                    <!-- Top Info row -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-3 border-b border-slate-800/40 mb-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs px-2.5 py-1 rounded bg-slate-800 font-bold uppercase tracking-wider text-slate-300">
                                {{ $bet->type === 'single' ? 'Individual' : 'Parlay' }}
                            </span>
                            <span class="text-xs text-slate-500">{{ $bet->created_at->format('d M Y, h:i A') }}</span>
                            
                            <!-- Bet Path badge if linked -->
                            @if($bet->bet_path_id)
                                <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 font-semibold">
                                    <i class="fa-solid fa-route mr-1"></i> Bet Path Paso {{ $bet->bet_path_step }}
                                </span>
                            @endif
                        </div>
                        
                        <!-- Status and Payout info -->
                        <div class="flex items-center gap-4">
                            @if($bet->status === 'pending')
                                <span class="px-2.5 py-0.5 text-xs font-bold rounded bg-amber-500/15 text-amber-300 border border-amber-500/35 uppercase">Pendiente</span>
                            @elseif($bet->status === 'won')
                                <span class="px-2.5 py-0.5 text-xs font-bold rounded bg-emerald-500/15 text-emerald-300 border border-emerald-500/35 uppercase">Ganada</span>
                            @elseif($bet->status === 'lost')
                                <span class="px-2.5 py-0.5 text-xs font-bold rounded bg-red-500/15 text-red-300 border border-red-500/35 uppercase">Perdida</span>
                            @elseif($bet->status === 'voided')
                                <span class="px-2.5 py-0.5 text-xs font-bold rounded bg-slate-800 text-slate-400 border border-slate-700 uppercase">Anulada</span>
                            @elseif($bet->status === 'half_won')
                                <span class="px-2.5 py-0.5 text-xs font-bold rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase">Mitad Ganada</span>
                            @elseif($bet->status === 'half_lost')
                                <span class="px-2.5 py-0.5 text-xs font-bold rounded bg-red-500/10 text-red-400 border border-red-500/20 uppercase">Mitad Perdida</span>
                            @endif

                            <div class="text-right">
                                <span class="text-[10px] text-slate-500 block">Retorno</span>
                                <span class="text-sm font-black {{ $bet->profit > 0 ? 'text-emerald-400' : ($bet->profit < 0 ? 'text-red-400' : 'text-slate-300') }}">
                                    ${{ number_format($bet->payout, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Selections -->
                    <div class="space-y-3 mb-4">
                        @foreach($bet->selections as $sel)
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 text-sm p-3 rounded-xl bg-slate-900/60 border border-slate-800/40">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-{{ $sel->sport->icon ?? 'dice' }} text-indigo-400 text-xs shrink-0"></i>
                                        <span class="font-bold text-white">{{ $sel->selection }}</span>
                                        <span class="text-xs text-slate-400">({{ $sel->market_name }})</span>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        {{ $sel->sport->name }} &bull; {{ $sel->league->name }} 
                                        @if($sel->teamHome || $sel->teamAway)
                                            &bull; {{ $sel->teamHome?->name ?? 'A' }} vs {{ $sel->teamAway?->name ?? 'B' }}
                                        @endif
                                        @if($sel->player)
                                            &bull; Jugador: {{ $sel->player->name }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right text-xs font-bold text-indigo-300 shrink-0">
                                    Cuota: x{{ $sel->odds }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Lower action panel of each card -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pt-3 border-t border-slate-800/40">
                        <div class="text-xs text-slate-400">
                            Apostado: <span class="text-white font-bold">${{ number_format($bet->stake, 2) }}</span> &bull; 
                            Cuota Total: <span class="text-indigo-300 font-bold">x{{ $bet->odds }}</span> &bull; 
                            Pago Esperado: <span class="text-emerald-400 font-bold">${{ number_format($bet->stake * $bet->odds, 2) }}</span>
                            @if(!empty($bet->notes))
                                <div class="text-[10px] text-slate-500 mt-1 italic">Nota: {{ $bet->notes }}</div>
                            @endif
                        </div>

                        <!-- Action controls -->
                        <div class="flex items-center gap-2 self-stretch sm:self-auto justify-end">
                            
                            <!-- AI Analysis Badge or button -->
                            @if($analyzingBetId == $bet->id)
                                <div wire:key="ai-loading-{{ $bet->id }}" class="px-2.5 py-1.5 rounded-xl bg-slate-800 border border-slate-700 text-[10px] flex items-center gap-1.5 text-indigo-400 font-bold uppercase tracking-wider shrink-0 cursor-default">
                                    <i class="fa-solid fa-circle-notch animate-spin"></i>
                                    <span>Analizando...</span>
                                </div>
                            @elseif($bet->analyzed_at)
                                <button wire:key="ai-badge-{{ $bet->id }}" wire:click="openAiAnalysisModal({{ $bet->id }})"
                                    class="px-2.5 py-1.5 rounded-xl bg-slate-800 border border-slate-700 text-[10px] flex items-center gap-1.5 hover:bg-slate-700 transition duration-150"
                                    title="Ver Análisis de IA">
                                    <i class="fa-solid fa-robot text-amber-400"></i>
                                    <span class="font-bold text-slate-300">Riesgo: 
                                        @if(isset($bet->ai_analysis['risk']))
                                            <span class="{{ $bet->ai_analysis['risk'] === 'segura' ? 'text-emerald-400' : ($bet->ai_analysis['risk'] === 'moderada' ? 'text-amber-400' : 'text-red-400') }} uppercase">
                                                {{ $bet->ai_analysis['risk'] }}
                                            </span>
                                        @else
                                            Analizado
                                        @endif
                                    </span>
                                </button>
                            @else
                                <!-- Button to trigger AI analysis -->
                                <button wire:key="ai-btn-{{ $bet->id }}"
                                    wire:click="analyzeBet({{ $bet->id }})"
                                    wire:loading.attr="disabled"
                                    class="py-1.5 px-3 rounded-xl bg-slate-800 border border-slate-700 hover:border-indigo-500/30 text-slate-300 hover:text-white text-xs font-bold transition duration-200 flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fa-solid fa-robot text-amber-400"></i>
                                    <span>Analizar Apuesta</span>
                                </button>
                            @endif

                            @if($bet->status === 'pending')
                                <!-- Settle Quick Buttons -->
                                <button wire:click="settleBet({{ $bet->id }}, 'won')"
                                    class="py-1.5 px-3 rounded-xl bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white border border-emerald-500/20 text-xs font-bold transition duration-150">
                                    Ganó
                                </button>
                                <button wire:click="settleBet({{ $bet->id }}, 'lost')"
                                    class="py-1.5 px-3 rounded-xl bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white border border-red-500/20 text-xs font-bold transition duration-150">
                                    Perdió
                                </button>
                                <button wire:click="openCustomSettleModal({{ $bet->id }})"
                                    class="py-1.5 px-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white text-xs transition duration-150"
                                    title="Pago personalizado / Cashout">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                            @else
                                <!-- Delete Button -->
                                <button wire:click="deleteBet({{ $bet->id }})" 
                                    wire:confirm="¿Estás seguro de que deseas eliminar esta apuesta?"
                                    class="p-2 text-slate-600 hover:text-red-400 rounded-lg hover:bg-red-500/10 transition duration-200">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            @endif

                        </div>
                    </div>

                </div>
            @empty
                <div class="py-12 text-center text-slate-500">
                    <div class="text-3xl mb-3 text-slate-700">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <span>No se encontraron apuestas con los filtros seleccionados.</span>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6 pt-4 border-t border-slate-800/40">
            {{ $bets->links() }}
        </div>
    </div>

    <!-- Settle Custom Payout Modal -->
    @if($showSettleModal)
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="w-full max-w-sm rounded-2xl glassmorphism p-6 relative">
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

                <div class="flex justify-between items-center pb-3 border-b border-slate-800 mb-5">
                    <h3 class="text-lg font-bold text-white">Cobrar Apuesta (Cashout / Personalizado)</h3>
                    <button wire:click="$set('showSettleModal', false)" class="text-slate-500 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form wire:submit.prevent="settleCustomWon" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Monto de Pago Retornado ($)</label>
                        <input type="number" step="0.01" wire:model="customPayout"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl py-3 px-4 text-white text-lg font-bold focus:outline-none focus:border-indigo-500 transition duration-200">
                        @error('customPayout') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        <span class="block text-[10px] text-slate-500 mt-1">Ingresa el monto neto devuelto (para Cashout anticipados, apuestas anuladas parciales, etc.).</span>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition duration-200">
                        Confirmar Cobro
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- Global AI Analysis Loader Overlay -->
    <div wire:loading wire:target="analyzeBet" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex flex-col items-center justify-center p-4">
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
                <span>Gemini 2.0 Flash está analizando</span>
            </div>
        </div>
    </div>

    <!-- AI Analysis Detail Modal -->
    @if($showAiModal && $selectedBetForAnalysis)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
            wire:click="$set('showAiModal', false)">
            
            <div class="w-full max-w-lg p-6 rounded-2xl bg-slate-900 border border-indigo-500/30 shadow-2xl text-left text-xs text-slate-200 relative flex flex-col max-h-[85vh] cursor-default" 
                wire:click.prevent.stop>
                
                <!-- Close Button 'X' -->
                <button wire:click="$set('showAiModal', false)" class="absolute top-4 right-4 text-slate-400 hover:text-white transition p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>

                <div class="flex justify-between items-center pb-3 border-b border-slate-800 mb-4 pr-8 shrink-0">
                    <span class="font-bold text-white flex items-center gap-1.5 text-sm">
                        <i class="fa-solid fa-robot text-amber-400"></i> Análisis IA (Gemini)
                    </span>
                    <span class="text-[10px] text-slate-500">Analizado: {{ $selectedBetForAnalysis->analyzed_at->format('d M, h:i A') }}</span>
                </div>

                <div class="space-y-4 overflow-y-auto pr-1 flex-1">
                    <div class="flex gap-6 items-center">
                        <div>
                            <span class="text-[9px] uppercase tracking-wider text-slate-500 block">Evaluación de Riesgo</span>
                            <span class="text-xs font-black uppercase {{ ($selectedBetForAnalysis->ai_analysis['risk'] ?? '') === 'segura' ? 'text-emerald-400' : (($selectedBetForAnalysis->ai_analysis['risk'] ?? '') === 'moderada' ? 'text-amber-400' : 'text-red-400') }}">
                                {{ $selectedBetForAnalysis->ai_analysis['risk'] ?? 'Moderado' }}
                            </span>
                        </div>
                        @if(isset($selectedBetForAnalysis->ai_analysis['score']))
                            <div class="border-l border-slate-800/80 pl-6">
                                <span class="text-[9px] uppercase tracking-wider text-slate-500 block">Nota Global</span>
                                <span class="text-xs font-black text-white bg-indigo-500/20 px-2 py-0.5 rounded border border-indigo-500/30">
                                    {{ $selectedBetForAnalysis->ai_analysis['score'] }}/100
                                </span>
                            </div>
                        @endif
                    </div>
                    
                    <div>
                        <span class="text-[9px] uppercase tracking-wider text-slate-500 block mb-1">Justificación / Forma</span>
                        <p class="text-[11px] leading-relaxed text-slate-300">{{ $selectedBetForAnalysis->ai_analysis['analysis'] ?? 'Análisis no estructurado.' }}</p>
                    </div>

                    @if(isset($selectedBetForAnalysis->ai_analysis['selection_scores']) && is_array($selectedBetForAnalysis->ai_analysis['selection_scores']) && count($selectedBetForAnalysis->ai_analysis['selection_scores']) > 0)
                        <div class="pt-3 border-t border-slate-800/60">
                            <span class="text-[9px] uppercase tracking-wider text-slate-500 block mb-1.5 font-bold">Notas de Selecciones</span>
                            <div class="space-y-1.5">
                                @foreach($selectedBetForAnalysis->ai_analysis['selection_scores'] as $selScore)
                                    @php
                                        $selIndex = ($selScore['selection_index'] ?? 1) - 1;
                                        $selModel = $selectedBetForAnalysis->selections[$selIndex] ?? null;
                                    @endphp
                                    @if($selModel)
                                        <div class="flex justify-between items-center text-[10px] bg-slate-950/40 px-2.5 py-2 rounded-lg border border-slate-800/50 gap-2">
                                            <div class="truncate flex-1">
                                                <span class="font-bold text-slate-200 block truncate">{{ $selModel->teamHome?->name ?? 'N/A' }} vs {{ $selModel->teamAway?->name ?? 'N/A' }}</span>
                                                <span class="text-slate-500 text-[8px] block truncate">{{ $selModel->market_name }}: <span class="text-slate-400 font-medium">{{ $selModel->selection }}</span></span>
                                            </div>
                                            <span class="font-extrabold text-[9px] shrink-0 px-1.5 py-0.5 rounded {{ ($selScore['score'] ?? 0) >= 75 ? 'text-emerald-400 bg-emerald-500/10 border border-emerald-500/20' : (($selScore['score'] ?? 0) >= 50 ? 'text-amber-400 bg-amber-500/10 border border-amber-500/20' : 'text-red-400 bg-red-500/10 border border-red-500/20') }}">
                                                {{ $selScore['score'] ?? 0 }}/100
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(isset($selectedBetForAnalysis->ai_analysis['stats']) && is_array($selectedBetForAnalysis->ai_analysis['stats']))
                        <div class="pt-3 border-t border-slate-800/60">
                            <span class="text-[9px] uppercase tracking-wider text-slate-500 block mb-1.5 font-bold">Estadísticas de Mercado (Últimos 5 juegos)</span>
                            @if(!empty($selectedBetForAnalysis->ai_analysis['stats']['description']))
                                <p class="text-[10px] text-slate-400 leading-snug mb-2 italic">{{ $selectedBetForAnalysis->ai_analysis['stats']['description'] }}</p>
                            @endif
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div class="p-2.5 rounded bg-slate-950/40 border border-slate-800/50">
                                    <span class="text-[8px] font-bold text-indigo-400 uppercase tracking-wider block mb-0.5">Local</span>
                                    <span class="text-slate-300 leading-tight block">{{ $selectedBetForAnalysis->ai_analysis['stats']['home_stats'] ?? 'Sin datos' }}</span>
                                </div>
                                <div class="p-2.5 rounded bg-slate-950/40 border border-slate-800/50">
                                    <span class="text-[8px] font-bold text-indigo-400 uppercase tracking-wider block mb-0.5">Visitante</span>
                                    <span class="text-slate-300 leading-tight block">{{ $selectedBetForAnalysis->ai_analysis['stats']['away_stats'] ?? 'Sin datos' }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(isset($selectedBetForAnalysis->ai_analysis['h2h']) && is_array($selectedBetForAnalysis->ai_analysis['h2h']) && count($selectedBetForAnalysis->ai_analysis['h2h']) > 0)
                        <div class="pt-3 border-t border-slate-800/60 font-bold">
                            <span class="text-[9px] uppercase tracking-wider text-slate-500 block mb-1.5">Enfrentamientos Directos (H2H)</span>
                            <div class="space-y-1.5">
                                @foreach($selectedBetForAnalysis->ai_analysis['h2h'] as $match)
                                    <div class="p-2.5 rounded-lg bg-slate-950/40 border border-slate-800/50 text-[10px]">
                                        @if(!empty($match['match']))
                                            <div class="text-[8px] font-bold text-indigo-400 uppercase tracking-wider mb-1 opacity-80 truncate">{{ $match['match'] }}</div>
                                        @endif
                                        <div class="flex justify-between items-center gap-2 font-medium mb-0.5">
                                            <span class="text-slate-300 truncate w-[42%]" title="{{ $match['home_team'] ?? '' }}">{{ $match['home_team'] ?? '' }}</span>
                                            <span class="font-extrabold text-indigo-400 bg-indigo-500/10 px-1.5 py-0.5 rounded text-[9px] shrink-0 min-w-[28px] text-center border border-indigo-500/20">
                                                {{ $match['score'] ?? 'vs' }}
                                            </span>
                                            <span class="text-slate-300 truncate w-[42%] text-right" title="{{ $match['away_team'] ?? '' }}">{{ $match['away_team'] ?? '' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-[8px] text-slate-500">
                                            <span>{{ $match['info'] ?? '' }}</span>
                                            <span>{{ $match['date'] ?? '' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
