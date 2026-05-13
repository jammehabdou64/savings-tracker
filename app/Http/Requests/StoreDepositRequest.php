<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->id === $this->route('goal')->user_id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Deposit amount must be greater than $0.',
        ];
    }
}
