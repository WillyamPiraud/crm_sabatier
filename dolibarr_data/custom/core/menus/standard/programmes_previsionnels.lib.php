<?php
/**
 * Extension du menu standard pour ajouter "Programmes prévisionnels" sous "Divers"
 * 
 * Ce fichier sera automatiquement chargé par Dolibarr
 */

/**
 * Function to modify menu and add Programmes prévisionnels after "Divers"
 * 
 * Cette fonction est appelée après le chargement du menu standard
 */
function modifyMenuAddProgrammesPrevisionnels(&$menu)
{
	global $langs;
	
	if (!isset($menu) || !is_object($menu)) {
		return;
	}
	
	// Charger les traductions
	$langs->load("admin");
	$langs->load("other");
	
	// Trouver l'index de "Divers" (OtherSetup) dans le menu
	$found_index = -1;
	if (isset($menu->liste) && is_array($menu->liste)) {
		foreach ($menu->liste as $index => $item) {
			if (isset($item['url']) && strpos($item['url'], '/admin/const.php') !== false) {
				$found_index = $index;
				break;
			}
		}
		
		// Si trouvé, insérer notre menu juste après
		if ($found_index >= 0) {
			// Insérer après "Divers"
			$new_item = array(
				'url' => '/admin/programmes_previsionnels.php?mainmenu=home',
				'title' => $langs->trans("ProgrammesPrevisionnels"),
				'level' => 1
			);
			
			// Insérer l'élément après "Divers"
			array_splice($menu->liste, $found_index + 1, 0, array($new_item));
		} else {
			// Si pas trouvé, ajouter à la fin
			$menu->add("/admin/programmes_previsionnels.php?mainmenu=home", $langs->trans("ProgrammesPrevisionnels"), 1);
		}
	} else {
		// Ajouter directement si la liste n'existe pas encore
		$menu->add("/admin/programmes_previsionnels.php?mainmenu=home", $langs->trans("ProgrammesPrevisionnels"), 1);
	}
}

