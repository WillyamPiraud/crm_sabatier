<?php
/**
 * Script de débogage pour vérifier pourquoi les fichiers ne sont pas attachés
 */

define('DOL_DOCUMENT_ROOT', '/var/www/html');
require_once DOL_DOCUMENT_ROOT.'/main.inc.php';

// ID du projet
$project_ref = 'PROJ-PR2512-0001-20251230212550';
$project_id = 13; // Récupérer depuis la base

// ID de la propal
$propal_ref = 'PR2512-0001';
$propal_id = 22; // Récupérer depuis la base

echo "=== DÉBOGAGE ATTACHEMENT FICHIERS PROJET ===\n\n";

// 1. Vérifier les programmes prévisionnels de la propal
echo "1. Vérification des programmes prévisionnels pour la propal $propal_ref (ID: $propal_id)...\n";
$sql = "SELECT pp.fk_programme_previsionnel, pr.file_path, pr.file_name, pr.active";
$sql .= " FROM ".MAIN_DB_PREFIX."propal_programme_previsionnel pp";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."programme_previsionnel pr ON pp.fk_programme_previsionnel = pr.rowid";
$sql .= " WHERE pp.fk_propal = ".((int)$propal_id);
$res = $db->query($sql);

if ($res) {
    $num = $db->num_rows($res);
    echo "   ✓ Trouvé $num programme(s) prévisionnel(s)\n";
    
    while ($obj = $db->fetch_object($res)) {
        echo "   - Programme ID: ".$obj->fk_programme_previsionnel.", fichier: ".$obj->file_path.", actif: ".$obj->active."\n";
        
        // Vérifier si le fichier existe
        $source_file = DOL_DATA_ROOT.'/'.$obj->file_path;
        echo "     Chemin complet: $source_file\n";
        echo "     Existe: ".(file_exists($source_file) ? 'OUI' : 'NON')."\n";
    }
} else {
    echo "   ✗ Erreur SQL: ".$db->lasterror()."\n";
}

// 2. Vérifier le répertoire du projet
echo "\n2. Vérification du répertoire du projet $project_ref...\n";
$project_dir = $conf->project->dir_output.'/'.dol_sanitizeFileName($project_ref);
echo "   Chemin: $project_dir\n";
echo "   Existe: ".(is_dir($project_dir) ? 'OUI' : 'NON')."\n";
if (is_dir($project_dir)) {
    $files = scandir($project_dir);
    echo "   Fichiers présents: ".count(array_filter($files, function($f) { return $f != '.' && $f != '..'; }))."\n";
}

// 3. Vérifier les entrées ECM
echo "\n3. Vérification des entrées ECM pour le projet $project_ref (ID: $project_id)...\n";
$sql_ecm = "SELECT rowid, filepath, filename, src_object_type, src_object_id FROM ".MAIN_DB_PREFIX."ecm_files WHERE src_object_type = 'project' AND src_object_id = ".((int)$project_id);
$res_ecm = $db->query($sql_ecm);
if ($res_ecm) {
    $num_ecm = $db->num_rows($res_ecm);
    echo "   ✓ Trouvé $num_ecm entrée(s) ECM\n";
    while ($obj_ecm = $db->fetch_object($res_ecm)) {
        echo "   - ECM ID: ".$obj_ecm->rowid.", fichier: ".$obj_ecm->filename.", chemin: ".$obj_ecm->filepath."\n";
    }
} else {
    echo "   ✗ Erreur SQL: ".$db->lasterror()."\n";
}

echo "\n=== FIN DU DÉBOGAGE ===\n";

