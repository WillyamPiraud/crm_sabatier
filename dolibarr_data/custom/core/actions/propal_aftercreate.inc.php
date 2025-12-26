<?php
/**
 * Hook exécuté après la création d'une proposition commerciale
 * Ce hook est appelé automatiquement par Dolibarr après la création d'une propal
 */

// Vérifier que la propal a été créée avec succès
if (isset($object) && is_object($object) && $object->id > 0 && $object->element == 'propal') {
	// Inclure le script d'auto-ajout de ligne de service
	if (file_exists(DOL_DOCUMENT_ROOT."/custom/core/actions/auto_add_service_line_propal.inc.php")) {
		$id = $object->id; // Définir $id pour le script
		include DOL_DOCUMENT_ROOT."/custom/core/actions/auto_add_service_line_propal.inc.php";
	}
}

