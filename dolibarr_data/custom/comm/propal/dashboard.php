<?php

require '../../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Vérifier que l'utilisateur a le droit de voir les propositions commerciales
if (!$user->hasRight('propal', 'lire')) {
	accessforbidden();
}

$langs->load('propal');
$langs->load('commercial');

// Récupérer les paramètres de l'URL (filtres, dates, etc.)
$action = GETPOST('action', 'aZ09');
$socid = GETPOST('socid', 'int');
$filter_status = GETPOST('filter_status', 'alpha');
if ($filter_status === '') {
	$filter_status = null;
} else {
	$filter_status = (int)$filter_status;
}
$year = GETPOST('year', 'int') ? GETPOST('year', 'int') : date('Y');

$month = GETPOST('month', 'int') ? GETPOST('month', 'int') : date('m');

// Créer une instance de la classe Propal pour utiliser ses méthodes
$propal = new Propal($db);
$formcompany = new FormCompany($db);

$status_labels = array(
	0 => 'Brouillon',
	1 => 'Envoyée',
	2 => 'Signée',
	3 => 'Non signée',
	4 => 'Facturée'
);

// Stats par status //
$sql = "SELECT p.fk_statut, COUNT(*) as nb, SUM(p.total_ht) as total_ht, SUM(p.total_ttc) as total_ttc";
$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
$sql .= " WHERE p.entity = ".$conf->entity;
if ($socid > 0) {
	$sql .= " AND p.fk_soc = ".((int)$socid);
}
$sql .= " GROUP BY p.fk_statut";
$sql .= " ORDER BY p.fk_statut";

$resql = $db->query($sql);
$stats_by_status = array();
$total_ht = 0;
$total_ttc = 0;
$total_nb = 0;

if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$stats_by_status[$obj->fk_statut] = array(
			'nb' => $obj->nb,
			'total_ht' => $obj->total_ht,
			'total_ttc' => $obj->total_ttc
		);
		$total_ht += $obj->total_ht;
		$total_ttc += $obj->total_ttc;
		$total_nb += $obj->nb;
	}
}

$nb_validated = isset($stats_by_status[Propal::STATUS_VALIDATED]) ? $stats_by_status[Propal::STATUS_VALIDATED]['nb'] : 0;
$nb_signed = isset($stats_by_status[Propal::STATUS_SIGNED]) ? $stats_by_status[Propal::STATUS_SIGNED]['nb'] : 0;
$nb_not_signed = isset($stats_by_status[Propal::STATUS_NOTSIGNED]) ? $stats_by_status[Propal::STATUS_NOTSIGNED]['nb'] : 0;

$conversion_rate = 0;
if ($total_nb > 0) {
	$conversion_rate = round(($nb_signed / $total_nb) * 100, 2);
}

$avg_amount_ht = 0;
$avg_amount_ttc = 0;
if ($total_nb > 0) {
	$avg_amount_ht = $total_ht / $total_nb;
	$avg_amount_ttc = $total_ttc / $total_nb;
}

$total_signed_ht = isset($stats_by_status[Propal::STATUS_SIGNED]) ? $stats_by_status[Propal::STATUS_SIGNED]['total_ht'] : 0;
$total_signed_ttc = isset($stats_by_status[Propal::STATUS_SIGNED]) ? $stats_by_status[Propal::STATUS_SIGNED]['total_ttc'] : 0;

$total_validated_ht = isset($stats_by_status[Propal::STATUS_VALIDATED]) ? $stats_by_status[Propal::STATUS_VALIDATED]['total_ht'] : 0;
$total_validated_ttc = isset($stats_by_status[Propal::STATUS_VALIDATED]) ? $stats_by_status[Propal::STATUS_VALIDATED]['total_ttc'] : 0;

// Relances //
$days_for_relance = 7;
$sql_relance = "SELECT p.rowid, p.ref, p.fk_soc, p.date_valid, p.total_ht, s.nom as client_name";
$sql_relance .= " FROM ".MAIN_DB_PREFIX."propal as p";
$sql_relance .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
$sql_relance .= " WHERE p.entity = ".$conf->entity;
$sql_relance .= " AND p.fk_statut = ".Propal::STATUS_VALIDATED;
$sql_relance .= " AND p.date_valid IS NOT NULL";
$sql_relance .= " AND DATEDIFF(NOW(), p.date_valid) >= ".((int)$days_for_relance);
$sql_relance .= " ORDER BY p.date_valid ASC";
$sql_relance .= " LIMIT 10";

$resql_relance = $db->query($sql_relance);
$relances = array();
if ($resql_relance) {
	while ($obj = $db->fetch_object($resql_relance)) {
		$relances[] = $obj;
	}
}

// Récupérer la liste des clients pour le select
$sql_clients = "SELECT s.rowid, s.nom";
$sql_clients .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql_clients .= " WHERE s.entity IN (".getEntity('societe').")";
$sql_clients .= " AND s.status = 1";
$sql_clients .= " ORDER BY s.nom ASC";
$resql_clients = $db->query($sql_clients);
$clients_list = array();
if ($resql_clients) {
	while ($obj = $db->fetch_object($resql_clients)) {
		$clients_list[$obj->rowid] = $obj->nom;
	}
}

// Liste des propositions filtrés //
// Charger TOUTES les propositions (sans filtres ni pagination) pour le JavaScript
$sql_all = "SELECT p.rowid, p.ref, p.fk_statut, p.datec, p.date_valid, p.datep, p.total_ht, p.total_ttc, s.nom as client_name, s.rowid as client_id";
$sql_all .= " FROM ".MAIN_DB_PREFIX."propal as p";
$sql_all .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
$sql_all .= " WHERE p.entity = ".$conf->entity;
$sql_all .= " ORDER BY p.datec DESC";

$resql_all = $db->query($sql_all);
$all_proposals = array();
$years_available = array();
if ($resql_all) {
	$num_rows = $db->num_rows($resql_all);
	dol_syslog("Dashboard: Chargement de ".$num_rows." propositions pour le filtrage côté client", LOG_DEBUG);
	while ($obj = $db->fetch_object($resql_all)) {
		$status_label = isset($status_labels[$obj->fk_statut]) ? $status_labels[$obj->fk_statut] : 'Inconnu';
		$datep_year = !empty($obj->datep) ? date('Y', strtotime($obj->datep)) : '';
		if (!empty($datep_year)) {
			$years_available[] = $datep_year;
		}
		$all_proposals[] = array(
			'id' => (int)$obj->rowid,
			'ref' => $obj->ref ? $obj->ref : '',
			'status_id' => (int)$obj->fk_statut,
			'status_label' => $status_label,
			'client_id' => (int)$obj->client_id,
			'client_name' => $obj->client_name ? $obj->client_name : '',
			'datec' => $obj->datec ? $obj->datec : '',
			'datep' => $obj->datep ? $obj->datep : '',
			'datep_year' => $datep_year,
			'total_ht' => (float)$obj->total_ht,
			'total_ttc' => (float)$obj->total_ttc
		);
	}
	if (!empty($years_available)) {
		$years_available = array_values(array_unique($years_available));
		rsort($years_available);
	}
} else {
	$error_msg = "Erreur SQL liste propositions: ".$db->lasterror();
	dol_syslog($error_msg, LOG_ERR);
	setEventMessage($error_msg, 'errors');
}

// Plus besoin de pagination côté serveur, tout est géré côté client

llxHeader('', $langs->trans('Dashboard commercial'));

// Titre de la page
print load_fiche_titre($langs->trans('Dashboard commercial'), '', 'propal');

print '<div class="fichecenter">';
print '<div class="fiche">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="6"><strong>Statistiques avancées</strong></td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td><strong>Total des propositions</strong></td>';
print '<td class="right"><strong>'.$total_nb.'</strong></td>';
print '<td><strong>Taux de conversion</strong></td>';
print '<td class="right"><strong style="font-size: 1.2em; color: #28a745;">'.$conversion_rate.'%</strong></td>';
print '<td><strong>Montant moyen</strong></td>';
print '<td class="right"><strong>'.price($avg_amount_ht).'</strong></td>';
print '</tr>';
print '<tr class="oddeven" style="background-color: #fff3cd;">';
print '<td><strong>Propositions envoyées</strong></td>';
print '<td class="right"><strong>'.$nb_validated.'</strong></td>';
print '<td><strong>Montant envoyé</strong></td>';
print '<td class="right"><strong style="font-size: 1.1em; color: #ffc107;">'.price($total_validated_ht).'</strong></td>';
print '<td colspan="2"></td>';
print '</tr>';
print '<tr class="oddeven" style="background-color: #d4edda;">';
print '<td><strong>Propositions acceptées</strong></td>';
print '<td class="right"><strong>'.$nb_signed.'</strong></td>';
print '<td><strong>Montant accepté</strong></td>';
print '<td class="right"><strong style="font-size: 1.1em; color: #28a745;">'.price($total_signed_ht).'</strong></td>';
print '<td colspan="2"></td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td><strong>Montant en attente</strong></td>';
print '<td class="right">'.price($total_validated_ht).'</td>';
print '<td><strong>Total HT</strong></td>';
print '<td class="right"><strong>'.price($total_ht).'</strong></td>';
print '<td><strong>Total TTC</strong></td>';
print '<td class="right"><strong>'.price($total_ttc).'</strong></td>';
print '</tr>';
print '</table>';

print '</div>';
print '</div>';


print '<div class="fichecenter">';
print '<div class="fiche">';

print '<table class="noborder centpercent">';
print '<thead>';
print '<tr class="liste_titre">';
print '<th style="width: 20%;">Statut</th>';
print '<th style="width: 15%;" class="right">Nombre</th>';
print '<th style="width: 20%;" class="right">Montant HT</th>';
print '<th style="width: 20%;" class="right">Montant TTC</th>';
print '<th style="width: 15%;" class="right">Pourcentage</th>';
print '</tr>';
print '</thead>';
print '<tbody>';

foreach ($status_labels as $status_id => $status_label) {
	$nb = isset($stats_by_status[$status_id]) ? $stats_by_status[$status_id]['nb'] : 0;
	$amount_ht = isset($stats_by_status[$status_id]) ? $stats_by_status[$status_id]['total_ht'] : 0;
	$amount_ttc = isset($stats_by_status[$status_id]) ? $stats_by_status[$status_id]['total_ttc'] : 0;
	$percentage = $total_nb > 0 ? round(($nb / $total_nb) * 100, 1) : 0;
	
	print '<tr class="oddeven">';
	print '<td><strong>'.$status_label.'</strong></td>';
	print '<td class="right">'.($nb > 0 ? '<strong>'.$nb.'</strong>' : $nb).'</td>';
	print '<td class="right">'.($amount_ht > 0 ? '<strong>'.price($amount_ht).'</strong>' : price($amount_ht)).'</td>';
	print '<td class="right">'.($amount_ttc > 0 ? '<strong>'.price($amount_ttc).'</strong>' : price($amount_ttc)).'</td>';
	print '<td class="right">'.($percentage > 0 ? '<strong>'.$percentage.'%</strong>' : $percentage.'%').'</td>';
	print '</tr>';
}

print '</tbody>';
print '<tfoot>';
print '<tr class="liste_titre">';
print '<td><strong>Total</strong></td>';
print '<td class="right"><strong>'.$total_nb.'</strong></td>';
print '<td class="right"><strong>'.price($total_ht).'</strong></td>';
print '<td class="right"><strong>'.price($total_ttc).'</strong></td>';
print '<td class="right"><strong>100%</strong></td>';
print '</tr>';
print '</tfoot>';

print '</table>';

print '</div>';
print '</div>';

print '<div class="fichecenter">';
print '<div class="fiche">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="5"><strong>Répartition par statut</strong></td>';
print '</tr>';
print '<tr>';
print '<td colspan="5">';
print '<div style="padding: 20px; background: #f8f9fa; border-radius: 5px;">';

$max_amount = 0;
foreach ($status_labels as $status_id => $status_label) {
	$amount = isset($stats_by_status[$status_id]) ? $stats_by_status[$status_id]['total_ht'] : 0;
	if ($amount > $max_amount) {
		$max_amount = $amount;
	}
}

foreach ($status_labels as $status_id => $status_label) {
	$nb = isset($stats_by_status[$status_id]) ? $stats_by_status[$status_id]['nb'] : 0;
	$amount_ht = isset($stats_by_status[$status_id]) ? $stats_by_status[$status_id]['total_ht'] : 0;
	$percentage = $max_amount > 0 ? round(($amount_ht / $max_amount) * 100, 1) : 0;
	
	$colors = array(
		0 => '#6c757d',
		1 => '#ffc107',
		2 => '#28a745',
		3 => '#dc3545',
		4 => '#17a2b8'
	);
	$color = isset($colors[$status_id]) ? $colors[$status_id] : '#6c757d';
	
	print '<div style="margin-bottom: 15px;">';
	print '<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">';
	print '<span><strong>'.$status_label.'</strong> ('.$nb.')</span>';
	print '<span><strong>'.price($amount_ht).'</strong></span>';
	print '</div>';
	print '<div style="background: #e9ecef; height: 25px; border-radius: 3px; overflow: hidden;">';
	print '<div style="background: '.$color.'; height: 100%; width: '.$percentage.'%; transition: width 0.5s; display: flex; align-items: center; padding-left: 10px; color: white; font-weight: bold;">';
	if ($percentage > 10) {
		print round($percentage, 0).'%';
	}
	print '</div>';
	print '</div>';
	print '</div>';
}

print '</div>';
print '</td>';
print '</tr>';
print '</table>';
print '</div>';
print '</div>';

// Affichage des relances //
if (!empty($relances)) {
	print '<div class="fichecenter">';
	print '<div class="fiche">';
	
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td colspan="5"><strong>Relances nécessaires (validées depuis plus de '.$days_for_relance.' jours)</strong></td>';
	print '</tr>';
	print '<tr class="liste_titre">';
	print '<td>Référence</td>';
	print '<td>Client</td>';
	print '<td>Date de validation</td>';
	print '<td class="right">Montant HT</td>';
	print '<td class="center">Action</td>';
	print '</tr>';
	
	foreach ($relances as $relance) {
		$days_ago = floor((time() - strtotime($relance->date_valid)) / 86400);
		
		print '<tr class="oddeven">';
		print '<td><a href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$relance->rowid.'">'.$relance->ref.'</a></td>';
		print '<td>'.$relance->client_name.'</td>';
		print '<td>'.dol_print_date($relance->date_valid, 'day').' (il y a '.$days_ago.' jours)</td>';
		print '<td class="right">'.price($relance->total_ht).'</td>';
		print '<td class="center"><a href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$relance->rowid.'&action=send" class="button">Envoyer</a></td>';
		print '</tr>';
	}
	
	print '</table>';
	
	print '</div>';
	print '</div>';
}

print '<div class="fichecenter">';
print '<div class="fiche">';

print load_fiche_titre('Liste des propositions', '', 'propal');

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" id="filterForm">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="6"><strong>Filtres</strong></td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>Statut</td>';
print '<td>';
print '<select name="filter_status" id="filter_status" class="flat filter-input">';
print '<option value="">Tous</option>';
foreach ($status_labels as $status_id => $status_label) {
	$selected = ($filter_status !== null && $filter_status !== '' && $filter_status == $status_id) ? 'selected' : '';
	print '<option value="'.$status_id.'" '.$selected.'>'.$status_label.'</option>';
}
print '</select>';
print '</td>';
print '<td>Client</td>';
print '<td>';
print '<select name="socid" id="filter_socid" class="flat filter-input" style="min-width: 200px;">';
print '<option value="0">Tous</option>';
if (!empty($clients_list)) {
	foreach ($clients_list as $client_id => $client_name) {
		$selected = ($socid > 0 && $socid == $client_id) ? 'selected' : '';
		print '<option value="'.$client_id.'" '.$selected.'>'.dol_escape_htmltag($client_name).'</option>';
	}
}
print '</select>';
print '</td>';
print '<td>Année</td>';
print '<td>';
print '<select name="year" id="filter_year" class="flat filter-input">';
print '<option value="">Toutes</option>';
if (!empty($years_available)) {
	foreach ($years_available as $y) {
		$selected = ($year && $year == $y) ? 'selected' : '';
		print '<option value="'.$y.'" '.$selected.'>'.$y.'</option>';
	}
}
print '</select>';
print '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td colspan="6" class="center">';
print ' <a href="'.$_SERVER['PHP_SELF'].'" class="button">Réinitialiser</a>';
print '</td>';
print '</tr>';
print '</table>';
print '</form>';

print '<div class="underbanner clearboth"></div>';

print '<table class="noborder centpercent" id="propal-table">';
print '<thead>';
print '<tr class="liste_titre">';
print '<th>Référence</th>';
print '<th>Client</th>';
print '<th>Statut</th>';
print '<th>Date de création</th>';
print '<th>Date de proposition</th>';
print '<th class="right">Montant HT</th>';
print '<th class="right">Montant TTC</th>';
print '</tr>';
print '</thead>';
print '<tbody id="propal-tbody">';
print '</tbody>';
print '</table>';

print '<div id="pagination-container" style="margin-top: 10px; text-align: center;">';
print '</div>';

print '</div>';
print '</div>';

// Préparer les données JSON AVANT le script JavaScript
if (!isset($all_proposals) || !is_array($all_proposals)) {
	$all_proposals = array();
}

// Nettoyer les valeurs NULL et s'assurer que toutes les valeurs sont sérialisables
$clean_proposals = array();
foreach ($all_proposals as $proposal) {
	$clean_proposal = array();
	foreach ($proposal as $key => $value) {
		if ($value === null) {
			$clean_proposal[$key] = '';
		} elseif (is_string($value)) {
			$clean_proposal[$key] = $value;
		} elseif (is_numeric($value)) {
			$clean_proposal[$key] = $value;
		} else {
			$clean_proposal[$key] = (string)$value;
		}
	}
	$clean_proposals[] = $clean_proposal;
}

// Stocker les données dans un attribut data pour éviter les problèmes de JSON trop long
$json_data = json_encode($clean_proposals, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES);
if ($json_data === false) {
	dol_syslog("Erreur JSON: ".json_last_error_msg(), LOG_ERR);
	$json_data = '[]';
}

// Utiliser base64 pour éviter TOUS les problèmes de caractères spéciaux
$json_base64 = base64_encode($json_data);
print '<script type="text/javascript">';
print 'try {';
print '	var jsonBase64 = "'.$json_base64.'";';
print '	var jsonString = atob(jsonBase64);';
print '	window.propalDashboardData = JSON.parse(jsonString);';
print '} catch(e) {';
print '	console.error("✗ Erreur lors du chargement des données:", e);';
print '	console.error("Message:", e.message);';
print '	console.error("Stack:", e.stack);';
print '	window.propalDashboardData = [];';
print '}';
print '</script>';

// JavaScript pour le filtrage côté client
$dolibarrUrlRootJson = json_encode(DOL_URL_ROOT, JSON_HEX_QUOT | JSON_HEX_TAG);
$jsBlock = <<<JS
<script type="text/javascript">
var dolibarrUrlRoot = $dolibarrUrlRootJson;
var itemsPerPage = 20;
var currentPage = 0;
var allProposals = [];

function filterAndDisplayProposals() {
	if (!allProposals || !Array.isArray(allProposals)) {
		console.error("✗ allProposals n'est pas un tableau valide");
		return;
	}
	var filterStatus = $("#filter_status").val() || '';
	var socid = parseInt($("#filter_socid").val()) || 0;
	var year = parseInt($("#filter_year").val()) || 0;

	// Filtrer les propositions
	var filtered = allProposals.filter(function(item) {
		if (filterStatus !== "" && parseInt(item.status_id) != parseInt(filterStatus)) return false;
		if (socid > 0 && parseInt(item.client_id) != socid) return false;
		if (year > 0 && item.datep_year != year.toString()) return false;
		return true;
	});
	
	// Pagination
	var totalPages = Math.ceil(filtered.length / itemsPerPage);
	var start = currentPage * itemsPerPage;
	var end = start + itemsPerPage;
	var pageData = filtered.slice(start, end);

	// Afficher les résultats
	var tbody = $("#propal-tbody");
	if (tbody.length === 0) {
		console.error("✗ tbody #propal-tbody non trouvé dans le DOM");
		return;
	}
	tbody.empty();

	if (pageData.length > 0) {
		$.each(pageData, function(index, item) {
			var row = $("<" + "tr" + ">").addClass("oddeven");
			var refLink = $("<" + "a" + ">").attr("href", dolibarrUrlRoot + "/comm/propal/card.php?id=" + item.id).text(item.ref || 'N/A');
			row.append($("<" + "td" + ">").append(refLink));
			
			var clientCell = $("<" + "td" + ">");
			if (item.client_id > 0) {
				var clientLink = $("<" + "a" + ">").attr("href", dolibarrUrlRoot + "/societe/card.php?socid=" + item.client_id).text(item.client_name || 'N/A');
				clientCell.append(clientLink);
			} else {
				clientCell.text(item.client_name || 'N/A');
			}
			row.append(clientCell);
			
			// Formater les dates et montants
			var datecFormatted = item.datec ? new Date(item.datec).toLocaleString('fr-FR', {year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'}) : 'N/A';
			var datepFormatted = item.datep ? new Date(item.datep).toLocaleDateString('fr-FR') : 'N/A';
			var totalHtFormatted = item.total_ht ? new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR', minimumFractionDigits: 2}).format(item.total_ht) : '0,00 €';
			var totalTtcFormatted = item.total_ttc ? new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR', minimumFractionDigits: 2}).format(item.total_ttc) : '0,00 €';
			
			row.append($("<" + "td" + ">").text(item.status_label));
			row.append($("<" + "td" + ">").text(datecFormatted));
			row.append($("<" + "td" + ">").text(datepFormatted));
			row.append($("<" + "td" + ">").addClass("right").text(totalHtFormatted));
			row.append($("<" + "td" + ">").addClass("right").text(totalTtcFormatted));
			
			tbody.append(row);
		});
	} else {
		var noDataRow = $("<" + "tr" + ">").addClass("oddeven");
		noDataRow.append($("<" + "td" + ">").attr("colspan", "7").addClass("center").html("<" + "strong" + ">Aucun enregistrement trouvé<" + "/strong" + ">"));
		tbody.append(noDataRow);
	}

	// Mettre à jour la pagination
	var paginationContainer = $("#pagination-container");
	paginationContainer.empty();
	if (filtered.length > 0) {
		var paginationDiv = $("<" + "div" + ">").addClass("pagination");
		var pageText = 'Page ' + (currentPage + 1) + ' / ' + totalPages + ' (' + filtered.length + ' enregistrement' + (filtered.length > 1 ? 's' : '') + ')';
		paginationDiv.append($("<" + "span" + ">").text(pageText));
		
		if (currentPage > 0) {
			var prevLink = $("<" + "a" + ">").attr("href", "#").addClass("button pagination-link").attr("data-page", currentPage - 1).text('Précédent');
			paginationDiv.append(' ').append(prevLink);
		}
		if (currentPage < (totalPages - 1)) {
			var nextLink = $("<" + "a" + ">").attr("href", "#").addClass("button pagination-link").attr("data-page", currentPage + 1).text('Suivant');
			paginationDiv.append(" ").append(nextLink);
		}
		paginationContainer.append(paginationDiv);
	}
}

jQuery(document).ready(function($) {
	// Charger les données depuis window.propalDashboardData
	try {
		if (typeof window.propalDashboardData !== "undefined" && Array.isArray(window.propalDashboardData)) {
			allProposals = window.propalDashboardData;
			filterAndDisplayProposals();
		} else {
			console.error("✗ window.propalDashboardData non défini ou n'est pas un tableau");
			console.error("Valeur:", window.propalDashboardData);
			var tbody = $("#propal-tbody");
			tbody.empty();
			var errorRow = $("<" + "tr" + ">").addClass("oddeven");
			var errorCell = $("<" + "td" + ">").attr("colspan", "7").addClass("center");
			var errorMsg = $("<" + "strong" + ">").css("color", "red").text('Erreur: Données non chargées');
			errorCell.append(errorMsg);
			errorRow.append(errorCell);
			tbody.append(errorRow);
		}
	} catch(e) {
		console.error("✗ ERREUR parsing JSON:", e);
		console.error("Message:", e.message);
		console.error("Stack:", e.stack);
		allProposals = [];
		var tbody = $("#propal-tbody");
		tbody.empty();
		var errorRow = $("<" + "tr" + ">").addClass("oddeven");
		var errorCell = $("<" + "td" + ">").attr("colspan", "7").addClass("center");
		var errorMsg = $("<" + "strong" + ">").css("color", "red").text('Erreur: Données invalides - ' + e.message);
		errorCell.append(errorMsg);
		errorRow.append(errorCell);
		tbody.append(errorRow);
	}
	// Écouter les changements sur les filtres
	$(".filter-input").on("change input", function() {
		currentPage = 0;
		filterAndDisplayProposals();
	});

	// Pour le champ année spécifiquement, aussi écouter blur
	$("#filter_year").on("blur", function() {
		currentPage = 0;
		filterAndDisplayProposals();
	});

	// Gérer la pagination
	$(document).on("click", ".pagination-link", function(e) {
		e.preventDefault();
		currentPage = parseInt($(this).data("page"));
		filterAndDisplayProposals();
	});
});
</script>
JS;
print $jsBlock;

llxFooter();

$db->close();
