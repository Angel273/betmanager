<div>
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Correos Autorizados</h1>
        <p class="text-slate-400 text-sm mt-1">Control de acceso privado. Solo los correos en esta lista podrán registrarse o iniciar sesión con Google.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Add Email Form Card -->
        <div class="lg:col-span-1">
            <div class="glassmorphism p-6 rounded-2xl relative">
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>
                
                <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle text-indigo-400"></i>
                    <span>Autorizar Nuevo Correo</span>
                </h2>

                <form wire:submit.prevent="addEmail" class="space-y-4">
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Correo de Google</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <input type="email" id="email" wire:model.defer="email" placeholder="usuario@gmail.com" 
                                class="w-full bg-slate-900/80 border border-slate-800 rounded-xl py-3 pl-10 pr-4 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                        </div>
                        @error('email') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition duration-200 flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/10">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        <span>Autorizar Acceso</span>
                    </button>
                </form>

                @if (session()->has('success'))
                    <div class="mt-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs flex gap-2 items-center">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mt-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-xs flex gap-2 items-center">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Emails Table Card -->
        <div class="lg:col-span-2">
            <div class="glassmorphism p-6 rounded-2xl relative flex flex-col h-full">
                <div class="absolute inset-0 rounded-2xl border border-indigo-500/10 pointer-events-none"></div>

                <!-- Search and Title -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-indigo-400"></i>
                        <span>Lista de Accesos Autorizados</span>
                    </h2>
                    
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <input type="text" wire:model.live="search" placeholder="Buscar correo..." 
                            class="w-full bg-slate-900/60 border border-slate-800 rounded-xl py-2 pl-9 pr-4 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition duration-200">
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                                <th class="py-3 px-4">Correo Autorizado</th>
                                <th class="py-3 px-4">Agregado Por</th>
                                <th class="py-3 px-4">Fecha Registro</th>
                                <th class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40 text-sm">
                            @forelse($emails as $item)
                                <tr class="hover:bg-slate-900/20 transition duration-150">
                                    <td class="py-3.5 px-4 font-medium text-white flex items-center gap-2.5">
                                        <div class="h-8 w-8 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-300">
                                            <i class="fa-solid fa-envelope text-xs"></i>
                                        </div>
                                        <span>{{ $item->email }}</span>
                                        @if(auth()->user()->email === $item->email)
                                            <span class="px-2 py-0.5 text-[9px] font-bold rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Tú</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-400">
                                        {{ $item->created_by ?? 'Sistema' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-400">
                                        {{ $item->created_at->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        @if($item->email !== auth()->user()->email)
                                            <button wire:click="removeEmail({{ $item->id }})" 
                                                wire:confirm="¿Estás seguro de que deseas revocar el acceso a este correo?"
                                                class="p-2 text-slate-500 hover:text-red-400 rounded-lg hover:bg-red-500/10 transition duration-200"
                                                title="Revocar acceso">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        @else
                                            <span class="text-xs text-slate-600 italic px-2">Protegido</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-slate-500">
                                        <div class="text-3xl mb-3 text-slate-700">
                                            <i class="fa-solid fa-folder-open"></i>
                                        </div>
                                        <span>No se encontraron correos autorizados.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4 pt-4 border-t border-slate-800/40">
                    {{ $emails->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
