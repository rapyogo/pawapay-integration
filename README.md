# pawapay-integration

Skill (compatible Claude / Claude Code / agents IA de développement) pour intégrer les
paiements Mobile Money via l'[API pawaPay](https://docs.pawapay.io/using_the_api) (V1).

Ce dépôt contient un **skill**, pas une librairie à installer : c'est un ensemble
d'instructions + de templates de code destiné à être chargé par un agent IA (Claude,
Claude Code, etc.) pour guider une intégration pawaPay de bout en bout — .env, code de
signature RFC-9421, deposits/payouts/refunds, vérification des callbacks, checklist de
sécurité avant production.

## Pourquoi ce skill

pawaPay est un agrégateur unique de Mobile Money Operators en Afrique (Orange Money, MTN
MoMo, Airtel Money, M-Pesa, Moov, Free Money, TNM, Zamtel, etc.) : une seule API, un
paramètre `correspondent` par opérateur/pays. Ce skill part de ce principe et évite le
piège classique de vouloir construire une couche d'abstraction "multi-fournisseurs"
au-dessus d'un service qui fait déjà cette abstraction.

## Contenu

```
SKILL.md                          # instructions principales pour l'agent IA
references/
  correspondents.md                # table complète des correspondents par pays
  signatures-rfc9421.md            # implémentation des signatures HTTP (RFC-9421)
  security-checklist.md            # checklist de mise en production réaliste
  generic-rest-guide.md            # guide d'adaptation pour toute stack non couverte
templates/
  env.template                     # modèle de .env documenté
  node-express/                    # client + route de callback Node.js
  php-laravel/                     # service + controller Laravel
```

## Utilisation

- **Avec Claude.ai / Claude Code / Cowork** : télécharger ou cloner ce dépôt, puis
  installer le dossier comme skill (`.skill` packagé disponible dans les
  [Releases](../../releases), ou directement le dossier source).
- **Sans agent IA** : les fichiers de `references/` et `templates/` restent lisibles et
  réutilisables comme documentation/boilerplate classique.

## Portée volontairement limitée

- Couvre l'API **V1** de pawaPay (celle documentée sur `docs.pawapay.io/using_the_api`).
  pawaPay propose aussi une **V2** avec un format de payload différent
  (`correspondent`→`provider`, etc.) — non couverte ici. Vérifiez la version utilisée par
  votre compte avant d'appliquer ce skill.
- Templates de code fournis pour **Node.js/Express** et **PHP/Laravel**. Pour toute autre
  stack, voir `references/generic-rest-guide.md` — pawaPay étant une API REST/JSON
  classique, l'adaptation est directe.

## Sécurité

Ne committez jamais de vrai token API ou de clé privée. `templates/env.template` ne
contient que des placeholders. Voir `references/security-checklist.md` avant toute mise
en production.

## Licence

MIT — voir [LICENSE](LICENSE).

## Avertissement

Ce skill n'est pas affilié à pawaPay. Toujours se référer à la
[documentation officielle](https://docs.pawapay.io) en cas de doute ou de changement d'API.
