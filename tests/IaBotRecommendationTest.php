<?php
/**
 * Tests unitaires pour la classe IaBotRecommendation
 * 
 * @author  Développeur
 * @copyright 2025
 */

// Inclusion des fichiers nécessaires pour les tests
require_once dirname(__FILE__) . '/../classes/IaBotRecommendation.php';
require_once dirname(__FILE__) . '/../classes/IaBotLogger.php';
require_once dirname(__FILE__) . '/../classes/IaBotException.php';

/**
 * Classe de tests unitaires pour IaBotRecommendation
 */
class IaBotRecommendationTest extends PHPUnit\Framework\TestCase
{
    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialisation du logger en mode test (pas de fichier de log)
        IaBotLogger::init(false);
        
        // Réinitialisation du cache de recommandations
        IaBotRecommendation::clearRecommendationsCache();
    }
    
    /**
     * Test de la validation d'un mot-clé
     */
    public function testValidateKeyword()
    {
        // Cas valides
        $this->assertTrue(IaBotRecommendation::validateKeyword('smartphone'));
        $this->assertTrue(IaBotRecommendation::validateKeyword('ordinateur portable'));
        $this->assertTrue(IaBotRecommendation::validateKeyword('accessoires'));
        
        // Cas invalides
        $this->assertFalse(IaBotRecommendation::validateKeyword(''));
        $this->assertFalse(IaBotRecommendation::validateKeyword('   '));
        $this->assertFalse(IaBotRecommendation::validateKeyword('a')); // Trop court
        
        // Cas avec caractères spéciaux
        $this->assertTrue(IaBotRecommendation::validateKeyword('caméra-sport'));
        $this->assertTrue(IaBotRecommendation::validateKeyword('vélo d\'occasion'));
    }
    
    /**
     * Test du nettoyage d'un mot-clé
     */
    public function testCleanKeyword()
    {
        // Test de nettoyage des espaces
        $this->assertEquals('smartphone', IaBotRecommendation::cleanKeyword(' smartphone '));
        
        // Test de conversion en minuscules
        $this->assertEquals('smartphone', IaBotRecommendation::cleanKeyword('SMARTPHONE'));
        
        // Test de nettoyage des caractères spéciaux
        $this->assertEquals('planche a voile', IaBotRecommendation::cleanKeyword('planche à voile!'));
        
        // Test de nettoyage des scripts
        $this->assertEquals('test', IaBotRecommendation::cleanKeyword('<script>alert("XSS");</script>test'));
    }
    
    /**
     * Test de la récupération des recommandations avec cache
     */
    public function testGetRecommendationsByKeywordCached()
    {
        // Création d'une méthode mock pour simuler getRecommendationsByKeyword
        $mockRecommendation = $this->getMockBuilder(IaBotRecommendation::class)
            ->setMethods(['getRecommendationsByKeyword'])
            ->getMock();
            
        $mockRecommendation->expects($this->once())
            ->method('getRecommendationsByKeyword')
            ->willReturn([
                ['id_product' => 1, 'name' => 'Produit de test'],
                ['id_product' => 2, 'name' => 'Produit de test 2']
            ]);
            
        // Premier appel - devrait appeler getRecommendationsByKeyword
        $recommendations = $mockRecommendation->getRecommendationsByKeywordCached('smartphone', 2, true);
        
        // Vérification du résultat
        $this->assertCount(2, $recommendations);
        $this->assertEquals(1, $recommendations[0]['id_product']);
        $this->assertEquals(2, $recommendations[1]['id_product']);
        
        // Deuxième appel avec le même mot-clé - devrait utiliser le cache
        $recommendations = $mockRecommendation->getRecommendationsByKeywordCached('smartphone', 2, true);
        
        // Vérification du résultat (même si getRecommendationsByKeyword n'est pas appelé)
        $this->assertCount(2, $recommendations);
    }
    
    /**
     * Test de la gestion des erreurs dans getRecommendationsByKeywordCached
     */
    public function testGetRecommendationsByKeywordCachedErrorHandling()
    {
        // Test avec un mot-clé invalide
        $recommendations = IaBotRecommendation::getRecommendationsByKeywordCached('');
        $this->assertEmpty($recommendations);
        
        // Test avec une exception
        $mockRecommendation = $this->getMockBuilder(IaBotRecommendation::class)
            ->setMethods(['getRecommendationsByKeyword'])
            ->getMock();
            
        $mockRecommendation->expects($this->once())
            ->method('getRecommendationsByKeyword')
            ->will($this->throwException(new Exception('Test exception')));
            
        $this->expectException(IaBotException::class);
        $mockRecommendation->getRecommendationsByKeywordCached('smartphone', 2, true);
    }
    
    /**
     * Test de la méthode clearCache
     */
    public function testClearCache()
    {
        // Création d'une méthode mock pour simuler getRecommendationsByKeyword
        $mockRecommendation = $this->getMockBuilder(IaBotRecommendation::class)
            ->setMethods(['getRecommendationsByKeyword'])
            ->getMock();
            
        $mockRecommendation->expects($this->exactly(2))
            ->method('getRecommendationsByKeyword')
            ->willReturn([
                ['id_product' => 1, 'name' => 'Produit de test']
            ]);
            
        // Premier appel - devrait appeler getRecommendationsByKeyword
        $mockRecommendation->getRecommendationsByKeywordCached('smartphone', 1, true);
        
        // Nettoyage du cache
        IaBotRecommendation::clearRecommendationsCache();
        
        // Deuxième appel - devrait appeler getRecommendationsByKeyword à nouveau
        $mockRecommendation->getRecommendationsByKeywordCached('smartphone', 1, true);
    }
    
    /**
     * Test de la méthode formatRecommendationForDisplay
     */
    public function testFormatRecommendationForDisplay()
    {
        // Création d'un produit de test
        $product = [
            'id_product' => 1,
            'name' => 'Produit de test',
            'description_short' => 'Description courte du produit',
            'price' => 499.99,
            'reference' => 'REF-001',
            'quantity' => 10
        ];
        
        // Formatage du produit
        $formatted = IaBotRecommendation::formatRecommendationForDisplay($product);
        
        // Vérifications
        $this->assertArrayHasKey('id', $formatted);
        $this->assertArrayHasKey('name', $formatted);
        $this->assertArrayHasKey('description', $formatted);
        $this->assertArrayHasKey('price', $formatted);
        $this->assertArrayHasKey('image', $formatted);
        $this->assertArrayHasKey('url', $formatted);
        
        $this->assertEquals(1, $formatted['id']);
        $this->assertEquals('Produit de test', $formatted['name']);
    }
}
