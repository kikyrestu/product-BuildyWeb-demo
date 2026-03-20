<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 7h18M3 12h18M3 17h18" />
                </svg>
            </span>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Tracking Board</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
            @endif

            <div class="mb-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <p>Tracking board menampilkan order tanggal <span class="font-semibold">{{ \Illuminate\Support\Carbon::parse($boardDate)->format('d M Y') }}</span>.</p>
                <form method="GET" action="{{ route('orders.tracking') }}" class="flex items-center gap-2">
                    <label for="board_date" class="text-xs font-semibold text-sky-700">Tanggal</label>
                    <input id="board_date" type="date" name="date" value="{{ $boardDate }}" class="bw-input py-1.5 text-xs">
                    <button class="inline-flex items-center justify-center rounded-md bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-500">Terapkan</button>
                </form>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
                @foreach ($statuses as $status)
                    <div class="bw-card p-4">
                        <h3 class="font-semibold text-sm uppercase tracking-wide text-slate-700">{{ $statusLabels[$status] ?? $status }}</h3>
                        <div class="mt-3 space-y-2">
                            @forelse ($columns[$status] as $order)
                                <div class="border border-slate-200 rounded-lg p-3 text-sm bg-white/80">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="font-semibold">{{ $order->receipt_number }}</p>
                                            <p class="text-slate-600">{{ $order->customer?->name ?? '-' }}</p>
                                        </div>
                                        <span class="bw-badge bw-badge-status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                                    </div>

                                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                                        <span class="bw-badge bw-badge-payment-{{ $order->payment_status }}">{{ $paymentStatusLabels[$order->payment_status] ?? $order->payment_status }}</span>
                                        <span class="text-xs text-slate-500">Rp {{ number_format((float) $order->final_amount, 0, ',', '.') }}</span>
                                    </div>

                                    @if ($order->paymentOption)
                                        <p class="mt-2 text-xs text-slate-600">Metode: {{ $order->paymentOption->label }}</p>
                                    @endif

                                    @if ($order->payment_proof_path)
                                        <a href="{{ route('media.public', ['path' => $order->payment_proof_path]) }}" target="_blank" class="inline-flex mt-2 text-xs text-cyan-700 hover:underline">Lihat Bukti Pembayaran</a>
                                    @endif

                                    @if ($order->completion_photo_path)
                                        <a href="{{ route('media.public', ['path' => $order->completion_photo_path]) }}" target="_blank" class="inline-flex mt-2 text-xs text-emerald-700 hover:underline">Lihat Bukti Foto</a>
                                    @endif

                                    <a
                                        href="{{ route('orders.send-wa', ['transaction' => $order, 'template_key' => $waTemplateByStatus[$order->status] ?? 'order_created']) }}"
                                        target="_blank"
                                        class="inline-flex mt-2 text-xs font-semibold text-green-700 hover:underline"
                                    >
                                        Kirim WA Customer
                                    </a>

                                    <a
                                        href="{{ $order->invoice_url }}"
                                        target="_blank"
                                        class="inline-flex mt-2 ml-2 text-xs font-semibold text-sky-700 hover:underline"
                                    >
                                        Buka Link Nota
                                    </a>

                                    <a
                                        href="{{ $order->invoice_pdf_url }}"
                                        class="inline-flex mt-2 ml-2 text-xs font-semibold text-violet-700 hover:underline"
                                    >
                                        Download PDF
                                    </a>

                                    <button
                                        type="button"
                                        class="inline-flex mt-2 ml-2 text-xs font-semibold text-indigo-700 hover:underline"
                                        onclick="navigator.clipboard.writeText('{{ $order->invoice_url }}').then(function(){ alert('Link nota disalin'); }).catch(function(){ window.prompt('Copy link nota:', '{{ $order->invoice_url }}'); });"
                                    >
                                        Copy Link Nota
                                    </button>

                                    <a href="{{ route('orders.show', $order) }}" class="inline-block mt-2 text-xs text-slate-700 hover:underline">Lihat detail</a>

                                    <div class="mt-3 space-y-2">
                                        <form method="POST" action="{{ route('orders.advance-status', $order) }}" enctype="multipart/form-data" class="space-y-2">
                                            @csrf
                                            @if ($order->status === 'proses_setrika' && empty($order->completion_photo_path))
                                                <input type="file" name="completion_photo" accept="image/*" class="bw-input text-xs">
                                                <p class="text-[11px] text-slate-500">Wajib upload foto untuk lanjut ke status selesai.</p>
                                            @endif
                                            <button class="w-full text-xs bg-slate-900 text-white rounded-md px-2 py-1.5 hover:bg-slate-700">Maju Status</button>
                                        </form>

                                        @if (!empty($transitions[$order->status]))
                                            <form method="POST" action="{{ route('orders.status.set', $order) }}" enctype="multipart/form-data" class="space-y-2">
                                                @csrf
                                                <select name="status" class="w-full text-xs bw-input py-1.5">
                                                    @foreach ($transitions[$order->status] as $next)
                                                        <option value="{{ $next }}">{{ $statusLabels[$next] ?? $next }}</option>
                                                    @endforeach
                                                </select>
                                                @if (in_array('selesai', $transitions[$order->status], true) && empty($order->completion_photo_path))
                                                    <input type="file" name="completion_photo" accept="image/*" class="bw-input text-xs">
                                                @endif
                                                <button class="w-full text-xs border border-slate-300 rounded-md px-2 py-1.5 hover:bg-slate-100">Set Status</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('orders.payment.update', $order) }}" enctype="multipart/form-data" class="space-y-2">
                                            @csrf
                                            <select name="payment_status" class="w-full text-xs bw-input py-1.5">
                                                <option value="unpaid" @selected($order->payment_status==='unpaid')>Unpaid</option>
                                                <option value="partial" @selected($order->payment_status==='partial')>Partial</option>
                                                <option value="paid" @selected($order->payment_status==='paid')>Paid</option>
                                            </select>

                                            <select name="payment_option_id" class="w-full text-xs bw-input py-1.5">
                                                <option value="">Pilih Metode Pembayaran</option>
                                                @foreach ($paymentOptions as $paymentOption)
                                                    <option value="{{ $paymentOption->id }}" @selected((int) $order->payment_option_id === (int) $paymentOption->id)>
                                                        {{ $paymentOption->label }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <input type="text" name="payment_note" value="{{ $order->payment_note }}" class="bw-input text-xs" placeholder="Catatan pembayaran (opsional)">

                                            <input type="file" name="payment_proof" accept="image/*" class="bw-input text-xs">
                                            <p class="text-[11px] text-slate-500">Jika metode transfer/QRIS, bukti pembayaran wajib diupload sebelum status diambil.</p>

                                            <button class="w-full text-xs border border-slate-300 rounded-md px-2 py-1.5 hover:bg-slate-100">Set Payment</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Tidak ada order.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
