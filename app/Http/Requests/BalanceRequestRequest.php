<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ResponseMessage;

class BalanceRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isFournisseur();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'montant' => 'required|numeric|min:' . \App\Enums\TransactionLimits::MIN_AMOUNT->value,
            'telephone_admin' => 'required|string|exists:users,telephone',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'montant.required' => ResponseMessage::AMOUNT_REQUIRED->value,
            'montant.numeric' => ResponseMessage::AMOUNT_NUMERIC->value,
            'montant.min' => ResponseMessage::AMOUNT_MIN->value,
            'telephone_admin.required' => ResponseMessage::TELEPHONE_ADMIN_REQUIRED->value,
            'telephone_admin.exists' => ResponseMessage::TELEPHONE_ADMIN_EXISTS->value,
        ];
    }
}
