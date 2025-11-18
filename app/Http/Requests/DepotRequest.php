<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ResponseMessage;
use App\Enums\TransactionLimits;

class DepotRequest extends FormRequest
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
        return [
            'montant' => 'required|numeric|min:' . TransactionLimits::MIN_AMOUNT->value,
            'telephone_recepteur_id' => 'required|string|exists:users,telephone',
            'note' => 'nullable|string|max:' . TransactionLimits::MAX_NOTE_LENGTH->value,
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
            'telephone_recepteur_id.required' => ResponseMessage::TELEPHONE_RECEPTEUR_REQUIRED->value,
            'telephone_recepteur_id.exists' => ResponseMessage::TELEPHONE_RECEPTEUR_EXISTS->value,
            'note.max' => ResponseMessage::NOTE_MAX_LENGTH->value,
        ];
    }
}
