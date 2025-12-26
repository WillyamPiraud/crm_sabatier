#!/bin/bash
# Script optimisé pour restaurer les fichiers core modifiés dans le container Docker
# Usage: ./restore_core_files.sh

CONTAINER_NAME="dolibarr_app"
BASE_DIR="dolibarr_data/core"

echo "🔄 Restauration des fichiers core modifiés..."

# Vérifier que le container est en cours d'exécution
if ! docker ps --format "{{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
    echo "❌ Erreur: Le container ${CONTAINER_NAME} n'est pas en cours d'exécution"
    exit 1
fi

# Fonction pour copier un fichier avec gestion d'erreur
copy_file() {
    local src=$1
    local dest=$2
    local name=$3
    
    if docker cp "${src}" "${CONTAINER_NAME}:${dest}" 2>/dev/null; then
        echo "✓ ${name} restauré"
        return 0
    else
        echo "✗ Erreur: ${name}"
        return 1
    fi
}

# Restaurer tous les fichiers en une seule passe
copy_file "${BASE_DIR}/comm/propal/card.php" "/var/www/html/comm/propal/card.php" "card.php (propal)"
copy_file "${BASE_DIR}/societe/card.php" "/var/www/html/societe/card.php" "card.php (societe)"
copy_file "${BASE_DIR}/core/menus/standard/eldy.lib.php" "/var/www/html/core/menus/standard/eldy.lib.php" "eldy.lib.php"
copy_file "${BASE_DIR}/core/menus/standard/empty.php" "/var/www/html/core/menus/standard/empty.php" "empty.php"

echo "✅ Restauration terminée!"

