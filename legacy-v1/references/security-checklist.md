# Checklist sécurité avant mise en production — pawaPay

Cette checklist ne reprend QUE ce qui est réellement applicable à une intégration Mobile Money
pawaPay. Volontairement, elle **exclut** :
- PCI DSS (aucune donnée de carte bancaire ne transite via pawaPay — c'est du Mobile Money,
  authentifié par PIN opérateur sur le téléphone du client, pas par pawaPay ni par vous).
- HMAC (pawaPay utilise RFC-9421, voir `signatures-rfc9421.md`).
- "Rotation de secrets automatique" (non documentée côté pawaPay — process manuel).

## Configuration et secrets

- [ ] Le token API sandbox et le token API production sont deux valeurs différentes, stockées
      séparément (jamais le même `.env` pour les deux environnements).
- [ ] `.env` est listé dans `.gitignore` ; seul `.env.example` (sans vraies valeurs) est versionné.
- [ ] Aucune clé/token n'apparaît en clair dans les logs applicatifs.
- [ ] Le passage sandbox → production ne change QUE la base URL et le token — si le code change
      autre chose entre les deux, c'est un signe d'erreur de conception.

## Requêtes sortantes (deposits/payouts/refunds)

- [ ] Chaque transaction a un `depositId`/`payoutId`/`refundId` en UUIDv4 généré côté client,
      jamais réutilisé volontairement (l'idempotence de l'API protège contre les doublons accidentels,
      pas contre une mauvaise gestion des retries).
- [ ] `amount` est envoyé en string, avec le nombre de décimales autorisé pour le correspondent
      exact utilisé (voir `correspondents.md`).
- [ ] `statementDescription` respecte la contrainte de 4 à 22 caractères alphanumériques.
- [ ] Le numéro MSISDN saisi par l'utilisateur final est validé (format, longueur) avant l'envoi —
      envisager d'utiliser `predict-correspondent` pour confirmer l'opérateur avant de router.

## Callbacks entrants

- [ ] L'URL de callback est configurée dans le Dashboard pawaPay (sandbox et production
      séparément).
- [ ] Les IP pawaPay sont whitelistées au niveau du firewall si l'infrastructure le permet :
      Sandbox `3.64.89.224/32` ; Production `18.192.208.15/32`, `18.195.113.136/32`,
      `3.72.212.107/32`, `54.73.125.42/32`, `54.155.38.214/32`, `54.73.130.113/32`.
- [ ] Si "signed callbacks" est activé : la signature RFC-9421 est vérifiée avant tout traitement
      métier du callback (voir `signatures-rfc9421.md`).
- [ ] Le montant du callback (`depositedAmount`) est comparé au montant demandé — un écart
      déclenche une alerte manuelle plutôt qu'une validation automatique de la transaction
      (protection contre le cas `AMOUNT_DISCREPANCY` documenté par pawaPay lui-même).
- [ ] Le endpoint de callback répond rapidement (2xx) même si le traitement métier est différé
      en file d'attente — un callback qui timeout côté pawaPay peut être renvoyé.

## Robustesse opérationnelle

- [ ] Si aucun callback n'est reçu dans un délai raisonnable, un mécanisme de polling sur
      "Check Deposit/Payout Status" prend le relais (l'API est asynchrone par nature).
- [ ] Les limites de transaction (min/max par MMO, plafonds journaliers/hebdo/mensuels des wallets)
      sont vérifiées via l'endpoint `active-configuration` plutôt que codées en dur — elles peuvent
      changer.
- [ ] Un plan de reconciliation existe pour les cas où un MMO est en dégradation temporaire
      (pawaPay met en file les payouts et les traite quand l'opérateur redevient disponible —
      ce n'est pas une erreur applicative à traiter comme un échec).

## Avant le premier vrai paiement en production

- [ ] Un test complet (deposit + callback, ou payout + callback) a été exécuté en sandbox avec
      succès de bout en bout.
- [ ] Le compte production est bien onboardé (accès production accordé après onboarding sandbox
      complété, selon le process pawaPay).
- [ ] Une personne humaine (pas seulement un log) est notifiée en cas d'échec de callback ou de
      transaction rejetée, au moins pendant les premières semaines de production.
