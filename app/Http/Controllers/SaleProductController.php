<?php

namespace App\Http\Controllers;

use App\Models\SaleProduct;
use Illuminate\Http\Request;

class SaleProductController extends Controller
{
    /**
     * Display a listing of sale products
     */
    public function index()
    {
        return response()->json(
            SaleProduct::with([
                'sale:id,invoice_number',
                'product:id,name',
                'createdBy:id,fullname'
            ])->get()
        );
    }

    /**
     * Store a newly created sale product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'is_free' => 'sometimes|boolean'
        ]);

        $saleProduct = SaleProduct::create([
            ...$validated,
            'created_by' => auth()->id()
        ]);

        return response()->json(
            $saleProduct->load([
                'sale:id,invoice_number',
                'product:id,name',
                'createdBy:id,fullname'
            ]),
            201
        );
    }

    /**
     * Display the specified sale product
     */
    public function show(SaleProduct $saleProduct)
    {
        return response()->json(
            $saleProduct->load([
                'sale:id,invoice_number',
                'product:id,name',
                'createdBy:id,fullname'
            ])
        );
    }

    /**
     * Update the specified sale product
     */
    public function update(Request $request, SaleProduct $saleProduct)
    {
        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0',
            'is_free' => 'sometimes|boolean'
        ]);

        $saleProduct->update($validated);

        return response()->json(
            $saleProduct->load([
                'sale:id,invoice_number',
                'product:id,name',
                'createdBy:id,fullname'
            ])
        );
    }

    /**
     * Remove the specified sale product
     */
    public function destroy(SaleProduct $saleProduct)
    {
        $saleProduct->delete();
        return response()->json(null, 204);
    }
}