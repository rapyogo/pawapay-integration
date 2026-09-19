# Portabilité entre agents de code

Le paquet repose sur SKILL.md et des références Markdown relatives. Aucun runtime MCP particulier n'est obligatoire. Conserver ces chemins lors de la copie.

Pour un agent qui reconnaît les Agent Skills, installer le dossier complet selon la documentation de sa version, puis demander : « Utilise le skill pawapay pour intégrer [flux] à [projet]. Inspecte le code existant, vérifie le contrat officiel et implémente les contrôles et tests nécessaires. »

Claude Code et Codex peuvent utiliser ce format de skill; les chemins de découverte et commandes dépendent de l'installation. Pour Antigravity, Hermes Agent et tout autre agent, vérifier le support de la version actuelle avant de promettre une découverte automatique. Sinon, lui demander explicitement de lire le SKILL.md du paquet et de suivre ses références. Cette méthode fonctionne comme instructions de projet, sans prétendre constituer une intégration native.

Les fichiers AGENTS.md, CLAUDE.md ou les règles propres à un outil peuvent pointer vers le skill, mais ne doivent pas dupliquer son contenu ou écraser les instructions existantes. Ne pas créer un fichier HERMES.md ou une configuration Antigravity en supposant qu'il sera reconnu sans documentation.

Le script d'inspection requiert Python 3 et PyYAML. Le skill textuel reste utilisable sans Python. Aucun script inclus ne demande un token PawaPay ou ne déplace des fonds.
