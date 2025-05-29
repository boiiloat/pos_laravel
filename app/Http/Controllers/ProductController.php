<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index()
    {
        try {
            Log::info('Attempting to fetch products');
            
            // First, try without relationships
            $productsCount = Product::count();
            Log::info('Total products count: ' . $productsCount);
            
            if ($productsCount === 0) {
                Log::info('No products found in database');
                return response()->json([
                    'data' => [],
                    'message' => 'No products found'
                ]);
            }
            
            // Try to get products without relationships first
            $productsWithoutRelations = Product::all();
            Log::info('Products without relations fetched successfully', [
                'count' => $productsWithoutRelations->count()
            ]);
            
            // Now try with relationships
            $products = Product::with(['category', 'creator'])->get();
            Log::info('Products with relations fetched successfully', [
                'count' => $products->count()
            ]);
            
            return response()->json([
                'data' => $products,
                'message' => 'Products retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'message' => 'Failed to retrieve products',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function indexSimple()
    {
        try {
            $products = Product::all();
            
            return response()->json([
                'data' => $products,
                'message' => 'Products retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in simple index: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to retrieve products',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Product creation attempt', [
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'user_role' => auth()->user()?->role_id ?? 'no user'
            ]);

            if (Gate::denies('create-products')) {
                Log::warning('Authorization failed for product creation', [
                    'user_id' => auth()->id(),
                    'user_role' => auth()->user()?->role_id
                ]);
                return response()->json([
                    'message' => 'Unauthorized action'
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
                'image' => 'nullable|image|max:2048'
            ]);

            Log::info('Validation passed', ['validated_data' => $validated]);

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                Log::info('Product image uploaded', ['path' => $imagePath]);
            }

            $product = Product::create([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'category_id' => $validated['category_id'],
                'image' => $imagePath,
                'created_by' => auth()->id()
            ]);

            Log::info('Product created successfully', ['product_id' => $product->id]);

            return response()->json([
                'data' => $product,
                'message' => 'Product created successfully'
            ], Response::HTTP_CREATED);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for product creation', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error creating product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);
            return response()->json([
                'message' => 'Failed to create product',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Product $product)
    {
        try {
            return response()->json([
                'data' => $product->load(['category', 'creator']),
                'message' => 'Product retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching product: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve product'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, Product $product)
    {
        try {
            if (Gate::denies('update-products')) {
                return response()->json([
                    'message' => 'Unauthorized action'
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
                'image' => 'nullable|image|max:2048'
            ]);

            $imagePath = $product->image;
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                    Log::info('Old product image deleted', ['path' => $product->image]);
                }
                $imagePath = $request->file('image')->store('products', 'public');
                Log::info('New product image uploaded', ['path' => $imagePath]);
            }

            $product->update([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'category_id' => $validated['category_id'],
                'image' => $imagePath
            ]);

            return response()->json([
                'data' => $product,
                'message' => 'Product updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update product'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

public function destroy(Product $product)
{
    try {
        if (Gate::denies('delete-products')) {
            return response()->json([
                'message' => 'Unauthorized action'
            ], Response::HTTP_FORBIDDEN);
        }

        // Delete the product image if it exists
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
            Log::info('Product image deleted during deletion', ['path' => $product->image]);
        }

        // Update the record with deletion info before deleting
        $product->update([
            'deleted_by' => auth()->id(),
            'deleted_date' => now()
        ]);

        // Actually delete the record from database
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ], Response::HTTP_NO_CONTENT);

    } catch (\Exception $e) {
        Log::error('Error deleting product: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to delete product'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

}