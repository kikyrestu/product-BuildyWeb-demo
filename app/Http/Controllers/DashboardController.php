<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $todayRevenue = (float) Transaction::query()
            ->where('payment_status', 'paid')
            ->whereDate('created_at', now()->toDateString())
            ->sum('final_amount');

        $totalOrders = Transaction::query()->count();

        $inProgressOrders = Transaction::query()
            ->whereIn('status', ['antrean', 'proses_cuci', 'proses_setrika'])
            ->count();

        $customersCount = Customer::query()->count();

        $recentOrders = Transaction::query()
            ->with('customer:id,name,phone')
            ->latest('id')
            ->limit(8)
            ->get();

        $pendingOrders = Transaction::query()->where('status', 'antrean')->count();
        $processingOrders = Transaction::query()->whereIn('status', ['proses_cuci', 'proses_setrika'])->count();
        $readyOrders = Transaction::query()->where('status', 'selesai')->count();
        $deliveredOrders = Transaction::query()->where('status', 'diambil')->count();

        return view('dashboard', [
            'todayRevenue' => $todayRevenue,
            'totalOrders' => $totalOrders,
            'inProgressOrders' => $inProgressOrders,
            'customersCount' => $customersCount,
            'recentOrders' => $recentOrders,
            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'readyOrders' => $readyOrders,
            'deliveredOrders' => $deliveredOrders,
        ]);
    }
}
