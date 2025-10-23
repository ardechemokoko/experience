<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Candidat\StoreCandidatRequest;
use App\Http\Requests\Candidat\UpdateCandidatRequest;
use App\Http\Requests\Candidat\CompleteProfileRequest;
use App\Http\Requests\Candidat\InscriptionFormationRequest;
use App\Http\Resources\CandidatResource;
use App\Http\Resources\DossierResource;
use App\Models\Candidat;
use App\Models\Dossier;
use App\Models\Utilisateur;
use App\Models\FormationAutoEcole;
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
 *     name="👨‍🎓 Candidats",
 *     description="Gestion des candidats au permis de conduire"
 * )
 */
class CandidatController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/candidats",
     *     operationId="getCandidats",
     *     tags={"👨‍🎓 Candidats"},
     *     summary="📋 Liste de tous les candidats",
     *     description="Récupère la liste paginée de tous les candidats avec leurs informations personnelles",
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Liste des candidats récupérée"
     *     ),
     *     @OA\Response(response=401, description="❌ Non authentifié")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->get('per_page', 15);
        
        $candidats = Candidat::with('personne')
            ->latest()
            ->paginate($perPage);

        return CandidatResource::collection($candidats);
    }

    /**
     * @OA\Post(
     *     path="/api/candidats",
     *     operationId="storeCandidat",
     *     tags={"👨‍🎓 Candidats"},
     *     summary="➕ Créer un nouveau candidat",
     *     description="Crée un nouveau candidat avec ses informations personnelles",
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"personne_id","numero_candidat","date_naissance","lieu_naissance","nip","type_piece","numero_piece","nationalite","genre"},
     *             @OA\Property(property="personne_id", type="string", format="uuid", example="019a0e34-d153-7330-8cb6-80b14fd8811c"),
     *             @OA\Property(property="numero_candidat", type="string", example="CAN-2025-001"),
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1995-05-15"),
     *             @OA\Property(property="lieu_naissance", type="string", example="Dakar"),
     *             @OA\Property(property="nip", type="string", example="1234567890123"),
     *             @OA\Property(property="type_piece", type="string", example="CNI"),
     *             @OA\Property(property="numero_piece", type="string", example="1234567890"),
     *             @OA\Property(property="nationalite", type="string", example="Sénégalaise"),
     *             @OA\Property(property="genre", type="string", enum={"M","F"}, example="M")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="✅ Candidat créé avec succès"
     *     ),
     *     @OA\Response(response=401, description="❌ Non authentifié"),
     *     @OA\Response(response=422, description="❌ Erreur de validation")
     * )
     */
    public function store(StoreCandidatRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $candidat = Candidat::create($request->validated());

            DB::commit();

            Log::info('Candidat créé', ['candidat_id' => $candidat->id]);

            return response()->json([
                'success' => true,
                'message' => 'Candidat créé avec succès.',
                'data' => new CandidatResource($candidat->load('personne'))
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur création candidat', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du candidat.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/candidats/{id}",
     *     operationId="getCandidat",
     *     tags={"👨‍🎓 Candidats"},
     *     summary="🔍 Détails d'un candidat",
     *     description="Récupère les détails complets d'un candidat",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du candidat",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Détails du candidat"
     *     ),
     *     @OA\Response(response=404, description="❌ Candidat non trouvé")
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $candidat = Candidat::with(['personne', 'dossiers'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new CandidatResource($candidat)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Candidat non trouvé.'
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/candidats/{id}",
     *     operationId="updateCandidat",
     *     tags={"👨‍🎓 Candidats"},
     *     summary="✏️ Mettre à jour un candidat",
     *     description="Met à jour les informations d'un candidat",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du candidat",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="numero_candidat", type="string", example="CAN-2025-001"),
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1995-05-15"),
     *             @OA\Property(property="lieu_naissance", type="string", example="Dakar"),
     *             @OA\Property(property="type_piece", type="string", example="CNI"),
     *             @OA\Property(property="numero_piece", type="string", example="1234567890"),
     *             @OA\Property(property="nationalite", type="string", example="Sénégalaise")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Candidat mis à jour"
     *     ),
     *     @OA\Response(response=404, description="❌ Candidat non trouvé"),
     *     @OA\Response(response=422, description="❌ Erreur de validation")
     * )
     */
    public function update(UpdateCandidatRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $candidat = Candidat::findOrFail($id);
            $candidat->update($request->validated());

            DB::commit();

            Log::info('Candidat mis à jour', ['candidat_id' => $candidat->id]);

            return response()->json([
                'success' => true,
                'message' => 'Candidat mis à jour avec succès.',
                'data' => new CandidatResource($candidat->load('personne'))
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur mise à jour candidat', [
                'candidat_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du candidat.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/candidats/{id}",
     *     operationId="deleteCandidat",
     *     tags={"👨‍🎓 Candidats"},
     *     summary="🗑️ Supprimer un candidat",
     *     description="Supprime un candidat et ses dossiers associés",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID du candidat",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="✅ Candidat supprimé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Candidat supprimé avec succès.")
     *         )
     *     ),
     *     @OA\Response(response=404, description="❌ Candidat non trouvé")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $candidat = Candidat::findOrFail($id);
            $candidat->delete();

            DB::commit();

            Log::info('Candidat supprimé', ['candidat_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Candidat supprimé avec succès.'
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur suppression candidat', [
                'candidat_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du candidat.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/candidats/complete-profile",
     *     operationId="completeProfile",
     *     tags={"👨‍🎓 Candidats"},
     *     summary="✅ Compléter le profil candidat",
     *     description="Permet à un utilisateur connecté de compléter son profil candidat avec ses informations personnelles",
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"date_naissance","lieu_naissance","nip","type_piece","numero_piece","nationalite","genre"},
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1995-05-15"),
     *             @OA\Property(property="lieu_naissance", type="string", example="Dakar"),
     *             @OA\Property(property="nip", type="string", example="1234567890123"),
     *             @OA\Property(property="type_piece", type="string", example="CNI"),
     *             @OA\Property(property="numero_piece", type="string", example="1234567890"),
     *             @OA\Property(property="nationalite", type="string", example="Sénégalaise"),
     *             @OA\Property(property="genre", type="string", enum={"M","F"}, example="M")
     *         )
     *     ),
     *     @OA\Response(response=201, description="✅ Profil candidat complété"),
     *     @OA\Response(response=400, description="❌ Profil déjà complété"),
     *     @OA\Response(response=401, description="❌ Non authentifié")
     * )
     */
    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Récupérer l'utilisateur depuis le token
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

            // Vérifier si le candidat existe déjà
            $existingCandidat = Candidat::where('personne_id', $user->personne->id)->first();
            if ($existingCandidat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre profil candidat est déjà complété.'
                ], 400);
            }

            // Générer un numéro de candidat unique
            $numeroCandidat = $this->generateNumeroCandidat();

            // Créer le candidat
            $candidat = Candidat::create([
                'personne_id' => $user->personne->id,
                'numero_candidat' => $numeroCandidat,
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'nip' => $request->nip,
                'type_piece' => $request->type_piece,
                'numero_piece' => $request->numero_piece,
                'nationalite' => $request->nationalite,
                'genre' => $request->genre,
            ]);

            DB::commit();

            Log::info('Profil candidat complété', [
                'candidat_id' => $candidat->id,
                'personne_id' => $user->personne->id,
                'numero_candidat' => $numeroCandidat
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profil candidat complété avec succès !',
                'data' => new CandidatResource($candidat->load('personne'))
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur complétion profil candidat', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la complétion du profil.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/candidats/inscription-formation",
     *     operationId="inscriptionFormation",
     *     tags={"👨‍🎓 Candidats"},
     *     summary="🎓 S'inscrire à une formation",
     *     description="Permet à un candidat de s'inscrire à une formation dans une auto-école. Crée automatiquement un dossier.",
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"auto_ecole_id","formation_id"},
     *             @OA\Property(property="auto_ecole_id", type="string", format="uuid"),
     *             @OA\Property(property="formation_id", type="string", format="uuid"),
     *             @OA\Property(property="commentaires", type="string", example="Je souhaite commencer dès que possible")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="✅ Inscription réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inscription à la formation réussie !"),
     *             @OA\Property(property="dossier", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="❌ Candidat non trouvé ou déjà inscrit"),
     *     @OA\Response(response=404, description="❌ Formation non trouvée")
     * )
     */
    public function inscriptionFormation(InscriptionFormationRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Récupérer le candidat depuis le token
            $token = $request->bearerToken();
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token d\'authentification manquant.'
                ], 401);
            }

            $payload = json_decode(base64_decode($token), true);
            $user = Utilisateur::with('personne.candidat')->find($payload['user_id']);

            if (!$user || !$user->personne || !$user->personne->candidat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez d\'abord compléter votre profil candidat.'
                ], 400);
            }

            $candidat = $user->personne->candidat;

            // Vérifier que la formation existe et appartient à l'auto-école
            $formation = FormationAutoEcole::where('id', $request->formation_id)
                ->where('auto_ecole_id', $request->auto_ecole_id)
                ->where('statut', true)
                ->first();

            if (!$formation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette formation n\'existe pas ou n\'est plus disponible.'
                ], 404);
            }

            // Vérifier si le candidat n'est pas déjà inscrit à cette formation
            $existingDossier = Dossier::where('candidat_id', $candidat->id)
                ->where('formation_id', $formation->id)
                ->whereIn('statut', ['en_attente', 'en_cours', 'valide'])
                ->first();

            if ($existingDossier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous êtes déjà inscrit à cette formation.'
                ], 400);
            }

            // Créer le dossier
            $dossier = Dossier::create([
                'candidat_id' => $candidat->id,
                'auto_ecole_id' => $request->auto_ecole_id,
                'formation_id' => $request->formation_id,
                'statut' => 'en_attente',
                'date_creation' => now(),
                'commentaires' => $request->commentaires,
            ]);

            DB::commit();

            Log::info('Inscription à une formation', [
                'candidat_id' => $candidat->id,
                'formation_id' => $formation->id,
                'dossier_id' => $dossier->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inscription à la formation réussie !',
                'dossier' => new DossierResource($dossier->load(['candidat.personne', 'autoEcole', 'formation']))
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Erreur inscription formation', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/candidats/mes-dossiers",
     *     operationId="mesDossiers",
     *     tags={"👨‍🎓 Candidats"},
     *     summary="📁 Mes dossiers",
     *     description="Récupère tous les dossiers du candidat connecté",
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="✅ Liste des dossiers du candidat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="dossiers", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=400, description="❌ Profil candidat non complété")
     * )
     */
    public function mesDossiers(Request $request): JsonResponse
    {
        try {
            // Récupérer le candidat depuis le token
            $token = $request->bearerToken();
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token d\'authentification manquant.'
                ], 401);
            }

            $payload = json_decode(base64_decode($token), true);
            $user = Utilisateur::with('personne.candidat')->find($payload['user_id']);

            if (!$user || !$user->personne || !$user->personne->candidat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez d\'abord compléter votre profil candidat.'
                ], 400);
            }

            $candidat = $user->personne->candidat;

            // Récupérer tous les dossiers du candidat
            $dossiers = Dossier::where('candidat_id', $candidat->id)
                ->with(['autoEcole', 'formation.typePermis', 'documents'])
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'candidat' => new CandidatResource($candidat->load('personne')),
                'dossiers' => DossierResource::collection($dossiers),
                'total_dossiers' => $dossiers->count()
            ]);

        } catch (Exception $e) {
            Log::error('Erreur récupération dossiers candidat', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des dossiers.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function generateNumeroCandidat(): string
    {
        $year = date('Y');
        $lastCandidat = Candidat::whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->first();

        $number = $lastCandidat ? intval(substr($lastCandidat->numero_candidat, -3)) + 1 : 1;

        return sprintf('CAN-%s-%03d', $year, $number);
    }
}


