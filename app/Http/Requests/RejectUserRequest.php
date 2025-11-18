<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->type === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'motif_rejet' => 'required|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'motif_rejet.required' => 'Le motif de rejet est obligatoire.',
            'motif_rejet.string' => 'Le motif de rejet doit être une chaîne de caractères.',
            'motif_rejet.max' => 'Le motif de rejet ne peut pas dépasser 500 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'motif_rejet' => 'motif de rejet',
        ];
    }
}
