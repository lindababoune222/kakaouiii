<?php
/**
 * Classe de gestion des exceptions pour le module IA Bot
 * 
 * @author  Développeur
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class IaBotException extends Exception
{
    /** @var string Code d'erreur */
    protected $errorCode;
    
    /** @var array Données contextuelles de l'erreur */
    protected $errorData;
    
    /** @var bool Indique si l'erreur doit être journalisée */
    protected $shouldLog;
    
    /**
     * Constructeur
     * 
     * @param string $message Message d'erreur
     * @param string $errorCode Code d'erreur
     * @param array $errorData Données contextuelles de l'erreur
     * @param bool $shouldLog Indique si l'erreur doit être journalisée
     * @param int $code Code d'exception PHP
     * @param Exception|null $previous Exception précédente
     */
    public function __construct(
        $message = "",
        $errorCode = "IABOT_ERROR",
        array $errorData = [],
        $shouldLog = true,
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->errorData = $errorData;
        $this->shouldLog = $shouldLog;
        
        // Journalisation automatique si nécessaire
        if ($shouldLog) {
            $this->logException();
        }
    }
    
    /**
     * Récupère le code d'erreur
     * 
     * @return string Code d'erreur
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
    
    /**
     * Récupère les données contextuelles de l'erreur
     * 
     * @return array Données contextuelles
     */
    public function getErrorData()
    {
        return $this->errorData;
    }
    
    /**
     * Journalise l'exception
     * 
     * @return void
     */
    protected function logException()
    {
        if (class_exists('IaBotLogger')) {
            $context = [
                'error_code' => $this->errorCode,
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => $this->getTraceAsString()
            ];
            
            if (!empty($this->errorData)) {
                $context['data'] = $this->errorData;
            }
            
            IaBotLogger::error($this->getMessage(), $context);
        }
    }
    
    /**
     * Crée une exception pour une erreur de validation
     * 
     * @param string $message Message d'erreur
     * @param array $errorData Données associées à l'erreur
     * @return IaBotException Instance de l'exception
     */
    public static function validationError($message, array $errorData = [])
    {
        return new self(
            $message,
            'IABOT_VALIDATION_ERROR',
            $errorData,
            true
        );
    }
    
    /**
     * Crée une exception pour une erreur de type "non trouvé"
     * 
     * @param string $message Message d'erreur
     * @param array $errorData Données associées à l'erreur
     * @return IaBotException Instance de l'exception
     */
    public static function notFoundError($message, array $errorData = [])
    {
        return new self(
            $message,
            'IABOT_NOT_FOUND_ERROR',
            $errorData,
            true
        );
    }
    
    /**
     * Crée une exception pour une erreur de base de données
     * 
     * @param string $message Message d'erreur
     * @param array $errorData Données associées à l'erreur
     * @return IaBotException Instance de l'exception
     */
    public static function databaseError($message, array $errorData = [])
    {
        $data = $errorData;
        
        return new self(
            $message,
            'IABOT_DATABASE_ERROR',
            $data,
            true
        );
    }
    
    /**
     * Crée une exception pour une ressource non trouvée
     * 
     * @param string $resourceType Type de ressource
     * @param mixed $resourceId Identifiant de la ressource
     * @return IaBotException Instance de l'exception
     */
    public static function resourceNotFound($resourceType, $resourceId)
    {
        return new self(
            sprintf('La ressource %s avec l\'identifiant %s n\'a pas été trouvée', $resourceType, $resourceId),
            'IABOT_RESOURCE_NOT_FOUND',
            [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId
            ],
            true
        );
    }
    
    /**
     * Crée une exception pour une erreur d'API
     * 
     * @param string $message Message d'erreur
     * @param array $errorData Données associées à l'erreur
     * @return IaBotException Instance de l'exception
     */
    public static function apiError($message, array $errorData = [])
    {
        return new self(
            $message,
            'IABOT_API_ERROR',
            $errorData,
            true
        );
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
        return new self(
            $message,
            'IABOT_CONFIGURATION_ERROR',
            $errorData,
            true
        );
    }
}
