<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MasterItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterItemController extends Controller
{
    public function services(): JsonResponse
    {
        return response()->json([
            'data' => $this->itemsByType('service'),
        ]);
    }

    public function durations(): JsonResponse
    {
        return response()->json([
            'data' => $this->itemsByType('duration'),
        ]);
    }

    public function addons(): JsonResponse
    {
        return response()->json([
            'data' => $this->itemsByType('addon'),
        ]);
    }

    private function itemsByType(string $type)
    {
        return MasterItem::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:service,duration,addon'],
            'name' => ['required', 'string', 'max:120'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'is_percentage_modifier' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $item = MasterItem::query()->create([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'base_price' => $validated['base_price'],
            'unit' => $validated['unit'] ?? null,
            'is_percentage_modifier' => (bool) ($validated['is_percentage_modifier'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return response()->json([
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, MasterItem $masterItem): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'is_percentage_modifier' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $masterItem->fill($validated);
        $masterItem->save();

        return response()->json([
            'data' => $masterItem,
        ]);
    }
}
