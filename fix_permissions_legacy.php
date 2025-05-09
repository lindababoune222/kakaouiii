<?php
/**
 * Script de correction des permissions pour le module IaBot
 * Compatible avec PrestaShop 1.6.x
 */

// Initialisation PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

header('Content-Type: text/html; charset=utf-8');
echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #25B9D7;">Correction des permissions IaBot (mode Legacy)</h1>';

// Fonction de détection de la version de PrestaShop
function getPrestaShopVersion() {
    if (defined('_PS_VERSION_')) {
        return _PS_VERSION_;
    }
    return 'unknown';
}

// Fonction de correction des permissions pour PrestaShop 1.6
function fixIaBotPermissionsLegacy() {
    $result = [
        'success' => true,
        'messages' => [],
        'errors' => []
    ];
    
    $result['messages'][] = "Version de PrestaShop détectée : " . getPrestaShopVersion();
    
    // 1. Récupérer tous les onglets du module
    $tabsToCheck = [
        'AdminIaBot',
        'AdminIaBotDashboard',
        'AdminIaBotConfiguration', 
        'AdminIaBotKnowledge',
        'AdminIaBotRecommendations',
        'AdminIaBotStatistics'
    ];
    
    $tabIds = [];
    foreach ($tabsToCheck as $className) {
        $tabId = (int)Tab::getIdFromClassName($className);
        if ($tabId) {
            $tabIds[$className] = $tabId;
            $result['messages'][] = "Onglet {$className} trouvé avec ID: {$tabId}";
        } else {
            $result['errors'][] = "Onglet {$className} non trouvé!";
        }
    }
    
    if (empty($tabIds)) {
        $result['success'] = false;
        $result['errors'][] = "Aucun onglet du module n'a été trouvé. Veuillez d'abord installer le module correctement.";
        return $result;
    }
    
    // 2. Récupérer tous les profils d'administration (1 = super admin par défaut)
    $adminProfiles = Db::getInstance()->executeS("
        SELECT id_profile, name 
        FROM `" . _DB_PREFIX_ . "profile` 
        WHERE id_profile = 1 OR id_profile IN (
            SELECT id_profile FROM `" . _DB_PREFIX_ . "profile_lang` 
            WHERE name LIKE '%admin%'
        )
    ");
    
    $result['messages'][] = "Profils d'administration trouvés: " . count($adminProfiles);
    
    // 3. Récupérer le module
    $moduleId = (int)Db::getInstance()->getValue("SELECT id_module FROM `" . _DB_PREFIX_ . "module` WHERE name = 'iabot'");
    if (!$moduleId) {
        $result['errors'][] = "Module IaBot non trouvé!";
    } else {
        $result['messages'][] = "Module IaBot trouvé avec ID: {$moduleId}";
    }
    
    // 4. Pour chaque onglet, corriger les permissions
    foreach ($tabIds as $className => $tabId) {
        $result['messages'][] = "Configuration des permissions pour {$className} (ID: {$tabId})";
        
        // Pour chaque profil administrateur, assigner les permissions
        foreach ($adminProfiles as $profile) {
            $profileId = (int)$profile['id_profile'];
            
            // Dans PrestaShop 1.6, on utilise la table access directement
            $sql = "INSERT IGNORE INTO `" . _DB_PREFIX_ . "access` 
                   (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`) 
                   VALUES ({$profileId}, {$tabId}, 1, 1, 1, 1)";
            
            Db::getInstance()->execute($sql);
            
            $result['messages'][] = "- Permissions d'onglet ajoutées pour profil #{$profileId}";
        }
        
        // Assurer la visibilité de l'onglet
        try {
            $tab = new Tab($tabId);
            if (!$tab->active) {
                $tab->active = 1;
                $tab->save();
                $result['messages'][] = "- Onglet {$className} activé";
            }
        } catch (Exception $e) {
            $result['errors'][] = "Erreur lors de la mise à jour de l'onglet {$className}: " . $e->getMessage();
        }
    }
    
    // 5. Vérifier les permissions directes sur le module
    if ($moduleId) {
        foreach ($adminProfiles as $profile) {
            $profileId = (int)$profile['id_profile'];
            
            // Assurer que le profil a les permissions sur le module
            $sql = "INSERT IGNORE INTO `" . _DB_PREFIX_ . "module_access` 
                   (`id_profile`, `id_module`, `configure`) 
                   VALUES ({$profileId}, {$moduleId}, 1)";
            
            // Vérifier si les colonnes supplémentaires existent
            $columnsResult = Db::getInstance()->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . "module_access` LIKE 'view'");
            if (!empty($columnsResult)) {
                $sql = "INSERT IGNORE INTO `" . _DB_PREFIX_ . "module_access` 
                       (`id_profile`, `id_module`, `view`, `configure`, `uninstall`) 
                       VALUES ({$profileId}, {$moduleId}, 1, 1, 1)";
            }
            
            Db::getInstance()->execute($sql);
            
            $result['messages'][] = "Permissions de module ajoutées pour le profil #{$profileId}";
        }
    }
    
    // 6. Correction des onglets parents
    // S'assurer que les onglets parents sont également accessibles
    foreach ($tabIds as $className => $tabId) {
        $tab = new Tab($tabId);
        if ($tab->id_parent > 0) {
            $parentTab = new Tab($tab->id_parent);
            if ($parentTab->id) {
                $result['messages'][] = "Vérification de l'onglet parent: " . $parentTab->class_name . " (ID: " . $parentTab->id . ")";
                
                foreach ($adminProfiles as $profile) {
                    $profileId = (int)$profile['id_profile'];
                    
                    // Dans PrestaShop 1.6, on utilise la table access directement
                    $sql = "INSERT IGNORE INTO `" . _DB_PREFIX_ . "access` 
                           (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`) 
                           VALUES ({$profileId}, {$parentTab->id}, 1, 1, 1, 1)";
                    
                    Db::getInstance()->execute($sql);
                    
                    $result['messages'][] = "- Permissions d'onglet parent ajoutées pour profil #{$profileId}";
                }
            }
        }
    }
    
    // 7. Vider les caches
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

// Exécuter la correction
$result = fixIaBotPermissionsLegacy();

// Afficher les résultats
if ($result['success']) {
    echo '<div style="color: green; padding: 15px; border-left: 5px solid green; background: #f1fff1;">
        <h2>✓ Correction des permissions terminée avec succès!</h2>
        <p>Les permissions ont été correctement configurées pour tous les onglets du module IaBot en mode PrestaShop 1.6.</p>
    </div>';
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

echo '<div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
    <h3>Prochaines étapes</h3>
    <ol>
        <li>Déconnectez-vous de l\'administration</li>
        <li>Reconnectez-vous pour que tous les changements prennent effet</li>
        <li>Vous devriez maintenant avoir accès à tous les onglets du module IaBot</li>
    </ol>
    <p><a href="../../../admin-dev/" style="display:inline-block; margin-top:20px; padding:10px 15px; background-color:#25B9D7; color:white; text-decoration:none; border-radius:4px;">Retour à l\'administration</a></p>
</div>
</div>';
