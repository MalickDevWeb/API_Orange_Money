<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ResponseMessage;

class AchatVirtuelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
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
            'note' => 'nullable|string|max:' . \App\Enums\TransactionLimits::MAX_NOTE_LENGTH->value,
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
            'note.max' => ResponseMessage::NOTE_MAX_LENGTH->value,
        ];
    }
}
