<?php
/**
 * Hook pour ajouter "Programmes prévisionnels" dans le menu Configuration → Divers
 * 
 * Ce hook modifie le menu après son chargement pour ajouter notre entrée
 */

// Ce fichier sera inclus automatiquement par Dolibarr
// Il modifie le menu $menu pour ajouter "Programmes prévisionnels" après "Divers"

if (isset($menu) && is_object($menu)) {
	global $langs;
	
	// Charger les traductions
	$langs->load("admin");
	$langs->load("other");
	
	// Ajouter le menu après "Divers" (OtherSetup)
	// Le menu "Divers" pointe vers /admin/const.php?mainmenu=home
	// On ajoute notre menu juste après avec le même niveau (1)
	// Utiliser leftmenu=const pour qu'il apparaisse sous "Divers"
	$menu->add("/custom/admin/programmes_previsionnels.php?mainmenu=home&leftmenu=const", $langs->trans("ProgrammesPrevisionnels"), 1);
}

