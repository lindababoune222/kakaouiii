<?php
/**
 * Script de nettoyage de l'interface dupliquée du module IaBot
 * 
 * Ce script identifie et supprime les onglets et sections dupliqués
 * dans l'interface d'administration du module.
 */

// Initialisation PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

header('Content-Type: text/html; charset=utf-8');
echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #25B9D7;">Nettoyage de l\'interface dupliquée IaBot</h1>';

// Fonction pour nettoyer les onglets dupliqués
function cleanDuplicateInterface() {
    $result = [
        'success' => true,
        'messages' => [],
        'errors' => [],
        'deleted_tabs' => []
    ];
    
    // 1. Récupérer les onglets du module
    $tabNames = [
        'AdminIaBot',
        'AdminIaBotDashboard',
        'AdminIaBotConfiguration', 
        'AdminIaBotKnowledge',
        'AdminIaBotRecommendations',
        'AdminIaBotStatistics'
    ];
    
    // Obtenir l'ID du module
    $moduleId = (int)Db::getInstance()->getValue("SELECT id_module FROM `" . _DB_PREFIX_ . "module` WHERE name = 'iabot'");
    if (!$moduleId) {
        $result['errors'][] = "Module IaBot non trouvé dans la base de données.";
        $result['success'] = false;
        return $result;
    }
    
    $result['messages'][] = "Module IaBot trouvé avec ID: {$moduleId}";
    
    // 2. Pour chaque nom d'onglet, vérifier s'il existe des doublons
    foreach ($tabNames as $className) {
        // Obtenir tous les onglets avec ce nom de classe
        $tabs = Db::getInstance()->executeS("
            SELECT t.id_tab, t.id_parent, t.position, t.module, t.active, tl.name 
            FROM `" . _DB_PREFIX_ . "tab` t
            LEFT JOIN `" . _DB_PREFIX_ . "tab_lang` tl ON t.id_tab = tl.id_tab
            WHERE t.class_name = '" . pSQL($className) . "'
            ORDER BY t.id_tab
        ");
        
        if (count($tabs) > 1) {
            $result['messages'][] = "Trouvé " . count($tabs) . " onglets pour la classe {$className}";
            
            // Garder le premier onglet (le plus ancien par ID) et supprimer les autres
            $keepTab = array_shift($tabs);
            $result['messages'][] = "Conservation de l'onglet #{$keepTab['id_tab']} ({$keepTab['name']})";
            
            foreach ($tabs as $duplicateTab) {
                $result['messages'][] = "Suppression du doublon #{$duplicateTab['id_tab']} ({$duplicateTab['name']})";
                
                // Supprimer l'onglet dupliqué
                Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . "tab` WHERE id_tab = " . (int)$duplicateTab['id_tab']);
                Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . "tab_lang` WHERE id_tab = " . (int)$duplicateTab['id_tab']);
                Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . "access` WHERE id_tab = " . (int)$duplicateTab['id_tab']);
                
                $result['deleted_tabs'][] = [
                    'id' => $duplicateTab['id_tab'],
                    'name' => $duplicateTab['name'],
                    'class' => $className
                ];
            }
        } else if (count($tabs) == 1) {
            $result['messages'][] = "Un seul onglet trouvé pour la classe {$className}, aucune action nécessaire.";
        } else {
            $result['messages'][] = "Aucun onglet trouvé pour la classe {$className}.";
        }
    }
    
    // 3. Vérifier les onglets orphelins (sans parent valide)
    $orphanTabs = Db::getInstance()->executeS("
        SELECT t.id_tab, t.class_name, t.id_parent, tl.name
        FROM `" . _DB_PREFIX_ . "tab` t
        LEFT JOIN `" . _DB_PREFIX_ . "tab_lang` tl ON t.id_tab = tl.id_tab
        WHERE t.module = 'iabot' 
        AND t.id_parent NOT IN (SELECT id_tab FROM `" . _DB_PREFIX_ . "tab`)
        AND t.id_parent > 0
    ");
    
    if (!empty($orphanTabs)) {
        $result['messages'][] = "Trouvé " . count($orphanTabs) . " onglets orphelins (sans parent valide).";
        
        foreach ($orphanTabs as $orphanTab) {
            // Trouver un nouveau parent valide
            $parentId = (int)Tab::getIdFromClassName('AdminParentModulesSf');
            if (!$parentId) {
                $parentId = (int)Tab::getIdFromClassName('AdminModules');
            }
            
            if ($parentId) {
                // Mettre à jour le parent de l'onglet orphelin
                Db::getInstance()->execute("
                    UPDATE `" . _DB_PREFIX_ . "tab` 
                    SET id_parent = " . (int)$parentId . " 
                    WHERE id_tab = " . (int)$orphanTab['id_tab']
                );
                
                $result['messages'][] = "Onglet orphelin #{$orphanTab['id_tab']} ({$orphanTab['name']}) rattaché au parent #{$parentId}.";
            } else {
                // Si aucun parent valide n'est trouvé, supprimer l'onglet orphelin
                Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . "tab` WHERE id_tab = " . (int)$orphanTab['id_tab']);
                Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . "tab_lang` WHERE id_tab = " . (int)$orphanTab['id_tab']);
                Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . "access` WHERE id_tab = " . (int)$orphanTab['id_tab']);
                
                $result['messages'][] = "Onglet orphelin #{$orphanTab['id_tab']} ({$orphanTab['name']}) supprimé car aucun parent valide n'a été trouvé.";
                $result['deleted_tabs'][] = [
                    'id' => $orphanTab['id_tab'],
                    'name' => $orphanTab['name'],
                    'class' => $orphanTab['class_name'],
                    'reason' => 'orphelin'
                ];
            }
        }
    } else {
        $result['messages'][] = "Aucun onglet orphelin trouvé.";
    }
    
    // 4. Reconstruire les positions des onglets
    $mainTabId = (int)Tab::getIdFromClassName('AdminIaBot');
    if ($mainTabId) {
        // Obtenir tous les onglets enfants
        $childTabs = Db::getInstance()->executeS("
            SELECT id_tab, class_name, position 
            FROM `" . _DB_PREFIX_ . "tab` 
            WHERE id_parent = " . (int)$mainTabId . "
            ORDER BY position
        ");
        
        // Réorganiser les positions
        $position = 0;
        foreach ($childTabs as $childTab) {
            Db::getInstance()->execute("
                UPDATE `" . _DB_PREFIX_ . "tab` 
                SET position = " . (int)$position . " 
                WHERE id_tab = " . (int)$childTab['id_tab']
            );
            
            $result['messages'][] = "Position de l'onglet {$childTab['class_name']} mise à jour: {$position}";
            $position++;
        }
    }
    
    // 5. Activer l'onglet principal et tous ses enfants
    if ($mainTabId) {
        Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "tab` SET active = 1 WHERE id_tab = " . (int)$mainTabId);
        Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "tab` SET active = 1 WHERE id_parent = " . (int)$mainTabId);
        $result['messages'][] = "Tous les onglets du module ont été activés.";
    }
    
    // 6. Vider les caches
    if (class_exists('Tools')) {
        if (method_exists('Tools', 'clearCache')) {
            Tools::clearCache();
            $result['messages'][] = "Cache vidé";
        }
        
        if (method_exists('Tools', 'clearSmartyCache')) {
            Tools::clearSmartyCache();
            $result['messages'][] = "Cache Smarty vidé";
        }
        
        if (method_exists('Tools', 'clearXMLCache')) {
            Tools::clearXMLCache();
            $result['messages'][] = "Cache XML vidé";
        }
        
        if (method_exists('Tools', 'generateIndex')) {
            Tools::generateIndex();
            $result['messages'][] = "Index régénéré";
        }
    }
    
    if (class_exists('Media') && method_exists('Media', 'clearCache')) {
        Media::clearCache();
        $result['messages'][] = "Cache Media vidé";
    }
    
    return $result;
}

// Exécuter le nettoyage
$result = cleanDuplicateInterface();

// Afficher les résultats
if ($result['success']) {
    echo '<div style="color: green; padding: 15px; border-left: 5px solid green; background: #f1fff1;">
        <h2>✓ Nettoyage de l\'interface dupliquée terminé avec succès!</h2>';
    
    if (!empty($result['deleted_tabs'])) {
        echo '<p>' . count($result['deleted_tabs']) . ' onglets dupliqués ont été supprimés.</p>';
    } else {
        echo '<p>Aucun onglet en double n\'a été trouvé, mais l\'interface a été optimisée.</p>';
    }
    
    echo '</div>';
} else {
    echo '<div style="color: red; padding: 15px; border-left: 5px solid red; background: #fff1f1;">
        <h2>⚠ Des erreurs sont survenues</h2>
        <ul>';
    foreach ($result['errors'] as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>
    </div>';
}

if (!empty($result['messages'])) {
    echo '<div style="margin-top: 20px; padding: 15px; background: #f8f8f8;">
        <h3>Détails des opérations effectuées</h3>
        <ul style="max-height: 300px; overflow-y: auto;">';
    foreach ($result['messages'] as $message) {
        echo '<li>' . htmlspecialchars($message) . '</li>';
    }
    echo '</ul>
    </div>';
}

if (!empty($result['deleted_tabs'])) {
    echo '<div style="margin-top: 20px; padding: 15px; background: #f9f9ff;">
        <h3>Onglets supprimés</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #eaeaea;">
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">ID</th>
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Nom</th>
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Classe</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($result['deleted_tabs'] as $tab) {
        echo '<tr>
                <td style="padding: 8px; border: 1px solid #ddd;">' . $tab['id'] . '</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($tab['name']) . '</td>
                <td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($tab['class']) . '</td>
            </tr>';
    }
    
    echo '</tbody>
        </table>
    </div>';
}

echo '<div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
    <h3>Prochaines étapes</h3>
    <ol>
        <li>Déconnectez-vous de l\'administration</li>
        <li>Reconnectez-vous pour que tous les changements prennent effet</li>
        <li>Vérifiez que les doublons ne sont plus présents dans l\'interface</li>
    </ol>
    <p><a href="../../../admin-dev/" style="display:inline-block; margin-top:20px; padding:10px 15px; background-color:#25B9D7; color:white; text-decoration:none; border-radius:4px;">Retour à l\'administration</a></p>
</div>
</div>';
