<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserActionRequest extends FormRequest
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
            'action' => 'required|string|in:approve,reject,suspend,unsuspend,ban,unban,delete,deposit',
        ];

        // Additional validation based on action
        switch ($this->input('action')) {
            case 'reject':
                $rules['motif_rejet'] = 'required|string|max:500';
                break;
            case 'deposit':
                $rules['montant'] = 'required|numeric|min:' . \App\Enums\TransactionLimits::MIN_AMOUNT->value . '|max:' . \App\Enums\TransactionLimits::MAX_AMOUNT->value;
                $rules['note'] = 'nullable|string|max:255';
                break;
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
            'action.in' => 'Action non valide.',
            'motif_rejet.required' => 'Le motif de rejet est obligatoire pour cette action.',
            'motif_rejet.max' => 'Le motif de rejet ne peut pas dépasser 500 caractères.',
            'montant.required' => 'Le montant est obligatoire pour un dépôt.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être supérieur à 0.',
            'montant.max' => 'Le montant ne peut pas dépasser 10 000 000.',
            'note.max' => 'La note ne peut pas dépasser 255 caractères.',
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
            'montant' => 'montant',
            'note' => 'note',
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
