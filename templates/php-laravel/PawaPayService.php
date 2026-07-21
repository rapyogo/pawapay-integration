<?php
// app/Services/PawaPayService.php
// Service pawaPay pour Laravel — API V1.
// Config attendue dans config/services.php (voir bloc à ajouter en bas de ce fichier),
// alimentée par les variables du .env (voir templates/env.template).

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PawaPayService
{
    private string $baseUrl;
    private string $apiToken;

    public function __construct()
    {
        $env = config('services.pawapay.env', 'sandbox');

        $this->baseUrl = $env === 'production'
            ? config('services.pawapay.base_url_production')
            : config('services.pawapay.base_url_sandbox');

        $this->apiToken = $env === 'production'
            ? config('services.pawapay.token_production')
            : config('services.pawapay.token_sandbox');

        if (empty($this->baseUrl) || empty($this->apiToken)) {
            throw new RuntimeException('Configuration pawaPay manquante : vérifier .env et config/services.php');
        }
    }

    private function client()
    {
        return Http::withToken($this->apiToken)
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson();
    }

    /**
     * Initier un deposit (encaisser de l'argent du client).
     *
     * @param string $amount montant en string, ex: "15.00"
     * @param string $currency ex: "USD"
     * @param string $country code ISO 3 lettres, ex: "COD"
     * @param string $correspondent ex: "ORANGE_COD"
     * @param string $msisdn numéro du client, ex: "243123456789"
     * @param string $statementDescription 4 à 22 caractères alphanumériques
     * @param array $metadata optionnel, format [['fieldName' => ..., 'fieldValue' => ..., 'isPII' => bool]]
     */
    public function initiateDeposit(
        string $amount,
        string $currency,
        string $country,
        string $correspondent,
        string $msisdn,
        string $statementDescription,
        array $metadata = []
    ): array {
        $depositId = (string) Str::uuid();

        $payload = [
            'depositId' => $depositId,
            'amount' => $amount,
            'currency' => $currency,
            'country' => $country,
            'correspondent' => $correspondent,
            'payer' => ['type' => 'MSISDN', 'address' => ['value' => $msisdn]],
            'customerTimestamp' => now()->toRfc3339String(),
            'statementDescription' => $statementDescription,
        ];

        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->client()->post('deposits', $payload)->throw();

        return ['depositId' => $depositId, 'result' => $response->json()];
    }

    /**
     * Initier un payout (envoyer de l'argent à un bénéficiaire).
     */
    public function initiatePayout(
        string $amount,
        string $currency,
        string $country,
        string $correspondent,
        string $msisdn,
        string $statementDescription,
        array $metadata = []
    ): array {
        $payoutId = (string) Str::uuid();

        $payload = [
            'payoutId' => $payoutId,
            'amount' => $amount,
            'currency' => $currency,
            'country' => $country,
            'correspondent' => $correspondent,
            'recipient' => ['type' => 'MSISDN', 'address' => ['value' => $msisdn]],
            'customerTimestamp' => now()->toRfc3339String(),
            'statementDescription' => $statementDescription,
        ];

        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->client()->post('payouts', $payload)->throw();

        return ['payoutId' => $payoutId, 'result' => $response->json()];
    }

    /**
     * Rembourser un deposit existant. Omettre $amount pour un remboursement total.
     */
    public function initiateRefund(string $depositId, ?string $amount = null, array $metadata = []): array
    {
        $refundId = (string) Str::uuid();

        $payload = ['refundId' => $refundId, 'depositId' => $depositId];

        if ($amount !== null) {
            $payload['amount'] = $amount;
        }
        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->client()->post('refunds', $payload)->throw();

        return ['refundId' => $refundId, 'result' => $response->json()];
    }

    public function checkDepositStatus(string $depositId): array
    {
        return $this->client()->get("deposits/{$depositId}")->throw()->json();
    }

    public function checkPayoutStatus(string $payoutId): array
    {
        return $this->client()->get("payouts/{$payoutId}")->throw()->json();
    }

    public function predictCorrespondent(string $msisdn): array
    {
        return $this->client()->get('predict-correspondent', ['msisdn' => $msisdn])->throw()->json();
    }
}

/*
 * === Bloc à ajouter dans config/services.php ===
 *
 * 'pawapay' => [
 *     'env' => env('PAWAPAY_ENV', 'sandbox'),
 *     'base_url_sandbox' => env('PAWAPAY_BASE_URL_SANDBOX', 'https://api.sandbox.pawapay.io/'),
 *     'base_url_production' => env('PAWAPAY_BASE_URL_PRODUCTION', 'https://api.pawapay.io/'),
 *     'token_sandbox' => env('PAWAPAY_API_TOKEN_SANDBOX'),
 *     'token_production' => env('PAWAPAY_API_TOKEN_PRODUCTION'),
 * ],
 */
