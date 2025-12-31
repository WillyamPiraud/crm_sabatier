<?php
/**
 * Script pour exporter la proposition commerciale vers le client mail par défaut
 * Génère le PDF et ouvre le client mail avec mailto:
 */

if (!isset($object) || !is_object($object) || $object->element != 'propal') {
    return;
}

// Récupérer l'email de facturation du tiers
$email_destinataire = '';
if (!empty($object->thirdparty->email)) {
    $email_destinataire = $object->thirdparty->email;
} elseif (!empty($object->thirdparty->email_facturation)) {
    $email_destinataire = $object->thirdparty->email_facturation;
} elseif (!empty($object->thirdparty->email_invoice)) {
    $email_destinataire = $object->thirdparty->email_invoice;
}

// Si pas d'email trouvé, essayer de récupérer depuis les contacts
if (empty($email_destinataire)) {
    require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
    $contact = new Contact($db);
    $contacts = $contact->getContactArray($object->socid, 1, 'BILLING');
    if (!empty($contacts) && isset($contacts[0]['email'])) {
        $email_destinataire = $contacts[0]['email'];
    }
}

// Générer le PDF de la proposition
$model = $object->model_pdf;
if (empty($model)) {
    $model = getDolGlobalString("PROPALE_ADDON_PDF");
}

require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
$modulepdf = new ModelePDFPropales($db);
$modulepdf->init($object, $model);

// Générer le PDF
$outputlangs = $langs;
$hidedetails = 0;
$hidedesc = 0;
$hideref = 0;

$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);

if ($result > 0) {
    // Récupérer le chemin du PDF généré
    $filepath = $object->last_main_doc;
    
    // Construire l'URL de téléchargement du PDF
    $pdf_url = DOL_URL_ROOT.'/document.php?modulepart=propal&file='.urlencode($filepath);
    
    // Construire le sujet du mail
    $subject = $langs->trans("CommercialProposal").' - '.$object->ref;
    if (!empty($object->thirdparty->name)) {
        $subject .= ' - '.$object->thirdparty->name;
    }
    
    // Construire le corps du message
    $message = $langs->trans("Dear").' '.($object->thirdparty->name ?? '').",\n\n";
    $message .= $langs->trans("PleaseFindAttached")." ".$langs->trans("CommercialProposal")." ".$object->ref.".\n\n";
    $message .= $langs->trans("BestRegards").",\n";
    $message .= $user->firstname.' '.$user->lastname;
    
    // Ajouter le lien de téléchargement du PDF dans le message
    $message .= "\n\n".$langs->trans("DownloadPDF").": ".$pdf_url;
    
    // Encoder pour mailto:
    $subject_encoded = rawurlencode($subject);
    $body_encoded = rawurlencode($message);
    
    // Créer le lien mailto:
    $mailto_link = "mailto:".$email_destinataire."?subject=".$subject_encoded."&body=".$body_encoded;
    
    // Rediriger vers le lien mailto: (ouvrira le client mail par défaut)
    header("Location: ".$mailto_link);
    exit;
} else {
    setEventMessages($object->error, $object->errors, 'errors');
}

