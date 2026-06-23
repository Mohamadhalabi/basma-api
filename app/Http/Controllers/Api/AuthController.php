<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController
{
    /**
     * Customer registration
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:customers,email',
                'phone' => 'nullable|unique:customers,phone',
                'password' => 'required|string|min:8|confirmed',
                'company_name' => 'nullable|string',
                'vat_number' => 'nullable|string',
            ]);

            // At least email or phone must be provided
            if (empty($validated['email']) && empty($validated['phone'])) {
                return response()->json([
                    'message' => 'Either email or phone must be provided',
                ], 422);
            }

            $customer = Customer::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'company_name' => $validated['company_name'] ?? null,
                'vat_number' => $validated['vat_number'] ?? null,
                'is_active' => true,
            ]);

            $token = $customer->createToken('basma-auth')->plainTextToken;

            return response()->json([
                'message' => 'Registration successful',
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'code' => $customer->code,
                    'company_name' => $customer->company_name,
                ],
                'token' => $token,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Customer login (email or phone + password)
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => 'required|string', // email or phone
            'password' => 'required|string',
        ]);

        // Find customer by email or phone
        $customer = Customer::where('email', $validated['login'])
            ->orWhere('phone', $validated['login'])
            ->first();

        // Check if customer exists and password is correct
        if (!$customer || !Hash::check($validated['password'], $customer->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if customer is active
        if (!$customer->is_active) {
            return response()->json([
                'message' => 'Account is inactive',
            ], 403);
        }

        $token = $customer->createToken('basma-auth')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'code' => $customer->code,
                'company_name' => $customer->company_name,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Customer logout (delete token)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user('sanctum')->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get current logged-in customer profile
     */
    public function profile(Request $request): JsonResponse
    {
        $customer = $request->user('sanctum');

        if (!$customer) {
            return response()->json([
                'message' => 'Not authenticated',
            ], 401);
        }

        $customer->load('addresses', 'priceLists', 'orders');

        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'code' => $customer->code,
            'company_name' => $customer->company_name,
            'vat_number' => $customer->vat_number,
            'addresses' => $customer->addresses,
            'active_price_list' => $customer->activePriceList(),
        ]);
    }
}
