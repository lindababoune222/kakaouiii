<?php
/**
 * Bootstrap pour les tests unitaires du module IaBot
 * 
 * Ce fichier configure l'environnement nécessaire pour exécuter les tests unitaires
 * en simulant les classes et fonctions de PrestaShop.
 * 
 * @author  Développeur
 * @copyright 2025
 */

// Définition des constantes nécessaires pour les tests
if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.8.0');
}

if (!defined('_PS_ROOT_DIR_')) {
    define('_PS_ROOT_DIR_', dirname(__FILE__) . '/..');
}

if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}

if (!defined('_PS_MODE_DEV_')) {
    define('_PS_MODE_DEV_', true);
}

// Fonction de mock pour pSQL (utilisée dans les requêtes)
if (!function_exists('pSQL')) {
    function pSQL($string, $htmlOK = false) {
        return $htmlOK ? htmlspecialchars($string, ENT_QUOTES) : addslashes($string);
    }
}

// Mock de la classe Db pour les tests
if (!class_exists('Db', false)) {
    class Db {
        public static function getInstance() {
            return new self();
        }
        
        public function executeS($query) {
            // Simulation de résultats pour les tests
            if (strpos($query, 'iabot_recommendation') !== false) {
                return [
                    [
                        'id_recommendation' => 1,
                        'id_product' => 1,
                        'keyword' => 'windsurf',
                        'position' => 1
                    ],
                    [
                        'id_recommendation' => 2,
                        'id_product' => 2,
                        'keyword' => 'windsurf',
                        'position' => 2
                    ]
                ];
            }
            
            if (strpos($query, 'iabot_message') !== false) {
                return [
                    [
                        'id_message' => 1,
                        'id_conversation' => 1,
                        'content' => 'Bonjour, je cherche une planche de windsurf',
                        'sender' => 'user',
                        'date_add' => '2025-01-01 12:00:00'
                    ],
                    [
                        'id_message' => 2,
                        'id_conversation' => 1,
                        'content' => 'Voici nos recommandations pour le windsurf',
                        'sender' => 'bot',
                        'date_add' => '2025-01-01 12:01:00'
                    ]
                ];
            }
            
            return [];
        }
        
        public function execute($query) {
            // Simulation de succès pour les requêtes d'insertion/mise à jour
            return true;
        }
        
        public function insert($table, $data, $nullValues = false, $useCache = true) {
            return true;
        }
        
        public function update($table, $data, $where = '', $limit = 0, $nullValues = false, $useCache = true) {
            return true;
        }
        
        public function delete($table, $where = '', $limit = 0, $useCache = true) {
            return true;
        }
        
        public function escape($string, $htmlOK = false) {
            return pSQL($string, $htmlOK);
        }
    }
}

// Mock de la classe DbQuery pour les tests
if (!class_exists('DbQuery', false)) {
    class DbQuery {
        private $query = '';
        private $select = '';
        private $from = '';
        private $where = '';
        private $order = '';
        private $limit = '';
        
        public function select($fields) {
            $this->select = 'SELECT ' . $fields;
            return $this;
        }
        
        public function from($table, $alias = null) {
            $this->from = 'FROM ' . _DB_PREFIX_ . $table . ($alias ? ' ' . $alias : '');
            return $this;
        }
        
        public function where($condition) {
            $this->where = $this->where ? $this->where . ' AND ' . $condition : 'WHERE ' . $condition;
            return $this;
        }
        
        public function orderBy($fields) {
            $this->order = 'ORDER BY ' . $fields;
            return $this;
        }
        
        public function limit($limit, $offset = 0) {
            $this->limit = 'LIMIT ' . (int)$offset . ', ' . (int)$limit;
            return $this;
        }
        
        public function build() {
            $this->query = $this->select . ' ' . $this->from;
            if ($this->where) {
                $this->query .= ' ' . $this->where;
            }
            if ($this->order) {
                $this->query .= ' ' . $this->order;
            }
            if ($this->limit) {
                $this->query .= ' ' . $this->limit;
            }
            return $this->query;
        }
        
        public function __toString() {
            return $this->build();
        }
    }
}

// Mock de la classe ObjectModel pour les tests
if (!class_exists('ObjectModel', false)) {
    class ObjectModel {
        public $id;
        
        public function __construct($id = null) {
            if ($id) {
                $this->id = (int)$id;
            }
        }
        
        public function add($autoDate = true, $nullValues = false) {
            // Simulation d'ajout réussi
            $this->id = rand(1, 1000);
            return true;
        }
        
        public function update($nullValues = false) {
            // Simulation de mise à jour réussie
            return true;
        }
        
        public function delete() {
            // Simulation de suppression réussie
            return true;
        }
    }
}

// Mock de la classe Product pour les tests
if (!class_exists('Product', false)) {
    class Product {
        public static function getProductName($id_product, $id_lang = null) {
            // Simulation de noms de produits
            $products = [
                1 => 'Planche de windsurf débutant',
                2 => 'Voile de windsurf 5m²',
                3 => 'Combinaison néoprène'
            ];
            
            return isset($products[$id_product]) ? $products[$id_product] : 'Produit inconnu';
        }
        
        public static function getProductsProperties($id_lang, $products) {
            // Simulation de propriétés de produits
            $result = [];
            foreach ($products as $product) {
                $id = isset($product['id_product']) ? $product['id_product'] : 0;
                $result[] = [
                    'id_product' => $id,
                    'name' => self::getProductName($id),
                    'description_short' => 'Description courte du produit ' . $id,
                    'price' => 499.99,
                    'reference' => 'REF-' . $id,
                    'quantity' => 10,
                    'link_rewrite' => 'produit-' . $id
                ];
            }
            return $result;
        }
    }
}

// Mock de la classe Validate pour les tests
if (!class_exists('Validate', false)) {
    class Validate {
        public static function isCleanHtml($html, $allowIframe = false) {
            // Vérification simplifiée pour les tests
            return !preg_match('/<script\b[^>]*>(.*?)<\/script>/is', $html);
        }
        
        public static function isInt($value) {
            return is_numeric($value) && (int)$value == $value;
        }
        
        public static function isUnsignedInt($value) {
            return self::isInt($value) && $value >= 0;
        }
        
        public static function isNullOrUnsignedId($id) {
            return $id === null || self::isUnsignedInt($id);
        }
        
        public static function isString($value) {
            return is_string($value);
        }
    }
}

// Mock de la classe Configuration pour les tests
if (!class_exists('Configuration', false)) {
    class Configuration {
        private static $values = [
            'PS_LANG_DEFAULT' => 1,
            'PS_SHOP_DEFAULT' => 1
        ];
        
        public static function get($key, $id_lang = null, $id_shop_group = null, $id_shop = null) {
            return isset(self::$values[$key]) ? self::$values[$key] : false;
        }
        
        public static function updateValue($key, $value, $html = false, $id_shop_group = null, $id_shop = null) {
            self::$values[$key] = $value;
            return true;
        }
    }
}

// Mock de la classe Context pour les tests
if (!class_exists('Context', false)) {
    class Context {
        public $language;
        public $shop;
        private static $instance;
        
        public function __construct() {
            $this->language = new stdClass();
            $this->language->id = 1;
            
            $this->shop = new stdClass();
            $this->shop->id = 1;
        }
        
        public static function getContext() {
            if (!isset(self::$instance)) {
                self::$instance = new Context();
            }
            return self::$instance;
        }
    }
}

// Mock de la classe Tools pour les tests
if (!class_exists('Tools', false)) {
    class Tools {
        public static function getValue($key, $default_value = false) {
            return $default_value;
        }
        
        public static function redirect($url, $base_uri = __PS_BASE_URI__, $headers = null) {
            // Ne fait rien en mode test
        }
        
        public static function getIsset($key) {
            return false;
        }
        
        public static function truncate($string, $max_length = 125, $suffix = '...') {
            if (strlen($string) <= $max_length) {
                return $string;
            }
            return substr($string, 0, $max_length - strlen($suffix)) . $suffix;
        }
    }
}

// Mock de la classe Link pour les tests
if (!class_exists('Link', false)) {
    class Link {
        public function getProductLink($id_product, $rewrite = null, $id_category = null, $ean13 = null) {
            return 'http://example.com/product/' . $id_product;
        }
        
        public function getImageLink($name, $ids, $type = null) {
            return 'http://example.com/img/p/' . $ids . '.jpg';
        }
    }
}

// Mock de la classe PriceFormatter pour les tests
if (!class_exists('PriceFormatter', false)) {
    class PriceFormatter {
        public function format($price) {
            return number_format($price, 2, ',', ' ') . ' €';
        }
    }
}

// Chargement des fichiers nécessaires pour les tests
require_once _PS_ROOT_DIR_ . '/classes/IaBotLogger.php';
require_once _PS_ROOT_DIR_ . '/classes/IaBotException.php';

// Initialisation du logger en mode test
IaBotLogger::init(false);

// Message de démarrage des tests
echo "Bootstrap des tests unitaires IaBot chargé avec succès.\n";
