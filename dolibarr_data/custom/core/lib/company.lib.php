<?php
/**
 * Fonction custom pour ajouter l'onglet Factures dans la fiche client
 */

/**
 * Surcharge de la fonction societe_prepare_head pour ajouter l'onglet Factures
 */
function societe_prepare_head(Societe $object)
{
	global $db, $langs, $conf, $user;
	global $hookmanager;

	// Appeler la fonction originale depuis le core
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	
	// Récupérer les onglets de base
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/societe/card.php?socid='.$object->id;
	$head[$h][1] = $langs->trans("ThirdParty");
	$head[$h][2] = 'card';
	$h++;

	if (!getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB') && $user->hasRight('societe', 'contact', 'lire')) {
		$nbContact = 0;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_contacts_thirdparty_'.$object->id;
		$dataretrieved = dol_getcache($cachekey);

		if (!is_null($dataretrieved)) {
			$nbContact = $dataretrieved;
		} else {
			$sql = "SELECT COUNT(p.rowid) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
			$parameters = array('contacttab' => true);
			$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object);
			$sql .= $hookmanager->resPrint;
			$sql .= " WHERE p.fk_soc = ".((int) $object->id);
			$sql .= " AND p.entity IN (".getEntity($object->element).")";
			$parameters = array('contacttab' => true);
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object);
			$sql .= $hookmanager->resPrint;
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbContact = $obj->nb;
			}
			dol_setcache($cachekey, $nbContact, 120);
		}

		$head[$h][0] = DOL_URL_ROOT.'/societe/contact.php?socid='.$object->id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	if ($object->client == 1 || $object->client == 2 || $object->client == 3) {
		$head[$h][0] = DOL_URL_ROOT.'/comm/card.php?socid='.$object->id;
		$head[$h][1] = '';
		if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') && ($object->client == 2 || $object->client == 3)) {
			$head[$h][1] .= $langs->trans("Prospect");
		}
		if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') && !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS') && $object->client == 3) {
			$head[$h][1] .= ' | ';
		}
		if (!getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS') && ($object->client == 1 || $object->client == 3)) {
			$head[$h][1] .= $langs->trans("Customer");
		}
		$head[$h][2] = 'customer';
		$h++;

		// Ajouter l'onglet Factures pour les clients
		if (isModEnabled('facture') && ($object->client == 1 || $object->client == 3) && $user->hasRight('facture', 'lire')) {
			// Compter le nombre de factures
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$facture = new Facture($db);
			$sql = "SELECT COUNT(f.rowid) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
			$sql .= " WHERE f.fk_soc = ".((int) $object->id);
			$sql .= " AND f.entity IN (".getEntity('facture').")";
			$resql = $db->query($sql);
			$nbFactures = 0;
			if ($resql) {
				$obj = $db->fetch_object($resql);
				$nbFactures = $obj->nb;
			}

			$head[$h][0] = DOL_URL_ROOT.'/societe/invoices.php?socid='.$object->id;
			$head[$h][1] = $langs->trans("Invoices");
			if ($nbFactures > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbFactures.'</span>';
			}
			$head[$h][2] = 'invoices';
			$h++;
		}

		if (getDolGlobalString('PRODUIT_CUSTOMER_PRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_AND_MULTIPRICES')) {
			$langs->load("products");
			$head[$h][0] = DOL_URL_ROOT.'/societe/price.php?socid='.$object->id;
			$head[$h][1] = $langs->trans("CustomerPrices");
			$head[$h][2] = 'price';
			$h++;
		}
	}

	// Continuer avec les autres onglets standards...
	$supplier_module_enabled = 0;
	if (isModEnabled('supplier_proposal') || isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) {
		$supplier_module_enabled = 1;
	}
	if ($supplier_module_enabled == 1 && $object->fournisseur && $user->hasRight('fournisseur', 'lire')) {
		$head[$h][0] = DOL_URL_ROOT.'/fourn/card.php?socid='.$object->id;
		$head[$h][1] = $langs->trans("Supplier");
		$head[$h][2] = 'supplier';
		$h++;
	}

	return $head;
}

