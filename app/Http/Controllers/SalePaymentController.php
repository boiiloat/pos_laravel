<?php

namespace App\Http\Controllers;

use App\Models\SalePayment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalePaymentController extends Controller
{
    /**
     * Display a listing of sale payments
     */
    public function index()
    {
        try {
            $payments = SalePayment::with([
                'sale:id,invoice_number',
                'paymentMethod:id,payment_method_name',
                'createdBy:id,fullname'
            ])->get();

            return response()->json([
                'success' => true,
                'message' => 'Sale payments retrieved successfully',
                'data' => $payments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sale payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created sale payment
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'payment_amount' => 'required|numeric|min:0.01',
                'exchange_rate' => 'required|numeric|min:0.0001',
                'payment_method_name' => 'required|string|max:255',
                'sale_id' => 'required|integer|exists:sales,id',
                'payment_method_id' => 'required|integer|exists:payment_methods,id',
                'created_by' => 'required|integer|exists:users,id'
            ]);

            // Create the payment
            $payment = SalePayment::create($validated);

            // Update sale payment status
            $sale = Sale::findOrFail($validated['sale_id']);
            $this->updateSalePaymentStatus($sale);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale payment created successfully',
                'data' => $payment->load(['sale', 'paymentMethod', 'createdBy'])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sale payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified sale payment
     */
    public function show(SalePayment $salePayment)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Sale payment retrieved successfully',
                'data' => $salePayment->load([
                    'sale:id,invoice_number',
                    'paymentMethod:id,payment_method_name',
                    'createdBy:id,fullname'
                ])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sale payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified sale payment
     */
    public function update(Request $request, SalePayment $salePayment)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'payment_amount' => 'sometimes|numeric|min:0.01',
                'exchange_rate' => 'sometimes|numeric|min:0.0001',
                'payment_method_name' => 'sometimes|string|max:255'
            ]);

            $salePayment->update($validated);

            // Update sale payment status if payment amount changed
            if (array_key_exists('payment_amount', $validated)) {
                $sale = $salePayment->sale()->first();
                $this->updateSalePaymentStatus($sale);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale payment updated successfully',
                'data' => $salePayment->load(['sale', 'paymentMethod', 'createdBy'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sale payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified sale payment
     */
    public function destroy(SalePayment $salePayment)
    {
        DB::beginTransaction();

        try {
            // Get the sale first before deleting
            $sale = $salePayment->sale()->first();
            $salePayment->delete();

            // Update sale payment status after deletion
            $this->updateSalePaymentStatus($sale);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale payment deleted successfully'
            ], 204);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sale payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update sale payment status based on payments
     */
    protected function updateSalePaymentStatus(?Sale $sale)
    {
        if (!$sale) {
            return;
        }

        $totalPaid = $sale->payments()->sum('payment_amount');
        $grandTotal = $sale->grand_total;

        $newStatus = $totalPaid >= $grandTotal ? 'completed' : 'pending';
        $isPaid = $totalPaid >= $grandTotal;

        $sale->update([
            'is_paid' => $isPaid,
            'status' => $newStatus
        ]);
    }
}