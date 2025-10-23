<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referentiel\StoreReferentielRequest;
use App\Http\Requests\Referentiel\UpdateReferentielRequest;
use App\Http\Resources\ReferentielResource;
use App\Models\Referentiel;
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
 *     name="📖 Référentiels",
 *     description="Gestion des données de référence (types de permis, sessions, etc.)"
 * )
 */
class ReferentielController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/referentiels",
     *     operationId="getReferentiels",
     *     tags={"📖 Référentiels"},
     *     summary="📋 Liste des référentiels",
     *     @OA\Parameter(name="type_ref", in="query", @OA\Schema(type="string"), description="Type de référentiel"),
     *     @OA\Parameter(name="statut", in="query", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="✅ Liste des référentiels")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Referentiel::query();

        if ($request->has('type_ref')) {
            $query->where('type_ref', $request->type_ref);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->boolean('statut'));
        }

        $referentiels = $query->orderBy('type_ref')->orderBy('libelle')->paginate($request->get('per_page', 15));

        return ReferentielResource::collection($referentiels);
    }

    /**
     * @OA\Post(
     *     path="/api/referentiels",
     *     operationId="storeReferentiel",
     *     tags={"📖 Référentiels"},
     *     summary="➕ Créer un référentiel",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"libelle","code","type_ref"},
     *             @OA\Property(property="libelle", type="string", example="Permis B"),
     *             @OA\Property(property="code", type="string", example="PERMIS_B"),
     *             @OA\Property(property="type_ref", type="string", example="type_permis"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="statut", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="✅ Référentiel créé")
     * )
     */
    public function store(StoreReferentielRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $referentiel = Referentiel::create($request->validated());

            DB::commit();

            Log::info('Référentiel créé', ['referentiel_id' => $referentiel->id]);

            return response()->json([
                'success' => true,
                'message' => 'Référentiel créé avec succès.',
                'data' => new ReferentielResource($referentiel)
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur création référentiel', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du référentiel.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/referentiels/{id}",
     *     operationId="getReferentiel",
     *     tags={"📖 Référentiels"},
     *     summary="🔍 Détails d'un référentiel",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="✅ Détails du référentiel")
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $referentiel = Referentiel::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new ReferentielResource($referentiel)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Référentiel non trouvé.'
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/referentiels/{id}",
     *     operationId="updateReferentiel",
     *     tags={"📖 Référentiels"},
     *     summary="✏️ Mettre à jour un référentiel",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="libelle", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="statut", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="✅ Référentiel mis à jour")
     * )
     */
    public function update(UpdateReferentielRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $referentiel = Referentiel::findOrFail($id);
            $referentiel->update($request->validated());

            DB::commit();

            Log::info('Référentiel mis à jour', ['referentiel_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Référentiel mis à jour avec succès.',
                'data' => new ReferentielResource($referentiel)
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
     *     path="/api/referentiels/{id}",
     *     operationId="deleteReferentiel",
     *     tags={"📖 Référentiels"},
     *     summary="🗑️ Supprimer un référentiel",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="✅ Référentiel supprimé")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $referentiel = Referentiel::findOrFail($id);
            $referentiel->delete();

            DB::commit();

            Log::info('Référentiel supprimé', ['referentiel_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Référentiel supprimé avec succès.'
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

