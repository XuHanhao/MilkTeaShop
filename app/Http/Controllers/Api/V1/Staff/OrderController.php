<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query();
        
        if ($request->has('status') && $request->string('status')) {
            $query->where('status', $request->string('status'));
        }
        
        $orders = $query->with(['items', 'customer'])
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success($orders);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'preparing', 'ready', 'completed', 'cancelled'])],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $order->update(['status' => $validated['status']]);
        $order->histories()->create([
            'status' => $validated['status'],
            'remark' => $validated['remark'] ?? null,
            'operator_id' => $request->user()->id,
        ]);

        return $this->success($order->refresh(), 'Order status updated successfully');
    }
}

