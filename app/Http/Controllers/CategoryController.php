<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\CategoryResource;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            Log::info('Attempting to fetch categories');
            
            $categories = Category::with('creator')->get();
            
            if ($categories->isEmpty()) {
                Log::info('No categories found in database');
                return response()->json([
                    'data' => [],
                    'message' => 'No categories found'
                ]);
            }
            
            return response()->json([
                'data' => CategoryResource::collection($categories),
                'message' => 'Categories retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching categories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to retrieve categories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function products(Category $category)
    {
        try {
            $products = $category->products()->with(['creator', 'category'])->get();
            
            return response()->json([
                'data' => $products,
                'message' => 'Products retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching category products: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve products'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Category creation attempt', [
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            if (Gate::denies('create-categories')) {
                Log::warning('Authorization failed for category creation');
                return response()->json([
                    'message' => 'Unauthorized action'
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
            ]);

            $category = Category::create([
                'name' => $validated['name'],
                'created_by' => auth()->id()
            ]);

            Log::info('Category created successfully', ['category_id' => $category->id]);

            return response()->json([
                'data' => new CategoryResource($category),
                'message' => 'Category created successfully'
            ], Response::HTTP_CREATED);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for category creation', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error creating category', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Failed to create category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Category $category)
    {
        try {
            $category->load(['creator', 'products.creator', 'products.category']);
            return response()->json([
                'data' => new CategoryResource($category),
                'message' => 'Category retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching category: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve category'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, Category $category)
    {
        try {
            if (Gate::denies('update-categories')) {
                return response()->json([
                    'message' => 'Unauthorized action'
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,'.$category->id,
            ]);

            $category->update($validated);

            return response()->json([
                'data' => new CategoryResource($category),
                'message' => 'Category updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update category'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Category $category)
    {
        try {
            if (Gate::denies('delete-categories')) {
                return response()->json([
                    'message' => 'Unauthorized action'
                ], Response::HTTP_FORBIDDEN);
            }

            $category->update([
                'deleted_by' => auth()->id(),
                'deleted_date' => now()
            ]);
            
            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully'
            ], Response::HTTP_NO_CONTENT);

        } catch (\Exception $e) {
            Log::error('Error deleting category: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete category'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}