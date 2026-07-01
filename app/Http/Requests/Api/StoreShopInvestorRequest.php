<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopInvestorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'user_id' => ['nullable', 'exists:users,id'],
            'payout_type' => ['required', Rule::in(['percentage', 'fixed_amount'])],
            'payout_value' => [
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->input('payout_type') === 'percentage' && ((float) $value < 0 || (float) $value > 100)) {
                        $fail('The percentage must be between 0 and 100.');
                    }
                },
            ],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
