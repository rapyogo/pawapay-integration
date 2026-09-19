<?php
// app/Http/Controllers/PawaPayCallbackController.php
// Reçoit les callbacks pawaPay (deposit/payout/refund status).
// Route à ajouter dans routes/web.php ou routes/api.php :
// Route::post('/webhooks/pawapay', [PawaPayCallbackController::class, 'handle'])
//     ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]); // route externe, pas de CSRF token

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PawaPayCallbackController extends Controller
{
    public function handle(Request $request): Response
    {
        // 1. Vérification signature RFC-9421 ici si "signed callbacks" est activé côté
        //    Dashboard pawaPay — voir references/signatures-rfc9421.md. Utiliser
        //    $request->getContent() (corps brut) pour le calcul du digest, pas $request->all()
        //    qui a déjà été parsé.

        $callback = $request->all();

        // 2. Vérification anti-fraude minimale avant tout traitement métier :
        //    comparer le montant reçu (depositedAmount) au montant attendu stocké lors
        //    de l'initiation, pour ce depositId précis.
        if (isset($callback['depositId'])) {
            // $attendu = Deposit::where('deposit_id', $callback['depositId'])->value('requested_amount');
            // if ($callback['depositedAmount'] !== $attendu) { /* alerter, ne pas créditer */ }
        }

        Log::info('Callback pawaPay reçu', $callback);

        // 3. Traiter selon $callback['status'] : COMPLETED, FAILED, etc.

        // Répondre 200 rapidement — déléguer le traitement long à une Queue Job si besoin.
        return response()->noContent();
    }
}
