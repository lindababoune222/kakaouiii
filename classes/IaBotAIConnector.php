<?php
/**
 * Classe de connexion aux API d'intelligence artificielle
 * 
 * @author  Développeur
 * @copyright 2025
 */

// Inclusion du fichier d'aide IDE pour la complétion
if (defined('_PS_DEV_MODE_') && _PS_DEV_MODE_) {
    require_once dirname(__FILE__) . '/../inc/prestashop-ide-helper.php';
}

require_once dirname(__FILE__) . '/IaBotLogger.php';
require_once dirname(__FILE__) . '/IaBotException.php';

/**
 * Classe de connexion aux API d'intelligence artificielle
 */
class IaBotAIConnector
{
    /**
     * URL de l'API OpenRouter
     */
    const OPENROUTER_API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    
    /**
     * Modèle par défaut (Meta Llama 3.3 70B)
     */
    const DEFAULT_MODEL = 'meta-llama/llama-3.3-70b-instruct:free';
    
    /**
     * Clé API stockée en configuration
     */
    const CONFIG_API_KEY = 'IABOT_OPENROUTER_API_KEY';
    
    /**
     * Modèle stocké en configuration
     */
    const CONFIG_MODEL = 'IABOT_AI_MODEL';
    
    /**
     * Température stockée en configuration (contrôle la créativité)
     */
    const CONFIG_TEMPERATURE = 'IABOT_AI_TEMPERATURE';
    
    /**
     * Cache des contextes de conversation
     */
    private static $conversationContexts = [];
    
    /**
     * Envoie une requête à l'API d'IA et récupère la réponse
     * 
     * @param string $prompt Message de l'utilisateur
     * @param int $idConversation ID de la conversation pour maintenir le contexte
     * @param array $options Options supplémentaires (modèle, température, etc.)
     * @return string Réponse générée par l'IA
     * @throws IaBotException En cas d'erreur
     */
    public static function getAIResponse($prompt, $idConversation = null, array $options = [])
    {
        try {
            // Récupération de la clé API
            $apiKey = Configuration::get(self::CONFIG_API_KEY);
            
            if (empty($apiKey)) {
                throw IaBotException::configurationError('Clé API OpenRouter non configurée');
            }
            
            // Récupération du modèle (ou utilisation du modèle par défaut)
            $model = !empty($options['model']) ? $options['model'] : 
                     Configuration::get(self::CONFIG_MODEL, self::DEFAULT_MODEL);
            
            // Récupération de la température (ou utilisation de la valeur par défaut)
            $temperature = isset($options['temperature']) ? (float)$options['temperature'] : 
                          (float)Configuration::get(self::CONFIG_TEMPERATURE, 0.7);
            
            // Préparation des messages pour l'API
            $messages = self::prepareMessages($prompt, $idConversation);
            
            // Préparation des données pour la requête
            $data = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => isset($options['max_tokens']) ? (int)$options['max_tokens'] : 500
            ];
            
            // Préparation des en-têtes HTTP
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ];
            
            // Initialisation de cURL
            $ch = curl_init(self::OPENROUTER_API_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            // Exécution de la requête
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Vérification des erreurs cURL
            if ($error) {
                throw IaBotException::apiError('Erreur cURL: ' . $error);
            }
            
            // Vérification du code HTTP
            if ($httpCode !== 200) {
                throw IaBotException::apiError('Erreur HTTP: ' . $httpCode . ' - ' . $response);
            }
            
            // Décodage de la réponse JSON
            $responseData = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw IaBotException::apiError('Erreur de décodage JSON: ' . json_last_error_msg());
            }
            
            // Vérification de la structure de la réponse
            if (!isset($responseData['choices'][0]['message']['content'])) {
                throw IaBotException::apiError('Format de réponse invalide');
            }
            
            // Extraction du contenu de la réponse
            $aiResponse = $responseData['choices'][0]['message']['content'];
            
            // Mise à jour du contexte de conversation
            if ($idConversation) {
                self::updateConversationContext($idConversation, $prompt, $aiResponse);
            }
            
            // Journalisation du succès
            IaBotLogger::info('Réponse IA générée avec succès', [
                'model' => $model,
                'id_conversation' => $idConversation,
                'prompt_length' => strlen($prompt),
                'response_length' => strlen($aiResponse)
            ]);
            
            return $aiResponse;
        } catch (IaBotException $e) {
            // L'exception a déjà été journalisée
            throw $e;
        } catch (Exception $e) {
            // Journalisation et conversion de l'exception
            $exception = new IaBotException(
                'Erreur lors de la génération de la réponse IA: ' . $e->getMessage(),
                'IABOT_AI_ERROR',
                [
                    'prompt' => substr($prompt, 0, 100) . (strlen($prompt) > 100 ? '...' : ''),
                    'id_conversation' => $idConversation
                ],
                true,
                $e->getCode(),
                $e
            );
            
            throw $exception;
        }
    }
    
    /**
     * Prépare les messages pour l'API en incluant le contexte de la conversation
     * 
     * @param string $prompt Message de l'utilisateur
     * @param int $idConversation ID de la conversation
     * @return array Messages formatés pour l'API
     */
    private static function prepareMessages($prompt, $idConversation = null)
    {
        // Message système pour définir le comportement du chatbot
        $systemMessage = [
            'role' => 'system',
            'content' => 'Tu es un assistant de shopping intelligent pour une boutique en ligne PrestaShop. ' .
                        'Tu dois être poli, serviable et fournir des informations précises sur les produits. ' .
                        'Tu peux recommander des produits en fonction des besoins du client. ' .
                        'Garde tes réponses concises et pertinentes.'
        ];
        
        // Initialisation des messages avec le message système
        $messages = [$systemMessage];
        
        // Ajout du contexte de la conversation si disponible
        if ($idConversation && isset(self::$conversationContexts[$idConversation])) {
            $context = self::$conversationContexts[$idConversation];
            
            // Ajout des messages précédents (limité aux 5 derniers échanges)
            foreach ($context as $exchange) {
                $messages[] = [
                    'role' => 'user',
                    'content' => $exchange['user']
                ];
                
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $exchange['assistant']
                ];
            }
        }
        
        // Ajout du message actuel de l'utilisateur
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];
        
        return $messages;
    }
    
    /**
     * Met à jour le contexte de la conversation
     * 
     * @param int $idConversation ID de la conversation
     * @param string $userMessage Message de l'utilisateur
     * @param string $assistantMessage Réponse de l'assistant
     */
    private static function updateConversationContext($idConversation, $userMessage, $assistantMessage)
    {
        // Initialisation du contexte si nécessaire
        if (!isset(self::$conversationContexts[$idConversation])) {
            self::$conversationContexts[$idConversation] = [];
        }
        
        // Ajout du nouvel échange
        self::$conversationContexts[$idConversation][] = [
            'user' => $userMessage,
            'assistant' => $assistantMessage
        ];
        
        // Limitation à 5 échanges pour éviter une consommation excessive de tokens
        if (count(self::$conversationContexts[$idConversation]) > 5) {
            self::$conversationContexts[$idConversation] = array_slice(
                self::$conversationContexts[$idConversation], 
                -5
            );
        }
    }
    
    /**
     * Efface le contexte d'une conversation
     * 
     * @param int $idConversation ID de la conversation
     */
    public static function clearConversationContext($idConversation)
    {
        if (isset(self::$conversationContexts[$idConversation])) {
            unset(self::$conversationContexts[$idConversation]);
            
            IaBotLogger::info('Contexte de conversation effacé', [
                'id_conversation' => $idConversation
            ]);
        }
    }
    
    /**
     * Crée une exception pour une erreur de configuration
     * 
     * @param string $message Message d'erreur
     * @param array $errorData Données associées à l'erreur
     * @return IaBotException Instance de l'exception
     */
    public static function configurationError($message, array $errorData = [])
    {
        return new IaBotException(
            $message,
            'IABOT_CONFIGURATION_ERROR',
            $errorData,
            true
        );
    }
}
