<?php
/**
 * TEST FINAL - Attachement des fichiers au projet
 * Simule exactement le processus de signature d'une proposition
 */

// Définir les constantes AVANT d'inclure main.inc.php
// Mais seulement si elles n'existent pas déjà
if (!defined('DOL_DOCUMENT_ROOT')) {
    define('DOL_DOCUMENT_ROOT', '/var/www/html');
}
if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 0);
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', 1);
}

// Activer l'affichage des erreurs pour le debug (mais ignorer les warnings sur constantes déjà définies)
error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Ignorer les warnings sur constantes déjà définies, propriétés dépréciées et fonctions dépréciées
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Ignorer les warnings sur constantes déjà définies
    if (strpos($errstr, 'already defined') !== false) {
        return true; // Ignorer cette erreur
    }
    // Ignorer les warnings sur propriétés dynamiques dépréciées (PHP 8.2+)
    if (strpos($errstr, 'Creation of dynamic property') !== false) {
        return true; // Ignorer cette erreur
    }
    // Ignorer les warnings sur fonctions dépréciées (mb_substr avec null, etc.)
    if (strpos($errstr, 'is deprecated') !== false || strpos($errstr, 'Passing null') !== false) {
        return true; // Ignorer ces warnings de dépréciation
    }
    // Pour les autres erreurs critiques, les afficher
    if ($errno == E_ERROR || $errno == E_PARSE || $errno == E_CORE_ERROR) {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erreur PHP</title>';
        $html .= '<style>body{font-family:Arial;margin:20px;background:#f5f5f5;} .error{color:red;font-weight:bold;background:white;padding:20px;border:2px solid red;border-radius:5px;}</style>';
        $html .= '</head><body>';
        $html .= '<div class="error">';
        $html .= '<h1>❌ Erreur PHP</h1>';
        $html .= '<p><strong>Erreur:</strong> '.htmlspecialchars($errstr).'</p>';
        $html .= '<p><strong>Fichier:</strong> '.htmlspecialchars($errfile).'</p>';
        $html .= '<p><strong>Ligne:</strong> '.$errline.'</p>';
        $html .= '</div>';
        $html .= '</body></html>';
        die($html);
    }
    return false; // Laisser PHP gérer les autres warnings
}, E_ALL & ~E_WARNING);

// Capturer les erreurs fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erreur Fatale</title>';
        echo '<style>body{font-family:Arial;margin:20px;background:#f5f5f5;} .error{color:red;font-weight:bold;background:white;padding:20px;border:2px solid red;border-radius:5px;}</style>';
        echo '</head><body>';
        echo '<div class="error">';
        echo '<h1>❌ Erreur Fatale PHP</h1>';
        echo '<p><strong>Type:</strong> '.$error['type'].'</p>';
        echo '<p><strong>Message:</strong> '.htmlspecialchars($error['message']).'</p>';
        echo '<p><strong>Fichier:</strong> '.htmlspecialchars($error['file']).'</p>';
        echo '<p><strong>Ligne:</strong> '.$error['line'].'</p>';
        echo '</div>';
        echo '</body></html>';
    }
});

// Inclure main.inc.php - Dolibarr définira DOL_DOCUMENT_ROOT si nécessaire
try {
    require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
} catch (Throwable $e) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erreur</title>';
    echo '<style>body{font-family:Arial;margin:20px;background:#f5f5f5;} .error{color:red;font-weight:bold;background:white;padding:20px;border:2px solid red;border-radius:5px;}</style>';
    echo '</head><body>';
    echo '<div class="error">';
    echo '<h1>❌ Erreur lors du chargement de main.inc.php</h1>';
    echo '<p><strong>Message:</strong> '.htmlspecialchars($e->getMessage()).'</p>';
    echo '<p><strong>Fichier:</strong> '.htmlspecialchars($e->getFile()).'</p>';
    echo '<p><strong>Ligne:</strong> '.$e->getLine().'</p>';
    echo '<pre>'.htmlspecialchars($e->getTraceAsString()).'</pre>';
    echo '</div>';
    echo '</body></html>';
    exit;
}

// Vérifier l'authentification
if (empty($user->id)) {
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Test Attachement Final</title>';
    $html .= '<style>body{font-family:Arial;margin:20px;background:#f5f5f5;} .error{color:red;font-weight:bold;background:white;padding:20px;border:2px solid red;border-radius:5px;}</style>';
    $html .= '</head><body>';
    $html .= '<div class="error">';
    $html .= '<h1>❌ Erreur d\'authentification</h1>';
    $html .= '<p>Vous devez être connecté à Dolibarr pour exécuter ce test.</p>';
    $html .= '<p><a href="'.DOL_URL_ROOT.'/index.php">Cliquez ici pour vous connecter</a></p>';
    $html .= '</div>';
    $html .= '</body></html>';
    die($html);
}

require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$langs->load("propal");
$langs->load("projects");

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Test Attachement Final</title>';
$html .= '<style>body{font-family:Arial;margin:20px;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .warning{color:orange;font-weight:bold;} table{border-collapse:collapse;width:100%;margin:10px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style>';
$html .= '</head><body>';
$html .= '<h1>=== TEST FINAL ATTACHEMENT FICHIERS ===</h1>';

// 1. Trouver une propal récente avec programmes prévisionnels
$html .= '<h2>1. Recherche d\'une proposition commerciale...</h2>';
$sql_propal = "SELECT p.rowid, p.ref, p.fk_soc, p.fk_statut, p.datep";
$sql_propal .= " FROM ".MAIN_DB_PREFIX."propal p";
$sql_propal .= " INNER JOIN ".MAIN_DB_PREFIX."propal_programme_previsionnel pp ON p.rowid = pp.fk_propal";
$sql_propal .= " WHERE p.fk_statut = 2"; // Signée (STATUS_SIGNED = 2)
$sql_propal .= " ORDER BY p.datep DESC";
$sql_propal .= " LIMIT 1";

$res_propal = $db->query($sql_propal);
if (!$res_propal) {
    $html .= '<p class="error">❌ ERREUR SQL: '.$db->lasterror().'</p>';
    $html .= '</body></html>';
    die($html);
}

$num_propal = $db->num_rows($res_propal);
if ($num_propal == 0) {
    $html .= '<p class="warning">⚠️ Aucune proposition signée avec programmes prévisionnels trouvée.</p>';
    $html .= '<p>Créez d\'abord une proposition, ajoutez des programmes, validez-la puis signez-la.</p>';
    $html .= '</body></html>';
    die($html);
}

$obj_propal = $db->fetch_object($res_propal);
$propal_id = $obj_propal->rowid;
$propal_ref = $obj_propal->ref;

$html .= '<p class="success">✓ Proposition trouvée: ID='.$propal_id.', Ref='.$propal_ref.'</p>';

// Charger l'objet propal
$propal = new Propal($db);
$result = $propal->fetch($propal_id);
if ($result <= 0) {
    $html .= '<p class="error">❌ Erreur chargement propal: '.$propal->error.'</p>';
    $html .= '</body></html>';
    die($html);
}

// 2. Vérifier les programmes prévisionnels
$html .= '<h2>2. Vérification des programmes prévisionnels...</h2>';
$sql_programmes = "SELECT pp.fk_programme_previsionnel, pr.file_path, pr.file_name, pr.active";
$sql_programmes .= " FROM ".MAIN_DB_PREFIX."propal_programme_previsionnel pp";
$sql_programmes .= " INNER JOIN ".MAIN_DB_PREFIX."programme_previsionnel pr ON pp.fk_programme_previsionnel = pr.rowid";
$sql_programmes .= " WHERE pp.fk_propal = ".((int)$propal_id);
$sql_programmes .= " AND pr.active = 1";

$res_programmes = $db->query($sql_programmes);
if (!$res_programmes) {
    $html .= '<p class="error">❌ ERREUR SQL: '.$db->lasterror().'</p>';
    $html .= '</body></html>';
    die($html);
}

$num_programmes = $db->num_rows($res_programmes);
$html .= '<p>✓ Programmes prévisionnels trouvés: '.$num_programmes.'</p>';

if ($num_programmes == 0) {
    $html .= '<p class="warning">⚠️ Aucun programme prévisionnel actif trouvé.</p>';
    $html .= '</body></html>';
    die($html);
}

$programmes = array();
while ($obj_prog = $db->fetch_object($res_programmes)) {
    $programmes[] = $obj_prog;
    $html .= '<p>- Programme ID='.$obj_prog->fk_programme_previsionnel.', Fichier: '.$obj_prog->file_name.'</p>';
}

// 3. Vérifier si un projet existe déjà
$html .= '<h2>3. Vérification du projet lié...</h2>';
// Charger les objets liés (comme dans card_propal.php)
if (method_exists($propal, 'load_object_linked')) {
    $propal->load_object_linked();
} else {
    // Méthode alternative si load_object_linked n'existe pas
    // Ne pas inclure link.lib.php s'il n'existe pas
    $propal->linkedObjects = array();
    $propal->linkedObjectsIds = array();
}

$project_existing = null;
if (!empty($propal->linkedObjects['project'])) {
    foreach ($propal->linkedObjects['project'] as $linkedProject) {
        $project_existing = $linkedProject;
        $html .= '<p class="success">✓ Projet existant trouvé: ID='.$linkedProject->id.', Ref='.$linkedProject->ref.'</p>';
        break;
    }
}

// 4. Simuler le processus d'attachement
$html .= '<h2>4. Simulation du processus d\'attachement...</h2>';

if ($project_existing) {
    $project_id = $project_existing->id;
    $project = $project_existing;
    $html .= '<p>Utilisation du projet existant: '.$project->ref.'</p>';
} else {
    $html .= '<p class="warning">⚠️ Aucun projet trouvé. Le projet devrait être créé automatiquement lors de la signature.</p>';
    $html .= '<p>Création d\'un projet de test...</p>';
    
    $db->begin();
    $project = new Project($db);
    $project->entity = $conf->entity;
    $project->ref = 'TEST-ATTACH-'.date('YmdHis');
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
    if ($project_id > 0) {
        $db->commit();
        $html .= '<p class="success">✓ Projet de test créé: ID='.$project_id.', Ref='.$project->ref.'</p>';
    } else {
        $db->rollback();
        $html .= '<p class="error">❌ Erreur création projet: '.$project->error.'</p>';
        $html .= '</body></html>';
        die($html);
    }
}

// 5. Simuler exactement le code de card.php
$html .= '<h2>5. Exécution du script d\'attachement (comme dans card.php)...</h2>';

// Variables comme dans card.php
$object = $propal; // La propal devient $object dans le script

$html .= '<p><strong>Variables définies:</strong></p>';
$html .= '<ul>';
$html .= '<li>project_id = '.$project_id.'</li>';
$html .= '<li>project->id = '.$project->id.'</li>';
$html .= '<li>project->ref = '.$project->ref.'</li>';
$html .= '<li>object->id = '.$object->id.'</li>';
$html .= '<li>object->ref = '.$object->ref.'</li>';
$html .= '<li>db = '.(isset($db) ? 'OUI' : 'NON').'</li>';
$html .= '<li>conf = '.(isset($conf) ? 'OUI' : 'NON').'</li>';
$html .= '<li>user = '.(isset($user) ? 'OUI' : 'NON').'</li>';
$html .= '</ul>';

// Créer le répertoire du projet si nécessaire
$project_dir = $conf->project->dir_output.'/'.dol_sanitizeFileName($project->ref);
if (!is_dir($project_dir)) {
    dol_mkdir($project_dir);
    $html .= '<p>✓ Répertoire projet créé: '.$project_dir.'</p>';
} else {
    $html .= '<p>✓ Répertoire projet existe: '.$project_dir.'</p>';
}

$files_attached = 0;
$errors = array();

foreach ($programmes as $obj_programme) {
    $html .= '<h3>Traitement du programme ID='.$obj_programme->fk_programme_previsionnel.'</h3>';
    
    if (empty($obj_programme->file_path)) {
        $html .= '<p class="warning">⚠️ file_path vide pour le programme ID='.$obj_programme->fk_programme_previsionnel.'</p>';
        continue;
    }
    
    // Construire le chemin complet du fichier source
    $source_file = DOL_DATA_ROOT.'/'.$obj_programme->file_path;
    $html .= '<p>Fichier source: '.$source_file.'</p>';
    
    if (!file_exists($source_file)) {
        $html .= '<p class="error">❌ Fichier source introuvable: '.$source_file.'</p>';
        $errors[] = "Fichier introuvable: ".$source_file;
        continue;
    }
    
    // Copier le fichier dans le répertoire du projet
    $dest_file = $project_dir.'/'.dol_sanitizeFileName($obj_programme->file_name);
    $html .= '<p>Fichier destination: '.$dest_file.'</p>';
    
    if (!@copy($source_file, $dest_file)) {
        $html .= '<p class="error">❌ Impossible de copier le fichier '.$source_file.' vers '.$dest_file.'</p>';
        $errors[] = "Erreur copie: ".$source_file." -> ".$dest_file;
        continue;
    }
    
    $html .= '<p class="success">✓ Fichier copié: '.$dest_file.'</p>';
    
    // Enregistrer le fichier dans l'ECM du projet
    $ecmfile = new EcmFiles($db);
    $ecmfile->filepath = 'project/'.dol_sanitizeFileName($project->ref).'/'.dol_sanitizeFileName($obj_programme->file_name);
    $ecmfile->filename = $obj_programme->file_name;
    $ecmfile->label = 'Programme prévisionnel - '.$obj_programme->file_name;
    $ecmfile->entity = $conf->entity;
    $ecmfile->share = 'project';
    $ecmfile->src_object_type = 'project';
    $ecmfile->src_object_id = $project_id;
    $ecmfile->filesize = filesize($dest_file);
    $ecmfile->filetype = 'application/pdf';
    $ecmfile->position = 0;
    $ecmfile->gen_or_uploaded = 'uploaded';
    $ecmfile->extraparams = json_encode(array('fk_project' => $project_id));
    
    $html .= '<p>Création entrée ECM - filepath='.$ecmfile->filepath.', src_object_id='.$ecmfile->src_object_id.'</p>';
    
    $result_ecm = $ecmfile->create($user);
    
    if ($result_ecm > 0) {
        $files_attached++;
        $html .= '<p class="success">✓ PDF attaché au projet (ECM ID='.$result_ecm.'): '.$obj_programme->file_name.'</p>';
    } else {
        $error_detail = $ecmfile->error;
        if (!empty($ecmfile->errors)) {
            $error_detail .= " - ".implode(', ', $ecmfile->errors);
        }
        $html .= '<p class="error">❌ Erreur création ECM: '.$error_detail.'</p>';
        $errors[] = "Erreur ECM: ".$error_detail;
    }
}

// 6. Vérification finale
$html .= '<h2>6. Vérification finale...</h2>';

$sql_ecm = "SELECT rowid, filepath, filename, src_object_type, src_object_id";
$sql_ecm .= " FROM ".MAIN_DB_PREFIX."ecm_files";
$sql_ecm .= " WHERE src_object_type = 'project' AND src_object_id = ".((int)$project_id);
$sql_ecm .= " ORDER BY rowid DESC";

$res_ecm = $db->query($sql_ecm);
if ($res_ecm) {
    $num_ecm = $db->num_rows($res_ecm);
    $html .= '<p><strong>Entrées ECM trouvées: '.$num_ecm.'</strong></p>';
    
    if ($num_ecm > 0) {
        $html .= '<table>';
        $html .= '<tr><th>ID</th><th>Chemin</th><th>Nom fichier</th><th>Type</th><th>ID Objet</th><th>Taille</th></tr>';
        while ($obj_ecm = $db->fetch_object($res_ecm)) {
            $html .= '<tr>';
            $html .= '<td>'.$obj_ecm->rowid.'</td>';
            $html .= '<td>'.$obj_ecm->filepath.'</td>';
            $html .= '<td>'.$obj_ecm->filename.'</td>';
            $html .= '<td>'.$obj_ecm->src_object_type.'</td>';
            $html .= '<td>'.$obj_ecm->src_object_id.'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        
        if ($files_attached > 0) {
            $html .= '<p class="success" style="font-size:20px;">✅ SUCCÈS : '.$files_attached.' fichier(s) attaché(s) !</p>';
        } else {
            $html .= '<p class="warning" style="font-size:20px;">⚠️ Les fichiers existent en ECM mais n\'ont pas été créés par ce test.</p>';
        }
    } else {
        $html .= '<p class="error" style="font-size:20px;">❌ ÉCHEC : Aucun fichier attaché</p>';
    }
} else {
    $html .= '<p class="error">❌ Erreur SQL ECM: '.$db->lasterror().'</p>';
}

// Vérifier les fichiers physiques
$html .= '<h2>7. Vérification des fichiers physiques...</h2>';
if (is_dir($project_dir)) {
    $html .= '<p class="success">✓ Répertoire existe: '.$project_dir.'</p>';
    $files = scandir($project_dir);
    $pdf_files = array_filter($files, function($f) { return strtolower(pathinfo($f, PATHINFO_EXTENSION)) == 'pdf'; });
    $html .= '<p><strong>Fichiers PDF présents: '.count($pdf_files).'</strong></p>';
    if (count($pdf_files) > 0) {
        $html .= '<ul>';
        foreach ($pdf_files as $file) {
            $html .= '<li>'.$file.'</li>';
        }
        $html .= '</ul>';
    }
} else {
    $html .= '<p class="error">❌ Répertoire n\'existe pas: '.$project_dir.'</p>';
}

// Résumé
$html .= '<h2>=== RÉSUMÉ ===</h2>';
$html .= '<ul>';
$html .= '<li>Proposition: '.$propal_ref.' (ID: '.$propal_id.')</li>';
$html .= '<li>Projet: '.$project->ref.' (ID: '.$project_id.')</li>';
$html .= '<li>Programmes prévisionnels: '.$num_programmes.'</li>';
$html .= '<li>Fichiers attachés: '.$files_attached.'</li>';
$html .= '<li>Entrées ECM: '.$num_ecm.'</li>';
if (!empty($errors)) {
    $html .= '<li class="error">Erreurs: '.count($errors).'</li>';
    $html .= '<ul>';
    foreach ($errors as $err) {
        $html .= '<li class="error">'.$err.'</li>';
    }
    $html .= '</ul>';
}
$html .= '</ul>';

$html .= '<h2>=== FIN DU TEST ===</h2>';
$html .= '</body></html>';

echo $html;

