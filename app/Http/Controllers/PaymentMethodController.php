<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of payment methods
     */
    public function index()
    {
        return response()->json(
            PaymentMethod::with([
                'createdBy:id,fullname',
                'deletedBy:id,fullname'
            ])->get()
        );
    }

    /**
     * Store a newly created payment method
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method_name' => 'required|string|max:255'
        ]);

        $paymentMethod = PaymentMethod::create([
            'payment_method_name' => $validated['payment_method_name'],
            'created_by' => auth()->id()
        ]);

        return response()->json(
            $paymentMethod->load([
                'createdBy:id,fullname',
                'deletedBy:id,fullname'
            ]),
            201
        );
    }

    /**
     * Display the specified payment method
     */
    public function show(PaymentMethod $paymentMethod)
    {
        return response()->json(
            $paymentMethod->load([
                'createdBy:id,fullname',
                'deletedBy:id,fullname'
            ])
        );
    }

    /**
     * Update the specified payment method
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'payment_method_name' => 'sometimes|required|string|max:255',
            'deleted_by' => 'sometimes|nullable|exists:users,id'
        ]);

        $paymentMethod->update($validated);

        return response()->json(
            $paymentMethod->load([
                'createdBy:id,fullname',
                'deletedBy:id,fullname'
            ])
        );
    }

    /**
     * Remove the specified payment method
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();
        return response()->json(null, 204);
    }
}