<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            Log::info('Attempting to fetch categories');
            
            // First, try without relationships
            $categoriesCount = Category::count();
            Log::info('Total categories count: ' . $categoriesCount);
            
            if ($categoriesCount === 0) {
                Log::info('No categories found in database');
                return response()->json([
                    'data' => [],
                    'message' => 'No categories found'
                ]);
            }
            
            // Try to get categories without relationships first
            $categoriesWithoutRelations = Category::all();
            Log::info('Categories without relations fetched successfully', [
                'count' => $categoriesWithoutRelations->count()
            ]);
            
            // Now try with relationships
            $categories = Category::with('creator')->get();
            Log::info('Categories with creator relation fetched successfully', [
                'count' => $categories->count()
            ]);
            
            return response()->json([
                'data' => $categories,
                'message' => 'Categories retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching categories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'message' => 'Failed to retrieve categories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Alternative simple version without relationships
    public function indexSimple()
    {
        try {
            $categories = Category::all();
            
            return response()->json([
                'data' => $categories,
                'message' => 'Categories retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in simple index: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to retrieve categories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Rest of your methods remain the same...
    public function products(Category $category)
    {
        try {
            return response()->json([
                'data' => $category->products()->with('creator')->get(),
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
                'user_id' => auth()->id(),
                'user_role' => auth()->user()?->role_id ?? 'no user'
            ]);

            if (Gate::denies('create-categories')) {
                Log::warning('Authorization failed for category creation', [
                    'user_id' => auth()->id(),
                    'user_role' => auth()->user()?->role_id
                ]);
                return response()->json([
                    'message' => 'Unauthorized action'
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
            ]);

            Log::info('Validation passed', ['validated_data' => $validated]);

            $category = Category::create([
                'name' => $validated['name'],
                'created_by' => auth()->id()
            ]);

            Log::info('Category created successfully', ['category_id' => $category->id]);

            return response()->json([
                'data' => $category,
                'message' => 'Category created successfully'
            ], Response::HTTP_CREATED);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for category creation', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error creating category', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => auth()->id()
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
            return response()->json([
                'data' => $category->load(['creator', 'products']),
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
                'data' => $category,
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

        // Update the record with deletion info before deleting
        $category->update([
            'deleted_by' => auth()->id(),
            'deleted_date' => now()
        ]);
        
        // Actually delete the record from database
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