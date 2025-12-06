<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tables = ['categories', 'products', 'sales', 'sale_items', 'expenses', 'store_settings', 'users'];

        $tableRules = [];

        foreach ($tables as $table) {
            $tableRules["changes.$table"] = ['array'];
            $tableRules["changes.$table.*.op"] = ['required', Rule::in(['upsert', 'delete'])];
            $tableRules["changes.$table.*.id"] = ['nullable', 'uuid'];
            $tableRules["changes.$table.*.updated_at"] = ['required', 'date'];
        }

        return array_merge([
            'clientTime' => ['required', 'date'],
        ], $tableRules);
    }
}
