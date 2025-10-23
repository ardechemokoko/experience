<?php

namespace App\Http\Requests\AutoEcole;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAutoEcoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_auto_ecole' => ['required', 'string', 'max:255'],
            'adresse' => ['nullable', 'string'],
            'email' => ['required', 'email', 'unique:auto_ecoles,email'],
            'responsable_id' => ['required', 'uuid', 'exists:personnes,id'],
            'contact' => ['required', 'string', 'max:20'],
            'statut' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_auto_ecole.required' => 'Le nom de l\'auto-école est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'email.unique' => 'Cet email est déjà utilisé par une autre auto-école.',
            'responsable_id.required' => 'Le responsable est obligatoire.',
            'responsable_id.exists' => 'Ce responsable n\'existe pas.',
            'contact.required' => 'Le contact est obligatoire.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Les données fournies ne sont pas valides.',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}

