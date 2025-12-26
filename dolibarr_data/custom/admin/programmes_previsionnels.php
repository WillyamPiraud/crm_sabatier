<?php
/**
 * Page d'administration des programmes prévisionnels
 * 
 * Ce fichier permet d'accéder à la gestion des programmes prévisionnels
 * directement depuis le menu Configuration → Autres → Programmes prévisionnels
 * 
 * Accès : http://localhost:8080/admin/programmes_previsionnels.php
 * Ou via le menu : Configuration → Autres → Programmes prévisionnels
 */

// Charger l'environnement Dolibarr
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/class/programme_previsionnel.class.php';

// Définir les paramètres de menu pour apparaître sous "Divers"
$mainmenu = 'home';
$leftmenu = 'programmes_previsionnels';

// Langues
$langs->load("admin");
$langs->load("other");

// Accès admin uniquement
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
	$programme->fetch($id);
	$result = $programme->delete();
	if ($result > 0) {
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	} else {
		setEventMessages($programme->error, null, 'errors');
	}
	$action = '';
}

if ($action == 'setactive' && $id > 0) {
	$programme->fetch($id);
	$active = GETPOSTINT('active');
	$sql = "UPDATE ".MAIN_DB_PREFIX."programme_previsionnel SET active = ".((int) $active)." WHERE rowid = ".((int) $id);
	$db->query($sql);
	$action = '';
}

// Header
llxHeader('', "Programmes Prévisionnels");

// Titre
print load_fiche_titre("Programmes Prévisionnels", '', 'title_setup.png');

// Bouton d'ajout
print '<div class="tabsAction">';
print '<a class="butAction" href="'.DOL_URL_ROOT.'/custom/admin/programmes_previsionnels/card.php?action=create">Ajouter un programme</a>';
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
		print '<a href="'.DOL_URL_ROOT.'/custom/admin/programmes_previsionnels/card.php?id='.$prog['id'].'">'.img_edit().'</a> ';
		// Bouton pour visualiser le PDF
		if (!empty($prog['file_name'])) {
			print '<a href="'.DOL_URL_ROOT.'/custom/admin/programmes_previsionnels/view_pdf.php?id='.$prog['id'].'" target="_blank" class="butActionRefused classfortooltip" title="Voir le PDF">'.img_picto('Voir le PDF', 'pdf', 'class="pictofixedwidth"').'</a> ';
		}
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=confirm_delete&id='.$prog['id'].'&confirm=no">'.img_delete().'</a>';
		print '</td>';
		print '</tr>';
	}
} else {
	print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

print '</table>';

llxFooter();
