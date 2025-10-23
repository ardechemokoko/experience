<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="📄 Documents",
 *     description="Gestion des documents des dossiers"
 * )
 */
class DocumentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/documents",
     *     operationId="getDocuments",
     *     tags={"📄 Documents"},
     *     summary="📋 Liste des documents",
     *     @OA\Parameter(name="dossier_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="valide", in="query", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="✅ Liste des documents")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Document::with(['dossier', 'typeDocument']);

        if ($request->has('dossier_id')) {
            $query->where('dossier_id', $request->dossier_id);
        }

        if ($request->has('valide')) {
            $query->where('valide', $request->boolean('valide'));
        }

        $documents = $query->latest()->paginate($request->get('per_page', 15));

        return DocumentResource::collection($documents);
    }

    /**
     * @OA\Post(
     *     path="/api/documents",
     *     operationId="storeDocument",
     *     tags={"📄 Documents"},
     *     summary="➕ Créer un document",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"dossier_id","type_document_id","nom_fichier","chemin_fichier","type_mime","taille_fichier"},
     *             @OA\Property(property="dossier_id", type="string", format="uuid"),
     *             @OA\Property(property="type_document_id", type="string", format="uuid"),
     *             @OA\Property(property="nom_fichier", type="string", example="carte_identite.pdf"),
     *             @OA\Property(property="chemin_fichier", type="string", example="/uploads/documents/carte_identite.pdf"),
     *             @OA\Property(property="type_mime", type="string", example="application/pdf"),
     *             @OA\Property(property="taille_fichier", type="integer", example=1024000),
     *             @OA\Property(property="valide", type="boolean", example=false),
     *             @OA\Property(property="commentaires", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="✅ Document créé")
     * )
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $document = Document::create($request->validated());

            DB::commit();

            Log::info('Document créé', ['document_id' => $document->id]);

            return response()->json([
                'success' => true,
                'message' => 'Document créé avec succès.',
                'data' => new DocumentResource($document->load(['dossier', 'typeDocument']))
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur création document', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du document.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/documents/{id}",
     *     operationId="getDocument",
     *     tags={"📄 Documents"},
     *     summary="🔍 Détails d'un document",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="✅ Détails du document")
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $document = Document::with(['dossier', 'typeDocument'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new DocumentResource($document)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé.'
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/documents/{id}",
     *     operationId="updateDocument",
     *     tags={"📄 Documents"},
     *     summary="✏️ Mettre à jour un document",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="valide", type="boolean"),
     *             @OA\Property(property="commentaires", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="✅ Document mis à jour")
     * )
     */
    public function update(UpdateDocumentRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $document = Document::findOrFail($id);
            $document->update($request->validated());

            DB::commit();

            Log::info('Document mis à jour', ['document_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Document mis à jour avec succès.',
                'data' => new DocumentResource($document->load(['dossier', 'typeDocument']))
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/documents/{id}",
     *     operationId="deleteDocument",
     *     tags={"📄 Documents"},
     *     summary="🗑️ Supprimer un document",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="✅ Document supprimé")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $document = Document::findOrFail($id);
            
            // Supprimer le fichier physique
            if (Storage::exists($document->chemin_fichier)) {
                Storage::delete($document->chemin_fichier);
            }
            
            $document->delete();

            DB::commit();

            Log::info('Document supprimé', ['document_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}

