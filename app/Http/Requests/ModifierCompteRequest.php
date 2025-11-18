<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModifierCompteRequest extends FormRequest
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
            'titulaire' => 'sometimes|string|max:255',
            'code_marchand' => 'sometimes|string|max:255|nullable',
            'statut' => 'sometimes|in:actif,inactif,suspendu',
            'nom_compte' => 'sometimes|string|max:255',
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
            'titulaire.string' => 'Le titulaire doit être une chaîne de caractères.',
            'titulaire.max' => 'Le titulaire ne peut pas dépasser 255 caractères.',
            'code_marchand.string' => 'Le code marchand doit être une chaîne de caractères.',
            'code_marchand.max' => 'Le code marchand ne peut pas dépasser 255 caractères.',
            'statut.in' => 'Le statut doit être actif, inactif ou suspendu.',
            'nom_compte.string' => 'Le nom du compte doit être une chaîne de caractères.',
            'nom_compte.max' => 'Le nom du compte ne peut pas dépasser 255 caractères.',
        ];
    }
}
