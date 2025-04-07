<?php
/**
 * Classe de gestion des statistiques
 * 
 * @author  Développeur
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class IaBotStatistic extends ObjectModel
{
    /** @var int ID de la statistique */
    public $id_statistic;
    
    /** @var int ID de la conversation */
    public $id_conversation;
    
    /** @var string Message de l'utilisateur */
    public $user_message;
    
    /** @var string Réponse du bot */
    public $bot_response;
    
    /** @var int Nombre de recommandations affichées */
    public $recommendations_count;
    
    /** @var int Nombre de clics sur les recommandations */
    public $recommendation_clicks;
    
    /** @var string Date de création */
    public $date_add;
    
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'iabot_statistic',
        'primary' => 'id_statistic',
        'fields' => [
            'id_conversation' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'user_message' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 1024],
            'bot_response' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 1024],
            'recommendations_count' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'recommendation_clicks' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];
    
    /**
     * Enregistre une interaction entre l'utilisateur et le bot
     * 
     * @param int $idConversation ID de la conversation
     * @param string $userMessage Message de l'utilisateur
     * @param string $botResponse Réponse du bot
     * @param int $recommendationsCount Nombre de recommandations affichées
     * @return bool Succès ou échec
     */
    public static function recordInteraction($idConversation, $userMessage, $botResponse, $recommendationsCount = 0)
    {
        $statistic = new IaBotStatistic();
        $statistic->id_conversation = (int)$idConversation;
        $statistic->user_message = pSQL($userMessage, true);
        $statistic->bot_response = pSQL($botResponse, true);
        $statistic->recommendations_count = (int)$recommendationsCount;
        $statistic->recommendation_clicks = 0;
        $statistic->date_add = date('Y-m-d H:i:s');
        
        return $statistic->save();
    }
    
    /**
     * Enregistre un clic sur une recommandation
     * 
     * @param int $idStatistic ID de la statistique
     * @return bool Succès ou échec
     */
    public static function recordRecommendationClick($idStatistic)
    {
        $idStatistic = (int)$idStatistic;
        
        if ($idStatistic <= 0) {
            return false;
        }
        
        return Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'iabot_statistic` 
            SET `recommendation_clicks` = `recommendation_clicks` + 1 
            WHERE `id_statistic` = ' . $idStatistic
        );
    }
    
    /**
     * Récupère les statistiques globales
     * 
     * @param string $dateFrom Date de début (format Y-m-d)
     * @param string $dateTo Date de fin (format Y-m-d)
     * @return array Statistiques globales
     */
    public static function getGlobalStats($dateFrom = null, $dateTo = null)
    {
        $whereClause = '';
        
        if ($dateFrom !== null && $dateTo !== null) {
            $whereClause = ' WHERE s.date_add BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"';
        } elseif ($dateFrom !== null) {
            $whereClause = ' WHERE s.date_add >= "' . pSQL($dateFrom) . ' 00:00:00"';
        } elseif ($dateTo !== null) {
            $whereClause = ' WHERE s.date_add <= "' . pSQL($dateTo) . ' 23:59:59"';
        }
        
        // Nombre total d'interactions
        $totalInteractions = (int)Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'iabot_statistic` s' . $whereClause
        );
        
        // Nombre total de conversations
        $totalConversations = (int)Db::getInstance()->getValue('
            SELECT COUNT(DISTINCT s.id_conversation) 
            FROM `' . _DB_PREFIX_ . 'iabot_statistic` s' . $whereClause
        );
        
        // Nombre total de recommandations affichées
        $totalRecommendations = (int)Db::getInstance()->getValue('
            SELECT SUM(s.recommendations_count) 
            FROM `' . _DB_PREFIX_ . 'iabot_statistic` s' . $whereClause
        );
        
        // Nombre total de clics sur les recommandations
        $totalClicks = (int)Db::getInstance()->getValue('
            SELECT SUM(s.recommendation_clicks) 
            FROM `' . _DB_PREFIX_ . 'iabot_statistic` s' . $whereClause
        );
        
        // Taux de clic (CTR)
        $ctr = $totalRecommendations > 0 ? round(($totalClicks / $totalRecommendations) * 100, 2) : 0;
        
        // Nombre moyen de messages par conversation
        $avgMessagesPerConversation = $totalConversations > 0 ? round($totalInteractions / $totalConversations, 2) : 0;
        
        return [
            'total_interactions' => $totalInteractions,
            'total_conversations' => $totalConversations,
            'total_recommendations' => $totalRecommendations,
            'total_clicks' => $totalClicks,
            'ctr' => $ctr,
            'avg_messages_per_conversation' => $avgMessagesPerConversation
        ];
    }
    
    /**
     * Récupère les statistiques par jour
     * 
     * @param string $dateFrom Date de début (format Y-m-d)
     * @param string $dateTo Date de fin (format Y-m-d)
     * @return array Statistiques par jour
     */
    public static function getDailyStats($dateFrom, $dateTo)
    {
        $sql = '
            SELECT 
                DATE(s.date_add) as day,
                COUNT(*) as interactions,
                COUNT(DISTINCT s.id_conversation) as conversations,
                SUM(s.recommendations_count) as recommendations,
                SUM(s.recommendation_clicks) as clicks
            FROM `' . _DB_PREFIX_ . 'iabot_statistic` s
            WHERE s.date_add BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"
            GROUP BY DATE(s.date_add)
            ORDER BY DATE(s.date_add) ASC
        ';
        
        $results = Db::getInstance()->executeS($sql);
        
        if (!$results) {
            return [];
        }
        
        // Formater les résultats
        $stats = [];
        foreach ($results as $row) {
            $ctr = $row['recommendations'] > 0 ? round(($row['clicks'] / $row['recommendations']) * 100, 2) : 0;
            $avgMessages = $row['conversations'] > 0 ? round($row['interactions'] / $row['conversations'], 2) : 0;
            
            $stats[] = [
                'day' => $row['day'],
                'interactions' => (int)$row['interactions'],
                'conversations' => (int)$row['conversations'],
                'recommendations' => (int)$row['recommendations'],
                'clicks' => (int)$row['clicks'],
                'ctr' => $ctr,
                'avg_messages' => $avgMessages
            ];
        }
        
        return $stats;
    }
    
    /**
     * Récupère les mots-clés les plus fréquents dans les messages des utilisateurs
     * 
     * @param int $limit Nombre maximum de mots-clés à récupérer
     * @param string $dateFrom Date de début (format Y-m-d)
     * @param string $dateTo Date de fin (format Y-m-d)
     * @return array Mots-clés les plus fréquents
     */
    public static function getTopKeywords($limit = 10, $dateFrom = null, $dateTo = null)
    {
        // Liste des mots à ignorer (stop words)
        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'et', 'ou', 'je', 'tu', 'il', 'elle', 'nous', 'vous', 
                     'ils', 'elles', 'ce', 'cette', 'ces', 'mon', 'ma', 'mes', 'ton', 'ta', 'tes', 'son', 'sa', 'ses',
                     'notre', 'nos', 'votre', 'vos', 'leur', 'leurs', 'que', 'qui', 'quoi', 'comment', 'pourquoi',
                     'où', 'quand', 'est', 'sont', 'être', 'avoir', 'faire', 'pour', 'dans', 'sur', 'avec', 'sans',
                     'par', 'de', 'du', 'au', 'aux', 'à', 'en', 'vers', 'chez', 'bonjour', 'salut', 'merci'];
        
        $whereClause = '';
        
        if ($dateFrom !== null && $dateTo !== null) {
            $whereClause = ' WHERE s.date_add BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"';
        } elseif ($dateFrom !== null) {
            $whereClause = ' WHERE s.date_add >= "' . pSQL($dateFrom) . ' 00:00:00"';
        } elseif ($dateTo !== null) {
            $whereClause = ' WHERE s.date_add <= "' . pSQL($dateTo) . ' 23:59:59"';
        }
        
        // Récupérer tous les messages des utilisateurs
        $messages = Db::getInstance()->executeS('
            SELECT s.user_message 
            FROM `' . _DB_PREFIX_ . 'iabot_statistic` s' . $whereClause
        );
        
        if (!$messages) {
            return [];
        }
        
        // Analyser les messages pour extraire les mots-clés
        $keywords = [];
        
        foreach ($messages as $message) {
            // Convertir en minuscules et supprimer la ponctuation
            $text = mb_strtolower($message['user_message'], 'UTF-8');
            $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
            
            // Diviser en mots
            $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
            
            // Compter les occurrences des mots (en ignorant les stop words et les mots courts)
            foreach ($words as $word) {
                if (mb_strlen($word, 'UTF-8') > 2 && !in_array($word, $stopWords)) {
                    if (isset($keywords[$word])) {
                        $keywords[$word]++;
                    } else {
                        $keywords[$word] = 1;
                    }
                }
            }
        }
        
        // Trier par fréquence décroissante
        arsort($keywords);
        
        // Limiter le nombre de résultats
        $topKeywords = array_slice($keywords, 0, $limit, true);
        
        // Formater les résultats
        $result = [];
        foreach ($topKeywords as $keyword => $count) {
            $result[] = [
                'keyword' => $keyword,
                'count' => $count
            ];
        }
        
        return $result;
    }
}
