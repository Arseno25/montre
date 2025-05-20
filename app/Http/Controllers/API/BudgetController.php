<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $query = Budget::where('user_id', $request->user()->id)
            ->with(['category']);

        // Filter by active status
        if ($request->has('active')) {
            $now = Carbon::now();
            $query->where(function ($q) use ($now) {
                $q->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
            });
        }

        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $budgets = $query->latest()->paginate(10);

        // Calculate progress for each budget
        $budgets->getCollection()->transform(function ($budget) {
            $budget->spent = $budget->category
                ->transactions()
                ->where('type', 'expense')
                ->whereBetween('date', [$budget->start_date, $budget->end_date])
                ->sum('amount');

            $budget->remaining = max(0, $budget->amount - $budget->spent);
            $budget->progress = $budget->amount > 0 ?
                min(100, round(($budget->spent / $budget->amount) * 100, 2)) : 0;

            return $budget;
        });

        return response()->json([
            'status' => true,
            'data' => $budgets
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for overlapping budgets in the same category
        $overlapping = Budget::where('category_id', $request->category_id)
            ->where('user_id', $request->user()->id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
            })->exists();

        if ($overlapping) {
            return response()->json([
                'status' => false,
                'message' => 'A budget already exists for this category during the specified period'
            ], 422);
        }

        $budget = Budget::create([
            'user_id' => $request->user()->id,
            'amount' => $request->amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'category_id' => $request->category_id,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Budget created successfully',
            'data' => $budget->load('category')
        ], 201);
    }

    public function show(Budget $budget)
    {
        if ($budget->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Calculate budget statistics
        $spent = $budget->category
            ->transactions()
            ->where('type', 'expense')
            ->whereBetween('date', [$budget->start_date, $budget->end_date])
            ->sum('amount');

        $remaining = max(0, $budget->amount - $spent);
        $progress = $budget->amount > 0 ?
            min(100, round(($spent / $budget->amount) * 100, 2)) : 0;

        $budget->spent = $spent;
        $budget->remaining = $remaining;
        $budget->progress = $progress;

        // Get daily spending breakdown
        $dailySpending = $budget->category
            ->transactions()
            ->where('type', 'expense')
            ->whereBetween('date', [$budget->start_date, $budget->end_date])
            ->selectRaw('DATE(date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'budget' => $budget->load('category'),
                'daily_spending' => $dailySpending
            ]
        ]);
    }

    public function update(Request $request, Budget $budget)
    {
        if ($budget->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for overlapping budgets in the same category (excluding current budget)
        $overlapping = Budget::where('category_id', $request->category_id)
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $budget->id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
            })->exists();

        if ($overlapping) {
            return response()->json([
                'status' => false,
                'message' => 'A budget already exists for this category during the specified period'
            ], 422);
        }

        $budget->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Budget updated successfully',
            'data' => $budget->load('category')
        ]);
    }

    public function destroy(Budget $budget)
    {
        if ($budget->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $budget->delete();

        return response()->json([
            'status' => true,
            'message' => 'Budget deleted successfully'
        ]);
    }
}
