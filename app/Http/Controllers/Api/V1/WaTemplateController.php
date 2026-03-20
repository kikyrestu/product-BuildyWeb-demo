<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WaTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => WaTemplate::query()->orderBy('template_key')->get(),
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'templates' => ['required', 'array', 'min:1'],
            'templates.*.template_key' => ['required', 'string', 'max:100'],
            'templates.*.title' => ['required', 'string', 'max:150'],
            'templates.*.content' => ['required', 'string', 'max:2000'],
            'templates.*.is_active' => ['sometimes', 'boolean'],
        ]);

        $stored = [];

        foreach ($validated['templates'] as $item) {
            $template = WaTemplate::query()->updateOrCreate(
                ['template_key' => $item['template_key']],
                [
                    'title' => $item['title'],
                    'content' => $item['content'],
                    'is_active' => (bool) ($item['is_active'] ?? true),
                ]
            );

            $stored[] = $template;
        }

        return response()->json([
            'data' => $stored,
        ]);
    }
}
