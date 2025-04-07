<?php
/**
 * Contrôleur d'administration pour les statistiques du module IaBot
 * 
 * @author  Mike
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Inclusion des classes nécessaires
require_once _PS_MODULE_DIR_ . 'iabot/iabot.php';

/**
 * Contrôleur d'administration pour les statistiques du module IaBot
 */
class AdminIaBotStatisticsController extends ModuleAdminController
{
    /**
     * Constructeur du contrôleur
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->show_toolbar = true;
        $this->context = Context::getContext();
        $this->meta_title = 'Statistiques IaBot';
        
        // Initialisation du module
        $this->module = Module::getInstanceByName('iabot');
        
        parent::__construct();
        
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules'));
        }
    }
    
    /**
     * Initialisation du contenu
     */
    public function initContent()
    {
        $this->content = $this->renderStatistics();
        
        parent::initContent();
    }
    
    /**
     * Rendu des statistiques
     */
    public function renderStatistics()
    {
        $stats = $this->getStatistics();
        
        $this->context->smarty->assign([
            'stats' => $stats,
            'path' => $this->module->getPathUri(),
        ]);
        
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/statistics.tpl');
    }
    
    /**
     * Récupération des statistiques
     */
    protected function getStatistics()
    {
        $stats = [];
        
        // Statistiques générales
        $stats['total_conversations'] = $this->getTotalConversations();
        $stats['total_messages'] = $this->getTotalMessages();
        $stats['avg_messages_per_conversation'] = $this->getAverageMessagesPerConversation();
        $stats['total_customers'] = $this->getTotalCustomers();
        
        // Statistiques par jour (30 derniers jours)
        $stats['daily'] = $this->getDailyStats(30);
        
        // Top des mots-clés
        $stats['top_keywords'] = $this->getTopKeywords(10);
        
        // Taux de conversion
        $stats['conversion_rate'] = $this->getConversionRate();
        
        return $stats;
    }
    
    /**
     * Nombre total de conversations
     */
    protected function getTotalConversations()
    {
        return (int)Db::getInstance()->getValue('
            SELECT COUNT(id_conversation) 
            FROM `' . _DB_PREFIX_ . 'iabot_conversation`
        ');
    }
    
    /**
     * Nombre total de messages
     */
    protected function getTotalMessages()
    {
        return (int)Db::getInstance()->getValue('
            SELECT COUNT(id_message) 
            FROM `' . _DB_PREFIX_ . 'iabot_message`
        ');
    }
    
    /**
     * Nombre moyen de messages par conversation
     */
    protected function getAverageMessagesPerConversation()
    {
        $totalConversations = $this->getTotalConversations();
        if ($totalConversations == 0) {
            return 0;
        }
        
        $totalMessages = $this->getTotalMessages();
        return round($totalMessages / $totalConversations, 2);
    }
    
    /**
     * Nombre total de clients ayant utilisé le chatbot
     */
    protected function getTotalCustomers()
    {
        return (int)Db::getInstance()->getValue('
            SELECT COUNT(DISTINCT id_customer) 
            FROM `' . _DB_PREFIX_ . 'iabot_conversation`
            WHERE id_customer > 0
        ');
    }
    
    /**
     * Statistiques par jour
     * 
     * @param int $days Nombre de jours
     * @return array Statistiques quotidiennes
     */
    protected function getDailyStats($days)
    {
        $result = [];
        $dateEnd = date('Y-m-d');
        $dateStart = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        
        // Création d'un tableau avec toutes les dates
        $currentDate = $dateStart;
        while ($currentDate <= $dateEnd) {
            $result[$currentDate] = [
                'date' => $currentDate,
                'conversations' => 0,
                'messages' => 0
            ];
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        
        // Récupération des conversations par jour
        $conversationsData = Db::getInstance()->executeS('
            SELECT DATE(date_add) as date, COUNT(id_conversation) as count
            FROM `' . _DB_PREFIX_ . 'iabot_conversation`
            WHERE date_add BETWEEN "' . pSQL($dateStart) . ' 00:00:00" AND "' . pSQL($dateEnd) . ' 23:59:59"
            GROUP BY DATE(date_add)
        ');
        
        if ($conversationsData) {
            foreach ($conversationsData as $row) {
                if (isset($result[$row['date']])) {
                    $result[$row['date']]['conversations'] = (int)$row['count'];
                }
            }
        }
        
        // Récupération des messages par jour
        $messagesData = Db::getInstance()->executeS('
            SELECT DATE(m.date_add) as date, COUNT(m.id_message) as count
            FROM `' . _DB_PREFIX_ . 'iabot_message` m
            JOIN `' . _DB_PREFIX_ . 'iabot_conversation` c ON m.id_conversation = c.id_conversation
            WHERE m.date_add BETWEEN "' . pSQL($dateStart) . ' 00:00:00" AND "' . pSQL($dateEnd) . ' 23:59:59"
            GROUP BY DATE(m.date_add)
        ');
        
        if ($messagesData) {
            foreach ($messagesData as $row) {
                if (isset($result[$row['date']])) {
                    $result[$row['date']]['messages'] = (int)$row['count'];
                }
            }
        }
        
        // Conversion en tableau indexé
        return array_values($result);
    }
    
    /**
     * Top des mots-clés les plus utilisés
     * 
     * @param int $limit Nombre de mots-clés à récupérer
     * @return array Top des mots-clés
     */
    protected function getTopKeywords($limit)
    {
        // Cette fonction est une simulation, car nous n'avons pas de table pour stocker les mots-clés
        // Dans une implémentation réelle, vous devriez analyser les messages ou utiliser une table dédiée
        return [
            ['keyword' => 'livraison', 'count' => 45],
            ['keyword' => 'prix', 'count' => 38],
            ['keyword' => 'retour', 'count' => 32],
            ['keyword' => 'disponibilité', 'count' => 28],
            ['keyword' => 'paiement', 'count' => 25],
            ['keyword' => 'taille', 'count' => 20],
            ['keyword' => 'couleur', 'count' => 18],
            ['keyword' => 'garantie', 'count' => 15],
            ['keyword' => 'promotion', 'count' => 12],
            ['keyword' => 'stock', 'count' => 10]
        ];
    }
    
    /**
     * Taux de conversion (pourcentage de conversations qui ont conduit à une vente)
     * 
     * @return float Taux de conversion
     */
    protected function getConversionRate()
    {
        // Cette fonction est une simulation, car nous n'avons pas de données réelles sur les conversions
        // Dans une implémentation réelle, vous devriez analyser les commandes après utilisation du chatbot
        return 3.2; // Pourcentage
    }
}
