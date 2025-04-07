<?php
/**
 * Script de correction des permissions pour le module IaBot
 * 
 * Ce script s'assure que toutes les permissions sont correctement configurées
 * pour les onglets d'administration du module IaBot, particulièrement pour
 * les comptes super admin.
 */

// Initialisation PrestaShop
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');

// Vérification de sécurité (accès administrateur uniquement)
if (!Context::getContext()->employee || !Context::getContext()->employee->isLoggedBack()) {
    die('Accès non autorisé');
}

echo '<h1>Correction des permissions pour le module IaBot</h1>';

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
        echo "<p>Onglet {$className} trouvé avec ID: {$tabId}</p>";
    } else {
        echo "<p style='color:red'>Onglet {$className} non trouvé!</p>";
    }
}

if (empty($tabIds)) {
    echo "<p style='color:red'>Aucun onglet du module n'a été trouvé. Veuillez d'abord installer le module correctement.</p>";
    exit;
}

// 2. Récupérer tous les profils d'administration (1 = super admin par défaut)
$adminProfiles = Db::getInstance()->executeS("
    SELECT id_profile, name 
    FROM `" . _DB_PREFIX_ . "profile` 
    WHERE id_profile = 1 OR id_profile IN (SELECT id_profile FROM `" . _DB_PREFIX_ . "profile_lang` WHERE name LIKE '%admin%')
");

echo "<p>Profils d'administration trouvés: " . count($adminProfiles) . "</p>";
foreach ($adminProfiles as $profile) {
    echo "<p>Profile #{$profile['id_profile']}: {$profile['name']}</p>";
}

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

echo "<p>Employés administrateurs trouvés: " . count($employees) . "</p>";
foreach ($employees as $employee) {
    echo "<p>Employé #{$employee['id_employee']}: {$employee['firstname']} {$employee['lastname']} ({$employee['email']}) - Profile #{$employee['id_profile']}</p>";
}

// 4. Récupérer le module
$module = Module::getInstanceByName('iabot');
if (!$module) {
    echo "<p style='color:red'>Module IaBot non trouvé!</p>";
    exit;
}

// 5. Vérifier et corriger les autorisations
echo "<h2>Correction des autorisations:</h2>";

// Vérification de la table des autorisations
$tablePermission = _DB_PREFIX_ . 'authorization_role';
$tablePermissionExists = count(Db::getInstance()->executeS("SHOW TABLES LIKE '" . $tablePermission . "'")) > 0;

if (!$tablePermissionExists) {
    echo "<p style='color:red'>La table d'autorisations n'existe pas dans cette version de PrestaShop!</p>";
    exit;
}

// Récupération des rôles pour les onglets
$roles = ['CREATE', 'READ', 'UPDATE', 'DELETE'];
$authRoles = [];

// Pour chaque onglet, récupérer et vérifier les rôles d'autorisation
foreach ($tabIds as $className => $tabId) {
    echo "<h3>Configuration des permissions pour {$className} (ID: {$tabId})</h3>";
    
    // 1. Vérifier si les rôles d'autorisation existent pour cet onglet
    $roleIds = [];
    foreach ($roles as $role) {
        // Format attendu pour le slug PrestaShop
        $slug1 = "ROLE_MOD_TAB_" . strtoupper('iabot') . "_" . strtoupper($className) . "_" . $role;
        $slug2 = "ROLE_MOD_TAB_" . strtoupper('iabot') . "_" . strtoupper(Tab::getClassNameById($tabId)) . "_" . $role;
        
        // Essayer les deux formats de slug possibles
        $roleId = Db::getInstance()->getValue(
            "SELECT id_authorization_role FROM `" . _DB_PREFIX_ . "authorization_role` 
             WHERE slug = '{$slug1}' OR slug = '{$slug2}'"
        );
        
        if ($roleId) {
            $roleIds[$role] = $roleId;
            echo "<p>Rôle {$role} trouvé avec ID: {$roleId}</p>";
        } else {
            // Si le rôle n'existe pas, le créer
            echo "<p style='color:orange'>Rôle {$role} non trouvé pour {$className}. Création...</p>";
            Db::getInstance()->execute(
                "INSERT INTO `" . _DB_PREFIX_ . "authorization_role` (`slug`) 
                 VALUES ('{$slug1}')"
            );
            $roleId = Db::getInstance()->Insert_ID();
            $roleIds[$role] = $roleId;
            echo "<p style='color:green'>Rôle {$role} créé avec ID: {$roleId}</p>";
        }
    }
    
    // Stocker les rôles pour cet onglet
    $authRoles[$tabId] = $roleIds;
    
    // 2. Pour chaque employé administrateur, assigner ces rôles
    foreach ($employees as $employee) {
        echo "<p>Configuration des permissions pour l'employé #{$employee['id_employee']}</p>";
        
        foreach ($roleIds as $role => $roleId) {
            // Vérifier si l'employé a déjà ce rôle
            $hasAccess = Db::getInstance()->getValue(
                "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "employee_access` 
                 WHERE id_employee = " . (int)$employee['id_employee'] . " 
                 AND id_authorization_role = " . (int)$roleId
            );
            
            if (!$hasAccess) {
                // Ajouter l'autorisation pour cet employé
                Db::getInstance()->execute(
                    "INSERT IGNORE INTO `" . _DB_PREFIX_ . "employee_access` 
                     (id_employee, id_authorization_role) 
                     VALUES (" . (int)$employee['id_employee'] . ", " . (int)$roleId . ")"
                );
                echo "<p style='color:green'>Accès {$role} ajouté pour l'employé #{$employee['id_employee']}</p>";
            } else {
                echo "<p>L'employé #{$employee['id_employee']} a déjà l'accès {$role}</p>";
            }
            
            // S'assurer que le profil a également ces permissions
            Db::getInstance()->execute(
                "INSERT IGNORE INTO `" . _DB_PREFIX_ . "access` 
                 (id_profile, id_authorization_role) 
                 VALUES (" . (int)$employee['id_profile'] . ", " . (int)$roleId . ")"
            );
            
            // Ajout des permissions de module
            Db::getInstance()->execute(
                "INSERT IGNORE INTO `" . _DB_PREFIX_ . "module_access` 
                 (id_profile, id_authorization_role) 
                 VALUES (" . (int)$employee['id_profile'] . ", " . (int)$roleId . ")"
            );
        }
    }
    
    // 3. Assurer la visibilité de l'onglet
    $tab = new Tab($tabId);
    if (!$tab->active) {
        $tab->active = 1;
        $tab->save();
        echo "<p style='color:green'>Onglet {$className} activé</p>";
    }
}

// 6. Vérifier les permissions directes sur le module
$moduleId = Module::getModuleIdByName('iabot');
if ($moduleId) {
    echo "<h3>Configuration des permissions pour le module (ID: {$moduleId})</h3>";
    
    foreach ($adminProfiles as $profile) {
        $profileId = (int)$profile['id_profile'];
        
        // Assurer que le profil a les permissions sur le module
        Db::getInstance()->execute(
            "INSERT IGNORE INTO `" . _DB_PREFIX_ . "module_access` 
             (`id_profile`, `id_module`, `view`, `configure`, `uninstall`) 
             VALUES ({$profileId}, {$moduleId}, 1, 1, 1)"
        );
        
        echo "<p style='color:green'>Permissions de module ajoutées pour le profil #{$profileId}</p>";
    }
}

// 7. Vider les caches
Context::getContext()->smarty->clearCache();
Tools::clearSmartyCache();
Tools::clearXMLCache();
Media::clearCache();
Tools::generateIndex();

echo "<h2 style='color:green'>✓ Correction des permissions terminée avec succès!</h2>";
echo "<p>Veuillez vous déconnecter et vous reconnecter pour que les changements prennent effet.</p>";
echo "<a href='../../../admin-dev/' style='display:inline-block; margin-top:20px; padding:10px 15px; background-color:#25B9D7; color:white; text-decoration:none; border-radius:4px;'>Retour à l'administration</a>";
