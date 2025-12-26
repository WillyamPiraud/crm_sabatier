<?php
/**
 * Script de test pour vérifier la méthode listAll()
 */
require_once '/var/www/html/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/class/programme_previsionnel.class.php';

$programme = new ProgrammePrevisionnel($db);
$programmes_actifs = $programme->listAll(1);

echo "Nombre de programmes actifs: ".count($programmes_actifs)."\n";
echo "Contenu du tableau:\n";
print_r($programmes_actifs);

// Test de la requête SQL directement
$sql = "SELECT rowid, ref, label, description, file_path, file_name";
$sql .= " FROM ".MAIN_DB_PREFIX."programme_previsionnel";
$sql .= " WHERE entity IN (1)";
$sql .= " AND active = 1";
$sql .= " ORDER BY label ASC";

echo "\nRequête SQL:\n".$sql."\n\n";

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    echo "Nombre de lignes retournées: ".$num."\n";
    while ($obj = $db->fetch_object($resql)) {
        echo "- ID: ".$obj->rowid.", Ref: ".$obj->ref.", Label: ".$obj->label."\n";
    }
} else {
    echo "Erreur SQL: ".$db->lasterror()."\n";
}


