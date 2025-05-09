<?php
/**
 * Script de réparation pour le module IaBot
 * 
 * Ce script vise à corriger l'erreur "Unknown column 'name'" lors de l'installation
 */

// Initialisation de PrestaShop
define('_PS_ADMIN_DIR_', 1);
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');

// Vérification de l'accès administrateur
if (!Context::getContext()->employee->isLoggedBack()) {
    die('Accès refusé. Connectez-vous en tant qu\'administrateur.');
}

echo '<html>
<head>
    <title>Réparation du module IaBot</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1000px; margin: 0 auto; }
        .error { color: red; }
        .success { color: green; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow: auto; }
        .btn { 
            display: inline-block; 
            padding: 8px 16px; 
            background: #0d47a1; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px;
        }
        .step { 
            background: #f9f9f9; 
            padding: 15px; 
            margin: 20px 0; 
            border-left: 5px solid #0d47a1; 
        }
    </style>
</head>
<body>
    <h1>Réparation du module IaBot</h1>';

// Fonction pour afficher les résultats d'une opération
function showResult($message, $success) {
    $class = $success ? 'success' : 'error';
    $icon = $success ? '✓' : '✗';
    echo "<p class='{$class}'>{$icon} {$message}</p>";
}

// Étape 1 : Vérifier si la table tab existe et sa structure
echo '<div class="step">
    <h2>Étape 1: Vérification de la table des onglets</h2>';

$db = Db::getInstance();
$tabExists = $db->executeS("SHOW TABLES LIKE '" . _DB_PREFIX_ . "tab'");

if (empty($tabExists)) {
    showResult("La table des onglets n'existe pas!", false);
} else {
    showResult("La table des onglets existe.", true);
    
    // Vérifier la structure de la table
    $tabColumns = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . "tab`");
    echo "<p>Structure de la table des onglets:</p>";
    echo "<pre>";
    foreach ($tabColumns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
    echo "</pre>";
}

// Étape 2 : Vérifier la table tab_lang
echo '</div><div class="step">
    <h2>Étape 2: Vérification de la table des traductions d\'onglets</h2>';

$tabLangExists = $db->executeS("SHOW TABLES LIKE '" . _DB_PREFIX_ . "tab_lang'");

if (empty($tabLangExists)) {
    showResult("La table des traductions d'onglets n'existe pas!", false);
} else {
    showResult("La table des traductions d'onglets existe.", true);
    
    // Vérifier la structure de la table
    $tabLangColumns = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . "tab_lang`");
    echo "<p>Structure de la table des traductions d'onglets:</p>";
    echo "<pre>";
    foreach ($tabLangColumns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
    echo "</pre>";
}

// Étape 3: Vérifier les tables du module
echo '</div><div class="step">
    <h2>Étape 3: Vérification des tables du module</h2>';

$tables = [
    'iabot_product_index',
    'iabot_conversation',
    'iabot_message',
    'iabot_knowledge',
    'iabot_recommendation',
    'iabot_statistic'
];

foreach ($tables as $table) {
    $tableExists = $db->executeS("SHOW TABLES LIKE '" . _DB_PREFIX_ . "{$table}'");
    
    if (empty($tableExists)) {
        showResult("La table {$table} n'existe pas.", false);
    } else {
        showResult("La table {$table} existe.", true);
        
        // Si c'est la table d'index de produits, vérifier qu'elle a une colonne name
        if ($table == 'iabot_product_index') {
            $columns = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . "{$table}` LIKE 'name'");
            if (empty($columns)) {
                showResult("PROBLÈME DÉTECTÉ: La colonne 'name' n'existe pas dans {$table}!", false);
            } else {
                showResult("La colonne 'name' existe dans {$table}.", true);
            }
        }
    }
}

// Étape 4: Actions de réparation
echo '</div><div class="step">
    <h2>Étape 4: Actions de réparation</h2>';

// Action: Créer ou corriger les tables SQL
if (isset($_GET['action']) && $_GET['action'] == 'fix_tables') {
    // Inclure le fichier SQL
    $sql = [];
    include(dirname(__FILE__) . '/sql/install.php');
    
    echo "<h3>Exécution des requêtes SQL...</h3>";
    foreach ($sql as $i => $query) {
        echo "<p>Requête " . ($i + 1) . ": ";
        try {
            $result = $db->execute($query);
            showResult($result ? "Succès" : "Échec", $result);
        } catch (Exception $e) {
            showResult("Erreur: " . $e->getMessage(), false);
        }
    }
    
    echo "<p>Rechargez la page pour voir si les problèmes ont été corrigés.</p>";
}

// Action: Installer les onglets directement en SQL
if (isset($_GET['action']) && $_GET['action'] == 'fix_tabs') {
    echo "<h3>Correction des onglets d'administration...</h3>";
    
    // Suppression des onglets existants
    $adminTabs = [
        'AdminIaBot',
        'AdminIaBotDashboard',
        'AdminIaBotConfiguration',
        'AdminIaBotKnowledge',
        'AdminIaBotRecommendations',
        'AdminIaBotStatistics'
    ];
    
    foreach ($adminTabs as $tab) {
        $id = (int)Tab::getIdFromClassName($tab);
        if ($id) {
            $db->execute("DELETE FROM `" . _DB_PREFIX_ . "tab` WHERE id_tab = " . $id);
            $db->execute("DELETE FROM `" . _DB_PREFIX_ . "tab_lang` WHERE id_tab = " . $id);
            showResult("Suppression de l'onglet {$tab}", true);
        }
    }
    
    // Création des nouveaux onglets avec les requêtes SQL
    $idParentModules = (int)Tab::getIdFromClassName('AdminParentModulesSf');
    
    // Créer l'onglet principal
    $db->execute("
        INSERT INTO `" . _DB_PREFIX_ . "tab` 
        (`id_parent`, `class_name`, `module`, `position`, `active`) 
        VALUES ({$idParentModules}, 'AdminIaBot', 'iabot', 0, 1)
    ");
    $idMainTab = (int)$db->Insert_ID();
    
    if ($idMainTab) {
        showResult("Création de l'onglet principal AdminIaBot", true);
        
        // Ajouter les traductions pour l'onglet principal
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $db->execute("
                INSERT INTO `" . _DB_PREFIX_ . "tab_lang` 
                (`id_tab`, `id_lang`, `name`) 
                VALUES ({$idMainTab}, {$lang['id_lang']}, 'Assistant IA')
            ");
        }
        
        // Créer les sous-onglets
        $subTabs = [
            ['AdminIaBotDashboard', 'Tableau de bord'],
            ['AdminIaBotConfiguration', 'Configuration'],
            ['AdminIaBotKnowledge', 'Base de connaissances'],
            ['AdminIaBotRecommendations', 'Recommandations'],
            ['AdminIaBotStatistics', 'Statistiques']
        ];
        
        foreach ($subTabs as $subTab) {
            $db->execute("
                INSERT INTO `" . _DB_PREFIX_ . "tab` 
                (`id_parent`, `class_name`, `module`, `position`, `active`) 
                VALUES ({$idMainTab}, '{$subTab[0]}', 'iabot', 0, 1)
            ");
            $idSubTab = (int)$db->Insert_ID();
            
            if ($idSubTab) {
                showResult("Création du sous-onglet {$subTab[0]}", true);
                
                // Ajouter les traductions pour le sous-onglet
                foreach ($languages as $lang) {
                    $db->execute("
                        INSERT INTO `" . _DB_PREFIX_ . "tab_lang` 
                        (`id_tab`, `id_lang`, `name`) 
                        VALUES ({$idSubTab}, {$lang['id_lang']}, '{$subTab[1]}')
                    ");
                }
            } else {
                showResult("Échec de création du sous-onglet {$subTab[0]}", false);
            }
        }
    } else {
        showResult("Échec de création de l'onglet principal", false);
    }
    
    echo "<p>Rechargez la page pour voir si les problèmes ont été corrigés.</p>";
}

// Boutons d'actions
echo '<div style="margin-top: 20px;">
    <a href="?action=fix_tables" class="btn">Exécuter les requêtes SQL pour créer/réparer les tables</a>
    <br><br>
    <a href="?action=fix_tabs" class="btn">Réparer les onglets d\'administration</a>
    <br><br>
    <a href="../../admin-dev/index.php?controller=AdminModules" class="btn" style="background:#555;">Retour aux modules</a>
</div>';

echo '</div>
</body>
</html>';
