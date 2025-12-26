<?php
/**
 * Script pour visualiser les fichiers PDF des programmes prévisionnels
 */

// Charger l'environnement Dolibarr
require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/class/programme_previsionnel.class.php';

// Accès admin uniquement
if (!$user->admin) {
	accessforbidden();
}

// Récupérer le paramètre
$id = GETPOSTINT('id');

if ($id <= 0) {
	http_response_code(404);
	exit;
}

// Charger le programme
$programme = new ProgrammePrevisionnel($db);
$result = $programme->fetch($id);

if ($result <= 0 || empty($programme->file_path)) {
	http_response_code(404);
	exit;
}

// Chemin complet du fichier
$file_path_full = DOL_DATA_ROOT.'/'.$programme->file_path;

if (!file_exists($file_path_full)) {
	http_response_code(404);
	exit;
}

// Envoyer le fichier
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="'.dol_escape_htmltag($programme->file_name).'"');
header('Content-Length: '.filesize($file_path_full));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($file_path_full);
exit;


