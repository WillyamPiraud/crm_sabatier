#!/bin/bash

COMPANY_LIB="/var/www/html/core/lib/company.lib.php"
PATCH_MARKER="// Factures - CUSTOM PATCH"

# Vérifier si le patch a déjà été appliqué
if grep -q "$PATCH_MARKER" "$COMPANY_LIB" 2>/dev/null; then
    echo "✓ Patch déjà appliqué"
    exit 0
fi

# Appliquer le patch pour l'onglet Factures
sed -i '/\$head\[\$h\]\[2\] = '\''customer'\'';/a\
\
	// Factures - CUSTOM PATCH\
	if (isModEnabled('\''invoice'\'') && ($object->client == 1 || $object->client == 3) && $user->hasRight('\''facture'\'', '\''lire'\'')) {\
		$langs->load("bills");\
		$nbInvoice = 0;\
		require_once DOL_DOCUMENT_ROOT.\x27/core/lib/memory.lib.php\x27;\
		$cachekey = \x27count_invoices_thirdparty_\x27.$object->id;\
		$dataretrieved = dol_getcache($cachekey);\
		if (!is_null($dataretrieved)) {\
			$nbInvoice = $dataretrieved;\
		} else {\
			$sql = "SELECT COUNT(f.rowid) as nb";\
			$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";\
			$sql .= " WHERE f.fk_soc = ".((int) $object->id);\
			$sql .= " AND f.entity IN (".getEntity("invoice").")";\
			$resql = $db->query($sql);\
			if ($resql) {\
				$obj = $db->fetch_object($resql);\
				$nbInvoice = $obj->nb;\
			}\
			dol_setcache($cachekey, $nbInvoice, 120);\
		}\
		$head[$h][0] = DOL_URL_ROOT.\x27/compta/facture/list.php?socid=\x27.$object->id;\
		$head[$h][1] = $langs->trans("Bills");\
		if ($nbInvoice > 0) {\
			$head[$h][1] .= \x27<span class="badge marginleftonlyshort">\x27.$nbInvoice.\x27</span>\x27;\
		}\
		$head[$h][2] = \x27invoice\x27;\
		$h++;\
	}\
' "$COMPANY_LIB"

echo "✓ Patch appliqué avec succès"

