# Guide d'Utilisation du Module IaBot

## Introduction

IaBot est un chatbot intelligent pour PrestaShop qui permet à vos clients d'obtenir des réponses à leurs questions et des recommandations de produits personnalisées. Ce guide vous aidera à installer, configurer et utiliser efficacement le module.

## Installation

1. Téléchargez le module IaBot depuis votre espace client ou depuis le marketplace PrestaShop
2. Connectez-vous à votre back-office PrestaShop
3. Allez dans "Modules > Gestionnaire de modules"
4. Cliquez sur "Ajouter un nouveau module"
5. Cliquez sur "Parcourir" et sélectionnez le fichier ZIP du module
6. Cliquez sur "Télécharger ce module"
7. Une fois le module téléchargé, cliquez sur "Installer"
8. Suivez les instructions d'installation

## Configuration

### Configuration générale

1. Après l'installation, accédez à la configuration du module en cliquant sur "Configurer" à côté du module IaBot dans la liste des modules
2. Dans l'onglet "Configuration générale", vous pouvez définir :
   - **Titre du chatbot** : Le nom qui sera affiché dans l'interface du chatbot
   - **Message de bienvenue** : Le message qui sera affiché lorsqu'un utilisateur ouvre le chatbot pour la première fois
   - **Couleur principale** : La couleur principale de l'interface du chatbot
   - **Position du chatbot** : Position sur l'écran (bas à droite, bas à gauche, etc.)
   - **Pages d'affichage** : Sélectionnez les pages où le chatbot sera affiché

### Configuration des recommandations

Dans l'onglet "Recommandations", vous pouvez configurer :

1. **Nombre de produits recommandés** : Le nombre maximum de produits à afficher dans les recommandations
2. **Critères de recommandation** : Les critères utilisés pour recommander des produits (popularité, prix, nouveautés, etc.)
3. **Mots-clés personnalisés** : Associez des mots-clés spécifiques à certains produits pour améliorer les recommandations

### Configuration de la base de connaissances

Dans l'onglet "Base de connaissances", vous pouvez :

1. **Indexer le catalogue** : Indexer automatiquement tous les produits et catégories dans la base de connaissances
2. **Ajouter des entrées manuelles** : Ajouter des questions/réponses personnalisées
3. **Importer/Exporter** : Importer ou exporter la base de connaissances au format CSV

## Utilisation

### Interface client

Le chatbot apparaît sous forme d'une icône flottante sur votre site. Lorsqu'un client clique sur cette icône, le chatbot s'ouvre et affiche le message de bienvenue.

Les clients peuvent :
- Poser des questions sur les produits, la livraison, les retours, etc.
- Rechercher des produits spécifiques
- Recevoir des recommandations personnalisées
- Consulter l'historique de leur conversation

### Interface administrateur

En tant qu'administrateur, vous avez accès à plusieurs fonctionnalités :

1. **Tableau de bord** : Visualisez les statistiques d'utilisation du chatbot
   - Nombre de conversations
   - Questions les plus fréquentes
   - Taux de conversion
   - Produits les plus recommandés

2. **Gestion des conversations** : Consultez et gérez les conversations des clients
   - Filtrez par date, client, statut
   - Exportez les conversations
   - Analysez les tendances

3. **Gestion de la base de connaissances** : Gérez les entrées de la base de connaissances
   - Ajoutez, modifiez ou supprimez des entrées
   - Organisez les entrées par catégories
   - Importez/exportez des données

4. **Gestion des recommandations** : Configurez le système de recommandation
   - Définissez des règles de recommandation
   - Associez des mots-clés à des produits
   - Excluez certains produits des recommandations

## Bonnes pratiques

Pour tirer le meilleur parti du module IaBot, suivez ces bonnes pratiques :

1. **Enrichissez régulièrement votre base de connaissances** :
   - Ajoutez les questions fréquemment posées
   - Mettez à jour les informations sur les produits
   - Créez des réponses pour les questions saisonnières

2. **Personnalisez les recommandations** :
   - Associez des mots-clés pertinents à vos produits
   - Utilisez des synonymes pour couvrir différentes façons de demander la même chose
   - Mettez en avant vos produits phares

3. **Analysez les statistiques** :
   - Identifiez les questions sans réponse
   - Repérez les produits les plus demandés
   - Suivez les tendances de conversion

4. **Optimisez l'expérience utilisateur** :
   - Personnalisez le message de bienvenue
   - Adaptez le style visuel à votre charte graphique
   - Testez différentes positions du chatbot

## Dépannage

### Problèmes courants et solutions

1. **Le chatbot ne s'affiche pas** :
   - Vérifiez que le module est actif
   - Assurez-vous que la page actuelle est incluse dans les pages d'affichage
   - Vérifiez les conflits avec d'autres modules JavaScript

2. **Les recommandations ne sont pas pertinentes** :
   - Indexez à nouveau votre catalogue
   - Ajoutez plus de mots-clés personnalisés
   - Vérifiez les critères de recommandation

3. **Erreurs de base de données** :
   - Consultez les logs d'erreur dans le dossier `var/logs`
   - Vérifiez les permissions de la base de données
   - Assurez-vous que les tables du module sont correctement créées

### Journaux d'erreurs

Le module enregistre les erreurs dans des fichiers de log situés dans le dossier `var/logs` de votre installation PrestaShop. Ces logs peuvent être utiles pour diagnostiquer les problèmes.

## Mises à jour

Pour mettre à jour le module :

1. Téléchargez la nouvelle version du module
2. Désactivez l'ancienne version (ne la désinstallez pas)
3. Installez la nouvelle version
4. Vérifiez la configuration

## Support

Si vous rencontrez des problèmes ou avez des questions, vous pouvez :

1. Consulter la documentation technique dans le dossier `docs`
2. Contacter notre support technique à l'adresse support@iabot.com
3. Visiter notre site web pour les dernières mises à jour et informations

## Conclusion

Le module IaBot est un outil puissant pour améliorer l'expérience client sur votre boutique PrestaShop. En suivant ce guide, vous pourrez configurer et utiliser efficacement toutes ses fonctionnalités pour augmenter vos ventes et la satisfaction de vos clients.
