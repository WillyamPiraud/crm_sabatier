<?php
/**
 * Script pour mettre à jour les extrafields existants pour les propositions commerciales
 * Rend tous les champs obligatoires SAUF "lieu_previsionnel" et s'assure qu'aucun n'est unique
 */

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Accès admin uniquement
if (!$user->admin) {
    die("Accès refusé. Vous devez être administrateur.");
}

$extrafields = new ExtraFields($db);

// Liste des extrafields à mettre à jour
$fields_to_update = array(
    'intitule_de_formation' => array('required' => 1, 'unique' => 0),
    'nombre_jours_formation' => array('required' => 1, 'unique' => 0),
    'tarif_global_ht' => array('required' => 1, 'unique' => 0),
    'objectifs_pedagogiques' => array('required' => 1, 'unique' => 0),
    'lieu_previsionnel' => array('required' => 0, 'unique' => 0), // OPTIONNEL
    'type_formation' => array('required' => 1, 'unique' => 0)
);

echo "<h2>Mise à jour des extrafields pour les propositions commerciales</h2>";

// Charger les extrafields existants
$extrafields->fetch_name_optionals_label('propal');

foreach ($fields_to_update as $field_name => $settings) {
    if (!isset($extrafields->attributes['propal']['label'][$field_name])) {
        echo "<p>⚠ Le champ '{$field_name}' n'existe pas. Ignoré.</p>";
        continue;
    }
    
    // Mettre à jour via SQL directement car addExtraField ne permet pas de modifier
    $sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
    $sql .= " SET fieldrequired = ".((int)$settings['required']);
    $sql .= ", fieldunique = ".((int)$settings['unique']);
    $sql .= " WHERE name = '".$db->escape($field_name)."'";
    $sql .= " AND elementtype = 'propal'";
    $sql .= " AND entity = ".((int)$conf->entity);
    
    $result = $db->query($sql);
    
    if ($result) {
        $label = $extrafields->attributes['propal']['label'][$field_name];
        echo "<p>✓ Champ '{$label}' ({$field_name}) mis à jour : required=".$settings['required'].", unique=".$settings['unique']."</p>";
    } else {
        echo "<p>✗ Erreur lors de la mise à jour du champ '{$field_name}': " . $db->lasterror() . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>Terminé !</strong></p>";
echo "<p>Les extrafields ont été mis à jour.</p>";
echo "<p><a href='/admin/extrafields.php?elementtype=propal'>Vérifier dans Modules/Applications → Propositions commerciales → Champs supplémentaires</a></p>";

