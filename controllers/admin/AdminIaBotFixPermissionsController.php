<?php
/**
 * Contrôleur administratif de correction des permissions pour le module IaBot
 *
 * Ce contrôleur permet de corriger les problèmes de permissions
 * qui empêchent le super admin d'accéder au module IaBot
 */

class AdminIaBotFixPermissionsController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    /**
     * Méthode principale du contrôleur
     */
    public function initContent()
    {
        parent::initContent();
        
        // Vérification de sécurité (super admin uniquement)
        if (!$this->context->employee->isSuperAdmin()) {
            $this->errors[] = $this->l('Vous devez être super-administrateur pour effectuer cette opération.');
            $this->redirectWithNotifications($this->context->link->getAdminLink('AdminModules'));
            return;
        }
        
        // Effectuer la correction des permissions
        $result = $this->repairPermissions();
        
        // Afficher les résultats
        $this->context->smarty->assign([
            'results' => $result,
            'admin_link' => $this->context->link->getAdminLink('AdminModules'),
        ]);
        
        $this->setTemplate('fix_permissions.tpl');
    }
    
    /**
     * Répare les permissions du module IaBot
     * 
     * @return array Résultats des opérations effectuées
     */
    protected function repairPermissions()
    {
        $result = [
            'success' => true,
            'messages' => [],
            'errors' => [],
            'tabs' => [],
            'profiles' => [],
            'employees' => []
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
                $result['tabs'][] = [
                    'name' => $className,
                    'id' => $tabId,
                    'found' => true
                ];
            } else {
                $result['tabs'][] = [
                    'name' => $className,
                    'found' => false
                ];
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
        
        foreach ($adminProfiles as $profile) {
            $result['profiles'][] = [
                'id' => $profile['id_profile'],
                'name' => $profile['name']
            ];
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
        
        foreach ($employees as $employee) {
            $result['employees'][] = [
                'id' => $employee['id_employee'],
                'name' => $employee['firstname'] . ' ' . $employee['lastname'],
                'email' => $employee['email'],
                'profile_id' => $employee['id_profile']
            ];
        }
        
        // 4. Récupérer le module
        $module = Module::getInstanceByName('iabot');
        if (!$module) {
            $result['success'] = false;
            $result['errors'][] = "Module IaBot non trouvé!";
            return $result;
        }
        
        // 5. Vérifier la table des autorisations
        $tablePermission = _DB_PREFIX_ . 'authorization_role';
        $tablePermissionExists = count(Db::getInstance()->executeS("SHOW TABLES LIKE '" . $tablePermission . "'")) > 0;
        
        if (!$tablePermissionExists) {
            $result['success'] = false;
            $result['errors'][] = "La table d'autorisations n'existe pas dans cette version de PrestaShop!";
            return $result;
        }
        
        // 6. Récupération des rôles pour les onglets
        $roles = ['CREATE', 'READ', 'UPDATE', 'DELETE'];
        $authRoles = [];
        
        // Pour chaque onglet, récupérer et vérifier les rôles d'autorisation
        foreach ($tabIds as $className => $tabId) {
            $tabRoles = [];
            
            // 1. Vérifier si les rôles d'autorisation existent pour cet onglet
            $roleIds = [];
            foreach ($roles as $role) {
                // Format attendu pour le slug PrestaShop
                $slug1 = "ROLE_MOD_TAB_" . strtoupper('iabot') . "_" . strtoupper($className) . "_" . $role;
                
                $roleId = Db::getInstance()->getValue(
                    "SELECT id_authorization_role FROM `" . _DB_PREFIX_ . "authorization_role` 
                     WHERE slug = '{$slug1}'"
                );
                
                if ($roleId) {
                    $roleIds[$role] = $roleId;
                    $tabRoles[] = [
                        'role' => $role,
                        'id' => $roleId,
                        'created' => false
                    ];
                } else {
                    // Si le rôle n'existe pas, le créer
                    Db::getInstance()->execute(
                        "INSERT INTO `" . _DB_PREFIX_ . "authorization_role` (`slug`) 
                         VALUES ('{$slug1}')"
                    );
                    $roleId = Db::getInstance()->Insert_ID();
                    $roleIds[$role] = $roleId;
                    $tabRoles[] = [
                        'role' => $role,
                        'id' => $roleId,
                        'created' => true
                    ];
                    $result['messages'][] = "Rôle {$role} créé pour {$className} avec ID: {$roleId}";
                }
            }
            
            // Stocker les rôles pour cet onglet
            $authRoles[$tabId] = $roleIds;
            
            // 2. Pour chaque employé administrateur, assigner ces rôles
            foreach ($employees as $employee) {
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
                        $result['messages'][] = "Accès {$role} ajouté pour l'employé #{$employee['id_employee']} sur {$className}";
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
                $result['messages'][] = "Onglet {$className} activé";
            }
        }
        
        // 7. Vérifier les permissions directes sur le module
        $moduleId = Module::getModuleIdByName('iabot');
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
        $this->context->smarty->clearCache();
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
        Tools::generateIndex();
        
        $result['messages'][] = "Tous les caches ont été vidés";
        
        return $result;
    }
}
