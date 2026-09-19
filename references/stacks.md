# Adaptations aux stacks

Ces modèles sont des recommandations d'implémentation. Vérifier les versions installées et leur documentation avant d'écrire le code. Ne pas imposer un changement de framework.

## Laravel et PHP

Isoler un client HTTP serveur et un service métier. Utiliser validation de requête, policies, transactions SQL, contraintes uniques et verrouillage des comptes. Lire le corps brut (`getContent()` ou `php://input` selon le framework) avant vérification du callback. Exempter seulement cette route du CSRF utilisateur. Garder secrets et clé privée hors de public et des vues.

Envoyer les jobs après commit et prévoir une outbox pour couvrir un crash entre commit et publication. Configurer timeout, retries, backoff et workers selon l'hébergement. `afterCommit` évite l'envoi avant commit mais ne remplace pas à lui seul une outbox durable.
Source : https://laravel.com/framework/docs/queues

## Next.js / React

Placer le client PawaPay, l'accès privilégié à la base et les secrets dans des modules serveur. Utiliser `server-only` là où pertinent; aucune variable NEXT_PUBLIC ne doit contenir de secret. Vérifier authentification et autorisation dans chaque Route Handler ou Server Action, indépendamment de la visibilité des boutons.

Consommer le corps du webhook une seule fois en octets, vérifier, puis parser. Choisir un runtime compatible avec la bibliothèque de signature. Ne pas supposer que toutes les bibliothèques Node fonctionnent dans un runtime edge.
Source : https://nextjs.org/docs/app/guides/data-security

## Flutter et React Native

Les applications utilisent votre API, jamais le token marchand. Vérifier le token de connexion côté backend. Conserver l'ID d'intention pour reprendre après fermeture, et demander l'état serveur à la reprise. Pour un lien de retour mobile, valider la navigation et l'accès à la commande. Ne pas stocker un succès définitif uniquement dans le state local. Les notifications push sont informatives, pas des preuves comptables.

## Firebase / Firestore

Utiliser un endpoint HTTPS pour les callbacks externes et un backend authentifié pour les demandes client. Vérifier Firebase ID token côté serveur; App Check, si employé, complète l'authentification sans la remplacer. Les règles Firestore empêchent les clients d'écrire soldes, registre, statuts PawaPay et outbox. Les accès Admin exigent leurs propres contrôles d'autorisation.

Une transaction Firestore peut être réexécutée : ne jamais y appeler l'API externe. Y enregistrer uniquement les documents locaux et l'outbox avec des IDs déterministes et contraintes via transactions. Déclencheurs, queues et applicateurs doivent supporter les doublons. Lire les documents nécessaires avant les écritures et contrôler la contention sur les comptes.
Source : https://firebase.google.com/docs/firestore/manage-data/transactions

## PostgreSQL, Neon et Supabase

Utiliser des montants NUMERIC ou des unités entières avec échelle, des index uniques et une vraie transaction pour la réservation et les effets. Deux UPDATE séparés par l'API ne constituent pas une transaction. Employer une connexion transactionnelle ou une fonction SQL contrôlée; verrouiller les comptes dans un ordre déterministe.

Avec Supabase, activer les règles RLS adaptées aux lectures client. Les clés privilégiées, dont service_role et les nouvelles secret keys selon la version, restent au backend. Une fonction privilégiée doit vérifier l'appelant et borner search_path et privilèges. Tester les refus d'accès entre tenants.
Source : https://supabase.com/docs/guides/database/postgres/row-level-security

Avec Neon, vérifier le mode de connexion, le pooling et les garanties transactionnelles du driver choisi avant d'implémenter les verrous. Ne pas compter sur un verrou consultatif de session conservé entre plusieurs requêtes serverless. Le détail dépend du driver et doit être confirmé dans sa documentation actuelle.

## Vercel

Séparer les environnements Preview et Production et utiliser une URL stable pour les callbacks. Ne pas attendre tout le paiement dans une requête HTTP. Les fonctions ont une durée bornée; persister le travail dans une queue/workflow durable, ou utiliser un worker externe avec un rapprochement planifié. Une promesse lancée sans attente n'est pas une garantie de traitement.
Source : https://vercel.com/docs/functions/limitations

## Cloud Run

Prévoir un service de réception accessible à PawaPay avec validation de signature applicative. Les services internes/queues peuvent rester protégés par IAM. Utiliser Cloud Tasks pour le travail asynchrone et un ordonnanceur pour la reprise, avec identité de service minimale. Vérifier la persistance avant ACK, les leases et la concurrence multi-instance. Garder secrets hors image Docker, état financier hors disque local et callbacks stables derrière le proxy.
Source : https://docs.cloud.google.com/run/docs/triggering/using-tasks
