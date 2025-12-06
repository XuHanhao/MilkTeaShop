<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class UserController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();
        
        if ($request->has('keyword') && $request->string('keyword')) {
            $keyword = $request->string('keyword');
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }
        
        if ($request->has('type') && $request->string('type')) {
            $query->where('type', $request->string('type'));
        }
        
        $users = $query->with('roles')
            ->paginate($request->integer('per_page', 15));

        return $this->success($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'type' => ['required', Rule::in(['customer', 'staff', 'manager', 'admin'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'password' => ['required', 'string', 'min:6'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'type' => $validated['type'],
            'status' => $validated['status'],
            'password' => Hash::make($validated['password']),
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        } else {
            $user->assignRole($validated['type']);
        }

        return $this->success($user->load('roles'), 'Account created successfully');
    }

    public function show(User $user): JsonResponse
    {
        return $this->success($user->load('roles'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'type' => ['sometimes', Rule::in(['customer', 'staff', 'manager', 'admin'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
            'password' => ['nullable', 'string', 'min:6'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->fill($validated)->save();

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles'] ?? []);
        }

        return $this->success($user->load('roles'), 'Account updated successfully');
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->success(null, 'Account deleted successfully');
    }

    public function dashboard(): JsonResponse
    {
        $today = Carbon::today();
        $last7DaysStart = Carbon::today()->subDays(6);

        $todayRevenue = Order::query()
            ->where('pay_status', 'paid')
            ->whereDate('paid_at', $today)
            ->sum('total_amount');

        $todayOrders = Order::query()
            ->whereDate('created_at', $today)
            ->count();

        $inventoryTotal = Product::query()->sum('stock');

        $salesTrend = Order::query()
            ->selectRaw('DATE(paid_at) as date, SUM(total_amount) as amount')
            ->where('pay_status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$last7DaysStart->clone()->startOfDay(), Carbon::today()->endOfDay()])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'amount' => (float) $row->amount,
            ]);

        $ordersTrend = Order::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$last7DaysStart->clone()->startOfDay(), Carbon::today()->endOfDay()])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'count' => (int) $row->count,
            ]);

        $inventoryTop = Product::query()
            ->select('name', 'stock')
            ->orderByDesc('stock')
            ->limit(5)
            ->get()
            ->map(fn ($product) => [
                'name' => $product->name,
                'stock' => (int) $product->stock,
            ]);

        return $this->success([
            'users_total' => User::count(),
            'customers_total' => User::where('type', 'customer')->count(),
            'staff_total' => User::whereIn('type', ['staff', 'manager'])->count(),
            'today_revenue' => (float) $todayRevenue,
            'today_orders' => (int) $todayOrders,
            'inventory_total' => (int) $inventoryTotal,
            'sales_trend' => $salesTrend,
            'orders_trend' => $ordersTrend,
            'inventory_top' => $inventoryTop,
        ]);
    }

    /**
     * Adjust user balance (admin only).
     */
    public function adjustBalance(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric'],
        ]);

        $newBalance = (float) $user->balance + (float) $validated['amount'];
        if ($newBalance < 0) {
            throw ValidationException::withMessages([
                'amount' => ['Balance cannot be negative after adjustment'],
            ]);
        }

        $user->balance = $newBalance;
        $user->save();

        return $this->success([
            'balance' => (float) $user->balance,
        ], 'Balance adjusted successfully');
    }
}

