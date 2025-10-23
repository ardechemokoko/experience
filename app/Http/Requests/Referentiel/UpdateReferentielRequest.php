<?php

namespace App\Http\Requests\Referentiel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateReferentielRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'statut' => ['sometimes', 'boolean'],
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

