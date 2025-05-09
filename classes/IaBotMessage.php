<?php
/**
 * Classe de gestion des messages du chatbot
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
require_once dirname(__FILE__) . '/IaBotAIConnector.php';

/**
 * Classe de gestion des messages du chatbot
 */
class IaBotMessage extends ObjectModel
{
    /** @var int ID du message */
    public $id_message;
    
    /** @var int ID de la conversation */
    public $id_conversation;
    
    /** @var string Contenu du message */
    public $content;
    
    /** @var string Expéditeur du message (user ou bot) */
    public $sender;
    
    /** @var string Date de création */
    public $date_add;
    
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'iabot_message',
        'primary' => 'id_message',
        'fields' => [
            'id_conversation' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'content' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true],
            'sender' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 10],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];
    
    /**
     * Récupère les messages d'une conversation
     * 
     * @param int $idConversation ID de la conversation
     * @param int $limit Nombre maximum de messages à récupérer (0 = tous)
     * @param int $offset Offset pour la pagination
     * @return array Liste des messages
     */
    public static function getByConversation($idConversation, $limit = 0, $offset = 0)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('iabot_message');
        $sql->where('id_conversation = ' . (int)$idConversation);
        $sql->orderBy('date_add ASC');
        
        if ($limit > 0) {
            $sql->limit($limit, $offset);
        }
        
        $result = Db::getInstance()->executeS($sql);
        
        if (!$result) {
            return [];
        }
        
        $messages = [];
        foreach ($result as $row) {
            $message = new IaBotMessage($row['id_message']);
            $messages[] = $message;
        }
        
        return $messages;
    }
    
    /**
     * Génère une réponse du bot en fonction du message de l'utilisateur
     * 
     * @param string $userMessage Message de l'utilisateur
     * @param int $idConversation ID de la conversation
     * @param int $idLang ID de la langue
     * @return array Réponse du bot et recommandations de produits
     */
    public static function generateBotResponse($userMessage, $idConversation, $idLang = null)
    {
        if ($idLang === null) {
            $idLang = (int)Context::getContext()->language->id;
        }
        
        // Nettoyer le message de l'utilisateur
        $cleanMessage = trim(strip_tags($userMessage));
        
        // Analyser les caractéristiques des produits recherchés
        $productFeatures = IaBotRecommendation::analyzeProductFeatures($cleanMessage);
        
        // Obtenir des recommandations basées sur le message et le contexte de la conversation
        $recommendations = [];
        if (Configuration::get('IABOT_SHOW_RECOMMENDATIONS')) {
            try {
                // Utiliser la nouvelle méthode de recommandation contextuelle
                $recommendations = IaBotRecommendation::getRecommendationsFromMessage(
                    $cleanMessage,
                    $idConversation,
                    $idLang,
                    (int)Configuration::get('IABOT_MAX_RECOMMENDATIONS', 3)
                );
            } catch (Exception $e) {
                // En cas d'erreur, revenir à l'extraction de mots-clés classique
                $keywords = self::extractKeywords($cleanMessage);
                if (!empty($keywords)) {
                    $recommendations = IaBotRecommendation::getProductsByKeywords(
                        $keywords, 
                        $idLang, 
                        (int)Configuration::get('IABOT_MAX_RECOMMENDATIONS', 3)
                    );
                }
            }
        }
        
        // Générer une réponse en fonction du message, des caractéristiques et des mots-clés
        $response = self::createResponse($cleanMessage, $productFeatures, $idLang);
        
        // Enregistrer la statistique
        IaBotStatistic::recordInteraction($idConversation, $cleanMessage, $response, count($recommendations));
        
        return [
            'reply' => $response,
            'recommendations' => $recommendations,
            'features' => $productFeatures // Ajouter les caractéristiques détectées pour un meilleur suivi
        ];
    }
    
    /**
     * Génère une réponse automatique à un message utilisateur en utilisant l'IA
     * 
     * @param int $idConversation ID de la conversation
     * @param string $userMessage Message de l'utilisateur
     * @param array $options Options pour la génération de la réponse
     * @return IaBotMessage Message généré
     * @throws IaBotException En cas d'erreur
     */
    public static function generateAIResponse($idConversation, $userMessage, array $options = [])
    {
        try {
            // Validation des paramètres
            if (!is_int($idConversation) || $idConversation <= 0) {
                throw IaBotException::validationError(
                    'ID de conversation invalide',
                    ['id_conversation' => $idConversation]
                );
            }
            
            if (empty($userMessage)) {
                throw IaBotException::validationError('Message utilisateur vide');
            }
            
            // Génération de la réponse avec l'IA
            $aiResponse = IaBotAIConnector::getAIResponse($userMessage, $idConversation, $options);
            
            // Création du message avec la réponse de l'IA
            $message = self::addMessageSafe($idConversation, $aiResponse, 'bot');
            
            // Journalisation du succès
            IaBotLogger::info('Réponse IA ajoutée avec succès', [
                'id_message' => $message->id_message,
                'id_conversation' => $idConversation,
                'response_length' => strlen($aiResponse)
            ]);
            
            return $message;
        } catch (IaBotException $e) {
            // L'exception a déjà été journalisée
            throw $e;
        } catch (Exception $e) {
            // Journalisation et conversion de l'exception
            $exception = new IaBotException(
                'Erreur lors de la génération de la réponse IA: ' . $e->getMessage(),
                'IABOT_MESSAGE_ERROR',
                [
                    'id_conversation' => $idConversation
                ],
                true,
                $e->getCode(),
                $e
            );
            
            throw $exception;
        }
    }
    
    /**
     * Extrait les mots-clés d'un message
     * 
     * @param string $message Message à analyser
     * @return array Liste des mots-clés
     */
    private static function extractKeywords($message)
    {
        // Liste des mots à ignorer (stop words)
        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'et', 'ou', 'je', 'tu', 'il', 'elle', 'nous', 'vous', 
                     'ils', 'elles', 'ce', 'cette', 'ces', 'mon', 'ma', 'mes', 'ton', 'ta', 'tes', 'son', 'sa', 'ses',
                     'notre', 'nos', 'votre', 'vos', 'leur', 'leurs', 'que', 'qui', 'quoi', 'comment', 'pourquoi',
                     'où', 'quand', 'est', 'sont', 'être', 'avoir', 'faire', 'pour', 'dans', 'sur', 'avec', 'sans',
                     'par', 'de', 'du', 'au', 'aux', 'à', 'en', 'vers', 'chez', 'bonjour', 'salut', 'merci'];
        
        // Convertir en minuscules et supprimer la ponctuation
        $message = mb_strtolower($message, 'UTF-8');
        $message = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $message);
        
        // Diviser en mots
        $words = preg_split('/\s+/', $message, -1, PREG_SPLIT_NO_EMPTY);
        
        // Filtrer les mots courts et les stop words
        $keywords = [];
        foreach ($words as $word) {
            if (mb_strlen($word, 'UTF-8') > 2 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }
        
        return $keywords;
    }
    
    /**
     * Crée une réponse en fonction du message et des caractéristiques détectées
     * 
     * @param string $message Message de l'utilisateur
     * @param array $features Caractéristiques des produits détectées
     * @param int $idLang ID de la langue
     * @return string Réponse du bot
     */
    private static function createResponse($message, $features, $idLang)
    {
        // Extraire des mots-clés pour la recherche dans la base de connaissances
        $keywords = self::extractKeywords($message);
        
        // Rechercher des informations dans la base de connaissances
        $knowledgeResults = self::searchByKeywords($keywords, $idLang);
        
        // Questions fréquentes et leurs réponses
        $faq = [
            'livraison' => 'Nous proposons plusieurs options de livraison. Pour les articles en stock, la livraison standard prend généralement 3-5 jours ouvrables. Nous offrons également une livraison express en 24-48h avec un supplément.',
            'retour' => 'Vous pouvez retourner les produits non utilisés dans leur emballage d\'origine dans les 14 jours suivant la réception. Veuillez consulter notre politique de retour pour plus de détails.',
            'garantie' => 'Tous nos produits sont couverts par une garantie fabricant de 2 ans contre les défauts de fabrication. Certains produits premium bénéficient d\'une garantie étendue.',
            'paiement' => 'Nous acceptons les cartes de crédit (Visa, Mastercard), PayPal, et les virements bancaires. Le paiement est sécurisé et vos données sont cryptées.'
        ];
        
        // Vérifier si le message correspond à une question fréquente
        foreach ($faq as $key => $answer) {
            if (mb_stripos($message, $key) !== false) {
                return $answer;
            }
        }
        
        // Générer une réponse personnalisée en fonction des caractéristiques détectées
        if (!empty($features)) {
            // Réponse spécifique au type de produit
            if (isset($features['type'])) {
                switch ($features['type']) {
                    case 'planche':
                        $response = 'Je vois que vous vous intéressez aux planches de windsurf. ';
                        
                        // Ajouter des détails en fonction du niveau
                        if (isset($features['niveau'])) {
                            switch ($features['niveau']) {
                                case 'débutant':
                                    $response .= 'Pour les débutants, je recommande une planche large et stable avec un grand volume (>160L). ';
                                    if (isset($features['volume']) && $features['volume'] < 160) {
                                        $response .= 'Le volume de ' . $features['volume'] . 'L que vous mentionnez est un peu faible pour un débutant. Je vous suggère plutôt un volume plus important pour plus de stabilité. ';
                                    }
                                    break;
                                case 'intermédiaire':
                                    $response .= 'Pour les intermédiaires, une planche de 120-160L offre un bon équilibre entre stabilité et maniabilité. ';
                                    break;
                                case 'avancé':
                                    $response .= 'Pour les riders avancés, les planches de moins de 120L offrent plus de réactivité et de performances. ';
                                    break;
                            }
                        } else {
                            $response .= 'Pour vous recommander la planche idéale, j\'aurais besoin de connaître votre niveau et votre poids. ';
                        }
                        
                        // Ajouter des détails sur le volume si mentionné
                        if (isset($features['volume']) && !isset($features['niveau'])) {
                            $volume = (float)$features['volume'];
                            if ($volume < 100) {
                                $response .= 'Un volume de ' . $volume . 'L convient aux windsurfers avancés et légers. ';
                            } elseif ($volume < 130) {
                                $response .= 'Un volume de ' . $volume . 'L est adapté aux pratiquants intermédiaires à avancés. ';
                            } elseif ($volume < 180) {
                                $response .= 'Un volume de ' . $volume . 'L offre un bon équilibre pour les débutants et intermédiaires. ';
                            } else {
                                $response .= 'Un volume de ' . $volume . 'L est parfait pour les débutants, offrant une excellente stabilité. ';
                            }
                        }
                        
                        $response .= 'Consultez les recommandations ci-dessous pour voir des planches qui pourraient vous convenir.';
                        return $response;
                        
                    case 'voile':
                        $response = 'Je vois que vous recherchez des voiles de windsurf. ';
                        
                        // Ajouter des détails en fonction du niveau
                        if (isset($features['niveau'])) {
                            switch ($features['niveau']) {
                                case 'débutant':
                                    $response .= 'Pour les débutants, je recommande des voiles légères et faciles à manipuler, généralement entre 3.5 et 5.5 m². ';
                                    break;
                                case 'intermédiaire':
                                    $response .= 'Pour les intermédiaires, des voiles entre 5.0 et 7.0 m² offrent un bon équilibre entre puissance et maniabilité. ';
                                    break;
                                case 'avancé':
                                    $response .= 'Pour les riders avancés, le choix de la voile dépend davantage de votre style (freeride, freestyle, wave) et des conditions de vent. ';
                                    break;
                            }
                        }
                        
                        // Ajouter des détails sur la surface si mentionnée
                        if (isset($features['surface'])) {
                            $surface = (float)$features['surface'];
                            if ($surface < 5.0) {
                                $response .= 'Une voile de ' . $surface . ' m² est adaptée aux vents forts ou aux riders légers. ';
                            } elseif ($surface < 6.5) {
                                $response .= 'Une voile de ' . $surface . ' m² est polyvalente et convient à une large gamme de conditions. ';
                            } else {
                                $response .= 'Une voile de ' . $surface . ' m² est idéale pour les vents légers à modérés. ';
                            }
                        }
                        
                        // Ajouter des détails sur les conditions de vent
                        if (isset($features['vent'])) {
                            switch ($features['vent']) {
                                case 'léger':
                                    $response .= 'Pour les vents légers, je recommande des voiles plus grandes (7.0+ m²) avec un profil puissant. ';
                                    break;
                                case 'modéré':
                                    $response .= 'Pour les vents modérés, des voiles de 5.5 à 7.0 m² offrent un bon équilibre. ';
                                    break;
                                case 'fort':
                                    $response .= 'Pour les vents forts, optez pour des voiles plus petites (3.5 à 5.5 m²) qui sont plus faciles à contrôler. ';
                                    break;
                            }
                        }
                        
                        $response .= 'Consultez les recommandations ci-dessous pour voir des voiles qui pourraient vous convenir.';
                        return $response;
                        
                    case 'combinaison':
                        $response = 'Je vois que vous recherchez une combinaison de windsurf. ';
                        $response .= 'Le choix de l\'\u00e9paisseur dépend de la température de l\'eau : 5/4/3mm pour l\'hiver, 3/2mm pour la mi-saison, et un shorty léger pour l\'\u00e9té. ';
                        $response .= 'Assurez-vous de choisir une combinaison spécifique pour le windsurf, avec une flexibilité accrue aux épaules et aux bras pour faciliter les mouvements.';
                        return $response;
                        
                    case 'harnais':
                        $response = 'Je vois que vous recherchez un harnais de windsurf. ';
                        $response .= 'Il existe deux types principaux : les harnais culotte (plus stables, idéaux pour les débutants) et les harnais ceinture (plus libres, préférés par les avancés). ';
                        $response .= 'Choisissez un modèle confortable avec un bon système de fermeture et une plaque dorsale adaptée à votre style de navigation.';
                        return $response;
                        
                    case 'accessoire':
                        $response = 'Je vois que vous recherchez des accessoires de windsurf. ';
                        $response .= 'Nous proposons une large gamme d\'accessoires : ailerons, dérives, pièces de mât, wishbones, rallonges, et bien plus encore. ';
                        $response .= 'Pour vous recommander les accessoires adaptés, pourriez-vous me préciser quel type d\'accessoire vous intéresse ?';
                        return $response;
                }
            }
            
            // Si une marque est mentionnée
            if (isset($features['marque'])) {
                $response = 'Je vois que vous vous intéressez à la marque ' . ucfirst($features['marque']) . '. ';
                $response .= 'C\'est une excellente marque reconnue pour sa qualité et ses performances. ';
                $response .= 'Consultez les recommandations ci-dessous pour voir les produits ' . ucfirst($features['marque']) . ' qui pourraient vous convenir.';
                return $response;
            }
            
            // Si le niveau est mentionné sans type spécifique
            if (isset($features['niveau']) && !isset($features['type'])) {
                switch ($features['niveau']) {
                    case 'débutant':
                        return 'Pour un débutant en windsurf, je recommande une planche large et stable (>75cm de large) avec un grand volume (>160L) et une voile de petite taille (4-5m²). Consultez les recommandations ci-dessous pour voir des équipements adaptés aux débutants.';
                    case 'intermédiaire':
                        return 'Pour un niveau intermédiaire, vous pouvez opter pour des planches de 70-75cm de large et 120-160L de volume, avec des voiles de 5-7m². Cela vous offrira un bon équilibre entre stabilité et performances. Consultez les recommandations ci-dessous.';
                    case 'avancé':
                        return 'Pour un niveau avancé, vous pouvez vous orienter vers des équipements plus techniques : planches plus étroites (<70cm) et moins volumineuses (<120L), avec des voiles adaptées à votre style (freeride, freestyle, wave). Consultez les recommandations ci-dessous.';
                }
            }
            
            // Si des conditions de vent sont mentionnées sans type spécifique
            if (isset($features['vent']) && !isset($features['type'])) {
                switch ($features['vent']) {
                    case 'léger':
                        return 'Pour naviguer par vent léger, je recommande des planches à grand volume et des voiles de grande taille (7.0+ m²). Cela vous permettra de planer plus facilement même avec peu de vent. Consultez les recommandations ci-dessous.';
                    case 'modéré':
                        return 'Pour des conditions de vent modéré, un équipement polyvalent est idéal : planche de volume moyen et voile de 5.5 à 7.0 m². Consultez les recommandations ci-dessous pour voir des produits adaptés.';
                    case 'fort':
                        return 'Pour naviguer par vent fort, privilégiez une planche plus petite et plus maniable, ainsi qu\'une voile de taille réduite (3.5 à 5.5 m²) pour garder le contrôle. Consultez les recommandations ci-dessous.';
                }
            }
        }
        
        // Réponses par défaut si aucune caractéristique spécifique n'est détectée
        $defaultResponses = [
            'Je suis votre assistant windsurf et je peux vous aider à choisir le matériel adapté à vos besoins. Pouvez-vous me préciser votre niveau et le type de navigation que vous pratiquez ?',
            'Merci pour votre message. Pour mieux vous conseiller, pourriez-vous me donner plus de détails sur ce que vous recherchez exactement ? Par exemple, votre niveau, le type d\'\u00e9quipement, ou les conditions dans lesquelles vous naviguez.',
            'Je suis là pour vous aider à trouver l\'\u00e9quipement de windsurf parfait. Quel est votre niveau d\'expérience et dans quelles conditions naviguez-vous habituellement ?',
            'Bonjour ! Je suis spécialisé dans le conseil en équipement de windsurf. N\'hésitez pas à me poser des questions sur nos produits ou à me demander des recommandations personnalisées.',
            'Pour vous proposer les meilleurs produits, j\'aurais besoin de connaître votre niveau, votre poids et le type de plan d\'eau sur lequel vous naviguez habituellement. Pouvez-vous me donner ces informations ?'
        ];
        
        // Réponse par défaut
        return $defaultResponses[array_rand($defaultResponses)];
    }
    
    /**
     * Valide le contenu d'un message avant son enregistrement
     * 
     * @param string $content Contenu du message à valider
     * @return bool Indique si le contenu est valide
     */
    public static function validateContent($content)
    {
        // Vérification de la longueur maximale (pour éviter les attaques par déni de service)
        if (strlen($content) > 10000) {
            return false;
        }
        
        // Vérification que le contenu n'est pas vide après nettoyage
        $cleanContent = trim(strip_tags($content));
        if (empty($cleanContent)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Valide l'expéditeur d'un message
     * 
     * @param string $sender Expéditeur à valider
     * @return bool Indique si l'expéditeur est valide
     */
    public static function validateSender($sender)
    {
        return in_array($sender, ['user', 'bot']);
    }
    
    /**
     * Nettoie le contenu d'un message avant son enregistrement
     * 
     * @param string $content Contenu du message à nettoyer
     * @return string Contenu nettoyé
     */
    public static function cleanContent($content)
    {
        // Suppression des balises script et iframe pour éviter les attaques XSS
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $content);
        
        // Nettoyage des attributs dangereux
        $content = preg_replace('/on\w+="[^"]*"/i', '', $content);
        $content = preg_replace('/on\w+=\'[^\']*\'/i', '', $content);
        
        return $content;
    }
    
    /**
     * Ajoute un message avec validation renforcée et gestion d'erreurs
     * 
     * @param int $idConversation ID de la conversation
     * @param string $content Contenu du message
     * @param string $sender Expéditeur du message
     * @return IaBotMessage Instance du message
     * @throws IaBotException En cas d'erreur
     */
    public static function addMessageSafe($idConversation, $content, $sender)
    {
        try {
            // Validation des paramètres
            if (!is_int($idConversation) || $idConversation <= 0) {
                throw IaBotException::validationError(
                    'ID de conversation invalide',
                    ['id_conversation' => $idConversation]
                );
            }
            
            if (!self::validateContent($content)) {
                throw IaBotException::validationError(
                    'Contenu du message invalide',
                    ['content' => substr($content, 0, 100) . (strlen($content) > 100 ? '...' : '')]
                );
            }
            
            if (!self::validateSender($sender)) {
                throw IaBotException::validationError(
                    'Expéditeur invalide',
                    ['sender' => $sender]
                );
            }
            
            // Nettoyage du contenu
            $content = self::cleanContent($content);
            
            $message = new self();
            $message->id_conversation = (int)$idConversation;
            $message->content = $content;
            $message->sender = $sender;
            $message->date_add = date('Y-m-d H:i:s');
            
            if (!$message->add()) {
                throw IaBotException::databaseError('Erreur lors de l\'ajout du message');
            }
            
            // Journalisation du succès
            IaBotLogger::info('Message ajouté avec succès', [
                'id_message' => $message->id_message,
                'id_conversation' => $idConversation,
                'sender' => $sender
            ]);
            
            return $message;
        } catch (IaBotException $e) {
            // L'exception a déjà été journalisée
            throw $e;
        } catch (Exception $e) {
            // Journalisation et conversion de l'exception
            $exception = new IaBotException(
                'Erreur lors de l\'ajout du message: ' . $e->getMessage(),
                'IABOT_MESSAGE_ERROR',
                [
                    'id_conversation' => $idConversation,
                    'sender' => $sender
                ],
                true,
                $e->getCode(),
                $e
            );
            
            throw $exception;
        }
    }
    
    /**
     * Recherche des mots-clés dans la base de connaissances
     * 
     * @param array $keywords Liste des mots-clés à rechercher
     * @param int $limit Nombre maximum de résultats
     * @return array Résultats de la recherche
     * @throws IaBotException En cas d'erreur
     */
    public static function searchByKeywords($keywords, $limit = 5)
    {
        try {
            // Validation des paramètres
            if (!is_array($keywords) || empty($keywords)) {
                return [];
            }
            
            // Recherche des connaissances correspondantes
            $results = IaBotKnowledge::searchByKeywords($keywords, $limit);
            
            // Journalisation du succès
            IaBotLogger::info('Recherche par mots-clés effectuée', [
                'keywords' => implode(', ', $keywords),
                'results_count' => count($results)
            ]);
            
            return $results;
        } catch (IaBotException $e) {
            // L'exception a déjà été journalisée
            throw $e;
        } catch (Exception $e) {
            // Journalisation et conversion de l'exception
            $exception = new IaBotException(
                'Erreur lors de la recherche par mots-clés: ' . $e->getMessage(),
                'IABOT_MESSAGE_ERROR',
                [
                    'keywords' => implode(', ', $keywords)
                ],
                true,
                $e->getCode(),
                $e
            );
            
            throw $exception;
        }
    }
}
