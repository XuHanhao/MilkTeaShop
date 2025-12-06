<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $inventories = Inventory::query()
            ->with('logs')
            ->paginate($request->integer('per_page', 20));

        return $this->success($inventories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:10'],
            'current_qty' => ['required', 'numeric', 'min:0'],
            'threshold_qty' => ['nullable', 'numeric', 'min:0'],
        ]);

        $inventory = Inventory::create($validated);

        return $this->success($inventory, 'Inventory item created successfully');
    }

    public function adjust(Request $request, Inventory $inventory): JsonResponse
    {
        $validated = $request->validate([
            'change_qty' => ['required', 'numeric'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $inventory->increment('current_qty', $validated['change_qty']);
        $inventory->logs()->create([
            'user_id' => $request->user()->id,
            'change_qty' => $validated['change_qty'],
            'reason' => $validated['reason'] ?? null,
        ]);

        return $this->success($inventory->refresh()->load('logs'), 'Inventory adjusted successfully');
    }
}

