<?php
/**
 * Script pour ajouter le champ "Programmes prévisionnels" dans le formulaire de création de propal
 */

$card_file = '/var/www/html/comm/propal/card.php';

if (!file_exists($card_file)) {
    die("Erreur : Le fichier $card_file n'existe pas\n");
}

// Lire le fichier
$content = file_get_contents($card_file);
$original_content = $content;

// 1. Ajouter le champ dans le formulaire (après incoterms, avant Template)
$pattern1 = '/(print \$form->select_incoterms\([^)]+\);\s+print \'<\/td><\/tr>\';\s+\}\s+// Template to use by default\s+print \'<tr class="field_model">\')/s';
$replacement1 = 'print $form->select_incoterms((!empty($soc->fk_incoterms) ? $soc->fk_incoterms : \'\'), (!empty($soc->location_incoterms) ? $soc->location_incoterms : \'\'));
			print \'</td></tr>\';
		}

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
			print "<td class=\"titlefieldcreate\">Programmes Prévisionnels</td><td class=\"valuefieldcreate\">";
			print "<select name=\"programmes_previsionnels[]\" multiple=\"multiple\" size=\"5\" class=\"minwidth300\">";
			foreach ($programmes_actifs as $prog) {
				$selected = in_array($prog["id"], $programmes_selectionnes) ? "selected" : "";
				print "<option value=\"".$prog["id"]."\" ".$selected.">".$prog["label"]."</option>";
			}
			print "</select>";
			print "<br><small>Maintenez Ctrl pour sélectionner plusieurs programmes</small>";
			print "</td></tr>";
		}

		// Template to use by default
		print \'<tr class="field_model">\'';

$content = preg_replace($pattern1, $replacement1, $content, 1);

// 2. Ajouter la sauvegarde après la première création
$pattern2 = '/(\$id = \$object->create\(\$user\);\s+if \(\$id > 0\) \{)/';
$replacement2 = '$id = $object->create($user);
		// Sauvegarder les programmes prévisionnels
		if ($id > 0) {
			require_once DOL_DOCUMENT_ROOT."/custom/class/programme_previsionnel.class.php";
			$programmes_ids = GETPOST("programmes_previsionnels", "array");
			if (!empty($programmes_ids) && is_array($programmes_ids)) {
				foreach ($programmes_ids as $prog_id) {
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."propal_programme_previsionnel";
					$sql .= " (fk_propal, fk_programme_previsionnel, date_creation, fk_user_creation)";
					$sql .= " VALUES (".((int) $id).", ".((int) $prog_id).", NOW(), ".((int) $user->id).")";
					$db->query($sql);
				}
			}
		}
		if ($id > 0) {';

$content = preg_replace($pattern2, $replacement2, $content, 1);

// 3. Ajouter la sauvegarde après la deuxième création (si différente)
$pattern3 = '/(\$id = \$object->create\(\$user\);\s+}\s+if \(\$id > 0\) \{)/';
$replacement3 = '$id = $object->create($user);
		// Sauvegarder les programmes prévisionnels
		if ($id > 0) {
			require_once DOL_DOCUMENT_ROOT."/custom/class/programme_previsionnel.class.php";
			$programmes_ids = GETPOST("programmes_previsionnels", "array");
			if (!empty($programmes_ids) && is_array($programmes_ids)) {
				foreach ($programmes_ids as $prog_id) {
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."propal_programme_previsionnel";
					$sql .= " (fk_propal, fk_programme_previsionnel, date_creation, fk_user_creation)";
					$sql .= " VALUES (".((int) $id).", ".((int) $prog_id).", NOW(), ".((int) $user->id).")";
					$db->query($sql);
				}
			}
		}
	}
	if ($id > 0) {';

$content = preg_replace($pattern3, $replacement3, $content, 1);

// Vérifier si des modifications ont été faites
if ($content !== $original_content) {
    // Créer un backup
    copy($card_file, $card_file . '.backup_' . date('YmdHis'));
    
    // Sauvegarder
    file_put_contents($card_file, $content);
    echo "✓ Modifications appliquées avec succès\n";
    echo "✓ Backup créé : " . $card_file . '.backup_' . date('YmdHis') . "\n";
} else {
    echo "⚠ Aucune modification appliquée. Les patterns n'ont pas été trouvés.\n";
}

