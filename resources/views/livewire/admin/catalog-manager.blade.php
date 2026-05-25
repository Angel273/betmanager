<div>
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Catálogos Deportivos</h1>
        <p class="text-slate-400 text-sm mt-1">Configura las regiones, países, deportes, ligas, equipos y jugadores disponibles para registrar apuestas.</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex flex-wrap gap-2 mb-8 border-b border-slate-800 pb-4">
        @foreach(['regions' => 'Regiones', 'countries' => 'Países', 'sports' => 'Deportes', 'leagues' => 'Ligas', 'teams' => 'Equipos', 'players' => 'Jugadores', 'blacklist' => 'Lista Negra'] as $tab => $label)
            <button wire:click="changeTab('{{ $tab }}')"
                class="px-5 py-2.5 rounded-xl text-sm font-semibold transition duration-200 {{ $activeTab === $tab ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/15' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <!-- Main Content Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- FORM COLUMN -->
        <div class="lg:col-span-1">
            <div class="glassmorphism p-6 rounded-2xl relative">
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

                <h2 class="text-lg font-bold text-white mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-indigo-400"></i>
                    <span>{{ $isEditMode ? 'Editar' : 'Agregar' }} {{ $activeTab === 'countries' ? 'País' : ($activeTab === 'regions' ? 'Región' : ($activeTab === 'sports' ? 'Deporte' : ($activeTab === 'leagues' ? 'Liga' : ($activeTab === 'teams' ? 'Equipo' : ($activeTab === 'players' ? 'Jugador' : 'Liga en Lista Negra'))))) }}</span>
                </h2>

                <!-- Success Alert -->
                @if (session()->has('success'))
                    <div class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs flex gap-2 items-center">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <!-- Dynamic Forms based on Active Tab -->
                <form wire:submit.prevent="save{{ ucfirst($activeTab === 'countries' ? 'Country' : ($activeTab === 'regions' ? 'Region' : ($activeTab === 'sports' ? 'Sport' : ($activeTab === 'leagues' ? 'League' : ($activeTab === 'teams' ? 'Team' : ($activeTab === 'players' ? 'Player' : 'BlacklistedLeague')))))) }}" class="space-y-4">
                    
                    @if($activeTab === 'regions')
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nombre de la Región</label>
                            <input type="text" wire:model="region_name" placeholder="Ej. Europa, América"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                            @error('region_name') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    @if($activeTab === 'countries')
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nombre del País</label>
                            <input type="text" wire:model="country_name" placeholder="Ej. España, México"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200 mb-4">
                            @error('country_name') <span class="text-red-400 text-xs mt-1 block mb-3">{{ $message }}</span> @enderror

                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Región</label>
                            <select wire:model="country_region_id"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                                <option value="">Selecciona una región...</option>
                                @foreach($regionsList as $reg)
                                    <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                                @endforeach
                            </select>
                            @error('country_region_id') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    @if($activeTab === 'sports')
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nombre del Deporte</label>
                            <input type="text" wire:model="sport_name" placeholder="Ej. Fútbol, Tenis"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200 mb-4">
                            @error('sport_name') <span class="text-red-400 text-xs mt-1 block mb-3">{{ $message }}</span> @enderror

                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Clase Icono FontAwesome (Opcional)</label>
                            <input type="text" wire:model="sport_icon" placeholder="Ej. futbol, basketball"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                            @error('sport_icon') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    @if($activeTab === 'leagues')
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nombre de la Liga / Torneo</label>
                            <input type="text" wire:model="league_name" placeholder="Ej. LaLiga, NBA, Champions League"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200 mb-4">
                            @error('league_name') <span class="text-red-400 text-xs mt-1 block mb-3">{{ $message }}</span> @enderror

                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Deporte</label>
                            <select wire:model="league_sport_id"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200 mb-4">
                                <option value="">Selecciona un deporte...</option>
                                @foreach($sportsList as $sport)
                                    <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                                @endforeach
                            </select>
                            @error('league_sport_id') <span class="text-red-400 text-xs mt-1 block mb-3">{{ $message }}</span> @enderror

                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">País (Opcional - Ej. Deja en blanco si es internacional)</label>
                            <select wire:model="league_country_id"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                                <option value="">Selecciona un país...</option>
                                @foreach($countriesList as $cnt)
                                    <option value="{{ $cnt->id }}">{{ $cnt->name }}</option>
                                @endforeach
                            </select>
                            @error('league_country_id') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    @if($activeTab === 'teams')
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                                {{ $isEditMode ? 'Nombre del Equipo' : 'Nombre del Equipo / Lista de Equipos' }}
                            </label>
                            
                            @if($isEditMode)
                                <input type="text" wire:model="team_name" placeholder="Ej. Real Madrid"
                                    class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200 mb-4">
                            @else
                                <textarea wire:model="team_name" rows="4" placeholder="Ej. Real Madrid, Barcelona, Atletico de Madrid (separados por comas o saltos de línea)"
                                    class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200 mb-1"></textarea>
                                <span class="text-slate-500 text-[10px] block mb-4">Puedes agregar múltiples equipos ingresando los nombres separados por comas o en líneas distintas.</span>
                            @endif
                            @error('team_name') <span class="text-red-400 text-xs mt-1 block mb-3">{{ $message }}</span> @enderror

                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Liga a la que pertenece</label>
                            <select wire:model="team_league_id"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                                <option value="">Selecciona una liga...</option>
                                @foreach($leaguesList as $league)
                                    <option value="{{ $league->id }}">{{ $league->name }} ({{ $league->sport->name }})</option>
                                @endforeach
                            </select>
                            @error('team_league_id') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    @if($activeTab === 'players')
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nombre del Jugador</label>
                            <input type="text" wire:model="player_name" placeholder="Ej. Lionel Messi, LeBron James"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200 mb-4">
                            @error('player_name') <span class="text-red-400 text-xs mt-1 block mb-3">{{ $message }}</span> @enderror

                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Equipo al que pertenece (Opcional)</label>
                            <select wire:model="player_team_id"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-300 focus:outline-none focus:border-indigo-500 transition duration-200">
                                <option value="">Selecciona un equipo...</option>
                                @foreach($teamsList as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }} ({{ $team->league->name }})</option>
                                @endforeach
                            </select>
                            @error('player_team_id') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    @if($activeTab === 'blacklist')
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nombre de la Liga a Excluir</label>
                            <input type="text" wire:model="blacklist_league_name" placeholder="Ej. U21 Premier League, Division 2"
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 px-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                            @error('blacklist_league_name') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/10">
                            <i class="fa-solid fa-save text-xs"></i>
                            <span>{{ $isEditMode ? 'Guardar Cambios' : 'Registrar' }}</span>
                        </button>
                        @if($isEditMode)
                            <button type="button" wire:click="resetForms" class="py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold transition duration-200">
                                Cancelar
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- LIST COLUMN -->
        <div class="lg:col-span-2">
            <div class="glassmorphism p-6 rounded-2xl relative flex flex-col h-full">
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

                <!-- Search and Title -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-list-ul text-indigo-400"></i>
                        <span>Listado de {{ ucfirst($activeTab) }}</span>
                    </h2>
                    
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <input type="text" wire:model.live="search" placeholder="Buscar..." 
                            class="w-full bg-slate-900/60 border border-slate-800 rounded-xl py-2 pl-9 pr-4 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                    </div>
                </div>

                <!-- Lists Tables -->
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                                @if($activeTab === 'regions')
                                    <th class="py-3 px-4">Región</th>
                                @elseif($activeTab === 'countries')
                                    <th class="py-3 px-4">País</th>
                                    <th class="py-3 px-4">Región</th>
                                @elseif($activeTab === 'sports')
                                    <th class="py-3 px-4">Deporte</th>
                                    <th class="py-3 px-4 text-center">Icono</th>
                                @elseif($activeTab === 'leagues')
                                    <th class="py-3 px-4">Liga</th>
                                    <th class="py-3 px-4">Deporte</th>
                                    <th class="py-3 px-4">País / Ámbito</th>
                                @elseif($activeTab === 'teams')
                                    <th class="py-3 px-4">Equipo</th>
                                    <th class="py-3 px-4">Liga</th>
                                    <th class="py-3 px-4">Deporte</th>
                                @elseif($activeTab === 'players')
                                    <th class="py-3 px-4">Jugador</th>
                                    <th class="py-3 px-4">Equipo</th>
                                    <th class="py-3 px-4">Liga</th>
                                @elseif($activeTab === 'blacklist')
                                    <th class="py-3 px-4">Liga en Lista Negra</th>
                                @endif
                                <th class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40 text-sm">
                            @forelse($items as $item)
                                <tr class="hover:bg-slate-900/20 transition duration-150">
                                    
                                    <!-- Regions Tab -->
                                    @if($activeTab === 'regions')
                                        <td class="py-3 px-4 font-semibold text-white">{{ $item->name }}</td>
                                    
                                    <!-- Countries Tab -->
                                    @elseif($activeTab === 'countries')
                                        <td class="py-3 px-4 font-semibold text-white">{{ $item->name }}</td>
                                        <td class="py-3 px-4 text-slate-400">{{ $item->region->name }}</td>
                                    
                                    <!-- Sports Tab -->
                                    @elseif($activeTab === 'sports')
                                        <td class="py-3 px-4 font-semibold text-white">{{ $item->name }}</td>
                                        <td class="py-3 px-4 text-center text-slate-400">
                                            @if($item->icon)
                                                <i class="fa-solid fa-{{ $item->icon }} text-base text-indigo-400"></i>
                                            @else
                                                <span class="text-xs italic text-slate-600">Ninguno</span>
                                            @endif
                                        </td>
                                    
                                    <!-- Leagues Tab -->
                                    @elseif($activeTab === 'leagues')
                                        <td class="py-3 px-4 font-semibold text-white">{{ $item->name }}</td>
                                        <td class="py-3 px-4 text-slate-400">{{ $item->sport->name }}</td>
                                        <td class="py-3 px-4 text-slate-400">
                                            {{ $item->country->name ?? 'Internacional / Copa' }}
                                        </td>
                                    
                                    <!-- Teams Tab -->
                                    @elseif($activeTab === 'teams')
                                        <td class="py-3 px-4 font-semibold text-white">{{ $item->name }}</td>
                                        <td class="py-3 px-4 text-slate-400">{{ $item->league->name }}</td>
                                        <td class="py-3 px-4 text-slate-500">{{ $item->league->sport->name }}</td>
                                    
                                    <!-- Players Tab -->
                                    @elseif($activeTab === 'players')
                                        <td class="py-3 px-4 font-semibold text-white">{{ $item->name }}</td>
                                        <td class="py-3 px-4 text-slate-400">
                                            {{ $item->team->name ?? 'Agente Libre / Individual' }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-500">
                                            {{ $item->team->league->name ?? '-' }}
                                        </td>
                                    <!-- Blacklisted Leagues Tab -->
                                    @elseif($activeTab === 'blacklist')
                                        <td class="py-3 px-4 font-semibold text-white">{{ $item->name }}</td>
                                    @endif

                                    <!-- Actions Column -->
                                    <td class="py-3 px-4 text-right">
                                        <div class="inline-flex gap-1">
                                            <button wire:click="edit{{ ucfirst($activeTab === 'countries' ? 'Country' : ($activeTab === 'regions' ? 'Region' : ($activeTab === 'sports' ? 'Sport' : ($activeTab === 'leagues' ? 'League' : ($activeTab === 'teams' ? 'Team' : ($activeTab === 'players' ? 'Player' : 'BlacklistedLeague')))))) }}({{ $item->id }})" 
                                                class="p-2 text-slate-400 hover:text-indigo-400 rounded-lg hover:bg-indigo-500/10 transition duration-200"
                                                title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button wire:click="delete{{ ucfirst($activeTab === 'countries' ? 'Country' : ($activeTab === 'regions' ? 'Region' : ($activeTab === 'sports' ? 'Sport' : ($activeTab === 'leagues' ? 'League' : ($activeTab === 'teams' ? 'Team' : ($activeTab === 'players' ? 'Player' : 'BlacklistedLeague')))))) }}({{ $item->id }})"
                                                wire:confirm="¿Estás seguro de que deseas eliminar este registro? (Se eliminarán los registros dependientes)."
                                                class="p-2 text-slate-500 hover:text-red-400 rounded-lg hover:bg-red-500/10 transition duration-200"
                                                title="Eliminar">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    @php $cols = ($activeTab === 'regions' || $activeTab === 'blacklist') ? 2 : ($activeTab === 'countries' || $activeTab === 'sports' ? 3 : 4); @endphp
                                    <td colspan="{{ $cols }}" class="py-12 text-center text-slate-500">
                                        <div class="text-3xl mb-3 text-slate-700">
                                            <i class="fa-solid fa-folder-open"></i>
                                        </div>
                                        <span>No se encontraron {{ $activeTab === 'blacklist' ? 'ligas excluidas' : $activeTab }}.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4 pt-4 border-t border-slate-800/40">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
