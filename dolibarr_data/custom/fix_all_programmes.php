<?php
/**
 * Script pour :
 * 1. Ajouter le champ programmes prévisionnels avec Select2
 * 2. Masquer les champs Date de livraison et Délai de livraison
 */

$card_file = '/var/www/html/comm/propal/card.php';

// Lire le fichier
$content = file_get_contents($card_file);

// 1. Masquer le champ "Date de livraison" dans le formulaire de création (ligne ~2485)
$content = preg_replace(
    '/(\/\/ Delivery date \(or manufacturing\)\s+print \'<tr class="field_date_livraison">.*?print \'<\/td><\/tr>\';)/s',
    '// Masqué: Date de livraison (formulaire de création)
		// $1',
    $content,
    1
);

// 2. Masquer le champ "Delivery date" dans le formulaire d'édition (ligne ~3178)
$content = preg_replace(
    '/(\/\/ Delivery date\s+\$langs->load\(\'deliveries\'\);\s+print \'<tr><td>\';\s+print \$form->editfieldkey.*?print \'<\/tr>\';)/s',
    '// Masqué: Delivery date (formulaire d\'édition)
		// $1',
    $content,
    1
);

// 3. Masquer le champ "Delivery delay" (ligne ~3185)
$content = preg_replace(
    '/(\/\/ Delivery delay\s+print \'<tr class="fielddeliverydelay"><td>.*?print \'<\/td><\/tr>\';)/s',
    '// Masqué: Delivery delay
		// $1',
    $content,
    1
);

// 4. Ajouter le code des programmes prévisionnels avant "// Template to use by default"
$code_programmes = '
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
			print "<select name=\"programmes_previsionnels[]\" multiple=\"multiple\" class=\"minwidth300\" id=\"programmes_previsionnels_select\">";
			foreach ($programmes_actifs as $prog) {
				$selected = in_array($prog["id"], $programmes_selectionnes) ? "selected" : "";
				print "<option value=\"".$prog["id"]."\" ".$selected.">".$prog["label"]."</option>";
			}
			print "</select>";
			print "<script>";
			print "$(document).ready(function() {";
			print "  $(\'select[name=\"programmes_previsionnels[]\"]\').select2({";
			print "    placeholder: \'Sélectionner des programmes prévisionnels\',";
			print "    allowClear: true,";
			print "    width: \'100%\',";
			print "    language: \'fr\'";
			print "  });";
			print "});";
			print "</script>";
			print "<style>.select2-container { z-index: 9999; } .select2-container--default .select2-selection--multiple { min-height: 38px; border: 1px solid #aaa; }</style>";
			print "</td></tr>";
		}
';

$content = preg_replace(
    '/(\s+)\/\/ Template to use by default/',
    $code_programmes.'$1// Template to use by default',
    $content,
    1
);

// Sauvegarder
file_put_contents($card_file, $content);
echo "✓ Modifications appliquées\n";



