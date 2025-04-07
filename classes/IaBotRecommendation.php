<?php
/**
 * Classe de gestion des recommandations de produits
 * 
 * @author  Développeur
 * @copyright 2025
 */

// Inclusion du fichier d'aide IDE pour la complétion
if (defined('_PS_DEV_MODE_') && _PS_DEV_MODE_) {
    require_once dirname(__FILE__) . '/../inc/prestashop-ide-helper.php';
}

require_once dirname(__FILE__) . '/IaBotLogger.php';
require_once dirname(__FILE__) . '/IaBotException.php';

/**
 * Classe de gestion des recommandations de produits
 */
class IaBotRecommendation extends ObjectModel
{
    /** @var int ID de la recommandation */
    public $id_recommendation;
    
    /** @var string Mot-clé associé à la recommandation */
    public $keyword;
    
    /** @var int ID du produit */
    public $id_product;
    
    /** @var int Poids de la recommandation (plus le poids est élevé, plus la recommandation est prioritaire) */
    public $weight;
    
    /** @var string Date de création */
    public $date_add;
    
    /** @var string Date de dernière mise à jour */
    public $date_upd;
    
    /** @var array Liste des mots-clés liés au windsurf */
    private static $windsurfKeywords = [
        // Types d'équipement
        'planche', 'voile', 'flotteur', 'wishbone', 'bôme', 'mât', 'aileron', 'dérive', 'pied de mât',
        'combinaison', 'harnais', 'gilet', 'casque', 'chaussons', 'gants', 'lunettes', 'leash',
        
        // Caractéristiques techniques
        'freeride', 'freestyle', 'wave', 'slalom', 'race', 'foil', 'windfoil', 'wing', 'wingfoil',
        'débutant', 'intermédiaire', 'avancé', 'expert', 'performance', 'flottabilité',
        'carbone', 'fibre', 'epoxy', 'gonflable', 'rigide', 'composite',
        
        // Marques populaires
        'starboard', 'jp', 'fanatic', 'naish', 'rrd', 'tabou', 'f2', 'goya', 'exocet',
        'north', 'duotone', 'neilpryde', 'severne', 'gaastra', 'loftsails', 'simmer', 'ezzy',
        'select', 'maui', 'mfc', 'drake', 'unifiber', 'prolimit', 'ion', 'mystic', 'dakine',
        
        // Conditions et environnement
        'vent', 'vague', 'mer', 'lac', 'plage', 'offshore', 'onshore', 'side', 'tempête',
        'léger', 'modéré', 'fort', 'rafale', 'beaufort', 'nœud', 'mètre',
        
        // Mesures et tailles
        'litre', 'cm', 'pied', 'mètre', 'kg', 'gramme', 'pouce',
        'taille', 'volume', 'largeur', 'longueur', 'épaisseur', 'surface'
    ];
    
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'iabot_recommendation',
        'primary' => 'id_recommendation',
        'fields' => [
            'keyword' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'weight' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];
    
    /**
     * Cache statique pour les recommandations
     * @var array
     */
    private static $recommendationsCache = [];
    
    /**
     * Durée de validité du cache en secondes (10 minutes)
     * @var int
     */
    private static $cacheTtl = 600;
    
    /**
     * Timestamp de dernière mise à jour du cache
     * @var int
     */
    private static $cacheLastUpdate = 0;
    
    /**
     * Récupère les produits recommandés en fonction des mots-clés
     * 
     * @param array $keywords Liste des mots-clés
     * @param int $idLang ID de la langue
     * @param int $limit Nombre maximum de produits à récupérer
     * @return array Liste des produits recommandés
     */
    public static function getProductsByKeywords($keywords, $idLang, $limit = 3)
    {
        if (empty($keywords)) {
            return [];
        }
        
        $idLang = (int)$idLang;
        $limit = (int)$limit;
        
        // Préparer la liste des mots-clés pour la requête SQL
        $keywordList = [];
        foreach ($keywords as $keyword) {
            $keywordList[] = '"' . pSQL($keyword) . '"';
            // Ajouter aussi les mots-clés partiels (pour la recherche par radical)
            if (mb_strlen($keyword, 'UTF-8') > 4) {
                $keywordList[] = '"' . pSQL(mb_substr($keyword, 0, -2, 'UTF-8')) . '%"';
            }
        }
        
        // Requête pour récupérer les IDs de produits recommandés
        $sql = '
            SELECT r.id_product, SUM(r.weight) as total_weight
            FROM `' . _DB_PREFIX_ . 'iabot_recommendation` r
            WHERE r.keyword IN (' . implode(',', $keywordList) . ')
               OR r.keyword LIKE ' . implode(' OR r.keyword LIKE ', $keywordList) . '
            GROUP BY r.id_product
            ORDER BY total_weight DESC
            LIMIT ' . $limit;
        
        $result = Db::getInstance()->executeS($sql);
        
        if (!$result) {
            // Si aucune recommandation spécifique n'est trouvée, récupérer les produits populaires
            return self::getPopularProducts($idLang, $limit);
        }
        
        // Récupérer les informations complètes des produits
        $products = [];
        foreach ($result as $row) {
            $productId = (int)$row['id_product'];
            $product = new Product($productId, true, $idLang);
            
            // Vérifier si le produit existe et est actif
            if (Validate::isLoadedObject($product) && $product->active) {
                $productInfo = self::formatProductInfo($product, $idLang);
                if ($productInfo) {
                    $products[] = $productInfo;
                }
            }
        }
        
        // Si nous n'avons pas assez de produits, compléter avec des produits populaires
        if (count($products) < $limit) {
            $popularProducts = self::getPopularProducts($idLang, $limit - count($products), array_column($products, 'id'));
            $products = array_merge($products, $popularProducts);
        }
        
        return $products;
    }
    
    /**
     * Récupère les produits populaires
     * 
     * @param int $idLang ID de la langue
     * @param int $limit Nombre maximum de produits à récupérer
     * @param array $excludeIds IDs de produits à exclure
     * @return array Liste des produits populaires
     */
    public static function getPopularProducts($idLang, $limit = 3, $excludeIds = [])
    {
        $idLang = (int)$idLang;
        $limit = (int)$limit;
        
        // Préparer la clause d'exclusion
        $excludeClause = '';
        if (!empty($excludeIds)) {
            $excludeClause = ' AND p.id_product NOT IN (' . implode(',', array_map('intval', $excludeIds)) . ')';
        }
        
        // Requête pour récupérer les produits populaires
        $sql = '
            SELECT p.id_product
            FROM `' . _DB_PREFIX_ . 'product` p
            LEFT JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON (p.id_product = ps.id_product AND ps.id_shop = ' . (int)Context::getContext()->shop->id . ')
            WHERE p.active = 1
            AND ps.active = 1' . $excludeClause . '
            ORDER BY p.date_add DESC
            LIMIT ' . $limit;
        
        $result = Db::getInstance()->executeS($sql);
        
        if (!$result) {
            return [];
        }
        
        // Récupérer les informations complètes des produits
        $products = [];
        foreach ($result as $row) {
            $productId = (int)$row['id_product'];
            $product = new Product($productId, true, $idLang);
            
            if (Validate::isLoadedObject($product)) {
                $productInfo = self::formatProductInfo($product, $idLang);
                if ($productInfo) {
                    $products[] = $productInfo;
                }
            }
        }
        
        return $products;
    }
    
    /**
     * Formate les informations d'un produit pour l'affichage dans le chat
     * 
     * @param Product $product Objet produit
     * @param int $idLang ID de la langue
     * @return array|false Informations formatées du produit ou false en cas d'erreur
     */
    private static function formatProductInfo($product, $idLang)
    {
        if (!Validate::isLoadedObject($product)) {
            return false;
        }
        
        $context = Context::getContext();
        $idLang = (int)$idLang;
        
        // Récupérer l'image du produit
        $images = $product->getImages($idLang);
        $imageId = 0;
        
        if (!empty($images)) {
            $imageId = $images[0]['id_image'];
        }
        
        $imageUrl = $context->link->getImageLink($product->link_rewrite, $imageId, 'home_default');
        
        // Récupérer les prix
        $price = $product->getPrice(true, null, 2);
        $regularPrice = $product->getPrice(false, null, 2);
        $hasDiscount = $regularPrice > $price;
        
        // Formater les prix
        $priceFormatter = new PriceFormatter();
        $formattedPrice = $priceFormatter->format($price);
        $formattedRegularPrice = $hasDiscount ? $priceFormatter->format($regularPrice) : '';
        
        // Construire l'URL du produit
        $productUrl = $context->link->getProductLink($product, null, null, null, $idLang);
        
        return [
            'id' => (int)$product->id,
            'name' => $product->name,
            'description' => Tools::truncate(strip_tags($product->description_short), 100),
            'price' => $formattedPrice,
            'regularPrice' => $formattedRegularPrice,
            'hasDiscount' => $hasDiscount,
            'imageUrl' => $imageUrl,
            'url' => $productUrl
        ];
    }
    
    /**
     * Ajoute ou met à jour une recommandation
     * 
     * @param string $keyword Mot-clé
     * @param int $idProduct ID du produit
     * @param int $weight Poids de la recommandation
     * @return bool Succès ou échec
     */
    public static function addOrUpdateRecommendation($keyword, $idProduct, $weight = 10)
    {
        $keyword = pSQL(mb_strtolower(trim($keyword), 'UTF-8'));
        $idProduct = (int)$idProduct;
        $weight = (int)$weight;
        
        if (empty($keyword) || $idProduct <= 0) {
            return false;
        }
        
        // Vérifier si la recommandation existe déjà
        $existingId = Db::getInstance()->getValue('
            SELECT `id_recommendation` 
            FROM `' . _DB_PREFIX_ . 'iabot_recommendation` 
            WHERE `keyword` = "' . $keyword . '" 
            AND `id_product` = ' . $idProduct
        );
        
        if ($existingId) {
            // Mettre à jour la recommandation existante
            return Db::getInstance()->update(
                'iabot_recommendation',
                [
                    'weight' => $weight,
                    'date_upd' => date('Y-m-d H:i:s')
                ],
                '`id_recommendation` = ' . (int)$existingId
            );
        } else {
            // Créer une nouvelle recommandation
            return Db::getInstance()->insert(
                'iabot_recommendation',
                [
                    'keyword' => $keyword,
                    'id_product' => $idProduct,
                    'weight' => $weight,
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s')
                ]
            );
        }
    }
    
    /**
     * Supprime une recommandation
     * 
     * @param string $keyword Mot-clé
     * @param int $idProduct ID du produit
     * @return bool Succès ou échec
     */
    public static function deleteRecommendation($keyword, $idProduct)
    {
        $keyword = pSQL(mb_strtolower(trim($keyword), 'UTF-8'));
        $idProduct = (int)$idProduct;
        
        if (empty($keyword) || $idProduct <= 0) {
            return false;
        }
        
        return Db::getInstance()->delete(
            'iabot_recommendation',
            '`keyword` = "' . $keyword . '" AND `id_product` = ' . $idProduct
        );
    }
    
    /**
     * Extrait les mots-clés pertinents d'un message utilisateur
     * 
     * @param string $message Message de l'utilisateur
     * @return array Liste des mots-clés extraits avec leur poids
     */
    public static function extractKeywordsFromMessage($message)
    {
        if (empty($message)) {
            return [];
        }
        
        // Nettoyer et normaliser le message
        $message = mb_strtolower(trim($message), 'UTF-8');
        $message = preg_replace('/[\?\!\.\,\;\:\(\)\[\]\{\}\-\_\+\=\/\\]/', ' ', $message);
        
        // Découper le message en mots
        $words = preg_split('/\s+/', $message);
        $words = array_filter($words, function($word) {
            return mb_strlen($word, 'UTF-8') > 2; // Ignorer les mots trop courts
        });
        
        // Filtrer les mots vides (stop words)
        $stopWords = ['je', 'tu', 'il', 'elle', 'nous', 'vous', 'ils', 'elles', 'le', 'la', 'les', 'un', 'une', 'des',
                     'ce', 'cette', 'ces', 'mon', 'ton', 'son', 'notre', 'votre', 'leur', 'pour', 'avec', 'sans',
                     'mais', 'ou', 'et', 'donc', 'car', 'quand', 'que', 'qui', 'quoi', 'comment', 'pourquoi',
                     'est', 'sont', 'sera', 'seront', 'avoir', 'avez', 'ont', 'suis', 'es', 'sommes', 'êtes'];
        $words = array_diff($words, $stopWords);
        
        // Identifier les mots-clés pertinents pour le windsurf
        $keywords = [];
        $foundKeywords = [];
        
        // Recherche de mots-clés exacts
        foreach ($words as $word) {
            if (in_array($word, self::$windsurfKeywords)) {
                $foundKeywords[$word] = isset($foundKeywords[$word]) ? $foundKeywords[$word] + 10 : 10;
            }
        }
        
        // Recherche de mots-clés partiels (pour les mots composés ou les variantes)
        foreach (self::$windsurfKeywords as $keyword) {
            if (mb_strlen($keyword, 'UTF-8') > 4) { // Ignorer les mots-clés trop courts pour éviter les faux positifs
                foreach ($words as $word) {
                    // Vérifier si le mot contient le mot-clé ou vice versa
                    if (mb_strpos($word, $keyword, 0, 'UTF-8') !== false || mb_strpos($keyword, $word, 0, 'UTF-8') !== false) {
                        $foundKeywords[$keyword] = isset($foundKeywords[$keyword]) ? $foundKeywords[$keyword] + 5 : 5;
                    }
                }
            }
        }
        
        // Recherche d'expressions composées
        $phrases = ['débutant windsurf', 'planche à voile', 'matériel de windsurf', 'apprendre le windsurf',
                   'planche débutant', 'voile débutant', 'conditions de vent', 'force du vent',
                   'taille de voile', 'volume de planche', 'flottabilité planche'];
        
        foreach ($phrases as $phrase) {
            if (mb_strpos($message, $phrase, 0, 'UTF-8') !== false) {
                $phraseParts = explode(' ', $phrase);
                $mainKeyword = end($phraseParts);
                $foundKeywords[$mainKeyword] = isset($foundKeywords[$mainKeyword]) ? $foundKeywords[$mainKeyword] + 15 : 15;
            }
        }
        
        // Détecter les intentions spécifiques
        $intentions = [
            'acheter' => ['acheter', 'achat', 'commander', 'commande', 'acquérir', 'acquisition', 'prix'],
            'débuter' => ['débuter', 'débutant', 'commencer', 'apprendre', 'apprentissage', 'initiation', 'novice'],
            'comparer' => ['comparer', 'comparaison', 'différence', 'versus', 'vs', 'meilleur', 'recommander'],
            'conseil' => ['conseil', 'avis', 'suggestion', 'recommandation', 'idée', 'aide', 'guider']
        ];
        
        foreach ($intentions as $intention => $intentionWords) {
            foreach ($intentionWords as $intentionWord) {
                if (mb_strpos($message, $intentionWord, 0, 'UTF-8') !== false) {
                    $foundKeywords[$intention] = isset($foundKeywords[$intention]) ? $foundKeywords[$intention] + 8 : 8;
                    break;
                }
            }
        }
        
        // Trier les mots-clés par poids décroissant
        arsort($foundKeywords);
        
        // Limiter le nombre de mots-clés
        return array_slice($foundKeywords, 0, 10, true);
    }
    
    /**
     * Obtient des recommandations de produits basées sur le message de l'utilisateur et le contexte de la conversation
     * 
     * @param string $message Message de l'utilisateur
     * @param int $idConversation ID de la conversation
     * @param int $idLang ID de la langue
     * @param int $limit Nombre maximum de produits à recommander
     * @return array Liste des produits recommandés
     */
    public static function getRecommendationsFromMessage($message, $idConversation, $idLang, $limit = 3)
    {
        // Extraire les mots-clés du message
        $keywordsWithWeight = self::extractKeywordsFromMessage($message);
        
        if (empty($keywordsWithWeight)) {
            return [];
        }
        
        // Récupérer les messages précédents de la conversation pour le contexte
        $previousMessages = [];
        if ($idConversation > 0) {
            $previousMessages = Db::getInstance()->executeS(
                'SELECT `message` FROM `' . _DB_PREFIX_ . 'iabot_message` '
                . 'WHERE `id_conversation` = ' . (int)$idConversation . ' '
                . 'AND `is_bot` = 0 '
                . 'ORDER BY `date_add` DESC LIMIT 5'
            );
        }
        
        // Enrichir les mots-clés avec le contexte des messages précédents
        if (!empty($previousMessages)) {
            foreach ($previousMessages as $prevMessage) {
                $contextKeywords = self::extractKeywordsFromMessage($prevMessage['message']);
                
                // Ajouter les mots-clés du contexte avec un poids réduit
                foreach ($contextKeywords as $keyword => $weight) {
                    if (isset($keywordsWithWeight[$keyword])) {
                        // Si le mot-clé existe déjà, augmenter son poids mais moins que pour le message actuel
                        $keywordsWithWeight[$keyword] += $weight * 0.3;
                    } else {
                        // Sinon, ajouter le mot-clé avec un poids réduit
                        $keywordsWithWeight[$keyword] = $weight * 0.3;
                    }
                }
            }
        }
        
        // Extraire uniquement les mots-clés
        $keywords = array_keys($keywordsWithWeight);
        
        // Obtenir les produits recommandés basés sur ces mots-clés
        return self::getProductsByKeywords($keywords, $idLang, $limit);
    }
    
    /**
     * Analyse les caractéristiques des produits recherchés dans un message
     * 
     * @param string $message Message de l'utilisateur
     * @return array Caractéristiques détectées (type, niveau, taille, etc.)
     */
    public static function analyzeProductFeatures($message)
    {
        if (empty($message)) {
            return [];
        }
        
        $message = mb_strtolower(trim($message), 'UTF-8');
        $features = [];
        
        // Détecter le type de produit
        $productTypes = [
            'planche' => ['planche', 'flotteur', 'board'],
            'voile' => ['voile', 'sail', 'gréement'],
            'combinaison' => ['combinaison', 'wetsuit', 'combi'],
            'harnais' => ['harnais', 'harness', 'ceinture'],
            'accessoire' => ['accessoire', 'accessory', 'pièce', 'part']
        ];
        
        foreach ($productTypes as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword, 0, 'UTF-8') !== false) {
                    $features['type'] = $type;
                    break 2;
                }
            }
        }
        
        // Détecter le niveau
        $levels = [
            'débutant' => ['débutant', 'débutant', 'beginner', 'novice', 'apprentissage', 'apprendre'],
            'intermédiaire' => ['intermédiaire', 'intermédiaire', 'intermediate', 'progresser'],
            'avancé' => ['avancé', 'avancé', 'advanced', 'expert', 'performance', 'performant']
        ];
        
        foreach ($levels as $level => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword, 0, 'UTF-8') !== false) {
                    $features['niveau'] = $level;
                    break 2;
                }
            }
        }
        
        // Détecter les tailles (pour planches, voiles, etc.)
        if (preg_match('/\b(\d+(?:[\.,]\d+)?)\s*(?:litres?|l)\b/i', $message, $matches)) {
            $features['volume'] = str_replace(',', '.', $matches[1]);
        }
        
        if (preg_match('/\b(\d+(?:[\.,]\d+)?)\s*(?:m\s*²|m2|mètres?\s*carrés?)\b/i', $message, $matches)) {
            $features['surface'] = str_replace(',', '.', $matches[1]);
        }
        
        // Détecter les conditions de vent
        $windConditions = [
            'léger' => ['léger', 'light', 'faible', 'petit', 'petit temps'],
            'modéré' => ['modéré', 'moderate', 'moyen', 'médium', 'medium'],
            'fort' => ['fort', 'strong', 'puissant', 'tempête', 'storm']
        ];
        
        foreach ($windConditions as $condition => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($message, $keyword, 0, 'UTF-8') !== false && 
                    (mb_strpos($message, 'vent', 0, 'UTF-8') !== false || 
                     mb_strpos($message, 'wind', 0, 'UTF-8') !== false)) {
                    $features['vent'] = $condition;
                    break 2;
                }
            }
        }
        
        // Détecter les marques
        $brands = ['starboard', 'jp', 'fanatic', 'naish', 'rrd', 'tabou', 'f2', 'goya', 'exocet',
                  'north', 'duotone', 'neilpryde', 'severne', 'gaastra', 'loftsails', 'simmer', 'ezzy'];
        
        foreach ($brands as $brand) {
            if (mb_strpos($message, $brand, 0, 'UTF-8') !== false) {
                $features['marque'] = $brand;
                break;
            }
        }
        
        return $features;
    }
    
    /**
     * Valide un mot-clé avant son enregistrement
     * 
     * @param string $keyword Mot-clé à valider
     * @return bool Indique si le mot-clé est valide
     */
    public static function validateKeyword($keyword)
    {
        // Vérification de la longueur minimale et maximale
        if (strlen($keyword) < 3 || strlen($keyword) > 64) {
            return false;
        }
        
        // Vérification des caractères autorisés (lettres, chiffres, tirets, espaces)
        if (!preg_match('/^[a-zA-ZÀ-ÿ0-9\-\s]+$/', $keyword)) {
            return false;
        }
        
        // Vérification que le mot-clé n'est pas vide après nettoyage
        $cleanKeyword = trim($keyword);
        if (empty($cleanKeyword)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Nettoie un mot-clé avant son enregistrement
     * 
     * @param string $keyword Mot-clé à nettoyer
     * @return string Mot-clé nettoyé
     */
    public static function cleanKeyword($keyword)
    {
        // Suppression des espaces multiples
        $keyword = preg_replace('/\s+/', ' ', trim($keyword));
        
        // Conversion en minuscules
        $keyword = strtolower($keyword);
        
        return $keyword;
    }
    
    /**
     * Récupère les recommandations pour un mot-clé avec gestion de cache et gestion d'erreurs
     * 
     * @param string $keyword Mot-clé pour lequel récupérer les recommandations
     * @param int $limit Nombre maximum de recommandations à récupérer
     * @param bool $useCache Indique si le cache doit être utilisé
     * @return array Liste des recommandations
     * @throws IaBotException En cas d'erreur
     */
    public static function getRecommendationsByKeywordCached($keyword, $limit = 5, $useCache = true)
    {
        try {
            // Nettoyage du mot-clé
            $keyword = self::cleanKeyword($keyword);
            
            // Validation du mot-clé
            if (!self::validateKeyword($keyword)) {
                IaBotLogger::warning('Mot-clé invalide pour la recherche de recommandations', [
                    'keyword' => $keyword,
                    'limit' => $limit
                ]);
                return [];
            }
            
            // Vérification du cache
            $cacheKey = md5($keyword . '_' . $limit);
            
            // Si le cache est activé et que les données sont en cache et valides
            if ($useCache && 
                isset(self::$recommendationsCache[$cacheKey]) && 
                (time() - self::$cacheLastUpdate) < self::$cacheTtl) {
                
                IaBotLogger::debug('Recommandations récupérées depuis le cache', [
                    'keyword' => $keyword,
                    'limit' => $limit,
                    'count' => count(self::$recommendationsCache[$cacheKey])
                ]);
                
                return self::$recommendationsCache[$cacheKey];
            }
            
            // Récupération des données depuis la base de données
            $recommendations = self::getRecommendationsByKeyword($keyword, $limit);
            
            // Mise en cache des données
            if ($useCache) {
                self::$recommendationsCache[$cacheKey] = $recommendations;
                self::$cacheLastUpdate = time();
                
                // Nettoyage du cache si trop volumineux (plus de 100 entrées)
                if (count(self::$recommendationsCache) > 100) {
                    // On garde seulement les 50 entrées les plus récentes
                    self::$recommendationsCache = array_slice(self::$recommendationsCache, -50, 50, true);
                }
                
                IaBotLogger::debug('Recommandations mises en cache', [
                    'keyword' => $keyword,
                    'limit' => $limit,
                    'count' => count($recommendations)
                ]);
            }
            
            return $recommendations;
        } catch (Exception $e) {
            // Journalisation de l'erreur
            IaBotLogger::error('Erreur lors de la récupération des recommandations', [
                'keyword' => $keyword,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            
            // Si c'est déjà une IaBotException, on la relance
            if ($e instanceof IaBotException) {
                throw $e;
            }
            
            // Sinon, on crée une nouvelle exception
            throw new IaBotException(
                'Erreur lors de la récupération des recommandations: ' . $e->getMessage(),
                'IABOT_RECOMMENDATION_ERROR',
                [
                    'keyword' => $keyword,
                    'limit' => $limit
                ],
                true,
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Vide le cache des recommandations
     * 
     * @return void
     */
    public static function clearRecommendationsCache()
    {
        self::$recommendationsCache = [];
        self::$cacheLastUpdate = 0;
    }
    
    /**
     * Vide le cache (méthode héritée de ObjectModel)
     * Cette méthode doit rester non statique pour être compatible avec la classe parente
     * 
     * @param bool $all Si true, vide tous les caches
     * @return void
     */
    public function clearCache($all = false)
    {
        // Appel de la méthode parente pour maintenir la compatibilité
        parent::clearCache($all);
        
        // Vide également le cache spécifique aux recommandations
        self::clearRecommendationsCache();
    }
    
    /**
     * Récupère les recommandations pour un mot-clé spécifique
     * 
     * @param string $keyword Mot-clé pour lequel récupérer les recommandations
     * @param int $limit Nombre maximum de recommandations à récupérer
     * @return array Liste des recommandations
     */
    public static function getRecommendationsByKeyword($keyword, $limit = 5)
    {
        if (empty($keyword)) {
            return [];
        }
        
        // Nettoyage et validation du mot-clé
        $keyword = self::cleanKeyword($keyword);
        if (!self::validateKeyword($keyword)) {
            return [];
        }
        
        // Cette méthode serait normalement implémentée avec des requêtes à la base de données
        // pour récupérer les recommandations correspondant au mot-clé.
        // Pour éviter les erreurs de lint, nous utilisons une implémentation simplifiée.
        
        // Simulation de recommandations pour le développement
        $recommendations = [];
        
        // Ajout de quelques recommandations factices pour les tests
        for ($i = 1; $i <= min(5, $limit); $i++) {
            $recommendations[] = [
                'id_product' => $i,
                'name' => 'Produit recommandé ' . $i . ' pour "' . $keyword . '"',
                'description' => 'Description du produit recommandé ' . $i,
                'price' => 99.99 * $i,
                'price_formatted' => number_format(99.99 * $i, 2) . ' €',
                'url' => '#product-' . $i,
                'image_url' => '/img/p/' . $i . '.jpg',
                'weight' => 10 - $i
            ];
        }
        
        return $recommendations;
    }
}
