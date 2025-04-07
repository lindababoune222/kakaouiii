<?php
/**
 * Outil de diagnostic pour le module IaBot
 * 
 * @author  Développeur
 * @copyright 2025
 */

// Définition du mode développement
define('_PS_DEV_MODE_', true);

// Inclusion des fichiers nécessaires
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/classes/IaBotLogger.php');

// Initialisation du logger avec mode verbose
IaBotLogger::init(true, 4, true);

// Vérification des droits d'accès
if (!Context::getContext()->employee || !Context::getContext()->employee->isLoggedBack()) {
    // Redirection vers la page de connexion si l'utilisateur n'est pas connecté
    Tools::redirectAdmin('index.php?controller=AdminLogin');
    exit;
}

// Génération du rapport de diagnostic
$diagnosticFile = IaBotLogger::generateDiagnostic(true, true);

// Affichage du rapport
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic IaBot</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
        h1, h2, h3 { color: #2C3E50; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: #fff; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
        .card-header { background: #3498DB; color: white; padding: 15px; font-weight: bold; }
        .card-body { padding: 15px; }
        .btn { display: inline-block; padding: 10px 15px; background: #3498DB; color: white; text-decoration: none; border-radius: 3px; }
        .btn:hover { background: #2980B9; }
        .btn-danger { background: #E74C3C; }
        .btn-danger:hover { background: #C0392B; }
        .btn-success { background: #2ECC71; }
        .btn-success:hover { background: #27AE60; }
        .btn-warning { background: #F39C12; }
        .btn-warning:hover { background: #D35400; }
        .alert { padding: 15px; border-radius: 3px; margin-bottom: 20px; }
        .alert-info { background: #D6EAF8; color: #2E86C1; }
        .alert-success { background: #D5F5E3; color: #27AE60; }
        .alert-warning { background: #FCF3CF; color: #D35400; }
        .alert-danger { background: #FADBD8; color: #C0392B; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        .actions { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnostic IaBot</h1>
        
        <div class="alert alert-info">
            <p>Un rapport de diagnostic a été généré pour vous aider à identifier les problèmes avec le module IaBot.</p>
            <p>Vous pouvez télécharger ce rapport ou exécuter des tests supplémentaires pour diagnostiquer les problèmes.</p>
        </div>
        
        <div class="card">
            <div class="card-header">Rapport de diagnostic</div>
            <div class="card-body">
                <p>Le rapport de diagnostic a été généré avec succès :</p>
                <p><strong>Fichier :</strong> <?php echo $diagnosticFile; ?></p>
                <div class="actions">
                    <a href="<?php echo str_replace(_PS_ROOT_DIR_, Context::getContext()->shop->getBaseURL(true), $diagnosticFile); ?>" class="btn" target="_blank">Voir le rapport</a>
                    <a href="<?php echo Context::getContext()->link->getAdminLink('AdminModules'); ?>&configure=iabot" class="btn">Retour à la configuration</a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Tests de diagnostic</div>
            <div class="card-body">
                <form action="" method="post" id="diagnostic-form">
                    <div class="form-group">
                        <label for="test-type">Type de test</label>
                        <select name="test-type" id="test-type">
                            <option value="ajax">Test de requête AJAX</option>
                            <option value="conversation">Test de création de conversation</option>
                            <option value="message">Test d'envoi de message</option>
                            <option value="api">Test de l'API OpenRouter</option>
                            <option value="database">Test de la base de données</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="message-group" style="display: none;">
                        <label for="test-message">Message de test</label>
                        <input type="text" name="test-message" id="test-message" value="Bonjour, ceci est un test." />
                    </div>
                    
                    <div class="actions">
                        <button type="button" id="run-test" class="btn btn-success">Exécuter le test</button>
                    </div>
                </form>
                
                <div id="test-result" style="display: none; margin-top: 20px;">
                    <h3>Résultat du test</h3>
                    <pre id="test-output" style="background: #f5f5f5; padding: 15px; border-radius: 3px; overflow: auto;"></pre>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Actions de maintenance</div>
            <div class="card-body">
                <div class="actions">
                    <a href="?action=clear_logs" class="btn btn-warning">Vider les logs</a>
                    <a href="?action=reset_recommendations" class="btn btn-danger">Réinitialiser les recommandations</a>
                    <a href="?action=check_tables" class="btn">Vérifier les tables</a>
                </div>
                
                <?php
                // Traitement des actions de maintenance
                $action = Tools::getValue('action');
                if ($action) {
                    echo '<div class="alert alert-info" style="margin-top: 20px;">';
                    
                    switch ($action) {
                        case 'clear_logs':
                            // Vider les logs
                            $logDir = _PS_MODULE_DIR_ . 'iabot/logs';
                            if (is_dir($logDir)) {
                                $logFiles = glob($logDir . '/*.log');
                                $count = 0;
                                foreach ($logFiles as $file) {
                                    if (unlink($file)) {
                                        $count++;
                                    }
                                }
                                echo "<p>$count fichiers de log ont été supprimés.</p>";
                            } else {
                                echo "<p>Le répertoire de logs n'existe pas.</p>";
                            }
                            break;
                            
                        case 'reset_recommendations':
                            // Réinitialiser les recommandations
                            $module = Module::getInstanceByName('iabot');
                            if ($module && method_exists($module, 'resetRecommendationsTable')) {
                                $result = $module->resetRecommendationsTable();
                                if ($result) {
                                    echo "<p>La table des recommandations a été réinitialisée avec succès.</p>";
                                } else {
                                    echo "<p>Une erreur est survenue lors de la réinitialisation de la table des recommandations.</p>";
                                }
                            } else {
                                echo "<p>La méthode de réinitialisation n'est pas disponible.</p>";
                            }
                            break;
                            
                        case 'check_tables':
                            // Vérifier les tables
                            $tables = [
                                'iabot_conversation',
                                'iabot_message',
                                'iabot_knowledge',
                                'iabot_product_index',
                                'iabot_recommendation',
                                'iabot_statistic'
                            ];
                            
                            echo "<p>Vérification des tables :</p><ul>";
                            foreach ($tables as $table) {
                                $fullTable = _DB_PREFIX_ . $table;
                                $result = Db::getInstance()->executeS("SHOW TABLES LIKE '$fullTable'");
                                if (count($result) > 0) {
                                    $count = Db::getInstance()->getValue("SELECT COUNT(*) FROM `$fullTable`");
                                    echo "<li>$table : <strong>Existe</strong> ($count enregistrements)</li>";
                                } else {
                                    echo "<li>$table : <strong style='color: red;'>N'existe pas</strong></li>";
                                }
                            }
                            echo "</ul>";
                            break;
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Afficher/masquer le champ de message en fonction du type de test
            document.getElementById('test-type').addEventListener('change', function() {
                var messageGroup = document.getElementById('message-group');
                if (this.value === 'message' || this.value === 'api') {
                    messageGroup.style.display = 'block';
                } else {
                    messageGroup.style.display = 'none';
                }
            });
            
            // Exécuter le test
            document.getElementById('run-test').addEventListener('click', function() {
                var testType = document.getElementById('test-type').value;
                var testMessage = document.getElementById('test-message').value;
                var resultDiv = document.getElementById('test-result');
                var outputPre = document.getElementById('test-output');
                
                resultDiv.style.display = 'block';
                outputPre.innerHTML = 'Exécution du test...';
                
                // Préparation des données pour la requête AJAX
                var formData = new FormData();
                formData.append('ajax', '1');
                formData.append('test_type', testType);
                
                if (testType === 'message' || testType === 'api') {
                    formData.append('test_message', testMessage);
                }
                
                // Envoi de la requête AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    outputPre.innerHTML = JSON.stringify(data, null, 2);
                })
                .catch(error => {
                    outputPre.innerHTML = 'Erreur : ' + error.message;
                });
            });
        });
    </script>
</body>
</html>
<?php
// Traitement des requêtes AJAX
if (Tools::getValue('ajax')) {
    $testType = Tools::getValue('test_type');
    $testMessage = Tools::getValue('test_message', 'Bonjour, ceci est un test.');
    $result = ['success' => false, 'message' => 'Type de test non reconnu'];
    
    switch ($testType) {
        case 'ajax':
            // Test de requête AJAX
            $result = [
                'success' => true,
                'message' => 'Test de requête AJAX réussi',
                'server' => $_SERVER,
                'headers' => getallheaders()
            ];
            break;
            
        case 'conversation':
            // Test de création de conversation
            try {
                $conversation = new IaBotConversation();
                $conversation->token = Tools::passwdGen(32);
                $conversation->ip_address = Tools::getRemoteAddr();
                $conversation->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
                $conversation->is_customer_logged = false;
                $conversation->id_customer = null;
                $conversation->date_add = date('Y-m-d H:i:s');
                $conversation->date_upd = date('Y-m-d H:i:s');
                
                if ($conversation->add()) {
                    $result = [
                        'success' => true,
                        'message' => 'Conversation créée avec succès',
                        'conversation' => [
                            'id' => $conversation->id,
                            'token' => $conversation->token
                        ]
                    ];
                } else {
                    $result = [
                        'success' => false,
                        'message' => 'Échec de création de la conversation',
                        'errors' => $conversation->getErrors()
                    ];
                }
            } catch (Exception $e) {
                $result = [
                    'success' => false,
                    'message' => 'Exception lors de la création de la conversation',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ];
            }
            break;
            
        case 'message':
            // Test d'envoi de message
            try {
                // Création d'une conversation temporaire
                $conversation = new IaBotConversation();
                $conversation->token = Tools::passwdGen(32);
                $conversation->ip_address = Tools::getRemoteAddr();
                $conversation->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
                $conversation->is_customer_logged = false;
                $conversation->id_customer = null;
                $conversation->date_add = date('Y-m-d H:i:s');
                $conversation->date_upd = date('Y-m-d H:i:s');
                
                if ($conversation->add()) {
                    // Envoi d'un message
                    $userMessage = new IaBotMessage();
                    $userMessage->id_conversation = (int)$conversation->id;
                    $userMessage->content = $testMessage;
                    $userMessage->sender = 'user';
                    $userMessage->date_add = date('Y-m-d H:i:s');
                    
                    if ($userMessage->add()) {
                        // Génération de la réponse
                        $botMessage = new IaBotMessage();
                        $botMessage->id_conversation = (int)$conversation->id;
                        
                        $response = $botMessage->generateAIResponse($testMessage, $conversation);
                        
                        $botMessage->content = $response['message'];
                        $botMessage->sender = 'bot';
                        $botMessage->date_add = date('Y-m-d H:i:s');
                        
                        if ($botMessage->add()) {
                            $result = [
                                'success' => true,
                                'message' => 'Message traité avec succès',
                                'conversation' => [
                                    'id' => $conversation->id,
                                    'token' => $conversation->token
                                ],
                                'user_message' => [
                                    'id' => $userMessage->id,
                                    'content' => $userMessage->content
                                ],
                                'bot_message' => [
                                    'id' => $botMessage->id,
                                    'content' => $botMessage->content
                                ],
                                'recommendations' => $response['recommendations'] ?? []
                            ];
                        } else {
                            $result = [
                                'success' => false,
                                'message' => 'Échec d\'ajout du message bot',
                                'errors' => $botMessage->getErrors()
                            ];
                        }
                    } else {
                        $result = [
                            'success' => false,
                            'message' => 'Échec d\'ajout du message utilisateur',
                            'errors' => $userMessage->getErrors()
                        ];
                    }
                } else {
                    $result = [
                        'success' => false,
                        'message' => 'Échec de création de la conversation',
                        'errors' => $conversation->getErrors()
                    ];
                }
            } catch (Exception $e) {
                $result = [
                    'success' => false,
                    'message' => 'Exception lors du traitement du message',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ];
            }
            break;
            
        case 'api':
            // Test de l'API OpenRouter
            try {
                require_once(dirname(__FILE__) . '/classes/IaBotAIConnector.php');
                
                $apiKey = Configuration::get('IABOT_API_KEY');
                $model = Configuration::get('IABOT_AI_MODEL', 'meta-llama/llama-3.3-70b-instruct:free');
                $temperature = (float)Configuration::get('IABOT_AI_TEMPERATURE', 0.7);
                
                if (empty($apiKey)) {
                    $result = [
                        'success' => false,
                        'message' => 'Clé API non configurée'
                    ];
                } else {
                    $connector = new IaBotAIConnector($apiKey, $model, $temperature);
                    $response = $connector->generateResponse($testMessage);
                    
                    $result = [
                        'success' => true,
                        'message' => 'Test API réussi',
                        'request' => [
                            'message' => $testMessage,
                            'model' => $model,
                            'temperature' => $temperature
                        ],
                        'response' => $response
                    ];
                }
            } catch (Exception $e) {
                $result = [
                    'success' => false,
                    'message' => 'Exception lors du test API',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ];
            }
            break;
            
        case 'database':
            // Test de la base de données
            $tables = [
                'iabot_conversation',
                'iabot_message',
                'iabot_knowledge',
                'iabot_product_index',
                'iabot_recommendation',
                'iabot_statistic'
            ];
            
            $tableStatus = [];
            foreach ($tables as $table) {
                $fullTable = _DB_PREFIX_ . $table;
                $exists = count(Db::getInstance()->executeS("SHOW TABLES LIKE '$fullTable'")) > 0;
                
                if ($exists) {
                    $count = Db::getInstance()->getValue("SELECT COUNT(*) FROM `$fullTable`");
                    $structure = Db::getInstance()->executeS("DESCRIBE `$fullTable`");
                    $tableStatus[$table] = [
                        'exists' => true,
                        'count' => $count,
                        'structure' => $structure
                    ];
                } else {
                    $tableStatus[$table] = [
                        'exists' => false
                    ];
                }
            }
            
            $result = [
                'success' => true,
                'message' => 'Test de base de données terminé',
                'tables' => $tableStatus
            ];
            break;
    }
    
    // Enregistrement du résultat du test dans les logs
    IaBotLogger::info('Test de diagnostic exécuté', [
        'type' => $testType,
        'result' => $result
    ]);
    
    // Envoi de la réponse JSON
    header('Content-Type: application/json');
    die(json_encode($result));
}
