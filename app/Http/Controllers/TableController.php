<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TableController extends Controller
{
    public function index()
    {
        try {
            $tables = Table::with(['creator'])->get()->map(function ($table) {
                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'created_at' => $table->created_at,
                    'updated_at' => $table->updated_at,
                    'created_by' => $table->creator ? $table->creator->fullname : null,
                    'created_by_id' => $table->created_by
                ];
            });

            return response()->json([
                'data' => $tables,
                'message' => 'Tables retrieved successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Table fetch error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve tables'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            if (Auth::user()->role_id !== 1) {
                return response()->json([
                    'message' => 'Unauthorized action'
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:tables,name',
            ]);

            $table = Table::create([
                'name' => $validated['name'],
                'created_by' => Auth::id()
            ]);

            $table->load('creator');

            return response()->json([
                'data' => [
                    'id' => $table->id,
                    'name' => $table->name,
                    'created_at' => $table->created_at,
                    'updated_at' => $table->updated_at,
                    'created_by' => $table->creator->fullname,
                    'created_by_id' => $table->created_by
                ],
                'message' => 'Table created successfully'
            ], Response::HTTP_CREATED);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Table creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create table'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Table $table)
    {
        try {
            $table->load('creator');

            return response()->json([
                'data' => [
                    'id' => $table->id,
                    'name' => $table->name,
                    'created_at' => $table->created_at,
                    'updated_at' => $table->updated_at,
                    'created_by' => $table->creator->fullname,
                    'created_by_id' => $table->created_by
                ],
                'message' => 'Table retrieved successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Table fetch error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve table'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, Table $table)
    {
        try {
            if (Auth::user()->role_id !== 1) {
                return response()->json([
                    'message' => 'Unauthorized action'
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:tables,name,' . $table->id,
            ]);

            $table->update($validated);
            $table->load('creator');

            return response()->json([
                'data' => [
                    'id' => $table->id,
                    'name' => $table->name,
                    'created_at' => $table->created_at,
                    'updated_at' => $table->updated_at,
                    'created_by' => $table->creator->fullname,
                    'created_by_id' => $table->created_by
                ],
                'message' => 'Table updated successfully'
            ], Response::HTTP_OK);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Table update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update table'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Table $table)
    {
        try {
            if (Auth::user()->role_id !== 1) {
                return response()->json([
                    'message' => 'Unauthorized action'
                ], Response::HTTP_FORBIDDEN);
            }

            $table->update([
                'deleted_by' => Auth::id(),
                'deleted_date' => now()
            ]);

            $table->delete();

            return response()->json([
                'message' => 'Table deleted successfully'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Table deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete table'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}