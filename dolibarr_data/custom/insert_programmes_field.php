<?php
/**
 * Script pour insérer le code des programmes prévisionnels dans card.php
 */

$card_file = '/var/www/html/comm/propal/card.php';

// Lire le fichier
$content = file_get_contents($card_file);

// Code à insérer
$code_to_insert = '
		// Programmes prévisionnels
		require_once DOL_DOCUMENT_ROOT."/custom/class/programme_previsionnel.class.php";
		$programme = new ProgrammePrevisionnel($db);
		$programmes_actifs = $programme->listAll(1); // 1 = actifs seulement
		
		// Récupérer les programmes déjà associés (si édition)
		$programmes_selectionnes = array();
		if (isset($object->id) && $object->id > 0) {
			$sql = "SELECT fk_programme_previsionnel FROM ".MAIN_DB_PREFIX."propal_programme_previsionnel WHERE fk_propal = ".((int) $object->id);
			$resql = $db->query($sql);
			if ($resql) {
				while ($obj = $db->fetch_object($resql)) {
					$programmes_selectionnes[] = $obj->fk_programme_previsionnel;
				}
			}
		}
		
		if (count($programmes_actifs) > 0) {
			print "<tr class=\"field_programmes_previsionnels\">";
			print "<td class=\"titlefieldcreate\">".$langs->trans("ProgrammesPrevisionnels")."</td><td class=\"valuefieldcreate\">";
			print "<select name=\"programmes_previsionnels[]\" multiple=\"multiple\" size=\"5\" class=\"minwidth300\">";
			foreach ($programmes_actifs as $prog) {
				$selected = in_array($prog["id"], $programmes_selectionnes) ? "selected" : "";
				print "<option value=\"".$prog["id"]."\" ".$selected.">".$prog["label"]."</option>";
			}
			print "</select>";
			print "<br><small>".$langs->trans("HoldCtrlToSelectMultiple")."</small>";
			print "</td></tr>";
		}
';

// Trouver la ligne "// Template to use by default" et insérer avant
$pattern = '/(\s+)\/\/ Template to use by default/';
if (preg_match($pattern, $content, $matches)) {
	$indent = $matches[1];
	$replacement = $code_to_insert.$indent.'// Template to use by default';
	$content = preg_replace($pattern, $replacement, $content, 1);
	
	// Sauvegarder
	file_put_contents($card_file, $content);
	echo "✓ Code inséré avec succès\n";
} else {
	echo "⚠ Pattern '// Template to use by default' non trouvé\n";
}



