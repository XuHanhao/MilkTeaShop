<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return $this->success($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOwner($request, $order);

        return $this->success($order->load(['items', 'histories', 'payments']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delivery_type' => ['required', Rule::in(['pickup', 'delivery'])],
            'delivery_address' => ['nullable', 'array'],
            'notes' => ['nullable', 'array'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.selected_options' => ['nullable', 'array'],
            'pay_with_balance' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        $order = DB::transaction(function () use ($validated, $request, $user) {
            $subtotal = 0;
            $itemsPayload = [];

            foreach ($validated['items'] as $item) {
                /** @var Product $product */
                $product = Product::query()
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Product {$product->name} has insufficient stock"],
                    ]);
                }

                $unitPrice = (float) $product->base_price;
                if (! empty($item['selected_options'])) {
                    foreach ($item['selected_options'] as $optionValueId) {
                        $option = $product->optionValues()->find($optionValueId);
                        if ($option) {
                            $unitPrice += (float) $option->extra_price;
                        }
                    }
                }

                $lineTotal = $unitPrice * $item['quantity'];
                $subtotal += $lineTotal;

                $itemsPayload[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => $product->image,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'selected_options' => $item['selected_options'] ?? [],
                ];

                $product->decrement('stock', $item['quantity']);
            }

            $deliveryFee = $validated['delivery_type'] === 'delivery' ? 5 : 0;
            $order = Order::create([
                'code' => 'NO'.Str::upper(Str::random(8)),
                'user_id' => $user->id,
                'status' => 'pending',
                'delivery_type' => $validated['delivery_type'],
                'pay_status' => 'unpaid',
                'channel' => 'web',
                'subtotal_amount' => $subtotal,
                'discount_amount' => 0,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $subtotal + $deliveryFee,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($itemsPayload as $payload) {
                $order->items()->create($payload);
            }

            $order->histories()->create([
                'status' => 'pending',
                'remark' => 'Customer submitted order',
            ]);

            if ($request->boolean('pay_with_balance')) {
                $total = (float) $order->total_amount;
                if ((float) $user->balance < $total) {
                    throw ValidationException::withMessages([
                        'balance' => ['Insufficient balance to complete payment'],
                    ]);
                }

                $user->decrement('balance', $total);
                $order->update([
                    'pay_status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            return $order;
        });

        return $this->success($order->load('items'), 'Order created, please pay as soon as possible');
    }

    protected function authorizeOwner(Request $request, Order $order): void
    {
        abort_if($order->user_id !== $request->user()->id, 403, 'Unauthorized to access this order');
    }
}

