<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Laporan Finansial</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bw-card p-6">
                <form method="GET" action="{{ route('reports.financial') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="bw-input">
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="bw-input">
                    <select name="payment_status" class="bw-input">
                        <option value="">Semua Pembayaran</option>
                        <option value="paid" @selected($paymentStatus==='paid')>Paid</option>
                        <option value="partial" @selected($paymentStatus==='partial')>Partial</option>
                        <option value="unpaid" @selected($paymentStatus==='unpaid')>Unpaid</option>
                    </select>
                    <button class="bw-btn-primary">Filter</button>
                </form>

                <div class="mt-4">
                    <a
                        href="{{ route('reports.financial.export', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'payment_status' => $paymentStatus]) }}"
                        class="inline-flex items-center bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm font-medium"
                    >
                        Download Excel (.xls)
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bw-card p-4"><p class="bw-card-title">Total Order</p><p class="bw-card-value">{{ $summary['total_orders'] }}</p></div>
                <div class="bw-card p-4"><p class="bw-card-title">Gross</p><p class="bw-card-value">Rp {{ number_format($summary['gross_total'], 0, ',', '.') }}</p></div>
                <div class="bw-card p-4"><p class="bw-card-title">Diskon</p><p class="bw-card-value">Rp {{ number_format($summary['discount_total'], 0, ',', '.') }}</p></div>
                <div class="bw-card p-4"><p class="bw-card-title">Net</p><p class="bw-card-value">Rp {{ number_format($summary['net_total'], 0, ',', '.') }}</p></div>
            </div>

            <div class="bw-table-wrap overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Resi</th>
                            <th class="px-4 py-2 text-left">Pelanggan</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Bayar</th>
                            <th class="px-4 py-2 text-right">Final</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions as $trx)
                            <tr>
                                <td class="px-4 py-2">{{ $trx->receipt_number }}</td>
                                <td class="px-4 py-2">{{ $trx->customer?->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $trx->status }}</td>
                                <td class="px-4 py-2">{{ $trx->payment_status }}</td>
                                <td class="px-4 py-2 text-right">Rp {{ number_format((float) $trx->final_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
