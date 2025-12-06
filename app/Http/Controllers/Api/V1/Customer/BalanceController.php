<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BalanceController extends BaseApiController
{
    public function show(Request $request): JsonResponse
    {
        return $this->success([
            'balance' => (float) $request->user()->balance,
        ]);
    }

    /**
     * Simple recharge interface (for demo purposes), actual projects should integrate third-party payment.
     */
    public function recharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();
        $user->increment('balance', $validated['amount']);

        return $this->success([
            'balance' => (float) $user->refresh()->balance,
        ], 'Balance recharged successfully');
    }
}


