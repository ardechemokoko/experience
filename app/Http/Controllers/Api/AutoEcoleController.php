<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AutoEcole\StoreAutoEcoleRequest;
use App\Http\Requests\AutoEcole\UpdateAutoEcoleRequest;
use App\Http\Resources\AutoEcoleResource;
use App\Http\Resources\FormationAutoEcoleResource;
use App\Http\Resources\DossierResource;
use App\Models\AutoEcole;
use App\Models\FormationAutoEcole;
use App\Models\Dossier;
use App\Models\Utilisateur;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Serveur de développement local"
 * )
 * 
 * @OA\Server(
 *     url="https://9c8r7bbvybn.preview.infomaniak.website",
 *     description="Serveur de production Infomaniak"
 * )
 * 
 * @OA\Tag(
 *     name="🏫 Auto-Écoles",
 *     description="Gestion des auto-écoles"
 * )
 */
class AutoEcoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/auto-ecoles",
     *     operationId="getAutoEcoles",
     *     tags={"🏫 Auto-Écoles"},
     *     summary="📋 Liste de toutes les auto-écoles",
     *     description="Récupère la liste paginée de toutes les auto-écoles",
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="statut", in="query", @OA\Schema(type="boolean"), description="Filtrer par statut"),
     *     @OA\Response(response=200, description="✅ Liste des auto-écoles")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AutoEcole::with(['responsable']);

        if ($request->has('statut')) {
            $query->where('statut', $request->boolean('statut'));
        }

        $autoEcoles = $query->latest()->paginate($request->get('per_page', 15));

        return AutoEcoleResource::collection($autoEcoles);
    }

    /**
     * @OA\Post(
     *     path="/api/auto-ecoles",
     *     operationId="storeAutoEcole",
     *     tags={"🏫 Auto-Écoles"},
     *     summary="➕ Créer une auto-école",
     *     description="Crée une nouvelle auto-école",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom_auto_ecole","email","responsable_id","contact"},
     *             @OA\Property(property="nom_auto_ecole", type="string", example="Auto-École Excellence"),
     *             @OA\Property(property="adresse", type="string", example="123 Avenue Principale"),
     *             @OA\Property(property="email", type="string", example="contact@excellence.com"),
     *             @OA\Property(property="responsable_id", type="string", format="uuid"),
     *             @OA\Property(property="contact", type="string", example="0612345678"),
     *             @OA\Property(property="statut", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="✅ Auto-école créée"),
     *     @OA\Response(response=422, description="❌ Erreur de validation")
     * )
     */
    public function store(StoreAutoEcoleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $autoEcole = AutoEcole::create($request->validated());

            DB::commit();

            Log::info('Auto-école créée', ['auto_ecole_id' => $autoEcole->id]);

            return response()->json([
                'success' => true,
                'message' => 'Auto-école créée avec succès.',
                'data' => new AutoEcoleResource($autoEcole->load('responsable'))
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur création auto-école', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'auto-école.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auto-ecoles/{id}",
     *     operationId="getAutoEcole",
     *     tags={"🏫 Auto-Écoles"},
     *     summary="🔍 Détails d'une auto-école",
     *     description="Récupère les détails complets d'une auto-école",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="✅ Détails de l'auto-école"),
     *     @OA\Response(response=404, description="❌ Auto-école non trouvée")
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $autoEcole = AutoEcole::with(['responsable', 'formations', 'dossiers'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new AutoEcoleResource($autoEcole)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Auto-école non trouvée.'
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/auto-ecoles/{id}",
     *     operationId="updateAutoEcole",
     *     tags={"🏫 Auto-Écoles"},
     *     summary="✏️ Mettre à jour une auto-école",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nom_auto_ecole", type="string"),
     *             @OA\Property(property="adresse", type="string"),
     *             @OA\Property(property="contact", type="string"),
     *             @OA\Property(property="statut", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="✅ Auto-école mise à jour"),
     *     @OA\Response(response=404, description="❌ Auto-école non trouvée")
     * )
     */
    public function update(UpdateAutoEcoleRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $autoEcole = AutoEcole::findOrFail($id);
            $autoEcole->update($request->validated());

            DB::commit();

            Log::info('Auto-école mise à jour', ['auto_ecole_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Auto-école mise à jour avec succès.',
                'data' => new AutoEcoleResource($autoEcole->load('responsable'))
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
     *     path="/api/auto-ecoles/{id}",
     *     operationId="deleteAutoEcole",
     *     tags={"🏫 Auto-Écoles"},
     *     summary="🗑️ Supprimer une auto-école",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="✅ Auto-école supprimée"),
     *     @OA\Response(response=404, description="❌ Auto-école non trouvée")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $autoEcole = AutoEcole::findOrFail($id);
            $autoEcole->delete();

            DB::commit();

            Log::info('Auto-école supprimée', ['auto_ecole_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Auto-école supprimée avec succès.'
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
     * @OA\Get(
     *     path="/api/auto-ecoles/{id}/formations",
     *     operationId="getFormationsAutoEcole",
     *     tags={"🏫 Auto-Écoles"},
     *     summary="📚 Formations d'une auto-école",
     *     description="Récupère toutes les formations actives proposées par une auto-école",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Liste des formations",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="auto_ecole", type="object"),
     *             @OA\Property(property="formations", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=404, description="❌ Auto-école non trouvée")
     * )
     */
    public function formations(string $id): JsonResponse
    {
        try {
            $autoEcole = AutoEcole::findOrFail($id);

            $formations = FormationAutoEcole::where('auto_ecole_id', $id)
                ->where('statut', true)
                ->with(['typePermis', 'session'])
                ->get();

            return response()->json([
                'success' => true,
                'auto_ecole' => new AutoEcoleResource($autoEcole),
                'formations' => FormationAutoEcoleResource::collection($formations),
                'total_formations' => $formations->count()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Auto-école non trouvée.'
            ], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auto-ecoles/mes-dossiers",
     *     operationId="mesDossiersAutoEcole",
     *     tags={"🏫 Auto-Écoles"},
     *     summary="📁 Dossiers de mon auto-école",
     *     description="Récupère tous les dossiers de l'auto-école du responsable connecté",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(name="statut", in="query", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Liste des dossiers",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="auto_ecole", type="object"),
     *             @OA\Property(property="dossiers", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="statistiques", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="❌ Pas d'auto-école associée"),
     *     @OA\Response(response=401, description="❌ Non authentifié")
     * )
     */
    public function mesDossiers(Request $request): JsonResponse
    {
        try {
            // Récupérer l'auto-école du responsable connecté
            $token = $request->bearerToken();
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token d\'authentification manquant.'
                ], 401);
            }

            $payload = json_decode(base64_decode($token), true);
            $user = Utilisateur::with('personne')->find($payload['user_id']);

            if (!$user || !$user->personne) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé.'
                ], 404);
            }

            // Récupérer l'auto-école dont l'utilisateur est responsable
            $autoEcole = AutoEcole::where('responsable_id', $user->personne->id)->first();

            if (!$autoEcole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune auto-école associée à votre compte.'
                ], 400);
            }

            // Récupérer les dossiers de l'auto-école
            $query = Dossier::where('auto_ecole_id', $autoEcole->id)
                ->with(['candidat.personne', 'formation.typePermis', 'documents']);

            // Filtrer par statut si demandé
            if ($request->has('statut')) {
                $query->where('statut', $request->statut);
            }

            $dossiers = $query->latest()->get();

            // Statistiques
            $statistiques = [
                'total' => $dossiers->count(),
                'en_attente' => $dossiers->where('statut', 'en_attente')->count(),
                'en_cours' => $dossiers->where('statut', 'en_cours')->count(),
                'valide' => $dossiers->where('statut', 'valide')->count(),
                'rejete' => $dossiers->where('statut', 'rejete')->count(),
            ];

            Log::info('Consultation dossiers auto-école', [
                'auto_ecole_id' => $autoEcole->id,
                'total_dossiers' => $statistiques['total']
            ]);

            return response()->json([
                'success' => true,
                'auto_ecole' => new AutoEcoleResource($autoEcole),
                'dossiers' => DossierResource::collection($dossiers),
                'statistiques' => $statistiques
            ]);

        } catch (Exception $e) {
            Log::error('Erreur récupération dossiers auto-école', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des dossiers.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}

