<?php
define("DOLIBARR_INSTALL_MODE", 1);
define("DOL_DOCUMENT_ROOT", "/var/www/html");
require_once DOL_DOCUMENT_ROOT."/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/security.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/user.class.php";

$user = new User($db);
$result = $user->fetch("", "admin");

if ($result > 0) {
    // Réinitialiser le mot de passe
    $user->setPassword($db, "admin", 0);
    $user->update($db);
    echo "SUCCESS: Mot de passe réinitialisé pour admin/admin\n";
    echo "Login: admin\n";
    echo "Password: admin\n";
} else {
    echo "ERROR: Utilisateur admin non trouvé\n";
}

