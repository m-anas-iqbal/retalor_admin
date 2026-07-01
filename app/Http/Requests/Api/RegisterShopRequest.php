<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterShopRequest extends FormRequest
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
            'shop.name' => ['required', 'string', 'max:255'],
            'shop.slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii', 'unique:shops,slug'],
            'shop.email' => ['nullable', 'email', 'max:255'],
            'shop.phone' => ['nullable', 'string', 'max:30'],
            'shop.address' => ['nullable', 'string', 'max:255'],
            'shop.city' => ['nullable', 'string', 'max:100'],
            'owner.name' => ['required', 'string', 'max:255'],
            'owner.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'owner.password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan_id' => ['required', 'exists:plans,id'],
            'payment_method' => ['required', Rule::in(['cash', 'ibft'])],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string', 'max:1000'],
            'payment_screenshot' => ['nullable', 'image', 'max:5120', 'required_if:payment_method,ibft'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
