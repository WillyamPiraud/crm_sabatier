<?php
/**
 * Script pour améliorer le champ de sélection des programmes prévisionnels avec Select2
 */

$card_file = '/var/www/html/comm/propal/card.php';

// Lire le fichier
$content = file_get_contents($card_file);

// 1. Supprimer le code de debug temporaire
$content = preg_replace('/\/\/ Debug temporaire.*?}\s*/s', '', $content);

// 2. Remplacer le select simple par un select avec Select2
$old_select = '/<select name="programmes_previsionnels\[\]" multiple="multiple" size="5" class="minwidth300">/';
$new_select = '<select name="programmes_previsionnels[]" multiple="multiple" class="minwidth300" id="programmes_previsionnels_select">';
$content = preg_replace($old_select, $new_select, $content);

// 3. Supprimer le texte "HoldCtrlToSelectMultiple" et le remplacer par Select2
$content = preg_replace('/<br><small>.*?HoldCtrlToSelectMultiple.*?<\/small>/', '', $content);

// 4. Ajouter le code Select2 après le select
$pattern = '/(if \(count\(\$programmes_actifs\) > 0\) \{.*?print "<\/select>";)/s';
$replacement = '$1
			print "<script>";
			print "$(document).ready(function() {";
			print "  $(\'select[name=\"programmes_previsionnels[]\"]\').select2({";
			print "    placeholder: \'Sélectionner des programmes prévisionnels\',";
			print "    allowClear: true,";
			print "    width: \'100%\',";
			print "    language: \'fr\'";
			print "  });";
			print "});";
			print "</script>";
			print "<style>.select2-container { z-index: 9999; } .select2-container--default .select2-selection--multiple { min-height: 38px; border: 1px solid #aaa; }</style>";';

$content = preg_replace($pattern, $replacement, $content, 1);

// Sauvegarder
file_put_contents($card_file, $content);
echo "✓ Champ amélioré avec Select2\n";

