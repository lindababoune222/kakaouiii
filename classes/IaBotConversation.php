<?php
/**
 * Classe de gestion des conversations
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
 * Classe de gestion des conversations
 */
class IaBotConversation extends ObjectModel
{
    /** @var int ID de la conversation */
    public $id_conversation;
    
    /** @var int ID du client (peut être null pour les visiteurs) */
    public $id_customer;
    
    /** @var string Jeton unique pour identifier la conversation */
    public $token;
    
    /** @var string Adresse IP du client */
    public $ip_address;
    
    /** @var string Agent utilisateur du client */
    public $user_agent;
    
    /** @var bool Client connecté ou non */
    public $is_customer_logged;
    
    /** @var string Date de création */
    public $date_add;
    
    /** @var string Date de dernière mise à jour */
    public $date_upd;
    
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'iabot_conversation',
        'primary' => 'id_conversation',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'token' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'ip_address' => ['type' => self::TYPE_STRING, 'validate' => 'isIp2Long', 'required' => true, 'size' => 64],
            'user_agent' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false, 'size' => 255],
            'is_customer_logged' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];
    
    /**
     * Crée une nouvelle conversation avec gestion d'erreurs
     * 
     * @param int $idCustomer ID du client (optionnel)
     * @return IaBotConversation|bool Instance de la conversation ou false en cas d'erreur
     * @throws IaBotException En cas d'erreur
     */
    public static function createConversation($idCustomer = null)
    {
        try {
            $conversation = new self();
            
            // Génération d'un token unique
            $conversation->token = self::generateToken();
            
            // Récupération de l'adresse IP
            $conversation->ip_address = Tools::getRemoteAddr();
            
            // Récupération de l'agent utilisateur
            $conversation->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            
            // Assignation de l'ID client si fourni
            if ($idCustomer !== null) {
                $conversation->id_customer = (int)$idCustomer;
                $conversation->is_customer_logged = true;
            } else {
                $conversation->id_customer = null;
                $conversation->is_customer_logged = false;
            }
            
            // Dates de création et mise à jour
            $conversation->date_add = date('Y-m-d H:i:s');
            $conversation->date_upd = date('Y-m-d H:i:s');
            
            // Enregistrement de la conversation
            if (!$conversation->add()) {
                throw IaBotException::databaseError('Erreur lors de la création de la conversation');
            }
            
            // Journalisation du succès
            IaBotLogger::info('Nouvelle conversation créée', [
                'id_conversation' => $conversation->id_conversation,
                'id_customer' => $conversation->id_customer,
                'token' => $conversation->token
            ]);
            
            return $conversation;
        } catch (IaBotException $e) {
            // L'exception a déjà été journalisée
            throw $e;
        } catch (Exception $e) {
            // Journalisation et conversion de l'exception
            $exception = new IaBotException(
                'Erreur lors de la création de la conversation: ' . $e->getMessage(),
                'IABOT_CONVERSATION_ERROR',
                [
                    'id_customer' => $idCustomer
                ],
                true,
                $e->getCode(),
                $e
            );
            
            throw $exception;
        }
    }
    
    /**
     * Récupère une conversation par son token avec gestion d'erreurs
     * 
     * @param string $token Token de la conversation
     * @return IaBotConversation|null Instance de la conversation ou null si non trouvée
     * @throws IaBotException En cas d'erreur
     */
    public static function getByToken($token)
    {
        try {
            if (empty($token)) {
                throw IaBotException::validationError('Token de conversation invalide');
            }
            
            $query = new DbQuery();
            $query->select('id_conversation')
                  ->from(self::$definition['table'])
                  ->where('token = \'' . pSQL($token) . '\'');
            
            $id = Db::getInstance()->getValue($query);
            
            if (!$id) {
                IaBotLogger::info('Conversation non trouvée pour le token', [
                    'token' => $token
                ]);
                return null;
            }
            
            $conversation = new self($id);
            
            if (!Validate::isLoadedObject($conversation)) {
                throw IaBotException::databaseError('Erreur lors du chargement de la conversation');
            }
            
            return $conversation;
        } catch (IaBotException $e) {
            // L'exception a déjà été journalisée
            throw $e;
        } catch (Exception $e) {
            // Journalisation et conversion de l'exception
            $exception = new IaBotException(
                'Erreur lors de la récupération de la conversation: ' . $e->getMessage(),
                'IABOT_CONVERSATION_ERROR',
                [
                    'token' => $token
                ],
                true,
                $e->getCode(),
                $e
            );
            
            throw $exception;
        }
    }
    
    /**
     * Génère un token unique pour une conversation
     * 
     * @return string Token généré
     */
    private static function generateToken()
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Récupère une conversation par son ID
     * 
     * @param int $idConversation ID de la conversation
     * @return IaBotConversation|false La conversation ou false si non trouvée
     */
    public static function getById($idConversation)
    {
        $id = (int)$idConversation;
        
        if ($id <= 0) {
            return false;
        }
        
        return new IaBotConversation($id);
    }
    
    /**
     * Récupère les messages d'une conversation
     * 
     * @param int $limit Nombre maximum de messages à récupérer (0 = tous)
     * @param int $offset Offset pour la pagination
     * @return array Liste des messages
     */
    public function getMessages($limit = 0, $offset = 0)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('iabot_message');
        $sql->where('id_conversation = ' . (int)$this->id);
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
     * Ajoute un message à la conversation
     * 
     * @param string $content Contenu du message
     * @param string $sender Expéditeur du message (user ou bot)
     * @return IaBotMessage|false Le message créé ou false en cas d'erreur
     */
    public function addMessage($content, $sender = 'user')
    {
        $message = new IaBotMessage();
        $message->id_conversation = (int)$this->id;
        $message->content = $content;
        $message->sender = $sender;
        $message->date_add = date('Y-m-d H:i:s');
        
        if ($message->save()) {
            // Mettre à jour la date de dernière mise à jour de la conversation
            $this->date_upd = date('Y-m-d H:i:s');
            $this->save();
            
            return $message;
        }
        
        return false;
    }
}
