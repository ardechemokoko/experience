<?php

namespace App\Http\Requests\Dossier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut' => ['sometimes', 'in:en_attente,en_cours,valide,rejete'],
            'date_modification' => ['nullable', 'date'],
            'commentaires' => ['nullable', 'string'],
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

