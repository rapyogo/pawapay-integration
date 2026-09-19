---
name: pawapay
description: Concevoir, intégrer, auditer et dépanner PawaPay Merchant API v2, avec encaissements directs ou hébergés, payouts, retraits de solde applicatif, remboursements, callbacks signés et rapprochement. Utiliser pour les projets web ou mobiles, notamment Laravel/PHP, Next.js/React, Flutter/React Native, Firebase, PostgreSQL/Neon/Supabase, Vercel et Cloud Run.
---
# PawaPay : intégration sécurisée

Répondre dans la langue du demandeur. Adapter le code au dépôt existant. Ce skill est une méthode d'intégration, pas une certification de sécurité ni un SDK déjà testé en production.

## Démarrer

1. Inspecter les instructions du dépôt, les versions, l'authentification, les routes, le stockage, le modèle de solde, les queues et les intégrations existantes. Ne pas remplacer un flux fonctionnel sans nécessité.
2. Identifier l'opération, l'environnement, les pays/devises/opérateurs, le montant fixe ou libre, le propriétaire de la transaction et les droits nécessaires. Lire la configuration du projet avant de poser les seules questions bloquantes.
3. Lire [contrat API](references/api-contract.md), [sécurité](references/security.md) et les références conditionnelles ci-dessous. Vérifier les pages officielles pertinentes avant de coder. Les documents consultés et leurs empreintes figurent dans [sources](references/sources.md) et [inventaire](references/source-inventory.json). Ne pas charger toutes les sources à chaque tâche.
4. Distinguer les faits PawaPay, les choix d'architecture recommandés et les points non confirmés. En cas de contradiction documentaire, lire [écarts](references/known-gaps.md), isoler le comportement et le vérifier en sandbox. Ne pas fabriquer un endpoint, un champ, une limite, un SDK ou une garantie.
5. Implémenter la tranche demandée, ses migrations et ses tests utiles. Préserver les conventions du projet. Séparer les états externes, l'état métier et les écritures financières.
6. Vérifier les scénarios de [validation](references/testing-operations.md). Rapporter ce qui est effectivement testé, ce qui est seulement documenté, et les blocages restant avant production.

## Choisir la référence

- Encaissements directs, checkout, Payment Page, redirections et montants libres : [collecte](references/collections.md).
- Retraits, payouts, remboursements, remittances et split payments : [décaissements](references/disbursements.md).
- Circuit de données, réservations, idempotence, inbox/outbox et comptabilité : [données](references/data-ledger.md).
- Laravel/PHP, Next.js, mobile, bases et hébergement : [stacks](references/stacks.md).
- Déploiement, panne, audit et tests : [exploitation](references/testing-operations.md).
- Chargement depuis Claude Code, Codex, Antigravity, Hermes ou autre agent : [portabilité](references/agent-portability.md).

## Invariants à préserver

- Garder les secrets PawaPay et les clés privées exclusivement côté serveur. Ne jamais demander ni enregistrer le PIN Mobile Money. Un OTP de préautorisation documenté est distinct du PIN et ne doit pas être journalisé.
- Calculer ou valider le montant côté serveur. Vérifier l'identité, le tenant, les droits, la devise, le bénéficiaire et le solde disponible avant tout retrait.
- Enregistrer l'identifiant UUIDv4 et l'intention avant l'appel. Réutiliser le même identifiant pour une reprise technique de la même intention, sans changer son contenu.
- Ni HTTP 200, ni `ACCEPTED`, ni `DUPLICATE_IGNORED`, ni retour de navigateur ne prouvent le succès financier. Un timeout n'est pas un échec financier.
- Authentifier les callbacks et traiter callback, polling et reprise via le même applicateur transactionnel idempotent. Un événement ne doit produire qu'un seul effet financier.
- Ne pas assimiler le portefeuille marchand PawaPay aux soldes individuels de la plateforme. Le second exige son propre registre et des réservations atomiques.
- Ne jamais appeler PawaPay dans une fonction de transaction Firestore/PostgreSQL susceptible d'être réexécutée automatiquement. Utiliser une intention persistée et un worker.
- Ne pas créer un nouveau payout parce qu'un précédent est lent, `ENQUEUED`, `PROCESSING` ou `IN_RECONCILIATION`.
- Ne pas exécuter de mouvement d'argent réel, modifier des callbacks en production ou remplacer des secrets sur la seule base d'une demande de code. Respecter l'autorisation de la session et les restrictions de l'environnement. Ne jamais inclure un secret dans le dépôt ou les sorties.

## Actualiser le contrat

Utiliser `python scripts/inspect_openapi.py --file /chemin/openapi_v2.yaml` pour relever les chemins, champs obligatoires et états du schéma officiel téléchargé. Le script ne contacte aucune API financière et ne prouve pas le comportement réel. Comparer ses résultats aux guides et aux tests sandbox, notamment pour les écarts connus.
