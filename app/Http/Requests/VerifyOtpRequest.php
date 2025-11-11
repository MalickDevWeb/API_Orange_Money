<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'telephone' => 'required|string|regex:/^[0-9]{9,15}$/',
            'otp_code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.regex' => 'Le numéro de téléphone doit contenir entre 9 et 15 chiffres.',
            'otp_code.required' => 'Le code OTP est obligatoire.',
            'otp_code.size' => 'Le code OTP doit contenir exactement 6 chiffres.',
            'otp_code.regex' => 'Le code OTP doit contenir uniquement des chiffres.',
        ];
    }
}
