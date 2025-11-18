<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BalanceRequestActionRequest extends FormRequest
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
        $rules = [
            'action' => 'required|string|in:approve,reject',
        ];

        // Additional validation based on action
        if ($this->input('action') === 'reject') {
            $rules['motif_rejet'] = 'required|string|max:500';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'L\'action est obligatoire.',
            'action.in' => 'Action non valide. Les actions possibles sont : approve, reject.',
            'motif_rejet.required' => 'Le motif de rejet est obligatoire pour rejeter une demande.',
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
            'action' => 'action',
            'motif_rejet' => 'motif de rejet',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from motif_rejet if present
        if ($this->has('motif_rejet')) {
            $this->merge([
                'motif_rejet' => trim($this->input('motif_rejet')),
            ]);
        }
    }
}
