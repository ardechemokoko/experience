<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dossier\StoreDossierRequest;
use App\Http\Requests\Dossier\UpdateDossierRequest;
use App\Http\Requests\Dossier\ValiderDossierRequest;
use App\Http\Requests\Document\UploadDocumentRequest;
use App\Http\Requests\Document\ValiderDocumentRequest;
use App\Http\Resources\DossierResource;
use App\Http\Resources\DocumentResource;
use App\Models\Dossier;
use App\Models\Document;
use App\Models\Utilisateur;
use App\Models\AutoEcole;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="📁 Dossiers",
 *     description="Gestion des dossiers de formation"
 * )
 */
class DossierController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/dossiers",
     *     operationId="getDossiers",
     *     tags={"📁 Dossiers"},
     *     summary="📋 Liste des dossiers",
     *     @OA\Parameter(name="candidat_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="auto_ecole_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="statut", in="query", @OA\Schema(type="string", enum={"en_attente","en_cours","valide","rejete"})),
     *     @OA\Response(response=200, description="✅ Liste des dossiers")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Dossier::with(['candidat.personne', 'autoEcole', 'formation', 'documents']);

        if ($request->has('candidat_id')) {
            $query->where('candidat_id', $request->candidat_id);
        }

        if ($request->has('auto_ecole_id')) {
            $query->where('auto_ecole_id', $request->auto_ecole_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $dossiers = $query->latest()->paginate($request->get('per_page', 15));

        return DossierResource::collection($dossiers);
    }

    /**
     * @OA\Post(
     *     path="/api/dossiers",
     *     operationId="storeDossier",
     *     tags={"📁 Dossiers"},
     *     summary="➕ Créer un dossier",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"candidat_id","auto_ecole_id","formation_id","date_creation"},
     *             @OA\Property(property="candidat_id", type="string", format="uuid"),
     *             @OA\Property(property="auto_ecole_id", type="string", format="uuid"),
     *             @OA\Property(property="formation_id", type="string", format="uuid"),
     *             @OA\Property(property="statut", type="string", enum={"en_attente","en_cours","valide","rejete"}),
     *             @OA\Property(property="date_creation", type="string", format="date"),
     *             @OA\Property(property="commentaires", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="✅ Dossier créé")
     * )
     */
    public function store(StoreDossierRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $dossier = Dossier::create($request->validated());

            DB::commit();

            Log::info('Dossier créé', ['dossier_id' => $dossier->id]);

            return response()->json([
                'success' => true,
                'message' => 'Dossier créé avec succès.',
                'data' => new DossierResource($dossier->load(['candidat.personne', 'autoEcole', 'formation']))
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur création dossier', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du dossier.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/dossiers/{id}",
     *     operationId="getDossier",
     *     tags={"📁 Dossiers"},
     *     summary="🔍 Détails d'un dossier",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="✅ Détails du dossier")
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $dossier = Dossier::with(['candidat.personne', 'autoEcole', 'formation', 'documents'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new DossierResource($dossier)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dossier non trouvé.'
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/dossiers/{id}",
     *     operationId="updateDossier",
     *     tags={"📁 Dossiers"},
     *     summary="✏️ Mettre à jour un dossier",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="statut", type="string"),
     *             @OA\Property(property="commentaires", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="✅ Dossier mis à jour")
     * )
     */
    public function update(UpdateDossierRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $dossier = Dossier::findOrFail($id);
            $dossier->update($request->validated());

            DB::commit();

            Log::info('Dossier mis à jour', ['dossier_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Dossier mis à jour avec succès.',
                'data' => new DossierResource($dossier->load(['candidat.personne', 'autoEcole', 'formation']))
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
     *     path="/api/dossiers/{id}",
     *     operationId="deleteDossier",
     *     tags={"📁 Dossiers"},
     *     summary="🗑️ Supprimer un dossier",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="✅ Dossier supprimé")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $dossier = Dossier::findOrFail($id);
            $dossier->delete();

            DB::commit();

            Log::info('Dossier supprimé', ['dossier_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Dossier supprimé avec succès.'
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

    /**
     * @OA\Post(
     *     path="/api/dossiers/{id}/upload-document",
     *     operationId="uploadDocument",
     *     tags={"📁 Dossiers"},
     *     summary="📤 Uploader un document",
     *     description="Upload un document et l'associe au dossier",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"type_document_id","fichier"},
     *                 @OA\Property(property="type_document_id", type="string", format="uuid"),
     *                 @OA\Property(property="fichier", type="string", format="binary"),
     *                 @OA\Property(property="commentaires", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="✅ Document uploadé avec succès"
     *     ),
     *     @OA\Response(response=403, description="❌ Ce dossier ne vous appartient pas"),
     *     @OA\Response(response=404, description="❌ Dossier non trouvé")
     * )
     */
    public function uploadDocument(UploadDocumentRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Récupérer le dossier
            $dossier = Dossier::with('candidat.personne')->findOrFail($id);

            // Vérifier que le dossier appartient au candidat connecté
            $token = $request->bearerToken();
            if ($token) {
                $payload = json_decode(base64_decode($token), true);
                $user = Utilisateur::with('personne.candidat')->find($payload['user_id']);

                if ($user && $user->personne && $user->personne->candidat) {
                    if ($dossier->candidat_id !== $user->personne->candidat->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Ce dossier ne vous appartient pas.'
                        ], 403);
                    }
                }
            }

            // Upload du fichier
            $file = $request->file('fichier');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('documents/' . date('Y/m'), $fileName, 'public');

            // Créer l'enregistrement du document
            $document = Document::create([
                'dossier_id' => $dossier->id,
                'type_document_id' => $request->type_document_id,
                'nom_fichier' => $file->getClientOriginalName(),
                'chemin_fichier' => $filePath,
                'type_mime' => $file->getMimeType(),
                'taille_fichier' => $file->getSize(),
                'valide' => false,
                'commentaires' => $request->commentaires,
            ]);

            // Mettre à jour la date de modification du dossier
            $dossier->update(['date_modification' => now()]);

            DB::commit();

            Log::info('Document uploadé', [
                'document_id' => $document->id,
                'dossier_id' => $dossier->id,
                'nom_fichier' => $fileName
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploadé avec succès !',
                'document' => new DocumentResource($document->load(['typeDocument']))
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur upload document', [
                'dossier_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du document.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/dossiers/{id}/valider",
     *     operationId="validerDossier",
     *     tags={"📁 Dossiers"},
     *     summary="✅ Valider ou rejeter un dossier",
     *     description="Permet à une auto-école de valider ou rejeter un dossier de candidature",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"statut"},
     *             @OA\Property(property="statut", type="string", enum={"valide","rejete"}, example="valide"),
     *             @OA\Property(property="commentaires", type="string", example="Dossier complet et conforme")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Dossier validé/rejeté",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="dossier", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="❌ Non autorisé"),
     *     @OA\Response(response=404, description="❌ Dossier non trouvé")
     * )
     */
    public function valider(ValiderDossierRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $dossier = Dossier::with(['candidat.personne', 'autoEcole', 'formation'])->findOrFail($id);

            // Vérifier que l'utilisateur est responsable de cette auto-école
            $token = $request->bearerToken();
            if ($token) {
                $payload = json_decode(base64_decode($token), true);
                $user = Utilisateur::with('personne')->find($payload['user_id']);

                if ($user && $user->personne) {
                    $autoEcole = AutoEcole::where('responsable_id', $user->personne->id)
                        ->where('id', $dossier->auto_ecole_id)
                        ->first();

                    if (!$autoEcole && $user->role !== 'admin') {
                        return response()->json([
                            'success' => false,
                            'message' => 'Vous n\'êtes pas autorisé à valider ce dossier.'
                        ], 403);
                    }
                }
            }

            // Mettre à jour le dossier
            $dossier->update([
                'statut' => $request->statut,
                'date_modification' => now(),
                'commentaires' => $request->commentaires,
            ]);

            DB::commit();

            $message = $request->statut === 'valide' 
                ? 'Dossier validé avec succès !' 
                : 'Dossier rejeté.';

            Log::info('Dossier validé/rejeté', [
                'dossier_id' => $id,
                'statut' => $request->statut,
                'responsable_id' => $user->personne->id ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'dossier' => new DossierResource($dossier)
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur validation dossier', [
                'dossier_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation du dossier.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/documents/{id}/valider",
     *     operationId="validerDocument",
     *     tags={"📄 Documents"},
     *     summary="✅ Valider ou rejeter un document",
     *     description="Permet à une auto-école de valider ou rejeter un document",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"valide"},
     *             @OA\Property(property="valide", type="boolean", example=true),
     *             @OA\Property(property="commentaires", type="string", example="Document conforme")
     *         )
     *     ),
     *     @OA\Response(response=200, description="✅ Document validé/rejeté"),
     *     @OA\Response(response=403, description="❌ Non autorisé")
     * )
     */
    public function validerDocument(ValiderDocumentRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $document = Document::with('dossier.autoEcole')->findOrFail($id);

            // Vérifier que l'utilisateur est responsable de l'auto-école du dossier
            $token = $request->bearerToken();
            if ($token) {
                $payload = json_decode(base64_decode($token), true);
                $user = Utilisateur::with('personne')->find($payload['user_id']);

                if ($user && $user->personne) {
                    $autoEcole = AutoEcole::where('responsable_id', $user->personne->id)
                        ->where('id', $document->dossier->auto_ecole_id)
                        ->first();

                    if (!$autoEcole && $user->role !== 'admin') {
                        return response()->json([
                            'success' => false,
                            'message' => 'Vous n\'êtes pas autorisé à valider ce document.'
                        ], 403);
                    }
                }
            }

            // Mettre à jour le document
            $document->update([
                'valide' => $request->valide,
                'commentaires' => $request->commentaires,
            ]);

            // Mettre à jour la date de modification du dossier
            $document->dossier->update(['date_modification' => now()]);

            DB::commit();

            $message = $request->valide 
                ? 'Document validé avec succès !' 
                : 'Document rejeté.';

            Log::info('Document validé/rejeté', [
                'document_id' => $id,
                'valide' => $request->valide
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'document' => new DocumentResource($document->load('typeDocument'))
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur validation document', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation du document.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}

