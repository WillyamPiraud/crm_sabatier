<?php
/**
 * Page de liste des programmes prévisionnels
 * 
 * Cette page permet de :
 * - Voir tous les programmes prévisionnels
 * - Créer un nouveau programme
 * - Modifier un programme existant
 * - Supprimer un programme
 * - Activer/désactiver un programme
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
$confirm = GETPOST('confirm', 'alpha');

// Initialiser l'objet
$programme = new ProgrammePrevisionnel($db);

// Actions
if ($action == 'confirm_delete' && $confirm == 'yes') {
	// La vérification du token CSRF se fait automatiquement dans main.inc.php
	// Si on arrive ici, c'est que le token est valide
	$programme->fetch($id);
	$result = $programme->delete();
	if ($result > 0) {
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	} else {
		setEventMessages($programme->error, null, 'errors');
	}
	$action = '';
}

// Afficher la page de confirmation de suppression
if ($action == 'confirm_delete' && $confirm == 'no') {
	$programme->fetch($id);
	llxHeader('', $langs->trans("Programmes Previsionnels"));
	print load_fiche_titre($langs->trans("ConfirmDelete"), '', 'title_setup.png');
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="confirm_delete">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="confirm" value="yes">';
	print '<div class="warning">';
	print $langs->trans("ConfirmDeleteProgrammePrevisionnel", $programme->label);
	print '</div>';
	print '<div class="center">';
	print '<input type="submit" class="button button-delete" value="'.$langs->trans("Delete").'"> ';
	print '<a href="'.$_SERVER["PHP_SELF"].'" class="button button-cancel">'.$langs->trans("Cancel").'</a>';
	print '</div>';
	print '</form>';
	llxFooter();
	exit;
}

// Header
llxHeader('', $langs->trans("Programmes Previsionnels"));

// Titre
print load_fiche_titre($langs->trans("Programmes Previsionnels"), '', 'title_setup.png');

// Bouton d'ajout
print '<div class="tabsAction">';
print '<a class="butAction" href="card.php?action=create">'.$langs->trans("AJOUTER UN PROGRAMME").'</a>';
print '</div>';

// Liste des programmes
$programmes = $programme->listAll(0); // 0 = tous, 1 = actifs seulement

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Ref").'</td>';
print '<td>'.$langs->trans("Label").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("File").'</td>';
print '<td>'.$langs->trans("Status").'</td>';
print '<td class="right">'.$langs->trans("Actions").'</td>';
print '</tr>';

if (count($programmes) > 0) {
	foreach ($programmes as $prog) {
		print '<tr class="oddeven">';
		print '<td>'.$prog['ref'].'</td>';
		print '<td>'.$prog['label'].'</td>';
		print '<td>'.dol_trunc($prog['description'], 50).'</td>';
		print '<td>'.$prog['file_name'].'</td>';
		
		// Statut
		$programme->fetch($prog['id']);
		$statut = $programme->active ? $langs->trans("Active") : $langs->trans("Inactive");
		print '<td>'.$statut.'</td>';
		
		// Actions
		print '<td class="right nowrap">';
		print '<a href="card.php?id='.$prog['id'].'">'.img_edit().'</a> ';
		if (!empty($programme->file_path)) {
			// Construire l'URL vers le fichier PDF
			$file_path_full = DOL_DATA_ROOT.'/'.$programme->file_path;
			if (file_exists($file_path_full)) {
				$file_url = DOL_URL_ROOT.'/custom/admin/programmes_previsionnels/view_pdf.php?id='.$prog['id'];
				print '<a href="'.$file_url.'" target="_blank" title="'.$langs->trans("View").'">'.img_picto($langs->trans("View"), 'pdf').'</a> ';
			}
		}
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=confirm_delete&id='.$prog['id'].'&token='.newToken().'&confirm=no">'.img_delete().'</a>';
		print '</td>';
		print '</tr>';
	}
} else {
	print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

print '</table>';

llxFooter();

