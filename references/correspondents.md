# Correspondents pawaPay par pays

Source : docs.pawapay.io/using_the_api (API V1). À revérifier via l'endpoint `active-configuration`
avant mise en production, car pawaPay peut ajouter des correspondents ou changer les règles de décimales.

Un `correspondent` = un couple (opérateur, pays). C'est la seule valeur à choisir pour router
un paiement — il n'y a rien d'autre à "intégrer" par opérateur.

| Pays | MMO | Correspondent | Devise | Décimales |
|---|---|---|---|---|
| Bénin | MTN | MTN_MOMO_BEN | XOF | Non supportées |
| Bénin | Moov | MOOV_BEN | XOF | Non supportées |
| Burkina Faso | Moov | MOOV_BFA | XOF | Non supportées |
| Burkina Faso | Orange | ORANGE_BFA | XOF | Non supportées |
| Cameroun | MTN | MTN_MOMO_CMR | XAF | Non supportées |
| Cameroun | Orange | ORANGE_CMR | XAF | Non supportées |
| Côte d'Ivoire | MTN | MTN_MOMO_CIV | XOF | Non supportées |
| Côte d'Ivoire | Orange | ORANGE_CIV | XOF | Non supportées |
| **RDC** | **Vodacom M-Pesa** | **VODACOM_MPESA_COD** | CDF | Non supportées |
| **RDC** | **Vodacom M-Pesa** | **VODACOM_MPESA_COD** | USD | 2 |
| **RDC** | **Airtel** | **AIRTEL_COD** | CDF | 2 |
| **RDC** | **Airtel** | **AIRTEL_COD** | USD | 2 |
| **RDC** | **Orange** | **ORANGE_COD** | CDF | 2 |
| **RDC** | **Orange** | **ORANGE_COD** | USD | 2 |
| Éthiopie | Safaricom M-Pesa | MPESA_ETH | ETB | 2 |
| Gabon | Airtel | AIRTEL_GAB | XAF | 2 |
| Ghana | MTN | MTN_MOMO_GHA | GHS | 2 |
| Ghana | AT | AIRTELTIGO_GHA | GHS | 2 |
| Ghana | Vodafone | VODAFONE_GHA | GHS | 2 |
| Kenya | M-Pesa | MPESA_KEN | KES | Deposits: non ; Payouts: 2 |
| Lesotho | M-Pesa | MPESA_LSO | LSL | 2 |
| Malawi | Airtel | AIRTEL_MWI | MWK | 2 |
| Malawi | TNM | TNM_MWI | MWK | 2 |
| Mozambique | Movitel | MOVITEL_MOZ | MZN | Deposits: non ; Payouts: 2 |
| Mozambique | Vodacom | VODACOM_MOZ | MZN | 2 |
| Nigeria | Airtel | AIRTEL_NGA | NGN | Non supportées |
| Nigeria | MTN | MTN_MOMO_NGA | NGN | 2 |
| Rép. du Congo | Airtel | AIRTEL_COG | XAF | Non supportées |
| Rép. du Congo | MTN | MTN_MOMO_COG | XAF | Non supportées |
| Rwanda | Airtel | AIRTEL_RWA | RWF | Non supportées |
| Rwanda | MTN | MTN_MOMO_RWA | RWF | Non supportées |
| Sénégal | Free | FREE_SEN | XOF | Non supportées |
| Sénégal | Orange | ORANGE_SEN | XOF | Non supportées |
| Sierra Leone | Orange | ORANGE_SLE | SLE | 2 |
| Tanzanie | Airtel | AIRTEL_TZA | TZS | 2 |
| Tanzanie | Vodacom | VODACOM_TZA | TZS | Non supportées |
| Tanzanie | Tigo | TIGO_TZA | TZS | Non supportées |
| Tanzanie | Halotel | HALOTEL_TZA | TZS | Non supportées |
| Ouganda | Airtel | AIRTEL_OAPI_UGA | UGX | Non supportées |
| Ouganda | MTN | MTN_MOMO_UGA | UGX | 2 |
| Zambie | Airtel | AIRTEL_OAPI_ZMB | ZMW | 2 |
| Zambie | MTN | MTN_MOMO_ZMB | ZMW | 2 |
| Zambie | Zamtel | ZAMTEL_ZMB | ZMW | 2 |

## Règle de validation des montants

- Toujours envoyer `amount` en **string**, jamais en nombre (ex: `"15"`, pas `15`).
- Vérifier le nombre de décimales autorisées pour le couple (correspondent, devise) exact avant l'envoi —
  une erreur ici est rejetée par l'API, mais autant filtrer côté client pour un message d'erreur clair
  à l'utilisateur final.
- Le champ `country` (code ISO 3 lettres, ex: `COD` pour RDC) doit correspondre au correspondent choisi.

## Vérification dynamique

Ne pas se fier uniquement à cette table statique en production : appeler l'endpoint
`GET active-configuration` pour connaître les correspondents réellement actifs sur le compte
(un compte marchand peut ne pas avoir accès à tous les correspondents d'un pays), et
`predict-correspondent` pour déterminer automatiquement le bon correspondent à partir d'un numéro MSISDN
saisi par l'utilisateur final (évite de demander à l'utilisateur de choisir son opérateur manuellement).
