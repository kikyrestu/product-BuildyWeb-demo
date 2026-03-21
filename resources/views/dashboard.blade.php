<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Dashboard Owner</h2>
            <span class="hidden md:inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">Laundry Operations</span>
        </div>
    </x-slot>

    @php
        $totalStatus = max(1, $pendingOrders + $processingOrders + $readyOrders + $deliveredOrders);
        $actualTotalStatus = $pendingOrders + $processingOrders + $readyOrders + $deliveredOrders;
        $radius = 70;
        $circumference = 2 * pi() * $radius;

        $segments = [
            ['value' => $pendingOrders, 'color' => '#f43f5e'],
            ['value' => $processingOrders, 'color' => '#f59e0b'],
            ['value' => $readyOrders, 'color' => '#22c55e'],
            ['value' => $deliveredOrders, 'color' => '#0ea5e9'],
        ];

        $offset = 0;
        $svgSegments = [];
        foreach ($segments as $segment) {
            $ratio = $segment['value'] / $totalStatus;
            $length = $ratio * $circumference;
            $svgSegments[] = [
                'length' => $length,
                'gap' => $circumference - $length,
                'offset' => $offset,
                'color' => $segment['color'],
            ];
            $offset -= $length;
        }
    @endphp

    <div class="py-7 lg:py-9">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-3xl bg-gradient-to-r from-sky-600 via-blue-600 to-blue-500 px-5 py-5 shadow-lg shadow-sky-500/20">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <article class="rounded-2xl border border-slate-300 bg-white/95 p-4 shadow-sm">
                        <p class="text-xs font-semibold tracking-wide text-slate-500">PENDING ORDERS</p>
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-3xl font-bold text-slate-900">{{ $pendingOrders }}</p>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/></svg>
                            </span>
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-300 bg-white/95 p-4 shadow-sm">
                        <p class="text-xs font-semibold tracking-wide text-slate-500">PROCESSING ORDER</p>
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-3xl font-bold text-slate-900">{{ $processingOrders }}</p>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6v6l3 3"/><circle cx="12" cy="12" r="9"/></svg>
                            </span>
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-300 bg-white/95 p-4 shadow-sm">
                        <p class="text-xs font-semibold tracking-wide text-slate-500">READY TO DELIVER</p>
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-3xl font-bold text-slate-900">{{ $readyOrders }}</p>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 12l3 3 7-7"/><circle cx="12" cy="12" r="9"/></svg>
                            </span>
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-300 bg-white/95 p-4 shadow-sm">
                        <p class="text-xs font-semibold tracking-wide text-slate-500">DELIVERED ORDERS</p>
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-3xl font-bold text-slate-900">{{ $deliveredOrders }}</p>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 text-sky-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
                            </span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                <div class="xl:col-span-2 bw-card p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-2xl font-semibold text-slate-900">Today's Delivery</h3>
                        <div class="flex items-center gap-2">
                            <span class="rounded-lg bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700">Omset Hari Ini: Rp {{ number_format($todayRevenue, 0, ',', '.') }}</span>
                            <span class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">Pelanggan: {{ $customersCount }}</span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        @forelse ($recentOrders as $order)
                            <a href="{{ route('orders.show', $order) }}" class="block rounded-xl border border-sky-200 bg-white p-3 hover:border-sky-400 hover:shadow-sm transition">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $order->customer?->name ?? 'Tanpa Nama' }}</p>
                                        <p class="text-xs text-slate-500 mt-1">{{ $order->receipt_number }}</p>
                                    </div>
                                    <span class="text-xs rounded-md bg-slate-100 px-2 py-1 text-slate-700">{{ $order->status }}</span>
                                </div>
                                <div class="mt-3 flex items-center justify-between">
                                    <p class="text-xs text-slate-500">{{ $order->payment_status }}</p>
                                    <p class="text-sm font-semibold text-slate-800">Rp {{ number_format((float) $order->final_amount, 0, ',', '.') }}</p>
                                </div>
                            </a>
                        @empty
                            <div class="md:col-span-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
                                Belum ada order hari ini.
                            </div>
                        @endforelse
                    </div>
                </div>

                <aside class="bw-card p-5">
                    <h3 class="text-2xl font-semibold text-slate-900">Overview</h3>
                    <div class="mx-auto mt-4 h-56 w-56 flex items-center justify-center">
                        <svg viewBox="0 0 180 180" class="h-56 w-56 -rotate-90">
                            <circle cx="90" cy="90" r="70" fill="none" stroke="#e2e8f0" stroke-width="32"></circle>
                            @if ($actualTotalStatus > 0)
                                @foreach ($svgSegments as $segment)
                                    @if ($segment['length'] > 0)
                                        <circle
                                            cx="90"
                                            cy="90"
                                            r="70"
                                            fill="none"
                                            stroke="{{ $segment['color'] }}"
                                            stroke-width="32"
                                            stroke-linecap="butt"
                                            stroke-dasharray="{{ $segment['length'] }} {{ $segment['gap'] }}"
                                            stroke-dashoffset="{{ $segment['offset'] }}"
                                        ></circle>
                                    @endif
                                @endforeach
                            @endif
                            <circle cx="90" cy="90" r="44" fill="white"></circle>
                        </svg>
                        @if ($actualTotalStatus === 0)
                            <span class="absolute text-xs font-semibold text-slate-500">No Data</span>
                        @endif
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <p class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>Pending ({{ $pendingOrders }})</p>
                        <p class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>Processing ({{ $processingOrders }})</p>
                        <p class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>Ready ({{ $readyOrders }})</p>
                        <p class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>Delivered ({{ $deliveredOrders }})</p>
                    </div>

                    <div class="mt-5 rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                        Total order: <span class="font-semibold text-slate-900">{{ $totalOrders }}</span><br>
                        Dalam proses: <span class="font-semibold text-amber-700">{{ $inProgressOrders }}</span>
                    </div>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
