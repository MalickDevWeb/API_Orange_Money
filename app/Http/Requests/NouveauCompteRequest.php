<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NouveauCompteRequest extends FormRequest
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
            'nom_compte' => 'required|string|max:255',
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
            'nom_compte.required' => 'Le nom du compte est obligatoire.',
            'nom_compte.string' => 'Le nom du compte doit être une chaîne de caractères.',
            'nom_compte.max' => 'Le nom du compte ne peut pas dépasser 255 caractères.',
        ];
    }
}
