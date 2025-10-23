<?php

namespace App\Http\Requests\Dossier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'candidat_id' => ['required', 'uuid', 'exists:candidats,id'],
            'auto_ecole_id' => ['required', 'uuid', 'exists:auto_ecoles,id'],
            'formation_id' => ['required', 'uuid', 'exists:formation_auto_ecoles,id'],
            'statut' => ['sometimes', 'in:en_attente,en_cours,valide,rejete'],
            'date_creation' => ['required', 'date'],
            'date_modification' => ['nullable', 'date'],
            'commentaires' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'candidat_id.required' => 'Le candidat est obligatoire.',
            'auto_ecole_id.required' => 'L\'auto-école est obligatoire.',
            'formation_id.required' => 'La formation est obligatoire.',
            'date_creation.required' => 'La date de création est obligatoire.',
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

