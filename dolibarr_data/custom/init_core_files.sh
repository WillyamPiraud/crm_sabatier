#!/bin/bash
# Script exécuté dans le container pour restaurer les fichiers core au démarrage
# Ce script est appelé depuis un hook Dolibarr ou un entrypoint personnalisé

CORE_DIR="/var/www/html/custom/../core"

if [ -d "$CORE_DIR" ]; then
    # Restaurer les fichiers depuis le volume monté (si accessible)
    if [ -f "$CORE_DIR/comm/propal/card.php" ]; then
        cp "$CORE_DIR/comm/propal/card.php" /var/www/html/comm/propal/card.php 2>/dev/null
    fi
    
    if [ -f "$CORE_DIR/societe/card.php" ]; then
        cp "$CORE_DIR/societe/card.php" /var/www/html/societe/card.php 2>/dev/null
    fi
    
    if [ -f "$CORE_DIR/core/menus/standard/eldy.lib.php" ]; then
        cp "$CORE_DIR/core/menus/standard/eldy.lib.php" /var/www/html/core/menus/standard/eldy.lib.php 2>/dev/null
    fi
    
    if [ -f "$CORE_DIR/core/menus/standard/empty.php" ]; then
        cp "$CORE_DIR/core/menus/standard/empty.php" /var/www/html/core/menus/standard/empty.php 2>/dev/null
    fi
fi

