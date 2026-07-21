// webhook-route.js
// Route Express pour recevoir les callbacks pawaPay (deposit/payout/refund status).
// Monter avec : app.use('/webhooks/pawapay', require('./webhook-route'));

const express = require('express');
const router = express.Router();

// IMPORTANT : express.json() doit être configuré pour préserver le corps brut si vous
// vérifiez la signature RFC-9421 (le digest doit être calculé sur le corps EXACT reçu).
// Exemple : app.use('/webhooks/pawapay', express.raw({ type: 'application/json' }));
// puis parser manuellement JSON.parse(req.body) après vérification du digest.

router.post('/', async (req, res) => {
  const callback = req.body;

  // 1. Répondre vite (2xx) pour éviter un retry pawaPay, traiter le reste en tâche de fond
  //    si le traitement métier est long.
  res.status(200).send();

  // 2. Vérification signature RFC-9421 — voir references/signatures-rfc9421.md.
  //    Ne pas traiter le callback si la signature est activée côté Dashboard et invalide ici.

  // 3. Vérification anti-fraude minimale : comparer le montant reçu au montant attendu
  //    stocké lors de l'initiation du deposit, avant de créditer quoi que ce soit.
  if (callback.depositId) {
    // ex: comparer callback.depositedAmount à la valeur stockée en base pour ce depositId
    // if (callback.depositedAmount !== attenduPourCeDepositId) { alerter(); return; }
  }

  // 4. Traiter selon callback.status : COMPLETED, FAILED, etc.
  console.log('Callback pawaPay reçu :', callback);
});

module.exports = router;
