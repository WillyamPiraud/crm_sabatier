<?php
/**
 * Test complet du processus de signature et création de projet
 */

define('DOL_DOCUMENT_ROOT', '/var/www/html');
require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

echo "<h2>=== TEST PROCESSUS COMPLET ===</h2>\n";

// 1. Récupérer une propal avec programmes prévisionnels
$propal_ref = 'PR2512-0001';
$propal = new Propal($db);
$result = $propal->fetch('', $propal_ref);
if ($result <= 0) {
    die("ERREUR: Propal $propal_ref non trouvée\n");
}
echo "<p>✓ Propal trouvée: ID=$propal->id, Ref=$propal->ref, Status=$propal->status</p>\n";

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
    die("<p style='color:red;'>❌ AUCUN PROGRAMME PRÉVISIONNEL</p>\n");
}

// 3. Simuler la création du projet comme dans card.php
echo "<h3>3. Simulation création projet...</h3>\n";

$db->begin();

$project = new Project($db);
$project->entity = $conf->entity;
$project->ref = 'TEST-PROCESS-'.date('YmdHis');
$project->title = 'Projet Test - '.$propal->ref;
$project->socid = $propal->socid;
$project->date_start = dol_now();
$project->status = 1;
$project->statut = 1;
$project->public = 1;
$project->usage_bill_time = 0;
$project->usage_task_time = 0;
$project->opp_status = '';
$project->opp_amount = 0;
$project->opp_percent = 0;

$project_id = $project->create($user);
echo "<p>Project ID créé: $project_id</p>\n";

if ($project_id <= 0) {
    $db->rollback();
    die("<p style='color:red;'>ERREUR création projet: ".$project->error."</p>\n");
}

// Lier le projet
require_once DOL_DOCUMENT_ROOT.'/core/lib/link.lib.php';
$result_link = $project->add_object_linked('propal', $propal->id);
echo "<p>Liaison projet: ".($result_link > 0 ? 'OK' : 'ÉCHEC')."</p>\n";

// Stocker les infos comme dans card.php
$project_to_attach = array(
    'id' => $project_id,
    'ref' => $project->ref,
    'project_obj' => $project
);

// COMMIT comme dans card.php
$db->commit();
echo "<p>✓ Commit effectué</p>\n";

// 4. Maintenant attacher les fichiers APRÈS le commit (comme dans card.php)
echo "<h3>4. Attachement des fichiers APRÈS commit...</h3>\n";

if (isset($project_to_attach) && !empty($project_to_attach['id']) && $project_to_attach['id'] > 0) {
    $project_id = $project_to_attach['id'];
    $project = $project_to_attach['project_obj'];
    $object = $propal; // La propal devient $object dans le script
    
    echo "<p>Variables définies:</p>\n";
    echo "<ul>\n";
    echo "<li>project_id = $project_id</li>\n";
    echo "<li>project->id = ".$project->id."</li>\n";
    echo "<li>project->ref = ".$project->ref."</li>\n";
    echo "<li>object->id = ".$object->id."</li>\n";
    echo "<li>object->ref = ".$object->ref."</li>\n";
    echo "</ul>\n";
    
    $attach_file = DOL_DOCUMENT_ROOT.'/custom/core/actions/attach_programmes_to_project.inc.php';
    if (file_exists($attach_file)) {
        echo "<p>✓ Script trouvé: $attach_file</p>\n";
        echo "<p>Inclusion du script...</p>\n";
        
        ob_start();
        include $attach_file;
        $output = ob_get_clean();
        
        if (!empty($output)) {
            echo "<p style='background:yellow;padding:10px;'>Output:</p>\n";
            echo "<pre>$output</pre>\n";
        }
    } else {
        die("<p style='color:red;'>❌ Script introuvable: $attach_file</p>\n");
    }
} else {
    echo "<p style='color:red;'>❌ project_to_attach non défini ou invalide</p>\n";
}

// 5. Vérifier les résultats
echo "<h3>5. Vérification finale...</h3>\n";

$sql_ecm = "SELECT rowid, filepath, filename, src_object_type, src_object_id FROM ".MAIN_DB_PREFIX."ecm_files WHERE src_object_type = 'project' AND src_object_id = ".((int)$project_id);
$res_ecm = $db->query($sql_ecm);
if ($res_ecm) {
    $num_ecm = $db->num_rows($res_ecm);
    echo "<p>✓ Entrées ECM trouvées: $num_ecm</p>\n";
    
    if ($num_ecm > 0) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>ID</th><th>Chemin</th><th>Nom fichier</th></tr>\n";
        while ($obj_ecm = $db->fetch_object($res_ecm)) {
            echo "<tr>";
            echo "<td>".$obj_ecm->rowid."</td>";
            echo "<td>".$obj_ecm->filepath."</td>";
            echo "<td>".$obj_ecm->filename."</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        echo "<p style='color:green;font-weight:bold;font-size:20px;'>✅ SUCCÈS : $num_ecm fichier(s) attaché(s) !</p>\n";
    } else {
        echo "<p style='color:red;font-weight:bold;font-size:20px;'>❌ ÉCHEC : Aucun fichier attaché</p>\n";
    }
}

echo "<h2>=== FIN DU TEST ===</h2>\n";

