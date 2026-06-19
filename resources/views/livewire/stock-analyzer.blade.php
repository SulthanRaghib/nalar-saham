<div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-slate-100"
     x-data="{ toasts: [] }"
     @toast.window="
        const t = { id: Date.now(), message: $event.detail.message, type: $event.detail.type || 'info' };
        toasts.push(t);
        setTimeout(() => toasts = toasts.filter(x => x.id !== t.id), 4500);
     ">

    {{-- Toast Notifications --}}
    <div class="fixed top-6 right-6 z-50 flex flex-col gap-3 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="true"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 translate-x-8"
                 class="pointer-events-auto max-w-sm rounded-2xl border px-5 py-4 shadow-2xl backdrop-blur-xl"
                 :class="{
                     'border-emerald-400/30 bg-emerald-950/90 text-emerald-200': toast.type === 'success',
                     'border-rose-400/30 bg-rose-950/90 text-rose-200': toast.type === 'error',
                     'border-amber-400/30 bg-amber-950/90 text-amber-200': toast.type === 'warning',
                     'border-blue-400/30 bg-blue-950/90 text-blue-200': toast.type === 'info',
                 }">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <template x-if="toast.type === 'success'">
                            <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </template>
                        <template x-if="toast.type === 'error'">
                            <svg class="w-5 h-5 text-rose-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        </template>
                        <template x-if="toast.type === 'warning'">
                            <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        </template>
                        <template x-if="toast.type === 'info'">
                            <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        </template>
                    </div>
                    <p class="text-sm font-medium" x-text="toast.message"></p>
                </div>
            </div>
        </template>
    </div>

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">

        {{-- ============================================================ --}}
        {{-- SECTION 1: HEADER + INPUT FORM + HISTORY SIDEBAR              --}}
        {{-- ============================================================ --}}
        <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-6 shadow-2xl shadow-slate-950/40 backdrop-blur-xl sm:p-8">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(34,197,94,0.16),transparent_32%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.12),transparent_28%),linear-gradient(135deg,rgba(15,23,42,0.9),rgba(2,6,23,0.95))]"></div>

            <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">

                {{-- LEFT: Input Form --}}
                <div class="space-y-6">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/25 bg-emerald-400/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Nalar Saham
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-400/10 px-3 py-1 text-xs font-medium text-blue-300">
                                Metode Benjamin Graham
                            </span>
                        </div>
                        <h1 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                            Analisis Fundamental Saham
                        </h1>
                        <p class="max-w-2xl text-sm leading-6 text-slate-400">
                            Masukkan kode saham Indonesia (IDX) untuk analisis fundamental otomatis.
                            Data diambil langsung dari Bursa Efek Indonesia (IDX).
                        </p>
                    </div>

                    <form wire:submit.prevent="analyze" class="space-y-5 rounded-3xl border border-white/10 bg-gradient-to-br from-slate-900/80 to-slate-950/60 p-5 shadow-lg shadow-slate-950/20 sm:p-6 backdrop-blur-sm">

                        {{-- Ticker Input + Analyze Button --}}
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <label for="ticker" class="mb-2.5 flex items-center gap-2 text-sm font-semibold text-slate-200">
                                    <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/><path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/></svg>
                                    Kode Saham
                                </label>
                                <div class="relative">
                                    <input id="ticker" type="text" wire:model="ticker"
                                        placeholder="Contoh: BBCA, TLKM, BBRI, ASII"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3.5 pr-12 text-base font-medium text-white uppercase placeholder:text-slate-500 placeholder:normal-case shadow-inner shadow-black/10 outline-none transition focus:border-emerald-400/60 focus:ring-4 focus:ring-emerald-400/15 hover:border-white/20" />
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2">
                                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                </div>
                                <p class="mt-1.5 text-xs text-slate-500">Ketik kode saja, tanpa .JK — sistem akan otomatis mendeteksi</p>
                                @error('ticker')
                                    <p class="mt-2 flex items-center gap-1.5 text-sm text-rose-300">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <button type="submit"
                                class="group inline-flex flex-shrink-0 items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-emerald-400 to-emerald-500 px-6 py-3.5 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/25 transition-all hover:shadow-emerald-500/40 hover:scale-[1.02] focus:outline-none focus:ring-4 focus:ring-emerald-400/30 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:scale-100 whitespace-nowrap">
                                <svg wire:loading wire:target="analyze" class="h-5 w-5 animate-spin text-slate-950" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg wire:loading.remove wire:target="analyze" class="w-5 h-5 transition group-hover:rotate-12" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                                <span wire:loading.remove wire:target="analyze">Analisis</span>
                                <span wire:loading wire:target="analyze">Memproses...</span>
                            </button>
                        </div>

                        {{-- Mode Toggle --}}
                        <div class="flex flex-wrap items-center gap-3 border-t border-white/10 pt-5">
                            <button type="button" wire:click="toggleMode"
                                class="group inline-flex items-center gap-2.5 rounded-full px-5 py-2.5 text-xs font-bold uppercase tracking-[0.18em] transition-all hover:scale-105 {{ $isManualMode ? 'bg-gradient-to-r from-amber-400/20 to-orange-400/15 text-amber-200 ring-2 ring-amber-300/40' : 'bg-gradient-to-r from-sky-400/20 to-blue-400/15 text-sky-200 ring-2 ring-sky-300/40' }}">
                                <span class="relative flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $isManualMode ? 'bg-amber-300' : 'bg-sky-300' }} opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 {{ $isManualMode ? 'bg-amber-300' : 'bg-sky-300' }}"></span>
                                </span>
                                {{ $isManualMode ? 'Input Manual' : 'Auto (IDX API)' }}
                            </button>
                            <p class="text-sm {{ $isManualMode ? 'text-amber-300/80' : 'text-sky-300/80' }}">
                                {{ $isManualMode ? 'Masukkan data fundamental dari laporan keuangan.' : 'Data akan diambil otomatis dari Bursa Efek Indonesia.' }}
                            </p>
                        </div>

                        {{-- Manual Input Fields (explicit, no @foreach — prevents Livewire DOM diffing bugs) --}}
                        @if ($isManualMode)
                            <div class="space-y-4 rounded-2xl border border-amber-400/20 bg-amber-400/5 p-5" wire:key="manual-inputs">
                                <div class="flex items-center gap-2 pb-3 border-b border-amber-400/20">
                                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                                    <h3 class="text-sm font-bold text-amber-200">Data Fundamental Manual</h3>
                                </div>

                                <div class="grid gap-4 pt-2 sm:grid-cols-2 xl:grid-cols-3">
                                    {{-- EPS --}}
                                    <div wire:key="input-eps">
                                        <label for="input-eps" class="mb-2 flex items-center gap-2 text-sm font-semibold text-amber-100">
                                            EPS <span class="ml-auto text-xs font-normal text-amber-300/60">(Earnings Per Share)</span>
                                        </label>
                                        <input id="input-eps" wire:model="eps" type="number" step="0.01" placeholder="350"
                                            class="w-full rounded-2xl border border-amber-400/20 bg-slate-950/80 px-4 py-3 text-sm text-white placeholder:text-slate-600 outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15 hover:border-amber-400/40" />
                                        <p class="mt-1.5 text-xs text-amber-300/70">Laba per lembar saham</p>
                                        @error('eps') <p class="mt-1.5 text-xs text-rose-300">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- BVPS --}}
                                    <div wire:key="input-bvps">
                                        <label for="input-bvps" class="mb-2 flex items-center gap-2 text-sm font-semibold text-amber-100">
                                            BVPS <span class="ml-auto text-xs font-normal text-amber-300/60">(Book Value Per Share)</span>
                                        </label>
                                        <input id="input-bvps" wire:model="bvps" type="number" step="0.01" placeholder="2100"
                                            class="w-full rounded-2xl border border-amber-400/20 bg-slate-950/80 px-4 py-3 text-sm text-white placeholder:text-slate-600 outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15 hover:border-amber-400/40" />
                                        <p class="mt-1.5 text-xs text-amber-300/70">Nilai buku per lembar saham</p>
                                        @error('bvps') <p class="mt-1.5 text-xs text-rose-300">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- DER --}}
                                    <div wire:key="input-der">
                                        <label for="input-der" class="mb-2 flex items-center gap-2 text-sm font-semibold text-amber-100">
                                            DER <span class="ml-auto text-xs font-normal text-amber-300/60">(Debt to Equity)</span>
                                        </label>
                                        <input id="input-der" wire:model="der" type="number" step="0.01" placeholder="0.75"
                                            class="w-full rounded-2xl border border-amber-400/20 bg-slate-950/80 px-4 py-3 text-sm text-white placeholder:text-slate-600 outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15 hover:border-amber-400/40" />
                                        <p class="mt-1.5 text-xs text-amber-300/70">Rasio hutang / ekuitas</p>
                                        @error('der') <p class="mt-1.5 text-xs text-rose-300">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- ROE --}}
                                    <div wire:key="input-roe">
                                        <label for="input-roe" class="mb-2 flex items-center gap-2 text-sm font-semibold text-amber-100">
                                            ROE <span class="ml-auto text-xs font-normal text-amber-300/60">(Return on Equity %)</span>
                                        </label>
                                        <input id="input-roe" wire:model="roe" type="number" step="0.01" placeholder="20.5"
                                            class="w-full rounded-2xl border border-amber-400/20 bg-slate-950/80 px-4 py-3 text-sm text-white placeholder:text-slate-600 outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15 hover:border-amber-400/40" />
                                        <p class="mt-1.5 text-xs text-amber-300/70">Tingkat pengembalian ekuitas (%)</p>
                                        @error('roe') <p class="mt-1.5 text-xs text-rose-300">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- NPM --}}
                                    <div wire:key="input-npm">
                                        <label for="input-npm" class="mb-2 flex items-center gap-2 text-sm font-semibold text-amber-100">
                                            NPM <span class="ml-auto text-xs font-normal text-amber-300/60">(Net Profit Margin %)</span>
                                        </label>
                                        <input id="input-npm" wire:model="npm" type="number" step="0.01" placeholder="35.2"
                                            class="w-full rounded-2xl border border-amber-400/20 bg-slate-950/80 px-4 py-3 text-sm text-white placeholder:text-slate-600 outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15 hover:border-amber-400/40" />
                                        <p class="mt-1.5 text-xs text-amber-300/70">Margin laba bersih (%)</p>
                                        @error('npm') <p class="mt-1.5 text-xs text-rose-300">{{ $message }}</p> @enderror
                                    </div>

                                    {{-- Harga Saham --}}
                                    <div wire:key="input-price">
                                        <label for="input-price" class="mb-2 flex items-center gap-2 text-sm font-semibold text-amber-100">
                                            Harga <span class="ml-auto text-xs font-normal text-amber-300/60">(Harga Saham Rp)</span>
                                        </label>
                                        <input id="input-price" wire:model="currentPrice" type="number" step="0.01" placeholder="9500"
                                            class="w-full rounded-2xl border border-amber-400/20 bg-slate-950/80 px-4 py-3 text-sm text-white placeholder:text-slate-600 outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15 hover:border-amber-400/40" />
                                        <p class="mt-1.5 text-xs text-amber-300/70">Harga pasar terkini</p>
                                        @error('currentPrice') <p class="mt-1.5 text-xs text-rose-300">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        @endif
                    </form>
                </div>

                {{-- RIGHT: History Sidebar --}}
                <aside class="space-y-4 rounded-3xl border border-white/10 bg-gradient-to-br from-slate-900/80 to-slate-950/70 p-5 shadow-lg shadow-slate-950/20 backdrop-blur-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="flex items-center gap-2 text-base font-bold text-white">
                                <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                Riwayat Analisis
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">Data tersimpan selama 30 hari</p>
                        </div>
                        <span class="rounded-full bg-emerald-400/15 border border-emerald-400/30 px-3 py-1.5 text-xs font-bold text-emerald-300">
                            {{ count($history) }}
                        </span>
                    </div>

                    <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1 custom-scrollbar">
                        @forelse ($history as $item)
                            @php
                                $itemId = $item['id'];
                                $isSelected = $selectedHistoryId === $itemId;
                                $status = $item['analysis_result']['status'] ?? '';
                            @endphp
                            <div class="group relative rounded-2xl border transition-all cursor-pointer
                                {{ $isSelected
                                    ? 'border-emerald-400/50 bg-emerald-400/10 ring-2 ring-emerald-400/30'
                                    : 'border-white/10 bg-white/5 hover:border-emerald-400/30 hover:bg-white/10' }}">

                                <div wire:click="loadFromHistory({{ $itemId }})" class="p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-bold tracking-wide {{ $isSelected ? 'text-emerald-200' : 'text-white' }}">
                                                    {{ $item['ticker'] ?? 'N/A' }}
                                                </span>
                                                @if ($isSelected)
                                                    <span class="rounded-full bg-emerald-400/20 border border-emerald-400/30 px-2 py-0.5 text-[10px] font-bold text-emerald-300">AKTIF</span>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-xs text-slate-400 line-clamp-1">
                                                {{ $item['analysis_result']['verdict'] ?? 'Tersimpan' }}
                                            </p>
                                        </div>
                                        <span class="flex-shrink-0 rounded-full px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider shadow-lg
                                            {{ $status === 'BUY' ? 'bg-emerald-400/15 text-emerald-300 border border-emerald-400/30' : ($status === 'AVOID' ? 'bg-rose-400/15 text-rose-300 border border-rose-400/30' : 'bg-amber-400/15 text-amber-200 border border-amber-400/30') }}">
                                            {{ $status ?: '—' }}
                                        </span>
                                    </div>

                                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                        <div class="rounded-xl bg-white/5 border border-white/10 p-2.5">
                                            <div class="text-[10px] uppercase tracking-wider text-slate-500 mb-1">MoS</div>
                                            @php $mos = (float) ($item['analysis_result']['margin_of_safety'] ?? 0); @endphp
                                            <div class="font-bold {{ $mos >= 30 ? 'text-emerald-300' : ($mos >= 0 ? 'text-amber-200' : 'text-rose-300') }}">
                                                {{ number_format($mos, 1) }}%
                                            </div>
                                        </div>
                                        <div class="rounded-xl bg-white/5 border border-white/10 p-2.5">
                                            <div class="text-[10px] uppercase tracking-wider text-slate-500 mb-1">Skor</div>
                                            <div class="font-bold text-white">
                                                {{ $item['analysis_result']['health_score'] ?? 0 }}<span class="text-slate-500">/3</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between">
                                        <span class="text-[10px] text-slate-500">{{ $item['timestamp'] ?? '' }}</span>
                                        <button type="button"
                                            wire:click.stop="deleteHistory({{ $itemId }})"
                                            wire:confirm="Hapus riwayat {{ $item['ticker'] }}?"
                                            class="inline-flex items-center gap-1 rounded-lg bg-rose-400/10 hover:bg-rose-400/20 border border-rose-400/20 hover:border-rose-400/40 px-2.5 py-1.5 text-[10px] font-bold text-rose-300 transition-all">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-white/20 bg-white/5 p-8 text-center">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="text-sm font-medium text-slate-400 mb-1">Belum Ada Riwayat</p>
                                <p class="text-xs text-slate-500">Analisis saham pertama Anda untuk mengisi riwayat</p>
                            </div>
                        @endforelse
                    </div>
                </aside>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- SECTION 2: ANALYSIS RESULTS                                   --}}
        {{-- ============================================================ --}}
        @if ($analysisResult !== null)
            @php
                $status = $analysisResult['status'] ?? 'REVIEW';
                $isBuy = $status === 'BUY';
                $isAvoid = $status === 'AVOID';
                $isHold = $status === 'HOLD';
                $warnings = $analysisResult['warnings'] ?? [];
                $healthBreakdown = $analysisResult['health_breakdown'] ?? [];
                $healthScore = $analysisResult['health_score'] ?? 0;
                $healthMax = $analysisResult['health_max'] ?? 100;
                $healthGrade = $analysisResult['health_grade'] ?? 'F';
                $healthGradeLabel = $analysisResult['health_grade_label'] ?? 'N/A';
                $canValue = $analysisResult['can_value'] ?? false;
                $valuationMethod = $analysisResult['valuation_method'] ?? 'N/A';
                $verdictReason = $analysisResult['verdict_reason'] ?? '';

                $gradeColor = match ($healthGrade) {
                    'A' => 'emerald',
                    'B' => 'sky',
                    'C' => 'amber',
                    'D' => 'orange',
                    'F' => 'rose',
                    default => 'slate',
                };
            @endphp

            {{-- Warnings / Red Flags Banner --}}
            @if (count($warnings) > 0)
                <section class="rounded-2xl border border-amber-400/30 bg-amber-400/5 p-5">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 flex-shrink-0 text-amber-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <div class="flex-1">
                            <h3 class="text-sm font-bold text-amber-200 mb-2">⚠ Peringatan Analisis ({{ count($warnings) }})</h3>
                            <ul class="space-y-1.5">
                                @foreach ($warnings as $warning)
                                    <li class="flex items-start gap-2 text-sm text-amber-100/90">
                                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                                        {{ $warning }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </section>
            @endif

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.6fr)]">
                {{-- LEFT: Main Results --}}
                <div class="overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-6 shadow-2xl shadow-slate-950/40 backdrop-blur-xl sm:p-8">
                    <div class="flex flex-col gap-6">

                        {{-- Ticker Header + Status Badge --}}
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-400/10 border border-blue-400/20 px-3 py-1 text-xs font-bold uppercase tracking-widest text-blue-300">
                                        Hasil Analisis
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-700/50 border border-white/10 px-3 py-1 text-xs font-medium text-slate-300">
                                        {{ $valuationMethod }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-end gap-3 mb-2">
                                    <h2 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">
                                        {{ $analysisResult['ticker'] }}
                                    </h2>
                                    <span class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-bold uppercase tracking-widest shadow-lg
                                        {{ $isBuy ? 'border-emerald-400/30 bg-emerald-400/15 text-emerald-300' : ($isAvoid ? 'border-rose-400/30 bg-rose-400/15 text-rose-300' : 'border-amber-400/30 bg-amber-400/15 text-amber-200') }}">
                                        {{ $status }}
                                    </span>
                                </div>
                                @if (($analysisResult['company_name'] ?? '') !== $analysisResult['ticker'])
                                    <p class="text-sm text-slate-400">{{ $analysisResult['company_name'] }}</p>
                                @endif
                            </div>

                            {{-- Health Grade Badge --}}
                            <div class="flex items-center gap-4">
                                <div class="text-center">
                                    <div class="w-20 h-20 rounded-2xl border-2 border-{{ $gradeColor }}-400/40 bg-{{ $gradeColor }}-400/10 flex items-center justify-center">
                                        <span class="text-3xl font-black text-{{ $gradeColor }}-300">{{ $healthGrade }}</span>
                                    </div>
                                    <p class="text-xs text-{{ $gradeColor }}-300 mt-2 font-bold">{{ $healthGradeLabel }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $healthScore }}/{{ $healthMax }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Key Metrics Grid --}}
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {{-- Harga --}}
                            <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-slate-950/60 to-slate-900/40 p-5 shadow-lg">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/10 rounded-full blur-2xl"></div>
                                <p class="text-xs uppercase tracking-widest text-slate-500 mb-3">Harga Saat Ini</p>
                                <div class="text-2xl font-bold text-white relative">
                                    Rp {{ number_format((float) $analysisResult['current_price'], 0, ',', '.') }}
                                </div>
                            </div>

                            {{-- Nilai Wajar --}}
                            <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-slate-950/60 to-slate-900/40 p-5 shadow-lg">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/10 rounded-full blur-2xl"></div>
                                <p class="text-xs uppercase tracking-widest text-slate-500 mb-3">Nilai Wajar</p>
                                <div class="text-2xl font-bold text-white relative">
                                    @if ($analysisResult['fair_value'] !== null)
                                        Rp {{ number_format((float) $analysisResult['fair_value'], 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-500">N/A</span>
                                    @endif
                                </div>
                                <p class="text-[10px] text-slate-500 mt-1">{{ $valuationMethod }}</p>
                            </div>

                            {{-- Margin of Safety --}}
                            <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-slate-950/60 to-slate-900/40 p-5 shadow-lg">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/10 rounded-full blur-2xl"></div>
                                <p class="text-xs uppercase tracking-widest text-slate-500 mb-3">Margin of Safety</p>
                                @if ($analysisResult['margin_of_safety'] !== null)
                                    @php $mos = (float) $analysisResult['margin_of_safety']; @endphp
                                    <div class="text-2xl font-bold relative {{ $mos >= 30 ? 'text-emerald-300' : ($mos >= 0 ? 'text-amber-200' : 'text-rose-300') }}">
                                        {{ number_format($mos, 2) }}%
                                    </div>
                                @else
                                    <div class="text-2xl font-bold text-slate-500 relative">N/A</div>
                                @endif
                            </div>

                            {{-- Health Score --}}
                            <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-slate-950/60 to-slate-900/40 p-5 shadow-lg">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-{{ $gradeColor }}-500/10 rounded-full blur-2xl"></div>
                                <p class="text-xs uppercase tracking-widest text-slate-500 mb-3">Health Score</p>
                                <div class="text-2xl font-bold text-{{ $gradeColor }}-300 relative">
                                    {{ $healthScore }}<span class="text-xl text-slate-500">/{{ $healthMax }}</span>
                                </div>
                                {{-- Progress Bar --}}
                                <div class="mt-2 w-full bg-slate-800 rounded-full h-2">
                                    <div class="bg-{{ $gradeColor }}-400 h-2 rounded-full transition-all" style="width: {{ min(100, $healthScore) }}%"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Fundamental Data Table --}}
                        <div class="space-y-4">
                            <h3 class="flex items-center gap-2 text-lg font-bold text-white border-b border-white/10 pb-3">
                                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                                Data Fundamental
                            </h3>

                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @php
                                    $metrics = [
                                        ['EPS', $analysisResult['eps'], 'Earnings Per Share', $analysisResult['eps'] < 0],
                                        ['BVPS', $analysisResult['bvps'], 'Book Value Per Share', $analysisResult['bvps'] < 0],
                                        ['DER', $analysisResult['der'], 'Debt to Equity', $analysisResult['der'] > 2],
                                        ['ROE', $analysisResult['roe'], 'Return on Equity', $analysisResult['roe'] < 0],
                                        ['NPM', $analysisResult['npm'], 'Net Profit Margin', $analysisResult['npm'] < 0 || $analysisResult['npm'] > 100],
                                    ];
                                @endphp
                                @foreach ($metrics as [$label, $value, $desc, $isWarning])
                                    <div class="rounded-2xl border p-4 transition-all {{ $isWarning ? 'border-rose-400/30 bg-rose-400/5' : 'border-white/10 bg-white/5 hover:bg-white/10' }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <p class="text-xs uppercase tracking-wider text-slate-500">{{ $label }}</p>
                                            @if ($isWarning)
                                                <span class="rounded-full bg-rose-400/15 border border-rose-400/30 px-2 py-0.5 text-[10px] font-bold text-rose-300">⚠</span>
                                            @endif
                                        </div>
                                        <p class="text-xl font-bold {{ $isWarning ? 'text-rose-300' : 'text-white' }}">
                                            {{ number_format((float) $value, 2) }}{{ in_array($label, ['ROE', 'NPM']) ? '%' : '' }}
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1">{{ $desc }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Health Score Breakdown --}}
                        @if (count($healthBreakdown) > 0)
                            <div class="space-y-4">
                                <h3 class="flex items-center gap-2 text-lg font-bold text-white border-b border-white/10 pb-3">
                                    <svg class="w-5 h-5 text-{{ $gradeColor }}-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Skor Kesehatan — Grade {{ $healthGrade }} ({{ $healthGradeLabel }})
                                </h3>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($healthBreakdown as $key => $metric)
                                        @php
                                            $pct = $metric['max'] > 0 ? round(($metric['score'] / $metric['max']) * 100) : 0;
                                            $barColor = $pct >= 70 ? 'emerald' : ($pct >= 40 ? 'amber' : 'rose');
                                        @endphp
                                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                            <div class="flex items-center justify-between mb-2">
                                                <p class="text-sm font-semibold text-white">{{ $metric['label'] }}</p>
                                                <span class="text-xs font-bold text-{{ $barColor }}-300">
                                                    {{ $metric['score'] }}/{{ $metric['max'] }}
                                                </span>
                                            </div>
                                            <div class="w-full bg-slate-800 rounded-full h-2 mb-2">
                                                <div class="bg-{{ $barColor }}-400 h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <p class="text-xs text-slate-400">Nilai: {{ $metric['value'] }}</p>
                                                <span class="text-xs font-medium {{ $pct >= 70 ? 'text-emerald-300' : ($pct >= 40 ? 'text-amber-200' : 'text-rose-300') }}">
                                                    {{ $metric['status'] }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- RIGHT: Verdict + Notes --}}
                <div class="space-y-6">
                    {{-- Verdict Card --}}
                    <div class="rounded-3xl border-2 p-6 shadow-2xl
                        {{ $isBuy ? 'border-emerald-400/30 bg-gradient-to-br from-emerald-950/60 to-emerald-900/20' : ($isAvoid ? 'border-rose-400/30 bg-gradient-to-br from-rose-950/60 to-rose-900/20' : 'border-amber-400/30 bg-gradient-to-br from-amber-950/60 to-amber-900/20') }}">
                        <div class="flex items-center gap-3 mb-4">
                            @if ($isBuy)
                                <div class="w-12 h-12 rounded-2xl bg-emerald-400/20 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </div>
                            @elseif ($isAvoid)
                                <div class="w-12 h-12 rounded-2xl bg-rose-400/20 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-rose-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-2xl bg-amber-400/20 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                </div>
                            @endif
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest {{ $isBuy ? 'text-emerald-400' : ($isAvoid ? 'text-rose-400' : 'text-amber-400') }}">
                                    Rekomendasi
                                </p>
                                <h3 class="text-xl font-bold text-white">{{ $analysisResult['verdict'] }}</h3>
                            </div>
                        </div>
                        <p class="text-sm leading-relaxed {{ $isBuy ? 'text-emerald-100' : ($isAvoid ? 'text-rose-100' : 'text-amber-100') }}">
                            {{ $verdictReason }}
                        </p>
                    </div>

                    {{-- Meta Info --}}
                    <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-slate-900/80 to-slate-950/70 p-6 shadow-2xl backdrop-blur-xl">
                        <h3 class="flex items-center gap-2 text-base font-bold text-white mb-4">
                            <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            Detail Analisis
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center py-2 border-b border-white/5">
                                <span class="text-slate-400">Mode Input</span>
                                <span class="font-medium text-white">{{ $analysisResult['input_mode'] }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/5">
                                <span class="text-slate-400">Metode Valuasi</span>
                                <span class="font-medium text-white">{{ $valuationMethod }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/5">
                                <span class="text-slate-400">Health Grade</span>
                                <span class="font-bold text-{{ $gradeColor }}-300">{{ $healthGrade }} — {{ $healthGradeLabel }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-white/5">
                                <span class="text-slate-400">Peringatan</span>
                                <span class="font-medium {{ count($warnings) > 0 ? 'text-amber-300' : 'text-emerald-300' }}">
                                    {{ count($warnings) > 0 ? count($warnings) . ' masalah' : 'Tidak ada' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-slate-400">Waktu</span>
                                <span class="font-medium text-white">{{ $analysisResult['timestamp'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.3); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(34, 197, 94, 0.3); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(34, 197, 94, 0.5); }
    .custom-scrollbar { scrollbar-width: thin; scrollbar-color: rgba(34, 197, 94, 0.3) rgba(15, 23, 42, 0.3); }
</style>
