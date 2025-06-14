<?php

// app/Http/Controllers/SalePaymentController.php
namespace App\Http\Controllers;

use App\Models\SalePayment;
use Illuminate\Http\Request;

class SalePaymentController extends Controller
{
    public function index()
    {
        return response()->json(
            SalePayment::with(['sale', 'paymentMethod', 'createdBy'])->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_amount' => 'required|numeric|min:0',
            'exchange_rate' => 'sometimes|numeric|min:0',
            'payment_method_name' => 'required|string|max:255',
            'sale_id' => 'required|exists:sales,id',
            'payment_method_id' => 'required|exists:payment_methods,id'
        ]);

        $payment = SalePayment::create([
            ...$validated,
            'created_by' => auth()->id(),
            'exchange_rate' => $validated['exchange_rate'] ?? 1
        ]);

        return response()->json($payment, 201);
    }

    public function show(SalePayment $salePayment)
    {
        return response()->json(
            $salePayment->load(['sale', 'paymentMethod', 'createdBy'])
        );
    }

    public function update(Request $request, SalePayment $salePayment)
    {
        $validated = $request->validate([
            'payment_amount' => 'sometimes|numeric|min:0',
            'exchange_rate' => 'sometimes|numeric|min:0',
            'payment_method_name' => 'sometimes|string|max:255'
        ]);

        $salePayment->update($validated);
        return response()->json($salePayment);
    }

    public function destroy(SalePayment $salePayment)
    {
        $salePayment->delete();
        return response()->json(null, 204);
    }
}
