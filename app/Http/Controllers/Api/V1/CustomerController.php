<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query()->orderBy('name');

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();

            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        return response()->json([
            'data' => $query->limit(20)->get(),
        ]);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'data' => $customer,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $customer = Customer::query()->create($validated);

        return response()->json([
            'data' => $customer,
        ], 201);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $customer->fill($validated);
        $customer->save();

        return response()->json([
            'data' => $customer,
        ]);
    }
}
