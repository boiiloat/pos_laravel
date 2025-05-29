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
            $products = Product::with(['category', 'creator'])->get();

            $transformedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'category_name' => $product->category->name ?? null,
                    'creator_name' => $product->creator->fullname ?? $product->creator->name ?? null,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ];
            });

            return response()->json([
                'data' => $transformedProducts,
                'message' => 'Products retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Product fetch error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve products'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            if (Gate::denies('create-products')) {
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

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
            }

            $product = Product::create([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'category_id' => $validated['category_id'],
                'image' => $imagePath,
                'created_by' => auth()->id()
            ]);

            $product->load(['category', 'creator']);

            return response()->json([
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'category_name' => $product->category->name ?? null,
                    'creator_name' => $product->creator->fullname ?? $product->creator->name ?? null,
                    'created_at' => $product->created_at
                ],
                'message' => 'Product created successfully'
            ], Response::HTTP_CREATED);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Product creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create product'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Product $product)
    {
        try {
            $product->load(['category', 'creator']);

            return response()->json([
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'category_name' => $product->category->name ?? null,
                    'creator_name' => $product->creator->fullname ?? $product->creator->name ?? null,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ],
                'message' => 'Product retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Product fetch error: ' . $e->getMessage());
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
                'name' => 'sometimes|required|string|max:255',
                'price' => 'sometimes|required|numeric|min:0',
                'category_id' => 'sometimes|required|exists:categories,id',
                'image' => 'nullable|image|max:2048'
            ]);

            if ($request->hasFile('image')) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $validated['image'] = $request->file('image')->store('products', 'public');
            }

            $product->update($validated);
            $product->refresh()->load(['category', 'creator']);

            return response()->json([
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'category_name' => $product->category->name ?? null,
                    'creator_name' => $product->creator->fullname ?? $product->creator->name ?? null,
                    'updated_at' => $product->updated_at
                ],
                'message' => 'Product updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
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

            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $product->update([
                'deleted_by' => auth()->id(),
                'deleted_date' => now()
            ]);

            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully'
            ], Response::HTTP_NO_CONTENT);

        } catch (\Exception $e) {
            Log::error('Product deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete product'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
