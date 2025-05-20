<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
  public function index(Request $request)
  {
    $categories = Category::where('user_id', $request->user()->id)
      ->withCount(['transactions', 'budgets'])
      ->latest()
      ->get();

    return response()->json([
      'status' => true,
      'data' => $categories
    ]);
  }

  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'description' => 'nullable|string|max:1000',
      'color' => 'nullable|string|max:7',
      'icon' => 'nullable|string|max:50',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'message' => 'Validation Error',
        'errors' => $validator->errors()
      ], 422);
    }

    $category = Category::create([
      'user_id' => $request->user()->id,
      'name' => $request->name,
      'description' => $request->description,
      'color' => $request->color,
      'icon' => $request->icon,
    ]);

    return response()->json([
      'status' => true,
      'message' => 'Category created successfully',
      'data' => $category
    ], 201);
  }

  public function show(Category $category)
  {
    if ($category->user_id !== auth()->id()) {
      return response()->json([
        'status' => false,
        'message' => 'Unauthorized access'
      ], 403);
    }

    $category->loadCount(['transactions', 'budgets']);

    return response()->json([
      'status' => true,
      'data' => $category
    ]);
  }

  public function update(Request $request, Category $category)
  {
    if ($category->user_id !== auth()->id()) {
      return response()->json([
        'status' => false,
        'message' => 'Unauthorized access'
      ], 403);
    }

    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'description' => 'nullable|string|max:1000',
      'color' => 'nullable|string|max:7',
      'icon' => 'nullable|string|max:50',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'message' => 'Validation Error',
        'errors' => $validator->errors()
      ], 422);
    }

    $category->update($request->all());

    return response()->json([
      'status' => true,
      'message' => 'Category updated successfully',
      'data' => $category
    ]);
  }

  public function destroy(Category $category)
  {
    if ($category->user_id !== auth()->id()) {
      return response()->json([
        'status' => false,
        'message' => 'Unauthorized access'
      ], 403);
    }

    // Check if category has any associated transactions or budgets
    if ($category->transactions()->exists() || $category->budgets()->exists()) {
      return response()->json([
        'status' => false,
        'message' => 'Cannot delete category with associated transactions or budgets'
      ], 422);
    }

    $category->delete();

    return response()->json([
      'status' => true,
      'message' => 'Category deleted successfully'
    ]);
  }

  public function statistics(Request $request)
  {
    $stats = Category::where('user_id', $request->user()->id)
      ->withCount(['transactions'])
      ->withSum('transactions', 'amount')
      ->get()
      ->map(function ($category) {
        return [
          'id' => $category->id,
          'name' => $category->name,
          'transaction_count' => $category->transactions_count,
          'total_amount' => $category->transactions_sum_amount ?? 0,
        ];
      });

    return response()->json([
      'status' => true,
      'data' => $stats
    ]);
  }
}
