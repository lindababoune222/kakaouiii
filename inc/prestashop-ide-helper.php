<?php
/**
 * Fichier d'aide pour l'IDE
 * 
 * Ce fichier est destiné uniquement à l'IDE pour résoudre les erreurs de lint.
 * Il ne doit pas être inclus dans le code exécuté.
 * 
 * @author  Développeur
 * @copyright 2025
 * @license   Propriétaire
 */

// Constantes de PrestaShop
if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.8.0');
}

if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', dirname(__FILE__) . '/../');
}

if (!defined('_PS_IMG_DIR_')) {
    define('_PS_IMG_DIR_', dirname(__FILE__) . '/../img/');
}

if (!defined('_PS_DEV_MODE_')) {
    define('_PS_DEV_MODE_', true);
}

// Types de données pour les définitions de champs
if (!defined('TYPE_INT')) {
    define('TYPE_INT', 1);
}

if (!defined('TYPE_BOOL')) {
    define('TYPE_BOOL', 2);
}

if (!defined('TYPE_STRING')) {
    define('TYPE_STRING', 3);
}

if (!defined('TYPE_FLOAT')) {
    define('TYPE_FLOAT', 4);
}

if (!defined('TYPE_DATE')) {
    define('TYPE_DATE', 5);
}

if (!defined('TYPE_HTML')) {
    define('TYPE_HTML', 6);
}

if (!defined('TYPE_NOTHING')) {
    define('TYPE_NOTHING', 7);
}

if (!defined('TYPE_SQL')) {
    define('TYPE_SQL', 8);
}

// Définition de la classe ObjectModel
if (!class_exists('ObjectModel')) {
    /**
     * Classe de base pour les objets du modèle
     */
    class ObjectModel
    {
        // Types de données pour les définitions de champs
        const TYPE_INT = 1;
        const TYPE_BOOL = 2;
        const TYPE_STRING = 3;
        const TYPE_FLOAT = 4;
        const TYPE_DATE = 5;
        const TYPE_HTML = 6;
        const TYPE_NOTHING = 7;
        const TYPE_SQL = 8;
        
        /**
         * Ajoute l'objet à la base de données
         * 
         * @return bool Succès ou échec
         */
        public function add()
        {
            return true;
        }
        
        /**
         * Met à jour l'objet dans la base de données
         * 
         * @return bool Succès ou échec
         */
        public function update()
        {
            return true;
        }
        
        /**
         * Supprime l'objet de la base de données
         * 
         * @return bool Succès ou échec
         */
        public function delete()
        {
            return true;
        }
    }
}

// Classes de base de PrestaShop
if (!class_exists('Tab')) {
    /**
     * Classe de gestion des onglets d'administration de PrestaShop
     */
    class Tab extends ObjectModel
    {
        /** @var int ID de l'onglet parent */
        public $id_parent;
        
        /** @var string Nom de la classe associée à l'onglet */
        public $class_name;
        
        /** @var string Nom du module associé à l'onglet */
        public $module;
        
        /** @var int Position de l'onglet */
        public $position;
        
        /** @var int Indique si l'onglet est actif */
        public $active;
        
        /** @var int ID de l'onglet */
        public $id;
        
        /** @var string Nom de l'onglet */
        public $name;
        
        /**
         * Constructeur
         *
         * @param int|null $id ID de l'onglet
         * @param int|null $id_lang ID de la langue
         */
        public function __construct($id = null, $id_lang = null)
        {
            // Constructeur
        }
        
        /**
         * Ajoute un nouvel onglet
         * 
         * @return bool Succès de l'opération
         */
        public function add()
        {
            return true;
        }
        
        /**
         * Supprime l'onglet
         * 
         * @return bool Succès de l'opération
         */
        public function delete()
        {
            return true;
        }
        
        /**
         * Récupère l'ID d'un onglet à partir de son nom de classe
         * 
         * @param string $className Nom de la classe
         * @param int|null $idLang ID de la langue
         * @return int ID de l'onglet
         */
        public static function getIdFromClassName($className, $idLang = null)
        {
            return 1;
        }
    }
}

// Classe Tools pour les redirections
if (!class_exists('Tools')) {
    /**
     * Classe d'outils de PrestaShop
     */
    class Tools
    {
        /**
         * Redirige vers une URL
         * 
         * @param string $url URL de redirection
         * @param int $http_response_code Code de réponse HTTP
         */
        public static function redirect($url, $http_response_code = null)
        {
            // Redirection
        }
    }
}

// Classe Context pour accéder au contexte global
if (!class_exists('Context')) {
    /**
     * Classe de contexte global de PrestaShop
     */
    class Context
    {
        /** @var Link Objet de gestion des liens */
        public $link;
        
        /**
         * Récupère l'instance unique du contexte
         * 
         * @return Context Instance du contexte
         */
        public static function getContext()
        {
            static $instance = null;
            if ($instance === null) {
                $instance = new Context();
            }
            return $instance;
        }
    }
}

// Classe Link pour la gestion des liens
if (!class_exists('Link')) {
    /**
     * Classe de gestion des liens de PrestaShop
     */
    class Link
    {
        /**
         * Récupère un lien vers une page d'administration
         * 
         * @param string $controller Nom du contrôleur
         * @param bool $withToken Indique si le token doit être inclus
         * @param array $params Paramètres de l'URL
         * @return string URL de la page d'administration
         */
        public function getAdminLink($controller, $withToken = true, $params = [])
        {
            return '#';
        }
        
        /**
         * Récupère un lien vers un module front
         * 
         * @param string $module Nom du module
         * @param string $controller Nom du contrôleur
         * @param array $params Paramètres de l'URL
         * @return string URL du module
         */
        public function getModuleLink($module, $controller, $params = [])
        {
            return '#';
        }
        
        /**
         * Récupère un lien vers un produit
         * 
         * @param mixed $product Produit ou ID du produit
         * @param string $alias Alias du produit
         * @return string URL du produit
         */
        public function getProductLink($product, $alias = null)
        {
            return '#';
        }
    }
}

// Classe Db pour la gestion de la base de données
if (!class_exists('Db')) {
    /**
     * Classe de gestion de la base de données de PrestaShop
     */
    class Db
    {
        /**
         * Récupère l'instance unique de la base de données
         * 
         * @return Db Instance de la base de données
         */
        public static function getInstance()
        {
            static $instance = null;
            if ($instance === null) {
                $instance = new Db();
            }
            return $instance;
        }
        
        /**
         * Exécute une requête SQL et retourne plusieurs lignes
         * 
         * @param string $sql Requête SQL
         * @return array Résultat de la requête
         */
        public function executeS($sql)
        {
            return [];
        }
        
        /**
         * Exécute une requête SQL
         * 
         * @param string $sql Requête SQL
         * @return bool Succès de l'exécution
         */
        public function execute($sql)
        {
            return true;
        }
        
        /**
         * Récupère une valeur unique d'une requête SQL
         * 
         * @param string $sql Requête SQL
         * @return mixed Valeur récupérée
         */
        public function getValue($sql)
        {
            return 0;
        }
        
        /**
         * Récupère une ligne d'une requête SQL
         * 
         * @param string $sql Requête SQL
         * @return array Ligne récupérée
         */
        public function getRow($sql)
        {
            return [];
        }
        
        /**
         * Échappe une valeur pour l'utiliser dans une requête SQL
         * 
         * @param string $string Chaîne à échapper
         * @param bool $htmlOk Indique si le HTML est autorisé
         * @return string Chaîne échappée
         */
        public function escape($string, $htmlOk = false)
        {
            return $string;
        }
        
        /**
         * Insère des données dans une table
         * 
         * @param string $table Nom de la table
         * @param array $data Données à insérer
         * @return bool Succès de l'insertion
         */
        public function insert($table, $data)
        {
            return true;
        }
        
        /**
         * Met à jour des données dans une table
         * 
         * @param string $table Nom de la table
         * @param array $data Données à mettre à jour
         * @param string $where Condition WHERE
         * @return bool Succès de la mise à jour
         */
        public function update($table, $data, $where)
        {
            return true;
        }
    }
}

// Classe DbQuery pour la construction de requêtes SQL
if (!class_exists('DbQuery')) {
    /**
     * Classe de construction de requêtes SQL de PrestaShop
     */
    class DbQuery
    {
        /**
         * Constructeur
         */
        public function __construct()
        {
        }
        
        /**
         * Définit les colonnes à sélectionner
         * 
         * @param string $fields Colonnes à sélectionner
         * @return DbQuery Instance courante
         */
        public function select($fields)
        {
            return $this;
        }
        
        /**
         * Définit la table à utiliser
         * 
         * @param string $table Nom de la table
         * @param string|null $alias Alias de la table
         * @return DbQuery Instance courante
         */
        public function from($table, $alias = null)
        {
            return $this;
        }
        
        /**
         * Ajoute une condition WHERE
         * 
         * @param string $condition Condition WHERE
         * @return DbQuery Instance courante
         */
        public function where($condition)
        {
            return $this;
        }
        
        /**
         * Ajoute une jointure LEFT JOIN
         * 
         * @param string $table Nom de la table à joindre
         * @param string|null $alias Alias de la table
         * @param string|null $on Condition de jointure
         * @return DbQuery Instance courante
         */
        public function leftJoin($table, $alias = null, $on = null)
        {
            return $this;
        }
        
        /**
         * Définit l'ordre de tri
         * 
         * @param string $orderBy Ordre de tri
         * @return DbQuery Instance courante
         */
        public function orderBy($orderBy)
        {
            return $this;
        }
        
        /**
         * Définit la limite de résultats
         * 
         * @param int $limit Nombre maximum de résultats
         * @param int $offset Offset de départ
         * @return DbQuery Instance courante
         */
        public function limit($limit, $offset = 0)
        {
            return $this;
        }
    }
}

// Classe Product pour la gestion des produits
if (!class_exists('Product')) {
    /**
     * Classe de gestion des produits de PrestaShop
     */
    class Product extends ObjectModel
    {
        /** @var string Nom du produit */
        public $name;
        
        /** @var string Description du produit */
        public $description;
        
        /** @var string Description courte du produit */
        public $description_short;
        
        /** @var string Référence du produit */
        public $reference;
        
        /** @var float Prix du produit */
        public $price;
        
        /** @var int ID de la catégorie par défaut */
        public $id_category_default;
        
        /** @var bool Indique si le produit est actif */
        public $active;
        
        /**
         * Constructeur
         * 
         * @param int|null $id ID du produit
         * @param bool $full Indique si toutes les données doivent être chargées
         * @param int|null $id_lang ID de la langue
         */
        public function __construct($id = null, $full = false, $id_lang = null)
        {
        }
        
        /**
         * Récupère le prix du produit
         * 
         * @return float Prix du produit
         */
        public function getPrice()
        {
            return 0.0;
        }
        
        /**
         * Récupère tous les produits
         * 
         * @param int $id_lang ID de la langue
         * @param int $start Offset de départ
         * @param int $limit Nombre maximum de résultats
         * @param string $order_by Colonne de tri
         * @param string $order_way Sens du tri
         * @param bool $only_active Indique si seuls les produits actifs doivent être retournés
         * @param bool $context Indique si le contexte doit être utilisé
         * @return array Liste des produits
         */
        public static function getProducts($id_lang, $start, $limit, $order_by, $order_way, $only_active = false, $context = true)
        {
            return [];
        }
    }
}

// Classe Validate pour la validation des données
if (!class_exists('Validate')) {
    /**
     * Classe de validation des données de PrestaShop
     */
    class Validate
    {
        /**
         * Vérifie si un objet est correctement chargé
         * 
         * @param mixed $object Objet à vérifier
         * @return bool Indique si l'objet est correctement chargé
         */
        public static function isLoadedObject($object)
        {
            return true;
        }
    }
}

// Classe ModuleFrontController pour les contrôleurs front-office des modules
if (!class_exists('ModuleFrontController')) {
    /**
     * Classe de base pour les contrôleurs front-office des modules de PrestaShop
     */
    class ModuleFrontController
    {
        /** @var Context Contexte de PrestaShop */
        public $context;
        
        /** @var bool Utilisation de SSL */
        public $ssl = false;
        
        /**
         * Initialisation du contrôleur
         */
        public function init()
        {
        }
        
        /**
         * Génère un mot de passe aléatoire
         * 
         * @param int $length Longueur du mot de passe
         * @return string Mot de passe généré
         */
        public function passwdGen($length = 8)
        {
            return '';
        }
        
        /**
         * Récupère l'adresse IP du client
         * 
         * @return string Adresse IP du client
         */
        public function getRemoteAddr()
        {
            return '127.0.0.1';
        }
    }
}

// Classe ModuleAdminController pour les contrôleurs back-office des modules
if (!class_exists('ModuleAdminController')) {
    /**
     * Classe de base pour les contrôleurs back-office des modules de PrestaShop
     */
    class ModuleAdminController
    {
        /** @var bool Utilisation de Bootstrap */
        public $bootstrap = true;
        
        /** @var string Type d'affichage */
        public $display = 'view';
        
        /** @var string Titre de la page */
        public $meta_title = '';
        
        /** @var Module Instance du module */
        public $module;
        
        /** @var Context Contexte de PrestaShop */
        public $context;
        
        /** @var string Contenu de la page */
        public $content;
        
        /**
         * Constructeur
         */
        public function __construct()
        {
        }
        
        /**
         * Initialisation du contenu
         */
        public function initContent()
        {
        }
        
        /**
         * Redirige vers une page d'administration
         * 
         * @param string $url URL de redirection
         */
        public function redirectAdmin($url)
        {
        }
        
        /**
         * Définit le template à utiliser
         * 
         * @param string $template Chemin vers le template
         */
        public function setTemplate($template)
        {
        }
    }
}

// Classe Module pour la gestion des modules
if (!class_exists('Module')) {
    /**
     * Classe de base pour les modules de PrestaShop
     */
    class Module
    {
        /** @var string Nom du module */
        public $name;
        
        /** @var bool Indique si le module est actif */
        public $active = true;
        
        /**
         * Récupère une instance d'un module par son nom
         * 
         * @param string $name Nom du module
         * @return Module Instance du module
         */
        public static function getInstanceByName($name)
        {
            return new Module();
        }
        
        /**
         * Récupère le chemin du module
         * 
         * @return string Chemin du module
         */
        public function getPathUri()
        {
            return '';
        }
        
        /**
         * Récupère le chemin local du module
         * 
         * @return string Chemin local du module
         */
        public function getLocalPath()
        {
            return '';
        }
    }
}

// Fonction pour échapper les chaînes SQL
if (!function_exists('pSQL')) {
    /**
     * Échappe une chaîne pour l'utiliser dans une requête SQL
     * 
     * @param string $string Chaîne à échapper
     * @param bool $htmlOk Indique si le HTML est autorisé
     * @return string Chaîne échappée
     */
    function pSQL($string, $htmlOk = false)
    {
        return $string;
    }
}
?>
