<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Member;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends BaseApiController
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            throw ValidationException::withMessages([
                'email' => ['Email or phone number must be provided'],
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'type' => 'customer',
        ]);
        $user->assignRole('customer');
        Member::create(['user_id' => $user->id]);

        $token = $user->createToken('api')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $user->load('roles'),
        ], 'Registration successful');
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'account' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $credentials['account'])
            ->orWhere('phone', $credentials['account'])
            ->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'account' => ['Invalid account or password'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('api-'.Str::uuid())->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $user->load('roles'),
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return $this->success(null, 'Logged out successfully');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->success($request->user()->load('roles'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $user->fill($validated)->save();

        return $this->success($user->refresh(), 'Profile updated successfully');
    }
}

