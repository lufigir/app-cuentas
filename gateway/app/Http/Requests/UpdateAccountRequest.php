<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:150'],
            'account_number' => ['sometimes', 'string', 'max:20'],
            'balance' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if (empty($this->validated())) {
                    $validator->errors()->add('account', 'No fields were provided to update.');
                }
            },
        ];
    }
}
