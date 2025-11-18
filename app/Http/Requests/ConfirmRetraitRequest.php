<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ResponseMessage;
use App\Enums\DefaultValues;

class ConfirmRetraitRequest extends FormRequest
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
            'confirmation_code' => 'required|string|size:' . DefaultValues::OTP_SIZE->value,
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'confirmation_code.required' => ResponseMessage::CONFIRMATION_CODE_REQUIRED->value,
            'confirmation_code.string' => ResponseMessage::CONFIRMATION_CODE_STRING->value,
            'confirmation_code.size' => ResponseMessage::CONFIRMATION_CODE_SIZE->value,
        ];
    }
}
