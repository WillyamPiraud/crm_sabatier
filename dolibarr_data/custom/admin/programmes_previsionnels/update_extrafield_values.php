<?php
/**
 * Script pour mettre à jour les valeurs du champ extrafield "programme_previsionnel"
 */

require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/class/programme_previsionnel.class.php';

// Langues
$langs->load("admin");

// Accès admin uniquement
if (!$user->admin) {
	accessforbidden();
}

$extrafields = new ExtraFields($db);
$programme = new ProgrammePrevisionnel($db);

// Récupérer le champ extrafield
$extrafields->fetch_name_optionals_label('propal');
$attribute_label = 'programme_previsionnel';

// Vérifier que le champ existe
if (!isset($extrafields->attributes['propal']['label'][$attribute_label])) {
	print "Erreur : Le champ extrafield 'programme_previsionnel' n'existe pas dans les propositions commerciales.<br>";
	print "Veuillez d'abord créer ce champ dans Configuration → Champs supplémentaires → Propositions commerciales.<br>";
	exit;
}

// Récupérer tous les programmes actifs
$programmes = $programme->listAll(1); // 1 = actifs seulement

// Construire la liste des valeurs au format Dolibarr
// Format : id|label (une valeur par ligne)
$values = array();
foreach ($programmes as $prog) {
	$values[] = $prog['id'].'|'.$prog['label'];
}

// Convertir en chaîne (format Dolibarr : une valeur par ligne)
$values_string = implode("\n", $values);

// Mettre à jour les valeurs du champ
$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
$sql .= " SET param = '".$db->escape($values_string)."'";
$sql .= " WHERE name = '".$db->escape($attribute_label)."'";
$sql .= " AND elementtype = 'propal'";

$resql = $db->query($sql);
if ($resql) {
	print "✓ Les valeurs du champ 'programme_previsionnel' ont été mises à jour avec ".count($programmes)." programme(s).<br>";
	print "<br>Valeurs mises à jour :<br>";
	print "<pre>".htmlspecialchars($values_string)."</pre>";
} else {
	print "Erreur lors de la mise à jour : ".$db->lasterror()."<br>";
}

print "<br><a href='list.php'>Retour à la liste des programmes</a>";

