# Circuit de données et registre financier

Architecture recommandée, indépendante du langage et distincte du contrat PawaPay.

## Frontières et responsabilités

| Composant | Rôle et données |
|---|---|
| Web / mobile | Saisie, confirmation, affichage; token utilisateur uniquement |
| Backend authentifié | Autorisation, prix, devise, bénéficiaire, clés d'idempotence et validation |
| Base transactionnelle | Intention immuable, réservation, registre financier, inbox/outbox |
| Worker d'envoi | Appel PawaPay après commit, même identifiant lors des reprises |
| Callback public spécialisé | Vérification cryptographique, persistance inbox, HTTP 200 |
| Applicateur d'état | Corrélation et transaction atomique des effets |
| Rapprochement planifié | Recherche d'états manquants puis rapprochement avec relevés |

Le frontend lit une projection limitée de ses propres paiements. Seul le backend privilégié écrit le registre, les réservations et les états externes. Le callback ne peut pas choisir librement à qui créditer un solde par une métadonnée.

## Modèle minimal

- `payment_intents` : identifiant interne, tenant, propriétaire, objet métier, opération, environnement, ID PawaPay, montant attendu ou mode libre, devise, bénéficiaire figé, empreinte de la requête, état local et externe, dates et version.
- `idempotency_requests` : contrainte unique `(tenant, acteur, opération, clé client)`; empreinte du contenu et réponse persistée. Même clé + autre payload : conflit explicite. L'unicité PawaPay est séparée `(compte marchand, environnement, type, provider_id)`.
- `payment_attempts` : rattachement des deposits au checkout et à la commande; unicité de l'identifiant externe et de l'effet de crédit.
- `wallet_accounts` : un compte par tenant/propriétaire/devise; disponible et réservé, versionnés et contrôlés transactionnellement.
- `ledger_transactions` et `ledger_entries` : écritures immuables, équilibrées par devise, clé d'effet métier unique; toute correction passe par une écriture compensatrice, pas la modification d'un historique.
- `callback_inbox` : ID interne, ID transaction, type, empreinte, heure, résultat de vérification et état de traitement. Ne pas inventer un eventId fourni par PawaPay s'il n'existe pas.
- `outbox_jobs` : intention, charge utile minimale, date de reprise, tentatives, lease et diagnostic masqué.

Un hash identique permet de dédupliquer le transport, mais ne suffit pas à empêcher les doubles crédits entre deux événements différents. La contrainte d'effet financier doit être indépendante du corps du callback. Ne pas dédupliquer tous les états d'une opération par le seul ID : cela supprimerait le résultat final après une notification intermédiaire.

## Algorithme d'envoi

Dans une seule transaction, vérifier les droits et le solde, réserver, enregistrer l'intention et l'outbox. Commit. Un worker prend un lease, relit l'intention et appelle PawaPay. S'il redémarre après l'appel mais avant la sauvegarde de réponse, interroger le même ID. Une répétition technique utilise le même ID et le même contenu. Ne jamais maintenir un verrou de base pendant l'appel réseau.

Séparer une tentative inconnue d'une nouvelle tentative après échec confirmé. Pour un paiement hébergé encore actif, NOT_FOUND n'autorise pas de recréer immédiatement la transaction. Pour une intention directe, vérifier environnement, type, ID et absence de soumission encore concurrente avant de traiter NOT_FOUND comme une non-initiation. Borner les reprises et conserver un chemin de revue si l'état reste indéterminé.

## Algorithme d'application

Dans une transaction atomique, verrouiller l'intention et les comptes concernés, contrôler l'ID, le montant, la devise et le bénéficiaire par rapport au snapshot autorisé. Pour le montant libre, utiliser la valeur confirmée et la politique enregistrée. Comparer l'état reçu au précédent.

- État en attente après état final : conserver le final.
- Même résultat final déjà appliqué : aucun nouvel effet.
- Deux résultats finaux contradictoires : mettre en anomalie et reconsulter PawaPay, sans inversion automatique du registre.
- Succès : inscrire l'effet unique, mettre à jour le solde ou consommer la réservation, mettre à jour la commande et créer sa notification dans l'outbox.
- Échec définitif : libérer une réservation uniquement si elle existe et n'a pas déjà été consommée ou libérée.

Utiliser une clé d'effet partagée pour les notifications checkout et deposit d'un même paiement. Pour une commande fixe recevant deux paiements réussis distincts, livrer une fois et enregistrer l'excédent comme exception financière, sans ignorer l'argent reçu.

## Frais et rapprochement

Distinguer principal payé, frais PawaPay, commission de plateforme, montant promis au client et montant net marchand. Ne pas déduire les frais d'un champ amount isolé. Rapprocher les écritures avec les soldes et relevés par portefeuille/pays/devise et période. Les soldes utilisateur et marchand ne sont pas interchangeables. Conserver les corrections traçables.
