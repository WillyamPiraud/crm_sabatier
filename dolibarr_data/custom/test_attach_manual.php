<?php
/**
 * Script de test manuel pour attacher les fichiers au projet existant
 */

define('DOL_DOCUMENT_ROOT', '/var/www/html');
require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

// ID de la dernière propal
$propal_ref = 'PR2512-0001';
$propal_id = 22;

echo "=== TEST ATTACHEMENT FICHIERS ===\n\n";

// 1. Vérifier la propal
$propal = new Propal($db);
$result = $propal->fetch('', $propal_ref);
if ($result <= 0) {
    echo "ERREUR: Propal $propal_ref non trouvée\n";
    exit;
}
echo "✓ Propal trouvée: ID=$propal->id, Ref=$propal->ref\n\n";

// 2. Vérifier les programmes prévisionnels
$sql = "SELECT pp.fk_programme_previsionnel, pr.file_path, pr.file_name, pr.active";
$sql .= " FROM ".MAIN_DB_PREFIX."propal_programme_previsionnel pp";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."programme_previsionnel pr ON pp.fk_programme_previsionnel = pr.rowid";
$sql .= " WHERE pp.fk_propal = ".((int)$propal->id);
$res = $db->query($sql);

if (!$res) {
    echo "ERREUR SQL: ".$db->lasterror()."\n";
    exit;
}

$num = $db->num_rows($res);
echo "✓ Programmes prévisionnels trouvés: $num\n\n";

if ($num == 0) {
    echo "⚠️  AUCUN PROGRAMME PRÉVISIONNEL ASSOCIÉ À CETTE PROPAL !\n";
    echo "C'est probablement pour ça que les fichiers ne sont pas attachés.\n";
    exit;
}

// 3. Vérifier le projet
$project_ref = 'PROJ-PR2512-0001-20251230212550';
$project = new Project($db);
$result = $project->fetch('', $project_ref);
if ($result <= 0) {
    echo "ERREUR: Projet $project_ref non trouvé\n";
    exit;
}
echo "✓ Projet trouvé: ID=$project->id, Ref=$project->ref\n\n";

// 4. Vérifier les fichiers source
while ($obj = $db->fetch_object($res)) {
    echo "Programme ID: ".$obj->fk_programme_previsionnel."\n";
    echo "  Fichier: ".$obj->file_path."\n";
    echo "  Nom: ".$obj->file_name."\n";
    echo "  Actif: ".$obj->active."\n";
    
    $source_file = DOL_DATA_ROOT.'/'.$obj->file_path;
    echo "  Chemin complet: $source_file\n";
    echo "  Existe: ".(file_exists($source_file) ? 'OUI' : 'NON')."\n";
    
    if (!file_exists($source_file)) {
        echo "  ⚠️  FICHIER INTROUVABLE !\n";
        continue;
    }
    
    // 5. Créer le répertoire du projet
    $project_dir = $conf->project->dir_output.'/'.dol_sanitizeFileName($project->ref);
    if (!is_dir($project_dir)) {
        dol_mkdir($project_dir);
        echo "  ✓ Répertoire projet créé: $project_dir\n";
    } else {
        echo "  ✓ Répertoire projet existe: $project_dir\n";
    }
    
    // 6. Copier le fichier
    $dest_file = $project_dir.'/'.dol_sanitizeFileName($obj->file_name);
    if (@copy($source_file, $dest_file)) {
        echo "  ✓ Fichier copié: $dest_file\n";
        
        // 7. Créer l'entrée ECM
        $ecmfile = new EcmFiles($db);
        $ecmfile->filepath = 'project/'.dol_sanitizeFileName($project->ref).'/'.dol_sanitizeFileName($obj->file_name);
        $ecmfile->filename = $obj->file_name;
        $ecmfile->label = 'Programme prévisionnel - '.$obj->file_name;
        $ecmfile->entity = $conf->entity;
        $ecmfile->share = 'project';
        $ecmfile->src_object_type = 'project';
        $ecmfile->src_object_id = $project->id;
        $ecmfile->filesize = filesize($dest_file);
        $ecmfile->filetype = 'application/pdf';
        $ecmfile->position = 0;
        $ecmfile->gen_or_uploaded = 'uploaded';
        $ecmfile->extraparams = json_encode(array('fk_project' => $project->id));
        
        $result_ecm = $ecmfile->create($user);
        if ($result_ecm > 0) {
            echo "  ✅ FICHIER ATTACHÉ (ECM ID: $result_ecm)\n";
        } else {
            echo "  ❌ ERREUR ECM: ".$ecmfile->error."\n";
            if (!empty($ecmfile->errors)) {
                echo "    Détails: ".print_r($ecmfile->errors, true)."\n";
            }
        }
    } else {
        echo "  ❌ ERREUR: Impossible de copier le fichier\n";
    }
    echo "\n";
}

echo "=== FIN DU TEST ===\n";

