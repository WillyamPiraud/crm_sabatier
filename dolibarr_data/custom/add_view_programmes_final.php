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

// Chercher la ligne "// Warehouse" dans la vue (après Shipping Method)
$search = "\t\t// Warehouse\n\t\tif (isModEnabled('stock') && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_PROPAL')) {";
$replace = "\t\t// Programmes prévisionnels associés\n\t\tif (\$object->id > 0) {\n\t\t\trequire_once DOL_DOCUMENT_ROOT.\"/custom/class/programme_previsionnel.class.php\";\n\t\t\t\$sql = \"SELECT p.rowid, p.label, p.ref\";\n\t\t\t\$sql .= \" FROM \".MAIN_DB_PREFIX.\"propal_programme_previsionnel pp\";\n\t\t\t\$sql .= \" INNER JOIN \".MAIN_DB_PREFIX.\"programme_previsionnel p ON pp.fk_programme_previsionnel = p.rowid\";\n\t\t\t\$sql .= \" WHERE pp.fk_propal = \".((int) \$object->id);\n\t\t\t\$resql = \$db->query(\$sql);\n\t\t\tif (\$resql && \$db->num_rows(\$resql) > 0) {\n\t\t\t\tprint \"<tr><td>Programmes Prévisionnels</td>\";\n\t\t\t\tprint \"<td>\";\n\t\t\t\twhile (\$obj = \$db->fetch_object(\$resql)) {\n\t\t\t\t\tprint \"<a href=\\\"\".DOL_URL_ROOT.\"/custom/admin/programmes_previsionnels/view_pdf.php?id=\".\$obj->rowid.\"\\\" target=\\\"_blank\\\">\";\n\t\t\t\t\tprint \$obj->label.\" (\".\$obj->ref.\")\";\n\t\t\t\t\tprint \"</a><br>\";\n\t\t\t\t}\n\t\t\t\tprint \"</td></tr>\";\n\t\t\t}\n\t\t}\n\n\t\t// Warehouse\n\t\tif (isModEnabled('stock') && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_PROPAL')) {";

if (strpos($content, $search) !== false) {
    $content = str_replace($search, $replace, $content);
    file_put_contents($card_file, $content);
    echo "✓ Affichage ajouté dans la vue\n";
} else {
    echo "⚠ Pattern non trouvé. Recherche alternative...\n";
    // Essayer avec un pattern plus simple
    $search2 = "\t\t}\n\n\t\t// Warehouse\n\t\tif (isModEnabled('stock')";
    $replace2 = "\t\t}\n\n\t\t// Programmes prévisionnels associés\n\t\tif (\$object->id > 0) {\n\t\t\trequire_once DOL_DOCUMENT_ROOT.\"/custom/class/programme_previsionnel.class.php\";\n\t\t\t\$sql = \"SELECT p.rowid, p.label, p.ref\";\n\t\t\t\$sql .= \" FROM \".MAIN_DB_PREFIX.\"propal_programme_previsionnel pp\";\n\t\t\t\$sql .= \" INNER JOIN \".MAIN_DB_PREFIX.\"programme_previsionnel p ON pp.fk_programme_previsionnel = p.rowid\";\n\t\t\t\$sql .= \" WHERE pp.fk_propal = \".((int) \$object->id);\n\t\t\t\$resql = \$db->query(\$sql);\n\t\t\tif (\$resql && \$db->num_rows(\$resql) > 0) {\n\t\t\t\tprint \"<tr><td>Programmes Prévisionnels</td>\";\n\t\t\t\tprint \"<td>\";\n\t\t\t\twhile (\$obj = \$db->fetch_object(\$resql)) {\n\t\t\t\t\tprint \"<a href=\\\"\".DOL_URL_ROOT.\"/custom/admin/programmes_previsionnels/view_pdf.php?id=\".\$obj->rowid.\"\\\" target=\\\"_blank\\\">\";\n\t\t\t\t\tprint \$obj->label.\" (\".\$obj->ref.\")\";\n\t\t\t\t\tprint \"</a><br>\";\n\t\t\t\t}\n\t\t\t\tprint \"</td></tr>\";\n\t\t\t}\n\t\t}\n\n\t\t// Warehouse\n\t\tif (isModEnabled('stock')";
    
    if (strpos($content, $search2) !== false) {
        $content = str_replace($search2, $replace2, $content);
        file_put_contents($card_file, $content);
        echo "✓ Affichage ajouté dans la vue (pattern alternatif)\n";
    } else {
        echo "✗ Aucun pattern trouvé\n";
    }
}

