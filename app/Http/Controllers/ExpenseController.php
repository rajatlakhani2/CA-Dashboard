<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('user');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('from_date')) {
            $query->where('expense_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('expense_date', '<=', $request->to_date);
        }

        $expenses = $query->latest('expense_date')->paginate(20);
        $categories = Expense::categories();

        // Monthly summary
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $totalMonthly = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount');
        $categorySummary = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get(); // Changed to get() to provide a collection for collect() in view

        return view('expenses.index', compact('expenses', 'categories', 'totalMonthly', 'categorySummary'));
    }

    public function create()
    {
        $categories = Expense::categories();
        return view('expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'payment_mode' => 'required|in:Cash,UPI,Bank Transfer,Cheque,Credit Card',
            'vendor' => 'nullable|string|max:255',
            'is_recurring' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        Expense::create(array_merge($request->all(), ['user_id' => auth()->id()]));

        return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully.');
    }

    public function edit(Expense $expense)
    {
        $categories = Expense::categories();
        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'payment_mode' => 'required|in:Cash,UPI,Bank Transfer,Cheque,Credit Card',
        ]);

        $expense->update($request->all());
        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }
}
