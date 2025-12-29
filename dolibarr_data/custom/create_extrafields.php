<?php
/**
 * Script pour créer les extrafields pour les propositions commerciales
 * À exécuter une fois via l'interface web ou en ligne de commande
 */

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Accès admin uniquement
if (!$user->admin) {
    die("Accès refusé. Vous devez être administrateur.");
}

$extrafields = new ExtraFields($db);

// Liste des extrafields à créer
$fields = array(
    array(
        'name' => 'intitule_de_formation',
        'label' => 'Intitulé de formation',
        'type' => 'varchar',
        'size' => 255,
        'pos' => 1,
        'required' => 0,
        'alwayseditable' => 1,
        'list' => 1,
        'printable' => 1
    ),
    array(
        'name' => 'nombre_jours_formation',
        'label' => 'Nombre jours formation',
        'type' => 'double',
        'size' => '24,8',
        'pos' => 100,
        'required' => 0,
        'alwayseditable' => 1,
        'list' => 1,
        'printable' => 1
    ),
    array(
        'name' => 'tarif_global_ht',
        'label' => 'Tarif global HT',
        'type' => 'price',
        'size' => NULL,
        'pos' => 100,
        'required' => 0,
        'alwayseditable' => 1,
        'list' => 1,
        'printable' => 1
    ),
    array(
        'name' => 'objectifs_pedagogiques',
        'label' => 'Objectifs pédagogiques',
        'type' => 'text',
        'size' => 2000,
        'pos' => 100,
        'required' => 0,
        'alwayseditable' => 1,
        'list' => 1,
        'printable' => 1
    ),
    array(
        'name' => 'lieu_previsionnel',
        'label' => 'Lieu prévisionnel',
        'type' => 'varchar',
        'size' => 255,
        'pos' => 100,
        'required' => 0,
        'alwayseditable' => 0,
        'list' => 1,
        'printable' => 1
    ),
    array(
        'name' => 'type_formation',
        'label' => 'Type formation',
        'type' => 'select',
        'size' => NULL,
        'pos' => 100,
        'required' => 0,
        'alwayseditable' => 1,
        'list' => 1,
        'printable' => 0,
        'param' => 'Intra-entreprise\nInter-entreprise\nE-learning\nPrésentiel\nDistanciel'
    )
);

echo "<h2>Création des extrafields pour les propositions commerciales</h2>";

foreach ($fields as $field) {
    // Vérifier si le champ existe déjà
    $extrafields->fetch_name_optionals_label('propal');
    if (isset($extrafields->attributes['propal']['label'][$field['name']])) {
        echo "<p>⚠ Le champ '{$field['label']}' existe déjà. Ignoré.</p>";
        continue;
    }
    
    // Créer le champ
    $result = $extrafields->addExtraField(
        $field['name'],
        $field['label'],
        $field['type'],
        1, // entity
        $field['size'],
        'propal',
        $field['pos'],
        $field['required'],
        0, // unique
        isset($field['param']) ? $field['param'] : '',
        $field['alwayseditable'],
        $field['list'],
        $field['printable']
    );
    
    if ($result > 0) {
        echo "<p>✓ Champ '{$field['label']}' créé avec succès.</p>";
    } else {
        echo "<p>✗ Erreur lors de la création du champ '{$field['label']}': " . $extrafields->error . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>Terminé !</strong></p>";
echo "<p>Les extrafields sont maintenant disponibles dans :</p>";
echo "<p><a href='/admin/extrafields.php?elementtype=propal'>Modules/Applications → Propositions commerciales → Champs supplémentaires</a></p>";

