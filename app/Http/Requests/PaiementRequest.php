<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ResponseMessage;
use App\Enums\TransactionLimits;

class PaiementRequest extends FormRequest
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
            'code_marchand' => 'nullable|string|exists:comptes,code_marchand',
            'telephone_marchand' => 'nullable|string|exists:users,telephone',
            'note' => 'nullable|string|max:' . TransactionLimits::MAX_NOTE_LENGTH->value,
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $codeMarchand = $this->input('code_marchand');
            $telephoneMarchand = $this->input('telephone_marchand');

            // Check that at least one merchant identifier is provided
            if (empty($codeMarchand) && empty($telephoneMarchand)) {
                $validator->errors()->add('merchant', ResponseMessage::PROVIDE_MERCHANT_CODE_OR_PHONE->value);
            }

            // Check that not both merchant identifiers are provided
            if (!empty($codeMarchand) && !empty($telephoneMarchand)) {
                $validator->errors()->add('merchant', ResponseMessage::PROVIDE_ONLY_ONE_MERCHANT_IDENTIFIER->value);
            }
        });
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
            'code_marchand.exists' => ResponseMessage::CODE_MARCHAND_EXISTS->value,
            'telephone_marchand.exists' => ResponseMessage::TELEPHONE_MARCHAND_EXISTS->value,
            'note.max' => ResponseMessage::NOTE_MAX_LENGTH->value,
        ];
    }
}
