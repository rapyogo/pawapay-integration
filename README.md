# PawaPay Integration, API v2

Skill réutilisable pour guider un agent de code dans une intégration sécurisée de PawaPay. Il contient des instructions, des références et un outil d’inspection du contrat, pas un SDK prêt pour la production.

## Utilisation

Cloner ce dépôt puis demander à votre agent :

> Lis SKILL.md, inspecte mon projet et utilise ses références pour intégrer le flux PawaPay demandé. Vérifie le contrat officiel actuel et implémente les contrôles et tests nécessaires.

Pour les agents qui prennent en charge les Agent Skills, installer le dossier selon la documentation de leur version. Le nom du skill est `pawapay`. Pour Claude Code, Codex, Antigravity, Hermes Agent et les autres outils, consulter [les consignes de portabilité](references/agent-portability.md). La lecture explicite du fichier reste possible sans découverte automatique.

## Contenu

- [SKILL.md](SKILL.md) : point d’entrée et invariants.
- [Contrat API v2](references/api-contract.md) : routes, données et états.
- [Encaissements](references/collections.md) : deposit, Checkout, Payment Page et redirections.
- [Décaissements](references/disbursements.md) : payouts, retraits et remboursements.
- [Sécurité](references/security.md) : secrets, signatures HTTP et callbacks.
- [Données et registre financier](references/data-ledger.md) : réservations, idempotence et rapprochement.
- [Stacks](references/stacks.md) : Laravel/PHP, Next.js/React, Flutter/React Native, Firebase, PostgreSQL/Neon/Supabase, Vercel et Cloud Run.
- [Tests et exploitation](references/testing-operations.md) : scénarios de panne et conditions de mise en production.
- [Écarts documentaires](references/known-gaps.md) : points nécessitant une validation ciblée.
- [Sources](references/sources.md) : inventaire documentaire daté.

## Inspection du schéma

Le script nécessite Python 3 et PyYAML. Télécharger la spécification OpenAPI officielle dans un fichier local, puis exécuter :

```sh
python scripts/inspect_openapi.py --file /chemin/openapi_v2.yaml
```

Le script n’effectue aucun appel réseau ni paiement. Il extrait la structure; il ne valide pas le comportement réel du fournisseur.

## Ancienne version

Les anciennes instructions et les templates API v1 sont conservés dans [legacy-v1](legacy-v1/README.md). Ils ne sont pas compatibles avec les payloads v2 et ne sont pas validés par cette mise à jour. Ne pas les utiliser comme implémentation v2. Les anciens packages de release, s’ils existent, ne sont pas mis à jour par cette modification du dépôt.

## Vérifications et limites

Skill validé structurellement, liens relatifs contrôlés et script exercé sur la spécification officielle. Scénario de raisonnement évalué pour la concurrence des retraits et des callbacks. Aucun paiement réel, test marchand sandbox ou audit de production effectué.

Documentation étudiée le 19 septembre 2026 : les capacités, limites et contrats doivent être revérifiés avant intégration. Le dépôt ne contient pas les manuels officiels complets et n’est pas affilié à PawaPay.

Ne jamais publier de token, clé privée ou données client. Consulter la [documentation officielle](https://docs.pawapay.io/v2/docs/welcome).

## Licence

MIT. Voir [LICENSE](LICENSE).
