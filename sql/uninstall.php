<?php
/**
 * Script de désinstallation SQL pour le module IaBot
 */

$sql = [];

// Suppression des tables dans l'ordre pour respecter les contraintes de clé étrangère
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'iabot_statistic`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'iabot_recommendation`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'iabot_product_index`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'iabot_knowledge`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'iabot_message`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'iabot_conversation`';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
