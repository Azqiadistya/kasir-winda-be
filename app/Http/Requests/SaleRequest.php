<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'uuid'],
            'paid' => ['required', 'integer', 'min:0'],
            'created_at' => ['sometimes', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'uuid'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'integer', 'min:0'],
        ];
    }
}
