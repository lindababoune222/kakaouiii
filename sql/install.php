<?php
/**
 * Script d'installation SQL pour le module IaBot
 */

$sql = [];

// Définition du moteur de base de données si non défini
if (!defined('_MYSQL_ENGINE_')) {
    define('_MYSQL_ENGINE_', 'InnoDB');
}

// Table des conversations
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'iabot_conversation` (
    `id_conversation` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_customer` INT(11) UNSIGNED NULL DEFAULT NULL,
    `token` VARCHAR(64) NOT NULL,
    `ip_address` VARCHAR(64) NOT NULL,
    `user_agent` VARCHAR(255) NULL DEFAULT NULL,
    `is_customer_logged` TINYINT(1) NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_conversation`),
    INDEX `idx_customer` (`id_customer`),
    INDEX `idx_token` (`token`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

// Table des messages
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'iabot_message` (
    `id_message` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_conversation` INT(11) UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `sender` ENUM(\'user\', \'bot\') NOT NULL,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_message`),
    INDEX `idx_conversation` (`id_conversation`),
    CONSTRAINT `fk_iabot_message_conversation` FOREIGN KEY (`id_conversation`) 
    REFERENCES `' . _DB_PREFIX_ . 'iabot_conversation` (`id_conversation`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

// Table de la base de connaissances
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'iabot_knowledge` (
    `id_knowledge` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_reference` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `reference_type` VARCHAR(32) NOT NULL DEFAULT \'\',
    `id_lang` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(128) NOT NULL,
    `content` TEXT NOT NULL,
    `keywords` VARCHAR(255) NULL DEFAULT \'\',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_knowledge`),
    INDEX `idx_reference` (`id_reference`, `reference_type`),
    INDEX `idx_lang` (`id_lang`),
    INDEX `idx_category` (`category`),
    INDEX `idx_active` (`active`),
    FULLTEXT INDEX `idx_content` (`title`, `content`, `keywords`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

// Table des recommandations de produits
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'iabot_recommendation` (
    `id_recommendation` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_product` int(10) unsigned NOT NULL,
    `keyword` varchar(64) NOT NULL,
    `weight` int(10) unsigned NOT NULL DEFAULT 10,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_recommendation`),
    KEY `id_product` (`id_product`),
    KEY `keyword` (`keyword`),
    KEY `weight` (`weight`),
    CONSTRAINT `fk_iabot_recommendation_product` FOREIGN KEY (`id_product`)
    REFERENCES `' . _DB_PREFIX_ . 'product` (`id_product`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

// Table de l'indexation des produits
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'iabot_product_index` (
    `id_product_index` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INT(11) UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `short_description` TEXT NULL,
    `reference` VARCHAR(64) NULL,
    `price` DECIMAL(20,6) NOT NULL DEFAULT 0,
    `link` VARCHAR(255) NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_product_index`),
    UNIQUE INDEX `idx_product` (`id_product`),
    FULLTEXT INDEX `idx_search` (`name`, `description`, `short_description`, `reference`),
    CONSTRAINT `fk_iabot_product_index_product` FOREIGN KEY (`id_product`)
    REFERENCES `' . _DB_PREFIX_ . 'product` (`id_product`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

// Mise à jour de la table des recommandations si elle existe déjà (conversion de position en weight)
$sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'iabot_recommendation` 
    ADD COLUMN IF NOT EXISTS `weight` INT(11) UNSIGNED NOT NULL DEFAULT 10 AFTER `keyword`,
    ADD COLUMN IF NOT EXISTS `date_upd` DATETIME NULL AFTER `date_add`';

// Suppression de la colonne position si elle existe
$sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'iabot_recommendation` 
    DROP COLUMN IF EXISTS `position`';

// Table des statistiques
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'iabot_statistic` (
    `id_statistic` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_conversation` INT(11) UNSIGNED NULL DEFAULT NULL,
    `id_customer` INT(11) UNSIGNED NULL DEFAULT NULL,
    `metric_type` VARCHAR(64) NOT NULL,
    `metric_value` VARCHAR(255) NOT NULL,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_statistic`),
    INDEX `idx_conversation` (`id_conversation`),
    INDEX `idx_customer` (`id_customer`),
    INDEX `idx_metric` (`metric_type`),
    CONSTRAINT `fk_iabot_statistic_conversation` FOREIGN KEY (`id_conversation`)
    REFERENCES `' . _DB_PREFIX_ . 'iabot_conversation` (`id_conversation`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_iabot_statistic_customer` FOREIGN KEY (`id_customer`)
    REFERENCES `' . _DB_PREFIX_ . 'customer` (`id_customer`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

// Table de configuration pour l'IA
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'iabot_config` (
    `id_config` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64) NOT NULL,
    `value` TEXT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_config`),
    UNIQUE INDEX `idx_name` (`name`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

// Valeurs par défaut pour la configuration
$sql[] = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'iabot_config` 
    (`name`, `value`, `date_add`, `date_upd`) VALUES 
    (\'IABOT_LIVE_MODE\', \'0\', NOW(), NOW()),
    (\'IABOT_API_KEY\', \'\', NOW(), NOW()),
    (\'IABOT_AI_MODEL\', \'meta-llama/llama-3.3-70b-instruct\', NOW(), NOW()),
    (\'IABOT_AI_TEMPERATURE\', \'0.7\', NOW(), NOW()),
    (\'IABOT_CHAT_COLOR\', \'0, 123, 255\', NOW(), NOW()),
    (\'IABOT_CHAT_POSITION\', \'bottom-right\', NOW(), NOW()),
    (\'IABOT_WELCOME_MESSAGE\', \'Bonjour ! Je suis l\\\'assistant virtuel de cette boutique. Comment puis-je vous aider aujourd\\\'hui ?\', NOW(), NOW()),
    (\'IABOT_PROMPT_PLACEHOLDER\', \'Posez votre question ici...\', NOW(), NOW()),
    (\'IABOT_SYSTEM_MESSAGE\', \'Tu es un assistant de shopping intelligent pour une boutique en ligne PrestaShop. Tu dois être poli, serviable et fournir des informations précises sur les produits.\', NOW(), NOW())';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

return true;
