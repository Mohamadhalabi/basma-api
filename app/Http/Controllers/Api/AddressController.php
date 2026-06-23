<?php

namespace App\Http\Controllers\Api;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController
{
    /**
     * Get all addresses for logged-in customer
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user('sanctum');
        $addresses = $customer->addresses()->get();

        return response()->json($addresses);
    }

    /**
     * Create new address
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255', // e.g., "Home", "Office"
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        $customer = $request->user('sanctum');

        // If setting as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $address = $customer->addresses()->create($validated);

        return response()->json([
            'message' => 'Address created successfully',
            'address' => $address,
        ], 201);
    }

    /**
     * Update address
     */
    public function update(Request $request, Address $address): JsonResponse
    {
        // Verify address belongs to customer
        if ($address->customer_id !== $request->user('sanctum')->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'label' => 'string|max:255',
            'line1' => 'string|max:255',
            'line2' => 'nullable|string|max:255',
            'city' => 'string|max:255',
            'region' => 'string|max:255',
            'postal_code' => 'string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            $address->customer->addresses()->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json([
            'message' => 'Address updated successfully',
            'address' => $address,
        ]);
    }

    /**
     * Delete address
     */
    public function destroy(Request $request, Address $address): JsonResponse
    {
        // Verify address belongs to customer
        if ($address->customer_id !== $request->user('sanctum')->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully',
        ]);
    }
}
