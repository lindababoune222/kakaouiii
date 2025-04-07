<?php
/**
 * Classe de gestion des logs pour le module IA Bot
 * 
 * @author  Développeur
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class IaBotLogger
{
    /** @var string Chemin vers le fichier de log */
    private static $logFile;
    
    /** @var string Chemin vers le fichier de diagnostic */
    private static $diagnosticFile;
    
    /** @var bool Indique si le logging est activé */
    private static $enabled = true;
    
    /** @var bool Indique si le mode de débogage détaillé est activé */
    private static $verboseDebug = false;
    
    /** @var int Niveau de log minimum */
    private static $logLevel = 1; // 1=ERROR, 2=WARNING, 3=INFO, 4=DEBUG
    
    /** @var array Niveaux de log disponibles */
    private static $logLevels = [
        1 => 'ERROR',
        2 => 'WARNING',
        3 => 'INFO',
        4 => 'DEBUG'
    ];
    
    /** @var array Stockage des erreurs pour le diagnostic */
    private static $errorStack = [];
    
    /** @var array Stockage des requêtes AJAX pour le diagnostic */
    private static $ajaxRequests = [];
    
    /** @var array Stockage des performances pour le diagnostic */
    private static $performanceMetrics = [];
    
    /** @var array Stockage des requêtes API pour le diagnostic */
    private static $apiRequests = [];
    
    /** @var array Stockage des données de session pour le diagnostic */
    private static $sessionData = [];
    
    /** @var array Stockage des informations d'indexation des produits */
    private static $indexingData = [];
    
    /**
     * Initialise le logger
     * 
     * @param bool $enabled Indique si le logging est activé
     * @param int $logLevel Niveau de log minimum
     * @param bool $verboseDebug Activer le mode de débogage détaillé
     * @return void
     */
    public static function init($enabled = true, $logLevel = 1, $verboseDebug = false)
    {
        self::$enabled = $enabled;
        self::$logLevel = max(1, min(4, (int)$logLevel));
        self::$verboseDebug = $verboseDebug;
        
        // Définition des chemins de fichiers
        $logDir = _PS_MODULE_DIR_ . 'iabot/logs';
        self::$logFile = $logDir . '/iabot_' . date('Y-m-d') . '.log';
        self::$diagnosticFile = $logDir . '/iabot_diagnostic_' . date('Y-m-d_H-i-s') . '.html';
        
        // Création du répertoire de logs si nécessaire
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Log de démarrage
        self::info('Logger initialisé', [
            'enabled' => $enabled,
            'logLevel' => $logLevel,
            'verboseDebug' => $verboseDebug,
            'logFile' => self::$logFile
        ]);
    }
    
    /**
     * Log une erreur
     * 
     * @param string $message Message d'erreur
     * @param array $context Contexte de l'erreur
     * @return void
     */
    public static function error($message, array $context = [])
    {
        // Ajout à la pile d'erreurs pour le diagnostic
        self::$errorStack[] = [
            'level' => 'ERROR',
            'message' => $message,
            'context' => $context,
            'time' => date('Y-m-d H:i:s'),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ];
        
        self::log(1, $message, $context);
    }
    
    /**
     * Log un avertissement
     * 
     * @param string $message Message d'avertissement
     * @param array $context Contexte de l'avertissement
     * @return void
     */
    public static function warning($message, array $context = [])
    {
        // Ajout à la pile d'erreurs pour le diagnostic si en mode verbose
        if (self::$verboseDebug) {
            self::$errorStack[] = [
                'level' => 'WARNING',
                'message' => $message,
                'context' => $context,
                'time' => date('Y-m-d H:i:s'),
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ];
        }
        
        self::log(2, $message, $context);
    }
    
    /**
     * Log une information
     * 
     * @param string $message Message d'information
     * @param array $context Contexte de l'information
     * @return void
     */
    public static function info($message, array $context = [])
    {
        self::log(3, $message, $context);
    }
    
    /**
     * Log un message de débogage
     * 
     * @param string $message Message de débogage
     * @param array $context Contexte du débogage
     * @return void
     */
    public static function debug($message, array $context = [])
    {
        self::log(4, $message, $context);
    }
    
    /**
     * Log une requête AJAX pour diagnostic
     * 
     * @param string $url URL de la requête
     * @param array $requestData Données de la requête
     * @param array $responseData Données de la réponse
     * @param bool $success Succès de la requête
     * @return void
     */
    public static function logAjaxRequest($url, $requestData, $responseData, $success = true)
    {
        self::$ajaxRequests[] = [
            'url' => $url,
            'request' => $requestData,
            'response' => $responseData,
            'success' => $success,
            'time' => date('Y-m-d H:i:s'),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ];
        
        self::debug('Requête AJAX', [
            'url' => $url,
            'request' => $requestData,
            'response' => $responseData,
            'success' => $success
        ]);
    }
    
    /**
     * Log une requête API pour diagnostic
     * 
     * @param string $endpoint Point de terminaison de l'API
     * @param array $requestData Données de la requête
     * @param array $responseData Données de la réponse
     * @param bool $success Succès de la requête
     * @param float $duration Durée de la requête en secondes
     * @return void
     */
    public static function logApiRequest($endpoint, $requestData, $responseData, $success = true, $duration = 0)
    {
        // Masquer la clé API dans les données de requête pour des raisons de sécurité
        if (isset($requestData['api_key'])) {
            $requestData['api_key'] = substr($requestData['api_key'], 0, 4) . '...' . substr($requestData['api_key'], -4);
        }
        if (isset($requestData['headers']['Authorization'])) {
            $requestData['headers']['Authorization'] = 'Bearer ' . substr(str_replace('Bearer ', '', $requestData['headers']['Authorization']), 0, 4) . '...';
        }
        
        self::$apiRequests[] = [
            'endpoint' => $endpoint,
            'request' => $requestData,
            'response' => $responseData,
            'success' => $success,
            'duration' => $duration,
            'time' => date('Y-m-d H:i:s'),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ];
        
        $logLevel = $success ? 3 : 1; // INFO si succès, ERROR si échec
        self::log($logLevel, 'Requête API ' . ($success ? 'réussie' : 'échouée'), [
            'endpoint' => $endpoint,
            'duration' => $duration,
            'request' => $requestData,
            'response' => $responseData
        ]);
    }
    
    /**
     * Commence à mesurer une métrique de performance
     * 
     * @param string $name Nom de la métrique
     * @param array $context Contexte de la métrique
     * @return string Identifiant unique de la métrique
     */
    public static function startPerformanceMetric($name, array $context = [])
    {
        $id = uniqid('perf_');
        self::$performanceMetrics[$id] = [
            'name' => $name,
            'context' => $context,
            'start_time' => microtime(true),
            'end_time' => null,
            'duration' => null,
            'memory_start' => memory_get_usage(),
            'memory_end' => null,
            'memory_peak' => null,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ];
        
        return $id;
    }
    
    /**
     * Termine la mesure d'une métrique de performance
     * 
     * @param string $id Identifiant unique de la métrique
     * @param array $additionalContext Contexte supplémentaire
     * @return float Durée de la métrique en secondes
     */
    public static function endPerformanceMetric($id, array $additionalContext = [])
    {
        if (!isset(self::$performanceMetrics[$id])) {
            self::warning('Tentative de terminer une métrique de performance inconnue', ['id' => $id]);
            return 0;
        }
        
        $endTime = microtime(true);
        $memoryEnd = memory_get_usage();
        $memoryPeak = memory_get_peak_usage();
        
        self::$performanceMetrics[$id]['end_time'] = $endTime;
        self::$performanceMetrics[$id]['duration'] = $endTime - self::$performanceMetrics[$id]['start_time'];
        self::$performanceMetrics[$id]['memory_end'] = $memoryEnd;
        self::$performanceMetrics[$id]['memory_peak'] = $memoryPeak;
        self::$performanceMetrics[$id]['memory_used'] = $memoryEnd - self::$performanceMetrics[$id]['memory_start'];
        self::$performanceMetrics[$id]['additional_context'] = $additionalContext;
        
        $duration = self::$performanceMetrics[$id]['duration'];
        $name = self::$performanceMetrics[$id]['name'];
        
        // Log la performance si elle dépasse un certain seuil (100ms par défaut)
        if ($duration > 0.1) {
            self::debug("Performance: $name", [
                'duration' => round($duration * 1000, 2) . ' ms',
                'memory_used' => self::formatBytes(self::$performanceMetrics[$id]['memory_used']),
                'memory_peak' => self::formatBytes($memoryPeak),
                'context' => array_merge(self::$performanceMetrics[$id]['context'], $additionalContext)
            ]);
        }
        
        return $duration;
    }
    
    /**
     * Enregistre des données de session pour le diagnostic
     * 
     * @param string $key Clé de la donnée
     * @param mixed $value Valeur de la donnée
     * @return void
     */
    public static function recordSessionData($key, $value)
    {
        self::$sessionData[$key] = [
            'value' => $value,
            'time' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Enregistre des informations d'indexation de produits
     * 
     * @param string $action Action d'indexation (start, progress, complete, error)
     * @param array $data Données d'indexation
     */
    public static function recordIndexing($action, $data = [])
    {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'data' => $data
        ];
        
        self::$indexingData[] = $entry;
        
        // Limiter le nombre d'entrées stockées en mémoire
        if (count(self::$indexingData) > 100) {
            array_shift(self::$indexingData);
        }
        
        // Enregistrement dans le fichier de log
        $level = ($action === 'error') ? 'error' : 'info';
        self::$level("Indexation: $action", $data);
    }
    
    /**
     * Récupère les données d'indexation des produits
     * 
     * @param int $limit Nombre maximum d'entrées à récupérer
     * @return array Données d'indexation
     */
    public static function getIndexingData($limit = 50)
    {
        return array_slice(self::$indexingData, -$limit);
    }
    
    /**
     * Analyse les problèmes d'indexation des produits
     * 
     * @return array Résultat de l'analyse
     */
    public static function analyzeIndexingIssues()
    {
        $result = [
            'total_operations' => 0,
            'successful_operations' => 0,
            'failed_operations' => 0,
            'last_indexing_time' => null,
            'average_duration' => 0,
            'common_errors' => [],
            'performance_issues' => false,
            'recommendations' => [],
            'product_stats' => [
                'total_products' => 0,
                'indexed_products' => 0,
                'indexing_rate' => 0
            ],
            'error_patterns' => []
        ];
        
        // Récupération des données d'indexation
        $indexingData = self::getIndexingData(100);
        
        if (empty($indexingData)) {
            $result['recommendations'][] = 'Aucune donnée d\'indexation disponible. Lancez une indexation des produits pour obtenir des diagnostics.';
            return $result;
        }
        
        // Analyse des opérations complètes
        $completeOperations = array_filter($indexingData, function($entry) {
            return $entry['action'] === 'complete';
        });
        
        $result['total_operations'] = count($completeOperations);
        
        // Analyse des durées et des taux de réussite
        $totalDuration = 0;
        $errorCounts = [];
        
        foreach ($completeOperations as $operation) {
            $data = $operation['data'];
            
            // Mise à jour de la dernière indexation
            if ($result['last_indexing_time'] === null || strtotime($operation['timestamp']) > strtotime($result['last_indexing_time'])) {
                $result['last_indexing_time'] = $operation['timestamp'];
            }
            
            // Calcul des statistiques de réussite
            $success = isset($data['count']) && isset($data['total']) && $data['count'] > 0;
            if ($success) {
                $result['successful_operations']++;
                $result['product_stats']['total_products'] = max($result['product_stats']['total_products'], (int)$data['total']);
                $result['product_stats']['indexed_products'] += (int)$data['count'];
            } else {
                $result['failed_operations']++;
            }
            
            // Calcul de la durée moyenne
            if (isset($data['duration']) && $data['duration'] > 0) {
                $totalDuration += (float)$data['duration'];
            }
            
            // Analyse des erreurs
            if (isset($data['errors']) && is_array($data['errors'])) {
                foreach ($data['errors'] as $error) {
                    $errorMessage = is_array($error) && isset($error['error']) ? $error['error'] : $error;
                    if (!isset($errorCounts[$errorMessage])) {
                        $errorCounts[$errorMessage] = 0;
                    }
                    $errorCounts[$errorMessage]++;
                }
            }
        }
        
        // Calcul de la durée moyenne
        if ($result['total_operations'] > 0) {
            $result['average_duration'] = round($totalDuration / $result['total_operations'], 2);
        }
        
        // Calcul du taux d'indexation
        if ($result['product_stats']['total_products'] > 0) {
            $result['product_stats']['indexing_rate'] = round(($result['product_stats']['indexed_products'] / $result['product_stats']['total_products']) * 100, 2);
        }
        
        // Analyse des erreurs les plus courantes
        arsort($errorCounts);
        $result['common_errors'] = array_slice($errorCounts, 0, 5, true);
        
        // Vérification des problèmes de performance
        $result['performance_issues'] = $result['average_duration'] > 5; // Plus de 5 secondes en moyenne
        
        // Analyse des erreurs pour détecter des modèles
        $errorPatterns = [
            'database' => 0,
            'memory' => 0,
            'validation' => 0,
            'connection' => 0,
            'timeout' => 0
        ];
        
        foreach ($errorCounts as $error => $count) {
            $lowerError = strtolower($error);
            if (strpos($lowerError, 'sql') !== false || strpos($lowerError, 'db') !== false || strpos($lowerError, 'database') !== false) {
                $errorPatterns['database'] += $count;
            }
            if (strpos($lowerError, 'memory') !== false || strpos($lowerError, 'allocation') !== false) {
                $errorPatterns['memory'] += $count;
            }
            if (strpos($lowerError, 'valid') !== false || strpos($lowerError, 'format') !== false) {
                $errorPatterns['validation'] += $count;
            }
            if (strpos($lowerError, 'connect') !== false || strpos($lowerError, 'network') !== false) {
                $errorPatterns['connection'] += $count;
            }
            if (strpos($lowerError, 'timeout') !== false || strpos($lowerError, 'time') !== false) {
                $errorPatterns['timeout'] += $count;
            }
        }
        
        $result['error_patterns'] = $errorPatterns;
        
        // Génération de recommandations
        if ($result['failed_operations'] > $result['successful_operations']) {
            $result['recommendations'][] = 'Le taux d\'échec des opérations d\'indexation est élevé. Vérifiez les erreurs courantes pour identifier le problème.';
        }
        
        if ($result['performance_issues']) {
            $result['recommendations'][] = 'Les opérations d\'indexation sont lentes (moyenne: ' . $result['average_duration'] . ' secondes). Envisagez d\'indexer les produits par lots plus petits.';
        }
        
        if ($errorPatterns['database'] > 0) {
            $result['recommendations'][] = 'Des erreurs de base de données ont été détectées. Vérifiez la structure de la table iabot_product_index et les permissions SQL.';
        }
        
        if ($errorPatterns['memory'] > 0) {
            $result['recommendations'][] = 'Des problèmes de mémoire ont été détectés. Augmentez la limite de mémoire PHP ou réduisez la taille des lots d\'indexation.';
        }
        
        if ($result['product_stats']['indexing_rate'] < 50) {
            $result['recommendations'][] = 'Le taux d\'indexation des produits est faible (' . $result['product_stats']['indexing_rate'] . '%). Vérifiez les filtres d\'indexation et les critères de sélection des produits.';
        }
        
        // Vérification des données d'index
        try {
            $db = Db::getInstance();
            $totalIndexed = (int)$db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'iabot_product_index`');
            $result['product_stats']['current_indexed'] = $totalIndexed;
            
            // Vérification de cohérence
            $totalProducts = (int)$db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product` WHERE active = 1');
            $result['product_stats']['total_active'] = $totalProducts;
            
            if ($totalIndexed === 0 && $totalProducts > 0) {
                $result['recommendations'][] = 'Aucun produit n\'est actuellement indexé alors que ' . $totalProducts . ' produits actifs sont disponibles. Lancez une indexation complète.';
            } elseif ($totalIndexed < ($totalProducts * 0.5)) {
                $result['recommendations'][] = 'Seulement ' . round(($totalIndexed / $totalProducts) * 100, 2) . '% des produits actifs sont indexés. Envisagez une réindexation complète.';
            }
        } catch (Exception $e) {
            $result['recommendations'][] = 'Impossible de vérifier l\'état actuel de l\'index: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Formate un nombre d'octets en une chaîne lisible
     * 
     * @param int $bytes Nombre d'octets
     * @param int $precision Précision
     * @return string Chaîne formatée
     */
    private static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Log un message avec un niveau spécifique
     * 
     * @param int $level Niveau de log
     * @param string $message Message à logger
     * @param array $context Contexte du message
     * @return void
     */
    private static function log($level, $message, array $context = [])
    {
        if (!self::$enabled || $level > self::$logLevel) {
            return;
        }
        
        // Initialisation du logger si nécessaire
        if (empty(self::$logFile)) {
            self::init();
        }
        
        // Formatage du message
        $logLevel = isset(self::$logLevels[$level]) ? self::$logLevels[$level] : 'UNKNOWN';
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[$timestamp] [$logLevel] $message$contextString" . PHP_EOL;
        
        // Écriture dans le fichier de log
        error_log($logMessage, 3, self::$logFile);
        
        // Affichage dans la console en mode développement
        if (defined('_PS_DEV_MODE_') && _PS_DEV_MODE_ && $level <= 2) {
            error_log($logMessage);
        }
    }
    
    /**
     * Formate une trace de débogage pour l'affichage
     * 
     * @param array $trace Trace de débogage
     * @return string Trace formatée
     */
    private static function formatTrace($trace)
    {
        $result = '';
        foreach ($trace as $i => $step) {
            $class = isset($step['class']) ? $step['class'] . $step['type'] : '';
            $file = isset($step['file']) ? $step['file'] : 'unknown file';
            $line = isset($step['line']) ? $step['line'] : 'unknown line';
            $result .= "#$i $file($line): $class{$step['function']}()\n";
        }
        return $result;
    }
    
    /**
     * Analyse les logs et génère un rapport de diagnostic automatique
     * 
     * @return array Rapport de diagnostic
     */
    public static function analyzeLogs()
    {
        $report = [
            'errors' => [
                'count' => 0,
                'most_frequent' => [],
                'recent' => []
            ],
            'warnings' => [
                'count' => 0,
                'most_frequent' => [],
                'recent' => []
            ],
            'ajax_requests' => [
                'count' => 0,
                'success_rate' => 0,
                'average_duration' => 0,
                'failed' => []
            ],
            'api_requests' => [
                'count' => 0,
                'success_rate' => 0,
                'average_duration' => 0,
                'failed' => []
            ],
            'performance' => [
                'slowest_operations' => [],
                'highest_memory_usage' => []
            ],
            'recommendations' => []
        ];
        
        // Analyse des erreurs et avertissements
        $errorMessages = [];
        $warningMessages = [];
        
        foreach (self::$errorStack as $error) {
            if ($error['level'] === 'ERROR') {
                $report['errors']['count']++;
                $errorMessages[$error['message']] = isset($errorMessages[$error['message']]) ? $errorMessages[$error['message']] + 1 : 1;
                
                if (count($report['errors']['recent']) < 5) {
                    $report['errors']['recent'][] = [
                        'message' => $error['message'],
                        'time' => $error['time'],
                        'context' => $error['context']
                    ];
                }
            } else if ($error['level'] === 'WARNING') {
                $report['warnings']['count']++;
                $warningMessages[$error['message']] = isset($warningMessages[$error['message']]) ? $warningMessages[$error['message']] + 1 : 1;
                
                if (count($report['warnings']['recent']) < 5) {
                    $report['warnings']['recent'][] = [
                        'message' => $error['message'],
                        'time' => $error['time'],
                        'context' => $error['context']
                    ];
                }
            }
        }
        
        // Tri des erreurs par fréquence
        arsort($errorMessages);
        arsort($warningMessages);
        
        // Récupération des erreurs les plus fréquentes
        $i = 0;
        foreach ($errorMessages as $message => $count) {
            if ($i >= 5) break;
            $report['errors']['most_frequent'][] = [
                'message' => $message,
                'count' => $count
            ];
            $i++;
        }
        
        // Récupération des avertissements les plus fréquents
        $i = 0;
        foreach ($warningMessages as $message => $count) {
            if ($i >= 5) break;
            $report['warnings']['most_frequent'][] = [
                'message' => $message,
                'count' => $count
            ];
            $i++;
        }
        
        // Analyse des requêtes AJAX
        $report['ajax_requests']['count'] = count(self::$ajaxRequests);
        if ($report['ajax_requests']['count'] > 0) {
            $successCount = count(array_filter(self::$ajaxRequests, function($req) { return $req['success']; }));
            $report['ajax_requests']['success_rate'] = round(($successCount / $report['ajax_requests']['count']) * 100, 2);
            
            // Récupération des requêtes AJAX échouées
            foreach (self::$ajaxRequests as $ajax) {
                if (!$ajax['success']) {
                    $report['ajax_requests']['failed'][] = [
                        'url' => $ajax['url'],
                        'time' => $ajax['time'],
                        'request' => $ajax['request'],
                        'response' => $ajax['response']
                    ];
                    
                    if (count($report['ajax_requests']['failed']) >= 5) {
                        break;
                    }
                }
            }
        }
        
        // Analyse des requêtes API
        $report['api_requests']['count'] = count(self::$apiRequests);
        if ($report['api_requests']['count'] > 0) {
            $successCount = count(array_filter(self::$apiRequests, function($req) { return $req['success']; }));
            $report['api_requests']['success_rate'] = round(($successCount / $report['api_requests']['count']) * 100, 2);
            
            // Calcul de la durée moyenne
            $totalDuration = 0;
            foreach (self::$apiRequests as $api) {
                $totalDuration += $api['duration'];
            }
            $report['api_requests']['average_duration'] = round($totalDuration / $report['api_requests']['count'], 3);
            
            // Récupération des requêtes API échouées
            foreach (self::$apiRequests as $api) {
                if (!$api['success']) {
                    $report['api_requests']['failed'][] = [
                        'endpoint' => $api['endpoint'],
                        'time' => $api['time'],
                        'request' => $api['request'],
                        'response' => $api['response']
                    ];
                    
                    if (count($report['api_requests']['failed']) >= 5) {
                        break;
                    }
                }
            }
        }
        
        // Analyse des performances
        $sortedPerformanceByDuration = self::$performanceMetrics;
        usort($sortedPerformanceByDuration, function($a, $b) {
            return $b['duration'] <=> $a['duration'];
        });
        
        $sortedPerformanceByMemory = self::$performanceMetrics;
        usort($sortedPerformanceByMemory, function($a, $b) {
            return $b['memory_used'] <=> $a['memory_used'];
        });
        
        // Récupération des opérations les plus lentes
        for ($i = 0; $i < min(5, count($sortedPerformanceByDuration)); $i++) {
            $metric = $sortedPerformanceByDuration[$i];
            $report['performance']['slowest_operations'][] = [
                'name' => $metric['name'],
                'duration' => round($metric['duration'] * 1000, 2) . ' ms',
                'context' => $metric['context']
            ];
        }
        
        // Récupération des opérations avec la plus haute utilisation mémoire
        for ($i = 0; $i < min(5, count($sortedPerformanceByMemory)); $i++) {
            $metric = $sortedPerformanceByMemory[$i];
            $report['performance']['highest_memory_usage'][] = [
                'name' => $metric['name'],
                'memory_used' => self::formatBytes($metric['memory_used']),
                'context' => $metric['context']
            ];
        }
        
        // Génération de recommandations
        if ($report['errors']['count'] > 0) {
            $report['recommendations'][] = 'Corriger les erreurs fréquentes, en particulier : ' . $report['errors']['most_frequent'][0]['message'];
        }
        
        if ($report['ajax_requests']['success_rate'] < 90 && $report['ajax_requests']['count'] > 0) {
            $report['recommendations'][] = 'Améliorer la gestion des requêtes AJAX, le taux de succès est seulement de ' . $report['ajax_requests']['success_rate'] . '%';
        }
        
        if ($report['api_requests']['success_rate'] < 90 && $report['api_requests']['count'] > 0) {
            $report['recommendations'][] = 'Vérifier la configuration de l\'API, le taux de succès est seulement de ' . $report['api_requests']['success_rate'] . '%';
        }
        
        if (!empty($report['performance']['slowest_operations']) && $report['performance']['slowest_operations'][0]['duration'] > '500 ms') {
            $report['recommendations'][] = 'Optimiser l\'opération "' . $report['performance']['slowest_operations'][0]['name'] . '" qui est particulièrement lente';
        }
        
        // Vérification des tables de base de données
        $tables = [
            'iabot_conversation',
            'iabot_message',
            'iabot_knowledge',
            'iabot_product_index',
            'iabot_recommendation',
            'iabot_statistic'
        ];
        
        $missingTables = [];
        foreach ($tables as $table) {
            $fullTable = _DB_PREFIX_ . $table;
            $result = Db::getInstance()->executeS("SHOW TABLES LIKE '$fullTable'");
            if (empty($result)) {
                $missingTables[] = $table;
            }
        }
        
        if (!empty($missingTables)) {
            $report['recommendations'][] = 'Réinstaller le module pour créer les tables manquantes : ' . implode(', ', $missingTables);
        }
        
        return $report;
    }
    
    /**
     * Génère un rapport de diagnostic HTML basé sur l'analyse des logs
     * 
     * @return string Chemin vers le fichier de rapport HTML
     */
    public static function generateAnalysisReport()
    {
        // Initialisation du logger si nécessaire
        if (empty(self::$diagnosticFile)) {
            self::init();
        }
        
        // Analyse des logs
        $report = self::analyzeLogs();
        
        // Génération du contenu HTML
        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport d\'analyse IaBot - ' . date('Y-m-d H:i:s') . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
        h1, h2, h3 { color: #2C3E50; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: #fff; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
        .card-header { background: #3498DB; color: white; padding: 15px; font-weight: bold; }
        .card-body { padding: 15px; }
        .alert { padding: 15px; border-radius: 3px; margin-bottom: 20px; }
        .alert-info { background: #D6EAF8; color: #2E86C1; }
        .alert-success { background: #D5F5E3; color: #27AE60; }
        .alert-warning { background: #FCF3CF; color: #D35400; }
        .alert-danger { background: #FADBD8; color: #C0392B; }
        .badge { display: inline-block; padding: 3px 7px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-error { background: #E74C3C; color: white; }
        .badge-warning { background: #F39C12; color: white; }
        .badge-info { background: #3498DB; color: white; }
        .badge-success { background: #2ECC71; color: white; }
        table { width: 100%; border-collapse: collapse; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .progress-container { width: 100%; background-color: #f1f1f1; border-radius: 5px; }
        .progress-bar { height: 20px; border-radius: 5px; }
        .progress-success { background-color: #4CAF50; }
        .progress-warning { background-color: #ff9800; }
        .progress-danger { background-color: #f44336; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Rapport d\'analyse IaBot</h1>
        <p>Généré le ' . date('Y-m-d H:i:s') . '</p>
        
        <div class="card">
            <div class="card-header">Résumé</div>
            <div class="card-body">
                <div class="alert ' . ($report['errors']['count'] > 0 ? 'alert-danger' : 'alert-success') . '">
                    <h3>Erreurs: ' . $report['errors']['count'] . '</h3>
                    <div class="progress-container">
                        <div class="progress-bar ' . ($report['errors']['count'] > 10 ? 'progress-danger' : ($report['errors']['count'] > 0 ? 'progress-warning' : 'progress-success')) . '" style="width:' . min(100, $report['errors']['count'] * 10) . '%"></div>
                    </div>
                </div>
                
                <div class="alert ' . ($report['warnings']['count'] > 10 ? 'alert-warning' : 'alert-success') . '">
                    <h3>Avertissements: ' . $report['warnings']['count'] . '</h3>
                    <div class="progress-container">
                        <div class="progress-bar ' . ($report['warnings']['count'] > 20 ? 'progress-danger' : ($report['warnings']['count'] > 10 ? 'progress-warning' : 'progress-success')) . '" style="width:' . min(100, $report['warnings']['count'] * 5) . '%"></div>
                    </div>
                </div>
                
                <div class="alert ' . ($report['ajax_requests']['success_rate'] < 90 ? 'alert-warning' : 'alert-success') . '">
                    <h3>Requêtes AJAX: ' . $report['ajax_requests']['count'] . ' (Taux de succès: ' . $report['ajax_requests']['success_rate'] . '%)</h3>
                    <div class="progress-container">
                        <div class="progress-bar ' . ($report['ajax_requests']['success_rate'] < 80 ? 'progress-danger' : ($report['ajax_requests']['success_rate'] < 90 ? 'progress-warning' : 'progress-success')) . '" style="width:' . $report['ajax_requests']['success_rate'] . '%"></div>
                    </div>
                </div>
                
                <div class="alert ' . ($report['api_requests']['success_rate'] < 90 ? 'alert-warning' : 'alert-success') . '">
                    <h3>Requêtes API: ' . $report['api_requests']['count'] . ' (Taux de succès: ' . $report['api_requests']['success_rate'] . '%)</h3>
                    <div class="progress-container">
                        <div class="progress-bar ' . ($report['api_requests']['success_rate'] < 80 ? 'progress-danger' : ($report['api_requests']['success_rate'] < 90 ? 'progress-warning' : 'progress-success')) . '" style="width:' . $report['api_requests']['success_rate'] . '%"></div>
                    </div>
                </div>
            </div>
        </div>';
        
        // Section des recommandations
        if (!empty($report['recommendations'])) {
            $html .= '
        <div class="card">
            <div class="card-header">Recommandations</div>
            <div class="card-body">
                <ul>';
            
            foreach ($report['recommendations'] as $recommendation) {
                $html .= '
                    <li>' . htmlspecialchars($recommendation) . '</li>';
            }
            
            $html .= '
                </ul>
            </div>
        </div>';
        }
        
        // Section des erreurs les plus fréquentes
        if (!empty($report['errors']['most_frequent'])) {
            $html .= '
        <div class="card">
            <div class="card-header">Erreurs les plus fréquentes</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Message</th>
                            <th>Nombre d\'occurrences</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($report['errors']['most_frequent'] as $error) {
                $html .= '
                        <tr>
                            <td>' . htmlspecialchars($error['message']) . '</td>
                            <td>' . $error['count'] . '</td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
        }
        
        // Section des erreurs récentes
        if (!empty($report['errors']['recent'])) {
            $html .= '
        <div class="card">
            <div class="card-header">Erreurs récentes</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Message</th>
                            <th>Contexte</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($report['errors']['recent'] as $error) {
                $html .= '
                        <tr>
                            <td>' . $error['time'] . '</td>
                            <td>' . htmlspecialchars($error['message']) . '</td>
                            <td><pre>' . htmlspecialchars(json_encode($error['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
        }
        
        // Section des requêtes AJAX échouées
        if (!empty($report['ajax_requests']['failed'])) {
            $html .= '
        <div class="card">
            <div class="card-header">Requêtes AJAX échouées</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>URL</th>
                            <th>Requête</th>
                            <th>Réponse</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($report['ajax_requests']['failed'] as $ajax) {
                $html .= '
                        <tr>
                            <td>' . $ajax['time'] . '</td>
                            <td>' . htmlspecialchars($ajax['url']) . '</td>
                            <td><pre>' . htmlspecialchars(json_encode($ajax['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>
                            <td><pre>' . htmlspecialchars(json_encode($ajax['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
        }
        
        // Section des opérations les plus lentes
        if (!empty($report['performance']['slowest_operations'])) {
            $html .= '
        <div class="card">
            <div class="card-header">Opérations les plus lentes</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Durée</th>
                            <th>Contexte</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($report['performance']['slowest_operations'] as $op) {
                $html .= '
                        <tr>
                            <td>' . htmlspecialchars($op['name']) . '</td>
                            <td>' . $op['duration'] . '</td>
                            <td><pre>' . htmlspecialchars(json_encode($op['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
        }
        
        $html .= '
    </div>
</body>
</html>';
        
        // Écriture du fichier de rapport
        $reportFile = dirname(self::$diagnosticFile) . '/iabot_analysis_' . date('Y-m-d_H-i-s') . '.html';
        file_put_contents($reportFile, $html);
        
        // Log de la génération du rapport
        self::info('Rapport d\'analyse généré', ['file' => $reportFile]);
        
        return $reportFile;
    }
    
    /**
     * Génère un rapport de diagnostic complet
     * 
     * @param bool $includeSystemInfo Inclure les informations système
     * @param bool $includeModuleConfig Inclure la configuration du module
     * @return string Chemin vers le fichier de diagnostic
     */
    public static function generateDiagnostic($includeSystemInfo = true, $includeModuleConfig = true)
    {
        // Initialisation du logger si nécessaire
        if (empty(self::$diagnosticFile)) {
            self::init();
        }
        
        // Collecte des informations système
        $systemInfo = [];
        if ($includeSystemInfo) {
            $systemInfo = [
                'PHP Version' => phpversion(),
                'PrestaShop Version' => _PS_VERSION_,
                'Server Software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
                'User Agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown',
                'Memory Limit' => ini_get('memory_limit'),
                'Max Execution Time' => ini_get('max_execution_time'),
                'Extensions' => get_loaded_extensions(),
                'Constants' => [
                    '_PS_ROOT_DIR_' => defined('_PS_ROOT_DIR_') ? _PS_ROOT_DIR_ : 'Not defined',
                    '_PS_MODULE_DIR_' => defined('_PS_MODULE_DIR_') ? _PS_MODULE_DIR_ : 'Not defined',
                    '_PS_DEV_MODE_' => defined('_PS_DEV_MODE_') ? (_PS_DEV_MODE_ ? 'true' : 'false') : 'Not defined'
                ]
            ];
        }
        
        // Collecte de la configuration du module
        $moduleConfig = [];
        if ($includeModuleConfig) {
            $configKeys = [
                'IABOT_LIVE_MODE',
                'IABOT_API_KEY',
                'IABOT_AI_MODEL',
                'IABOT_AI_TEMPERATURE',
                'IABOT_CHAT_COLOR',
                'IABOT_CHAT_POSITION',
                'IABOT_WELCOME_MESSAGE',
                'IABOT_PROMPT_PLACEHOLDER',
                'IABOT_SYSTEM_MESSAGE'
            ];
            
            foreach ($configKeys as $key) {
                $value = Configuration::get($key);
                // Masquer la clé API pour des raisons de sécurité
                if ($key === 'IABOT_API_KEY' && !empty($value)) {
                    $value = substr($value, 0, 4) . '...' . substr($value, -4);
                }
                $moduleConfig[$key] = $value;
            }
        }
        
        // Génération du contenu HTML
        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic IaBot - ' . date('Y-m-d H:i:s') . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
        h1, h2, h3 { color: #2C3E50; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: #fff; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
        .card-header { background: #3498DB; color: white; padding: 15px; font-weight: bold; }
        .card-body { padding: 15px; }
        .error { background: #FADBD8; }
        .warning { background: #FCF3CF; }
        .success { background: #D5F5E3; }
        .info { background: #D6EAF8; }
        table { width: 100%; border-collapse: collapse; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .timestamp { color: #7F8C8D; font-size: 0.9em; }
        .badge { display: inline-block; padding: 3px 7px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-error { background: #E74C3C; color: white; }
        .badge-warning { background: #F39C12; color: white; }
        .badge-info { background: #3498DB; color: white; }
        .badge-debug { background: #95A5A6; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnostic IaBot</h1>
        <p class="timestamp">Généré le ' . date('Y-m-d H:i:s') . '</p>
        
        <div class="card">
            <div class="card-header">Résumé</div>
            <div class="card-body">
                <p>Erreurs: ' . count(array_filter(self::$errorStack, function($item) { return $item['level'] === 'ERROR'; })) . '</p>
                <p>Avertissements: ' . count(array_filter(self::$errorStack, function($item) { return $item['level'] === 'WARNING'; })) . '</p>
                <p>Requêtes AJAX: ' . count(self::$ajaxRequests) . '</p>
                <p>Requêtes API: ' . count(self::$apiRequests) . '</p>
            </div>
        </div>';
        
        // Section des erreurs et avertissements
        if (!empty(self::$errorStack)) {
            $html .= '
        <div class="card">
            <div class="card-header">Erreurs et Avertissements</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Niveau</th>
                            <th>Heure</th>
                            <th>Message</th>
                            <th>Contexte</th>
                            <th>Trace</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach (self::$errorStack as $error) {
                $badgeClass = strtolower($error['level']) === 'error' ? 'badge-error' : 'badge-warning';
                $html .= '
                        <tr>
                            <td><span class="badge ' . $badgeClass . '">' . $error['level'] . '</span></td>
                            <td>' . $error['time'] . '</td>
                            <td>' . htmlspecialchars($error['message']) . '</td>
                            <td><pre>' . htmlspecialchars(json_encode($error['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>
                            <td><pre>' . htmlspecialchars(self::formatTrace($error['trace'])) . '</pre></td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
        }
        
        // Section des requêtes AJAX
        if (!empty(self::$ajaxRequests)) {
            $html .= '
        <div class="card">
            <div class="card-header">Requêtes AJAX</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th>Heure</th>
                            <th>URL</th>
                            <th>Requête</th>
                            <th>Réponse</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach (self::$ajaxRequests as $ajax) {
                $badgeClass = $ajax['success'] ? 'badge-info' : 'badge-error';
                $status = $ajax['success'] ? 'Succès' : 'Échec';
                $html .= '
                        <tr>
                            <td><span class="badge ' . $badgeClass . '">' . $status . '</span></td>
                            <td>' . $ajax['time'] . '</td>
                            <td>' . htmlspecialchars($ajax['url']) . '</td>
                            <td><pre>' . htmlspecialchars(json_encode($ajax['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>
                            <td><pre>' . htmlspecialchars(json_encode($ajax['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
        }
        
        // Section des requêtes API
        if (!empty(self::$apiRequests)) {
            $html .= '
        <div class="card">
            <div class="card-header">Requêtes API</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th>Heure</th>
                            <th>Point de terminaison</th>
                            <th>Requête</th>
                            <th>Réponse</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach (self::$apiRequests as $api) {
                $badgeClass = $api['success'] ? 'badge-info' : 'badge-error';
                $status = $api['success'] ? 'Succès' : 'Échec';
                $html .= '
                        <tr>
                            <td><span class="badge ' . $badgeClass . '">' . $status . '</span></td>
                            <td>' . $api['time'] . '</td>
                            <td>' . htmlspecialchars($api['endpoint']) . '</td>
                            <td><pre>' . htmlspecialchars(json_encode($api['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>
                            <td><pre>' . htmlspecialchars(json_encode($api['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
        }
        
        // Section des performances
        if (!empty(self::$performanceMetrics)) {
            $html .= '
        <div class="card">
            <div class="card-header">Performances</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Durée</th>
                            <th>Mémoire utilisée</th>
                            <th>Mémoire maximale</th>
                            <th>Contexte</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach (self::$performanceMetrics as $metric) {
                $html .= '
                        <tr>
                            <td>' . htmlspecialchars($metric['name']) . '</td>
                            <td>' . round($metric['duration'] * 1000, 2) . ' ms</td>
                            <td>' . self::formatBytes($metric['memory_used']) . '</td>
                            <td>' . self::formatBytes($metric['memory_peak']) . '</td>
                            <td><pre>' . htmlspecialchars(json_encode($metric['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
        }
        
        // Section des données de session
        if (!empty(self::$sessionData)) {
            $html .= '
        <div class="card">
            <div class="card-header">Données de session</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Clé</th>
                            <th>Valeur</th>
                            <th>Heure</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach (self::$sessionData as $key => $data) {
                $html .= '
                        <tr>
                            <td>' . htmlspecialchars($key) . '</td>
                            <td>' . htmlspecialchars($data['value']) . '</td>
                            <td>' . $data['time'] . '</td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
        }
        
        // Section des informations système
        if (!empty($systemInfo)) {
            $html .= '
        <div class="card">
            <div class="card-header">Informations Système</div>
            <div class="card-body">
                <table>
                    <tbody>';
            
            foreach ($systemInfo as $key => $value) {
                if ($key === 'Extensions' || $key === 'Constants') {
                    $html .= '
                        <tr>
                            <td><strong>' . htmlspecialchars($key) . '</strong></td>
                            <td><pre>' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>
                        </tr>';
                } else {
                    $html .= '
                        <tr>
                            <td><strong>' . htmlspecialchars($key) . '</strong></td>
                            <td>' . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . '</td>
                        </tr>';
                }
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
        }
        
        // Section de la configuration du module
        if (!empty($moduleConfig)) {
            $html .= '
        <div class="card">
            <div class="card-header">Configuration du Module</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Clé</th>
                            <th>Valeur</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($moduleConfig as $key => $value) {
                $html .= '
                        <tr>
                            <td>' . htmlspecialchars($key) . '</td>
                            <td>' . htmlspecialchars($value) . '</td>
                        </tr>';
            }
            
            $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
        }
        
        $html .= '
    </div>
</body>
</html>';
        
        // Écriture du fichier de diagnostic
        file_put_contents(self::$diagnosticFile, $html);
        
        // Log de la génération du diagnostic
        self::info('Diagnostic généré', ['file' => self::$diagnosticFile]);
        
        return self::$diagnosticFile;
    }
}
