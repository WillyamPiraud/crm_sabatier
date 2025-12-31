<?php
/**
 * Script pour télécharger le PDF d'une proposition commerciale
 * Utilisé par le bouton "Exporter vers mail"
 */

define('NOTOKENRENEWAL', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);
define('NOREQUIREAJAX', 1);

// Désactiver l'affichage des erreurs pour éviter d'envoyer du contenu avant les headers
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Chemin vers main.inc.php depuis custom/
if (file_exists(__DIR__.'/../../main.inc.php')) {
	require_once __DIR__.'/../../main.inc.php';
} elseif (file_exists('/var/www/html/main.inc.php')) {
	require_once '/var/www/html/main.inc.php';
} else {
	// Utiliser header pour l'erreur au lieu de die pour éviter d'envoyer du contenu
	header('Content-Type: text/plain');
	die('Erreur: Impossible de trouver main.inc.php');
}

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

$id = GETPOST('id', 'int');
$token = GETPOST('token', 'aZ09');

// Vérifier les permissions
if (!$user->hasRight("propal", "lire")) {
	header('Content-Type: text/plain');
	die('Accès refusé');
}

// Charger la proposition
$object = new Propal($db);
$result = $object->fetch($id);
if ($result <= 0) {
	header('Content-Type: text/plain');
	die('Erreur: Impossible de charger la proposition commerciale');
}

// Vérifier le token
if (!dol_verifyToken($token)) {
	header('Content-Type: text/plain');
	die('Token invalide');
}

// Générer le PDF si ce n'est pas déjà fait
if (empty($object->last_main_doc)) {
	$outputlangs = $langs;
	$hidedetails = 0;
	$hidedesc = 0;
	$hideref = 0;
	$result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	if ($result > 0) {
		$object->fetch($id); // Recharger pour avoir last_main_doc
	}
}

// Chemin du fichier PDF
if (!empty($object->last_main_doc)) {
	// Essayer plusieurs chemins possibles
	$pdf_paths = array(
		DOL_DATA_ROOT.'/propal/'.dol_sanitizeFileName($object->ref).'/'.dol_sanitizeFileName($object->last_main_doc),
		'/var/www/documents/propal/'.dol_sanitizeFileName($object->ref).'/'.dol_sanitizeFileName($object->last_main_doc),
		DOL_DATA_ROOT.'/propal/'.$object->ref.'/'.$object->last_main_doc,
		'/var/www/documents/propal/'.$object->ref.'/'.$object->last_main_doc
	);
	
	$pdf_path = null;
	foreach ($pdf_paths as $path) {
		if (file_exists($path)) {
			$pdf_path = $path;
			break;
		}
	}
	
	if ($pdf_path && file_exists($pdf_path)) {
		// Télécharger directement le PDF avec les bons headers
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="'.dol_sanitizeFileName($object->ref).'.pdf"');
		header('Content-Length: '.filesize($pdf_path));
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Expires: 0');
		
		// Lire et envoyer le fichier
		readfile($pdf_path);
		exit;
	} else {
		// Debug: logger les chemins testés
		dol_syslog("PDF non trouvé. Chemins testés: ".implode(", ", $pdf_paths), LOG_WARNING);
		dol_syslog("last_main_doc: ".$object->last_main_doc, LOG_WARNING);
		dol_syslog("ref: ".$object->ref, LOG_WARNING);
		dol_syslog("DOL_DATA_ROOT: ".DOL_DATA_ROOT, LOG_WARNING);
		header('Content-Type: text/plain');
		die('Erreur: Fichier PDF non trouvé. Référence: '.$object->ref.', Fichier: '.$object->last_main_doc);
	}
}

// Si le fichier n'existe pas, erreur
header('Content-Type: text/plain');
die('Erreur: Aucun PDF disponible pour cette proposition commerciale');
?>

