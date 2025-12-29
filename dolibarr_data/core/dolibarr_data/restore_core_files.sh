#!/bin/bash
# Script optimisé pour restaurer les fichiers core modifiés
CONTAINER_NAME="dolibarr_app"
BASE_DIR="dolibarr_data/core"

echo "🔄 Restauration des fichiers core modifiés..."
docker cp ${BASE_DIR}/comm/propal/card.php ${CONTAINER_NAME}:/var/www/html/comm/propal/card.php && echo "✓ card.php (propal)" || echo "✗ Erreur propal"
docker cp ${BASE_DIR}/societe/card.php ${CONTAINER_NAME}:/var/www/html/societe/card.php && echo "✓ card.php (societe)" || echo "✗ Erreur societe"
docker cp ${BASE_DIR}/core/menus/standard/eldy.lib.php ${CONTAINER_NAME}:/var/www/html/core/menus/standard/eldy.lib.php && echo "✓ eldy.lib.php" || echo "✗ Erreur eldy"
docker cp ${BASE_DIR}/core/menus/standard/empty.php ${CONTAINER_NAME}:/var/www/html/core/menus/standard/empty.php && echo "✓ empty.php" || echo "✗ Erreur empty"
echo "✅ Terminé!"
