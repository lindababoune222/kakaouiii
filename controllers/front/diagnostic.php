<?php
/**
 * Contrôleur de diagnostic pour le module IaBot
 * 
 * @author  Développeur
 * @copyright 2025
 */

class IabotDiagnosticModuleFrontController extends ModuleFrontController
{
    /** @var bool Indique si le contrôleur est accessible publiquement */
    public $auth = true;
    
    /** @var bool Indique si SSL est requis */
    public $ssl = true;
    
    /**
     * Initialisation du contrôleur
     */
    public function init()
    {
        parent::init();
        
        // Utiliser un token d'accès au lieu de vérifier l'authentification employé
        $token = Tools::getValue('token');
        $validToken = Configuration::get('IABOT_DIAGNOSTIC_TOKEN');
        
        // Si aucun token n'est configuré, en créer un
        if (empty($validToken)) {
            $validToken = Tools::passwdGen(32);
            Configuration::updateValue('IABOT_DIAGNOSTIC_TOKEN', $validToken);
        }
        
        // Vérifier si le token est valide ou si l'utilisateur est un employé
        $isEmployee = $this->context->employee && $this->context->employee->isLoggedBack();
        $isValidToken = !empty($token) && $token === $validToken;
        
        if (!$isEmployee && !$isValidToken) {
            // Rediriger vers la page d'accueil avec un message d'erreur
            Tools::redirect('index.php?controller=authentication&back=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }
    
    /**
     * Affichage du diagnostic
     */
    public function initContent()
    {
        parent::initContent();
        
        // Inclusion des classes nécessaires
        require_once(_PS_MODULE_DIR_ . 'iabot/classes/IaBotLogger.php');
        
        // Initialisation du logger avec mode verbose
        IaBotLogger::init(true, 4, true);
        
        // Traitement des requêtes AJAX
        if (Tools::getValue('ajax')) {
            $this->processAjaxRequest();
            exit;
        }
        
        // Traitement des actions
        $action = Tools::getValue('action');
        if ($action) {
            $this->processAction($action);
        }
        
        // Génération du rapport de diagnostic
        $diagnosticFile = IaBotLogger::generateDiagnostic(true, true);
        
        // Préparation des données pour l'affichage
        $this->context->smarty->assign([
            'diagnostic_file' => $diagnosticFile,
            'diagnostic_url' => $this->context->link->getModuleLink('iabot', 'diagnostic', ['view_file' => basename($diagnosticFile)]),
            'module_dir' => _PS_MODULE_DIR_ . 'iabot/',
            'action_result' => $this->context->cookie->action_result,
            'module_link' => $this->context->link->getAdminLink('AdminModules') . '&configure=iabot'
        ]);
        
        // Suppression du message de résultat après affichage
        $this->context->cookie->action_result = null;
        
        // Affichage du template
        $this->setTemplate('module:iabot/views/templates/front/diagnostic.tpl');
    }
    
    /**
     * Traitement des requêtes AJAX
     */
    private function processAjaxRequest()
    {
        $testType = Tools::getValue('test_type');
        $testMessage = Tools::getValue('test_message', 'Bonjour, ceci est un test.');
        $testData = Tools::getValue('test_data', []);
        $result = ['success' => false, 'message' => 'Type de test non reconnu'];
        
        switch ($testType) {
            case 'ajax':
                // Test de requête AJAX
                $result = [
                    'success' => true,
                    'message' => 'Test de requête AJAX réussi',
                    'server' => $_SERVER,
                    'headers' => getallheaders()
                ];
                break;
                
            case 'conversation':
                // Test de création de conversation
                try {
                    $conversation = new IaBotConversation();
                    $conversation->token = Tools::passwdGen(32);
                    $conversation->ip_address = Tools::getRemoteAddr();
                    $conversation->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
                    $conversation->is_customer_logged = false;
                    $conversation->id_customer = null;
                    $conversation->date_add = date('Y-m-d H:i:s');
                    $conversation->date_upd = date('Y-m-d H:i:s');
                    
                    if ($conversation->add()) {
                        $result = [
                            'success' => true,
                            'message' => 'Conversation créée avec succès',
                            'conversation' => [
                                'id' => $conversation->id,
                                'token' => $conversation->token
                            ]
                        ];
                    } else {
                        $result = [
                            'success' => false,
                            'message' => 'Échec de création de la conversation',
                            'errors' => $conversation->getErrors()
                        ];
                    }
                } catch (Exception $e) {
                    $result = [
                        'success' => false,
                        'message' => 'Exception lors de la création de la conversation',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ];
                }
                break;
                
            case 'message':
                // Test d'envoi de message
                try {
                    // Création d'une conversation temporaire
                    $conversation = new IaBotConversation();
                    $conversation->token = Tools::passwdGen(32);
                    $conversation->ip_address = Tools::getRemoteAddr();
                    $conversation->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
                    $conversation->is_customer_logged = false;
                    $conversation->id_customer = null;
                    $conversation->date_add = date('Y-m-d H:i:s');
                    $conversation->date_upd = date('Y-m-d H:i:s');
                    
                    if ($conversation->add()) {
                        // Envoi d'un message
                        $userMessage = new IaBotMessage();
                        $userMessage->id_conversation = (int)$conversation->id;
                        $userMessage->content = $testMessage;
                        $userMessage->sender = 'user';
                        $userMessage->date_add = date('Y-m-d H:i:s');
                        
                        if ($userMessage->add()) {
                            // Génération de la réponse
                            $botMessage = new IaBotMessage();
                            $botMessage->id_conversation = (int)$conversation->id;
                            
                            $response = $botMessage->generateAIResponse($testMessage, $conversation);
                            
                            $botMessage->content = $response['message'];
                            $botMessage->sender = 'bot';
                            $botMessage->date_add = date('Y-m-d H:i:s');
                            
                            if ($botMessage->add()) {
                                $result = [
                                    'success' => true,
                                    'message' => 'Message traité avec succès',
                                    'conversation' => [
                                        'id' => $conversation->id,
                                        'token' => $conversation->token
                                    ],
                                    'user_message' => [
                                        'id' => $userMessage->id,
                                        'content' => $userMessage->content
                                    ],
                                    'bot_message' => [
                                        'id' => $botMessage->id,
                                        'content' => $botMessage->content
                                    ],
                                    'recommendations' => $response['recommendations'] ?? []
                                ];
                            } else {
                                $result = [
                                    'success' => false,
                                    'message' => 'Échec d\'ajout du message bot',
                                    'errors' => $botMessage->getErrors()
                                ];
                            }
                        } else {
                            $result = [
                                'success' => false,
                                'message' => 'Échec d\'ajout du message utilisateur',
                                'errors' => $userMessage->getErrors()
                            ];
                        }
                    } else {
                        $result = [
                            'success' => false,
                            'message' => 'Échec de création de la conversation',
                            'errors' => $conversation->getErrors()
                        ];
                    }
                } catch (Exception $e) {
                    $result = [
                        'success' => false,
                        'message' => 'Exception lors du traitement du message',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ];
                }
                break;
                
            case 'api':
                // Test de l'API OpenRouter
                try {
                    $connector = new IaBotAIConnector();
                    $response = $connector->generateResponse($testMessage, []);
                    
                    $result = [
                        'success' => true,
                        'message' => 'Test de l\'API OpenRouter réussi',
                        'response' => $response
                    ];
                } catch (Exception $e) {
                    $result = [
                        'success' => false,
                        'message' => 'Échec du test de l\'API OpenRouter',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ];
                }
                break;
                
            case 'indexing':
                // Test d'indexation des produits
                try {
                    $forceReindex = isset($testData['force_reindex']) ? (bool)$testData['force_reindex'] : false;
                    $startTime = microtime(true);
                    
                    // Récupération des produits à indexer
                    $productIds = [];
                    $limit = 10; // Limiter à 10 produits pour le test
                    
                    if ($forceReindex) {
                        // Récupérer tous les produits actifs
                        $sql = new DbQuery();
                        $sql->select('id_product');
                        $sql->from('product');
                        $sql->where('active = 1');
                        $sql->limit($limit);
                        $productIds = array_column(Db::getInstance()->executeS($sql), 'id_product');
                    } else {
                        // Récupérer les produits non indexés
                        $sql = 'SELECT p.id_product 
                                FROM `' . _DB_PREFIX_ . 'product` p 
                                LEFT JOIN `' . _DB_PREFIX_ . 'iabot_product_index` i ON p.id_product = i.id_product 
                                WHERE p.active = 1 AND i.id_product IS NULL 
                                LIMIT ' . (int)$limit;
                        $productIds = array_column(Db::getInstance()->executeS($sql), 'id_product');
                    }
                    
                    // Indexation des produits
                    $indexedCount = 0;
                    $indexingErrors = [];
                    $indexedProducts = [];
                    
                    foreach ($productIds as $idProduct) {
                        $product = new Product($idProduct, true, Context::getContext()->language->id);
                        
                        if (Validate::isLoadedObject($product)) {
                            try {
                                // Vérifier si le produit est déjà indexé
                                $existingIndex = IaBotProductIndex::getByProductId($idProduct);
                                
                                if ($existingIndex && !$forceReindex) {
                                    // Produit déjà indexé, on passe au suivant
                                    continue;
                                }
                                
                                if ($existingIndex && $forceReindex) {
                                    // Supprimer l'index existant
                                    $index = new IaBotProductIndex($existingIndex['id_iabot_product_index']);
                                    $index->delete();
                                }
                                
                                // Création ou mise à jour de l'index
                                $index = new IaBotProductIndex();
                                $index->id_product = (int)$idProduct;
                                $index->name = $product->name;
                                $index->description = strip_tags($product->description);
                                $index->short_description = strip_tags($product->description_short);
                                $index->reference = $product->reference;
                                $index->price = $product->getPrice();
                                $index->link = Context::getContext()->link->getProductLink($product);
                                $index->date_add = date('Y-m-d H:i:s');
                                $index->date_upd = date('Y-m-d H:i:s');
                                
                                if ($index->add()) {
                                    $indexedCount++;
                                    $indexedProducts[] = [
                                        'id' => $idProduct,
                                        'name' => $product->name,
                                        'reference' => $product->reference
                                    ];
                                } else {
                                    $indexingErrors[] = [
                                        'id' => $idProduct,
                                        'name' => $product->name,
                                        'error' => 'Échec de l\'ajout dans l\'index'
                                    ];
                                }
                            } catch (Exception $e) {
                                $indexingErrors[] = [
                                    'id' => $idProduct,
                                    'error' => $e->getMessage()
                                ];
                            }
                        } else {
                            $indexingErrors[] = [
                                'id' => $idProduct,
                                'error' => 'Produit non trouvé ou inactif'
                            ];
                        }
                    }
                    
                    $endTime = microtime(true);
                    $executionTime = round($endTime - $startTime, 2);
                    
                    // Vérification de l'index après indexation
                    $totalIndexed = Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'iabot_product_index`');
                    
                    // Recherche de test
                    $searchResults = [];
                    if ($indexedCount > 0 && !empty($indexedProducts)) {
                        // Utiliser le nom du premier produit indexé comme terme de recherche
                        $searchTerm = explode(' ', $indexedProducts[0]['name'])[0];
                        $searchResults = IaBotProductIndex::search($searchTerm, 5);
                    }
                    
                    // Journalisation de l'opération
                    IaBotLogger::logIndexing([
                        'operation' => 'test_indexing',
                        'products_processed' => count($productIds),
                        'products_indexed' => $indexedCount,
                        'execution_time' => $executionTime,
                        'force_reindex' => $forceReindex
                    ]);
                    
                    $result = [
                        'success' => true,
                        'message' => 'Test d\'indexation des produits terminé',
                        'stats' => [
                            'products_found' => count($productIds),
                            'products_indexed' => $indexedCount,
                            'execution_time' => $executionTime . ' secondes',
                            'total_indexed' => $totalIndexed
                        ],
                        'indexed_products' => $indexedProducts,
                        'errors' => $indexingErrors,
                        'search_results' => $searchResults
                    ];
                } catch (Exception $e) {
                    $result = [
                        'success' => false,
                        'message' => 'Échec du test d\'indexation des produits',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ];
                }
                break;
                
            case 'database':
                // Test de la base de données
                $tables = [
                    'iabot_conversation',
                    'iabot_message',
                    'iabot_knowledge',
                    'iabot_product_index',
                    'iabot_recommendation',
                    'iabot_statistic'
                ];
                
                $tableStatus = [];
                foreach ($tables as $table) {
                    $fullTable = _DB_PREFIX_ . $table;
                    $tableExists = count(Db::getInstance()->executeS("SHOW TABLES LIKE '$fullTable'")) > 0;
                    
                    if ($tableExists) {
                        $count = Db::getInstance()->getValue("SELECT COUNT(*) FROM `$fullTable`");
                        $structure = Db::getInstance()->executeS("DESCRIBE `$fullTable`");
                        $tableStatus[$table] = [
                            'exists' => true,
                            'count' => $count,
                            'structure' => $structure
                        ];
                    } else {
                        $tableStatus[$table] = [
                            'exists' => false
                        ];
                    }
                }
                
                $result = [
                    'success' => true,
                    'message' => 'Test de base de données terminé',
                    'tables' => $tableStatus
                ];
                break;
        }
        
        // Enregistrement du résultat du test dans les logs
        IaBotLogger::info('Test de diagnostic exécuté', [
            'type' => $testType,
            'result' => $result
        ]);
        
        // Envoi de la réponse JSON
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    /**
     * Traitement des actions
     * 
     * @param string $action Action à exécuter
     */
    private function processAction($action)
    {
        $result = '';
        
        switch ($action) {
            case 'clear_logs':
                // Vider les logs
                $logDir = _PS_MODULE_DIR_ . 'iabot/logs';
                if (is_dir($logDir)) {
                    $logFiles = glob($logDir . '/*.log');
                    $count = 0;
                    foreach ($logFiles as $file) {
                        if (unlink($file)) {
                            $count++;
                        }
                    }
                    $result = "$count fichiers de log ont été supprimés.";
                } else {
                    $result = "Le répertoire de logs n'existe pas.";
                }
                break;
                
            case 'reset_recommendations':
                // Réinitialiser les recommandations
                $module = Module::getInstanceByName('iabot');
                if ($module && method_exists($module, 'resetRecommendationsTable')) {
                    $success = $module->resetRecommendationsTable();
                    if ($success) {
                        $result = "La table des recommandations a été réinitialisée avec succès.";
                    } else {
                        $result = "Une erreur est survenue lors de la réinitialisation de la table des recommandations.";
                    }
                } else {
                    $result = "La méthode de réinitialisation n'est pas disponible.";
                }
                break;
                
            case 'check_tables':
                // Vérifier les tables
                $tables = [
                    'iabot_conversation',
                    'iabot_message',
                    'iabot_knowledge',
                    'iabot_product_index',
                    'iabot_recommendation',
                    'iabot_statistic'
                ];
                
                $result = "Vérification des tables :\n";
                foreach ($tables as $table) {
                    $fullTable = _DB_PREFIX_ . $table;
                    $tableExists = count(Db::getInstance()->executeS("SHOW TABLES LIKE '$fullTable'")) > 0;
                    if ($tableExists) {
                        $count = Db::getInstance()->getValue("SELECT COUNT(*) FROM `$fullTable`");
                        $result .= "- $table : Existe ($count enregistrements)\n";
                    } else {
                        $result .= "- $table : N'existe pas\n";
                    }
                }
                break;
                
            case 'view_file':
                // Afficher un fichier de diagnostic
                $filename = Tools::getValue('view_file');
                if ($filename && preg_match('/^iabot_(diagnostic|analysis)_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}\.(html|log)$/', $filename)) {
                    $filePath = _PS_MODULE_DIR_ . 'iabot/logs/' . $filename;
                    if (file_exists($filePath)) {
                        header('Content-Type: text/html; charset=utf-8');
                        readfile($filePath);
                        exit;
                    }
                }
                $result = "Fichier non trouvé ou nom de fichier invalide.";
                break;
                
            case 'analyze':
                // Générer un rapport d'analyse
                $reportFile = IaBotLogger::generateAnalysisReport();
                $result = "Rapport d'analyse généré : " . basename($reportFile);
                Tools::redirect($this->context->link->getModuleLink('iabot', 'diagnostic', ['view_file' => basename($reportFile)]));
                exit;
                break;
        }
        
        // Stockage du résultat dans un cookie pour affichage
        if ($result) {
            $this->context->cookie->action_result = $result;
        }
    }
}
