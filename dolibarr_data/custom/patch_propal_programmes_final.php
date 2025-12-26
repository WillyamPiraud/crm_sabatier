<?php
/**
 * Script pour ajouter le champ "Programmes prévisionnels" dans le formulaire de création de propal
 */

$card_file = '/var/www/html/comm/propal/card.php';

if (!file_exists($card_file)) {
    die("Erreur : Le fichier $card_file n'existe pas\n");
}

// Lire le fichier
$lines = file($card_file);
$new_lines = array();
$modified = false;

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $new_lines[] = $line;
    
    // 1. Ajouter le champ dans le formulaire (après incoterms, avant Template)
    if (strpos($line, '// Template to use by default') !== false && 
        strpos($lines[$i-1] ?? '', 'print \'</td></tr>\';') !== false) {
        
        // Insérer le code pour les programmes prévisionnels
        $code = "\t\t// Programmes prévisionnels\n";
        $code .= "\t\trequire_once DOL_DOCUMENT_ROOT.\"/custom/class/programme_previsionnel.class.php\";\n";
        $code .= "\t\t\$programme = new ProgrammePrevisionnel(\$db);\n";
        $code .= "\t\t\$programmes_actifs = \$programme->listAll(1); // 1 = actifs seulement\n";
        $code .= "\t\t\n";
        $code .= "\t\t// Récupérer les programmes déjà associés (si édition)\n";
        $code .= "\t\t\$programmes_selectionnes = array();\n";
        $code .= "\t\tif (isset(\$object->id) && \$object->id > 0) {\n";
        $code .= "\t\t\t\$sql = \"SELECT fk_programme_previsionnel FROM \".MAIN_DB_PREFIX.\"propal_programme_previsionnel WHERE fk_propal = \".((int) \$object->id);\n";
        $code .= "\t\t\t\$resql = \$db->query(\$sql);\n";
        $code .= "\t\t\tif (\$resql) {\n";
        $code .= "\t\t\t\twhile (\$obj = \$db->fetch_object(\$resql)) {\n";
        $code .= "\t\t\t\t\t\$programmes_selectionnes[] = \$obj->fk_programme_previsionnel;\n";
        $code .= "\t\t\t\t}\n";
        $code .= "\t\t\t}\n";
        $code .= "\t\t}\n";
        $code .= "\t\t\n";
        $code .= "\t\tif (count(\$programmes_actifs) > 0) {\n";
        $code .= "\t\t\tprint \"<tr class=\\\"field_programmes_previsionnels\\\">\";\n";
        $code .= "\t\t\tprint \"<td class=\\\"titlefieldcreate\\\">Programmes Prévisionnels</td><td class=\\\"valuefieldcreate\\\">\";\n";
        $code .= "\t\t\tprint \"<select name=\\\"programmes_previsionnels[]\\\" multiple=\\\"multiple\\\" size=\\\"5\\\" class=\\\"minwidth300\\\">\";\n";
        $code .= "\t\t\tforeach (\$programmes_actifs as \$prog) {\n";
        $code .= "\t\t\t\t\$selected = in_array(\$prog[\"id\"], \$programmes_selectionnes) ? \"selected\" : \"\";\n";
        $code .= "\t\t\t\tprint \"<option value=\\\"\".\$prog[\"id\"].\"\\\" \".\$selected.\">\".\$prog[\"label\"].\"</option>\";\n";
        $code .= "\t\t\t}\n";
        $code .= "\t\t\tprint \"</select>\";\n";
        $code .= "\t\t\tprint \"<br><small>Maintenez Ctrl pour sélectionner plusieurs programmes</small>\";\n";
        $code .= "\t\t\tprint \"</td></tr>\";\n";
        $code .= "\t\t}\n\n";
        
        $new_lines[] = $code;
        $modified = true;
        echo "✓ Champ ajouté dans le formulaire (ligne " . ($i+1) . ")\n";
    }
    
    // 2. Ajouter la sauvegarde après la création
    if (preg_match('/\$id = \$object->create\(\$user\);/', $line) && 
        isset($lines[$i+1]) && strpos($lines[$i+1], 'if ($id > 0)') !== false) {
        
        // Vérifier si déjà modifié
        if (strpos($lines[$i+2] ?? '', 'Sauvegarder les programmes prévisionnels') === false) {
            $code = "\t\t\t// Sauvegarder les programmes prévisionnels\n";
            $code .= "\t\t\tif (\$id > 0) {\n";
            $code .= "\t\t\t\trequire_once DOL_DOCUMENT_ROOT.\"/custom/class/programme_previsionnel.class.php\";\n";
            $code .= "\t\t\t\t\$programmes_ids = GETPOST(\"programmes_previsionnels\", \"array\");\n";
            $code .= "\t\t\t\tif (!empty(\$programmes_ids) && is_array(\$programmes_ids)) {\n";
            $code .= "\t\t\t\t\tforeach (\$programmes_ids as \$prog_id) {\n";
            $code .= "\t\t\t\t\t\t\$sql = \"INSERT INTO \".MAIN_DB_PREFIX.\"propal_programme_previsionnel\";\n";
            $code .= "\t\t\t\t\t\t\$sql .= \" (fk_propal, fk_programme_previsionnel, date_creation, fk_user_creation)\";\n";
            $code .= "\t\t\t\t\t\t\$sql .= \" VALUES (\".((int) \$id).\", \".((int) \$prog_id).\", NOW(), \".((int) \$user->id).\")\";\n";
            $code .= "\t\t\t\t\t\t\$db->query(\$sql);\n";
            $code .= "\t\t\t\t\t}\n";
            $code .= "\t\t\t\t}\n";
            $code .= "\t\t\t}\n";
            
            // Insérer après la ligne suivante (if ($id > 0))
            $new_lines[] = $lines[$i+1]; // La ligne if ($id > 0)
            $new_lines[] = $code;
            $i++; // Sauter la ligne suivante car on l'a déjà ajoutée
            $modified = true;
            echo "✓ Sauvegarde ajoutée après création (ligne " . ($i+1) . ")\n";
        }
    }
}

if ($modified) {
    // Créer un backup
    copy($card_file, $card_file . '.backup_' . date('YmdHis'));
    
    // Sauvegarder
    file_put_contents($card_file, implode('', $new_lines));
    echo "✓ Modifications appliquées avec succès\n";
} else {
    echo "⚠ Aucune modification appliquée\n";
}

