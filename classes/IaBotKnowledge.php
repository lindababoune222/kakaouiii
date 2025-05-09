<?php
/**
 * Classe de gestion de la base de connaissances
 * 
 * @author  Mike
 * @copyright 2025
 */

// Inclusion du fichier d'aide IDE pour la complétion
if (defined('_PS_DEV_MODE_') && _PS_DEV_MODE_) {
    require_once dirname(__FILE__) . '/../inc/prestashop-ide-helper.php';
}

require_once dirname(__FILE__) . '/IaBotLogger.php';
require_once dirname(__FILE__) . '/IaBotException.php';

/**
 * Classe de gestion de la base de connaissances
 */
class IaBotKnowledge extends ObjectModel
{
    /** @var int ID de l'entrée de la base de connaissances */
    public $id_knowledge;
    
    /** @var int ID de référence (produit, catégorie, etc.) */
    public $id_reference;
    
    /** @var string Type de référence (product, category, etc.) */
    public $reference_type;
    
    /** @var int ID de la langue */
    public $id_lang;
    
    /** @var string Titre de l'entrée */
    public $title;
    
    /** @var string Catégorie de la connaissance */
    public $category;
    
    /** @var string Contenu de l'entrée */
    public $content;
    
    /** @var string Mots-clés associés à la connaissance */
    public $keywords;
    
    /** @var bool État actif de la connaissance */
    public $active = true;
    
    /** @var string Date de création */
    public $date_add;
    
    /** @var string Date de dernière mise à jour */
    public $date_upd;
    
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'iabot_knowledge',
        'primary' => 'id_knowledge',
        'fields' => [
            'id_reference' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false, 'default' => 0],
            'reference_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 32, 'default' => ''],
            'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false, 'default' => 0],
            'title' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'category' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'content' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true],
            'keywords' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 255],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];
    
    /**
     * Recherche des entrées de connaissances par mot-clé avec gestion d'erreurs
     * 
     * @param string $keyword Mot-clé à rechercher
     * @param int $idLang ID de la langue (0 pour toutes les langues)
     * @param int $limit Nombre maximum de résultats
     * @return array Liste des entrées de connaissances
     * @throws IaBotException En cas d'erreur
     */
    public static function searchByKeyword($keyword, $idLang = 0, $limit = 5)
    {
        try {
            // Validation des paramètres
            if (empty($keyword)) {
                throw IaBotException::validationError('Mot-clé de recherche vide');
            }
            
            // Nettoyage du mot-clé
            $keyword = trim(pSQL($keyword));
            
            $query = new DbQuery();
            $query->select('k.*')
                  ->from(self::$definition['table'], 'k');
            
            // Filtrage par langue si spécifié
            if ($idLang > 0) {
                $query->where('k.id_lang = ' . (int)$idLang);
            }
            
            // Ne retourner que les connaissances actives
            $query->where('k.active = 1');
            
            // Recherche dans le titre, le contenu et les mots-clés
            $query->where('(k.title LIKE \'%' . $keyword . '%\' OR k.content LIKE \'%' . $keyword . '%\' OR k.keywords LIKE \'%' . $keyword . '%\')');
            
            // Limitation du nombre de résultats
            if ($limit > 0) {
                $query->limit((int)$limit);
            }
            
            $results = Db::getInstance()->executeS($query);
            
            if (!$results) {
                return [];
            }
            
            // Journalisation du résultat
            IaBotLogger::debug('Recherche dans la base de connaissances', [
                'keyword' => $keyword,
                'id_lang' => $idLang,
                'count' => count($results)
            ]);
            
            return $results;
        } catch (IaBotException $e) {
            // L'exception a déjà été journalisée
            throw $e;
        } catch (Exception $e) {
            // Journalisation et conversion de l'exception
            $exception = new IaBotException(
                'Erreur lors de la recherche dans la base de connaissances: ' . $e->getMessage(),
                'IABOT_KNOWLEDGE_ERROR',
                [
                    'keyword' => $keyword,
                    'id_lang' => $idLang
                ],
                true,
                $e->getCode(),
                $e
            );
            
            throw $exception;
        }
    }
    
    /**
     * Ajoute une entrée à la base de connaissances avec gestion d'erreurs
     * 
     * @param string $title Titre de l'entrée
     * @param string $category Catégorie de l'entrée
     * @param string $content Contenu de l'entrée
     * @param string $keywords Mots-clés associés
     * @param bool $active État actif
     * @param int $idReference ID de référence (optionnel)
     * @param string $referenceType Type de référence (optionnel)
     * @param int $idLang ID de la langue (optionnel)
     * @return IaBotKnowledge Instance de l'entrée créée
     * @throws IaBotException En cas d'erreur
     */
    public static function addKnowledge($title, $category, $content, $keywords = '', $active = true, $idReference = 0, $referenceType = '', $idLang = 0)
    {
        try {
            // Validation des paramètres
            if (empty($title) || strlen($title) > 255) {
                throw IaBotException::validationError('Titre invalide', ['title' => $title]);
            }
            
            if (empty($category) || strlen($category) > 128) {
                throw IaBotException::validationError('Catégorie invalide', ['category' => $category]);
            }
            
            if (empty($content)) {
                throw IaBotException::validationError('Contenu vide');
            }
            
            // Nettoyage du contenu HTML
            if (!Validate::isCleanHtml($content)) {
                throw IaBotException::validationError('Le contenu contient du HTML non valide');
            }
            
            // Création de l'entrée
            $knowledge = new self();
            $knowledge->title = pSQL($title);
            $knowledge->category = pSQL($category);
            $knowledge->content = $content;
            $knowledge->keywords = pSQL($keywords);
            $knowledge->active = (bool)$active;
            $knowledge->id_reference = (int)$idReference;
            $knowledge->reference_type = pSQL($referenceType);
            $knowledge->id_lang = (int)$idLang;
            $knowledge->date_add = date('Y-m-d H:i:s');
            $knowledge->date_upd = date('Y-m-d H:i:s');
            
            // Enregistrement
            if (!$knowledge->add()) {
                throw IaBotException::databaseError('Erreur lors de l\'ajout de l\'entrée à la base de connaissances');
            }
            
            // Journalisation du succès
            IaBotLogger::info('Nouvelle entrée ajoutée à la base de connaissances', [
                'id_knowledge' => $knowledge->id_knowledge,
                'title' => $title,
                'category' => $category
            ]);
            
            return $knowledge;
        } catch (IaBotException $e) {
            // L'exception a déjà été journalisée
            throw $e;
        } catch (Exception $e) {
            // Journalisation et conversion de l'exception
            $exception = new IaBotException(
                'Erreur lors de l\'ajout à la base de connaissances: ' . $e->getMessage(),
                'IABOT_KNOWLEDGE_ERROR',
                [
                    'title' => $title,
                    'category' => $category
                ],
                true,
                $e->getCode(),
                $e
            );
            
            throw $exception;
        }
    }
    
    /**
     * Supprime une entrée de la base de connaissances avec gestion d'erreurs
     * 
     * @param int $idReference ID de référence
     * @param string $referenceType Type de référence
     * @param int $idLang ID de la langue (0 pour toutes les langues)
     * @return bool Succès ou échec
     * @throws IaBotException En cas d'erreur
     */
    public static function deleteKnowledge($idReference, $referenceType, $idLang = 0)
    {
        try {
            // Validation des paramètres
            if (!Validate::isUnsignedId($idReference)) {
                throw IaBotException::validationError('ID de référence invalide', ['id_reference' => $idReference]);
            }
            
            if (empty($referenceType)) {
                throw IaBotException::validationError('Type de référence invalide', ['reference_type' => $referenceType]);
            }
            
            // Recherche de l'entrée
            $query = new DbQuery();
            $query->select('k.*')
                  ->from(self::$definition['table'], 'k')
                  ->where('k.id_reference = ' . (int)$idReference)
                  ->where('k.reference_type = \'' . pSQL($referenceType) . '\'');
            
            if ($idLang > 0) {
                $query->where('k.id_lang = ' . (int)$idLang);
            }
            
            $knowledge = Db::getInstance()->getRow($query);
            
            if (!$knowledge) {
                throw IaBotException::notFoundError('Entrée non trouvée', ['id_reference' => $idReference, 'reference_type' => $referenceType]);
            }
            
            // Suppression de l'entrée
            if (!Db::getInstance()->delete(self::$definition['table'], 'id_knowledge = ' . (int)$knowledge['id_knowledge'])) {
                throw IaBotException::databaseError('Erreur lors de la suppression de l\'entrée de la base de connaissances');
            }
            
            // Journalisation du succès
            IaBotLogger::info('Entrée supprimée de la base de connaissances', [
                'id_knowledge' => $knowledge['id_knowledge'],
                'id_reference' => $idReference,
                'reference_type' => $referenceType
            ]);
            
            return true;
        } catch (IaBotException $e) {
            // L'exception a déjà été journalisée
            throw $e;
        } catch (Exception $e) {
            // Journalisation et conversion de l'exception
            $exception = new IaBotException(
                'Erreur lors de la suppression de l\'entrée de la base de connaissances: ' . $e->getMessage(),
                'IABOT_KNOWLEDGE_ERROR',
                [
                    'id_reference' => $idReference,
                    'reference_type' => $referenceType,
                    'id_lang' => $idLang
                ],
                true,
                $e->getCode(),
                $e
            );
            
            throw $exception;
        }
    }
    
    /**
     * Indexe tous les produits et catégories dans la base de connaissances avec gestion d'erreurs
     * 
     * @return int Nombre d'éléments indexés
     * @throws IaBotException En cas d'erreur
     */
    public static function indexAll()
    {
        try {
            $count = 0;
            
            // Indexer les produits
            $products = Product::getProducts(Context::getContext()->language->id, 0, 0, 'id_product', 'ASC', false, true);
            
            foreach ($products as $productInfo) {
                $product = new Product((int)$productInfo['id_product'], true);
                
                if (Validate::isLoadedObject($product)) {
                    $languages = Language::getLanguages(false);
                    
                    foreach ($languages as $language) {
                        $langId = (int)$language['id_lang'];
                        
                        // Préparer le contenu à indexer
                        $content = $product->name[$langId] . ' ' . 
                                  strip_tags($product->description_short[$langId]) . ' ' . 
                                  strip_tags($product->description[$langId]);
                        
                        // Ajouter à la base de connaissances
                        if (self::addKnowledge(
                            $product->name[$langId],
                            'Produit',
                            $content,
                            '',
                            true,
                            (int)$product->id,
                            'product',
                            $langId
                        )) {
                            $count++;
                        }
                    }
                }
            }
            
            // Indexer les catégories
            $categories = Category::getCategories(false, true, false);
            
            foreach ($categories as $categoryInfo) {
                if (isset($categoryInfo['id_category']) && $categoryInfo['id_category'] > 1) { // Ignorer la catégorie racine
                    $category = new Category((int)$categoryInfo['id_category'], true);
                    
                    if (Validate::isLoadedObject($category)) {
                        $languages = Language::getLanguages(false);
                        
                        foreach ($languages as $language) {
                            $langId = (int)$language['id_lang'];
                            
                            // Préparer le contenu à indexer
                            $content = $category->name[$langId] . ' ' . strip_tags($category->description[$langId]);
                            
                            // Ajouter à la base de connaissances
                            if (self::addKnowledge(
                                $category->name[$langId],
                                'Catégorie',
                                $content,
                                '',
                                true,
                                (int)$category->id,
                                'category',
                                $langId
                            )) {
                                $count++;
                            }
                        }
                    }
                }
            }
            
            // Journalisation du succès
            IaBotLogger::info('Indexation des produits et catégories terminée', ['count' => $count]);
            
            return $count;
        } catch (IaBotException $e) {
            // L'exception a déjà été journalisée
            throw $e;
        } catch (Exception $e) {
            // Journalisation et conversion de l'exception
            $exception = new IaBotException(
                'Erreur lors de l\'indexation des produits et catégories: ' . $e->getMessage(),
                'IABOT_KNOWLEDGE_ERROR',
                [],
                true,
                $e->getCode(),
                $e
            );
            
            throw $exception;
        }
    }
}
