<?php
/**
 * Contrôleur AJAX pour le chat IA
 * 
 * @author  Développeur
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotConversation.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotMessage.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotRecommendation.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotStatistic.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotLogger.php';

// Vérification de la version de PrestaShop
if (!defined('_PS_VERSION_')) {
    exit;
}

// Définition du mode développement pour l'IDE (uniquement pour le développement)
if (!defined('_PS_DEV_MODE_')) {
    define('_PS_DEV_MODE_', true);
}

// Inclusion du fichier d'aide pour l'IDE (uniquement pour le développement)
if (defined('_PS_DEV_MODE_') && _PS_DEV_MODE_) {
    require_once dirname(__FILE__) . '/../../inc/prestashop-ide-helper.php';
}

// Initialisation du logger avec mode verbose activé
IaBotLogger::init(true, 4, true);

class IabotAjaxModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool Utilisation de SSL
     */
    public $ssl = true;
    
    /**
     * Initialisation du contrôleur
     */
    public function init()
    {
        parent::init();
        header('Content-Type: application/json');
        IaBotLogger::info('Initialisation du contrôleur AJAX', [
            'uri' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD']
        ]);
    }

    /**
     * Post-process
     */
    public function postProcess()
    {
        // Vérification de la requête AJAX
        if (!$this->isXmlHttpRequest()) {
            IaBotLogger::warning('Requête non-AJAX rejetée', [
                'headers' => getallheaders(),
                'server' => $_SERVER
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Requête invalide'
            ]);
            return;
        }

        // Récupération des données de la requête
        $input = [];
        
        // Essayer de lire les données JSON du corps de la requête
        $inputJSON = file_get_contents('php://input');
        IaBotLogger::debug('Données brutes reçues', ['raw' => $inputJSON]);
        
        if (!empty($inputJSON)) {
            $decoded = json_decode($inputJSON, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $decoded;
                IaBotLogger::debug('Données JSON décodées avec succès', ['input' => $input]);
            } else {
                IaBotLogger::warning('Erreur de décodage JSON', [
                    'error' => json_last_error_msg(),
                    'raw' => $inputJSON
                ]);
            }
        }
        
        // Si aucune donnée JSON valide n'a été trouvée, essayer avec les données POST
        if (empty($input) && !empty($_POST)) {
            $input = $_POST;
            IaBotLogger::debug('Utilisation des données POST', ['post' => $_POST]);
        }
        
        // Vérifier si nous avons des données valides
        if (empty($input)) {
            IaBotLogger::error('Aucune donnée valide reçue', [
                'post' => $_POST,
                'raw' => $inputJSON,
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'non défini'
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Données invalides'
            ]);
            return;
        }

        // Exécution de l'action appropriée
        if (isset($input['action'])) {
            IaBotLogger::info('Action AJAX reçue', ['action' => $input['action']]);
            
            switch ($input['action']) {
                case 'initConversation':
                    $this->processInitConversation($input);
                    break;
                case 'sendMessage':
                    $this->processSendMessage($input);
                    break;
                case 'recordRecommendationClick':
                    $this->processRecordRecommendationClick($input);
                    break;
                case 'indexProducts':
                    $this->processIndexProducts($input);
                    break;
                case 'get_indexing_stats':
                    $this->processGetIndexingStats();
                    break;
                case 'optimize_products':
                    $this->processOptimizeProducts($input);
                    break;
                default:
                    IaBotLogger::warning('Action inconnue', ['action' => $input['action']]);
                    $this->sendJsonResponse([
                        'success' => false,
                        'message' => 'Action inconnue'
                    ]);
            }
        } else {
            IaBotLogger::warning('Action non spécifiée', ['input' => $input]);
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Action non spécifiée'
            ]);
        }
    }

    /**
     * Vérifie si la requête est une requête AJAX
     * 
     * @return bool Retourne true si la requête est une requête AJAX, false sinon
     */
    public function isXmlHttpRequest()
    {
        $headers = getallheaders();
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
        
        IaBotLogger::debug('Vérification requête AJAX', [
            'is_ajax' => $isAjax,
            'headers' => $headers,
            'http_x_requested_with' => $_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'non défini'
        ]);
        
        return $isAjax;
    }
    
    /**
     * Envoie une réponse JSON et termine l'exécution
     *
     * @param array $data Données à envoyer
     * @return void
     */
    private function sendJsonResponse($data)
    {
        IaBotLogger::debug('Envoi de la réponse JSON', ['response' => $data]);
        
        header('Content-Type: application/json');
        die(json_encode($data));
    }
    
    /**
     * Traite l'initialisation d'une conversation
     *
     * @param array $input Données d'entrée
     * @return void
     */
    private function processInitConversation($input)
    {
        try {
            IaBotLogger::info('Initialisation d\'une conversation', ['input' => $input]);
            
            // Création d'une nouvelle conversation
            $conversation = new IaBotConversation();
            $conversation->token = Tools::passwdGen(32);
            $conversation->ip_address = Tools::getRemoteAddr();
            $conversation->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $conversation->is_customer_logged = isset($input['isCustomerLogged']) ? (bool)$input['isCustomerLogged'] : false;
            $conversation->id_customer = isset($input['customerId']) ? (int)$input['customerId'] : null;
            $conversation->date_add = date('Y-m-d H:i:s');
            $conversation->date_upd = date('Y-m-d H:i:s');
            
            if ($conversation->add()) {
                IaBotLogger::info('Conversation créée avec succès', [
                    'id_conversation' => $conversation->id,
                    'token' => $conversation->token
                ]);
                
                // Ajout du message de bienvenue
                $welcomeMessage = new IaBotMessage();
                $welcomeMessage->id_conversation = (int)$conversation->id;
                $welcomeMessage->content = Configuration::get('IABOT_WELCOME_MESSAGE');
                $welcomeMessage->sender = 'bot';
                $welcomeMessage->date_add = date('Y-m-d H:i:s');
                $welcomeMessage->add();
                
                $this->sendJsonResponse([
                    'success' => true,
                    'conversationId' => $conversation->token,
                    'welcomeMessage' => $welcomeMessage->content
                ]);
            } else {
                IaBotLogger::error('Échec de création de la conversation', [
                    'errors' => $conversation->getErrors()
                ]);
                
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Erreur lors de la création de la conversation'
                ]);
            }
        } catch (Exception $e) {
            IaBotLogger::error('Exception lors de l\'initialisation de la conversation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur interne: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Traite l'envoi d'un message
     *
     * @param array $input Données d'entrée
     * @return void
     */
    private function processSendMessage($input)
    {
        try {
            IaBotLogger::info('Traitement d\'un message', ['input' => $input]);
            
            // Vérification des données requises
            if (!isset($input['conversationId']) || !isset($input['message'])) {
                IaBotLogger::warning('Données manquantes pour l\'envoi du message', [
                    'conversationId' => $input['conversationId'] ?? 'non défini',
                    'message' => $input['message'] ?? 'non défini'
                ]);
                
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Données manquantes'
                ]);
                return;
            }
            
            // Récupération de la conversation
            $conversation = IaBotConversation::getByToken($input['conversationId']);
            if (!Validate::isLoadedObject($conversation)) {
                IaBotLogger::warning('Conversation non trouvée', [
                    'conversationId' => $input['conversationId']
                ]);
                
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Conversation non trouvée'
                ]);
                return;
            }
            
            // Mise à jour de la date de la conversation
            $conversation->date_upd = date('Y-m-d H:i:s');
            $conversation->update();
            
            // Enregistrement du message de l'utilisateur
            $userMessage = new IaBotMessage();
            $userMessage->id_conversation = (int)$conversation->id;
            $userMessage->content = $input['message'];
            $userMessage->sender = 'user';
            $userMessage->date_add = date('Y-m-d H:i:s');
            $userMessage->add();
            
            // Génération de la réponse du bot
            $botMessage = new IaBotMessage();
            $botMessage->id_conversation = (int)$conversation->id;
            
            // Génération de la réponse via l'IA ou recherche dans la base de connaissances
            $response = $botMessage->generateAIResponse($input['message'], $conversation);
            
            $botMessage->content = $response['message'];
            $botMessage->sender = 'bot';
            $botMessage->date_add = date('Y-m-d H:i:s');
            $botMessage->add();
            
            // Statistiques
            IaBotStatistic::recordMessage($conversation->id, $userMessage->id, $botMessage->id);
            
            // Préparation de la réponse
            $responseData = [
                'success' => true,
                'message' => $botMessage->content
            ];
            
            // Ajout des recommandations de produits si disponibles
            if (!empty($response['recommendations'])) {
                $responseData['recommendations'] = $response['recommendations'];
            }
            
            IaBotLogger::info('Message traité avec succès', [
                'id_conversation' => $conversation->id,
                'id_user_message' => $userMessage->id,
                'id_bot_message' => $botMessage->id
            ]);
            
            $this->sendJsonResponse($responseData);
        } catch (Exception $e) {
            IaBotLogger::error('Exception lors du traitement du message', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur interne: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Traite l'enregistrement d'un clic sur une recommandation
     *
     * @param array $input Données d'entrée
     * @return void
     */
    private function processRecordRecommendationClick($input)
    {
        try {
            IaBotLogger::info('Enregistrement d\'un clic sur une recommandation', ['input' => $input]);
            
            // Vérification des données requises
            if (!isset($input['conversationId']) || !isset($input['productId'])) {
                IaBotLogger::warning('Données manquantes pour l\'enregistrement du clic', [
                    'conversationId' => $input['conversationId'] ?? 'non défini',
                    'productId' => $input['productId'] ?? 'non défini'
                ]);
                
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Données manquantes'
                ]);
                return;
            }
            
            // Récupération de la conversation
            $conversation = IaBotConversation::getByToken($input['conversationId']);
            if (!Validate::isLoadedObject($conversation)) {
                IaBotLogger::warning('Conversation non trouvée pour le clic', [
                    'conversationId' => $input['conversationId']
                ]);
                
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Conversation non trouvée'
                ]);
                return;
            }
            
            // Enregistrement du clic
            $productId = (int)$input['productId'];
            $result = IaBotRecommendation::recordClick($conversation->id, $productId);
            
            IaBotLogger::info('Clic sur recommandation enregistré', [
                'id_conversation' => $conversation->id,
                'id_product' => $productId,
                'result' => $result
            ]);
            
            $this->sendJsonResponse([
                'success' => true,
                'message' => 'Clic enregistré avec succès'
            ]);
        } catch (Exception $e) {
            IaBotLogger::error('Exception lors de l\'enregistrement du clic', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur interne: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Traitement de l'indexation des produits
     * 
     * @param array $input Données d'entrée
     * @return void
     */
    private function processIndexProducts($input)
    {
        // Initialisation du compteur et du tableau d'erreurs
        $count = 0;
        $errors = [];
        $startTime = microtime(true);
        
        try {
            // Vérification des paramètres
            $forceReindex = isset($input['force_reindex']) ? (bool)$input['force_reindex'] : false;
            
            // Journalisation du début de l'indexation
            IaBotLogger::info('Début de l\'indexation des produits', [
                'force_reindex' => $forceReindex
            ]);
            
            // Si réindexation forcée, vider la table d'abord
            if ($forceReindex) {
                Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'iabot_product_index`');
                IaBotLogger::info('Table d\'index vidée pour réindexation complète');
            }
            
            // Récupération des produits actifs
            $context = Context::getContext();
            $products = Product::getProducts($context->language->id, 0, 0, 'id_product', 'ASC', false, true);
            
            // Vérification que des produits ont été trouvés
            if (empty($products)) {
                IaBotLogger::warning('Aucun produit trouvé pour l\'indexation');
                IaBotLogger::recordIndexing('complete', [
                    'count' => 0,
                    'duration' => microtime(true) - $startTime,
                    'message' => 'Aucun produit trouvé'
                ]);
                $this->sendJsonResponse([
                    'success' => true,
                    'count' => 0,
                    'message' => 'Aucun produit trouvé pour l\'indexation'
                ]);
                return;
            }
            
            // Journalisation du nombre de produits trouvés
            IaBotLogger::info('Produits trouvés pour indexation', [
                'count' => count($products),
                'force_reindex' => $forceReindex
            ]);
            
            // Traitement de chaque produit
            $totalProducts = count($products);
            $processedProducts = 0;
            
            foreach ($products as $product) {
                $productId = (int)$product['id_product'];
                $processedProducts++;
                
                // Journalisation de la progression
                if ($processedProducts % 10 === 0 || $processedProducts === $totalProducts) {
                    $progress = round(($processedProducts / $totalProducts) * 100);
                    IaBotLogger::recordIndexing('progress', [
                        'processed' => $processedProducts,
                        'total' => $totalProducts,
                        'progress' => $progress
                    ]);
                }
                
                try {
                    // Récupération des données complètes du produit
                    $productObj = new Product($productId, true, $context->language->id);
                    
                    // Vérification que le produit est valide
                    if (!Validate::isLoadedObject($productObj)) {
                        $errors[] = "Produit ID $productId non valide";
                        IaBotLogger::warning("Produit ID $productId non valide lors de l'indexation");
                        continue;
                    }
                    
                    // Récupération des données à indexer
                    $name = $productObj->name;
                    $description = strip_tags($productObj->description);
                    $shortDescription = strip_tags($productObj->description_short);
                    $reference = $productObj->reference;
                    $price = $productObj->getPrice();
                    $link = $context->link->getProductLink($productObj);
                    
                    // Vérification si le produit existe déjà dans l'index
                    $existingIndex = IaBotProductIndex::getByProductId($productId);
                    
                    // Création ou mise à jour de l'index
                    if ($existingIndex && !$forceReindex) {
                        // Mise à jour de l'index existant via SQL direct pour éviter les problèmes d'ObjectModel
                        $updated = Db::getInstance()->update(
                            'iabot_product_index',
                            [
                                'name' => pSQL($name),
                                'description' => pSQL($description, true),
                                'short_description' => pSQL($shortDescription, true),
                                'reference' => pSQL($reference),
                                'price' => (float)$price,
                                'link' => pSQL($link),
                                'date_upd' => date('Y-m-d H:i:s')
                            ],
                            'id_product = ' . (int)$productId
                        );
                        
                        if ($updated) {
                            $count++;
                        } else {
                            $errors[] = "Erreur lors de la mise à jour de l'index du produit ID $productId";
                            IaBotLogger::error("Erreur lors de la mise à jour de l'index du produit ID $productId");
                        }
                    } else {
                        // Création d'un nouvel index via SQL direct
                        $inserted = Db::getInstance()->insert(
                            'iabot_product_index',
                            [
                                'id_product' => (int)$productId,
                                'name' => pSQL($name),
                                'description' => pSQL($description, true),
                                'short_description' => pSQL($shortDescription, true),
                                'reference' => pSQL($reference),
                                'price' => (float)$price,
                                'link' => pSQL($link),
                                'date_add' => date('Y-m-d H:i:s'),
                                'date_upd' => date('Y-m-d H:i:s')
                            ]
                        );
                        
                        if ($inserted) {
                            $count++;
                        } else {
                            $errors[] = "Erreur lors de l'indexation du produit ID $productId";
                            IaBotLogger::error("Erreur lors de l'indexation du produit ID $productId");
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = "Exception pour le produit ID $productId: " . $e->getMessage();
                    IaBotLogger::error('Erreur lors de l\'indexation d\'un produit', [
                        'id_product' => $productId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    IaBotLogger::recordIndexing('error', [
                        'id_product' => $productId,
                        'message' => $e->getMessage()
                    ]);
                }
            }
            
            // Calcul de la durée totale
            $duration = microtime(true) - $startTime;
            
            // Journalisation de la fin de l'indexation
            IaBotLogger::info('Fin de l\'indexation des produits', [
                'count' => $count,
                'errors' => count($errors),
                'duration' => $duration
            ]);
            
            IaBotLogger::recordIndexing('complete', [
                'count' => $count,
                'total' => $totalProducts,
                'errors' => count($errors),
                'duration' => $duration
            ]);
            
            // Enregistrement de la métrique de performance
            IaBotLogger::recordPerformance('product_indexing', $duration, [
                'count' => $count,
                'total' => $totalProducts,
                'errors' => count($errors)
            ]);
            
            // Envoi de la réponse
            $this->sendJsonResponse([
                'success' => true,
                'count' => $count,
                'total' => $totalProducts,
                'errors' => $errors,
                'duration' => round($duration, 2)
            ]);
        } catch (Exception $e) {
            IaBotLogger::error('Exception lors de l\'indexation des produits', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'indexation des produits: ' . $e->getMessage(),
                'count' => $count
            ]);
        }
    }
    
    /**
     * Récupère les statistiques d'indexation des produits
     * 
     * @return void
     */
    private function processGetIndexingStats()
    {
        try {
            // Vérification que la classe IaBotLogger est chargée
            if (!class_exists('IaBotLogger')) {
                require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotLogger.php';
            }
            
            // Récupération des statistiques d'indexation
            $stats = IaBotLogger::analyzeIndexingIssues();
            
            // Récupération des données supplémentaires
            $db = Db::getInstance();
            
            // Nombre total de produits indexés
            $totalIndexed = (int)$db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'iabot_product_index`');
            $stats['current_indexed'] = $totalIndexed;
            
            // Nombre total de produits actifs
            $totalProducts = (int)$db->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product` WHERE active = 1');
            $stats['total_active'] = $totalProducts;
            
            // Dernière indexation
            $lastIndexing = $db->getRow('SELECT MAX(date_upd) as date_upd FROM `' . _DB_PREFIX_ . 'iabot_product_index`');
            if ($lastIndexing && isset($lastIndexing['date_upd'])) {
                $stats['last_indexing_time'] = $lastIndexing['date_upd'];
            }
            
            // Envoi de la réponse
            $this->sendJsonResponse([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            IaBotLogger::error('Erreur lors de la récupération des statistiques d\'indexation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des statistiques d\'indexation: ' . $e->getMessage()
            ]);
        }

    }
}
    /**
     * Traite la demande d'optimisation des produits
     * 
     * @param array $input Données d'entrée
     */
    private function processOptimizeProducts($input)
    {
        try {
            // Vérification des permissions
            if (!$this->module->hasAdminRights()) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Permissions insuffisantes'
                ]);
                return;
            }
            
            // Récupération des IDs de produits
            if (!isset($input['product_ids'])) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Aucun produit sélectionné'
                ]);
                return;
            }
            
            // Décodage des IDs de produits
            $productIds = json_decode($input['product_ids'], true);
            
            if (!is_array($productIds) || empty($productIds)) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Format de données invalide'
                ]);
                return;
            }
            
            // Récupération des options d'optimisation
            $options = [
                'short_description_lines' => (int)($input['short_description_lines'] ?? 4),
                'long_description_lines' => (int)($input['long_description_lines'] ?? 15),
                'seo_level' => (int)($input['seo_level'] ?? 7),
                'additional_keywords' => $input['additional_keywords'] ?? ''
            ];
            
            // Journalisation de l'action
            IaBotLogger::info('Optimisation des produits', [
                'product_count' => count($productIds),
                'options' => $options
            ]);
            
            // Optimisation des produits
            $startTime = microtime(true);
            $result = $this->module->optimizeProducts($productIds, $options);
            $duration = microtime(true) - $startTime;
            
            // Journalisation du résultat
            IaBotLogger::info('Résultat de l\'optimisation des produits', [
                'success' => $result['success'],
                'optimized_count' => count($result['optimized_products']),
                'error_count' => count($result['errors']),
                'duration' => round($duration, 2) . ' secondes'
            ]);
            
            // Enregistrement des performances
            IaBotLogger::recordPerformance('product_optimization', $duration, [
                'product_count' => count($productIds),
                'optimized_count' => count($result['optimized_products']),
                'error_count' => count($result['errors'])
            ]);
            
            $this->sendJsonResponse([
                'success' => $result['success'],
                'message' => $result['message'],
                'optimized_products' => $result['optimized_products'],
                'errors' => $result['errors'],
                'duration' => round($duration, 2)
            ]);
        } catch (Exception $e) {
            IaBotLogger::error('Erreur lors de l\'optimisation des produits', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'optimisation des produits: ' . $e->getMessage()
            ]);
        }
    }
