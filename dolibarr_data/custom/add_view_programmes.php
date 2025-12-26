<?php
/**
 * Script pour ajouter l'affichage des programmes prévisionnels dans la vue de la propal
 */

$card_file = '/var/www/html/comm/propal/card.php';

if (!file_exists($card_file)) {
    die("Erreur : Le fichier $card_file n'existe pas\n");
}

// Lire le fichier
$content = file_get_contents($card_file);

// Chercher la ligne "// Warehouse" dans la vue (pas dans le formulaire de création)
$pattern = '/(\/\/ Warehouse\s+if \(isModEnabled\(\'stock\'\))/';
$replacement = '// Programmes prévisionnels associés
		if ($object->id > 0) {
			require_once DOL_DOCUMENT_ROOT."/custom/class/programme_previsionnel.class.php";
			$sql = "SELECT p.rowid, p.label, p.ref";
			$sql .= " FROM ".MAIN_DB_PREFIX."propal_programme_previsionnel pp";
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."programme_previsionnel p ON pp.fk_programme_previsionnel = p.rowid";
			$sql .= " WHERE pp.fk_propal = ".((int) $object->id);
			$resql = $db->query($sql);
			if ($resql && $db->num_rows($resql) > 0) {
				print "<tr><td>Programmes Prévisionnels</td>";
				print "<td>";
				while ($obj = $db->fetch_object($resql)) {
					print "<a href=\"".DOL_URL_ROOT."/custom/admin/programmes_previsionnels/view_pdf.php?id=".$obj->rowid."\" target=\"_blank">";
					print $obj->label." (".$obj->ref.")";
					print "</a><br>";
				}
				print "</td></tr>";
			}
		}

		// Warehouse
		if (isModEnabled(\'stock\'))';

$new_content = preg_replace($pattern, $replacement, $content, 1);

if ($new_content !== $content) {
    file_put_contents($card_file, $new_content);
    echo "✓ Affichage ajouté dans la vue\n";
} else {
    echo "⚠ Pattern non trouvé\n";
}

