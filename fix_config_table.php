<?php
/**
 * Script de réparation pour la table iabot_config
 * Ce script corrige l'erreur "Unknown column 'name' in 'INSERT INTO'"
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
</style>
';

echo '<h1>Réparation de la table iabot_config</h1>';

// Étape 1: Vérifier si la table existe déjà
echo '<div class="step">';
echo '<h2>Étape 1: Vérification de la table</h2>';

$tableExists = $db->executeS("SHOW TABLES LIKE '{$prefix}iabot_config'");

if (!empty($tableExists)) {
    echo '<p class="success">La table iabot_config existe.</p>';
    
    // Vérifier la structure de la table
    $columns = $db->executeS("SHOW COLUMNS FROM `{$prefix}iabot_config`");
    
    echo '<p>Structure actuelle de la table:</p>';
    echo '<pre>';
    foreach ($columns as $column) {
        echo "{$column['Field']} - {$column['Type']}\n";
    }
    echo '</pre>';
    
    // Vérifier si la colonne name existe
    $hasNameColumn = false;
    foreach ($columns as $column) {
        if ($column['Field'] == 'name') {
            $hasNameColumn = true;
            break;
        }
    }
    
    if ($hasNameColumn) {
        echo '<p class="success">La colonne "name" existe dans la table.</p>';
    } else {
        echo '<p class="error">La colonne "name" n\'existe PAS dans la table.</p>';
    }
} else {
    echo '<p class="error">La table iabot_config n\'existe pas.</p>';
}

echo '</div>';

// Étape 2: Corriger ou créer la table
echo '<div class="step">';
echo '<h2>Étape 2: Réparation de la table</h2>';

if (isset($_GET['action']) && $_GET['action'] == 'fix') {
    // Supprimer la table si elle existe
    $db->execute("DROP TABLE IF EXISTS `{$prefix}iabot_config`");
    echo '<p>Suppression de la table existante (si présente).</p>';
    
    // Recréer la table avec la structure correcte
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `{$prefix}iabot_config` (
        `id_config` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(64) NOT NULL,
        `value` TEXT NULL,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_config`),
        UNIQUE INDEX `idx_name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $result = $db->execute($createTableQuery);
    
    if ($result) {
        echo '<p class="success">Création de la table réussie.</p>';
        
        // Insérer les valeurs par défaut
        $insertQuery = "INSERT IGNORE INTO `{$prefix}iabot_config` 
            (`name`, `value`, `date_add`, `date_upd`) VALUES 
            ('IABOT_LIVE_MODE', '0', NOW(), NOW()),
            ('IABOT_API_KEY', '', NOW(), NOW()),
            ('IABOT_AI_MODEL', 'meta-llama/llama-3.3-70b-instruct', NOW(), NOW()),
            ('IABOT_AI_TEMPERATURE', '0.7', NOW(), NOW()),
            ('IABOT_CHAT_COLOR', '0, 123, 255', NOW(), NOW()),
            ('IABOT_CHAT_POSITION', 'bottom-right', NOW(), NOW()),
            ('IABOT_WELCOME_MESSAGE', 'Bonjour ! Je suis l\'assistant virtuel de cette boutique. Comment puis-je vous aider aujourd\'hui ?', NOW(), NOW()),
            ('IABOT_PROMPT_PLACEHOLDER', 'Posez votre question ici...', NOW(), NOW()),
            ('IABOT_SYSTEM_MESSAGE', 'Tu es un assistant de shopping intelligent pour une boutique en ligne PrestaShop. Tu dois être poli, serviable et fournir des informations précises sur les produits.', NOW(), NOW())";
        
        $result = $db->execute($insertQuery);
        
        if ($result) {
            echo '<p class="success">Insertion des données par défaut réussie.</p>';
        } else {
            echo '<p class="error">Erreur lors de l\'insertion des données par défaut.</p>';
        }
        
        // Vérifier à nouveau la structure de la table
        $columns = $db->executeS("SHOW COLUMNS FROM `{$prefix}iabot_config`");
        
        echo '<p>Nouvelle structure de la table:</p>';
        echo '<pre>';
        foreach ($columns as $column) {
            echo "{$column['Field']} - {$column['Type']}\n";
        }
        echo '</pre>';
    } else {
        echo '<p class="error">Échec de la création de la table.</p>';
    }
} else {
    echo '<p>Cliquez sur le bouton ci-dessous pour recréer la table iabot_config avec la structure correcte:</p>';
    echo '<a href="?action=fix" class="btn">Réparer la table</a>';
}

echo '</div>';

// Étape 3: Essayer de réinstaller le module
echo '<div class="step">';
echo '<h2>Étape 3: Après la réparation</h2>';

echo '<p>Après avoir réparé la table, vous pouvez:</p>';
echo '<ul>';
echo '<li><a href="../../admin-dev/index.php?controller=AdminModules" class="btn">Retourner aux modules</a> et essayer d\'installer le module maintenant.</li>';
echo '<li><a href="module_fix.php" class="btn">Retourner à l\'outil de diagnostic complet</a> si nécessaire.</li>';
echo '</ul>';

echo '</div>';
