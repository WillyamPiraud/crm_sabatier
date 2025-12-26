#!/bin/bash
# Script pour restaurer les fichiers core modifiés au démarrage du container

# Attendre que le container soit prêt
sleep 2

# Restaurer les fichiers modifiés depuis les fichiers locaux
if [ -f "/var/www/html/custom/../core/comm/propal/card.php" ]; then
    cp /var/www/html/custom/../core/comm/propal/card.php /var/www/html/comm/propal/card.php
    echo "✓ card.php (propal) restauré"
fi

if [ -f "/var/www/html/custom/../core/societe/card.php" ]; then
    cp /var/www/html/custom/../core/societe/card.php /var/www/html/societe/card.php
    echo "✓ card.php (societe) restauré"
fi

if [ -f "/var/www/html/custom/../core/core/menus/standard/eldy.lib.php" ]; then
    cp /var/www/html/custom/../core/core/menus/standard/eldy.lib.php /var/www/html/core/menus/standard/eldy.lib.php
    echo "✓ eldy.lib.php restauré"
fi

if [ -f "/var/www/html/custom/../core/core/menus/standard/empty.php" ]; then
    cp /var/www/html/custom/../core/core/menus/standard/empty.php /var/www/html/core/menus/standard/empty.php
    echo "✓ empty.php restauré"
fi

