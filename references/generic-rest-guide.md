# Guide générique — adapter pawaPay à une stack non couverte par un template

pawaPay est une API REST/JSON classique authentifiée par Bearer token. Aucun SDK officiel
n'est garanti pour toutes les stacks — avant d'écrire du code brut, vérifier s'il existe un
SDK communautaire maintenu (ex: `katorymnd/pawa-pay-integration` pour PHP existe en dehors
de Laravel spécifiquement). Ne jamais affirmer qu'un SDK officiel existe sans l'avoir vérifié.

## Ce qui ne change JAMAIS entre les stacks

1. **Authentification** : header `Authorization: Bearer <token>` sur chaque appel.
2. **Base URL** : `https://api.sandbox.pawapay.io/` ou `https://api.pawapay.io/` selon
   l'environnement — seule variable qui change entre sandbox et prod avec le token.
3. **Endpoints** (API V1) :
   - `POST /deposits` — encaisser
   - `POST /payouts` — envoyer de l'argent
   - `POST /refunds` — rembourser
   - `GET /deposits/{depositId}` — statut d'un deposit
   - `GET /payouts/{payoutId}` — statut d'un payout
   - `GET /active-configuration` — correspondents/limites actifs sur le compte
   - `GET /predict-correspondent?msisdn=...` — deviner l'opérateur à partir d'un numéro
   - `GET /public-keys` — récupérer les clés publiques pawaPay (pour vérifier les callbacks signés)
4. **Format des montants** : toujours en string, jamais en nombre natif du langage (évite les
   problèmes d'arrondi flottant IEEE-754 qui n'ont pas leur place dans un paiement).
5. **Identifiants de transaction** : UUIDv4 générés côté client, un par tentative — jamais réutilisés.
6. **Nature asynchrone** : chaque appel POST retourne un statut immédiat (`ACCEPTED`, `ENQUEUED`,
   `REJECTED`), le statut final arrive par callback (configuré dans le Dashboard, pas dans l'API)
   ou par polling sur l'endpoint de statut.

## Étapes pour adapter à une stack quelconque (Python, Go, Java, Ruby, etc.)

1. Utiliser le client HTTP standard du langage (`requests`/`httpx` en Python, `net/http` en Go,
   `HttpClient`/`RestTemplate` en Java, `Net::HTTP`/`Faraday` en Ruby, etc.) — pas besoin de
   dépendance spécifique à pawaPay pour les cas simples sans signature RFC-9421.
2. Reproduire exactement les payloads JSON documentés dans `references/correspondents.md` et le
   corps de ce skill — ne pas inventer de champs supplémentaires.
3. Générer un UUIDv4 avec la bibliothèque standard du langage (`uuid` en Python, `github.com/google/uuid`
   en Go, `java.util.UUID` en Java, `SecureRandom.uuid` en Ruby).
4. Exposer une route/endpoint HTTP côté application pour recevoir les callbacks pawaPay, répondre
   vite en 2xx, traiter le montant reçu vs attendu avant de créditer quoi que ce soit.
5. Si signature RFC-9421 nécessaire : chercher une bibliothèque HTTP Message Signatures existante
   pour le langage (RFC-9421 est un standard IETF, pas propriétaire à pawaPay — des libs génériques
   existent dans plusieurs écosystèmes) plutôt que réimplémenter le calcul cryptographique à la main.

## Ce qu'il ne faut pas faire

- Ne pas générer un "SDK maison complet" avec pattern adaptateur multi-provider — pawaPay est déjà
  l'abstraction ; un simple module/service avec 3-4 fonctions (deposit, payout, refund, check status)
  suffit dans 95% des cas réels.
- Ne pas coder en dur les clés publiques pawaPay pour la vérification de signature — elles peuvent
  tourner, toujours les récupérer via l'endpoint `Public Keys`.
