<div class="min-h-screen bg-slate-950 text-slate-100">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
        <section
            class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-slate-950/40 backdrop-blur-xl sm:p-8">
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(34,197,94,0.16),transparent_32%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.12),transparent_28%),linear-gradient(135deg,rgba(15,23,42,0.9),rgba(2,6,23,0.95))]">
            </div>
            <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
                <div class="space-y-6">
                    <div class="space-y-3">
                        <span
                            class="inline-flex items-center rounded-full border border-emerald-400/25 bg-emerald-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-300">
                            Phase 4 - Reactive Stock Analysis
                        </span>
                        <div class="space-y-2">
                            <h1 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                                Stock Analysis Dashboard
                            </h1>
                            <p class="max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                                Search a ticker, fetch fundamentals automatically, or switch to manual mode and complete
                                the analysis with your own data. Results and 24-hour history are kept in Redis for a
                                fast repeat workflow.
                            </p>
                        </div>
                    </div>

                    <form wire:submit.prevent="analyze"
                        class="space-y-4 rounded-3xl border border-white/10 bg-slate-900/60 p-4 shadow-lg shadow-slate-950/20 sm:p-5">
                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                            <div>
                                <label for="ticker" class="mb-2 block text-sm font-medium text-slate-300">Ticker
                                    symbol</label>
                                <input id="ticker" type="text" wire:model.live="ticker"
                                    placeholder="AAPL, TSLA, MSFT"
                                    class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-sm text-white placeholder:text-slate-500 shadow-inner shadow-black/10 outline-none transition focus:border-emerald-400/60 focus:ring-4 focus:ring-emerald-400/15" />
                                @error('ticker')
                                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 focus:outline-none focus:ring-4 focus:ring-emerald-400/30 disabled:cursor-not-allowed disabled:opacity-60">
                                <svg wire:loading wire:target="analyze" class="h-4 w-4 animate-spin text-slate-950"
                                    viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-90" fill="currentColor"
                                        d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span wire:loading.remove wire:target="analyze">Analyze</span>
                                <span wire:loading wire:target="analyze">Analyzing</span>
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 border-t border-white/10 pt-4">
                            <button type="button" wire:click="toggleMode"
                                class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition {{ $isManualMode ? 'bg-amber-400/15 text-amber-200 ring-1 ring-amber-300/30' : 'bg-sky-400/15 text-sky-200 ring-1 ring-sky-300/30' }}">
                                <span
                                    class="h-2 w-2 rounded-full {{ $isManualMode ? 'bg-amber-300' : 'bg-sky-300' }}"></span>
                                {{ $isManualMode ? 'Manual Input' : 'Auto API Fetch' }}
                            </button>
                            <p class="text-sm text-slate-400">
                                {{ $isManualMode ? 'Enter your own fundamentals below.' : 'Live data will be fetched from the API.' }}
                            </p>
                        </div>

                        @if ($isManualMode)
                            <div class="grid gap-4 pt-2 sm:grid-cols-2 xl:grid-cols-3">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-300">EPS</label>
                                    <input wire:model.live="eps" type="number" step="0.01"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15" />
                                    @error('eps')
                                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-300">BVPS</label>
                                    <input wire:model.live="bvps" type="number" step="0.01"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15" />
                                    @error('bvps')
                                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-300">DER</label>
                                    <input wire:model.live="der" type="number" step="0.01"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15" />
                                    @error('der')
                                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-300">ROE (%)</label>
                                    <input wire:model.live="roe" type="number" step="0.01"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15" />
                                    @error('roe')
                                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-300">NPM (%)</label>
                                    <input wire:model.live="npm" type="number" step="0.01"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15" />
                                    @error('npm')
                                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-300">Current Price</label>
                                    <input wire:model.live="currentPrice" type="number" step="0.01"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-sm text-white outline-none transition focus:border-amber-400/60 focus:ring-4 focus:ring-amber-400/15" />
                                    @error('currentPrice')
                                        <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        @endif
                    </form>
                </div>

                <aside
                    class="space-y-4 rounded-3xl border border-white/10 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/20">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-white">Recent Searches</h2>
                            <p class="text-sm text-slate-400">Loaded from the 24-hour Redis cache.</p>
                        </div>
                        <div class="rounded-full bg-white/5 px-3 py-1 text-xs font-medium text-slate-300">
                            {{ count($history) }} item{{ count($history) === 1 ? '' : 's' }}
                        </div>
                    </div>

                    <div class="space-y-3">
                        @forelse ($history as $item)
                            <div
                                class="rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:border-emerald-400/20 hover:bg-white/7">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-sm font-semibold tracking-wide text-white">
                                            {{ $item['ticker'] ?? 'N/A' }}</div>
                                        <div class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">
                                            {{ $item['analysis_result']['verdict'] ?? ($item['analysis_result']['status'] ?? 'Archived') }}
                                        </div>
                                    </div>
                                    <span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] {{ ($item['analysis_result']['status'] ?? '') === 'BUY' ? 'bg-emerald-400/15 text-emerald-300' : (($item['analysis_result']['status'] ?? '') === 'AVOID' ? 'bg-rose-400/15 text-rose-300' : 'bg-amber-400/15 text-amber-200') }}">
                                        {{ $item['analysis_result']['status'] ?? 'Saved' }}
                                    </span>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-3 text-sm text-slate-300">
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.16em] text-slate-500">MoS</div>
                                        <div>
                                            {{ number_format((float) ($item['analysis_result']['margin_of_safety'] ?? 0), 2) }}%
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Score</div>
                                        <div>
                                            {{ $item['analysis_result']['health_score'] ?? 0 }}/{{ $item['analysis_result']['score_max'] ?? 3 }}
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-slate-500">
                                    {{ $item['timestamp'] ?? ($item['analysis_result']['timestamp'] ?? '') }}
                                </div>
                            </div>
                        @empty
                            <div
                                class="rounded-2xl border border-dashed border-white/10 bg-white/5 p-6 text-sm text-slate-400">
                                No recent searches yet. Run your first analysis to populate this history panel.
                            </div>
                        @endforelse
                    </div>
                </aside>
            </div>
        </section>

        @if ($analysisResult !== null)
            @php
                $status = $analysisResult['status'] ?? 'REVIEW';
                $isBuy = $status === 'BUY';
                $isAvoid = $status === 'AVOID';
                $isHold = $status === 'HOLD';
                $statusClasses = $isBuy
                    ? 'from-emerald-400/15 via-emerald-400/5 to-transparent text-emerald-300 ring-emerald-400/20'
                    : ($isAvoid
                        ? 'from-rose-400/15 via-rose-400/5 to-transparent text-rose-300 ring-rose-400/20'
                        : 'from-amber-400/15 via-amber-400/5 to-transparent text-amber-200 ring-amber-400/20');
            @endphp

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.6fr)]">
                <div
                    class="overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-slate-950/40 backdrop-blur-xl sm:p-8">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Analysis result
                            </p>
                            <div class="mt-3 flex flex-wrap items-end gap-3">
                                <h2 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                                    {{ $analysisResult['ticker'] }}</h2>
                                <span
                                    class="rounded-2xl border border-white/10 bg-gradient-to-r px-4 py-2 text-sm font-semibold uppercase tracking-[0.22em] {{ $statusClasses }}">
                                    {{ $status }}
                                </span>
                            </div>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                                {{ $analysisResult['verdict'] }} using {{ $analysisResult['input_mode'] }}.
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-300">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Scoring model</div>
                            <div class="mt-1 font-medium text-white">Benjamin Graham + health score</div>
                        </div>
                    </div>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 shadow-lg shadow-black/10">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Current Price</div>
                            <div class="mt-3 text-2xl font-semibold text-white">
                                ${{ number_format((float) ($analysisResult['current_price'] ?? 0), 2) }}</div>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 shadow-lg shadow-black/10">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Fair Value</div>
                            <div class="mt-3 text-2xl font-semibold text-white">
                                ${{ number_format((float) ($analysisResult['fair_value'] ?? 0), 2) }}</div>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 shadow-lg shadow-black/10">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Margin of Safety</div>
                            <div
                                class="mt-3 text-2xl font-semibold {{ ($analysisResult['margin_of_safety'] ?? 0) >= 30 ? 'text-emerald-300' : (($analysisResult['margin_of_safety'] ?? 0) >= 0 ? 'text-amber-200' : 'text-rose-300') }}">
                                {{ number_format((float) ($analysisResult['margin_of_safety'] ?? 0), 2) }}%
                            </div>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 shadow-lg shadow-black/10">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Health Score</div>
                            <div class="mt-3 text-2xl font-semibold text-white">
                                {{ $analysisResult['health_score'] ?? 0 }}/{{ $analysisResult['score_max'] ?? 3 }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-500">EPS</div>
                            <div class="mt-2 text-lg font-medium text-white">
                                {{ number_format((float) ($analysisResult['eps'] ?? 0), 2) }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-500">BVPS</div>
                            <div class="mt-2 text-lg font-medium text-white">
                                {{ number_format((float) ($analysisResult['bvps'] ?? 0), 2) }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-500">DER</div>
                            <div class="mt-2 text-lg font-medium text-white">
                                {{ number_format((float) ($analysisResult['der'] ?? 0), 2) }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-500">ROE / NPM</div>
                            <div class="mt-2 text-lg font-medium text-white">
                                {{ number_format((float) ($analysisResult['roe'] ?? 0), 2) }}% /
                                {{ number_format((float) ($analysisResult['npm'] ?? 0), 2) }}%
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-[2rem] border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-slate-950/40 backdrop-blur-xl">
                    <h3 class="text-lg font-semibold text-white">Analysis Notes</h3>
                    <div class="mt-4 space-y-4 text-sm leading-6 text-slate-300">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Final verdict</div>
                            <div class="mt-2 text-base font-medium text-white">{{ $analysisResult['verdict'] }}</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Mode</div>
                            <div class="mt-2 text-base font-medium text-white">{{ $analysisResult['input_mode'] }}
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-500">Saved at</div>
                            <div class="mt-2 text-base font-medium text-white">{{ $analysisResult['timestamp'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>
