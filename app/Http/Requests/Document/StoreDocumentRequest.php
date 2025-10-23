<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dossier_id' => ['required', 'uuid', 'exists:dossiers,id'],
            'type_document_id' => ['required', 'uuid', 'exists:referentiels,id'],
            'nom_fichier' => ['required', 'string', 'max:255'],
            'chemin_fichier' => ['required', 'string', 'max:500'],
            'type_mime' => ['required', 'string', 'max:100'],
            'taille_fichier' => ['required', 'integer', 'min:0'],
            'valide' => ['sometimes', 'boolean'],
            'commentaires' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'dossier_id.required' => 'Le dossier est obligatoire.',
            'type_document_id.required' => 'Le type de document est obligatoire.',
            'nom_fichier.required' => 'Le nom du fichier est obligatoire.',
            'chemin_fichier.required' => 'Le chemin du fichier est obligatoire.',
            'type_mime.required' => 'Le type MIME est obligatoire.',
            'taille_fichier.required' => 'La taille du fichier est obligatoire.',
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

