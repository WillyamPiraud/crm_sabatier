<?php
require_once '/var/www/html/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/class/programme_previsionnel.class.php';

$programme = new ProgrammePrevisionnel($db);
$programmes_actifs = $programme->listAll(1);

echo "Nombre de programmes actifs: ".count($programmes_actifs)."\n";
foreach ($programmes_actifs as $prog) {
    echo "- ID: ".$prog['id'].", Label: ".$prog['label']."\n";
}


