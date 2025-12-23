# Configuration DevContainer pour Dolibarr

Ce dossier permet d'ouvrir le conteneur Docker Dolibarr directement dans Cursor.

## Utilisation

### Option 1 : Attacher au conteneur en cours d'exécution

1. Assurez-vous que le conteneur est démarré :

   ```bash
   docker-compose up -d
   ```

2. Dans Cursor :

   - Appuyez sur `Cmd+Shift+P` (Mac) ou `Ctrl+Shift+P` (Windows/Linux)
   - Tapez : **"Remote-Containers: Attach to Running Container"**
   - Sélectionnez : `dolibarr_app`

3. Cursor ouvrira une nouvelle fenêtre connectée au conteneur !

### Option 2 : Ouvrir depuis la barre latérale Docker

1. Installez l'extension **Docker** dans Cursor
2. Dans la barre latérale, cliquez sur l'icône Docker
3. Trouvez `dolibarr_app` dans la liste
4. Clic droit → **"Attach Visual Studio Code"** ou **"Open in Container"**

## Fichiers accessibles

Une fois connecté, vous pourrez éditer directement :

- `/var/www/html/core/lib/company.lib.php` (et tous les fichiers core)
- `/var/www/html/custom/` (fichiers personnalisés)
- `/var/www/html/conf/` (configuration)

## Avantages

✅ Édition directe des fichiers dans le conteneur  
✅ Auto-complétion et coloration syntaxique  
✅ Terminal intégré dans le conteneur  
✅ Débogage possible  
✅ Pas besoin de copier/recopier les fichiers
