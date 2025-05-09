<?php
/**
 * Tests unitaires pour la classe IaBotException
 * 
 * @author  Développeur
 * @copyright 2025
 */

// Inclusion des fichiers nécessaires pour les tests
require_once dirname(__FILE__) . '/../classes/IaBotException.php';
require_once dirname(__FILE__) . '/../classes/IaBotLogger.php';

/**
 * Classe de tests unitaires pour IaBotException
 */
class IaBotExceptionTest extends PHPUnit\Framework\TestCase
{
    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialisation du logger en mode test (pas de fichier de log)
        IaBotLogger::init(false);
    }
    
    /**
     * Test du constructeur de l'exception
     */
    public function testConstructor()
    {
        $exception = new IaBotException(
            'Message d\'erreur test',
            'TEST_ERROR',
            ['param' => 'value'],
            false,
            123
        );
        
        $this->assertEquals('Message d\'erreur test', $exception->getMessage());
        $this->assertEquals('TEST_ERROR', $exception->getErrorCode());
        $this->assertEquals(['param' => 'value'], $exception->getErrorData());
        $this->assertEquals(123, $exception->getCode());
    }
    
    /**
     * Test de la méthode statique validationError
     */
    public function testValidationError()
    {
        $exception = IaBotException::validationError(
            'Erreur de validation',
            ['field' => 'name', 'value' => '']
        );
        
        $this->assertEquals('Erreur de validation', $exception->getMessage());
        $this->assertEquals('IABOT_VALIDATION_ERROR', $exception->getErrorCode());
        $this->assertEquals(['field' => 'name', 'value' => ''], $exception->getErrorData());
    }
    
    /**
     * Test de la méthode statique databaseError
     */
    public function testDatabaseError()
    {
        $exception = IaBotException::databaseError(
            'Erreur de base de données',
            ['query' => 'SELECT * FROM table']
        );
        
        $this->assertEquals('Erreur de base de données', $exception->getMessage());
        $this->assertEquals('IABOT_DATABASE_ERROR', $exception->getErrorCode());
        $this->assertEquals(['query' => 'SELECT * FROM table'], $exception->getErrorData());
    }
    
    /**
     * Test de la méthode statique apiError
     */
    public function testApiError()
    {
        $exception = IaBotException::apiError(
            'Erreur d\'API',
            ['endpoint' => '/api/products', 'status' => 404]
        );
        
        $this->assertEquals('Erreur d\'API', $exception->getMessage());
        $this->assertEquals('IABOT_API_ERROR', $exception->getErrorCode());
        $this->assertEquals(['endpoint' => '/api/products', 'status' => 404], $exception->getErrorData());
    }
    
    /**
     * Test de la méthode getErrorCode
     */
    public function testGetErrorCode()
    {
        $exception = new IaBotException('Message', 'CUSTOM_ERROR');
        $this->assertEquals('CUSTOM_ERROR', $exception->getErrorCode());
    }
    
    /**
     * Test de la méthode getErrorData
     */
    public function testGetErrorData()
    {
        $errorData = ['key1' => 'value1', 'key2' => 'value2'];
        $exception = new IaBotException('Message', 'ERROR', $errorData);
        $this->assertEquals($errorData, $exception->getErrorData());
    }
    
    /**
     * Test de la méthode toArray
     */
    public function testToArray()
    {
        $exception = new IaBotException(
            'Message d\'erreur',
            'ERROR_CODE',
            ['param' => 'value'],
            false,
            123
        );
        
        $array = $exception->toArray();
        
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('error_code', $array);
        $this->assertArrayHasKey('error_data', $array);
        
        $this->assertEquals('Message d\'erreur', $array['message']);
        $this->assertEquals(123, $array['code']);
        $this->assertEquals('ERROR_CODE', $array['error_code']);
        $this->assertEquals(['param' => 'value'], $array['error_data']);
    }
    
    /**
     * Test de la méthode toJson
     */
    public function testToJson()
    {
        $exception = new IaBotException(
            'Message d\'erreur',
            'ERROR_CODE',
            ['param' => 'value'],
            false,
            123
        );
        
        $json = $exception->toJson();
        $decoded = json_decode($json, true);
        
        $this->assertNotFalse($decoded);
        $this->assertEquals('Message d\'erreur', $decoded['message']);
        $this->assertEquals('ERROR_CODE', $decoded['error_code']);
    }
}
