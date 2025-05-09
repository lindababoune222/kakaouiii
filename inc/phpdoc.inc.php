<?php
/**
 * Fichier d'annotations PHPDoc pour le module IA Bot
 * 
 * Ce fichier contient les annotations PHPDoc pour les classes et constantes de PrestaShop
 * utilisées dans le module IA Bot. Il est inclus dans tous les fichiers du module pour
 * éviter les erreurs de lint.
 * 
 * @author  Développeur
 * @copyright 2025
 * @license   Propriétaire
 */

/**
 * Constantes de PrestaShop
 * @var string _PS_VERSION_ Version de PrestaShop
 * @var string _PS_MODULE_DIR_ Chemin vers le répertoire des modules
 * @var string _PS_IMG_DIR_ Chemin vers le répertoire des images
 */

/**
 * @var class Module Classe de base pour les modules PrestaShop
 * @property string $name Nom du module
 * @property string $tab Onglet dans lequel le module apparaît
 * @property string $version Version du module
 * @property string $author Auteur du module
 * @property int $need_instance Indique si une instance du module est nécessaire
 * @property bool $bootstrap Indique si le module utilise Bootstrap
 * @property string $displayName Nom d'affichage du module
 * @property string $description Description du module
 * @property string $confirmUninstall Message de confirmation de désinstallation
 * @property array $ps_versions_compliancy Versions de PrestaShop compatibles
 * @method string l(string $string, string $module = null, string $domain = null) Méthode de traduction
 * @method bool registerHook(string|array $hooks) Enregistre un ou plusieurs hooks
 * @method bool unregisterHook(string|array $hooks) Désenregistre un ou plusieurs hooks
 * @method bool install() Installe le module
 * @method bool uninstall() Désinstalle le module
 * @method string getContent() Retourne le contenu de la page de configuration du module
 * @method array hookDisplayHeader() Hook pour l'en-tête
 * @method array hookDisplayFooter() Hook pour le pied de page
 * @method array hookDisplayLeftColumn() Hook pour la colonne de gauche
 * @method array hookDisplayRightColumn() Hook pour la colonne de droite
 * @method array hookDisplayHome() Hook pour la page d'accueil
 * @method array hookDisplayProductAdditionalInfo() Hook pour les informations additionnelles du produit
 * @method array hookDisplayBackOfficeHeader() Hook pour l'en-tête du back-office
 */

/**
 * @var class Configuration Classe de gestion de la configuration de PrestaShop
 * @method static bool updateValue(string $key, mixed $value, bool $html = false, int $idShopGroup = null, int $idShop = null)
 * @method static mixed get(string $key, mixed $default = null, int $idLang = null, int $idShopGroup = null, int $idShop = null, bool $default_value = false)
 * @method static bool deleteByName(string $key)
 */

/**
 * @var class Tab Classe de gestion des onglets d'administration de PrestaShop
 * @property int $id_parent ID de l'onglet parent
 * @property string $class_name Nom de la classe associée à l'onglet
 * @property string $module Nom du module associé à l'onglet
 * @property int $position Position de l'onglet
 * @property int $active Indique si l'onglet est actif
 * @method bool save() Enregistre l'onglet
 * @method bool delete() Supprime l'onglet
 */

/**
 * @var class Language Classe de gestion des langues de PrestaShop
 * @property int $id ID de la langue
 * @property string $name Nom de la langue
 * @property string $iso_code Code ISO de la langue
 * @property string $language_code Code de la langue
 * @property bool $active Indique si la langue est active
 * @method static array getLanguages(bool $active = true, int $idShop = null) Retourne la liste des langues
 */

/**
 * @var class Context Classe de gestion du contexte de PrestaShop
 * @method static Context getContext() Retourne l'instance du contexte
 * @property \Cookie $cookie Cookie de la session
 * @property \Language $language Langue actuelle
 * @property \Link $link Gestionnaire de liens
 * @property \Customer $customer Client connecté
 * @property \Shop $shop Boutique actuelle
 */

/**
 * @var class ModuleFrontController Contrôleur frontal pour les modules PrestaShop
 * @property \Module $module Module associé au contrôleur
 * @property \Context $context Contexte PrestaShop
 * @method void init() Initialise le contrôleur
 * @method void initContent() Initialise le contenu du contrôleur
 * @method void setMedia() Définit les médias du contrôleur
 * @method void postProcess() Exécute les traitements post-process
 */
