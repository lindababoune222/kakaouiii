<?php
/**
 * Tests unitaires pour la classe IaBotMessage
 * 
 * @author  Développeur
 * @copyright 2025
 */

// Inclusion des fichiers nécessaires pour les tests
require_once dirname(__FILE__) . '/../classes/IaBotMessage.php';
require_once dirname(__FILE__) . '/../classes/IaBotLogger.php';
require_once dirname(__FILE__) . '/../classes/IaBotException.php';

/**
 * Classe de tests unitaires pour IaBotMessage
 */
class IaBotMessageTest extends PHPUnit\Framework\TestCase
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
     * Test de la validation du contenu d'un message
     */
    public function testValidateContent()
    {
        // Cas valide : contenu normal
        $this->assertTrue(IaBotMessage::validateContent('Ceci est un message de test'));
        
        // Cas valide : contenu avec HTML
        $this->assertTrue(IaBotMessage::validateContent('<p>Ceci est un <strong>message</strong> de test</p>'));
        
        // Cas invalide : contenu vide
        $this->assertFalse(IaBotMessage::validateContent(''));
        
        // Cas invalide : contenu avec seulement des espaces
        $this->assertFalse(IaBotMessage::validateContent('   '));
        
        // Cas invalide : contenu avec seulement des balises HTML sans texte
        $this->assertFalse(IaBotMessage::validateContent('<p></p>'));
        
        // Cas invalide : contenu trop long (plus de 10000 caractères)
        $longContent = str_repeat('a', 10001);
        $this->assertFalse(IaBotMessage::validateContent($longContent));
    }
    
    /**
     * Test de la validation de l'expéditeur d'un message
     */
    public function testValidateSender()
    {
        // Cas valides
        $this->assertTrue(IaBotMessage::validateSender('user'));
        $this->assertTrue(IaBotMessage::validateSender('bot'));
        
        // Cas invalides
        $this->assertFalse(IaBotMessage::validateSender(''));
        $this->assertFalse(IaBotMessage::validateSender('admin'));
        $this->assertFalse(IaBotMessage::validateSender('USER'));
        $this->assertFalse(IaBotMessage::validateSender('BOT'));
    }
    
    /**
     * Test du nettoyage du contenu d'un message
     */
    public function testCleanContent()
    {
        // Test de nettoyage des scripts
        $contentWithScript = '<p>Contenu normal</p><script>alert("XSS");</script>';
        $cleanedContent = IaBotMessage::cleanContent($contentWithScript);
        $this->assertStringNotContainsString('<script>', $cleanedContent);
        
        // Test de nettoyage des iframes
        $contentWithIframe = '<p>Contenu normal</p><iframe src="https://malicious.com"></iframe>';
        $cleanedContent = IaBotMessage::cleanContent($contentWithIframe);
        $this->assertStringNotContainsString('<iframe', $cleanedContent);
        
        // Test de nettoyage des attributs dangereux
        $contentWithOnclick = '<a href="#" onclick="alert(\'XSS\')">Lien</a>';
        $cleanedContent = IaBotMessage::cleanContent($contentWithOnclick);
        $this->assertStringNotContainsString('onclick', $cleanedContent);
    }
    
    /**
     * Test de l'ajout d'un message sécurisé (méthode addMessageSafe)
     */
    public function testAddMessageSafeValidation()
    {
        // Test avec un ID de conversation invalide
        $this->expectException(IaBotException::class);
        $this->expectExceptionMessage('ID de conversation invalide');
        IaBotMessage::addMessageSafe(0, 'Test message', 'user');
        
        // Test avec un contenu invalide
        $this->expectException(IaBotException::class);
        $this->expectExceptionMessage('Contenu du message invalide');
        IaBotMessage::addMessageSafe(1, '', 'user');
        
        // Test avec un expéditeur invalide
        $this->expectException(IaBotException::class);
        $this->expectExceptionMessage('Expéditeur invalide');
        IaBotMessage::addMessageSafe(1, 'Test message', 'invalid');
    }
    
    /**
     * Test de la méthode getDefaultResponse
     */
    public function testGetDefaultResponse()
    {
        $response = IaBotMessage::getDefaultResponse();
        
        // Vérification que la réponse n'est pas vide
        $this->assertNotEmpty($response);
        
        // Vérification que la réponse est une chaîne de caractères
        $this->assertIsString($response);
    }
    
    /**
     * Test de la méthode analyzeMessage
     */
    public function testAnalyzeMessage()
    {
        // Test avec un message sur le windsurf
        $keywords = IaBotMessage::analyzeMessage('Je cherche une planche de windsurf pour débutant');
        $this->assertContains('planche', $keywords);
        $this->assertContains('windsurf', $keywords);
        $this->assertContains('débutant', $keywords);
        
        // Test avec un message vide
        $keywords = IaBotMessage::analyzeMessage('');
        $this->assertEmpty($keywords);
        
        // Test avec un message sans mots-clés pertinents
        $keywords = IaBotMessage::analyzeMessage('Bonjour, comment allez-vous ?');
        $this->assertEmpty($keywords);
    }
}
