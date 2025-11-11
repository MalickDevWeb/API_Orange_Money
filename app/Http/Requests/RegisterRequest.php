<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use App\Enums\MessagesErreursRequests;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'telephone' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'type' => ['required', new Enum(UserType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => MessagesErreursRequests::NOM_REQUIRED->value,
            'nom.string' => MessagesErreursRequests::NOM_STRING->value,
            'prenom.required' => MessagesErreursRequests::PRENOM_REQUIRED->value,
            'prenom.string' => MessagesErreursRequests::PRENOM_STRING->value,
            'telephone.required' => MessagesErreursRequests::TELEPHONE_REQUIRED->value,
            'email.required' => MessagesErreursRequests::EMAIL_REQUIRED->value,
            'email.email' => MessagesErreursRequests::EMAIL_EMAIL->value,
            'password.required' => MessagesErreursRequests::PASSWORD_REQUIRED->value,
            'password.string' => MessagesErreursRequests::PASSWORD_STRING->value,
            'password.min' => MessagesErreursRequests::PASSWORD_MIN->value,
            'type.required' => MessagesErreursRequests::TYPE_REQUIRED->value,
            'type.enum' => MessagesErreursRequests::TYPE_ENUM->value,
        ];
    }
}
