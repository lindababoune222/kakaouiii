<?php
/**
 * Contrôleur d'administration pour le tableau de bord du module IaBot
 * 
 * @author  Développeur
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Inclusion des classes nécessaires
require_once _PS_MODULE_DIR_ . 'iabot/iabot.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotConversation.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotMessage.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotStatistic.php';

/**
 * Contrôleur d'administration pour le tableau de bord du module IaBot
 */
class AdminIaBotDashboardController extends ModuleAdminController
{
    /**
     * Constructeur du contrôleur
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        $this->meta_title = 'Tableau de bord IaBot'; // Utilisation d'une chaîne directe au lieu de l()
        
        // Initialisation du module
        $this->module = Module::getInstanceByName('iabot');
        
        parent::__construct();
        
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules'));
        }
    }
    
    /**
     * Initialisation du contenu avant l'affichage
     */
    public function initContent()
    {
        $this->content = $this->renderView();
        
        parent::initContent();
    }
    
    /**
     * Rendu de la vue principale
     */
    public function renderView()
    {
        // Statistiques générales
        $totalConversations = $this->getTotalConversations();
        $totalMessages = $this->getTotalMessages();
        $activeConversations = $this->getActiveConversations();
        $averageMessagesPerConversation = $totalConversations > 0 ? round($totalMessages / $totalConversations, 1) : 0;
        
        // Statistiques par jour (7 derniers jours)
        $statsPerDay = $this->getStatsPerDay();
        
        // Conversations récentes
        $recentConversations = $this->getRecentConversations();
        
        // Assignation des variables au template
        $this->context->smarty->assign([
            'module_dir' => _PS_MODULE_DIR_ . $this->module->name,
            'module_path' => $this->module->getPathUri(),
            'total_conversations' => $totalConversations,
            'total_messages' => $totalMessages,
            'active_conversations' => $activeConversations,
            'average_messages' => $averageMessagesPerConversation,
            'stats_per_day' => $statsPerDay,
            'recent_conversations' => $recentConversations,
            'iabot_live_mode' => Configuration::get('IABOT_LIVE_MODE'),
            'iabot_chat_color' => Configuration::get('IABOT_CHAT_COLOR'),
            'link' => $this->context->link,
            'ajaxFrontUrl' => $this->context->link->getModuleLink('iabot', 'ajax')
        ]);
        
        // Ajout du lien vers l'outil de diagnostic
        $token = Configuration::get('IABOT_DIAGNOSTIC_TOKEN');
        if (empty($token)) {
            $token = Tools::passwdGen(32);
            Configuration::updateValue('IABOT_DIAGNOSTIC_TOKEN', $token);
        }
        
        $diagnosticUrl = $this->context->link->getModuleLink('iabot', 'diagnostic', ['token' => $token]);
        $this->context->smarty->assign('diagnostic_url', $diagnosticUrl);
        
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/dashboard.tpl');
    }
    
    /**
     * Récupère le nombre total de conversations
     */
    private function getTotalConversations()
    {
        return Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'iabot_conversation`
        ');
    }
    
    /**
     * Récupère le nombre total de messages
     */
    private function getTotalMessages()
    {
        return Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'iabot_message`
        ');
    }
    
    /**
     * Récupère le nombre de conversations actives (dernières 24h)
     */
    private function getActiveConversations()
    {
        return Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'iabot_conversation`
            WHERE `date_upd` > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ');
    }
    
    /**
     * Récupère les statistiques par jour pour les 7 derniers jours
     */
    private function getStatsPerDay()
    {
        $stats = [];
        $days = [];
        
        // Génération des 7 derniers jours
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $days[$date] = [
                'date' => $date,
                'conversations' => 0,
                'messages' => 0
            ];
        }
        
        // Récupération des conversations par jour
        $conversationsPerDay = Db::getInstance()->executeS('
            SELECT DATE(date_add) as day, COUNT(*) as count
            FROM `' . _DB_PREFIX_ . 'iabot_conversation`
            WHERE date_add > DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(date_add)
        ');
        
        if ($conversationsPerDay) {
            foreach ($conversationsPerDay as $row) {
                if (isset($days[$row['day']])) {
                    $days[$row['day']]['conversations'] = (int)$row['count'];
                }
            }
        }
        
        // Récupération des messages par jour
        $messagesPerDay = Db::getInstance()->executeS('
            SELECT DATE(date_add) as day, COUNT(*) as count
            FROM `' . _DB_PREFIX_ . 'iabot_message`
            WHERE date_add > DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(date_add)
        ');
        
        if ($messagesPerDay) {
            foreach ($messagesPerDay as $row) {
                if (isset($days[$row['day']])) {
                    $days[$row['day']]['messages'] = (int)$row['count'];
                }
            }
        }
        
        return array_values($days);
    }
    
    /**
     * Récupère les conversations récentes
     */
    private function getRecentConversations()
    {
        $conversations = Db::getInstance()->executeS('
            SELECT c.*, COUNT(m.id_message) as message_count,
                   COALESCE(cu.firstname, \'\') as customer_firstname,
                   COALESCE(cu.lastname, \'\') as customer_lastname,
                   COALESCE(cu.email, \'\') as customer_email
            FROM `' . _DB_PREFIX_ . 'iabot_conversation` c
            LEFT JOIN `' . _DB_PREFIX_ . 'iabot_message` m ON c.id_conversation = m.id_conversation
            LEFT JOIN `' . _DB_PREFIX_ . 'customer` cu ON c.id_customer = cu.id_customer
            GROUP BY c.id_conversation
            ORDER BY c.date_upd DESC
            LIMIT 10
        ');
        
        if (!$conversations) {
            return [];
        }
        
        // Formatage des données
        foreach ($conversations as &$conversation) {
            // Formatage des dates
            $conversation['date_add_formatted'] = Tools::displayDate($conversation['date_add'], null, true);
            $conversation['date_upd_formatted'] = Tools::displayDate($conversation['date_upd'], null, true);
            
            // Récupération du dernier message
            $lastMessage = Db::getInstance()->getRow('
                SELECT content, sender, date_add
                FROM `' . _DB_PREFIX_ . 'iabot_message`
                WHERE id_conversation = ' . (int)$conversation['id_conversation'] . '
                ORDER BY date_add DESC
                LIMIT 1
            ');
            
            if ($lastMessage) {
                $conversation['last_message'] = $lastMessage['content'];
                $conversation['last_message_sender'] = $lastMessage['sender'];
                $conversation['last_message_date'] = Tools::displayDate($lastMessage['date_add'], null, true);
            } else {
                $conversation['last_message'] = '';
                $conversation['last_message_sender'] = '';
                $conversation['last_message_date'] = '';
            }
            
            // Nom du client ou visiteur
            if (!empty($conversation['customer_firstname']) && !empty($conversation['customer_lastname'])) {
                $conversation['customer_name'] = $conversation['customer_firstname'] . ' ' . $conversation['customer_lastname'];
            } else {
                $conversation['customer_name'] = 'Visiteur (' . $conversation['ip_address'] . ')';
            }
        }
        
        return $conversations;
    }
}
