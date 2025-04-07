<?php
/**
 * Classe IaBotProductIndex
 * 
 * Gère l'indexation des produits pour le chatbot
 * 
 * @author  Développeur
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Classe pour l'indexation des produits
 */
class IaBotProductIndex extends ObjectModel
{
    /** @var int ID du produit */
    public $id_product;
    
    /** @var string Nom du produit */
    public $name;
    
    /** @var string Description complète du produit */
    public $description;
    
    /** @var string Description courte du produit */
    public $short_description;
    
    /** @var string Référence du produit */
    public $reference;
    
    /** @var float Prix du produit */
    public $price;
    
    /** @var string Lien vers la page du produit */
    public $link;
    
    /** @var string Date de création */
    public $date_add;
    
    /** @var string Date de mise à jour */
    public $date_upd;
    
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'iabot_product_index',
        'primary' => 'id_product_index',
        'fields' => [
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'description' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'short_description' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 64],
            'price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'link' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 255],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
    
    /**
     * Recherche des produits dans l'index
     * 
     * @param string $query Requête de recherche
     * @param int $limit Nombre maximum de résultats
     * @param int $offset Offset pour la pagination
     * @return array Résultats de la recherche
     */
    public static function search($query, $limit = 5, $offset = 0)
    {
        $db = Db::getInstance();
        $searchTerms = explode(' ', trim($query));
        
        // Construction de la requête SQL avec recherche par mots-clés
        $searchConditions = [];
        foreach ($searchTerms as $term) {
            if (strlen($term) < 3) {
                continue; // Ignorer les termes trop courts
            }
            $term = $db->escape($term, false);
            $searchConditions[] = "i.name LIKE '%{$term}%'";
            $searchConditions[] = "i.description LIKE '%{$term}%'";
            $searchConditions[] = "i.short_description LIKE '%{$term}%'";
            $searchConditions[] = "i.reference LIKE '%{$term}%'";
        }
        
        $searchWhere = !empty($searchConditions) ? ' AND (' . implode(' OR ', $searchConditions) . ')' : '';
        
        // Exécution de la requête SQL directement
        $sql = 'SELECT i.*, p.id_category_default 
                FROM `' . _DB_PREFIX_ . 'iabot_product_index` i
                LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON i.id_product = p.id_product
                WHERE p.active = 1' . $searchWhere . '
                ORDER BY i.name ASC
                LIMIT ' . (int)$offset . ', ' . (int)$limit;
        
        return $db->executeS($sql);
    }
    
    /**
     * Récupère un produit indexé par son ID
     * 
     * @param int $idProduct ID du produit
     * @return array|false Données du produit ou false si non trouvé
     */
    public static function getByProductId($idProduct)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('iabot_product_index');
        $sql->where('id_product = ' . (int)$idProduct);
        
        $result = $db->executeS($sql);
        return $result && count($result) > 0 ? $result[0] : false;
    }
    
    /**
     * Supprime un produit de l'index
     * 
     * @param int $idProduct ID du produit à supprimer
     * @return bool Succès de l'opération
     */
    public static function deleteByProductId($idProduct)
    {
        return Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'iabot_product_index` WHERE `id_product` = ' . (int)$idProduct
        );
    }
    
    /**
     * Compte le nombre de produits indexés
     * 
     * @return int Nombre de produits indexés
     */
    public static function countIndexedProducts()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('COUNT(*)');
        $sql->from('iabot_product_index');
        
        $result = $db->executeS($sql);
        return $result && isset($result[0]['COUNT(*)']) ? (int)$result[0]['COUNT(*)'] : 0;
    }
}
