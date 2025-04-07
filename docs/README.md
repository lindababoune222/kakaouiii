# Documentation Technique du Module IaBot

## Vue d'ensemble

Le module IaBot est un chatbot intelligent pour PrestaShop qui permet aux clients de poser des questions sur les produits et de recevoir des recommandations personnalisées. Ce module utilise une base de connaissances et un système de recommandation pour fournir des réponses pertinentes aux utilisateurs.

## Architecture du module

Le module est construit selon une architecture orientée objet avec une séparation claire des responsabilités :

```
iabot/
├── classes/                  # Classes principales du module
│   ├── IaBotConversation.php # Gestion des conversations
│   ├── IaBotException.php    # Système de gestion d'exceptions
│   ├── IaBotKnowledge.php    # Base de connaissances
│   ├── IaBotLogger.php       # Système de journalisation
│   ├── IaBotMessage.php      # Gestion des messages
│   ├── IaBotRecommendation.php # Système de recommandation de produits
│   └── IaBotStatistic.php    # Statistiques d'utilisation
├── controllers/              # Contrôleurs front et admin
├── docs/                     # Documentation
├── inc/                      # Fichiers d'inclusion
│   └── prestashop-ide-helper.php # Aide pour l'IDE
├── js/                       # Scripts JavaScript
├── sql/                      # Scripts SQL d'installation
│   ├── install.php           # Installation des tables
│   └── uninstall.php         # Désinstallation des tables
├── tests/                    # Tests unitaires
│   ├── IaBotExceptionTest.php
│   ├── IaBotKnowledgeTest.php
│   ├── IaBotLoggerTest.php
│   ├── IaBotMessageTest.php
│   ├── IaBotRecommendationTest.php
│   └── bootstrap.php         # Bootstrap pour les tests
├── views/                    # Vues et templates
│   ├── css/                  # Styles CSS
│   ├── js/                   # Scripts JS spécifiques aux vues
│   └── templates/            # Templates Smarty
├── iabot.php                 # Fichier principal du module
└── phpunit.xml               # Configuration PHPUnit pour les tests
```

## Composants principaux

### 1. Système de Gestion des Conversations (`IaBotConversation`)

Cette classe gère les conversations entre les utilisateurs et le chatbot. Chaque conversation est identifiée par un token unique et peut être associée à un client connecté ou à un visiteur anonyme.

Fonctionnalités principales :
- Création de nouvelles conversations
- Récupération des conversations existantes par token
- Suivi des métadonnées (IP, agent utilisateur, etc.)

### 2. Système de Gestion des Messages (`IaBotMessage`)

Cette classe gère les messages échangés dans une conversation. Elle inclut des fonctionnalités de validation et de nettoyage des messages pour éviter les attaques XSS et autres problèmes de sécurité.

Fonctionnalités principales :
- Ajout de messages sécurisés
- Validation du contenu des messages
- Analyse des messages pour extraire des mots-clés

### 3. Système de Recommandation de Produits (`IaBotRecommendation`)

Cette classe fournit des recommandations de produits basées sur des mots-clés. Elle utilise un système de cache pour améliorer les performances.

Fonctionnalités principales :
- Recherche de produits par mots-clés
- Mise en cache des recommandations
- Formatage des produits pour l'affichage

### 4. Base de Connaissances (`IaBotKnowledge`)

Cette classe gère la base de connaissances du chatbot, qui contient des informations sur les produits, les catégories et d'autres éléments du catalogue.

Fonctionnalités principales :
- Recherche dans la base de connaissances
- Ajout et suppression d'entrées
- Indexation automatique des produits et catégories

### 5. Système de Gestion d'Erreurs (`IaBotException` et `IaBotLogger`)

Le module utilise un système robuste de gestion d'erreurs avec des exceptions typées et un système de journalisation pour faciliter le débogage.

Fonctionnalités principales :
- Exceptions spécifiques pour différents types d'erreurs
- Journalisation des erreurs et des événements
- Rotation automatique des fichiers de log

## Schéma de la base de données

Le module utilise les tables suivantes :

1. **iabot_conversation** : Stocke les conversations
   - `id_conversation` : ID unique de la conversation
   - `id_customer` : ID du client (peut être null pour les visiteurs)
   - `token` : Token unique pour identifier la conversation
   - `ip_address` : Adresse IP du client
   - `user_agent` : Agent utilisateur du client
   - `is_customer_logged` : Indique si le client est connecté
   - `date_add` : Date de création
   - `date_upd` : Date de dernière mise à jour

2. **iabot_message** : Stocke les messages
   - `id_message` : ID unique du message
   - `id_conversation` : ID de la conversation
   - `content` : Contenu du message
   - `sender` : Expéditeur du message (user/bot)
   - `date_add` : Date d'ajout

3. **iabot_knowledge** : Stocke la base de connaissances
   - `id_knowledge` : ID unique de l'entrée
   - `id_reference` : ID de référence (produit, catégorie, etc.)
   - `reference_type` : Type de référence
   - `id_lang` : ID de la langue
   - `title` : Titre de l'entrée
   - `content` : Contenu de l'entrée
   - `date_add` : Date d'ajout
   - `date_upd` : Date de mise à jour

4. **iabot_recommendation** : Stocke les recommandations de produits
   - `id_recommendation` : ID unique de la recommandation
   - `id_product` : ID du produit
   - `keyword` : Mot-clé associé
   - `position` : Position dans les résultats
   - `date_add` : Date d'ajout

5. **iabot_statistic** : Stocke les statistiques d'utilisation
   - `id_statistic` : ID unique de la statistique
   - `id_conversation` : ID de la conversation
   - `id_customer` : ID du client
   - `metric_type` : Type de métrique
   - `metric_value` : Valeur de la métrique
   - `date_add` : Date d'ajout

## Système de Gestion d'Erreurs

Le module utilise un système de gestion d'erreurs basé sur les exceptions pour gérer les erreurs de manière structurée et cohérente.

### Types d'exceptions

- `IaBotException::validationError()` : Erreurs de validation des données
- `IaBotException::databaseError()` : Erreurs de base de données
- `IaBotException::apiError()` : Erreurs d'API
- `IaBotException::notFoundError()` : Erreurs de ressource non trouvée

### Niveaux de journalisation

- `ERROR` : Erreurs critiques qui nécessitent une attention immédiate
- `WARNING` : Avertissements qui n'empêchent pas le fonctionnement normal
- `INFO` : Informations générales sur le fonctionnement du module
- `DEBUG` : Informations détaillées pour le débogage

## Système de Cache

Le module utilise un système de cache en mémoire pour améliorer les performances des recommandations de produits. Le cache est automatiquement nettoyé lorsqu'il devient trop volumineux.

## Tests Unitaires

Le module inclut un ensemble complet de tests unitaires pour assurer la qualité du code. Les tests peuvent être exécutés avec PHPUnit.

```bash
cd /path/to/iabot
phpunit
```

## Bonnes pratiques de développement

Le module suit les bonnes pratiques suivantes :

1. **Validation des entrées** : Toutes les entrées utilisateur sont validées et nettoyées
2. **Gestion des erreurs** : Utilisation d'exceptions typées et de journalisation
3. **Séparation des responsabilités** : Chaque classe a une responsabilité unique
4. **Documentation** : Code documenté avec des commentaires PHPDoc
5. **Tests** : Tests unitaires pour chaque classe principale

## Intégration avec PrestaShop

Le module s'intègre avec PrestaShop en utilisant les classes et fonctions natives :

- `ObjectModel` pour la gestion des objets en base de données
- `Db` pour les requêtes SQL
- `Tools` pour les utilitaires
- `Validate` pour la validation des données
- `Context` pour accéder au contexte de PrestaShop

## Sécurité

Le module implémente plusieurs mesures de sécurité :

1. **Validation des entrées** : Toutes les entrées utilisateur sont validées
2. **Protection contre XSS** : Nettoyage des contenus HTML
3. **Requêtes SQL sécurisées** : Utilisation de `pSQL` et de requêtes préparées
4. **Gestion des erreurs** : Les erreurs sont journalisées sans exposer d'informations sensibles

## Performances

Le module optimise les performances grâce à :

1. **Système de cache** : Cache en mémoire pour les recommandations
2. **Requêtes SQL optimisées** : Utilisation d'index et de requêtes efficaces
3. **Limitation des résultats** : Pagination des résultats pour éviter de surcharger la base de données

## Maintenance et débogage

Pour faciliter la maintenance et le débogage, le module inclut :

1. **Système de journalisation** : Logs détaillés avec rotation automatique
2. **Exceptions typées** : Exceptions spécifiques pour différents types d'erreurs
3. **Tests unitaires** : Tests pour valider le fonctionnement du module

## Conclusion

Le module IaBot fournit une solution complète de chatbot pour PrestaShop avec des fonctionnalités avancées de recommandation de produits et de gestion des conversations. Son architecture modulaire et son système robuste de gestion d'erreurs en font une solution fiable et maintenable.
