<?php
/**
 * Script pour ajouter le champ "Programmes prévisionnels" dans les propositions commerciales
 * Ce script modifie directement le fichier card.php
 */

$card_file = '/var/www/html/comm/propal/card.php';

// Code à ajouter après la création de la proposition (après $id = $object->create($user);)
$code_save = '
		// Sauvegarder les programmes prévisionnels
		if ($id > 0) {
			require_once DOL_DOCUMENT_ROOT.\'/custom/class/programme_previsionnel.class.php\';
			$programmes_ids = GETPOST(\'programmes_previsionnels\', \'array\');
			
			if (!empty($programmes_ids) && is_array($programmes_ids)) {
				foreach ($programmes_ids as $prog_id) {
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."propal_programme_previsionnel";
					$sql .= " (fk_propal, fk_programme_previsionnel, date_creation, fk_user_creation)";
					$sql .= " VALUES (".((int) $id).", ".((int) $prog_id).", NOW(), ".((int) $user->id).")";
					$db->query($sql);
				}
			}
		}
';

// Code à ajouter dans le formulaire de création (après le champ Projet)
$code_form = '
		// Programmes prévisionnels
		require_once DOL_DOCUMENT_ROOT.\'/custom/class/programme_previsionnel.class.php\';
		$programme = new ProgrammePrevisionnel($db);
		$programmes_actifs = $programme->listAll(1); // 1 = actifs seulement
		
		if (count($programmes_actifs) > 0) {
			print \'<tr class="field_programmes_previsionnels">\';
			print \'<td class="titlefieldcreate">\'.$langs->trans("ProgrammesPrevisionnels").\'</td><td class="valuefieldcreate">\';
			print \'<select name="programmes_previsionnels[]" multiple="multiple" size="5" class="minwidth300">\';
			foreach ($programmes_actifs as $prog) {
				print \'<option value="\'.$prog[\'id\'].\'">\'.$prog[\'label\'].\'</option>\';
			}
			print \'</select>\';
			print \'<br><small>\'.$langs->trans("HoldCtrlToSelectMultiple").\'</small>\';
			print \'</td></tr>\';
		}
';

// Code à ajouter dans la vue (après les autres champs)
$code_view = '
		// Programmes prévisionnels associés
		if ($object->id > 0) {
			require_once DOL_DOCUMENT_ROOT.\'/custom/class/programme_previsionnel.class.php\';
			$sql = "SELECT p.rowid, p.label, p.ref";
			$sql .= " FROM ".MAIN_DB_PREFIX."propal_programme_previsionnel pp";
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."programme_previsionnel p ON pp.fk_programme_previsionnel = p.rowid";
			$sql .= " WHERE pp.fk_propal = ".((int) $object->id);
			$resql = $db->query($sql);
			if ($resql && $db->num_rows($resql) > 0) {
				print \'<tr><td>\'.$langs->trans("ProgrammesPrevisionnels").\'</td>\';
				print \'<td>\';
				while ($obj = $db->fetch_object($resql)) {
					print \'<a href="\'.DOL_URL_ROOT.\'/custom/admin/programmes_previsionnels/view_pdf.php?id=\'.$obj->rowid.\'" target="_blank">\';
					print $obj->label.\' (\'.$obj->ref.\')\';
					print \'</a><br>\';
				}
				print \'</td></tr>\';
			}
		}
';

echo "Script de modification créé. Utilisez ce script pour modifier card.php manuellement.\n";
echo "Les modifications doivent être faites à 3 endroits :\n";
echo "1. Après la création (ligne ~650)\n";
echo "2. Dans le formulaire de création (ligne ~2500)\n";
echo "3. Dans la vue (ligne ~3180)\n";



