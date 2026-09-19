# Écarts et limites documentaires observés

État au 19 septembre 2026. Les divergences ci-dessous exigent une décision vérifiée, pas une hypothèse cachée.

| Sujet | Observation | Consigne |
|---|---|---|
| Remboursement complet | Le guide autorise l'absence de montant; RefundInitiationRequest hérite de amount et currency obligatoires | Tester la forme voulue et consigner le contrat validé |
| Deposit avec redirection | Le guide emploie successfulUrl/failedUrl; ces propriétés n'apparaissent pas dans DepositInitiationRequest observé | Vérifier auprès du fournisseur ou en sandbox, ne pas prétendre avoir un contrat complet |
| Payment Page | Texte du guide mentionne msisdn; schéma v2 emploie phoneNumber et amountDetails | Utiliser v2, vérifier le payload effectif |
| Annulation/renvoi | Le guide de migration parle de query params; OpenAPI donne des segments de chemin | Employer les routes /resend-callback/{id} et /fail-enqueued/{id} confirmées dans l'API |
| Métadonnées | La modélisation additionalProperties du schéma est inhabituelle et les formes request/response diffèrent | Suivre la forme documentée par exemples et tester; ne pas générer une propriété littérale additionalProperties |
| Amount | Regex OpenAPI refuse certains zéros finaux alors que les guides montrent des montants tels que 100.50 | Sérialiser exactement et normaliser les zéros non significatifs, vérifier précision opérateur et acceptation sandbox |
| Checkout vs deposit | Certains textes généraux disent callbacks finaux, mais deposit redirigé peut fournir authorizationUrl intermédiaire | Traiter séparément étape utilisateur et finalité financière |
| Split et bulk remittance | Présents dans OpenAPI mais absents de llms.txt | Ne pas promettre l'activation; confirmer les conditions et le contrat |
| Signature expires | Guide emploie un libellé d'expiration du keypair | Appliquer la sémantique RFC 9421, vérifier la compatibilité avec PawaPay |
| Paramètre statementId | La référence OpenAPI du paramètre pointe vers un schéma, sans name/in | Corriger explicitement la génération du client et vérifier le chemin documenté |
| Dashboard | Plusieurs pages référencent v1 ou anciens noms | Utiliser pour les procédures d'exploitation, pas pour construire des payloads v2 |

Source principale : https://docs.pawapay.io/v2/api-reference/openapi_v2.yaml ; comparer avec les pages guides correspondantes dans sources.md.

Les schémas, pays, opérateurs, limites, IPs et capacités peuvent évoluer. Les recommandations de déploiement ne valent pas validation de chaque combinaison de versions. Aucun appel financier ni test avec un compte marchand réel n'a été effectué lors de la création du skill.
