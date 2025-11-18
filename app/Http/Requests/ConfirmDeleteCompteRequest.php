<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmDeleteCompteRequest extends FormRequest
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
            'confirmation_code' => 'required|string|size:' . \App\Enums\DefaultValues::OTP_SIZE->value,
            'compte_id' => 'required|string|exists:comptes,id',
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation_code.required' => 'Le code de confirmation est obligatoire.',
            'confirmation_code.string' => 'Le code de confirmation doit être une chaîne de caractères.',
            'confirmation_code.size' => 'Le code de confirmation doit contenir exactement 6 caractères.',
            'compte_id.required' => 'L\'ID du compte est obligatoire.',
            'compte_id.string' => 'L\'ID du compte doit être une chaîne de caractères.',
            'compte_id.exists' => 'Le compte spécifié n\'existe pas.',
        ];
    }
}
