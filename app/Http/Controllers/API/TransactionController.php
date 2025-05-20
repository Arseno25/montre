<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
  public function index(Request $request)
  {
    $query = Transaction::where('user_id', $request->user()->id)
      ->with(['category']);

    // Filter by date range if provided
    if ($request->has('start_date') && $request->has('end_date')) {
      $query->whereBetween('date', [$request->start_date, $request->end_date]);
    }

    // Filter by category if provided
    if ($request->has('category_id')) {
      $query->where('category_id', $request->category_id);
    }

    // Filter by type if provided
    if ($request->has('type')) {
      $query->where('type', $request->type);
    }

    $transactions = $query->latest()->paginate(15);

    return response()->json([
      'status' => true,
      'data' => $transactions
    ]);
  }

  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'amount' => 'required|numeric|min:0',
      'type' => 'required|in:income,expense',
      'date' => 'required|date',
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

    $transaction = Transaction::create([
      'user_id' => $request->user()->id,
      'amount' => $request->amount,
      'type' => $request->type,
      'date' => $request->date,
      'category_id' => $request->category_id,
      'description' => $request->description,
    ]);

    return response()->json([
      'status' => true,
      'message' => 'Transaction created successfully',
      'data' => $transaction->load('category')
    ], 201);
  }

  public function show(Transaction $transaction)
  {
    if ($transaction->user_id !== auth()->id()) {
      return response()->json([
        'status' => false,
        'message' => 'Unauthorized access'
      ], 403);
    }

    return response()->json([
      'status' => true,
      'data' => $transaction->load('category')
    ]);
  }

  public function update(Request $request, Transaction $transaction)
  {
    if ($transaction->user_id !== auth()->id()) {
      return response()->json([
        'status' => false,
        'message' => 'Unauthorized access'
      ], 403);
    }

    $validator = Validator::make($request->all(), [
      'amount' => 'required|numeric|min:0',
      'type' => 'required|in:income,expense',
      'date' => 'required|date',
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

    $transaction->update($request->all());

    return response()->json([
      'status' => true,
      'message' => 'Transaction updated successfully',
      'data' => $transaction->load('category')
    ]);
  }

  public function destroy(Transaction $transaction)
  {
    if ($transaction->user_id !== auth()->id()) {
      return response()->json([
        'status' => false,
        'message' => 'Unauthorized access'
      ], 403);
    }

    $transaction->delete();

    return response()->json([
      'status' => true,
      'message' => 'Transaction deleted successfully'
    ]);
  }

  public function summary(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'start_date' => 'required|date',
      'end_date' => 'required|date|after:start_date',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => false,
        'message' => 'Validation Error',
        'errors' => $validator->errors()
      ], 422);
    }

    $summary = [
      'total_income' => Transaction::where('user_id', $request->user()->id)
        ->where('type', 'income')
        ->whereBetween('date', [$request->start_date, $request->end_date])
        ->sum('amount'),
      'total_expense' => Transaction::where('user_id', $request->user()->id)
        ->where('type', 'expense')
        ->whereBetween('date', [$request->start_date, $request->end_date])
        ->sum('amount'),
      'by_category' => Transaction::where('user_id', $request->user()->id)
        ->whereBetween('date', [$request->start_date, $request->end_date])
        ->with('category')
        ->get()
        ->groupBy('category.name')
        ->map(function ($transactions) {
          return [
            'total' => $transactions->sum('amount'),
            'count' => $transactions->count()
          ];
        })
    ];

    return response()->json([
      'status' => true,
      'data' => $summary
    ]);
  }
}
