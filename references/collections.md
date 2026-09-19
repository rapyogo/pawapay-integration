# Encaissements et redirections

## Choisir le flux

| Besoin | Choix |
|---|---|
| Interface entièrement intégrée au produit | Deposit direct, avec parcours déterminé par authType |
| Page hébergée avec plusieurs essais et référence unique | Checkout |
| Parcours Payment Page déjà existant | Conserver et sécuriser son cycle deposit, sans migration gratuite |
| Montant libre | Checkout sans amounts, ou Payment Page sans amountDetails; appliquer les règles métier au crédit confirmé |

Les pages hébergées concernent la collecte. Ne pas inventer une page PawaPay de retrait redirigé à partir de leur existence.
Sources : https://docs.pawapay.io/v2/docs/checkouts et https://docs.pawapay.io/v2/docs/payment_page

## Deposit direct

Enregistrer la commande et son intention. Construire le payload côté serveur, appeler l'API, enregistrer l'acceptation sans marquer la commande payée, puis attendre une preuve vérifiée.

- `PROVIDER_AUTH` : afficher les instructions de l'opérateur et le nom montré sur le téléphone. Distinguer PIN automatique ou manuel. Si le prompt est revivable, afficher ses instructions, sans créer un nouveau deposit.
- `PREAUTH` : afficher `authTokenInstructions`, recevoir uniquement le code de préautorisation prévu, transmettre `preAuthorisationCode` au serveur puis à PawaPay. Exclure ce code des traces, URL, analytics et métadonnées. Ne jamais demander le PIN.
- `REDIRECT_AUTH` : suivre `nextStep`; `GET_AUTH_URL` signifie attendre via callback ou lecture; `REDIRECT_TO_AUTH_URL` permet d'utiliser `authorizationUrl`. Un callback peut donc apporter une étape intermédiaire et pas seulement un état terminal. Les champs de retour montrés dans le guide sont incomplets dans OpenAPI : valider ce contrat avant de livrer ce mode.

Valider les URL reçues : TLS et destinations attendues pour le fournisseur et l'environnement, sans récupération arbitraire côté serveur. Échapper les instructions affichées; autoriser uniquement les schémas nécessaires pour des liens USSD vérifiés. Le retour navigateur consulte le backend authentifié, sans créditer de solde.
Source : https://docs.pawapay.io/v2/docs/deposits

## Checkout

Créer et enregistrer `checkoutId` UUIDv4, puis envoyer au minimum `checkoutId` et `returnUrl`. Fixer `amounts` pour une commande à prix déterminé; couvrir chaque combinaison pays/devise autorisée. Sans amounts, le client choisit son montant. Quand `payer` est fourni, renseigner `payer.accountDetails.allowCustomerToOverride`.

Stocker `checkoutCode`, `redirectUrl`, l'expiration et le rattachement utilisateur/commande. Le code de retour est un identifiant non secret, pas une preuve d'autorisation. Vérifier l'accès à la commande avant d'en exposer les données.

La durée documentée est de 3 à 60 minutes, 15 par défaut. Le client peut reprendre plusieurs tentatives dans un même checkout. L'échec d'une tentative ne signifie pas l'échec du checkout. Corréler `deposit` et `depositsHistory` et produire un seul crédit même si les callbacks deposit et checkout arrivent tous les deux. Si le callback deposit arrive avant le rattachement vérifié au checkout, le conserver pour rapprochement sans crédit immédiat.

Ne pas utiliser clientReferenceId comme preuve que le dépôt appartient au bon checkout sans relation vérifiée.

L'expiration bloque de nouveaux essais. Conserver les tentatives déjà initiées et vérifier leur résultat en cas de course entre paiement et expiration. Ne pas livrer deux fois une commande qui avait plusieurs intentions ou plusieurs sessions.

## Payment Page

Conserver `depositId` avant POST `/v2/paymentpage`. Utiliser les champs v2 `phoneNumber`, `amountDetails`, `country`, `returnUrl`, `language` (EN/FR). Ne pas reprendre les mentions v1 `msisdn` du texte du guide.

La session dure 15 minutes. Le deposit n'existe qu'après l'action de paiement du client. Un `NOT_FOUND` pendant une session encore valide est normal. Aucun callback n'est garanti en cas d'abandon ou d'expiration sans paiement. Après expiration, faire une vérification serveur avant de classer la session abandonnée. Pour un nouvel essai après échec confirmé, créer un nouveau depositId. Ne pas déclencher cette création sur un simple timeout.
Source : https://docs.pawapay.io/v2/api-reference/payment-page/deposit-via-payment-page

## Montant libre et accès mobile

Distinguer un don ou rechargement à prix libre d'une facture à montant exigé. Pour une facture, vérifier le montant et la devise attendus; pour un rechargement, créditer exactement le montant confirmé selon la politique de frais, sans supposer la valeur saisie dans l'interface.

Le mobile ouvre la page hébergée ou l'app opérateur, puis récupère l'état via le serveur. Prévoir fermeture de l'app, retour sans réseau, absence de retour et relance. Les liens universels/app links ne prouvent pas un paiement. Ne jamais inclure de token PawaPay dans l'application, même obfusqué.
