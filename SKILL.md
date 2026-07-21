---
name: pawapay-integration
description: >
  Guide complet et pas-à-pas pour intégrer les paiements Mobile Money via l'API pawaPay (Merchant API V1 - deposits, payouts, refunds, callbacks signés RFC-9421). À utiliser dès que l'utilisateur mentionne pawaPay, Mobile Money, Orange Money, MTN MoMo, Airtel Money, M-Pesa, dépôt/retrait mobile money, webhook de paiement mobile, ou veut configurer un fichier .env pour des paiements en Afrique. Détecte le langage/framework du projet (Node.js, Laravel/PHP, ou autre stack via guide générique REST), génère le .env documenté, le code de signature RFC-9421, les fonctions deposit/payout/refund, la vérification des callbacks, et une checklist de sécurité avant mise en production. Conçu pour être utilisable par des non-développeurs  - ne demande que le strict nécessaire et explique chaque étape en français simple.
---

# Intégration pawaPay (Mobile Money)

## Portée réelle de ce skill (important)

pawaPay est un **agrégateur unique** de Mobile Money Operators (MMO) en Afrique. Cela veut dire :
- Il n'y a **pas** d'intégration séparée à faire pour Orange Money, MTN MoMo, Airtel Money, M-Pesa, Moov, etc. Tous sont déjà accessibles via **une seule API pawaPay**, en spécifiant un `correspondent` (ex: `ORANGE_COD`, `AIRTEL_COD`, `VODACOM_MPESA_COD` pour la RDC).
- Ajouter un "nouveau fournisseur" = ajouter une constante `correspondent` + vérifier son pays/devise/décimales dans `references/correspondents.md`. Ce n'est jamais une nouvelle intégration technique.
- Ne jamais proposer de "pattern adaptateur multi-provider" pour swap pawaPay avec un autre agrégateur, sauf si l'utilisateur le demande explicitement pour une vraie redondance business (deux comptes marchands différents).

**Version de l'API** : ce skill couvre l'API **V1** (celle documentée sur docs.pawapay.io/using_the_api, avec `correspondent`, `payer`/`recipient.address.value`, `customerTimestamp`, `statementDescription`). pawaPay a une **V2** qui renomme des champs (`correspondent`→`provider`, `MSISDN`→`MMO`, `address.value`→`accountDetails.phoneNumber`, `statementDescription`→`customerMessage`, suppression de `customerTimestamp`). **Avant de générer du code, demande à l'utilisateur quelle version son compte utilise** (visible dans le Dashboard pawaPay ou dans la doc reçue de leur contact commercial). Ne jamais mélanger les deux formats.

## Étape 1 — Comprendre le besoin réel

Pose ces questions (une à la fois, langage simple) :
1. Le projet existe déjà, ou c'est un nouveau projet ? → si existant, demander le chemin/langage.
2. Quelles opérations sont nécessaires : encaisser de l'argent (**deposit**), envoyer de l'argent à quelqu'un (**payout**), et/ou rembourser (**refund**) ?
3. Sandbox ou déjà en production ?
4. Pays et opérateur(s) concernés (ex: RDC → Orange, Airtel, Vodacom M-Pesa) ?

## Étape 2 — Détecter le langage/framework

Si un projet existe, inspecte-le (package.json, composer.json, requirements.txt, etc.) pour détecter la stack. Sinon, demande.

- **Node.js / Express** → utiliser `templates/node-express/`
- **PHP / Laravel** → utiliser `templates/php-laravel/`
- **Toute autre stack** (Python/Django/Flask, Java/Spring, Go, Ruby, .NET, etc.) → suivre `references/generic-rest-guide.md` et adapter le code pawaPay (qui est un simple appel HTTP JSON) aux conventions de la stack détectée. Ne jamais inventer un SDK officiel pawaPay pour un langage qui n'en a pas — vérifier d'abord s'il existe un SDK communautaire avant d'écrire du code brut.

## Étape 3 — Générer le fichier .env

Demande UNIQUEMENT ce qui est nécessaire selon les opérations choisies à l'étape 1, puis génère un `.env` commenté à partir de `templates/env.template`. Ne jamais écrire de vraie clé dans le code source — uniquement des placeholders dans `.env.example`, jamais dans `.env` lui-même (qui doit être gitignoré).

## Étape 4 — Générer le code d'intégration

Utiliser le template correspondant à la stack. Chaque template couvre :
- Client HTTP configuré (base URL sandbox/production depuis l'env)
- Génération de `depositId`/`payoutId`/`refundId` en UUIDv4
- Fonction deposit, payout, refund (selon besoin réel de l'utilisateur — ne pas générer ce qui n'est pas demandé)
- Vérification de statut (polling) en fallback si pas de callback configuré
- Réception et validation de callback (signature RFC-9421 si activée, sinon a minima vérification de l'IP source — voir `references/security-checklist.md`)

Si l'utilisateur veut la signature RFC-9421 des requêtes sortantes (couche de sécurité optionnelle mais recommandée), lire `references/signatures-rfc9421.md` avant de générer ce code — c'est la partie la plus technique et la plus facile à mal implémenter.

## Étape 5 — Vérifier avant production

Avant de dire "c'est prêt", parcourir `references/security-checklist.md` avec l'utilisateur point par point et confirmer chaque case. Vérifier en particulier :
- Le `.env` réel n'est jamais commité (vérifier `.gitignore`)
- Les montants sont bien des strings avec le bon nombre de décimales pour le correspondent choisi (voir `references/correspondents.md` — ex: RDC en CDF supporte 2 décimales pour Airtel/Orange mais PAS pour Vodacom M-Pesa)
- Le montant du callback (`depositedAmount`) est comparé au montant demandé avant de considérer la transaction comme fiable (protection contre `AMOUNT_DISCREPANCY`)
- Les IP de callback pawaPay sont whitelistées côté firewall si pertinent
- Test réel en sandbox avant bascule production (token différent, base URL différente — RIEN d'autre ne change)

## Erreurs fréquentes à expliquer simplement

- **"depositId déjà utilisé"** → l'API est idempotente par design ; réutiliser un UUID générera le même résultat que la première tentative, jamais une erreur silencieuse. Toujours générer un nouvel UUIDv4 par tentative.
- **Montant rejeté** → décimales non supportées par ce correspondent précis (voir table pays dans `references/correspondents.md`).
- **Pas de callback reçu** → vérifier que l'URL de callback est configurée dans le Dashboard pawaPay (ce n'est pas un paramètre de l'API, ça se configure dans le Dashboard) et que le firewall autorise les IP pawaPay listées dans `references/security-checklist.md`.
- **Signature invalide** → horloge serveur désynchronisée (RFC-9421 utilise un horodatage avec expiration courte) ou mauvaise clé publique/privée entre sandbox et production (chaque environnement a sa propre paire de clés).

## Fichiers de référence

- `references/correspondents.md` — table complète des correspondents par pays (source : docs.pawapay.io/using_the_api)
- `references/signatures-rfc9421.md` — détail de l'implémentation des signatures
- `references/security-checklist.md` — checklist de mise en production réaliste (sans PCI DSS ni HMAC inventés)
- `references/generic-rest-guide.md` — guide d'adaptation pour toute stack non couverte par un template
- `templates/node-express/` — intégration Node.js complète
- `templates/php-laravel/` — intégration Laravel complète
- `templates/env.template` — modèle de .env documenté
