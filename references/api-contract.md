# Contrat PawaPay v2

Relevé documentaire : 19 septembre 2026. Revalider les détails susceptibles d'évoluer avant chaque intégration.

## Transport et identité

Utiliser HTTPS, JSON et `Authorization: Bearer <secret serveur>`. Les hôtes sont `https://api.sandbox.pawapay.io` et `https://api.pawapay.io`, suivis des chemins v2. Séparer identifiants, secrets, données, callbacks et clés de signature par environnement.
Source : https://docs.pawapay.io/v2/docs/how_to_start

## Cartographie des endpoints

| Intention | Méthode et chemin |
|---|---|
| Encaisser directement | POST /v2/deposits |
| Lire un encaissement | GET /v2/deposits/{depositId} |
| Renvoyer son callback | POST /v2/deposits/resend-callback/{depositId} |
| Créer un checkout | POST /v2/checkouts |
| Lire un checkout | GET /v2/checkouts/{checkoutId} |
| Expirer un checkout | POST /v2/checkouts/{checkoutId}/expire |
| Créer une Payment Page | POST /v2/paymentpage |
| Initier un payout | POST /v2/payouts |
| Initier un lot | POST /v2/payouts/bulk |
| Lire un payout | GET /v2/payouts/{payoutId} |
| Renvoyer son callback | POST /v2/payouts/resend-callback/{payoutId} |
| Demander son annulation en file | POST /v2/payouts/fail-enqueued/{payoutId} |
| Initier un remboursement | POST /v2/refunds |
| Lire un remboursement | GET /v2/refunds/{refundId} |
| Renvoyer son callback | POST /v2/refunds/resend-callback/{refundId} |
| Annuler un remboursement en file | POST /v2/refunds/fail-enqueued/{refundId} |
| Remittance | POST /v2/remittances |
| Lire une remittance | GET /v2/remittances/{remittanceId} |
| Callback et annulation remittance | POST /v2/remittances/resend-callback/{remittanceId}, POST /v2/remittances/fail-enqueued/{remittanceId} |
| Soldes marchands | GET /v2/wallet-balances |
| Demander un relevé | POST /v2/statements |
| Lire son état | GET /v2/statements/{statementId} |
| Configuration du compte | GET /v2/active-conf |
| Disponibilité opérateurs | GET /v2/availability |
| Normaliser/prédire un opérateur | POST /v2/predict-provider |
| Clés publiques des callbacks | GET /v2/public-key/http |

Source des chemins : https://docs.pawapay.io/v2/api-reference/openapi_v2.yaml
Le schéma expose aussi `/v2/split-payments`, sa lecture par ID et `/v2/remittances/bulk`, absents de l'index des guides consulté. Leur présence n'établit pas leur activation sur le compte.

## Données minimales

Pour un deposit : `depositId`, `amount` sous forme de chaîne décimale, `currency`, `payer.type = MMO`, `payer.accountDetails.phoneNumber`, `payer.accountDetails.provider`.
Pour un payout : même forme, avec `payoutId` et `recipient` à la place du payeur.
Pour `predict-provider` : `phoneNumber`; utiliser le numéro normalisé retourné. L'opérateur prédit reste corrigeable par l'utilisateur. Ne pas sélectionner arbitrairement le premier opérateur.

Valider pays, devise, disponibilité par opération, `minAmount`, `maxAmount`, `decimalsInAmount` sur la configuration active du compte. `NONE` et `TWO_PLACES` sont les options documentées de précision opérateur. Ne pas déduire l'activation d'un opérateur de sa seule présence dans la liste publique. Ne pas convertir implicitement CDF en USD ou l'inverse.

Représenter l'argent par décimaux exacts ou unités entières avec échelle explicite. Ne pas utiliser les flottants binaires. Normaliser la représentation sans changer la valeur. Le schéma Amount et ses exemples divergent sur certains zéros finaux : voir les écarts. Rejeter les fractions incompatibles, ou appliquer une règle métier explicite affichée avant confirmation, jamais un arrondi silencieux.

`customerMessage` : 4 à 22 caractères alphanumériques ASCII ou espaces selon le schéma. `clientReferenceId` sert au rapprochement, pas de clé d'idempotence de l'API. Les métadonnées de requête utilisent une liste d'objets, avec `isPII` pour les données personnelles; minimiser ces données. La forme de réponse peut différer. Ne pas recopier les exemples de schéma malformés aveuglément.
Sources : pages initiate-deposit, initiate-payout, active-configuration, predict-provider de l'inventaire.

## Trois niveaux d'état

| Niveau | Valeurs et traitement |
|---|---|
| Initiation deposit/payout/refund/checkout | ACCEPTED : suivre; REJECTED : examiner failureReason; DUPLICATE_IGNORED : relire l'intention existante |
| Recherche de transaction | FOUND avec data; NOT_FOUND sans preuve de succès. Ne pas confondre status de l'enveloppe et data.status |
| Deposit | ACCEPTED, PROCESSING, IN_RECONCILIATION, COMPLETED, FAILED |
| Payout / refund | ACCEPTED, ENQUEUED, PROCESSING, IN_RECONCILIATION, COMPLETED, FAILED |
| Checkout | WAITING_PAYMENT, PROCESSING, COMPLETED, FAILED, EXPIRED, CANCELLED |

Pour deposit/payout/refund, seuls COMPLETED et FAILED sont terminaux. Pour checkout, les quatre derniers états sont terminaux; la réconciliation d'une tentative reste présentée comme PROCESSING. Conserver un état interne d'incertitude pour erreurs réseau ou réponse inconnue, sans inventer un statut PawaPay. Accepter les champs de réponse supplémentaires, mais ne jamais interpréter un état inconnu comme un succès.
