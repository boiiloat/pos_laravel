<?php

namespace App\Http\Controllers;

use App\Models\SaleProduct;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleProductController extends Controller
{
    /**
     * Display a listing of sale products with pagination
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $saleProducts = SaleProduct::with([
                    'sale:id,invoice_number,status',
                    'product:id,name,price',
                    'createdBy:id,name'
                ])
                ->latest()
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Sale products retrieved successfully',
                'data' => $saleProducts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sale products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created sale product
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate request
            $validated = $request->validate([
                'sale_id' => 'required|exists:sales,id',
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
                'is_free' => 'sometimes|boolean'
            ]);

            // Get product details
            $product = Product::findOrFail($validated['product_id']);
            $sale = Sale::findOrFail($validated['sale_id']);

            // Create sale product
            $saleProduct = SaleProduct::create([
                'sale_id' => $validated['sale_id'],
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'price' => $validated['price'],
                'is_free' => $validated['is_free'] ?? false,
                'product_name' => $product->name,
                'image' => $product->image,
                'created_by' => Auth::id()
            ]);

            // Update sale totals
            $this->updateSaleTotals($sale);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale product created successfully',
                'data' => $saleProduct->load([
                    'sale:id,invoice_number',
                    'product:id,name',
                    'createdBy:id,name'
                ])
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
                'message' => 'Failed to create sale product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk store sale products
     */
    public function storeBulk(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'sale_id' => 'required|exists:sales,id',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.is_free' => 'sometimes|boolean'
            ]);

            $sale = Sale::findOrFail($request->sale_id);
            $createdProducts = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                $saleProduct = SaleProduct::create([
                    'sale_id' => $request->sale_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'is_free' => $item['is_free'] ?? false,
                    'product_name' => $product->name,
                    'image' => $product->image,
                    'created_by' => Auth::id()
                ]);

                $createdProducts[] = $saleProduct;
            }

            // Update sale totals
            $this->updateSaleTotals($sale);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale products created successfully',
                'data' => $createdProducts
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sale products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified sale product
     */
    public function show($id)
    {
        try {
            $saleProduct = SaleProduct::with([
                'sale:id,invoice_number',
                'product:id,name',
                'createdBy:id,name'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Sale product retrieved successfully',
                'data' => $saleProduct
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sale product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified sale product
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'quantity' => 'sometimes|integer|min:1',
                'price' => 'sometimes|numeric|min:0',
                'is_free' => 'sometimes|boolean'
            ]);

            $saleProduct = SaleProduct::findOrFail($id);
            $saleProduct->update($validated);

            // Update sale totals if quantity or price changed
            if (isset($validated['quantity']) ){
                $this->updateSaleTotals($saleProduct->sale);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale product updated successfully',
                'data' => $saleProduct->load([
                    'sale:id,invoice_number',
                    'product:id,name',
                    'createdBy:id,name'
                ])
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
                'message' => 'Failed to update sale product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified sale product
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $saleProduct = SaleProduct::findOrFail($id);
            $sale = $saleProduct->sale;
            $saleProduct->delete();

            // Update sale totals
            $this->updateSaleTotals($sale);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale product deleted successfully'
            ], 204);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sale product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to update sale totals
     */
    private function updateSaleTotals(Sale $sale)
    {
        $subTotal = SaleProduct::where('sale_id', $sale->id)
            ->sum(DB::raw('price * quantity'));

        $sale->update([
            'sub_total' => $subTotal,
            'grand_total' => $subTotal - $sale->discount
        ]);
    }
}