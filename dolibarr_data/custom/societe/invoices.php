<?php
/**
 * Page pour afficher les factures d'un client
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fiscalyear.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "commercial"));

$socid = GETPOSTINT('socid');

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'societe', $socid, '&societe');

$object = new Societe($db);
if ($socid > 0) {
	$result = $object->fetch($socid);
}

/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

$title = $langs->trans("Invoices");
$help_url = 'EN:Third_Party_Setup|FR:Paramétrage_tiers|ES:Configuración_del_tercero';
llxHeader('', $title, $help_url);

if ($socid > 0) {
	$head = societe_prepare_head($object);
	print dol_get_fiche_head($head, 'invoices', $langs->trans("ThirdParty"), -1, 'company');

	// Liste des factures
	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';

	print load_fiche_titre($langs->trans("Invoices"), '', 'invoice');

	// Requête pour récupérer les factures
	$sql = "SELECT f.rowid, f.ref, f.ref_client, f.datef, f.total_ht, f.total_ttc, f.paye, f.fk_statut";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " WHERE f.fk_soc = ".((int) $socid);
	$sql .= " AND f.entity IN (".getEntity('facture').")";
	$sql .= " ORDER BY f.datef DESC, f.ref DESC";

	$resql = $db->query($sql);

	if ($resql) {
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("Ref").'</th>';
		print '<th>'.$langs->trans("Date").'</th>';
		print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th>'.$langs->trans("Status").'</th>';
		print '<th></th>';
		print '</tr>';

		$num = $db->num_rows($resql);
		$i = 0;
		$total_ht = 0;
		$total_ttc = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td><a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$obj->rowid.'">'.$obj->ref.'</a></td>';
			print '<td>'.dol_print_date($db->jdate($obj->datef), 'day').'</td>';
			print '<td class="right">'.price($obj->total_ht).'</td>';
			print '<td class="right">'.price($obj->total_ttc).'</td>';
			print '<td>'.$form->LibStatut($obj->paye, $obj->fk_statut, 3).'</td>';
			print '<td class="right"><a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$obj->rowid.'">'.$langs->trans("View").'</a></td>';
			print '</tr>';

			$total_ht += $obj->total_ht;
			$total_ttc += $obj->total_ttc;
			$i++;
		}

		if ($num > 0) {
			print '<tr class="liste_titre">';
			print '<td colspan="2" class="right"><strong>'.$langs->trans("Total").'</strong></td>';
			print '<td class="right"><strong>'.price($total_ht).'</strong></td>';
			print '<td class="right"><strong>'.price($total_ttc).'</strong></td>';
			print '<td colspan="2"></td>';
			print '</tr>';
		} else {
			print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("NoInvoiceFound").'</td></tr>';
		}

		print '</table>';
	} else {
		dol_print_error($db);
	}

	print '</div>';
	print '</div>';
}

llxFooter();
$db->close();

