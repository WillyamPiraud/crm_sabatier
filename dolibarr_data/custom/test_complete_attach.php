<?php
/**
 * Script de test complet pour vérifier l'attachement des fichiers
 */

define('DOL_DOCUMENT_ROOT', '/var/www/html');
require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

echo "<h2>=== TEST COMPLET ATTACHEMENT FICHIERS ===</h2>\n";

// 1. Récupérer une propal avec programmes prévisionnels
$propal_ref = 'PR2512-0001';
$propal = new Propal($db);
$result = $propal->fetch('', $propal_ref);
if ($result <= 0) {
    die("ERREUR: Propal $propal_ref non trouvée\n");
}
echo "<p>✓ Propal trouvée: ID=$propal->id, Ref=$propal->ref</p>\n";

// 2. Vérifier les programmes prévisionnels
$sql = "SELECT pp.fk_programme_previsionnel, pr.file_path, pr.file_name, pr.active";
$sql .= " FROM ".MAIN_DB_PREFIX."propal_programme_previsionnel pp";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."programme_previsionnel pr ON pp.fk_programme_previsionnel = pr.rowid";
$sql .= " WHERE pp.fk_propal = ".((int)$propal->id);
$res = $db->query($sql);

if (!$res) {
    die("ERREUR SQL: ".$db->lasterror()."\n");
}

$num = $db->num_rows($res);
echo "<p>✓ Programmes prévisionnels trouvés: $num</p>\n";

if ($num == 0) {
    die("<p style='color:red;'>❌ AUCUN PROGRAMME PRÉVISIONNEL - C'est le problème !</p>\n");
}

// 3. Récupérer ou créer un projet de test
$project_ref = 'PROJ-PR2512-0001-20251230212550';
$project = new Project($db);
$result = $project->fetch('', $project_ref);

if ($result <= 0) {
    echo "<p>⚠️ Projet $project_ref non trouvé, création d'un projet de test...</p>\n";
    
    $project->ref = 'TEST-'.date('YmdHis');
    $project->title = 'Projet Test - '.$propal->ref;
    $project->socid = $propal->socid;
    $project->entity = $conf->entity;
    $project->date_start = dol_now();
    $project->status = 1;
    $project->statut = 1;
    $project->public = 1;
    
    $project_id = $project->create($user);
    if ($project_id <= 0) {
        die("<p style='color:red;'>ERREUR: Impossible de créer le projet: ".$project->error."</p>\n");
    }
    echo "<p>✓ Projet créé: ID=$project_id, Ref=$project->ref</p>\n";
} else {
    $project_id = $project->id;
    echo "<p>✓ Projet trouvé: ID=$project_id, Ref=$project->ref</p>\n";
}

// 4. Simuler l'appel du script d'attachement
echo "<h3>4. Exécution du script d'attachement...</h3>\n";

// Définir les variables comme dans card.php
$object = $propal; // La propal devient $object dans le script

// Inclure le script d'attachement
$attach_file = DOL_DOCUMENT_ROOT.'/custom/core/actions/attach_programmes_to_project.inc.php';
if (file_exists($attach_file)) {
    echo "<p>✓ Script trouvé: $attach_file</p>\n";
    echo "<p>Variables avant inclusion:</p>\n";
    echo "<ul>\n";
    echo "<li>project_id = ".(isset($project_id) ? $project_id : 'NON DÉFINI')."</li>\n";
    echo "<li>project->id = ".(isset($project->id) ? $project->id : 'NON DÉFINI')."</li>\n";
    echo "<li>object->id = ".(isset($object->id) ? $object->id : 'NON DÉFINI')."</li>\n";
    echo "<li>object->ref = ".(isset($object->ref) ? $object->ref : 'NON DÉFINI')."</li>\n";
    echo "</ul>\n";
    
    // Capturer les messages
    ob_start();
    include $attach_file;
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "<p style='background:yellow;padding:10px;'>Output du script:</p>\n";
        echo "<pre>$output</pre>\n";
    }
} else {
    die("<p style='color:red;'>❌ Script introuvable: $attach_file</p>\n");
}

// 5. Vérifier les fichiers attachés
echo "<h3>5. Vérification des fichiers attachés...</h3>\n";

$sql_ecm = "SELECT rowid, filepath, filename, src_object_type, src_object_id FROM ".MAIN_DB_PREFIX."ecm_files WHERE src_object_type = 'project' AND src_object_id = ".((int)$project_id);
$res_ecm = $db->query($sql_ecm);
if ($res_ecm) {
    $num_ecm = $db->num_rows($res_ecm);
    echo "<p>✓ Entrées ECM trouvées: $num_ecm</p>\n";
    
    if ($num_ecm > 0) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>ID</th><th>Chemin</th><th>Nom fichier</th><th>Type</th><th>ID Objet</th></tr>\n";
        while ($obj_ecm = $db->fetch_object($res_ecm)) {
            echo "<tr>";
            echo "<td>".$obj_ecm->rowid."</td>";
            echo "<td>".$obj_ecm->filepath."</td>";
            echo "<td>".$obj_ecm->filename."</td>";
            echo "<td>".$obj_ecm->src_object_type."</td>";
            echo "<td>".$obj_ecm->src_object_id."</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        echo "<p style='color:green;font-weight:bold;'>✅ SUCCÈS : $num_ecm fichier(s) attaché(s) !</p>\n";
    } else {
        echo "<p style='color:red;font-weight:bold;'>❌ ÉCHEC : Aucun fichier attaché</p>\n";
    }
} else {
    echo "<p style='color:red;'>ERREUR SQL ECM: ".$db->lasterror()."</p>\n";
}

// 6. Vérifier les fichiers physiques
echo "<h3>6. Vérification des fichiers physiques...</h3>\n";
$project_dir = $conf->project->dir_output.'/'.dol_sanitizeFileName($project->ref);
if (is_dir($project_dir)) {
    $files = scandir($project_dir);
    $files = array_filter($files, function($f) { return $f != '.' && $f != '..'; });
    echo "<p>✓ Répertoire existe: $project_dir</p>\n";
    echo "<p>✓ Fichiers présents: ".count($files)."</p>\n";
    if (count($files) > 0) {
        echo "<ul>\n";
        foreach ($files as $file) {
            echo "<li>$file</li>\n";
        }
        echo "</ul>\n";
    }
} else {
    echo "<p style='color:red;'>❌ Répertoire n'existe pas: $project_dir</p>\n";
}

echo "<h2>=== FIN DU TEST ===</h2>\n";

