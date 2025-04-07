<?php
/**
 * Module IaBot pour PrestaShop
 *
 * @author    IaBot Team
 * @copyright 2023 IaBot
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Définir les constantes nécessaires si elles n'existent pas
if (!defined('_PS_ROOT_DIR_')) {
    define('_PS_ROOT_DIR_', dirname(__FILE__, 3));
}

if (!defined('_PS_CACHE_DIR_')) {
    define('_PS_CACHE_DIR_', _PS_ROOT_DIR_ . '/var/cache/');
}

if (!defined('_COOKIE_KEY_')) {
    define('_COOKIE_KEY_', 'iabot_default_key');
}

// Définition de l'interface WidgetInterface si elle n'existe pas
if (!interface_exists('WidgetInterface')) {
    interface WidgetInterface
    {
        public function renderWidget(string $hookName, array $configuration): string;
        public function getWidgetVariables(string $hookName, array $configuration): array;
    }
}

// Import des classes PrestaShop nécessaires
use PrestaShopBundle\Controller\Admin\Sell\Catalog\Product\ProductController;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Classe principale du module IaBot
 */
class IaBot extends Module implements WidgetInterface
{
    /**
     * @var string Nom du module
     */
    public $name;
    
    /**
     * @var string Onglet du module
     */
    public $tab;
    
    /**
     * @var string Version du module
     */
    public $version;
    
    /**
     * @var string Auteur du module
     */
    public $author;
    
    /**
     * @var int Besoin d'instance
     */
    public $need_instance;
    
    /**
     * @var bool Utilisation du bootstrap
     */
    public $bootstrap;
    
    /**
     * @var string Nom affiché du module
     */
    public $displayName;
    
    /**
     * @var string Description du module
     */
    public $description;
    
    /**
     * @var string Message de confirmation de désinstallation
     */
    public $confirmUninstall;
    
    /**
     * @var array Compatibilité des versions de PrestaShop
     */
    public $ps_versions_compliancy;
    
    /**
     * @var string Chemin du module
     */
    public $_path;
    
    /**
     * @var int ID du module
     */
    public $id;
    
    /**
     * @var \Context|null Instance du contexte PrestaShop
     */
    protected $context;
    
    /**
     * @var \Controller|null Contrôleur actuel
     */
    protected $controller;
    
    /**
     * @var \Customer|null Client actuel
     */
    protected $customer;
    
    /**
     * @var \Cart|null Panier actuel
     */
    protected $cart;
    
    /**
     * @var \Currency|null Devise actuelle
     */
    protected $currency;
    
    /**
     * @var \Smarty|null Instance de Smarty
     */
    protected $smarty;
    
    /**
     * @var \Employee|null Employé actuel
     */
    protected $employee;
    
    /**
     * @var \Db|null Instance de la base de données
     */
    protected $db;
    
    /**
     * @var string Icône du module
     */
    protected $icon;
    
    /**
     * @var array|string Libellés du module
     */
    protected $wording;
    
    /**
     * @var string Domaine de traduction
     */
    protected $wording_domain;
    
    /**
     * @var array Liste des hooks utilisés par le module
     */
    protected $hooks = [
        'displayBeforeBodyClosingTag',
        'displayHeader',
        'displayFooter',
        'displayProductAdditionalInfo',
        'displayBackOfficeHeader',
        'actionAuthentication',
        'actionCustomerLogoutAfter',
        'actionProductAdd',
        'actionProductUpdate',
        'actionProductDelete',
        'actionCategoryAdd',
        'actionCategoryUpdate',
        'actionCategoryDelete',
        'actionOrderStatusUpdate'
    ];
    
    /**
     * @var array Configuration des onglets d'administration
     */
    protected $tabConfig = [
        [
            'name' => 'Configuration IaBot',
            'class_name' => 'AdminIaBotConfiguration',
            'visible' => true,
            'parent_class_name' => 'AdminParentModulesSf'
        ],
        [
            'name' => 'Optimisation des produits',
            'class_name' => 'AdminIaBotProductOptimizer',
            'visible' => true,
            'parent_class_name' => 'AdminParentModulesSf'
        ]
    ];

    /**
     * Constructeur du module
     */
    public function __construct()
    {
        $this->name = 'iabot';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'IaBot Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('IaBot - Assistant IA pour PrestaShop');
        $this->description = $this->l('Module d\'assistant IA pour votre boutique PrestaShop');
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module?');

        // Initialisation des propriétés
        $this->initializeProperties();
    }

    /**
     * Initialisation des propriétés
     */
    protected function initializeProperties()
    {
        // Initialisation des propriétés par défaut
        $this->controller = null;
        $this->customer = null;
        $this->cart = null;
        $this->currency = null;
        $this->smarty = null;
        $this->employee = null;
        $this->db = null;
        $this->icon = 'chat';
        $this->wording = [];
        $this->wording_domain = 'Modules.Iabot.Admin';
        
        // Initialisation du contexte
        if (class_exists('Context')) {
            $this->context = Context::getContext();
            
            // Initialisation des propriétés liées au contexte
            if (isset($this->context)) {
                if (isset($this->context->controller)) {
                    $this->controller = $this->context->controller;
                }
                
                if (isset($this->context->customer)) {
                    $this->customer = $this->context->customer;
                }
                
                if (isset($this->context->cart)) {
                    $this->cart = $this->context->cart;
                }
                
                if (isset($this->context->currency)) {
                    $this->currency = $this->context->currency;
                }
                
                if (isset($this->context->smarty)) {
                    $this->smarty = $this->context->smarty;
                }
                
                if (isset($this->context->employee)) {
                    $this->employee = $this->context->employee;
                }
            }
        }
        
        // Initialisation de la base de données
        if (class_exists('Db')) {
            $this->db = Db::getInstance();
        }
    }
    
    /**
     * Installation du module
     *
     * @return bool
     */
    public function install()
    {
        // Vérifier si la méthode parent existe
        if (!method_exists(parent::class, 'install')) {
            return false;
        }
        
        // Installation du module parent
        if (!parent::install()) {
            return false;
        }
        
        // Installation des tables SQL
        if (!$this->installDb()) {
            $this->uninstall();
            return false;
        }
        
        // Installation des onglets d'administration
        if (!$this->installTabs()) {
            $this->uninstall();
            return false;
        }
        
        // Enregistrement des hooks
        foreach ($this->hooks as $hook) {
            if (!$this->registerHook($hook)) {
                $this->uninstall();
                return false;
            }
        }
        
        // Configuration par défaut
        \Configuration::updateValue('IABOT_ENABLED', true);
        \Configuration::updateValue('IABOT_API_KEY', '');
        \Configuration::updateValue('IABOT_API_URL', 'https://api.iabot.ai/v1');
        \Configuration::updateValue('IABOT_POSITION', 'right');
        \Configuration::updateValue('IABOT_THEME', 'light');
        \Configuration::updateValue('IABOT_TITLE', 'Assistant IA');
        \Configuration::updateValue('IABOT_SUBTITLE', 'Comment puis-je vous aider ?');
        
        return true;
    }
    
    /**
     * Désinstallation du module
     *
     * @return bool
     */
    public function uninstall()
    {
        // Vérifier si la méthode parent existe
        if (!method_exists(parent::class, 'uninstall')) {
            return false;
        }
        
        // Suppression des onglets d'administration
        if (!$this->uninstallTabs()) {
            return false;
        }
        
        // Suppression des tables SQL
        if (!$this->uninstallDb()) {
            return false;
        }
        
        // Suppression de la configuration
        $this->deleteByName('IABOT_ENABLED');
        $this->deleteByName('IABOT_API_KEY');
        $this->deleteByName('IABOT_API_URL');
        $this->deleteByName('IABOT_POSITION');
        $this->deleteByName('IABOT_THEME');
        $this->deleteByName('IABOT_TITLE');
        $this->deleteByName('IABOT_SUBTITLE');
        
        // Désinstallation du module parent
        if (!parent::uninstall()) {
            return false;
        }
        
        return true;
    }

    /**
     * Installation des tables SQL
     *
     * @return bool
     */
    protected function installDb()
    {
        $sql_file = dirname(__FILE__) . '/sql/install.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            if ($sql) {
                $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);
                $sql = preg_split("/;\s*[\r\n]+/", trim($sql));
                
                foreach ($sql as $query) {
                    if (!empty(trim($query)) && !Db::getInstance()->execute(trim($query))) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Désinstallation des tables SQL
     *
     * @return bool
     */
    protected function uninstallDb()
    {
        $sql_file = dirname(__FILE__) . '/sql/uninstall.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            if ($sql) {
                $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
                $sql = preg_split("/;\s*[\r\n]+/", trim($sql));
                
                foreach ($sql as $query) {
                    if (!empty(trim($query)) && !Db::getInstance()->execute(trim($query))) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Installation de la configuration par défaut
     *
     * @return bool
     */
    protected function installDefaultConfig()
    {
        $config = [
            'IABOT_ENABLED' => 1,
            'IABOT_POSITION' => 'right',
            'IABOT_THEME' => 'light',
            'IABOT_TITLE' => $this->l('Assistant IA'),
            'IABOT_SUBTITLE' => $this->l('Comment puis-je vous aider ?')
        ];
        
        foreach ($config as $key => $value) {
            if (!Configuration::updateValue($key, $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Désinstallation de la configuration
     *
     * @return bool
     */
    protected function uninstallConfig()
    {
        $config = [
            'IABOT_ENABLED',
            'IABOT_POSITION',
            'IABOT_THEME',
            'IABOT_TITLE',
            'IABOT_SUBTITLE'
        ];
        
        foreach ($config as $key) {
            if (method_exists('Configuration', 'deleteByName')) {
                if (!$this->deleteByName($key)) {
                    return false;
                }
            } else {
                // Fallback si la méthode n'existe pas
                Db::getInstance()->delete('configuration', 'name = \'' . pSQL($key) . '\'');
            }
        }
        
        return true;
    }
    
    /**
     * Supprimer une configuration par son nom
     *
     * @param string $name Nom de la configuration
     * @return bool
     */
    public function deleteByName($name)
    {
        if (method_exists('\Configuration', 'deleteByName')) {
            return \Configuration::deleteByName($name);
        }
        
        // Fallback si la méthode n'existe pas
        if (!$this->db) {
            return false;
        }
        
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'configuration` WHERE `name` = \'' . pSQL($name) . '\'';
        return $this->db->execute($sql);
    }

    /**
     * Obtenir l'ID de la dernière insertion
     * 
     * @return int
     */
    protected function getLastInsertId()
    {
        // Vérifier si la propriété db est définie
        if (!isset($this->db) || !$this->db) {
            return 0;
        }
        
        // Vérifier quelle méthode est disponible
        if (method_exists($this->db, 'Insert_ID')) {
            return $this->db->Insert_ID();
        } elseif (method_exists($this->db, 'insertId')) {
            return $this->db->insertId();
        }
        
        return 0;
    }

    /**
     * Vider le cache
     */
    public function clearCache()
    {
        // Vider le cache Smarty
        $this->clearSmartyCache();
        
        // Vider le cache de configuration
        if (method_exists('Configuration', 'clearConfigurationCacheForTesting')) {
            Configuration::clearConfigurationCacheForTesting();
        } else {
            // Fallback pour les anciennes versions
            $this->clearConfigCache();
        }
        
        return true;
    }
    
    /**
     * Vider le cache Smarty
     */
    public function clearSmartyCache()
    {
        if (isset($this->smarty) && method_exists($this->smarty, 'clearAllCache')) {
            $this->smarty->clearAllCache();
        } else {
            // Fallback pour les anciennes versions
            $this->clearSmartyDirectoryCache();
        }
        
        return true;
    }
    
    /**
     * Vider le cache de configuration
     */
    protected function clearConfigCache()
    {
        // Vérifier si la méthode Configuration::clearConfigurationCacheForTesting existe
        if (method_exists('Configuration', 'clearConfigurationCacheForTesting')) {
            Configuration::clearConfigurationCacheForTesting();
        } elseif (method_exists('Configuration', 'clearCache')) {
            Configuration::clearCache();
        } else {
            // Fallback si aucune méthode n'existe
            $this->clearCache();
        }
    }
    
    /**
     * Optimise les descriptions des produits pour le SEO
     * 
     * @param array $productIds Liste des IDs des produits à optimiser
     * @param array $options Options d'optimisation
     * @return array Résultat de l'optimisation
     */
    public function optimizeProducts($productIds, $options = [])
    {
        if (!is_array($productIds) || empty($productIds)) {
            return [
                'success' => false,
                'message' => $this->l('Aucun produit sélectionné.'),
                'optimized_products' => [],
                'errors' => []
            ];
        }
        
        // Récupération des options
        $shortDescriptionLines = isset($options['short_description_lines']) ? (int)$options['short_description_lines'] : 4;
        $longDescriptionLines = isset($options['long_description_lines']) ? (int)$options['long_description_lines'] : 15;
        $seoLevel = isset($options['seo_level']) ? (int)$options['seo_level'] : 7;
        $additionalKeywords = isset($options['additional_keywords']) ? $options['additional_keywords'] : '';
        
        $result = [
            'success' => true,
            'message' => '',
            'optimized_products' => [],
            'errors' => []
        ];
        
        // Traitement de chaque produit
        foreach ($productIds as $productId) {
            try {
                $product = new Product($productId, true, $this->context->language->id);
                
                if (!Validate::isLoadedObject($product)) {
                    $result['errors'][] = sprintf($this->l('Produit ID %d non valide'), $productId);
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
                            $this->l('Description courte optimisée pour le SEO'),
                            $this->l('Description longue optimisée pour le SEO'),
                            sprintf($this->l('Longueur de la description courte ajustée à %d lignes'), $shortDescriptionLines),
                            sprintf($this->l('Longueur de la description longue ajustée à %d lignes'), $longDescriptionLines)
                        ]
                    ];
                } else {
                    $result['errors'][] = sprintf($this->l('Erreur lors de la mise à jour du produit ID %d'), $productId);
                }
            } catch (Exception $e) {
                $result['errors'][] = sprintf($this->l('Exception pour le produit ID %d: %s'), $productId, $e->getMessage());
            }
        }
        
        $result['message'] = sprintf(
            $this->l('%d produits ont été améliorés avec succès.'),
            count($result['optimized_products'])
        );
        
        if (!empty($result['errors'])) {
            $result['message'] .= ' ' . sprintf(
                $this->l('%d erreurs ont été rencontrées.'),
                count($result['errors'])
            );
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
     * Vérifie si l'utilisateur actuel a des droits d'administration
     * 
     * @return bool Retourne true si l'utilisateur a des droits d'administration, false sinon
     */
    public function hasAdminRights()
    {
        // Vérification que le contexte est initialisé
        if (!isset($this->context) || !isset($this->context->employee)) {
            return false;
        }
        
        // Vérification que l'employé est connecté
        if (!$this->context->employee->isLoggedBack()) {
            return false;
        }
        
        // Vérification que l'employé a les droits d'administration du module
        if (!$this->context->employee->hasAuthOnModule($this->name)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Vider le cache Smarty en supprimant les fichiers du répertoire
     */
    protected function clearSmartyDirectoryCache()
    {
        $cacheDir = defined('_PS_CACHE_DIR_') ? _PS_CACHE_DIR_ . 'smarty/cache' : _PS_ROOT_DIR_ . '/var/cache/smarty/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    } elseif (is_dir($file)) {
                        $this->deleteDirectory($file);
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Supprimer un répertoire et son contenu
     *
     * @param string $dir Chemin du répertoire
     * @return bool
     */
    protected function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        
        return @rmdir($dir);
    }
    
    /**
     * Obtenir l'adresse IP du client
     * 
     * @return string
     */
    protected function getRemoteAddr()
    {
        // Vérifier si la classe Tools existe et si la méthode getRemoteAddr existe
        if (class_exists('Tools') && method_exists('Tools', 'getRemoteAddr')) {
            return Tools::getRemoteAddr();
        }
        
        // Fallback si la méthode n'existe pas
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }
        
        return '127.0.0.1';
    }
    
    /**
     * Générer un token de sécurité
     * 
     * @param string $page Nom de la page
     * @return string
     */
    protected function getToken($page = null)
    {
        // Vérifier si la classe Tools existe et si la méthode getToken existe
        if (class_exists('Tools') && method_exists('Tools', 'getToken')) {
            return Tools::getToken(false);
        } elseif (class_exists('Tools') && method_exists('Tools', 'getAdminToken')) {
            // Fallback si la méthode getToken n'existe pas
            $page = $page ?: 'AdminModules';
            $employee_id = isset($this->employee) && $this->employee ? (int)$this->employee->id : 0;
            return Tools::getAdminToken($page . (int)$employee_id . (int)$this->id);
        }
        
        // Fallback si aucune méthode n'existe
        $key = defined('_COOKIE_KEY_') ? _COOKIE_KEY_ : 'iabot_default_key';
        $employee_id = isset($this->employee) && $this->employee ? (int)$this->employee->id : 0;
        return md5($key . $employee_id . (int)$this->id);
    }

    /**
     * Traduction d'une chaîne
     * 
     * @param string $string Chaîne à traduire
     * @param string|bool $specific Module spécifique
     * @return string
     */
    public function l($string, $specific = false)
    {
        // Vérifier si la méthode parent existe
        if (method_exists(parent::class, 'l')) {
            return parent::l($string, $specific);
        }
        
        // Fallback si la méthode n'existe pas
        if (class_exists('\Translate')) {
            if (method_exists('\Translate', 'getModuleTranslation')) {
                return \Translate::getModuleTranslation($this->name, $string, $specific ?: $this->name);
            }
        }
        
        return $string;
    }

    /**
     * Récupérer le chemin local du module
     */
    public function getLocalPath()
    {
        return _PS_MODULE_DIR_ . $this->name . '/';
    }

    /**
     * Affichage du widget
     * 
     * @param string $hookName    Nom du hook
     * @param array  $configuration Configuration
     * 
     * @return string
     */
    public function renderWidget(string $hookName, array $configuration): string
    {
        // Vérifier si le contexte est disponible
        if (!isset($this->context) || !$this->context) {
            return '';
        }
        
        // Récupérer les variables du widget
        $variables = $this->getWidgetVariables($hookName, $configuration);
        
        // Assigner les variables au template
        if (isset($this->context->smarty)) {
            $this->context->smarty->assign($variables);
            
            // Récupérer le contenu du template
            if (method_exists($this->context->smarty, 'fetch')) {
                return $this->context->smarty->fetch('module:' . $this->name . '/views/templates/hook/' . $hookName . '.tpl');
            }
        }
        
        return '';
    }
    
    /**
     * Récupérer les variables du widget
     *
     * @param string $hookName    Nom du hook
     * @param array  $configuration Configuration
     *
     * @return array
     */
    public function getWidgetVariables(string $hookName, array $configuration = []): array
    {
        return [
            'iabot_enabled' => (bool)Configuration::get('IABOT_ENABLED', true),
            'iabot_position' => Configuration::get('IABOT_POSITION', 'right'),
            'iabot_theme' => Configuration::get('IABOT_THEME', 'light'),
            'iabot_title' => Configuration::get('IABOT_TITLE', $this->l('Assistant IA')),
            'iabot_subtitle' => Configuration::get('IABOT_SUBTITLE', $this->l('Comment puis-je vous aider ?')),
            'iabot_module_dir' => $this->_path,
            'iabot_ajax_url' => isset($this->context->link) ? $this->context->link->getModuleLink($this->name, 'ajax', [], true) : '',
        ];
    }

    /**
     * Affichage du script avant la fermeture du body
     */
    public function hookDisplayBeforeBodyClosingTag($params)
    {
        // Vérifier si le contexte est disponible
        if (!isset($this->context) || !$this->context) {
            return '';
        }
        
        // Vérifier si Smarty est disponible
        if (!isset($this->context->smarty)) {
            return '';
        }
        
        // Assigner les variables au template
        $this->context->smarty->assign([
            'iabot_enabled' => (bool)Configuration::get('IABOT_ENABLED', true),
            'iabot_position' => Configuration::get('IABOT_POSITION', 'right'),
            'iabot_theme' => Configuration::get('IABOT_THEME', 'light'),
            'iabot_title' => Configuration::get('IABOT_TITLE', $this->l('Assistant IA')),
            'iabot_subtitle' => Configuration::get('IABOT_SUBTITLE', $this->l('Comment puis-je vous aider ?')),
            'iabot_module_dir' => $this->_path,
            'iabot_ajax_url' => isset($this->context->link) ? $this->context->link->getModuleLink($this->name, 'ajax', [], true) : '',
        ]);

        // Récupérer le contenu du template
        if (method_exists($this->context->smarty, 'fetch')) {
            return $this->context->smarty->fetch('module:' . $this->name . '/views/templates/front/scripts.tpl');
        }
        
        return '';
    }

    /**
     * Installation des onglets d'administration
     *
     * @return bool
     */
    public function installTabs()
    {
        // Vérifier si la classe Tab existe
        if (!class_exists('Tab')) {
            return false;
        }
        
        // Création de l'onglet principal
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminIaBot';
        $tab->name = array();
        $tab->icon = $this->icon;
        
        // Vérifier si la méthode getIdFromClassName existe
        if (method_exists('Tab', 'getIdFromClassName')) {
            $tab->id_parent = Tab::getIdFromClassName('SELL');
        } else {
            // Valeur par défaut si la méthode n'existe pas
            $tab->id_parent = 0;
        }
        
        $tab->module = $this->name;

        // Vérifier si la classe Language existe
        if (class_exists('Language')) {
            foreach (Language::getLanguages() as $lang) {
                if (isset($lang['id_lang'])) {
                    $tab->name[$lang['id_lang']] = 'Assistant IA';
                }
            }
        } else {
            // Fallback si Language n'existe pas
            $tab->name[1] = 'Assistant IA';
        }

        // Vérifier si la méthode save existe
        if (method_exists($tab, 'save')) {
            if (!$tab->save()) {
                return false;
            }
        } else {
            // Fallback si la méthode save n'existe pas
            $db = Db::getInstance();
            $db->insert('tab', [
                'class_name' => pSQL($tab->class_name),
                'module' => pSQL($tab->module),
                'id_parent' => (int)$tab->id_parent,
                'active' => (int)$tab->active
            ]);
            
            $tabId = $this->getLastInsertId();
            
            if ($tabId) {
                foreach ($tab->name as $langId => $name) {
                    $db->insert('tab_lang', [
                        'id_tab' => (int)$tabId,
                        'id_lang' => (int)$langId,
                        'name' => pSQL($name)
                    ]);
                }
            }
        }

        // Ajout des permissions pour l'employé actuel
        if (isset($this->context) && isset($this->context->employee) && 
            isset($this->context->employee->id) && isset($tab->id)) {
            $this->addTabPermission($tab->id, $this->context->employee->id);
        }

        return true;
    }

    /**
     * Désinstallation des onglets d'administration
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        // Vérifier si la classe Tab existe et si la méthode getIdFromClassName existe
        if (class_exists('Tab') && method_exists('Tab', 'getIdFromClassName')) {
            $tabId = Tab::getIdFromClassName('AdminIaBot');
            if ($tabId) {
                $tab = new Tab($tabId);
                if (method_exists($tab, 'delete')) {
                    return $tab->delete();
                } else {
                    // Fallback si la méthode delete n'existe pas
                    $db = Db::getInstance();
                    $db->delete('tab', 'id_tab = ' . (int)$tabId);
                    $db->delete('tab_lang', 'id_tab = ' . (int)$tabId);
                    return true;
                }
            }
        } else {
            // Fallback si Tab n'existe pas ou getIdFromClassName n'existe pas
            $db = Db::getInstance();
            $tabId = $db->getValue('SELECT id_tab FROM `' . _DB_PREFIX_ . 'tab` WHERE class_name = "AdminIaBot"');
            if ($tabId) {
                $db->delete('tab', 'id_tab = ' . (int)$tabId);
                $db->delete('tab_lang', 'id_tab = ' . (int)$tabId);
            }
        }
        return true;
    }

    /**
     * Ajouter les permissions sur un onglet pour un employé
     *
     * @param int $idTab ID de l'onglet
     * @param int $idEmployee ID de l'employé
     * @return bool
     */
    protected function addTabPermission($idTab, $idEmployee)
    {
        // Vérifier si la base de données est disponible
        $db = Db::getInstance();
        if (!$db) {
            return false;
        }
        
        // Vérifier si la table des autorisations existe
        $tablePermission = _DB_PREFIX_ . 'authorization_role';
        $tablePermissionExists = $db->getValue("SHOW TABLES LIKE '" . pSQL($tablePermission) . "'") ? true : false;

        if ($tablePermissionExists) {
            // PrestaShop 1.7+ utilise des rôles d'autorisation
            $roles = ['CREATE', 'READ', 'UPDATE', 'DELETE'];
            
            if (class_exists('Tab')) {
                $tab = new Tab($idTab);
                $className = $tab->class_name;

                foreach ($roles as $role) {
                    $slug = "ROLE_MOD_TAB_" . strtoupper($this->name) . "_" . strtoupper($className) . "_" . $role;
                    $roleId = $db->getValue(
                        "SELECT id_authorization_role FROM `" . _DB_PREFIX_ . "authorization_role` 
                         WHERE slug = '" . pSQL($slug) . "'"
                    );

                    if (!$roleId) {
                        $db->execute(
                            "INSERT INTO `" . _DB_PREFIX_ . "authorization_role` (`slug`) 
                             VALUES ('" . pSQL($slug) . "')"
                        );
                        $roleId = (int)$this->getLastInsertId();
                    }

                    if ($roleId) {
                        $db->execute(
                            'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'access` (`id_profile`, `id_authorization_role`) 
                             VALUES (1, ' . (int)$roleId . ')'
                        );

                        $db->execute(
                            'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'module_access` (`id_profile`, `id_authorization_role`) 
                             VALUES (1, ' . (int)$roleId . ')'
                        );
                    }
                }
            }
        }

        return true;
    }

    /**
     * Vérifier si le nom du hook est valide
     * 
     * @param string $hook_name Nom du hook
     * @return bool
     */
    protected function isValidHookName($hook_name)
    {
        return is_string($hook_name) && !empty($hook_name);
    }
    
    /**
     * Récupérer l'ID d'un hook par son nom
     * 
     * @param string $hook_name Nom du hook
     * @return int|bool
     */
    protected function getHookIdByName($hook_name)
    {
        if (!$this->db) {
            return false;
        }
        
        $sql = 'SELECT `id_hook` FROM `' . _DB_PREFIX_ . 'hook` WHERE `name` = \'' . pSQL($hook_name) . '\'';
        $result = $this->db->getRow($sql);
        
        return isset($result['id_hook']) ? (int)$result['id_hook'] : false;
    }
    
    /**
     * Créer un nouveau hook
     * 
     * @param string $hook_name Nom du hook
     * @return int|bool
     */
    protected function createHook($hook_name)
    {
        if (!$this->db) {
            return false;
        }
        
        $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'hook` (`name`, `title`, `description`)
                VALUES (\'' . pSQL($hook_name) . '\', \'' . pSQL($hook_name) . '\', \'\')';
        
        if (!$this->db->execute($sql)) {
            return false;
        }
        
        return $this->getHookIdByName($hook_name);
    }

    /**
     * Enregistrement d'un hook
     *
     * @param string $hook_name Nom du hook
     * @param array|null $shop_list Liste des boutiques
     * @return bool
     */
    public function registerHook($hook_name, $shop_list = null)
    {
        // Vérifier si le nom du hook est valide
        if (!$this->isValidHookName($hook_name)) {
            return false;
        }
        
        // Vérifier si la méthode parent existe
        if (method_exists(parent::class, 'registerHook')) {
            return parent::registerHook($hook_name, $shop_list);
        }
        
        // Fallback si la méthode n'existe pas
        $hook_id = $this->getHookIdByName($hook_name);
        if (!$hook_id) {
            $hook_id = $this->createHook($hook_name);
        }
        
        if (!$hook_id) {
            return false;
        }
        
        // Vérifier si l'ID du module est défini
        if (!isset($this->id) || !$this->id) {
            return false;
        }
        
        // Enregistrer le hook
        $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'hook_module` (`id_module`, `id_hook`, `position`)
                VALUES (' . (int)$this->id . ', ' . (int)$hook_id . ', 
                (SELECT IFNULL(MAX(position), 0) + 1 FROM `' . _DB_PREFIX_ . 'hook_module` WHERE `id_hook` = ' . (int)$hook_id . '))';
        
        return $this->db && $this->db->execute($sql);
    }
}
