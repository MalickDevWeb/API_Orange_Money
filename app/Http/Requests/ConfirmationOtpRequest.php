<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmationOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'otp_code' => 'required|string|size:6',
            'compte_id' => 'required|string|exists:comptes,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'otp_code.required' => 'Le code OTP est obligatoire.',
            'otp_code.string' => 'Le code OTP doit être une chaîne de caractères.',
            'otp_code.size' => 'Le code OTP doit contenir exactement 6 caractères.',
            'compte_id.required' => 'L\'ID du compte est obligatoire.',
            'compte_id.string' => 'L\'ID du compte doit être une chaîne de caractères.',
            'compte_id.exists' => 'Le compte spécifié n\'existe pas.',
        ];
    }
}
