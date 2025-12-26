<?php
/**
 * Page de création/édition d'un programme prévisionnel
 * 
 * Cette page permet de :
 * - Créer un nouveau programme prévisionnel avec upload de PDF
 * - Modifier un programme existant
 * - Gérer les fichiers PDF associés
 */

// Charger l'environnement Dolibarr
require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/class/programme_previsionnel.class.php';

// Langues
$langs->load("admin");
$langs->load("other");

// Accès
if (!$user->admin) {
	accessforbidden();
}

// Paramètres
$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');
$cancel = GETPOST('cancel', 'alpha');

// Initialiser l'objet
$programme = new ProgrammePrevisionnel($db);

// Charger si édition
if ($id > 0) {
	$programme->fetch($id);
}

// Traitement du formulaire
$error = 0;
if ($action == 'add' || $action == 'update') {
	$programme->ref = GETPOST('ref', 'alpha');
	$programme->label = GETPOST('label', 'alphanohtml');
	$programme->description = GETPOST('description', 'restricthtml');

	// Gestion de l'upload du fichier
	if (!empty($_FILES['file']['name'])) {
		// Utiliser DOL_DATA_ROOT qui pointe vers /var/www/documents
		$upload_dir = DOL_DATA_ROOT.'/programmes_previsionnels';
		if (!is_dir($upload_dir)) {
			dol_mkdir($upload_dir);
		}

		$tmpfile = $_FILES['file']['tmp_name'];
		$filename = $_FILES['file']['name'];
		$filesize = $_FILES['file']['size'];

		// Vérifier que c'est un PDF
		$fileinfo = pathinfo($filename);
		if (strtolower($fileinfo['extension']) != 'pdf') {
			setEventMessages($langs->trans("ErrorFileMustBePDF"), null, 'errors');
			$error++;
		}

		if (!$error) {
			// Générer un nom de fichier unique
			$newfilename = dol_sanitizeFileName($programme->ref.'_'.time().'.pdf');
			$destfile = $upload_dir.'/'.$newfilename;

			if (move_uploaded_file($tmpfile, $destfile)) {
				$programme->file_path = 'programmes_previsionnels/'.$newfilename;
				$programme->file_name = $filename;
				$programme->file_size = $filesize;
			} else {
				setEventMessages($langs->trans("ErrorFailedToSaveFile").' : '.$destfile, null, 'errors');
				$error++;
			}
		}
	}

	if (!$error) {
		if ($action == 'add') {
			$result = $programme->create($user);
			if ($result > 0) {
				setEventMessages($langs->trans("RecordCreated"), null, 'mesgs');
				header("Location: list.php");
				exit;
			} else {
				setEventMessages($programme->error, null, 'errors');
				$error++;
			}
		} else {
			$result = $programme->update($user);
			if ($result > 0) {
				setEventMessages($langs->trans("RecordUpdated"), null, 'mesgs');
				header("Location: list.php");
				exit;
			} else {
				setEventMessages($programme->error, null, 'errors');
				$error++;
			}
		}
	}
}

if ($cancel) {
	header("Location: list.php");
	exit;
}

// Header
llxHeader('', $langs->trans("ProgrammePrevisionnel"));

// Formulaire
$form = new Form($db);

print load_fiche_titre($langs->trans($action == 'create' ? "NewProgrammePrevisionnel" : "EditProgrammePrevisionnel"), '', 'title_setup.png');

print '<form name="programme" action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="'.($action == 'create' ? 'add' : 'update').'">';
if ($id > 0) {
	print '<input type="hidden" name="id" value="'.$id.'">';
}

print '<table class="border centpercent">';

// Référence
print '<tr><td class="fieldrequired">'.$langs->trans("Ref").'</td>';
print '<td><input type="text" name="ref" value="'.dol_escape_htmltag($programme->ref).'" class="minwidth200"></td></tr>';

// Libellé
print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td>';
print '<td><input type="text" name="label" value="'.dol_escape_htmltag($programme->label).'" class="minwidth300"></td></tr>';

// Description
print '<tr><td>'.$langs->trans("Description").'</td>';
print '<td><textarea name="description" rows="3" class="minwidth300">'.dol_escape_htmltag($programme->description).'</textarea></td></tr>';

// Fichier PDF
print '<tr><td class="fieldrequired">'.$langs->trans("PDFFile").'</td>';
print '<td>';
if (!empty($programme->file_name)) {
	print '<div class="marginbottomonly">';
	print $langs->trans("CurrentFile").': <strong>'.$programme->file_name.'</strong> ';
	print '<a href="'.DOL_URL_ROOT.'/'.$programme->file_path.'" target="_blank">'.$langs->trans("Download").'</a>';
	print '</div>';
	print $langs->trans("UploadNewFile").': ';
}
print '<input type="file" name="file" accept=".pdf"></td></tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'"> ';
print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
print '</div>';

print '</form>';

llxFooter();

