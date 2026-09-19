# Sécurité des échanges et des opérations

Les mécanismes de signature ci-dessous sont documentés par PawaPay. Les contrôles métier, l'inbox/outbox et les politiques de rejeu sont des recommandations d'architecture, à adapter et tester.

## Secrets et authentification

Conserver token et clé privée dans le gestionnaire de secrets du backend, jamais dans le frontend, le binaire mobile, une variable publique, Git ou les traces. Restreindre le compte de service et les rôles de production. Séparer les environnements et faire échouer le démarrage si l'hôte et les secrets ne correspondent pas.

Prévoir rotation et révocation testées. Le dashboard documente au maximum deux tokens actifs : organiser une transition contrôlée plutôt qu'une révocation prématurée. Restreindre les rôles techniques et financiers et activer l'authentification forte disponible. Ne pas générer de secrets réels dans un exemple.
Source : https://docs.pawapay.io/dashboard/other/system_conf/api_tokens

## Signatures HTTP

PawaPay utilise RFC 9421, pas un HMAC webhook inventé. Activer séparément les requêtes signées et les callbacks signés dans le dashboard. Les endpoints concernés et leur configuration doivent être vérifiés avant activation en production.

Pour signer une requête, sérialiser le JSON une seule fois, calculer Content-Digest sur les octets réellement envoyés, puis construire la base de signature avec les composants signés dans leur ordre. Le guide recommande @method, @authority, @path, Signature-Date, Content-Digest et Content-Type. Ajouter Signature et Signature-Input. La clé publique marchande est enregistrée chez PawaPay; la clé privée reste au serveur.

Pour vérifier un callback :

1. Capturer le corps brut avant toute transformation JSON, fixer une limite de taille et lire les en-têtes sans perte.
2. Parser les Structured Fields de Signature-Input et Signature avec une implémentation RFC 9421 maintenue. Ne pas improviser un split sur les virgules.
3. Recalculer le digest sur les octets bruts et le comparer; le header Content-Digest seul n'authentifie rien.
4. Résoudre keyid avec les clés publiques de `/v2/public-key/http` sur l'hôte PawaPay configuré. Ne jamais suivre une URL de clé fournie par le callback. Mettre en cache et autoriser un rafraîchissement borné sur une clé inconnue.
5. Vérifier signature, algorithme autorisé, composants requis et horodatages. Reconstituer autorité et chemin publics correctement derrière le proxy; ne pas faire confiance à des headers forwarded provenant de n'importe quel client.
6. Définir une tolérance d'horloge et une politique de rejeu compatibles avec les retries et renvois manuels. `created` et `expires` sont les paramètres temporels RFC de la signature; ne pas confondre expiration de signature et expiration de clé malgré un libellé ambigu du guide.
7. Seulement après validation, parser le métier, rapprocher l'ID et appliquer la transition.

Le guide accepte SHA-256/SHA-512 pour le digest et RSA-PSS SHA-512, RSA PKCS1 v1.5 SHA-256, ECDSA P-256 SHA-256, ECDSA P-384 SHA-384 pour les signatures. Utiliser l'algorithme réellement négocié, une bibliothèque éprouvée et des vecteurs officiels; tester notamment l'encodage ECDSA. Ne pas annoncer qu'un vérificateur est prêt pour production sans ces tests.
Sources : https://docs.pawapay.io/v2/docs/signatures ; https://github.com/PawaPay/signatures-node-example ; https://www.rfc-editor.org/rfc/rfc9421

## Réception et défense en profondeur

Le callback doit être accessible en HTTPS avec certificat valide. Exempter uniquement sa route des mécanismes incompatibles d'authentification utilisateur/CSRF, puis lui appliquer l'authentification PawaPay. Ne pas désactiver globalement CSRF, l'authentification ou la vérification TLS.

Après vérification, persister durablement l'événement avant HTTP 200. En cas d'échec de persistance, ne pas accuser réception comme si le traitement était garanti. Renvoyer 200 pour un doublon déjà enregistré et valide. La livraison est réessayée pendant 15 minutes selon le guide; la reprise au-delà dépend du polling et du renvoi explicite.

Ne pas créditer à partir d'un callback non authentifié. Si les signatures ne sont pas disponibles dans le mode choisi, traiter le callback comme un signal non fiable et relire la transaction avec le token serveur avant tout effet financier. Ne pas dégrader automatiquement cette politique lorsqu'une signature échoue. L'allowlist IP officielle est une défense supplémentaire, pas une preuve métier ni un remplacement de la vérification.
Source : https://docs.pawapay.io/v2/docs/what_to_know

## Contrôles applicatifs

Vérifier propriétaire et tenant sur initiation, statut, export, remboursement et retrait. Appliquer limitation de débit par acteur et par opération; les UUID ne sont pas des autorisations. Calculer le prix serveur, borner le montant libre et limiter les destinations autorisées.

Les URLs de retour proviennent d'une configuration autorisée, pas directement d'un champ utilisateur. Ne pas accepter d'open redirect ni récupérer une URL privée arbitraire (SSRF). Masquer tokens, OTP, clés, numéros et URLs sensibles dans les logs. Minimiser les données personnelles en base, restreindre l'accès et définir leur rétention. Le drapeau isPII ne chiffre pas la donnée et ne remplace pas ces contrôles.

Tester accès interutilisateurs, rejeu, double soumission concurrente, callbacks altérés, montant/devise/bénéficiaire modifiés et élévation de privilèges. Les règles de pays, d'éligibilité et les conditions marchandes restent à vérifier avec le fournisseur; ce skill ne constitue pas un avis juridique.
