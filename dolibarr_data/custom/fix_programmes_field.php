<?php
/**
 * Script pour corriger le code des programmes prévisionnels dans card.php
 */

$card_file = '/var/www/html/comm/propal/card.php';

// Lire le fichier
$content = file_get_contents($card_file);

// Supprimer toutes les lignes de debug et le code mal formaté
$content = preg_replace('/print "<!-- DEBUG:.*?-->\";\s*/', '', $content);
$content = preg_replace('/if \(count\(\$programmes_actifs\) == 0\) \{.*?\}\s*/s', '', $content);
$content = preg_replace('/\/\/ Debug:.*?\n/', '', $content);

// Trouver et remplacer le bloc mal formaté
$pattern = '/(\s+)\/\/ Programmes prévisionnels\s+require_once.*?listAll\(1\);.*?if \(count\(\$programmes_selectionnes\) > 0\) \{.*?\}\s+if \(count\(\$programmes_actifs\) > 0\) \{/s';
$replacement = '$1// Programmes prévisionnels
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
		
		if (count($programmes_actifs) > 0) {';

$content = preg_replace($pattern, $replacement, $content, 1);

// Sauvegarder
file_put_contents($card_file, $content);
echo "✓ Code corrigé\n";



