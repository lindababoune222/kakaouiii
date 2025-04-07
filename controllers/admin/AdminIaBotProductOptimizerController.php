<?php
/**
 * Contrôleur d'administration pour l'optimisation des produits du module IaBot
 * 
 * @author  Mike
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Inclusion des classes nécessaires
require_once _PS_MODULE_DIR_ . 'iabot/iabot.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotAIConnector.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotProductIndex.php';

/**
 * Contrôleur d'administration pour l'optimisation des produits du module IaBot
 */
class AdminIaBotProductOptimizerController extends ModuleAdminController
{
    /**
     * Constructeur du contrôleur
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        $this->meta_title = 'Optimisation des produits IaBot';
        
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
        
        // Préparation du contenu avant l'appel au parent
        $this->content = $this->renderProductOptimizerPage();
        
        parent::initContent();
    }
    
    /**
     * Traitement des requêtes AJAX
     */
    protected function processAjaxRequest()
    {
        header('Content-Type: application/json');
        
        $action = Tools::getValue('action');
        
        if ($action === 'optimize_products') {
            $productIds = Tools::getValue('product_ids');
            
            if (!$productIds) {
                die(json_encode([
                    'success' => false,
                    'message' => $this->module->l('Aucun produit sélectionné.')
                ]));
            }
            
            try {
                // Décodage des IDs de produits
                $productIds = json_decode($productIds, true);
                
                if (!is_array($productIds) || empty($productIds)) {
                    die(json_encode([
                        'success' => false,
                        'message' => $this->module->l('Format de données invalide.')
                    ]));
                }
                
                // Récupération des options d'optimisation
                $shortDescriptionLines = (int)Tools::getValue('short_description_lines', 4);
                $longDescriptionLines = (int)Tools::getValue('long_description_lines', 15);
                $seoLevel = (int)Tools::getValue('seo_level', 7);
                $additionalKeywords = Tools::getValue('additional_keywords', '');
                
                // Optimisation des produits
                $result = $this->optimizeProducts($productIds, [
                    'short_description_lines' => $shortDescriptionLines,
                    'long_description_lines' => $longDescriptionLines,
                    'seo_level' => $seoLevel,
                    'additional_keywords' => $additionalKeywords
                ]);
                
                die(json_encode([
                    'success' => true,
                    'message' => sprintf(
                        $this->module->l('%d produits ont été améliorés avec succès.'),
                        count($result['optimized_products'])
                    ),
                    'optimized_products' => $result['optimized_products']
                ]));
            } catch (Exception $e) {
                die(json_encode([
                    'success' => false,
                    'message' => $this->module->l('Erreur lors de l\'optimisation des produits:') . ' ' . $e->getMessage()
                ]));
            }
        }
        
        die(json_encode([
            'success' => false,
            'message' => $this->module->l('Action non reconnue.')
        ]));
    }
    
    /**
     * Rendu de la page d'optimisation des produits
     */
    protected function renderProductOptimizerPage()
    {
        // Récupération des produits
        $products = $this->getProductsForOptimization();
        
        // Assignation des variables au template
        $this->context->smarty->assign([
            'module_dir' => $this->module->getPathUri(),
            'current_url' => $this->context->link->getAdminLink('AdminIaBotProductOptimizer'),
            'ajax_url' => $this->context->link->getAdminLink('AdminIaBotProductOptimizer', true, [], ['ajax' => 1]),
            'products' => $products,
            'confirmation_message' => Tools::getValue('confirmation') ? $this->module->l('Les produits ont été améliorés avec succès.') : null,
            'error_message' => Tools::getValue('error') ? $this->module->l('Une erreur est survenue lors de l\'amélioration des produits.') : null
        ]);
        
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/product_optimizer.tpl');
    }
    
    /**
     * Récupère la liste des produits pour l'optimisation
     * 
     * @return array Liste des produits
     */
    protected function getProductsForOptimization()
    {
        $products = Product::getProducts($this->context->language->id, 0, 50, 'id_product', 'DESC', false, true);
        $result = [];
        
        foreach ($products as $product) {
            $productObj = new Product($product['id_product'], true, $this->context->language->id);
            
            if (!Validate::isLoadedObject($productObj)) {
                continue;
            }
            
            // Calcul du score SEO
            $seoScore = $this->calculateSeoScore($productObj);
            
            // Récupération de la catégorie par défaut
            $category = new Category($productObj->id_category_default, $this->context->language->id);
            $categoryName = Validate::isLoadedObject($category) ? $category->name : '';
            
            // Récupération de l'image
            $image = Image::getCover($productObj->id);
            $imagePath = null;
            
            if ($image) {
                $imagePath = $this->context->link->getImageLink(
                    $productObj->link_rewrite,
                    $image['id_image'],
                    ImageType::getFormattedName('small')
                );
            }
            
            $result[] = [
                'id_product' => $productObj->id,
                'name' => $productObj->name,
                'reference' => $productObj->reference,
                'category_name' => $categoryName,
                'price' => Tools::displayPrice($productObj->getPrice()),
                'seo_score' => $seoScore,
                'image' => $imagePath,
                'edit_url' => $this->context->link->getAdminLink('AdminProducts', true, ['id_product' => $productObj->id, 'updateproduct' => 1])
            ];
        }
        
        return $result;
    }
    
    /**
     * Calcule le score SEO d'un produit
     * 
     * @param Product $product Produit
     * @return int Score SEO (0-100)
     */
    protected function calculateSeoScore($product)
    {
        $score = 0;
        $maxScore = 0;
        
        // Vérification du nom du produit
        if (!empty($product->name)) {
            $nameLength = Tools::strlen($product->name);
            if ($nameLength >= 5 && $nameLength <= 70) {
                $score += 15;
            } elseif ($nameLength > 0) {
                $score += 5;
            }
            $maxScore += 15;
        }
        
        // Vérification de la description courte
        if (!empty($product->description_short)) {
            $shortDescLength = Tools::strlen(strip_tags($product->description_short));
            if ($shortDescLength >= 50 && $shortDescLength <= 300) {
                $score += 20;
            } elseif ($shortDescLength > 0) {
                $score += 10;
            }
            $maxScore += 20;
        }
        
        // Vérification de la description longue
        if (!empty($product->description)) {
            $descLength = Tools::strlen(strip_tags($product->description));
            if ($descLength >= 300) {
                $score += 25;
            } elseif ($descLength >= 100) {
                $score += 15;
            } elseif ($descLength > 0) {
                $score += 5;
            }
            $maxScore += 25;
        }
        
        // Vérification des mots-clés dans la description
        if (!empty($product->name) && !empty($product->description)) {
            $nameWords = explode(' ', Tools::strtolower($product->name));
            $description = Tools::strtolower(strip_tags($product->description));
            
            $keywordCount = 0;
            foreach ($nameWords as $word) {
                if (Tools::strlen($word) > 3 && strpos($description, $word) !== false) {
                    $keywordCount++;
                }
            }
            
            if ($keywordCount >= 3) {
                $score += 20;
            } elseif ($keywordCount > 0) {
                $score += 10;
            }
            $maxScore += 20;
        }
        
        // Vérification de la référence
        if (!empty($product->reference)) {
            $score += 10;
            $maxScore += 10;
        }
        
        // Vérification des images
        $images = Image::getImages($this->context->language->id, $product->id);
        if (count($images) > 0) {
            $score += 10;
            $maxScore += 10;
        }
        
        // Calcul du score final
        return $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
    }
    
    /**
     * Optimise les descriptions des produits
     * 
     * @param array $productIds Liste des IDs des produits à optimiser
     * @param array $options Options d'optimisation
     * @return array Résultat de l'optimisation
     */
    protected function optimizeProducts($productIds, $options = [])
    {
        $result = [
            'success' => true,
            'optimized_products' => [],
            'errors' => []
        ];
        
        // Récupération des options
        $shortDescriptionLines = isset($options['short_description_lines']) ? (int)$options['short_description_lines'] : 4;
        $longDescriptionLines = isset($options['long_description_lines']) ? (int)$options['long_description_lines'] : 15;
        $seoLevel = isset($options['seo_level']) ? (int)$options['seo_level'] : 7;
        $additionalKeywords = isset($options['additional_keywords']) ? $options['additional_keywords'] : '';
        
        // Traitement de chaque produit
        foreach ($productIds as $productId) {
            try {
                $product = new Product($productId, true, $this->context->language->id);
                
                if (!Validate::isLoadedObject($product)) {
                    $result['errors'][] = sprintf('Produit ID %d non valide', $productId);
                    continue;
                }
                
                // Récupération des informations du produit
                $productName = $product->name;
                $currentShortDesc = strip_tags($product->description_short);
                $currentDesc = strip_tags($product->description);
                $reference = $product->reference;
                
                // Récupération de la catégorie
                $category = new Category($product->id_category_default, $this->context->language->id);
                $categoryName = Validate::isLoadedObject($category) ? $category->name : '';
                
                // Génération des nouvelles descriptions
                $newShortDesc = $this->generateOptimizedShortDescription(
                    $productName,
                    $currentShortDesc,
                    $categoryName,
                    $reference,
                    $shortDescriptionLines,
                    $seoLevel,
                    $additionalKeywords
                );
                
                $newDesc = $this->generateOptimizedLongDescription(
                    $productName,
                    $currentDesc,
                    $categoryName,
                    $reference,
                    $longDescriptionLines,
                    $seoLevel,
                    $additionalKeywords
                );
                
                // Mise à jour du produit
                $product->description_short = $newShortDesc;
                $product->description = $newDesc;
                
                if ($product->update()) {
                    $result['optimized_products'][] = [
                        'id_product' => $product->id,
                        'name' => $product->name,
                        'improvements' => [
                            'Description courte optimisée pour le SEO',
                            'Description longue optimisée pour le SEO',
                            'Longueur de la description courte ajustée à ' . $shortDescriptionLines . ' lignes',
                            'Longueur de la description longue ajustée à ' . $longDescriptionLines . ' lignes'
                        ]
                    ];
                } else {
                    $result['errors'][] = sprintf('Erreur lors de la mise à jour du produit ID %d', $productId);
                }
            } catch (Exception $e) {
                $result['errors'][] = sprintf('Exception pour le produit ID %d: %s', $productId, $e->getMessage());
            }
        }
        
        return $result;
    }
    
    /**
     * Génère une description courte optimisée pour le SEO
     * 
     * @param string $productName Nom du produit
     * @param string $currentDesc Description actuelle
     * @param string $categoryName Nom de la catégorie
     * @param string $reference Référence du produit
     * @param int $lines Nombre de lignes souhaitées
     * @param int $seoLevel Niveau d'optimisation SEO (1-10)
     * @param string $additionalKeywords Mots-clés supplémentaires
     * @return string Description courte optimisée
     */
    protected function generateOptimizedShortDescription($productName, $currentDesc, $categoryName, $reference, $lines = 4, $seoLevel = 7, $additionalKeywords = '')
    {
        // Utilisation de l'IA pour générer une description optimisée
        // Dans un cas réel, vous feriez un appel à l'API d'IA
        
        // Pour cette démonstration, nous allons simuler une description optimisée
        $shortDesc = '';
        
        // Si la description actuelle est vide, on en crée une nouvelle
        if (empty($currentDesc)) {
            $shortDesc = "Découvrez notre " . $productName . ", un produit de qualité supérieure dans la catégorie " . $categoryName . ". ";
            $shortDesc .= "Référence " . $reference . ". ";
            $shortDesc .= "Ce produit offre des performances exceptionnelles et une durabilité à toute épreuve. ";
            $shortDesc .= "Idéal pour tous vos besoins, il vous garantit une satisfaction totale.";
        } else {
            // Sinon, on améliore la description existante
            $shortDesc = $currentDesc;
            
            // Ajout du nom du produit si absent
            if (strpos($shortDesc, $productName) === false) {
                $shortDesc = "Le " . $productName . " : " . $shortDesc;
            }
            
            // Ajout de la catégorie si absente
            if (!empty($categoryName) && strpos($shortDesc, $categoryName) === false) {
                $shortDesc .= " Ce produit appartient à la catégorie " . $categoryName . ".";
            }
        }
        
        // Ajustement de la longueur pour obtenir environ le nombre de lignes demandé
        // En moyenne, une ligne contient environ 80 caractères
        $targetLength = $lines * 80;
        $currentLength = Tools::strlen($shortDesc);
        
        if ($currentLength < $targetLength) {
            // La description est trop courte, on l'enrichit
            $additionalText = [
                "Profitez d'une qualité exceptionnelle.",
                "Un rapport qualité-prix imbattable.",
                "Livraison rapide et soignée.",
                "Satisfaction garantie ou remboursé.",
                "Produit testé et approuvé par nos experts.",
                "Disponible en stock pour une livraison immédiate."
            ];
            
            while (Tools::strlen($shortDesc) < $targetLength && !empty($additionalText)) {
                $shortDesc .= " " . array_shift($additionalText);
            }
        } elseif ($currentLength > $targetLength * 1.2) {
            // La description est trop longue, on la tronque
            $shortDesc = Tools::substr($shortDesc, 0, $targetLength - 3) . "...";
        }
        
        // Formatage HTML
        $shortDesc = '<p>' . $shortDesc . '</p>';
        
        return $shortDesc;
    }
    
    /**
     * Génère une description longue optimisée pour le SEO
     * 
     * @param string $productName Nom du produit
     * @param string $currentDesc Description actuelle
     * @param string $categoryName Nom de la catégorie
     * @param string $reference Référence du produit
     * @param int $lines Nombre de lignes souhaitées
     * @param int $seoLevel Niveau d'optimisation SEO (1-10)
     * @param string $additionalKeywords Mots-clés supplémentaires
     * @return string Description longue optimisée
     */
    protected function generateOptimizedLongDescription($productName, $currentDesc, $categoryName, $reference, $lines = 15, $seoLevel = 7, $additionalKeywords = '')
    {
        // Utilisation de l'IA pour générer une description optimisée
        // Dans un cas réel, vous feriez un appel à l'API d'IA
        
        // Pour cette démonstration, nous allons simuler une description optimisée
        $longDesc = '';
        
        // Si la description actuelle est vide, on en crée une nouvelle
        if (empty($currentDesc)) {
            $longDesc = "<h2>Présentation du " . $productName . "</h2>";
            $longDesc .= "<p>Le " . $productName . " (référence : " . $reference . ") est un produit haut de gamme de la catégorie " . $categoryName . ". ";
            $longDesc .= "Conçu avec les meilleurs matériaux, il offre des performances exceptionnelles et une durabilité à toute épreuve.</p>";
            
            $longDesc .= "<h3>Caractéristiques principales</h3>";
            $longDesc .= "<ul>";
            $longDesc .= "<li>Qualité supérieure garantie</li>";
            $longDesc .= "<li>Design ergonomique et élégant</li>";
            $longDesc .= "<li>Facilité d'utilisation</li>";
            $longDesc .= "<li>Durabilité exceptionnelle</li>";
            $longDesc .= "</ul>";
            
            $longDesc .= "<h3>Avantages du " . $productName . "</h3>";
            $longDesc .= "<p>En choisissant le " . $productName . ", vous optez pour un produit qui vous accompagnera pendant de nombreuses années. ";
            $longDesc .= "Sa polyvalence et sa fiabilité en font un investissement judicieux pour tous vos besoins.</p>";
            
            $longDesc .= "<h3>Pourquoi choisir notre " . $productName . " ?</h3>";
            $longDesc .= "<p>Notre " . $productName . " se distingue par sa qualité supérieure et son excellent rapport qualité-prix. ";
            $longDesc .= "Nous garantissons votre satisfaction ou vous êtes remboursé.</p>";
        } else {
            // Sinon, on améliore la description existante
            $longDesc = $currentDesc;
            
            // Structuration en HTML si ce n'est pas déjà fait
            if (strpos($longDesc, '<h2>') === false) {
                $paragraphs = explode("\n", $longDesc);
                $longDesc = "<h2>Présentation du " . $productName . "</h2>";
                
                foreach ($paragraphs as $index => $paragraph) {
                    if (!empty(trim($paragraph))) {
                        if ($index === 0) {
                            $longDesc .= "<p>" . $paragraph . "</p>";
                        } elseif ($index === 1) {
                            $longDesc .= "<h3>Caractéristiques</h3><p>" . $paragraph . "</p>";
                        } else {
                            $longDesc .= "<p>" . $paragraph . "</p>";
                        }
                    }
                }
                
                // Ajout d'une section avantages si absente
                if (strpos($longDesc, 'avantage') === false) {
                    $longDesc .= "<h3>Avantages du " . $productName . "</h3>";
                    $longDesc .= "<p>En choisissant le " . $productName . ", vous optez pour un produit de qualité supérieure. ";
                    $longDesc .= "Sa polyvalence et sa fiabilité en font un investissement judicieux.</p>";
                }
            }
            
            // Ajout du nom du produit si absent
            if (strpos($longDesc, $productName) === false) {
                $longDesc = str_replace('<h2>', '<h2>' . $productName . ' - ', $longDesc);
            }
            
            // Ajout de la catégorie si absente
            if (!empty($categoryName) && strpos($longDesc, $categoryName) === false) {
                $longDesc .= "<p>Ce produit appartient à la catégorie " . $categoryName . ".</p>";
            }
        }
        
        // Ajustement de la longueur pour obtenir environ le nombre de lignes demandé
        // En moyenne, une ligne HTML contient environ 100 caractères
        $targetLength = $lines * 100;
        $currentLength = Tools::strlen(strip_tags($longDesc));
        
        if ($currentLength < $targetLength) {
            // La description est trop courte, on l'enrichit
            $longDesc .= "<h3>Informations complémentaires</h3>";
            $longDesc .= "<p>Notre service client est à votre disposition pour répondre à toutes vos questions concernant le " . $productName . ". ";
            $longDesc .= "N'hésitez pas à nous contacter pour obtenir des conseils personnalisés.</p>";
            
            $longDesc .= "<h3>Livraison et garantie</h3>";
            $longDesc .= "<p>Nous proposons une livraison rapide et soignée pour votre " . $productName . ". ";
            $longDesc .= "Tous nos produits sont garantis pour vous assurer une tranquillité d'esprit totale.</p>";
        } elseif ($currentLength > $targetLength * 1.5) {
            // La description est beaucoup trop longue, on la tronque intelligemment
            // On garde les balises h2 et h3 et quelques paragraphes
            preg_match_all('/<h[23][^>]*>.*?<\/h[23]>|<p>.*?<\/p>|<ul>.*?<\/ul>/s', $longDesc, $matches);
            
            if (!empty($matches[0])) {
                $elements = $matches[0];
                $newDesc = '';
                $newLength = 0;
                
                foreach ($elements as $element) {
                    $elementLength = Tools::strlen(strip_tags($element));
                    
                    if ($newLength + $elementLength <= $targetLength * 1.2) {
                        $newDesc .= $element;
                        $newLength += $elementLength;
                    } else {
                        break;
                    }
                }
                
                if (!empty($newDesc)) {
                    $longDesc = $newDesc;
                }
            }
        }
        
        return $longDesc;
    }
    
    /**
     * Ajout de JavaScript pour les actions AJAX
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        
        $this->addJS($this->module->getPathUri() . 'views/js/product-optimizer.js');
        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
        $this->addCSS($this->module->getPathUri() . 'views/css/product-optimizer.css');
    }
    
    /**
     * Traitement des requêtes AJAX
     */
    public function ajaxProcessOptimizeProducts()
    {
        $productIds = Tools::getValue('product_ids', []);
        $options = [
            'short_description_lines' => (int)Tools::getValue('short_description_lines', 4),
            'long_description_lines' => (int)Tools::getValue('long_description_lines', 15),
            'seo_level' => (int)Tools::getValue('seo_level', 7),
            'additional_keywords' => Tools::getValue('additional_keywords', '')
        ];
        
        $result = $this->optimizeProducts($productIds, $options);
        
        die(json_encode($result));
    }
}