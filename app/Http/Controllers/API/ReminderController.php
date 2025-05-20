<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $query = Reminder::where('user_id', $request->user()->id)
            ->with(['category', 'transaction']);

        // Filter by completion status
        if ($request->has('is_completed')) {
            $query->where('is_completed', $request->boolean('is_completed'));
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('due_date', [$request->start_date, $request->end_date]);
        }

        // Filter upcoming reminders
        if ($request->boolean('upcoming')) {
            $query->where('due_date', '>=', Carbon::now())
                ->where('is_completed', false);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $reminders = $query->orderBy('due_date', 'asc')->paginate(15);

        return response()->json([
            'status' => true,
            'data' => $reminders
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'required|date|after_or_equal:today',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $reminder = Reminder::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'category_id' => $request->category_id,
            'is_completed' => false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Reminder created successfully',
            'data' => $reminder->load(['category', 'transaction'])
        ], 201);
    }

    public function show(Reminder $reminder)
    {
        if ($reminder->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $reminder->load(['category', 'transaction'])
        ]);
    }

    public function update(Request $request, Reminder $reminder)
    {
        if ($reminder->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'required|date',
            'category_id' => 'required|exists:categories,id',
            'is_completed' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $reminder->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Reminder updated successfully',
            'data' => $reminder->load(['category', 'transaction'])
        ]);
    }

    public function destroy(Reminder $reminder)
    {
        if ($reminder->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $reminder->delete();

        return response()->json([
            'status' => true,
            'message' => 'Reminder deleted successfully'
        ]);
    }

    public function markAsComplete(Reminder $reminder)
    {
        if ($reminder->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $reminder->update(['is_completed' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Reminder marked as completed',
            'data' => $reminder->load(['category', 'transaction'])
        ]);
    }

    public function upcoming()
    {
        $upcomingReminders = Reminder::where('user_id', auth()->id())
            ->where('due_date', '>=', Carbon::now())
            ->where('is_completed', false)
            ->orderBy('due_date', 'asc')
            ->with(['category', 'transaction'])
            ->take(10)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $upcomingReminders
        ]);
    }
}
