<?php
/**
 * Script pour attacher les PDFs des programmes prévisionnels au projet
 * Appelé après la création d'un projet depuis une proposition commerciale
 */

// Log de début
dol_syslog("DEBUG: === DÉBUT SCRIPT ATTACHEMENT ===");

// Vérifier que les variables nécessaires sont définies
if (!isset($project) || !isset($object) || !isset($db) || !isset($conf) || !isset($user)) {
	$error_msg = "ERROR: Variables manquantes pour attacher les fichiers au projet";
	dol_syslog($error_msg, LOG_ERR);
	$debug_messages[] = $error_msg;
	setEventMessage($error_msg, 'errors');
	return;
}

// Vérifier que le projet a été créé avec succès
// $project_id peut ne pas être défini, utiliser $project->id à la place
$project_id_to_use = isset($project_id) && $project_id > 0 ? $project_id : (isset($project->id) && $project->id > 0 ? $project->id : 0);

if ($project_id_to_use <= 0) {
	$error_msg = "Erreur : Projet ID non défini";
	dol_syslog($error_msg." (project_id=".(isset($project_id) ? $project_id : 'NON DÉFINI').", project->id=".(isset($project->id) ? $project->id : 'NON DÉFINI').")", LOG_ERR);
	setEventMessage($error_msg, 'errors');
	return;
}

// Log de début
dol_syslog("DEBUG: === DÉBUT ATTACHEMENT FICHIERS PROJET ===");
dol_syslog("DEBUG: Projet ID: ".$project_id_to_use.", Ref: ".$project->ref);
dol_syslog("DEBUG: Propal ID: ".(isset($object->id) ? $object->id : 'NON DÉFINI').", Ref: ".(isset($object->ref) ? $object->ref : 'NON DÉFINI'));

// Récupérer les programmes prévisionnels associés à la propal
$propal_id = !empty($object->id) ? $object->id : (isset($object->rowid) ? $object->rowid : 0);

if (empty($propal_id)) {
	$error_msg = "Erreur : ID propal non défini";
	dol_syslog($error_msg, LOG_ERR);
	setEventMessage($error_msg, 'errors');
	return;
}

$sql_programmes = "SELECT pp.fk_programme_previsionnel, pr.file_path, pr.file_name";
$sql_programmes .= " FROM ".MAIN_DB_PREFIX."propal_programme_previsionnel pp";
$sql_programmes .= " INNER JOIN ".MAIN_DB_PREFIX."programme_previsionnel pr ON pp.fk_programme_previsionnel = pr.rowid";
$sql_programmes .= " WHERE pp.fk_propal = ".((int)$propal_id);
$sql_programmes .= " AND pr.active = 1";

dol_syslog("DEBUG: Requête SQL: ".$sql_programmes);
$res_programmes = $db->query($sql_programmes);

if (!$res_programmes) {
	$error_msg = "Erreur SQL lors de la récupération des programmes prévisionnels";
	dol_syslog($error_msg.": ".$db->lasterror(), LOG_ERR);
	setEventMessage($error_msg, 'errors');
	return;
}

$num_programmes = $db->num_rows($res_programmes);
dol_syslog("DEBUG: ".$num_programmes." programme(s) prévisionnel(s) trouvé(s)");

if ($num_programmes == 0) {
	dol_syslog("DEBUG: Aucun programme prévisionnel à attacher pour la propal ".$propal_id);
	return;
}

// Créer le répertoire du projet si nécessaire
$project_dir = $conf->project->dir_output.'/'.dol_sanitizeFileName($project->ref);
if (!is_dir($project_dir)) {
	dol_mkdir($project_dir);
	dol_syslog("DEBUG: Répertoire projet créé: ".$project_dir);
}

// Charger les classes nécessaires
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$files_attached = 0;

while ($obj_programme = $db->fetch_object($res_programmes)) {
	dol_syslog("DEBUG: Traitement programme ID=".$obj_programme->fk_programme_previsionnel);
	
	if (empty($obj_programme->file_path)) {
		dol_syslog("WARNING: file_path vide pour le programme ID=".$obj_programme->fk_programme_previsionnel, LOG_WARNING);
		continue;
	}
	
	// Construire le chemin complet du fichier source
	$source_file = DOL_DATA_ROOT.'/'.$obj_programme->file_path;
	
	if (!file_exists($source_file)) {
		dol_syslog("WARNING: Fichier source introuvable: ".$source_file, LOG_WARNING);
		continue;
	}
	
	// Copier le fichier dans le répertoire du projet
	$dest_file = $project_dir.'/'.dol_sanitizeFileName($obj_programme->file_name);
	
	if (!@copy($source_file, $dest_file)) {
		dol_syslog("ERROR: Impossible de copier le fichier ".$source_file." vers ".$dest_file, LOG_ERR);
		continue;
	}
	
	dol_syslog("DEBUG: Fichier copié: ".$dest_file);
	
	// Enregistrer le fichier dans l'ECM du projet
	$ecmfile = new EcmFiles($db);
	$ecmfile->filepath = 'project/'.dol_sanitizeFileName($project->ref).'/'.dol_sanitizeFileName($obj_programme->file_name);
	$ecmfile->filename = $obj_programme->file_name;
	$ecmfile->label = 'Programme prévisionnel - '.$obj_programme->file_name;
	$ecmfile->entity = $conf->entity;
	$ecmfile->share = 'project';
	$ecmfile->src_object_type = 'project';
	$ecmfile->src_object_id = $project_id_to_use;
	$ecmfile->filesize = filesize($dest_file);
	$ecmfile->filetype = 'application/pdf';
	$ecmfile->position = 0;
	$ecmfile->gen_or_uploaded = 'uploaded';
	$ecmfile->extraparams = json_encode(array('fk_project' => $project_id_to_use));
	
	dol_syslog("DEBUG: Création entrée ECM - filepath=".$ecmfile->filepath.", src_object_id=".$ecmfile->src_object_id);
	$result_ecm = $ecmfile->create($user);
	
	if ($result_ecm > 0) {
		$files_attached++;
		dol_syslog("SUCCESS: PDF attaché au projet (ECM ID=".$result_ecm."): ".$obj_programme->file_name);
	} else {
		$error_detail = $ecmfile->error;
		if (!empty($ecmfile->errors)) {
			$error_detail .= " - ".implode(', ', $ecmfile->errors);
		}
		dol_syslog("ERROR: Erreur création ECM: ".$error_detail, LOG_ERR);
	}
}

if ($files_attached > 0) {
	$success_msg = $files_attached." fichier(s) PDF attaché(s) au projet";
	dol_syslog("SUCCESS: ".$success_msg." ".$project->ref);
	setEventMessage($success_msg, 'mesgs');
} else {
	dol_syslog("WARNING: Aucun fichier n'a pu être attaché au projet", LOG_WARNING);
}

dol_syslog("DEBUG: === FIN ATTACHEMENT FICHIERS PROJET ===");

