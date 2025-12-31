#!/bin/bash
# Script pour restaurer les fichiers core modifiés dans le conteneur Docker Dolibarr

CONTAINER_NAME="dolibarr_app"

# Vérifier que le conteneur est en cours d'exécution
if ! docker ps | grep -q "$CONTAINER_NAME"; then
    echo "❌ Erreur : Le conteneur $CONTAINER_NAME n'est pas en cours d'exécution"
    exit 1
fi

echo "🔄 Restauration des fichiers core modifiés dans $CONTAINER_NAME..."
echo ""

# Fonction pour copier un fichier
copy_file() {
    local source=$1
    local dest=$2
    local description=$3
    
    if [ -f "$source" ]; then
        docker cp "$source" "$CONTAINER_NAME:$dest"
        if [ $? -eq 0 ]; then
            echo "✅ $description restauré"
        else
            echo "❌ Erreur lors de la copie de $description"
        fi
    else
        echo "⚠️  Fichier source introuvable : $source"
    fi
}

# Restaurer card_propal.php
copy_file "dolibarr_data/custom/card_propal.php" "/var/www/html/comm/propal/card.php" "card.php (propal)"

# Restaurer eldy.lib.php si modifié
if [ -f "dolibarr_data/core/dolibarr_data/core/core/menus/standard/eldy.lib.php" ]; then
    copy_file "dolibarr_data/core/dolibarr_data/core/core/menus/standard/eldy.lib.php" "/var/www/html/core/menus/standard/eldy.lib.php" "eldy.lib.php"
fi

echo ""
echo "✅ Restauration terminée !"
