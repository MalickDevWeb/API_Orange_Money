<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnifiedTransactionRequest extends FormRequest
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
            'telephone_recepteur' => 'required|string|exists:users,telephone',
            'montant' => 'required|numeric|min:0.01',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'telephone_recepteur.required' => 'Le numéro de téléphone du destinataire est requis.',
            'telephone_recepteur.exists' => 'Le destinataire n\'existe pas.',
            'montant.required' => 'Le montant est requis.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être supérieur à 0.',
        ];
    }
}
