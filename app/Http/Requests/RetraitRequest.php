<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ResponseMessage;

class RetraitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'montant' => 'required|numeric|min:' . \App\Enums\TransactionLimits::MIN_AMOUNT->value,
            'note' => 'nullable|string|max:' . \App\Enums\TransactionLimits::MAX_NOTE_LENGTH->value,
        ];

        // For fournisseurs, telephone_emetteur is required
        if (auth()->user() && auth()->user()->isFournisseur()) {
            $rules['telephone_emetteur'] = 'required|string|exists:users,telephone';
        } else {
            $rules['telephone_emetteur'] = 'nullable|string|exists:users,telephone';
        }

        return $rules;
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
            'telephone_emetteur.required' => ResponseMessage::PROVIDER_PHONE_REQUIRED->value,
            'telephone_emetteur.exists' => ResponseMessage::TELEPHONE_EMETTEUR_EXISTS->value,
            'note.max' => ResponseMessage::NOTE_MAX_LENGTH->value,
        ];
    }
}
