<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Sale::with(['createdBy', 'table', 'products'])->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'sometimes|unique:sales',
            'sub_total' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount' => 'nullable|numeric|min:0',
            'grand_total' => 'sometimes|numeric',
            'is_paid' => 'boolean',
            'status' => 'in:pending,completed,cancelled',
            'sale_date' => 'required|date',
            'table_id' => 'nullable|exists:tables,id',
            'created_by' => 'required|exists:users,id',
            'products' => 'sometimes|array',
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
            'products.*.price' => 'required_with:products|numeric|min:0',
            'products.*.is_free' => 'sometimes|boolean',
        ]);

        // Calculate grand_total if not provided
        if (!isset($validated['grand_total'])) {
            $validated['grand_total'] = $validated['sub_total'];

            if (isset($validated['discount_type']) && isset($validated['discount'])) {
                if ($validated['discount_type'] === 'fixed') {
                    $validated['grand_total'] -= $validated['discount'];
                } else {
                    $validated['grand_total'] -= ($validated['sub_total'] * $validated['discount'] / 100);
                }

                // Ensure grand_total doesn't go below 0
                $validated['grand_total'] = max(0, $validated['grand_total']);
            }
        }

        $sale = Sale::create($validated);

        // Add products if provided
        if (isset($validated['products'])) {
            foreach ($validated['products'] as $productData) {
                $product = Product::find($productData['product_id']);
                $this->addProductToSale($sale, $product, $productData);
            }
            $this->recalculateSaleTotals($sale);
        }

        return response()->json($sale->load('products'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        return response()->json($sale->load(['createdBy', 'table', 'products']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'invoice_number' => 'sometimes|unique:sales,invoice_number,' . $sale->id,
            'sub_total' => 'sometimes|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount' => 'nullable|numeric|min:0',
            'grand_total' => 'sometimes|numeric',
            'is_paid' => 'boolean',
            'status' => 'in:pending,completed,cancelled',
            'sale_date' => 'sometimes|date',
            'table_id' => 'nullable|exists:tables,id',
            'created_by' => 'sometimes|exists:users,id',
            'products' => 'sometimes|array',
            'products.*.id' => 'sometimes|exists:sale_products,id,sale_id,'.$sale->id,
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
            'products.*.price' => 'required_with:products|numeric|min:0',
            'products.*.is_free' => 'sometimes|boolean',
        ]);

        // Recalculate grand_total if sub_total, discount_type, or discount changed
        if (isset($validated['sub_total']) || isset($validated['discount_type']) || isset($validated['discount'])) {
            $subTotal = $validated['sub_total'] ?? $sale->sub_total;
            $discountType = $validated['discount_type'] ?? $sale->discount_type;
            $discount = $validated['discount'] ?? $sale->discount;

            $validated['grand_total'] = $subTotal;

            if ($discountType && $discount) {
                if ($discountType === 'fixed') {
                    $validated['grand_total'] -= $discount;
                } else {
                    $validated['grand_total'] -= ($subTotal * $discount / 100);
                }

                // Ensure grand_total doesn't go below 0
                $validated['grand_total'] = max(0, $validated['grand_total']);
            }
        }

        $sale->update($validated);

        // Handle product updates
        if (isset($validated['products'])) {
            $this->handleProductUpdates($sale, $validated['products']);
            $this->recalculateSaleTotals($sale);
        }

        return response()->json($sale->load('products'));
    }

    /**
     * Add product to sale
     */
    public function addProduct(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'is_free' => 'boolean',
            'created_by' => 'required|exists:users,id'
        ]);

        $product = Product::find($validated['product_id']);

        $saleProduct = $this->addProductToSale($sale, $product, $validated);
        $this->recalculateSaleTotals($sale);

        return response()->json($saleProduct, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        $sale->delete();
        return response()->json(null, 204);
    }

    /**
     * Helper method to add product to sale
     */
    protected function addProductToSale(Sale $sale, Product $product, array $data)
    {
        return $sale->products()->create([
            'product_id' => $product->id,
            'quantity' => $data['quantity'],
            'price' => $data['price'],
            'is_free' => $data['is_free'] ?? false,
            'product_name' => $product->name,
            'image' => $product->image,
            'created_by' => $data['created_by']
        ]);
    }

    /**
     * Handle product updates for a sale
     */
    protected function handleProductUpdates(Sale $sale, array $products)
    {
        $existingProductIds = [];
        
        foreach ($products as $productData) {
            if (isset($productData['id'])) {
                // Update existing product
                $sale->products()
                    ->where('id', $productData['id'])
                    ->update([
                        'quantity' => $productData['quantity'],
                        'price' => $productData['price'],
                        'is_free' => $productData['is_free'] ?? false
                    ]);
                $existingProductIds[] = $productData['id'];
            } else {
                // Add new product
                $product = Product::find($productData['product_id']);
                $this->addProductToSale($sale, $product, $productData);
            }
        }
        
        // Remove products not included in the request
        $sale->products()
            ->whereNotIn('id', $existingProductIds)
            ->delete();
    }

    /**
     * Recalculate sale totals based on products
     */
   protected function recalculateSaleTotals(Sale $sale)
{
    // Calculate subtotal at database level for better performance
    $subTotal = $sale->products()
        ->selectRaw('SUM(price * quantity) as total')
        ->value('total') ?? 0;

    $grandTotal = $subTotal;

    // Apply discount if exists
    if ($sale->discount_type && $sale->discount) {
        if ($sale->discount_type === 'fixed') {
            $grandTotal -= $sale->discount;
        } else {
            $grandTotal -= ($subTotal * $sale->discount / 100);
        }
        $grandTotal = max(0, $grandTotal);
    }

    $sale->update([
        'sub_total' => $subTotal,
        'grand_total' => $grandTotal
    ]);
}
}