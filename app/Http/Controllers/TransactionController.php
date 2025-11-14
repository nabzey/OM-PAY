<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }
  
    /**
     * @OA\Get(
     *     path="/api/transactions",
     *     summary="Obtenir les transactions du compte connecté",
     *     description="Récupère toutes les transactions du compte de l'utilisateur connecté avec pagination et tri par ordre décroissant",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page pour la pagination",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de transaction",
     *         @OA\Schema(type="string", enum={"paiement", "transfert"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         @OA\Schema(type="string", enum={"en_attente", "reussie", "echouee"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transactions récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="compte_id", type="integer"),
     *             @OA\Property(property="transactions", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="reference", type="string"),
     *                         @OA\Property(property="type", type="string"),
     *                         @OA\Property(property="montant", type="number", format="decimal"),
     *                         @OA\Property(property="destinataire", type="string"),
     *                         @OA\Property(property="statut", type="string"),
     *                         @OA\Property(property="date_execution", type="string", format="date-time"),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             ),
     *             @OA\Property(property="filtres_appliques", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getTransactionsByCompte(Request $request): JsonResponse
    {
        $filtres = $request->only([
            'type', 'statut', 'date_debut', 'date_fin', 'per_page'
        ]);

        $transactions = $this->transactionService->getHistoriqueTransactions(
            $request->user(),
            $filtres
        );

        return response()->json([
            'compte_id' => $request->user()->id,
            'transactions' => $transactions,
            'filtres_appliques' => $filtres
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/transactions/paiement",
     *     summary="Effectuer un paiement depuis le compte connecté",
     *     description="Effectue un paiement vers un marchand avec code marchand ou numéro de téléphone",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant", "destinataire", "methode_paiement"},
     *             @OA\Property(property="montant", type="number", format="decimal", example=5000, description="Montant du paiement en FCFA"),
     *             @OA\Property(property="destinataire", type="string", example="MARCHAND001", description="Code marchand ou numéro de téléphone du destinataire"),
     *             @OA\Property(property="methode_paiement", type="string", enum={"code_marchand", "numero_telephone"}, example="code_marchand", description="Méthode de paiement"),
     *             @OA\Property(property="description", type="string", example="Paiement de facture", description="Description optionnelle"),
     *             @OA\Property(property="devise", type="string", enum={"XOF", "USD", "EUR"}, example="XOF", description="Devise (défaut: XOF)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès"),
     *             @OA\Property(property="transaction", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="reference", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="montant", type="number", format="decimal"),
     *                 @OA\Property(property="destinataire", type="string"),
     *                 @OA\Property(property="statut", type="string"),
     *                 @OA\Property(property="date_execution", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Échec du paiement",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Échec de la transaction"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de requête invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function effectuerPaiement(CreateTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['type'] = 'paiement'; // Forcer le type paiement

        try {
            $transaction = $this->transactionService->effectuerPaiement(
                $request->user(),
                $validated
            );

            return response()->json([
                'message' => 'Paiement effectué avec succès',
                'transaction' => [
                    'id' => $transaction->id,
                    'reference' => $transaction->reference,
                    'type' => $transaction->type,
                    'montant' => $transaction->montant,
                    'destinataire' => $transaction->destinataire,
                    'statut' => $transaction->statut,
                    'date_execution' => $transaction->date_execution,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Échec du paiement',
                'error' => $e->getMessage()
            ], 400);
        }
    }


    public function index(Request $request): JsonResponse
    {
        $filtres = $request->only([
            'type', 'statut', 'date_debut', 'date_fin', 'per_page'
        ]);

        $transactions = $this->transactionService->getHistoriqueTransactions(
            $request->user(),
            $filtres
        );

        return response()->json([
            'compte_id' => $request->user()->id,
            'transactions' => $transactions,
            'filtres_appliques' => $filtres
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/transactions",
     *     summary="Effectuer un paiement",
     *     description="Effectue un paiement vers un marchand avec code marchand ou numéro de téléphone",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "montant", "destinataire", "methode_paiement"},
     *             @OA\Property(property="type", type="string", enum={"paiement"}, example="paiement", description="Type de transaction (paiement)"),
     *             @OA\Property(property="montant", type="number", format="decimal", example=5000, description="Montant du paiement en FCFA"),
     *             @OA\Property(property="destinataire", type="string", example="MARCHAND001", description="Code marchand ou numéro de téléphone du destinataire"),
     *             @OA\Property(property="methode_paiement", type="string", enum={"code_marchand", "numero_telephone"}, example="code_marchand", description="Méthode de paiement"),
     *             @OA\Property(property="description", type="string", example="Paiement de facture", description="Description optionnelle"),
     *             @OA\Property(property="devise", type="string", enum={"XOF", "USD", "EUR"}, example="XOF", description="Devise (défaut: XOF)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès"),
     *             @OA\Property(property="transaction", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="reference", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="montant", type="number", format="decimal"),
     *                 @OA\Property(property="destinataire", type="string"),
     *                 @OA\Property(property="statut", type="string"),
     *                 @OA\Property(property="date_execution", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Échec du paiement",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Échec de la transaction"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de requête invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    /**
     * @OA\Post(
     *     path="/api/transferts",
     *     summary="Effectuer un transfert",
     *     description="Effectue un transfert vers un autre compte Orange Money existant",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "montant", "destinataire"},
     *             @OA\Property(property="type", type="string", enum={"transfert"}, example="transfert", description="Type de transaction (transfert)"),
     *             @OA\Property(property="montant", type="number", format="decimal", example=10000, description="Montant du transfert en FCFA"),
     *             @OA\Property(property="destinataire", type="string", example="+221771234567", description="Numéro de téléphone du bénéficiaire (doit avoir un compte Orange Money actif)"),
     *             @OA\Property(property="description", type="string", example="Transfert d'argent", description="Description optionnelle"),
     *             @OA\Property(property="devise", type="string", enum={"XOF", "USD", "EUR"}, example="XOF", description="Devise (défaut: XOF)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès"),
     *             @OA\Property(property="transaction", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="reference", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="montant", type="number", format="decimal"),
     *                 @OA\Property(property="destinataire", type="string"),
     *                 @OA\Property(property="statut", type="string"),
     *                 @OA\Property(property="date_execution", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Échec du transfert",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Échec de la transaction"),
     *             @OA\Property(property="error", type="string", example="Destinataire introuvable")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de requête invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function effectuerTransaction(CreateTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Debug: vérifier les données validées
        Log::info('Données validées pour transaction:', $validated);

        try {
            $type = $validated['type'] ?? 'paiement'; // Par défaut paiement si non spécifié

            if ($type === 'transfert') {
                $transaction = $this->transactionService->effectuerTransfert(
                    $request->user(),
                    $validated
                );
                $message = 'Transfert effectué avec succès';
                $destinataire = $transaction->metadata['destinataire_nom'] ?? $transaction->destinataire;
            } else {
                $transaction = $this->transactionService->effectuerPaiement(
                    $request->user(),
                    $validated
                );
                $message = 'Paiement effectué avec succès';
                $destinataire = $transaction->destinataire;
            }

            return response()->json([
                'message' => $message,
                'transaction' => [
                    'id' => $transaction->id,
                    'reference' => $transaction->reference,
                    'type' => $transaction->type,
                    'montant' => $transaction->montant,
                    'destinataire' => $destinataire,
                    'statut' => $transaction->statut,
                    'date_execution' => $transaction->date_execution,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Échec de la transaction',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function genererQrCode(string $codeMarchand, Request $request): JsonResponse
    {
        // Validation basique du code marchand
        if (strlen($codeMarchand) < 6 || strlen($codeMarchand) > 20) {
            return response()->json([
                'message' => 'Code marchand invalide'
            ], 404);
        }

        $montantSuggere = $request->query('montant');

        // Créer les données pour le QR code
        $qrData = [
            'type' => 'paiement_marchand',
            'code_marchand' => $codeMarchand,
            'timestamp' => now()->toISOString()
        ];

        if ($montantSuggere) {
            $qrData['montant_suggere'] = (float) $montantSuggere;
        }

        // Encoder en JSON pour le QR code
        $qrContent = json_encode($qrData);

        // Générer le QR code en SVG
        $qrCodeSvg = QrCode::format('svg')
            ->size(300)
            ->generate($qrContent);

        // Encoder en base64 pour transmission
        $qrCodeBase64 = base64_encode($qrCodeSvg);

        return response()->json([
            'qr_code' => $qrCodeBase64,
            'code_marchand' => $codeMarchand,
            'montant_suggere' => $montantSuggere ? (float) $montantSuggere : null,
            'data' => $qrData
        ]);
    }

    
    public function show(Request $request, string $reference): JsonResponse
    {
        $transaction = $this->transactionService->getTransactionDetails(
            $reference,
            $request->user()
        );

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction introuvable'
            ], 404);
        }

        return response()->json([
            'compte_id' => $request->user()->id,
            'transaction' => $transaction
        ]);
    }
}
