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

        // Filter by period
        if ($request->has('period')) {
            $query->where('period', $request->period);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $budgets = $query->latest()->paginate(10);

        // Calculate statistics for each budget
        $budgets->getCollection()->transform(function ($budget) {
            $spent = $budget->category
                ->transactions()
                ->whereBetween('transaction_date', [$budget->start_date, $budget->end_date])
                ->where('type', 'expense')
                ->sum('amount');

            $budget->statistics = [
                'spent' => $spent,
                'remaining' => max(0, $budget->amount - $spent),
                'progress_percentage' => $budget->amount > 0 ?
                    min(100, round(($spent / $budget->amount) * 100, 2)) : 0
            ];

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
            'category_id' => 'required|exists:categories,id',
            'period' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'amount' => 'required|numeric|min:0',
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
            'category_id' => $request->category_id,
            'period' => $request->period,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'amount' => $request->amount,
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
            ->whereBetween('transaction_date', [$budget->start_date, $budget->end_date])
            ->where('type', 'expense')
            ->sum('amount');

        $statistics = [
            'spent' => $spent,
            'remaining' => max(0, $budget->amount - $spent),
            'progress_percentage' => $budget->amount > 0 ?
                min(100, round(($spent / $budget->amount) * 100, 2)) : 0
        ];

        // Get daily spending breakdown
        $dailySpending = $budget->category
            ->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$budget->start_date, $budget->end_date])
            ->selectRaw('DATE(transaction_date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'budget' => $budget->load('category'),
                'statistics' => $statistics,
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
            'category_id' => 'required|exists:categories,id',
            'period' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'amount' => 'required|numeric|min:0',
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
