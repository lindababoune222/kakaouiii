<?php
/**
 * Script de nettoyage des onglets dupliqués du module IaBot
 * 
 * Ce script supprime tous les doublons de menus et onglets du module
 */

// Initialisation de PrestaShop
define('_PS_ADMIN_DIR_', 1);
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');

// Vérification de sécurité (accès administrateur uniquement)
if (defined('_PS_ADMIN_DIR_') && Context::getContext()->employee && Context::getContext()->employee->isLoggedBack()) {
    // Continuer avec le script
} else {
    exit('Accès réservé aux administrateurs connectés.');
}

$db = Db::getInstance();
$prefix = _DB_PREFIX_;

// Style CSS
echo '
<style>
    body {font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto;}
    h1 {color: #333; border-bottom: 1px solid #ddd; padding-bottom: 10px;}
    .success {color: green; font-weight: bold;}
    .error {color: red; font-weight: bold;}
    .warning {color: orange; font-weight: bold;}
    .info {color: #0d47a1; font-weight: bold;}
    .step {background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #0d47a1;}
    pre {background: #f4f4f4; padding: 10px; overflow: auto; border-radius: 4px;}
    .btn {
        display: inline-block; 
        padding: 8px 16px; 
        background: #0d47a1; 
        color: white; 
        text-decoration: none; 
        border-radius: 4px;
        margin: 5px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    table, th, td {
        border: 1px solid #ddd;
    }
    th, td {
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f4f4f4;
    }
</style>
';

echo '<h1>Nettoyage des onglets dupliqués du module IaBot</h1>';

// Étape 1: Analyse des onglets du module
echo '<div class="step">';
echo '<h2>Étape 1: Analyse des onglets du module</h2>';

// Récupérer tous les onglets du module iabot
$tabs = $db->executeS("
    SELECT t.id_tab, t.class_name, t.id_parent, t.position, t.module, tl.name 
    FROM `{$prefix}tab` t
    JOIN `{$prefix}tab_lang` tl ON t.id_tab = tl.id_tab
    WHERE t.module = 'iabot'
    AND tl.id_lang = " . (int)Configuration::get('PS_LANG_DEFAULT') . "
    ORDER BY t.class_name, t.id_tab
");

// Regrouper les onglets par class_name pour identifier les doublons
$tabsByClass = [];
foreach ($tabs as $tab) {
    if (!isset($tabsByClass[$tab['class_name']])) {
        $tabsByClass[$tab['class_name']] = [];
    }
    $tabsByClass[$tab['class_name']][] = $tab;
}

// Afficher les résultats
echo '<p>Analyse des onglets du module iabot:</p>';

echo '<table>';
echo '<tr><th>Class Name</th><th>Nombre d\'occurrences</th><th>Détails</th></tr>';

$totalDuplicates = 0;
$hasDuplicates = false;

foreach ($tabsByClass as $className => $classTabs) {
    $count = count($classTabs);
    $totalDuplicates += ($count - 1);
    
    echo '<tr>';
    echo '<td>' . htmlspecialchars($className) . '</td>';
    echo '<td>' . $count . '</td>';
    echo '<td>';
    
    if ($count > 1) {
        $hasDuplicates = true;
        echo '<span class="warning">DUPLIQUÉ</span><br>';
        foreach ($classTabs as $tab) {
            echo 'ID: ' . $tab['id_tab'] . ', Nom: ' . htmlspecialchars($tab['name']) . '<br>';
        }
    } else {
        echo '<span class="success">OK</span>';
    }
    
    echo '</td>';
    echo '</tr>';
}

echo '</table>';

if ($hasDuplicates) {
    echo '<p class="warning">Nombre total de doublons détectés: ' . $totalDuplicates . '</p>';
} else {
    echo '<p class="success">Aucun doublon détecté. Les onglets sont propres.</p>';
}

echo '</div>';

// Étape 2: Nettoyage des onglets dupliqués
echo '<div class="step">';
echo '<h2>Étape 2: Nettoyage des onglets dupliqués</h2>';

if (isset($_GET['action']) && $_GET['action'] == 'clean') {
    // Stratégie 1: Supprimer tous les onglets du module
    echo '<h3>Nettoyage de tous les onglets du module</h3>';
    
    // Récupérer tous les IDs d'onglets
    $tabIds = array_map(function($tab) {
        return $tab['id_tab'];
    }, $tabs);
    
    if (!empty($tabIds)) {
        // Supprimer les entrées de la table tab_lang
        $result1 = $db->execute("
            DELETE FROM `{$prefix}tab_lang`
            WHERE id_tab IN (" . implode(',', $tabIds) . ")
        ");
        
        // Supprimer les entrées de la table tab
        $result2 = $db->execute("
            DELETE FROM `{$prefix}tab`
            WHERE id_tab IN (" . implode(',', $tabIds) . ")
        ");
        
        if ($result1 && $result2) {
            echo '<p class="success">Tous les onglets du module ont été supprimés avec succès.</p>';
        } else {
            echo '<p class="error">Erreur lors de la suppression des onglets.</p>';
        }
    } else {
        echo '<p class="info">Aucun onglet à supprimer.</p>';
    }
    
    // Stratégie 2: Recréer correctement les onglets
    echo '<h3>Recréation des onglets du module</h3>';
    
    // Structure des onglets à créer
    $tabsToCreate = [
        ['AdminIaBot', 'Assistant IA', 'AdminParentModulesSf'],
        ['AdminIaBotDashboard', 'Tableau de bord', 'AdminIaBot'],
        ['AdminIaBotConfiguration', 'Configuration', 'AdminIaBot'],
        ['AdminIaBotKnowledge', 'Base de connaissances', 'AdminIaBot'],
        ['AdminIaBotRecommendations', 'Recommandations', 'AdminIaBot'],
        ['AdminIaBotStatistics', 'Statistiques', 'AdminIaBot']
    ];
    
    $createdTabs = [];
    
    // Créer l'onglet parent d'abord
    $idParentModules = (int)Tab::getIdFromClassName('AdminParentModulesSf');
    $idMainTab = 0;
    
    foreach ($tabsToCreate as $i => $tabInfo) {
        $className = $tabInfo[0];
        $tabName = $tabInfo[1];
        $parentClassName = $tabInfo[2];
        
        // Déterminer l'ID parent
        $idParent = 0;
        if ($className == 'AdminIaBot') {
            $idParent = $idParentModules;
        } else {
            $idParent = $idMainTab;
        }
        
        // Créer l'onglet
        $db->insert('tab', [
            'id_parent' => (int)$idParent,
            'class_name' => pSQL($className),
            'module' => 'iabot',
            'position' => (int)$i,
            'active' => 1
        ]);
        
        $idTab = (int)$db->Insert_ID();
        
        // Si c'est l'onglet principal, enregistrer son ID
        if ($className == 'AdminIaBot') {
            $idMainTab = $idTab;
        }
        
        // Insérer les traductions
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            $db->insert('tab_lang', [
                'id_tab' => (int)$idTab,
                'id_lang' => (int)$language['id_lang'],
                'name' => pSQL($tabName)
            ]);
        }
        
        $createdTabs[] = [
            'id_tab' => $idTab,
            'class_name' => $className,
            'name' => $tabName
        ];
    }
    
    // Afficher les onglets créés
    if (!empty($createdTabs)) {
        echo '<p class="success">Nouveaux onglets créés avec succès:</p>';
        echo '<ul>';
        foreach ($createdTabs as $tab) {
            echo '<li>' . htmlspecialchars($tab['name']) . ' (ID: ' . $tab['id_tab'] . ', Classe: ' . $tab['class_name'] . ')</li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="error">Aucun onglet n\'a été créé.</p>';
    }
    
    echo '<p>Le nettoyage est terminé. Vous pouvez maintenant <a href="../../admin-dev/index.php?controller=AdminModules" class="btn">retourner à l\'administration</a>.</p>';
} else {
    if ($hasDuplicates) {
        echo '<p>Des doublons ont été détectés dans les onglets du module. Cliquez sur le bouton ci-dessous pour nettoyer et recréer correctement les onglets:</p>';
        echo '<a href="?action=clean" class="btn" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer tous les onglets du module et les recréer proprement ?\');">Nettoyer les onglets dupliqués</a>';
    } else {
        echo '<p class="success">Aucun nettoyage nécessaire, les onglets sont propres.</p>';
    }
}

echo '</div>';

// Étape 3: Autres actions
echo '<div class="step">';
echo '<h2>Étape 3: Autres actions</h2>';

echo '<ul>';
echo '<li><a href="../../admin-dev/index.php?controller=AdminModules" class="btn">Retourner à l\'administration des modules</a></li>';
echo '<li><a href="fix_config_table.php" class="btn">Revenir à l\'outil de réparation de la table de configuration</a></li>';
echo '</ul>';

echo '</div>';
