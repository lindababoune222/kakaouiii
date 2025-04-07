# Correction du module IaBot pour PrestaShop 8

## Problème résolu

Le module IaBot présentait une erreur lors de l'installation :

```
L'action Install est impossible pour le module iabot. 
SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; 
check the manual that corresponds to your MariaDB server version for the right syntax to use near 'LIMIT 1' at line 1
```

## Correction apportée

Le problème principal était que le module essayait de charger des fichiers SQL qui n'existaient pas. Les fichiers d'installation et de désinstallation étaient au format PHP, mais le code essayait de les charger comme des fichiers SQL.

### Modifications effectuées :

1. Dans le fichier `iabot.php`, la méthode `installDb()` a été modifiée pour charger correctement le fichier `install.php` au lieu de `install.sql`.

2. Dans le fichier `iabot.php`, la méthode `uninstallDb()` a été modifiée pour charger correctement le fichier `uninstall.php` au lieu de `uninstall.sql`.

## Comment utiliser cette correction

1. Téléchargez le fichier `iabot_corrige.zip`
2. Décompressez-le et remplacez votre module actuel par cette version corrigée
3. Essayez d'installer le module à nouveau depuis le back-office de PrestaShop

## Remarques supplémentaires

Cette correction ne modifie que le strict nécessaire pour résoudre l'erreur d'installation. Le module devrait maintenant s'installer correctement sans erreur SQL.

Si vous rencontrez d'autres problèmes après l'installation, n'hésitez pas à nous contacter pour obtenir de l'aide supplémentaire.