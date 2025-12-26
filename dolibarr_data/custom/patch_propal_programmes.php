<?php
/**
 * Script pour ajouter le champ "Programmes prévisionnels" dans les propositions commerciales
 * Ce script modifie directement le fichier card.php
 */

$card_file = '/var/www/html/comm/propal/card.php';

// Lire le fichier
$content = file_get_contents($card_file);

// 1. Ajouter le code de sauvegarde après la création (ligne ~618)
$pattern1 = '/\$id = \$object->create\(\$user\);\s+if \(\$id > 0\) \{/';
$replacement1 = '$id = $object->create($user);
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
$content = preg_replace($pattern1, $replacement1, $content, 1);

// 2. Ajouter le champ dans le formulaire (après Incoterms, avant Template)
$pattern2 = '/print \$form->select_incoterms.*?print \'<\/td><\/tr>\';\s+\}\s+print \'<tr class="field_model">\'/s';
$replacement2 = 'print $form->select_incoterms((!empty($soc->fk_incoterms) ? $soc->fk_incoterms : ""), (!empty($soc->location_incoterms) ? $soc->location_incoterms : ""));
			print "</td></tr>";
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

		print "<tr class=\"field_model">';

$content = preg_replace($pattern2, $replacement2, $content, 1);

// 3. Ajouter l'affichage dans la vue (après Shipping Method)
$pattern3 = '/print \'<\/td>\';\s+print \'<\/tr>\';\s+\}\s+print \'<tr><td>\';\s+print \$langs->trans\(\'Warehouse\'\);/s';
$replacement3 = 'print "</td>";
			print "</tr>";
		}

		// Programmes prévisionnels associés
		if ($object->id > 0) {
			require_once DOL_DOCUMENT_ROOT."/custom/class/programme_previsionnel.class.php";
			$sql = "SELECT p.rowid, p.label, p.ref";
			$sql .= " FROM ".MAIN_DB_PREFIX."propal_programme_previsionnel pp";
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."programme_previsionnel p ON pp.fk_programme_previsionnel = p.rowid";
			$sql .= " WHERE pp.fk_propal = ".((int) $object->id);
			$resql = $db->query($sql);
			if ($resql && $db->num_rows($resql) > 0) {
				print "<tr><td>".$langs->trans("ProgrammesPrevisionnels")."</td>";
				print "<td>";
				while ($obj = $db->fetch_object($resql)) {
					print "<a href=\"".DOL_URL_ROOT."/custom/admin/programmes_previsionnels/view_pdf.php?id=".$obj->rowid."\" target=\"_blank">";
					print $obj->label." (".$obj->ref.")";
					print "</a><br>";
				}
				print "</td></tr>";
			}
		}

		print "<tr><td>";
		print $langs->trans("Warehouse");';

$content = preg_replace($pattern3, $replacement3, $content, 1);

// Sauvegarder
file_put_contents($card_file, $content);
echo "✓ Modifications appliquées\n";


