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
$search1 = "print '</td></tr>';\n\t\t}\n\n\t\t// Template to use by default";
$replace1 = "print '</td></tr>';\n\t\t}\n\n\t\t// Programmes prévisionnels\n\t\trequire_once DOL_DOCUMENT_ROOT.\"/custom/class/programme_previsionnel.class.php\";\n\t\t\$programme = new ProgrammePrevisionnel(\$db);\n\t\t\$programmes_actifs = \$programme->listAll(1); // 1 = actifs seulement\n\t\t\n\t\t// Récupérer les programmes déjà associés (si édition)\n\t\t\$programmes_selectionnes = array();\n\t\tif (isset(\$object->id) && \$object->id > 0) {\n\t\t\t\$sql = \"SELECT fk_programme_previsionnel FROM \".MAIN_DB_PREFIX.\"propal_programme_previsionnel WHERE fk_propal = \".((int) \$object->id);\n\t\t\t\$resql = \$db->query(\$sql);\n\t\t\tif (\$resql) {\n\t\t\t\twhile (\$obj = \$db->fetch_object(\$resql)) {\n\t\t\t\t\t\$programmes_selectionnes[] = \$obj->fk_programme_previsionnel;\n\t\t\t\t}\n\t\t\t}\n\t\t}\n\t\t\n\t\tif (count(\$programmes_actifs) > 0) {\n\t\t\tprint \"<tr class=\\\"field_programmes_previsionnels\\\">\";\n\t\t\tprint \"<td class=\\\"titlefieldcreate\\\">Programmes Prévisionnels</td><td class=\\\"valuefieldcreate\\\">\";\n\t\t\tprint \"<select name=\\\"programmes_previsionnels[]\\\" multiple=\\\"multiple\\\" size=\\\"5\\\" class=\\\"minwidth300\\\">\";\n\t\t\tforeach (\$programmes_actifs as \$prog) {\n\t\t\t\t\$selected = in_array(\$prog[\"id\"], \$programmes_selectionnes) ? \"selected\" : \"\";\n\t\t\t\tprint \"<option value=\\\"\".\$prog[\"id\"].\"\\\" \".\$selected.\">\".\$prog[\"label\"].\"</option>\";\n\t\t\t}\n\t\t\tprint \"</select>\";\n\t\t\tprint \"<br><small>Maintenez Ctrl pour sélectionner plusieurs programmes</small>\";\n\t\t\tprint \"</td></tr>\";\n\t\t}\n\n\t\t// Template to use by default";

if (strpos($content, $search1) !== false) {
    $content = str_replace($search1, $replace1, $content);
    echo "✓ Champ ajouté dans le formulaire\n";
} else {
    echo "⚠ Pattern 1 non trouvé\n";
}

// 2. Ajouter la sauvegarde après la première création
$search2 = "\$id = \$object->create(\$user);\n\t\t\tif (\$id > 0) {";
$replace2 = "\$id = \$object->create(\$user);\n\t\t\t// Sauvegarder les programmes prévisionnels\n\t\t\tif (\$id > 0) {\n\t\t\t\trequire_once DOL_DOCUMENT_ROOT.\"/custom/class/programme_previsionnel.class.php\";\n\t\t\t\t\$programmes_ids = GETPOST(\"programmes_previsionnels\", \"array\");\n\t\t\t\tif (!empty(\$programmes_ids) && is_array(\$programmes_ids)) {\n\t\t\t\t\tforeach (\$programmes_ids as \$prog_id) {\n\t\t\t\t\t\t\$sql = \"INSERT INTO \".MAIN_DB_PREFIX.\"propal_programme_previsionnel\";\n\t\t\t\t\t\t\$sql .= \" (fk_propal, fk_programme_previsionnel, date_creation, fk_user_creation)\";\n\t\t\t\t\t\t\$sql .= \" VALUES (\".((int) \$id).\", \".((int) \$prog_id).\", NOW(), \".((int) \$user->id).\")\";\n\t\t\t\t\t\t\$db->query(\$sql);\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}\n\t\t\tif (\$id > 0) {";

if (strpos($content, $search2) !== false) {
    $content = str_replace($search2, $replace2, $content);
    echo "✓ Sauvegarde ajoutée après première création\n";
} else {
    echo "⚠ Pattern 2 non trouvé\n";
}

// Vérifier si des modifications ont été faites
if ($content !== $original_content) {
    // Créer un backup
    copy($card_file, $card_file . '.backup_' . date('YmdHis'));
    
    // Sauvegarder
    file_put_contents($card_file, $content);
    echo "✓ Modifications appliquées avec succès\n";
} else {
    echo "⚠ Aucune modification appliquée\n";
}

