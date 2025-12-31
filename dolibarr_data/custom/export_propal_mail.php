<?php
/**
 * Script pour exporter une proposition commerciale vers le client mail par défaut
 * Génère le PDF et ouvre le client mail avec le message pré-rempli
 */

define('NOTOKENRENEWAL', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);
define('NOREQUIREAJAX', 1);

// Chemin vers main.inc.php depuis custom/
if (file_exists(__DIR__.'/../../main.inc.php')) {
	require_once __DIR__.'/../../main.inc.php';
} elseif (file_exists('/var/www/html/main.inc.php')) {
	require_once '/var/www/html/main.inc.php';
} else {
	die('Erreur: Impossible de trouver main.inc.php');
}
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$id = GETPOST('id', 'int');
$token = GETPOST('token', 'aZ09');

// Vérifier les permissions
if (!$user->hasRight("propal", "lire")) {
	accessforbidden();
}

// Charger la proposition
$object = new Propal($db);
$result = $object->fetch($id);
if ($result <= 0) {
	dol_print_error($db, $object->error);
	exit;
}

// Charger le tiers associé
if (!empty($object->socid)) {
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	if (empty($object->thirdparty) || !is_object($object->thirdparty)) {
		$object->thirdparty = new Societe($db);
		$object->thirdparty->fetch($object->socid);
	}
}

// Vérifier le token
if (!dol_verifyToken($token)) {
	accessforbidden('Invalid token');
}

// Générer le PDF si ce n'est pas déjà fait
if (empty($object->last_main_doc)) {
	$outputlangs = $langs;
	$hidedetails = 0;
	$hidedesc = 0;
	$hideref = 0;
	$result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	if ($result > 0) {
		$object->fetch($id); // Recharger pour avoir last_main_doc
	}
}

// Récupérer l'email de facturation du tiers
$email_dest = '';
if (!empty($object->thirdparty->email)) {
	$email_dest = $object->thirdparty->email;
} elseif (!empty($object->thirdparty->email_facturation)) {
	$email_dest = $object->thirdparty->email_facturation;
} elseif (!empty($object->thirdparty->email_invoice)) {
	$email_dest = $object->thirdparty->email_invoice;
}

// Si pas d'email trouvé, essayer depuis les contacts
if (empty($email_dest)) {
	require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
	$contact = new Contact($db);
	$contacts = $contact->getContactArray($object->socid, 1, 'BILLING');
	if (!empty($contacts) && isset($contacts[0]['email'])) {
		$email_dest = $contacts[0]['email'];
	}
}

// Construire le sujet en français
$subject_mailto = "Proposition commerciale - ".$object->ref;
if (!empty($object->thirdparty->name)) {
	$subject_mailto .= ' - '.$object->thirdparty->name;
}

// Construire le message en français
$message_mailto = "Bonjour ".($object->thirdparty->name ?? '').",\n\n";
$message_mailto .= "Veuillez trouver ci-joint la proposition commerciale ".$object->ref.".\n\n";
$message_mailto .= "Cordialement,\n";
$message_mailto .= $user->firstname.' '.$user->lastname;

// Créer le lien mailto:
$mailto_link = "mailto:".($email_dest ? $email_dest : "")."?subject=".rawurlencode($subject_mailto)."&body=".rawurlencode($message_mailto);

// Créer une page HTML qui télécharge le PDF et ouvre mailto
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Export vers mail</title>
</head>
<body>
	<script type="text/javascript">
		<?php if (!empty($object->last_main_doc)): ?>
			<?php
			$pdf_path = DOL_DATA_ROOT.'/propal/'.dol_sanitizeFileName($object->ref).'/'.dol_sanitizeFileName($object->last_main_doc);
			if (file_exists($pdf_path)):
				$pdf_url = DOL_URL_ROOT.'/document.php?modulepart=propal&file='.urlencode($object->last_main_doc);
			?>
				// Télécharger le PDF dans un nouvel onglet
				var pdfWindow = window.open('<?php echo $pdf_url; ?>', '_blank');
				
				// Attendre un peu puis ouvrir le client mail
				setTimeout(function() {
					window.location.href = '<?php echo $mailto_link; ?>';
				}, 1000);
			<?php else: ?>
				// Pas de PDF, juste ouvrir mailto
				window.location.href = '<?php echo $mailto_link; ?>';
			<?php endif; ?>
		<?php else: ?>
			// Pas de PDF, juste ouvrir mailto
			window.location.href = '<?php echo $mailto_link; ?>';
		<?php endif; ?>
	</script>
	<p>Préparation de l'export...</p>
	<p>Le PDF va être téléchargé et votre client mail va s'ouvrir.</p>
	<p>Vous pourrez alors glisser-déposer le PDF dans votre email.</p>
</body>
</html>
<?php
exit;
?>

