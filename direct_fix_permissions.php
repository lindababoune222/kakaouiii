<?php
/**
 * Script de correction directe des permissions pour le module IaBot
 * 
 * Ce script applique directement les corrections de permissions sans passer par l'interface d'administration
 */

// Initialisation PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

// Désactiver la vérification des tokens CSRF pour cette opération
$adminController = Context::getContext()->controller;
if (method_exists($adminController, 'disableDefaultToken')) {
    $adminController->disableDefaultToken();
}

header('Content-Type: text/html; charset=utf-8');
echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #25B9D7;">Correction directe des permissions IaBot</h1>';

// Fonction de correction directe des permissions
function fixIaBotPermissions() {
    $result = [
        'success' => true,
        'messages' => [],
        'errors' => []
    ];
    
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
        WHERE id_profile = 1 OR id_profile IN (SELECT id_profile FROM `" . _DB_PREFIX_ . "profile_lang` WHERE name LIKE '%admin%')
    ");
    
    $result['messages'][] = "Profils d'administration trouvés: " . count($adminProfiles);
    
    // 3. Récupérer tous les employés avec ces profils
    $employees = [];
    foreach ($adminProfiles as $profile) {
        $profileEmployees = Db::getInstance()->executeS("
            SELECT id_employee, email, firstname, lastname, id_profile 
            FROM `" . _DB_PREFIX_ . "employee` 
            WHERE id_profile = " . (int)$profile['id_profile'] . " AND active = 1
        ");
        
        $employees = array_merge($employees, $profileEmployees);
    }
    
    $result['messages'][] = "Employés administrateurs trouvés: " . count($employees);
    
    // 4. Récupérer le module
    $moduleId = (int)Db::getInstance()->getValue("SELECT id_module FROM `" . _DB_PREFIX_ . "module` WHERE name = 'iabot'");
    if (!$moduleId) {
        $result['errors'][] = "Module IaBot non trouvé!";
    } else {
        $result['messages'][] = "Module IaBot trouvé avec ID: {$moduleId}";
    }
    
    // 5. Vérifier la table des autorisations
    $tablePermission = _DB_PREFIX_ . 'authorization_role';
    $tablePermissionExists = count(Db::getInstance()->executeS("SHOW TABLES LIKE '" . $tablePermission . "'")) > 0;
    
    if (!$tablePermissionExists) {
        $result['success'] = false;
        $result['errors'][] = "La table d'autorisations n'existe pas dans cette version de PrestaShop!";
        return $result;
    }
    
    // 6. Pour chaque onglet, récupérer et vérifier les rôles d'autorisation
    $roles = ['CREATE', 'READ', 'UPDATE', 'DELETE'];
    
    foreach ($tabIds as $className => $tabId) {
        $result['messages'][] = "Configuration des permissions pour {$className} (ID: {$tabId})";
        
        // 1. Vérifier si les rôles d'autorisation existent pour cet onglet
        $roleIds = [];
        foreach ($roles as $role) {
            // Format attendu pour le slug PrestaShop
            $slug = "ROLE_MOD_TAB_IABOT_" . strtoupper($className) . "_" . $role;
            
            $roleId = Db::getInstance()->getValue(
                "SELECT id_authorization_role FROM `" . _DB_PREFIX_ . "authorization_role` 
                 WHERE slug = '{$slug}'"
            );
            
            if ($roleId) {
                $roleIds[$role] = $roleId;
                $result['messages'][] = "- Rôle {$role} trouvé avec ID: {$roleId}";
            } else {
                // Si le rôle n'existe pas, le créer
                Db::getInstance()->execute(
                    "INSERT INTO `" . _DB_PREFIX_ . "authorization_role` (`slug`) 
                     VALUES ('{$slug}')"
                );
                $roleId = (int)Db::getInstance()->Insert_ID();
                $roleIds[$role] = $roleId;
                $result['messages'][] = "- Rôle {$role} créé avec ID: {$roleId}";
            }
        }
        
        // 2. Pour chaque employé administrateur, assigner ces rôles
        foreach ($employees as $employee) {
            foreach ($roleIds as $role => $roleId) {
                // Ajouter l'autorisation pour cet employé
                Db::getInstance()->execute(
                    "INSERT IGNORE INTO `" . _DB_PREFIX_ . "employee_access` 
                     (id_employee, id_authorization_role) 
                     VALUES (" . (int)$employee['id_employee'] . ", " . (int)$roleId . ")"
                );
            }
            $result['messages'][] = "- Permissions ajoutées pour employé #{$employee['id_employee']}";
        }
        
        // 3. Pour chaque profil administrateur, assigner ces rôles
        foreach ($adminProfiles as $profile) {
            $profileId = (int)$profile['id_profile'];
            
            foreach ($roleIds as $role => $roleId) {
                // Ajouter les permissions d'accès
                Db::getInstance()->execute(
                    "INSERT IGNORE INTO `" . _DB_PREFIX_ . "access` 
                     (id_profile, id_authorization_role) 
                     VALUES ({$profileId}, {$roleId})"
                );
            }
            
            // Ajouter aussi les permissions d'accès au tab directement (double sécurité)
            Db::getInstance()->execute(
                "INSERT IGNORE INTO `" . _DB_PREFIX_ . "access` 
                 (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`) 
                 VALUES ({$profileId}, {$tabId}, 1, 1, 1, 1)"
            );
            
            $result['messages'][] = "- Permissions ajoutées pour profil #{$profileId}";
        }
        
        // 4. Assurer la visibilité de l'onglet
        $tab = new Tab($tabId);
        if (!$tab->active) {
            $tab->active = 1;
            $tab->save();
            $result['messages'][] = "- Onglet {$className} activé";
        }
    }
    
    // 7. Vérifier les permissions directes sur le module
    if ($moduleId) {
        foreach ($adminProfiles as $profile) {
            $profileId = (int)$profile['id_profile'];
            
            // Assurer que le profil a les permissions sur le module
            Db::getInstance()->execute(
                "INSERT IGNORE INTO `" . _DB_PREFIX_ . "module_access` 
                 (`id_profile`, `id_module`, `view`, `configure`, `uninstall`) 
                 VALUES ({$profileId}, {$moduleId}, 1, 1, 1)"
            );
            
            $result['messages'][] = "Permissions de module ajoutées pour le profil #{$profileId}";
        }
    }
    
    // 8. Vider les caches
    if (method_exists('Tools', 'clearSmartyCache')) {
        Tools::clearSmartyCache();
    }
    if (method_exists('Tools', 'clearXMLCache')) {
        Tools::clearXMLCache();
    }
    if (method_exists('Media', 'clearCache')) {
        Media::clearCache();
    }
    if (method_exists('Tools', 'generateIndex')) {
        Tools::generateIndex();
    }
    
    $result['messages'][] = "Tous les caches ont été vidés";
    
    return $result;
}

// Exécuter la correction
$result = fixIaBotPermissions();

// Afficher les résultats
if ($result['success']) {
    echo '<div style="color: green; padding: 15px; border-left: 5px solid green; background: #f1fff1;">
        <h2>✓ Correction des permissions terminée avec succès!</h2>
        <p>Les permissions ont été correctement configurées pour tous les onglets du module IaBot.</p>
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
