<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Expense::class, 'expense');
    }

    public function index(Request $request)
    {
        $expenses = Expense::query()
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return ExpenseResource::collection($expenses);
    }

    public function store(ExpenseRequest $request)
    {
        $expense = Expense::create($request->validated());

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function show(Expense $expense)
    {
        return new ExpenseResource($expense);
    }

    public function update(ExpenseRequest $request, Expense $expense)
    {
        $expense->update($request->validated());

        return new ExpenseResource($expense);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return response()->json(['data' => null], 204);
    }
}
