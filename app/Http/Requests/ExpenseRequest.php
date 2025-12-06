<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'uuid'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
            'created_at' => ['sometimes', 'date'],
        ];
    }
}
