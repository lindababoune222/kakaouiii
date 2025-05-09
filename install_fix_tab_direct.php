<?php
/**
 * Script d'installation directe de l'onglet de correction des permissions
 * Version simplifiée sans vérification d'accès
 */

// Initialisation PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #25B9D7;">Installation directe de l\'outil de correction des permissions IaBot</h1>';

// Vérification de l'existence du contrôleur
$controllerPath = _PS_MODULE_DIR_ . 'iabot/controllers/admin/AdminIaBotFixPermissionsController.php';
if (!file_exists($controllerPath)) {
    die('<div style="color: red; padding: 15px; border-left: 5px solid red; background: #fff1f1;">
        Le fichier du contrôleur n\'existe pas à l\'emplacement: ' . $controllerPath . '
        <br>Veuillez vérifier que le module est correctement installé.
    </div>');
}

// Vérification de l'existence du template
$templatePath = _PS_MODULE_DIR_ . 'iabot/views/templates/admin/fix_permissions.tpl';
if (!file_exists($templatePath)) {
    die('<div style="color: red; padding: 15px; border-left: 5px solid red; background: #fff1f1;">
        Le fichier de template n\'existe pas à l\'emplacement: ' . $templatePath . '
        <br>Veuillez vérifier que le module est correctement installé.
    </div>');
}

// Création de l'onglet si nécessaire
$tabId = Tab::getIdFromClassName('AdminIaBotFixPermissions');

if (!$tabId) {
    // Création de l'onglet
    $tab = new Tab();
    $tab->active = 1;
    $tab->class_name = 'AdminIaBotFixPermissions';
    $tab->name = [];
    
    // Ajouter le nom dans toutes les langues
    foreach (Language::getLanguages(true) as $lang) {
        $tab->name[$lang['id_lang']] = 'Correction Permissions IaBot';
    }
    
    // Configurer le parent (module principal IaBot)
    $parentTabId = Tab::getIdFromClassName('AdminIaBot');
    if ($parentTabId) {
        $tab->id_parent = $parentTabId;
    } else {
        // Si le parent n'existe pas, utiliser la section Modules comme parent
        $tab->id_parent = Tab::getIdFromClassName('AdminParentModulesSf');
    }
    
    $tab->module = 'iabot';
    
    if ($tab->save()) {
        echo '<div style="color: green; padding: 15px; border-left: 5px solid green; background: #f1fff1;">
            L\'onglet de correction des permissions a été créé avec succès !
        </div>';
        
        // Ajouter les permissions pour tous les administrateurs
        $adminProfiles = Db::getInstance()->executeS("
            SELECT id_profile 
            FROM `" . _DB_PREFIX_ . "profile` 
            WHERE id_profile = 1 OR id_profile IN (
                SELECT id_profile FROM `" . _DB_PREFIX_ . "profile_lang` 
                WHERE name LIKE '%admin%' OR name LIKE '%administrateur%'
            )
        ");
        
        if (!empty($adminProfiles)) {
            echo '<div style="margin-top: 20px;">
                <h2>Configuration des permissions...</h2>
                <ul>';
            
            foreach ($adminProfiles as $profile) {
                $profileId = (int)$profile['id_profile'];
                
                // Ajout des permissions directes sur l'onglet
                Db::getInstance()->execute("
                    INSERT IGNORE INTO `" . _DB_PREFIX_ . "access` 
                    (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`) 
                    VALUES ({$profileId}, {$tab->id}, 1, 1, 1, 1)
                ");
                
                echo '<li>Permissions ajoutées pour le profil #' . $profileId . '</li>';
            }
            
            echo '</ul></div>';
        }
    } else {
        echo '<div style="color: red; padding: 15px; border-left: 5px solid red; background: #fff1f1;">
            Erreur lors de la création de l\'onglet.
        </div>';
    }
} else {
    echo '<div style="color: blue; padding: 15px; border-left: 5px solid blue; background: #f1f1ff;">
        L\'onglet de correction des permissions existe déjà (ID: ' . $tabId . ').
    </div>';
}

// Instructions finales
echo '<div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
    <h2>Comment utiliser l\'outil de correction</h2>
    <ol>
        <li>Connectez-vous à votre back-office PrestaShop</li>
        <li>Accédez au menu "Modules" > "Assistant IA" > "Correction Permissions IaBot"</li>
        <li>L\'outil corrigera automatiquement toutes les permissions</li>
        <li>Pour de meilleurs résultats, déconnectez-vous et reconnectez-vous après l\'exécution de l\'outil</li>
    </ol>
    <p><a href="../../../admin-dev/" style="display:inline-block; margin-top:20px; padding:10px 15px; background-color:#25B9D7; color:white; text-decoration:none; border-radius:4px;">Retour à l\'administration</a></p>
</div>
</div>';
