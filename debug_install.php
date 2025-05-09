<?php
/**
 * Script de dépannage pour l'installation du module IaBot
 * 
 * Ce script aide à diagnostiquer et résoudre les problèmes d'installation
 * en inspectant la structure de la base de données et en exécutant
 * les requêtes SQL nécessaires.
 */

// Initialisation de PrestaShop
define('_PS_ADMIN_DIR_', 1);
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');

// Vérification de l'accès administrateur
if (!Context::getContext()->employee->isLoggedBack()) {
    die('Accès refusé. Connectez-vous en tant qu\'administrateur.');
}

// Fonctions auxiliaires
function printHeader($title) {
    echo "<h2 style='background:#f4f4f4;padding:10px;margin:20px 0 10px;border-left:4px solid #0d47a1;'>{$title}</h2>";
}

function printTable($data, $headers = []) {
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;margin:10px 0;width:100%;'>";
    
    if (!empty($headers)) {
        echo "<tr style='background:#0d47a1;color:white;'>";
        foreach ($headers as $header) {
            echo "<th>{$header}</th>";
        }
        echo "</tr>";
    }
    
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
}

// Style CSS
echo "
<style>
body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { background: #f8f9fa; padding: 10px; border-left: 4px solid #17a2b8; margin: 10px 0; }
.btn { 
    display: inline-block; 
    padding: 8px 16px; 
    background: #0d47a1; 
    color: white; 
    text-decoration: none; 
    border-radius: 4px;
    margin: 5px;
}
.btn:hover { background: #0a3882; }
</style>
";

echo "<h1>Outil de dépannage d'installation du module IaBot</h1>";

// Analyse de la base de données
printHeader("Structure des tables");

// Vérification des tables du module
$tablePrefix = _DB_PREFIX_;
$moduleTables = [
    'iabot_conversation',
    'iabot_message',
    'iabot_knowledge',
    'iabot_recommendation',
    'iabot_product_index',
    'iabot_statistic',
    'tab'
];

$db = Db::getInstance();

foreach ($moduleTables as $table) {
    $tableName = $tablePrefix . $table;
    $query = "SHOW TABLES LIKE '{$tableName}'";
    $exists = $db->executeS($query);
    
    echo "<div>";
    if (!empty($exists)) {
        echo "<p class='success'>✓ Table <strong>{$tableName}</strong> existe</p>";
        
        // Affichage de la structure
        $columns = $db->executeS("SHOW COLUMNS FROM `{$tableName}`");
        if (!empty($columns)) {
            $columnData = [];
            foreach ($columns as $column) {
                $columnData[] = [
                    $column['Field'],
                    $column['Type'],
                    $column['Null'],
                    $column['Key'],
                    $column['Default'],
                    $column['Extra']
                ];
            }
            
            printTable(
                $columnData, 
                ['Colonne', 'Type', 'Null', 'Clé', 'Défaut', 'Extra']
            );
        }
    } else {
        echo "<p class='error'>✗ Table <strong>{$tableName}</strong> n'existe pas</p>";
    }
    echo "</div>";
}

// Vérification des onglets d'administration
printHeader("Onglets d'administration");

$adminTabs = [
    'AdminIaBot',
    'AdminIaBotDashboard',
    'AdminIaBotConfiguration',
    'AdminIaBotKnowledge',
    'AdminIaBotRecommendations',
    'AdminIaBotStatistics'
];

$tabsData = [];
foreach ($adminTabs as $tabClass) {
    $tabId = Tab::getIdFromClassName($tabClass);
    $statusText = $tabId ? 'Existe (ID: ' . $tabId . ')' : 'Manquant';
    $tabsData[] = [$tabClass, $statusText];
}

printTable($tabsData, ['Classe', 'Statut']);

// Actions de dépannage
printHeader("Actions de dépannage");

// Action 1: Correction manuelle de la structure
echo "<div class='info'>";
echo "<h3>Installation forcée des tables SQL</h3>";
echo "<p>Cette action exécute directement les requêtes SQL pour créer ou mettre à jour les tables du module.</p>";

if (isset($_GET['action']) && $_GET['action'] === 'fix_tables') {
    echo "<div style='background:#e8f5e9;padding:10px;margin:10px 0;border-left:4px solid green;'>";
    echo "<h4>Résultats d'exécution des requêtes SQL</h4>";
    
    // Inclusion du fichier SQL
    $sql = [];
    include(dirname(__FILE__) . '/sql/install.php');
    
    foreach ($sql as $index => $query) {
        $queryNumber = $index + 1;
        echo "<p style='font-family:monospace;margin:5px 0;'><strong>Requête {$queryNumber}:</strong> ";
        try {
            $result = $db->execute($query);
            if ($result) {
                echo "<span class='success'>✓ Succès</span>";
            } else {
                echo "<span class='error'>✗ Échec</span>";
            }
        } catch (Exception $e) {
            echo "<span class='error'>✗ Erreur: " . htmlspecialchars($e->getMessage()) . "</span>";
        }
        echo "</p>";
    }
    
    echo "</div>";
} else {
    echo "<a href='?action=fix_tables' class='btn'>Exécuter les requêtes SQL</a>";
}
echo "</div>";

// Action 2: Correction des onglets d'administration
echo "<div class='info'>";
echo "<h3>Réparation des onglets d'administration</h3>";
echo "<p>Cette action supprime et recrée les onglets d'administration du module.</p>";

if (isset($_GET['action']) && $_GET['action'] === 'fix_tabs') {
    echo "<div style='background:#e8f5e9;padding:10px;margin:10px 0;border-left:4px solid green;'>";
    echo "<h4>Résultats de la réparation des onglets</h4>";
    
    // Suppression des anciens onglets
    foreach ($adminTabs as $tabClass) {
        $tabId = Tab::getIdFromClassName($tabClass);
        if ($tabId) {
            $tab = new Tab($tabId);
            $deleted = $tab->delete();
            if ($deleted) {
                echo "<p>Suppression de l'onglet {$tabClass}: <span class='success'>Réussi</span></p>";
            } else {
                echo "<p>Suppression de l'onglet {$tabClass}: <span class='error'>Échec</span></p>";
            }
        }
    }
    
    // Recréation des onglets par insertion directe
    $defaultLangId = (int)Configuration::get('PS_LANG_DEFAULT');
    $languages = Language::getLanguages(false);
    
    $tabsToCreate = [
        ['AdminIaBot', 'Assistant IA', 'AdminParentModulesSf'],
        ['AdminIaBotDashboard', 'Tableau de bord', 'AdminIaBot'],
        ['AdminIaBotConfiguration', 'Configuration', 'AdminIaBot'],
        ['AdminIaBotKnowledge', 'Base de connaissances', 'AdminIaBot'],
        ['AdminIaBotRecommendations', 'Recommandations', 'AdminIaBot'],
        ['AdminIaBotStatistics', 'Statistiques', 'AdminIaBot']
    ];
    
    foreach ($tabsToCreate as $tabInfo) {
        $className = $tabInfo[0];
        $tabName = $tabInfo[1];
        $parentClass = $tabInfo[2];
        
        $idParent = (int)Tab::getIdFromClassName($parentClass);
        
        // Création de l'onglet via SQL direct
        $db->insert('tab', [
            'id_parent' => $idParent,
            'class_name' => pSQL($className),
            'module' => 'iabot',
            'position' => 0,
            'active' => 1
        ]);
        
        $idTab = (int)$db->Insert_ID();
        
        // Insertion des traductions
        foreach ($languages as $language) {
            $db->insert('tab_lang', [
                'id_tab' => $idTab,
                'id_lang' => (int)$language['id_lang'],
                'name' => pSQL($tabName)
            ]);
        }
        
        if ($idTab) {
            echo "<p>Création de l'onglet {$className}: <span class='success'>Réussi (ID: {$idTab})</span></p>";
        } else {
            echo "<p>Création de l'onglet {$className}: <span class='error'>Échec</span></p>";
        }
    }
    
    echo "</div>";
} else {
    echo "<a href='?action=fix_tabs' class='btn'>Réparer les onglets</a>";
}
echo "</div>";

// Action 3: Installation propre
echo "<div class='info'>";
echo "<h3>Réinstallation complète du module</h3>";
echo "<p>Cette action désinstalle complètement le module puis le réinstalle proprement.</p>";

if (isset($_GET['action']) && $_GET['action'] === 'reinstall') {
    echo "<div style='background:#e8f5e9;padding:10px;margin:10px 0;border-left:4px solid green;'>";
    echo "<h4>Résultats de la réinstallation</h4>";
    
    // Chargement du module
    $module = Module::getInstanceByName('iabot');
    
    if ($module) {
        // Désinstallation
        $uninstalled = $module->uninstall();
        if ($uninstalled) {
            echo "<p>Désinstallation du module: <span class='success'>Réussi</span></p>";
        } else {
            echo "<p>Désinstallation du module: <span class='error'>Échec</span></p>";
        }
        
        // Réinstallation
        $installed = $module->install();
        if ($installed) {
            echo "<p>Réinstallation du module: <span class='success'>Réussi</span></p>";
        } else {
            echo "<p>Réinstallation du module: <span class='error'>Échec</span></p>";
        }
    } else {
        echo "<p class='error'>Impossible de charger l'instance du module</p>";
    }
    
    echo "</div>";
} else {
    echo "<a href='?action=reinstall' class='btn'>Réinstaller le module</a>";
}
echo "</div>";

// Action 4: Nettoyage des tables
echo "<div class='info'>";
echo "<h3>Nettoyage complet des tables</h3>";
echo "<p><strong>ATTENTION:</strong> Cette action supprime toutes les tables du module. À utiliser uniquement en dernier recours.</p>";

if (isset($_GET['action']) && $_GET['action'] === 'clean_tables') {
    echo "<div style='background:#e8f5e9;padding:10px;margin:10px 0;border-left:4px solid green;'>";
    echo "<h4>Résultats du nettoyage</h4>";
    
    // Suppression des tables
    foreach ($moduleTables as $table) {
        if ($table !== 'tab') { // Ne pas supprimer la table des onglets native
            $tableName = $tablePrefix . $table;
            $query = "DROP TABLE IF EXISTS `{$tableName}`";
            $result = $db->execute($query);
            
            if ($result) {
                echo "<p>Suppression de la table {$tableName}: <span class='success'>Réussi</span></p>";
            } else {
                echo "<p>Suppression de la table {$tableName}: <span class='error'>Échec</span></p>";
            }
        }
    }
    
    echo "</div>";
} else {
    echo "<a href='?action=clean_tables' class='btn' style='background:#d32f2f;' onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer toutes les tables du module ?\");'>Supprimer les tables</a>";
}
echo "</div>";

// Revenir à l'administration
echo "<p style='margin-top:30px;'><a href='../../admin-dev/index.php' class='btn'>Retour à l'administration</a></p>";
