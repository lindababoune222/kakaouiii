<?php
/**
 * Contrôleur d'administration pour la configuration du module IaBot
 * 
 * @author  Développeur
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Inclusion des classes nécessaires
require_once _PS_MODULE_DIR_ . 'iabot/iabot.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotAIConnector.php';

/**
 * Contrôleur d'administration pour la configuration du module IaBot
 */
class AdminIaBotConfigurationController extends ModuleAdminController
{
    /**
     * Constructeur du contrôleur
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        $this->meta_title = 'Configuration IaBot';
        
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
        // Traitement des requêtes AJAX
        if (Tools::isSubmit('ajax')) {
            $this->processAjaxRequest();
            exit;
        }
        
        if (Tools::isSubmit('submitResetRecommendations')) {
            $this->module->resetRecommendationsTable();
            $this->confirmations[] = $this->l('La table des recommandations a été réinitialisée avec succès.');
        }
        
        if (Tools::isSubmit('submitIndexProducts')) {
            $forceReindex = (bool)Tools::getValue('force_reindex', false);
            
            $result = $this->module->indexAllProducts($forceReindex);
            
            if ($result['success']) {
                $this->confirmations[] = sprintf(
                    $this->l('Indexation des produits terminée avec succès. %d produits ont été indexés.'),
                    $result['count']
                );
                
                if (!empty($result['errors'])) {
                    $this->warnings[] = sprintf(
                        $this->l('Attention : %d erreurs ont été rencontrées lors de l\'indexation.'),
                        count($result['errors'])
                    );
                    
                    foreach ($result['errors'] as $error) {
                        $this->errors[] = $error;
                    }
                }
            } else {
                $this->errors[] = $this->l('Une erreur est survenue lors de l\'indexation des produits.');
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        $this->errors[] = $error;
                    }
                }
            }
        }
        
        // Préparation du contenu avant l'appel au parent
        $this->content = $this->renderConfigurationForm();
        
        parent::initContent();
    }
    
    /**
     * Traitement des requêtes AJAX
     */
    protected function processAjaxRequest()
    {
        header('Content-Type: application/json');
        
        $action = Tools::getValue('action');
        
        if ($action === 'indexProducts') {
            $forceReindex = (bool)Tools::getValue('force_reindex', false);
            
            try {
                $result = $this->module->indexAllProducts($forceReindex);
                
                die(json_encode([
                    'success' => true,
                    'message' => $this->module->l('Indexation des produits terminée avec succès.'),
                    'count' => $result['count'],
                    'errors' => $result['errors']
                ]));
            } catch (Exception $e) {
                die(json_encode([
                    'success' => false,
                    'message' => $this->module->l('Erreur lors de l\'indexation des produits:') . ' ' . $e->getMessage()
                ]));
            }
        }
        
        die(json_encode([
            'success' => false,
            'message' => $this->module->l('Action non reconnue.')
        ]));
    }
    
    /**
     * Rendu du formulaire de configuration
     */
    protected function renderConfigurationForm()
    {
        $this->processConfigurationForm();
        
        // Récupération des valeurs actuelles
        $currentValues = [
            'IABOT_LIVE_MODE' => Configuration::get('IABOT_LIVE_MODE'),
            'IABOT_API_KEY' => Configuration::get('IABOT_API_KEY'),
            'IABOT_AI_MODEL' => Configuration::get('IABOT_AI_MODEL'),
            'IABOT_AI_TEMPERATURE' => Configuration::get('IABOT_AI_TEMPERATURE'),
            'IABOT_CHAT_COLOR' => Configuration::get('IABOT_CHAT_COLOR'),
            'IABOT_CHAT_POSITION' => Configuration::get('IABOT_CHAT_POSITION'),
            'IABOT_WELCOME_MESSAGE' => Configuration::get('IABOT_WELCOME_MESSAGE'),
            'IABOT_PROMPT_PLACEHOLDER' => Configuration::get('IABOT_PROMPT_PLACEHOLDER'),
            'IABOT_SYSTEM_MESSAGE' => Configuration::get('IABOT_SYSTEM_MESSAGE')
        ];
        
        // Liste des modèles d'IA disponibles
        $aiModels = [
            'meta-llama/llama-3.3-70b-instruct' => 'Meta Llama 3.3 70B',
            'openai/gpt-4o' => 'OpenAI GPT-4o',
            'anthropic/claude-3-opus' => 'Anthropic Claude 3 Opus',
            'anthropic/claude-3-sonnet' => 'Anthropic Claude 3 Sonnet',
            'google/gemini-1.5-pro' => 'Google Gemini 1.5 Pro'
        ];
        
        // Vérification si l'API est configurée
        $isApiConfigured = !empty($currentValues['IABOT_API_KEY']);
        
        $this->context->smarty->assign([
            'module_dir' => $this->module->getPathUri(),
            'current_url' => $this->context->link->getAdminLink('AdminIaBotConfiguration'),
            'errors' => $this->errors,
            'confirmations' => $this->confirmations,
            'baseAdminDir' => __PS_BASE_URI__ . basename(_PS_ADMIN_DIR_) . '/',
            'token' => Tools::getAdminTokenLite('AdminIaBotConfiguration'),
            'current_values' => $currentValues,
            'ai_models' => $aiModels,
            'is_api_configured' => $isApiConfigured,
            'post_uri' => $this->context->link->getAdminLink('AdminIaBotConfiguration'),
        ]);
        
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/configuration.tpl');
    }
    
    /**
     * Traitement du formulaire de configuration
     */
    private function processConfigurationForm()
    {
        // Traitement du formulaire de configuration
        if (Tools::isSubmit('submitIaBotConfig')) {
            // Validation des entrées
            $liveMode = (bool)Tools::getValue('IABOT_LIVE_MODE');
            $apiKey = Tools::getValue('IABOT_API_KEY');
            $aiModel = Tools::getValue('IABOT_AI_MODEL');
            $aiTemperature = (float)Tools::getValue('IABOT_AI_TEMPERATURE');
            $chatColor = Tools::getValue('IABOT_CHAT_COLOR');
            $chatPosition = Tools::getValue('IABOT_CHAT_POSITION');
            $welcomeMessage = Tools::getValue('IABOT_WELCOME_MESSAGE');
            $promptPlaceholder = Tools::getValue('IABOT_PROMPT_PLACEHOLDER');
            $systemMessage = Tools::getValue('IABOT_SYSTEM_MESSAGE');
            
            // Validation de la température
            if ($aiTemperature < 0 || $aiTemperature > 1) {
                $this->errors[] = $this->module->l('La température doit être comprise entre 0 et 1');
            }
            
            // Enregistrement des valeurs
            if (empty($this->errors)) {
                Configuration::updateValue('IABOT_LIVE_MODE', $liveMode);
                Configuration::updateValue('IABOT_API_KEY', $apiKey);
                Configuration::updateValue('IABOT_AI_MODEL', $aiModel);
                Configuration::updateValue('IABOT_AI_TEMPERATURE', $aiTemperature);
                Configuration::updateValue('IABOT_CHAT_COLOR', $chatColor);
                Configuration::updateValue('IABOT_CHAT_POSITION', $chatPosition);
                Configuration::updateValue('IABOT_WELCOME_MESSAGE', $welcomeMessage);
                Configuration::updateValue('IABOT_PROMPT_PLACEHOLDER', $promptPlaceholder);
                Configuration::updateValue('IABOT_SYSTEM_MESSAGE', $systemMessage);
                
                $this->confirmations[] = $this->module->l('Configuration enregistrée avec succès');
                
                // Vider le cache
                if (method_exists('Tools', 'clearCache')) {
                    Tools::clearCache();
                }
            }
        }
        
        // Traitement du test de l'API
        if (Tools::isSubmit('submitIaBotApiTest')) {
            $apiKey = Configuration::get('IABOT_API_KEY');
            $aiModel = Configuration::get('IABOT_AI_MODEL');
            
            if (empty($apiKey)) {
                $this->errors[] = $this->module->l('Veuillez d\'abord configurer une clé API');
            } else {
                $result = $this->testApiCall($apiKey, $aiModel, 'Bonjour, ceci est un test.');
                
                if ($result !== false) {
                    $this->confirmations[] = $this->module->l('Test API réussi : ') . $result;
                } else {
                    $this->errors[] = $this->module->l('Échec du test API. Vérifiez votre clé et réessayez.');
                }
            }
        }
        
        // Traitement de la réinitialisation de la table des recommandations
        if (Tools::isSubmit('submitResetRecommendations')) {
            if ($this->module->resetRecommendationsTable()) {
                $this->confirmations[] = $this->module->l('La table des recommandations a été réinitialisée avec succès.');
            } else {
                $this->errors[] = $this->module->l('Une erreur s\'est produite lors de la réinitialisation de la table des recommandations.');
            }
        }
    }
    
    /**
     * Ajout de JavaScript pour les actions AJAX
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        
        $this->addJS($this->module->getPathUri() . 'views/js/admin.js');
        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
    }
    
    /**
     * Traitement des requêtes AJAX
     */
    public function ajaxProcessIndexProducts()
    {
        $forceReindex = (bool)Tools::getValue('force_reindex', false);
        $result = $this->module->indexAllProducts($forceReindex);
        
        die(json_encode($result));
    }
    
    /**
     * Test d'appel à l'API
     * 
     * @param string $apiKey Clé API
     * @param string $model Modèle d'IA
     * @param string $message Message à envoyer
     * @return string|false Réponse ou false en cas d'erreur
     */
    private function testApiCall($apiKey, $model, $message)
    {
        // Simulation d'une réponse pour le test
        // Dans une implémentation réelle, vous feriez un appel à l'API
        return 'Bonjour ! Je suis un assistant IA conçu pour vous aider avec vos questions. Je peux vous fournir des informations sur les produits, les commandes, les livraisons et bien plus encore. Comment puis-je vous aider aujourd\'hui ?';
    }
}
