# Payouts, retraits et remboursements

## Séparer trois notions

Un payout transfère des fonds du portefeuille marchand PawaPay au portefeuille Mobile Money du destinataire. Un retrait de solde utilisateur ajoute les contrôles et écritures de votre propre plateforme. Un settlement verse les fonds marchands selon une configuration de règlement distincte. Ne pas employer ces concepts comme des synonymes.
Source : https://docs.pawapay.io/v2/docs/payouts et https://docs.pawapay.io/dashboard/finances/settlements

## Retrait applicatif

1. Authentifier et autoriser l'utilisateur; rattacher montant, devise et bénéficiaire à sa demande. Faire confirmer le destinataire, et appliquer la réauthentification prévue pour les retraits ou changements sensibles.
2. Dans une transaction locale, vérifier le solde disponible et réserver le principal ainsi que les frais applicatifs connus. Inscrire l'intention immuable, son UUIDv4 et un travail à envoyer.
3. Après commit, le worker appelle PawaPay avec cet UUID. Un timeout conserve la réservation et déclenche une recherche d'état, pas un second retrait.
4. `ACCEPTED` signifie attente. `ENQUEUED` reste réservé. `IN_RECONCILIATION` reste indéterminé. Sur succès confirmé, consommer la réservation une fois; sur échec final confirmé, la libérer une fois.
5. Notifier l'utilisateur depuis une outbox persistée après la mise à jour comptable. Conserver la preuve et la traçabilité sans exposer le numéro complet.

Contrôler le solde marchand et la disponibilité, sans traiter une lecture de solde PawaPay comme un verrou concurrent. Le principal du payout est réservé chez PawaPay; les frais PawaPay sont déduits au succès. Modéliser ces frais séparément des frais facturés à l'utilisateur. Ne pas inventer un tarif.

## Attente, annulation et lots

La disponibilité payout peut être OPERATIONAL, DELAYED ou CLOSED. DELAYED peut conduire à ENQUEUED après acceptation; CLOSED peut rejeter. Prévenir l'utilisateur et conserver l'état exact.

`fail-enqueued` concerne seulement une opération encore en file. Son acceptation n'est pas une confirmation d'échec financier. Attendre FAILED, avec MANUALLY_CANCELLED le cas échéant, avant de libérer les fonds. Si l'opération a démarré, l'annulation peut être refusée.

Les lots payout sont limités à 20 éléments selon la page dédiée. Conserver un identifiant et un état par élément; les acceptations peuvent être partielles et les callbacks séparés. Reprendre seulement les éléments indéterminés avec leurs IDs existants.
Sources : https://docs.pawapay.io/v2/api-reference/payouts/cancel-enqueued-payout et https://docs.pawapay.io/v2/api-reference/payouts/initiate-bulk-payout

## Refund

Rattacher le refund à un deposit réussi, jamais à un numéro arbitraire modifié par le client. Enregistrer refundId avant appel. Sérialiser les remboursements par deposit et réserver leur montant pour que le total réalisé + en attente ne dépasse pas le montant remboursable. Une nouvelle demande ne doit pas chevaucher un remboursement en cours.

Le guide décrit un remboursement complet sans amount, et des remboursements partiels avec amount et currency; après remboursement partiel, l'absence de montant vise le reste. Le schéma hérité exige pourtant amount/currency. Ne pas masquer cette divergence : utiliser une forme explicitement testée en sandbox et documenter le choix. Reprendre les contrôles opérateur, devise, décimales, attente et callbacks des payouts, sans transformer le refund en payout générique.
Pour un rechargement déjà crédité, réserver ou débiter atomiquement le solde utilisateur correspondant avant le remboursement. S’il a été dépensé, appliquer une décision métier explicite : ne pas rembourser tout en laissant les mêmes fonds disponibles au retrait. Sérialiser le refund et le retrait sur le même compte.

Source : https://docs.pawapay.io/v2/docs/refunds

## Remittances et split payments

N'activer une remittance que si elle correspond au produit demandé et aux capacités du compte. Elle ajoute les données d'identité de l'expéditeur, ses informations de transaction et celles du bénéficiaire. Lire les schémas requis et les règles du fournisseur; ne pas fabriquer les informations d'identité manquantes ni assimiler un payout local à une remittance.

Le schéma OpenAPI contient split-payments, mais la documentation indexée n'en fournit pas le guide. Le schéma observé limite `splits` à un élément. Ne pas promettre un partage arbitraire entre plusieurs bénéficiaires. Faire confirmer disponibilité, frais, échecs partiels et réconciliation avant implémentation. Un encaissement suivi de payouts est une autre architecture, avec ses propres risques et écritures.
