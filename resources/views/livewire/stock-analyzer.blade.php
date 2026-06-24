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
                    <p class="text-sm font-medium" x-text="toast.message"></p>
                </div>
            </div>
        </template>
    </div>

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- ============================================================ --}}
        {{-- HEADER + SEARCH                                              --}}
        {{-- ============================================================ --}}
        <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-6 shadow-2xl shadow-slate-950/40 backdrop-blur-xl sm:p-8">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(34,197,94,0.16),transparent_32%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.12),transparent_28%)]"></div>

            <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.65fr)]">

                {{-- LEFT: Input Form --}}
                <div class="space-y-6">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/25 bg-emerald-400/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Nalar Saham Pro
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-400/10 px-3 py-1 text-xs font-medium text-blue-300">
                                Analisis Lengkap
                            </span>
                        </div>
                        <h1 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Analisis Saham Indonesia</h1>
                        <p class="max-w-2xl text-sm leading-6 text-slate-400">
                            Fundamental, Teknikal, Bandarmology, dan Trading Plan — semua dalam satu tempat.
                        </p>
                    </div>

                    <form wire:submit.prevent="analyze" class="space-y-5 rounded-3xl border border-white/10 bg-gradient-to-br from-slate-900/80 to-slate-950/60 p-5 shadow-lg shadow-slate-950/20 sm:p-6 backdrop-blur-sm">
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <label for="ticker" class="mb-2.5 flex items-center gap-2 text-sm font-semibold text-slate-200">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    Kode Saham
                                </label>
                                <input id="ticker" type="text" wire:model="ticker"
                                    placeholder="Contoh: BBCA, TLKM, BBRI, ASII"
                                    class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3.5 text-base font-medium text-white uppercase placeholder:text-slate-500 placeholder:normal-case shadow-inner shadow-black/10 outline-none transition focus:border-emerald-400/60 focus:ring-4 focus:ring-emerald-400/15 hover:border-white/20" />
                                @error('ticker')
                                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                class="group inline-flex flex-shrink-0 items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-emerald-400 to-emerald-500 px-6 py-3.5 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/25 transition-all hover:shadow-emerald-500/40 hover:scale-[1.02] focus:outline-none focus:ring-4 focus:ring-emerald-400/30 disabled:cursor-not-allowed disabled:opacity-60 whitespace-nowrap">
                                <svg wire:loading wire:target="analyze" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="analyze">🔍 Analisis</span>
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
                                {{ $isManualMode ? 'Masukkan data fundamental dari laporan keuangan.' : 'Data diambil otomatis dari Bursa Efek Indonesia.' }}
                            </p>
                        </div>

                        {{-- Manual Input Fields --}}
                        @if ($isManualMode)
                            <div class="space-y-4 rounded-2xl border border-amber-400/20 bg-amber-400/5 p-5" wire:key="manual-inputs">
                                <h3 class="text-sm font-bold text-amber-200 flex items-center gap-2 pb-3 border-b border-amber-400/20">
                                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                                    Data Fundamental Manual
                                </h3>
                                <div class="grid gap-4 pt-2 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach ([
                                        ['eps', 'EPS', 'Earnings Per Share', '350', 'Laba per lembar saham'],
                                        ['bvps', 'BVPS', 'Book Value Per Share', '2100', 'Nilai buku per lembar saham'],
                                        ['der', 'DER', 'Debt to Equity', '0.75', 'Rasio hutang / ekuitas'],
                                        ['roe', 'ROE', 'Return on Equity %', '20.5', 'Tingkat pengembalian ekuitas (%)'],
                                        ['npm', 'NPM', 'Net Profit Margin %', '35.2', 'Margin laba bersih (%)'],
                                        ['currentPrice', 'Harga', 'Harga Saham Rp', '9500', 'Harga pasar terkini'],
                                    ] as [$field, $label, $sub, $ph, $desc])
                                        <div wire:key="input-{{ $field }}">
                                            <label for="input-{{ $field }}" class="mb-2 flex items-center gap-2 text-sm font-semibold text-amber-100">
                                                {{ $label }} <span class="ml-auto text-xs font-normal text-amber-300/60">({{ $sub }})</span>
                                            </label>
                                            <input id="input-{{ $field }}" wire:model="{{ $field }}" type="number" step="0.01" placeholder="{{ $ph }}"
                                                class="w-full rounded-2xl border border-amber-400/20 bg-slate-950/80 px-4 py-3 text-sm text-white placeholder:text-slate-600 outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15 hover:border-amber-400/40" />
                                            <p class="mt-1.5 text-xs text-amber-300/70">{{ $desc }}</p>
                                            @error($field) <p class="mt-1.5 text-xs text-rose-300">{{ $message }}</p> @enderror
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </form>
                </div>

                {{-- RIGHT: History --}}
                <aside class="space-y-4 rounded-3xl border border-white/10 bg-gradient-to-br from-slate-900/80 to-slate-950/70 p-5 shadow-lg shadow-slate-950/20 backdrop-blur-sm">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="flex items-center gap-2 text-base font-bold text-white">
                            <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                            Riwayat
                        </h2>
                        <span class="rounded-full bg-emerald-400/15 border border-emerald-400/30 px-3 py-1.5 text-xs font-bold text-emerald-300">{{ count($history) }}</span>
                    </div>
                    <div class="space-y-2 max-h-[500px] overflow-y-auto pr-1 custom-scrollbar">
                        @forelse ($history as $item)
                            @php $itemId = $item['id']; $isSelected = $selectedHistoryId === $itemId; $status = $item['analysis_result']['status'] ?? ''; @endphp
                            <div wire:click="loadFromHistory({{ $itemId }})"
                                 class="group relative rounded-xl border p-3 transition-all cursor-pointer {{ $isSelected ? 'border-emerald-400/50 bg-emerald-400/10' : 'border-white/10 bg-white/5 hover:border-emerald-400/30 hover:bg-white/10' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-bold {{ $isSelected ? 'text-emerald-200' : 'text-white' }}">{{ $item['ticker'] ?? 'N/A' }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $status === 'BUY' ? 'bg-emerald-400/15 text-emerald-300' : ($status === 'AVOID' ? 'bg-rose-400/15 text-rose-300' : 'bg-amber-400/15 text-amber-200') }}">{{ $status ?: '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-[10px] text-slate-500">{{ $item['timestamp'] ?? '' }}</span>
                                    <button type="button" wire:click.stop="deleteHistory({{ $itemId }})" wire:confirm="Hapus riwayat {{ $item['ticker'] }}?"
                                        class="opacity-0 group-hover:opacity-100 text-[10px] text-rose-300 hover:text-rose-200 transition">Hapus</button>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-white/20 bg-white/5 p-6 text-center">
                                <p class="text-sm text-slate-400">Belum Ada Riwayat</p>
                                <p class="text-xs text-slate-500 mt-1">Analisis saham pertama Anda</p>
                            </div>
                        @endforelse
                    </div>
                </aside>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- ANALYSIS RESULTS                                              --}}
        {{-- ============================================================ --}}
        @if ($analysisResult !== null)
            @php
                $status = $analysisResult['status'] ?? 'REVIEW';
                $isBuy = $status === 'BUY';
                $isAvoid = $status === 'AVOID';
                $isHold = $status === 'HOLD';
                $warnings = $analysisResult['warnings'] ?? [];
                $healthScore = $analysisResult['health_score'] ?? 0;
                $healthMax = $analysisResult['health_max'] ?? 100;
                $healthGrade = $analysisResult['health_grade'] ?? 'F';
                $healthGradeLabel = $analysisResult['health_grade_label'] ?? 'N/A';
                $gradeColor = match ($healthGrade) { 'A' => 'emerald', 'B' => 'sky', 'C' => 'amber', 'D' => 'orange', 'F' => 'rose', default => 'slate' };
            @endphp

            {{-- ============================================================ --}}
            {{-- SECTION 1: Price & Trading Overview Cards                     --}}
            {{-- ============================================================ --}}
            <section class="space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-bold text-white">{{ $analysisResult['ticker'] }}</h2>
                    @if (($analysisResult['company_name'] ?? '') !== $analysisResult['ticker'])
                        <span class="text-sm text-slate-400">{{ $analysisResult['company_name'] }}</span>
                    @endif
                    <span class="inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-bold uppercase tracking-wider shadow
                        {{ $isBuy ? 'border-emerald-400/30 bg-emerald-400/15 text-emerald-300' : ($isAvoid ? 'border-rose-400/30 bg-rose-400/15 text-rose-300' : 'border-amber-400/30 bg-amber-400/15 text-amber-200') }}">
                        {{ $status }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-{{ $gradeColor }}-400/10 border border-{{ $gradeColor }}-400/30 px-3 py-1 text-xs font-bold text-{{ $gradeColor }}-300">
                        Grade {{ $healthGrade }}
                    </span>
                </div>

                {{-- Price, Change, Volume, Value Cards --}}
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Harga --}}
                    <div class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-5">
                        <p class="text-xs uppercase tracking-widest text-slate-500 mb-2">Harga Saat Ini</p>
                        <p class="text-2xl font-bold text-white">Rp {{ number_format((float) $analysisResult['current_price'], 0, ',', '.') }}</p>
                        @if ($tradingData)
                            @php $change = $tradingData['change'] ?? 0; $changePct = $tradingData['change_percent'] ?? 0; @endphp
                            <p class="text-sm mt-1 {{ $change >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $change >= 0 ? '+' : '' }}{{ number_format($change, 0, ',', '.') }} ({{ $change >= 0 ? '+' : '' }}{{ $changePct }}%)
                            </p>
                        @endif
                    </div>

                    {{-- Perubahan Hari Ini --}}
                    <div class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-5">
                        <p class="text-xs uppercase tracking-widest text-slate-500 mb-2">Perubahan Hari Ini</p>
                        @if ($tradingData && $tradingData['change_percent'] !== null)
                            @php $pct = $tradingData['change_percent']; @endphp
                            <p class="text-2xl font-bold {{ $pct >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $pct >= 0 ? '+' : '' }}{{ $pct }}%
                            </p>
                            <p class="text-xs text-slate-500 mt-1">Prev: Rp {{ number_format($tradingData['previous'] ?? 0, 0, ',', '.') }}</p>
                        @else
                            <p class="text-2xl font-bold text-slate-500">N/A</p>
                        @endif
                    </div>

                    {{-- Volume --}}
                    <div class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-5">
                        <p class="text-xs uppercase tracking-widest text-slate-500 mb-2">Volume</p>
                        @if ($tradingData && $tradingData['volume'])
                            <p class="text-2xl font-bold text-white">{{ $this->formatNumber($tradingData['volume']) }}</p>
                            <p class="text-xs text-slate-500 mt-1">lot hari ini</p>
                        @else
                            <p class="text-2xl font-bold text-slate-500">N/A</p>
                        @endif
                    </div>

                    {{-- Value --}}
                    <div class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-5">
                        <p class="text-xs uppercase tracking-widest text-slate-500 mb-2">Value Transaksi</p>
                        @if ($tradingData && $tradingData['value'])
                            <p class="text-2xl font-bold text-white">Rp {{ $this->formatNumber($tradingData['value']) }}</p>
                            <p class="text-xs text-slate-500 mt-1">hari ini</p>
                        @else
                            <p class="text-2xl font-bold text-slate-500">N/A</p>
                        @endif
                    </div>
                </div>
            </section>

            {{-- ============================================================ --}}
            {{-- SECTION 2: Foreign & Ritel Flow                               --}}
            {{-- ============================================================ --}}
            @if ($bandarmologyData)
                @php
                    $foreignVal = $bandarmologyData['net_foreign']['value'] ?? null;
                    $foreignBuy = $bandarmologyData['net_foreign']['buy'] ?? null;
                    $foreignSell = $bandarmologyData['net_foreign']['sell'] ?? null;
                    $hasForeignData = $foreignVal !== null && ($foreignBuy > 0 || $foreignSell > 0 || abs($foreignVal) > 0);
                    $ritelVal = $bandarmologyData['net_ritel']['value'] ?? null;
                    $hasRitelData = $ritelVal !== null && abs($ritelVal) > 0;
                @endphp
                <section class="grid gap-3 sm:grid-cols-2">
                    {{-- Net Foreign --}}
                    <div class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-5">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs uppercase tracking-widest text-slate-500">🌐 Net Foreign Buy/Sell</p>
                            @if ($hasForeignData)
                                @php $foreignStatus = $bandarmologyData['net_foreign']['status'] ?? 'N/A'; @endphp
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $foreignStatus === 'Net Buy' ? 'bg-emerald-400/15 text-emerald-300' : 'bg-rose-400/15 text-rose-300' }}">
                                    {{ $foreignStatus }}
                                </span>
                            @endif
                        </div>
                        @if ($hasForeignData)
                            <p class="text-2xl font-bold {{ $foreignVal >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $foreignVal >= 0 ? '+' : '' }}Rp {{ $this->formatNumber(abs($foreignVal)) }}
                            </p>
                            @if ($foreignBuy > 0 || $foreignSell > 0)
                                <div class="flex gap-4 mt-2 text-xs">
                                    <span class="text-emerald-400">Buy: Rp {{ $this->formatNumber($foreignBuy) }}</span>
                                    <span class="text-rose-400">Sell: Rp {{ $this->formatNumber($foreignSell) }}</span>
                                </div>
                            @endif
                        @else
                            <p class="text-lg text-slate-500">Data tidak tersedia untuk saham ini</p>
                            <p class="text-xs text-slate-600 mt-1">Data foreign flow hanya tersedia untuk saham tertentu</p>
                        @endif
                    </div>

                    {{-- Net Ritel --}}
                    <div class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-5">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs uppercase tracking-widest text-slate-500">👥 Net Ritel Buy/Sell</p>
                            @if ($hasRitelData)
                                @php $ritelStatus = $bandarmologyData['net_ritel']['status'] ?? 'N/A'; @endphp
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold {{ $ritelStatus === 'Net Buy' ? 'bg-emerald-400/15 text-emerald-300' : 'bg-rose-400/15 text-rose-300' }}">
                                    {{ $ritelStatus }}
                                </span>
                            @endif
                        </div>
                        @if ($hasRitelData)
                            <p class="text-2xl font-bold {{ $ritelVal >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $ritelVal >= 0 ? '+' : '' }}Rp {{ $this->formatNumber(abs($ritelVal)) }}
                            </p>
                        @else
                            <p class="text-lg text-slate-500">Data tidak tersedia untuk saham ini</p>
                            <p class="text-xs text-slate-600 mt-1">Data ritel flow hanya tersedia untuk saham tertentu</p>
                        @endif
                    </div>
                </section>
            @endif

            {{-- ============================================================ --}}
            {{-- SECTION 3: TradingView Chart                                  --}}
            {{-- ============================================================ --}}
            <section class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-4 overflow-hidden" wire:key="tv-chart-{{ $analysisResult['ticker'] }}">
                <h3 class="flex items-center gap-2 text-base font-bold text-white mb-3 px-1">
                    📈 Grafik Harga — {{ $analysisResult['ticker'] }}
                </h3>
                <div class="rounded-xl overflow-hidden" style="height: 500px;">
                    <!-- TradingView Advanced Chart Widget (Embed) -->
                    <div class="tradingview-widget-container" style="height:100%;width:100%">
                        <div class="tradingview-widget-container__widget" style="height:calc(100% - 32px);width:100%"></div>
                        <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js" async>
                        {
                            "autosize": true,
                            "symbol": "IDX:{{ $analysisResult['ticker'] }}",
                            "interval": "D",
                            "timezone": "Asia/Jakarta",
                            "theme": "dark",
                            "style": "1",
                            "locale": "id",
                            "backgroundColor": "rgba(15, 23, 42, 1)",
                            "gridColor": "rgba(30, 41, 59, 0.5)",
                            "hide_top_toolbar": false,
                            "hide_legend": false,
                            "allow_symbol_change": true,
                            "save_image": false,
                            "calendar": false,
                            "studies": ["RSI@tv-basicstudies", "MASimple@tv-basicstudies"],
                            "support_host": "https://www.tradingview.com"
                        }
                        </script>
                    </div>
                </div>
            </section>

            {{-- ============================================================ --}}
            {{-- SECTION 4: Bandarmology / Accumulation                        --}}
            {{-- ============================================================ --}}
            @if ($bandarmologyData)
                <section class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-6">
                    <h3 class="flex items-center gap-2 text-base font-bold text-white mb-4">
                        🎯 Analisis Bandarmology
                    </h3>
                    <div class="grid gap-4 lg:grid-cols-2">
                        {{-- Left: Score & Phase --}}
                        <div class="space-y-4">
                            {{-- Accumulation Score --}}
                            @php $accScore = $bandarmologyData['accumulation_score'] ?? 50; @endphp
                            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-semibold text-white">Skor Akumulasi</p>
                                    <span class="text-lg font-bold {{ $accScore >= 60 ? 'text-emerald-400' : ($accScore >= 40 ? 'text-amber-400' : 'text-rose-400') }}">{{ $accScore }}/100</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-3">
                                    <div class="h-3 rounded-full transition-all {{ $accScore >= 60 ? 'bg-emerald-400' : ($accScore >= 40 ? 'bg-amber-400' : 'bg-rose-400') }}" style="width: {{ $accScore }}%"></div>
                                </div>
                            </div>

                            {{-- Phase --}}
                            @php
                                $phase = $bandarmologyData['phase'] ?? 'N/A';
                                $phaseColor = match($phase) { 'Akumulasi' => 'emerald', 'Markup' => 'sky', 'Distribusi' => 'rose', 'Markdown' => 'red', default => 'amber' };
                            @endphp
                            <div class="rounded-xl border border-{{ $phaseColor }}-400/30 bg-{{ $phaseColor }}-400/5 p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="w-3 h-3 rounded-full bg-{{ $phaseColor }}-400"></span>
                                    <p class="text-sm font-bold text-{{ $phaseColor }}-300">Fase: {{ $phase }}</p>
                                </div>
                                <p class="text-sm text-slate-300 leading-relaxed">{{ $bandarmologyData['phase_description'] ?? '' }}</p>
                            </div>
                        </div>

                        {{-- Right: Indicators --}}
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ([
                                ['A/D Line', $bandarmologyData['ad_line']['trend'] ?? 'N/A', 'Akumulasi/Distribusi'],
                                ['OBV', $bandarmologyData['obv']['trend'] ?? 'N/A', 'On-Balance Volume'],
                                ['MFI', ($bandarmologyData['mfi']['value'] ?? 'N/A') . ' (' . ($bandarmologyData['mfi']['status'] ?? '') . ')', 'Money Flow Index'],
                                ['VPT', $bandarmologyData['vpt']['trend'] ?? 'N/A', 'Volume Price Trend'],
                            ] as [$label, $value, $desc])
                                <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                                    <p class="text-[10px] uppercase tracking-wider text-slate-500 mb-1">{{ $label }}</p>
                                    <p class="text-sm font-bold text-white">{{ $value }}</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">{{ $desc }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            {{-- ============================================================ --}}
            {{-- SECTION 5: Technical Analysis                                 --}}
            {{-- ============================================================ --}}
            @if ($technicalData)
                <section class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-6">
                    <h3 class="flex items-center gap-2 text-base font-bold text-white mb-4">
                        📊 Analisis Teknikal
                    </h3>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {{-- RSI --}}
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-1">RSI (14)</p>
                            @php $rsiVal = $technicalData['rsi']['value'] ?? null; @endphp
                            <p class="text-xl font-bold {{ $rsiVal !== null && $rsiVal < 30 ? 'text-emerald-400' : ($rsiVal !== null && $rsiVal > 70 ? 'text-rose-400' : 'text-white') }}">
                                {{ $rsiVal !== null ? round($rsiVal, 1) : 'N/A' }}
                            </p>
                            <p class="text-xs {{ $rsiVal !== null && $rsiVal < 30 ? 'text-emerald-400' : ($rsiVal !== null && $rsiVal > 70 ? 'text-rose-400' : 'text-slate-500') }} mt-1">{{ $technicalData['rsi']['status'] ?? 'N/A' }}</p>
                        </div>

                        {{-- MACD --}}
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-1">MACD</p>
                            <p class="text-xl font-bold text-white">{{ $technicalData['macd']['histogram'] !== null ? round($technicalData['macd']['histogram'], 1) : 'N/A' }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $technicalData['macd']['status'] ?? 'N/A' }}</p>
                        </div>

                        {{-- Trend --}}
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Tren</p>
                            @php $dir = $technicalData['trend']['direction'] ?? 'N/A'; @endphp
                            <p class="text-xl font-bold {{ $dir === 'Bullish' ? 'text-emerald-400' : ($dir === 'Bearish' ? 'text-rose-400' : 'text-amber-400') }}">{{ $dir }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $technicalData['trend']['strength'] ?? '' }}</p>
                        </div>

                        {{-- Signal --}}
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-1">Sinyal</p>
                            @php $sig = $technicalData['signal']['action'] ?? 'HOLD'; @endphp
                            <p class="text-xl font-bold {{ $sig === 'BUY' ? 'text-emerald-400' : ($sig === 'SELL' ? 'text-rose-400' : 'text-amber-400') }}">{{ $sig }}</p>
                            <p class="text-xs text-slate-500 mt-1">Confidence: {{ $technicalData['signal']['confidence'] ?? 0 }}%</p>
                        </div>
                    </div>

                    {{-- Moving Averages & Bollinger --}}
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Moving Averages</p>
                            <div class="grid grid-cols-4 gap-2 text-center">
                                @foreach (['ma5', 'ma10', 'ma20', 'ma50'] as $ma)
                                    <div>
                                        <p class="text-[10px] text-slate-500 uppercase">{{ strtoupper($ma) }}</p>
                                        <p class="text-sm font-bold text-white">{{ $technicalData['moving_averages'][$ma] !== null ? number_format($technicalData['moving_averages'][$ma], 0, ',', '.') : '-' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Bollinger Bands & Support/Resistance</p>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div><span class="text-slate-500">Support:</span> <span class="font-bold text-emerald-400">{{ $technicalData['support_resistance']['support'] !== null ? number_format($technicalData['support_resistance']['support'], 0, ',', '.') : '-' }}</span></div>
                                <div><span class="text-slate-500">Resistance:</span> <span class="font-bold text-rose-400">{{ $technicalData['support_resistance']['resistance'] !== null ? number_format($technicalData['support_resistance']['resistance'], 0, ',', '.') : '-' }}</span></div>
                                <div><span class="text-slate-500">BB Lower:</span> <span class="font-bold text-white">{{ $technicalData['bollinger']['lower'] !== null ? number_format($technicalData['bollinger']['lower'], 0, ',', '.') : '-' }}</span></div>
                                <div><span class="text-slate-500">BB Upper:</span> <span class="font-bold text-white">{{ $technicalData['bollinger']['upper'] !== null ? number_format($technicalData['bollinger']['upper'], 0, ',', '.') : '-' }}</span></div>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- ============================================================ --}}
            {{-- SECTION 6: Trading Plan                                       --}}
            {{-- ============================================================ --}}
            @if ($tradingPlan)
                <section class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-6">
                    <h3 class="flex items-center gap-2 text-base font-bold text-white mb-4">
                        🗺️ Rencana Trading
                    </h3>

                    {{-- Entry / SL / TP Cards --}}
                    <div class="grid gap-3 sm:grid-cols-3 mb-4">
                        {{-- Entry Area --}}
                        <div class="rounded-xl border-2 border-sky-400/30 bg-sky-400/5 p-4 text-center">
                            <p class="text-xs uppercase tracking-wider text-sky-400 mb-2">📍 Area Entry</p>
                            <p class="text-xl font-bold text-white">
                                Rp {{ number_format($tradingPlan['entry_area']['low'], 0, ',', '.') }} - {{ number_format($tradingPlan['entry_area']['high'], 0, ',', '.') }}
                            </p>
                        </div>

                        {{-- Stop Loss --}}
                        <div class="rounded-xl border-2 border-rose-400/30 bg-rose-400/5 p-4 text-center">
                            <p class="text-xs uppercase tracking-wider text-rose-400 mb-2">🛑 Stop Loss</p>
                            <p class="text-xl font-bold text-rose-300">Rp {{ number_format($tradingPlan['stop_loss']['price'], 0, ',', '.') }}</p>
                            <p class="text-xs text-rose-400/80 mt-1">({{ $tradingPlan['stop_loss']['percentage'] }}%)</p>
                        </div>

                        {{-- Take Profit --}}
                        <div class="rounded-xl border-2 border-emerald-400/30 bg-emerald-400/5 p-4 text-center">
                            <p class="text-xs uppercase tracking-wider text-emerald-400 mb-2">🎯 Take Profit</p>
                            <p class="text-lg font-bold text-emerald-300">TP1: Rp {{ number_format($tradingPlan['take_profit']['tp1']['price'], 0, ',', '.') }} <span class="text-xs">(+{{ $tradingPlan['take_profit']['tp1']['percentage'] }}%)</span></p>
                            <p class="text-lg font-bold text-emerald-200 mt-1">TP2: Rp {{ number_format($tradingPlan['take_profit']['tp2']['price'], 0, ',', '.') }} <span class="text-xs">(+{{ $tradingPlan['take_profit']['tp2']['percentage'] }}%)</span></p>
                        </div>
                    </div>

                    {{-- Probability, Outlook, Duration --}}
                    <div class="grid gap-3 sm:grid-cols-3 mb-4">
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Probabilitas Kenaikan</p>
                            @php $prob = $tradingPlan['probabilitas'] ?? 0; @endphp
                            <p class="text-2xl font-bold {{ $prob >= 60 ? 'text-emerald-400' : ($prob >= 35 ? 'text-amber-400' : 'text-rose-400') }}">{{ $prob }}%</p>
                            <div class="w-full bg-slate-800 rounded-full h-2 mt-2">
                                <div class="h-2 rounded-full transition-all {{ $prob >= 60 ? 'bg-emerald-400' : ($prob >= 35 ? 'bg-amber-400' : 'bg-rose-400') }}" style="width: {{ $prob }}%"></div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Outlook</p>
                            <p class="text-lg font-bold text-white">{{ $tradingPlan['outlook'] ?? 'N/A' }}</p>
                            <p class="text-xs text-slate-400 mt-1">R:R {{ $tradingPlan['risk_reward'] ?? '-' }}x</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Durasi Rekomendasi</p>
                            <p class="text-lg font-bold text-white">{{ $tradingPlan['durasi'] ?? 'N/A' }}</p>
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if (!empty($tradingPlan['catatan']))
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Catatan Penting</p>
                            <ul class="space-y-1.5">
                                @foreach ($tradingPlan['catatan'] as $note)
                                    <li class="text-sm text-slate-300">{{ $note }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>
            @endif

            {{-- ============================================================ --}}
            {{-- SECTION 7: Fundamental + Health Score + Verdict               --}}
            {{-- ============================================================ --}}
            <section class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.6fr)]">
                {{-- LEFT: Fundamental Data --}}
                <div class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] p-6">
                    <h3 class="flex items-center gap-2 text-base font-bold text-white mb-4">
                        📋 Data Fundamental & Kesehatan
                    </h3>

                    {{-- Key Metrics --}}
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-4">
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Nilai Wajar</p>
                            <p class="text-xl font-bold text-white">
                                {{ $analysisResult['fair_value'] !== null ? 'Rp ' . number_format($analysisResult['fair_value'], 0, ',', '.') : 'N/A' }}
                            </p>
                            <p class="text-[10px] text-slate-500">{{ $analysisResult['valuation_method'] ?? '' }}</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Margin of Safety</p>
                            @php $mos = $analysisResult['margin_of_safety']; @endphp
                            <p class="text-xl font-bold {{ $mos !== null && $mos >= 30 ? 'text-emerald-300' : ($mos !== null && $mos >= 0 ? 'text-amber-200' : 'text-rose-300') }}">
                                {{ $mos !== null ? number_format($mos, 1) . '%' : 'N/A' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Health Score</p>
                            <p class="text-xl font-bold text-{{ $gradeColor }}-300">{{ $healthScore }}<span class="text-slate-500">/{{ $healthMax }}</span></p>
                            <div class="w-full bg-slate-800 rounded-full h-2 mt-2">
                                <div class="bg-{{ $gradeColor }}-400 h-2 rounded-full" style="width: {{ min(100, $healthScore) }}%"></div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Grade</p>
                            <p class="text-xl font-bold text-{{ $gradeColor }}-300">{{ $healthGrade }} — {{ $healthGradeLabel }}</p>
                        </div>
                    </div>

                    {{-- Fundamental Metrics --}}
                    <div class="grid gap-2 sm:grid-cols-5">
                        @foreach ([
                            ['EPS', $analysisResult['eps'], $analysisResult['eps'] < 0],
                            ['BVPS', $analysisResult['bvps'], $analysisResult['bvps'] < 0],
                            ['DER', $analysisResult['der'], $analysisResult['der'] > 2],
                            ['ROE', $analysisResult['roe'] . '%', $analysisResult['roe'] < 0],
                            ['NPM', $analysisResult['npm'] . '%', $analysisResult['npm'] < 0],
                        ] as [$label, $value, $warn])
                            <div class="rounded-xl border p-3 {{ $warn ? 'border-rose-400/30 bg-rose-400/5' : 'border-white/10 bg-white/5' }}">
                                <p class="text-[10px] uppercase tracking-wider text-slate-500">{{ $label }}</p>
                                <p class="text-lg font-bold {{ $warn ? 'text-rose-300' : 'text-white' }}">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- Warnings --}}
                    @if (count($warnings) > 0)
                        <div class="mt-4 rounded-xl border border-amber-400/30 bg-amber-400/5 p-4">
                            <p class="text-xs font-bold text-amber-200 mb-2">⚠ Peringatan ({{ count($warnings) }})</p>
                            <ul class="space-y-1">
                                @foreach ($warnings as $w)
                                    <li class="text-xs text-amber-100/90 flex items-start gap-1.5">
                                        <span class="mt-1.5 w-1 h-1 rounded-full bg-amber-400 flex-shrink-0"></span>{{ $w }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                {{-- RIGHT: Verdict --}}
                <div class="space-y-4">
                    <div class="rounded-2xl border-2 p-5
                        {{ $isBuy ? 'border-emerald-400/30 bg-gradient-to-br from-emerald-950/60 to-emerald-900/20' : ($isAvoid ? 'border-rose-400/30 bg-gradient-to-br from-rose-950/60 to-rose-900/20' : 'border-amber-400/30 bg-gradient-to-br from-amber-950/60 to-amber-900/20') }}">
                        <p class="text-xs font-bold uppercase tracking-widest {{ $isBuy ? 'text-emerald-400' : ($isAvoid ? 'text-rose-400' : 'text-amber-400') }} mb-2">Rekomendasi</p>
                        <h3 class="text-xl font-bold text-white mb-2">{{ $analysisResult['verdict'] }}</h3>
                        <p class="text-sm leading-relaxed {{ $isBuy ? 'text-emerald-100' : ($isAvoid ? 'text-rose-100' : 'text-amber-100') }}">
                            {{ $analysisResult['verdict_reason'] ?? '' }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <p class="text-xs uppercase tracking-wider text-slate-500 mb-3">Detail</p>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-slate-400">Mode</span><span class="text-white">{{ $analysisResult['input_mode'] }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-400">Valuasi</span><span class="text-white">{{ $analysisResult['valuation_method'] ?? '' }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-400">Waktu</span><span class="text-white">{{ $analysisResult['timestamp'] }}</span></div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============================================================ --}}
            {{-- SECTION 8: AI Analysis (Optional)                             --}}
            {{-- ============================================================ --}}
            @if ($aiAvailable)
                <section class="rounded-2xl border border-white/10 bg-gradient-to-br from-purple-950/30 to-white/[0.02] p-6">
                    <h3 class="flex items-center gap-2 text-base font-bold text-white mb-4">
                        🤖 Analisis AI (Gemini)
                    </h3>

                    @if ($aiAnalysis === null)
                        <div class="text-center py-6">
                            <p class="text-sm text-slate-400 mb-4">Dapatkan narasi analisis mendalam dari AI untuk saham {{ $analysisResult['ticker'] }}</p>
                            <button wire:click="generateAiAnalysis"
                                wire:loading.attr="disabled"
                                wire:target="generateAiAnalysis"
                                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 to-violet-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-purple-500/25 transition-all hover:shadow-purple-500/40 hover:scale-[1.02] disabled:opacity-60">
                                <svg wire:loading wire:target="generateAiAnalysis" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="generateAiAnalysis">✨ Generate Analisis AI</span>
                                <span wire:loading wire:target="generateAiAnalysis">Membuat analisis...</span>
                            </button>
                        </div>
                    @else
                        <div class="space-y-4">
                            <div class="prose prose-invert prose-sm max-w-none rounded-xl border border-white/10 bg-white/5 p-5">
                                {!! nl2br(e($aiAnalysis['summary'] ?? '')) !!}
                            </div>
                            <p class="text-[10px] text-slate-500">
                                Dibuat oleh {{ $aiAnalysis['model'] ?? 'Gemini' }} pada {{ $aiAnalysis['generated_at'] ?? '' }}
                            </p>
                        </div>
                    @endif
                </section>
            @endif

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
