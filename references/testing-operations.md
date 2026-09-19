# Validation et exploitation

## Matrice de tests

Exécuter les tests dans une base isolée et l'environnement sandbox autorisé. Ne pas utiliser un véritable numéro de client pour un scénario d'échec simulé. Les numéros officiels varient par opérateur : les lire dans https://docs.pawapay.io/v2/docs/test_numbers plutôt que les inventer. Le sandbox ne reproduit pas les prompts PIN réels.

| Scénario | Résultat attendu |
|---|---|
| Succès direct ou hébergé | Une seule écriture et une seule livraison |
| Même clé métier, appels simultanés | Une intention, une réservation, un ID externe |
| Même clé, autre bénéficiaire/montant | Rejet en conflit, aucun appel financier |
| Callback répété et polling concurrent | Effet unique malgré plusieurs arrivées |
| Callback deposit et checkout | Un seul crédit pour le dépôt concerné |
| Corps, signature, date ou keyid falsifiés | Pas d'effet financier |
| Callback tardif ou hors ordre | Pas de régression d'un état final |
| Timeout après acceptation distante | Même ID, solde réservé, rapprochement |
| Crash après appel avant sauvegarde | Reprise sans nouveau paiement |
| Crash entre base et queue | Outbox récupérée, pas de travail perdu |
| Deux retraits excédant ensemble le solde | Un refus atomique, pas de solde négatif |
| Payout en file puis annulation | Réservation maintenue jusqu'à échec confirmé |
| Remboursements partiels concurrents | Total engagé inférieur ou égal au remboursable |
| Retour navigateur forgé | Aucune confirmation financière |
| Session abandonnée | Pas de crédit, suivi de l'expiration pertinente |
| Checkout : tentative échouée puis réussie | Checkout suivi, réussite appliquée une fois |
| Mauvaise devise ou prix modifié | Rejet ou anomalie, aucune livraison indue |
| Accès au paiement d'un autre utilisateur | Refus côté serveur et règles de base |
| Worker relancé / transaction rejouée | Aucun appel externe dans le retry transactionnel |
| Signature via proxy et rotation de clé | Vérification correcte des octets et de la cible publique |

Tester aussi les états inconnus, 401/403, rejet HTTP 200, 429 si rencontré, 5xx, indisponibilité opérateur, manque de fonds marchand, minimum/maximum et décimales. Un échec technique n'est pas automatiquement un échec métier.

## Reprise et incidents

Planifier la lecture des opérations non terminales avec backoff, jitter et budget borné. Le guide propose des contrôles des opérations âgées de plus de 15 minutes; définir les paramètres selon le flux et le volume, sans multiplier les appels en boucle. Conserver IN_RECONCILIATION et ENQUEUED jusqu'au résultat confirmé. Les callbacks perdus peuvent être renvoyés si l'opération est finale.

Suivre âge des opérations, callbacks refusés, retard inbox/outbox, échecs de signature, erreurs API, différences comptables et fonds marchands insuffisants. Journaliser IDs et codes, pas les secrets. Face à une suspicion de doublon, suspendre l'envoi concerné, relire les IDs et rapprocher avant toute correction.

## Rapprochement financier

Lister les portefeuilles puis demander les relevés nécessaires avec pays/devise et opérateur quand applicable. La période API est limitée à 31 jours, le lien de téléchargement dure un jour et les données du jour peuvent avoir jusqu'à 20 minutes de retard. Télécharger côté serveur dans une destination autorisée, contrôler les URL et la taille, protéger le CSV et neutraliser les formules lors de son export utilisateur.
Source : https://docs.pawapay.io/v2/api-reference/finances/initiate-statement

Le dashboard facilite recherche, preuve de paiement, renvoi de callback, annulation en file, rôles, audit, alimentation du portefeuille et règlements. Ne pas déduire des libellés dashboard anciens le contrat JSON v2.

## Avant production

Vérifier les capacités actives du compte, les URLs publiques, secrets, signatures dans les deux sens, proxy, accès aux clés publiques, workers, reprise, règles d'accès et restauration des données. Préserver les callbacks pendant un rollback même si l'initiation de nouveaux paiements est coupée.

Lancer les tests réels seulement avec une autorisation explicite couvrant environnement, bénéficiaire/payeur et montant, selon les règles de l'environnement d'agent. Prévoir un feature flag et une observation contrôlée. Ne pas qualifier l'intégration de certifiée ou auditée par PawaPay sans preuve.
Source : https://docs.pawapay.io/v2/docs/going_live

## Vérification du paquet lors de sa création

Validation structurelle du skill et de ses liens relatifs effectuée. Script exécuté sur la spécification récupérée : 30 opérations extraites; héritage des champs obligatoires payout, séparation des états checkout et rejet des références externes vérifiés. Un avertissement a identifié le paramètre statementId mal référencé dans OpenAPI.

Une évaluation indépendante du skill sur un scénario Flutter/Firebase a produit les réservations atomiques, la reprise avec le même ID, la déduplication checkout/deposit et la remontée des deux contradictions documentaires ciblées. C’est une vérification de raisonnement, pas un test applicatif ni une preuve de sécurité en production. Aucun paiement effectué.
