<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Compte;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TransactionService
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Effectuer un paiement
     */
    public function effectuerPaiement(Compte $compte, array $data): Transaction
    {
        return DB::transaction(function () use ($compte, $data) {
            // Créer la transaction
            $transaction = Transaction::create([
                'reference' => Transaction::generateReference(),
                'compte_id' => $compte->id,
                'type' => 'paiement',
                'methode_paiement' => $data['methode_paiement'], // 'code_marchand' ou 'numero_telephone'
                'destinataire' => $data['destinataire'],
                'montant' => $data['montant'],
                'devise' => $data['devise'] ?? 'XOF',
                'statut' => 'en_attente',
                'description' => $data['description'] ?? 'Paiement via Orange Money',
                'metadata' => $data['metadata'] ?? null,
            ]);

            try {
                // Simuler le traitement du paiement (en production, intégrer avec l'API Orange Money)
                $this->traiterPaiement($transaction, $data);

                // Marquer comme réussie
                $transaction->marquerCommeReussie();

                // Envoyer notification SMS
                $this->envoyerNotificationSms($transaction);

                Log::info("Paiement réussi: {$transaction->reference} - {$transaction->montant} {$transaction->devise}");

                return $transaction;

            } catch (Exception $e) {
                $transaction->marquerCommeEchouee($e->getMessage());
                Log::error("Échec du paiement {$transaction->reference}: " . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Effectuer un transfert
     */
    public function effectuerTransfert(Compte $compte, array $data): Transaction
    {
        return DB::transaction(function () use ($compte, $data) {
            // Vérifier que le destinataire existe
            $destinataire = Compte::where('telephone', $data['destinataire'])->first();
            if (!$destinataire) {
                throw new Exception('Destinataire introuvable');
            }

            // Créer la transaction
            $transaction = Transaction::create([
                'reference' => Transaction::generateReference(),
                'compte_id' => $compte->id,
                'type' => 'transfert',
                'destinataire' => $data['destinataire'],
                'montant' => $data['montant'],
                'devise' => $data['devise'] ?? 'XOF',
                'statut' => 'en_attente',
                'description' => $data['description'] ?? 'Transfert Orange Money',
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'destinataire_id' => $destinataire->id,
                    'destinataire_nom' => $destinataire->nom
                ]),
            ]);

            try {
                // Simuler le transfert (en production, intégrer avec l'API Orange Money)
                $this->traiterTransfert($transaction, $destinataire, $data);

                // Marquer comme réussie
                $transaction->marquerCommeReussie();

                // Envoyer notifications SMS
                $this->envoyerNotificationSms($transaction);
                $this->envoyerNotificationSmsDestinataire($transaction, $destinataire);

                Log::info("Transfert réussi: {$transaction->reference} - {$transaction->montant} {$transaction->devise} vers {$destinataire->telephone}");

                return $transaction;

            } catch (Exception $e) {
                $transaction->marquerCommeEchouee($e->getMessage());
                Log::error("Échec du transfert {$transaction->reference}: " . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Traiter le paiement (simulation)
     */
    private function traiterPaiement(Transaction $transaction, array $data): void
    {
        // Simulation - En production, intégrer avec l'API Orange Money
        // Vérifier le code marchand ou le numéro de téléphone
        if ($transaction->methode_paiement === 'code_marchand') {
            // Vérifier que le code marchand existe (simulation)
            if (strlen($transaction->destinataire) < 6) {
                throw new Exception('Code marchand invalide');
            }
        } elseif ($transaction->methode_paiement === 'numero_telephone') {
            // Vérifier que le numéro est valide (simulation)
            if (!preg_match('/^\+221/', $transaction->destinataire)) {
                throw new Exception('Numéro de téléphone invalide');
            }
        }

        // Simuler un délai de traitement
        sleep(1);
    }

    /**
     * Traiter le transfert (simulation)
     */
    private function traiterTransfert(Transaction $transaction, Compte $destinataire, array $data): void
    {
        // Simulation - En production, intégrer avec l'API Orange Money
        // Vérifier que le destinataire peut recevoir le transfert

        // Simuler un délai de traitement
        sleep(1);
    }

    /**
     * Envoyer notification SMS pour la transaction
     */
    private function envoyerNotificationSms(Transaction $transaction): void
    {
        $message = $this->genererMessageNotification($transaction);
        $this->smsService->envoyerSms($transaction->compte->telephone, $message);
    }

    /**
     * Envoyer notification SMS au destinataire (pour les transferts)
     */
    private function envoyerNotificationSmsDestinataire(Transaction $transaction, Compte $destinataire): void
    {
        $message = "Vous avez reçu un transfert de {$transaction->montant} {$transaction->devise} de {$transaction->compte->nom}.\nRéférence: {$transaction->reference}";
        $this->smsService->envoyerSms($destinataire->telephone, $message);
    }

    /**
     * Générer le message de notification
     */
    private function genererMessageNotification(Transaction $transaction): string
    {
        $type = $transaction->type === 'paiement' ? 'Paiement' : 'Transfert';
        $destinataire = $transaction->methode_paiement === 'code_marchand' ? 'marchand' : $transaction->destinataire;

        return "{$type} de {$transaction->montant} {$transaction->devise} effectué avec succès.\n" .
               "Destinataire: {$destinataire}\n" .
               "Référence: {$transaction->reference}\n" .
               "Orange Money - Transaction sécurisée.";
    }

    /**
     * Obtenir l'historique des transactions d'un compte
     */
    public function getHistoriqueTransactions(Compte $compte, array $filtres = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Transaction::pourCompte($compte->id)->latest();

        if (isset($filtres['type'])) {
            $query->parType($filtres['type']);
        }

        if (isset($filtres['statut'])) {
            $query->where('statut', $filtres['statut']);
        }

        if (isset($filtres['date_debut'])) {
            $query->where('created_at', '>=', $filtres['date_debut']);
        }

        if (isset($filtres['date_fin'])) {
            $query->where('created_at', '<=', $filtres['date_fin']);
        }

        return $query->paginate($filtres['per_page'] ?? 15);
    }

    /**
     * Obtenir les détails d'une transaction
     */
    public function getTransactionDetails(string $reference, Compte $compte): ?Transaction
    {
        return Transaction::where('reference', $reference)
                         ->pourCompte($compte->id)
                         ->first();
    }
}