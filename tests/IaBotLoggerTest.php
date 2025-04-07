<?php
/**
 * Tests unitaires pour la classe IaBotLogger
 * 
 * @author  Développeur
 * @copyright 2025
 */

// Inclusion des fichiers nécessaires pour les tests
require_once dirname(__FILE__) . '/../classes/IaBotLogger.php';

/**
 * Classe de tests unitaires pour IaBotLogger
 */
class IaBotLoggerTest extends PHPUnit\Framework\TestCase
{
    /**
     * Chemin vers le fichier de log de test
     */
    private $testLogFile;
    
    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Création d'un fichier de log temporaire pour les tests
        $this->testLogFile = sys_get_temp_dir() . '/iabot_test_' . uniqid() . '.log';
        
        // Initialisation du logger avec le fichier de test
        IaBotLogger::init(true, $this->testLogFile);
    }
    
    /**
     * Nettoyage après chaque test
     */
    protected function tearDown(): void
    {
        // Suppression du fichier de log de test s'il existe
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
        
        parent::tearDown();
    }
    
    /**
     * Test de la méthode d'initialisation
     */
    public function testInit()
    {
        // Réinitialisation avec un nouveau fichier
        $newLogFile = sys_get_temp_dir() . '/iabot_test_new_' . uniqid() . '.log';
        IaBotLogger::init(true, $newLogFile);
        
        // Vérification que le logger est bien initialisé
        IaBotLogger::info('Test init');
        
        // Vérification que le fichier a été créé
        $this->assertFileExists($newLogFile);
        
        // Nettoyage
        if (file_exists($newLogFile)) {
            unlink($newLogFile);
        }
    }
    
    /**
     * Test de la méthode error
     */
    public function testError()
    {
        IaBotLogger::error('Message d\'erreur test', ['source' => 'test']);
        
        // Vérification que le message a été écrit dans le fichier
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('[ERROR]', $logContent);
        $this->assertStringContainsString('Message d\'erreur test', $logContent);
        $this->assertStringContainsString('"source":"test"', $logContent);
    }
    
    /**
     * Test de la méthode warning
     */
    public function testWarning()
    {
        IaBotLogger::warning('Message d\'avertissement test');
        
        // Vérification que le message a été écrit dans le fichier
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('[WARNING]', $logContent);
        $this->assertStringContainsString('Message d\'avertissement test', $logContent);
    }
    
    /**
     * Test de la méthode info
     */
    public function testInfo()
    {
        IaBotLogger::info('Message d\'information test');
        
        // Vérification que le message a été écrit dans le fichier
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('[INFO]', $logContent);
        $this->assertStringContainsString('Message d\'information test', $logContent);
    }
    
    /**
     * Test de la méthode debug
     */
    public function testDebug()
    {
        IaBotLogger::debug('Message de debug test');
        
        // Vérification que le message a été écrit dans le fichier
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('[DEBUG]', $logContent);
        $this->assertStringContainsString('Message de debug test', $logContent);
    }
    
    /**
     * Test de la méthode log
     */
    public function testLog()
    {
        // Test avec différents niveaux de log
        IaBotLogger::log(1, 'Message niveau 1');
        IaBotLogger::log(2, 'Message niveau 2');
        IaBotLogger::log(3, 'Message niveau 3');
        IaBotLogger::log(4, 'Message niveau 4');
        
        // Vérification que les messages ont été écrits dans le fichier
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Message niveau 1', $logContent);
        $this->assertStringContainsString('Message niveau 2', $logContent);
        $this->assertStringContainsString('Message niveau 3', $logContent);
        $this->assertStringContainsString('Message niveau 4', $logContent);
    }
    
    /**
     * Test de la méthode getLogLevelName
     */
    public function testGetLogLevelName()
    {
        // Test de la méthode privée via réflexion
        $reflectionClass = new ReflectionClass('IaBotLogger');
        $method = $reflectionClass->getMethod('getLogLevelName');
        $method->setAccessible(true);
        
        $this->assertEquals('ERROR', $method->invoke(null, 1));
        $this->assertEquals('WARNING', $method->invoke(null, 2));
        $this->assertEquals('INFO', $method->invoke(null, 3));
        $this->assertEquals('DEBUG', $method->invoke(null, 4));
        $this->assertEquals('UNKNOWN', $method->invoke(null, 5));
    }
    
    /**
     * Test du comportement lorsque la journalisation est désactivée
     */
    public function testDisabledLogging()
    {
        // Désactivation de la journalisation
        IaBotLogger::init(false);
        
        // Tentative d'écriture d'un log
        IaBotLogger::info('Ce message ne devrait pas être journalisé');
        
        // Vérification que le fichier n'a pas été modifié ou créé
        if (file_exists($this->testLogFile)) {
            $logContent = file_get_contents($this->testLogFile);
            $this->assertStringNotContainsString('Ce message ne devrait pas être journalisé', $logContent);
        } else {
            // Si le fichier n'existe pas, le test passe aussi
            $this->assertTrue(true);
        }
    }
    
    /**
     * Test de la rotation des logs
     */
    public function testLogRotation()
    {
        // Création d'un petit fichier de log pour tester la rotation
        $smallLogFile = sys_get_temp_dir() . '/iabot_small_' . uniqid() . '.log';
        file_put_contents($smallLogFile, str_repeat('a', 1024 * 1024 * 2)); // 2 MB
        
        // Initialisation du logger avec le petit fichier
        IaBotLogger::init(true, $smallLogFile);
        
        // Écriture d'un message qui devrait déclencher la rotation
        IaBotLogger::info('Message après rotation');
        
        // Vérification que le fichier original a été renommé
        $rotatedFile = $smallLogFile . '.1';
        $this->assertFileExists($rotatedFile);
        
        // Vérification que le nouveau fichier contient le message
        $logContent = file_get_contents($smallLogFile);
        $this->assertStringContainsString('Message après rotation', $logContent);
        
        // Nettoyage
        if (file_exists($smallLogFile)) {
            unlink($smallLogFile);
        }
        if (file_exists($rotatedFile)) {
            unlink($rotatedFile);
        }
    }
}
