<?php

namespace App\Http\Requests\Referentiel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReferentielRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:referentiels,code'],
            'type_ref' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'statut' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire.',
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code existe déjà.',
            'type_ref.required' => 'Le type de référentiel est obligatoire.',
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

