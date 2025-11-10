<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\MessageErreursReqest;

class StoreCompteRequest extends FormRequest
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
            'numero_compte' => 'required|string|unique:comptes,numero_compte',
            'solde' => 'required|numeric|min:0',
            'client_id' => 'required|exists:clients,id',
            'type_compte' => 'required|string',
            'devise' => 'required|string',
            'status' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'numero_compte.required' => MessageErreursReqest::NUMERO_COMPTE_REQUIRED->value,
            'numero_compte.string' => MessageErreursReqest::NUMERO_COMPTE_STRING->value,
            'numero_compte.unique' => MessageErreursReqest::NUMERO_COMPTE_UNIQUE->value,
            'solde.required' => MessageErreursReqest::SOLDE_INITIAL_REQUIRED->value,
            'solde.numeric' => MessageErreursReqest::SOLDE_INITIAL_NUMERIC->value,
            'solde.min' => MessageErreursReqest::SOLDE_INITIAL_MIN->value,
            'client_id.required' => MessageErreursReqest::CLIENT_ID_REQUIRED->value,
            'client_id.exists' => MessageErreursReqest::CLIENT_ID_EXISTS->value,
            'type_compte.required' => MessageErreursReqest::TYPE_COMPTE_REQUIRED->value,
            'type_compte.string' => MessageErreursReqest::TYPE_COMPTE_STRING->value,
            'devise.required' => 'La devise est obligatoire.',
            'devise.string' => 'La devise doit être une chaîne de caractères.',
            'status.required' => 'Le statut est obligatoire.',
            'status.string' => 'Le statut doit être une chaîne de caractères.',
        ];

    }
}
