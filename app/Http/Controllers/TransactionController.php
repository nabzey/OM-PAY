<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Gestion des transactions Orange Money - Paiements et transferts"
 * )
 */
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
     *     summary="Obtenir l'historique des transactions",
     *     description="Récupère l'historique des transactions de l'utilisateur connecté avec possibilité de filtrage.",
     *     tags={"Transactions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Type de transaction (paiement, transfert)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Statut de la transaction (en_attente, reussie, echouee)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="date_debut",
     *         in="query",
     *         description="Date de début (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_fin",
     *         in="query",
     *         description="Date de fin (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="transactions", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="filtres_appliques", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
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
            'transactions' => $transactions,
            'filtres_appliques' => $filtres
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/paiements",
     *     summary="Effectuer un paiement",
     *     description="Effectue un paiement vers un marchand ou un numéro de téléphone.",
     *     tags={"Transactions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant", "methode_paiement", "destinataire"},
     *             @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant du paiement en FCFA"),
     *             @OA\Property(property="methode_paiement", type="string", enum={"code_marchand", "numero_telephone"}, example="numero_telephone"),
     *             @OA\Property(property="destinataire", type="string", example="+221771234567", description="Code marchand ou numéro de téléphone"),
     *             @OA\Property(property="description", type="string", example="Paiement de facture", description="Description optionnelle")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="transaction", type="object",
     *                 @OA\Property(property="reference", type="string"),
     *                 @OA\Property(property="montant", type="number"),
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
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides"
     *     )
     * )
     */
    public function effectuerPaiement(CreateTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Debug: vérifier les données validées
        Log::info('Données validées pour paiement:', $validated);

        try {
            $transaction = $this->transactionService->effectuerPaiement(
                $request->user(),
                $validated
            );

            return response()->json([
                'message' => 'Paiement effectué avec succès',
                'transaction' => [
                    'reference' => $transaction->reference,
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

    /**
     * @OA\Post(
     *     path="/api/transferts",
     *     summary="Effectuer un transfert d'argent",
     *     description="Transfère de l'argent vers un autre compte Orange Money.",
     *     tags={"Transactions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant", "destinataire"},
     *             @OA\Property(property="montant", type="number", format="float", example=10000, description="Montant du transfert en FCFA"),
     *             @OA\Property(property="destinataire", type="string", example="+221701234567", description="Numéro de téléphone du destinataire"),
     *             @OA\Property(property="description", type="string", example="Transfert familial", description="Description optionnelle")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="transaction", type="object",
     *                 @OA\Property(property="reference", type="string"),
     *                 @OA\Property(property="montant", type="number"),
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
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides"
     *     )
     * )
     */
    public function effectuerTransfert(CreateTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $transaction = $this->transactionService->effectuerTransfert(
                $request->user(),
                $validated
            );

            return response()->json([
                'message' => 'Transfert effectué avec succès',
                'transaction' => [
                    'reference' => $transaction->reference,
                    'montant' => $transaction->montant,
                    'destinataire' => $transaction->metadata['destinataire_nom'] ?? $transaction->destinataire,
                    'statut' => $transaction->statut,
                    'date_execution' => $transaction->date_execution,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Échec du transfert',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/transactions/{reference}",
     *     summary="Obtenir les détails d'une transaction",
     *     description="Récupère les détails complets d'une transaction spécifique.",
     *     tags={"Transactions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="reference",
     *         in="path",
     *         required=true,
     *         description="Référence de la transaction",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la transaction",
     *         @OA\JsonContent(
     *             @OA\Property(property="transaction", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction introuvable",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function show(string $reference): JsonResponse
    {
        $transaction = $this->transactionService->getTransactionDetails(
            $reference,
            request()->user()
        );

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction introuvable'
            ], 404);
        }

        return response()->json([
            'transaction' => $transaction
        ]);
    }
}
