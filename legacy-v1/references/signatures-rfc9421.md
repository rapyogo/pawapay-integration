# Signatures RFC-9421 (couche de sécurité optionnelle)

Ce n'est **pas** du HMAC. pawaPay implémente le standard **RFC-9421** (HTTP Message Signatures),
avec signature asymétrique (clé publique/privée). C'est optionnel mais recommandé pour les
requêtes financières (deposits, payouts, bulk payouts, refunds).

## Ce qu'il faut avant de commencer

1. Générer une paire de clés (ECDSA P-256 recommandé — le plus simple à mettre en œuvre).
2. Uploader la **clé publique** dans le Dashboard pawaPay (séparément pour sandbox et production —
   ce sont deux paires de clés différentes, jamais la même clé sur les deux environnements).
3. Activer "Only accept signed requests" dans le Dashboard.
4. Garder la **clé privée** uniquement côté serveur, jamais dans le `.env` commité, idéalement dans
   un secret manager (Vault, AWS Secrets Manager, ou au minimum variables d'environnement serveur
   non versionnées).

## Étapes techniques (pour requêtes sortantes : deposits/payouts/refunds)

1. **Content-Digest** : hasher le corps de la requête (SHA-256 ou SHA-512), header `Content-Digest`.
2. **Signature base** : construire la base à signer à partir des composants dérivés
   `@method`, `@authority`, `@path` + headers `Signature-Date`, `Content-Digest`, `Content-Type`.
3. **Signature** : signer la base avec la clé privée (ECDSA P-256, ECDSA P-384, RSA-PSS-SHA512,
   ou RSA-PKCS1-v1.5-SHA256).
4. **Headers finaux** à ajouter à la requête : `Signature`, `Signature-Input` (avec `alg`, `created`,
   `expires`, `keyid`), en plus de `Signature-Date` et `Content-Digest`.

Exemple officiel de code de signature en Node : https://github.com/PawaPay/signatures-node-example
— toujours partir de cet exemple officiel plutôt que de réimplémenter RFC-9421 depuis zéro à la main,
c'est une spec facile à implémenter légèrement de travers (ordre des composants, encodage du digest).

## Pour les callbacks entrants (vérifier que pawaPay est bien l'expéditeur)

1. pawaPay envoie les headers `Signature`, `Signature-Input`, `Signature-Date`, `Content-Digest`,
   `Content-Type` sur chaque callback (si "signed callbacks" est activé dans le Dashboard).
2. Récupérer la clé publique de pawaPay via l'endpoint `Public Keys` (elle peut tourner — ne pas
   la coder en dur, la refetcher ou la mettre en cache avec expiration).
3. Recalculer le digest du corps reçu et le comparer à `Content-Digest`.
4. Reconstruire la signature base à partir de `Signature-Input` et vérifier `Signature` avec la
   clé publique pawaPay.
5. Rejeter (HTTP 4xx, ne pas traiter) tout callback dont la signature ne correspond pas — ne jamais
   traiter un callback "au cas où" même si la signature échoue.

## Ce qui n'est PAS dans la documentation pawaPay (à ne pas inventer)

- Pas de rotation automatique de clé documentée — c'est une action manuelle via le Dashboard si
  compromission suspectée.
- Pas de mécanisme HMAC en parallèle de RFC-9421 — un seul mécanisme de signature existe.
