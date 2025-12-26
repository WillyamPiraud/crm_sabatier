<?php
/**
 * Script pour automatiser l'ajout d'une ligne de service et des PDFs des programmes prévisionnels
 * après la création d'une proposition commerciale
 */

// Debug : vérifier que le script est bien appelé - TOUJOURS EXÉCUTÉ
@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - Script INCLUDED - id=".(isset($id) ? $id : 'NOT SET').", object=".(isset($object) && is_object($object) ? 'SET' : 'NOT SET')."\n", FILE_APPEND);
dol_syslog("Auto-add service line script STARTED - id=".(isset($id) ? $id : 'NOT SET').", object=".(isset($object) && is_object($object) ? 'SET' : 'NOT SET'));

// Vérifier que la propal a été créée avec succès
@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - Checking conditions: id=".(isset($id) ? $id : 'NOT SET').", object=".(isset($object) && is_object($object) ? 'SET' : 'NOT SET')."\n", FILE_APPEND);

if (isset($id) && $id > 0 && isset($object) && is_object($object)) {
	dol_syslog("Auto-add service line: Conditions OK, id=".$id);
	@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - Conditions OK, proceeding...\n", FILE_APPEND);
	
	// Recharger l'objet pour avoir les extrafields sauvegardés
	$object->fetch($id);
	$object->fetch_optionals();
	
	// Récupérer les valeurs des extrafields depuis l'objet (ils sont sauvegardés par create())
	$intitule_formation = isset($object->array_options['options_intitule_de_formation']) ? $object->array_options['options_intitule_de_formation'] : '';
	$nombre_jours_str = isset($object->array_options['options_nombre_jours_formation']) ? $object->array_options['options_nombre_jours_formation'] : '';
	$tarif_global_ht_str = isset($object->array_options['options_tarif_global_ht']) ? $object->array_options['options_tarif_global_ht'] : '';
	$objectifs_pedagogiques = isset($object->array_options['options_objectifs_pedagogiques']) ? $object->array_options['options_objectifs_pedagogiques'] : '';
	$lieu_previsionnel = isset($object->array_options['options_lieu_previsionnel']) ? $object->array_options['options_lieu_previsionnel'] : '';
	$type_formation = isset($object->array_options['options_type_formation']) ? $object->array_options['options_type_formation'] : '';
	
	// Si les valeurs ne sont pas dans l'objet, essayer depuis POST
	if (empty($intitule_formation) && empty($tarif_global_ht_str)) {
		$intitule_formation = GETPOST('options_intitule_de_formation', 'alphanohtml');
		$nombre_jours_str = GETPOST('options_nombre_jours_formation', 'alpha');
		$tarif_global_ht_str = GETPOST('options_tarif_global_ht', 'alpha');
		$objectifs_pedagogiques = GETPOST('options_objectifs_pedagogiques', 'restricthtml');
		$lieu_previsionnel = GETPOST('options_lieu_previsionnel', 'alphanohtml');
		$type_formation = GETPOST('options_type_formation', 'alphanohtml');
	}
	
	// Convertir les nombres (gérer les formats avec espaces et virgules)
	$nombre_jours = 0;
	if (!empty($nombre_jours_str)) {
		$nombre_jours = (float)str_replace(array(' ', ','), array('', '.'), $nombre_jours_str);
	}
	
	$tarif_global_ht = 0;
	if (!empty($tarif_global_ht_str)) {
		// Enlever les espaces et remplacer la virgule par un point
		$tarif_global_ht = (float)str_replace(array(' ', ','), array('', '.'), $tarif_global_ht_str);
	}
	
	// Log pour debug
	dol_syslog("Auto-add service line DEBUG: id=".$id.", intitule=".$intitule_formation.", tarif_str=".$tarif_global_ht_str.", tarif=".$tarif_global_ht.", jours=".$nombre_jours);
	@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - Values: intitule=".$intitule_formation.", tarif_str=".$tarif_global_ht_str.", tarif=".$tarif_global_ht.", jours=".$nombre_jours."\n", FILE_APPEND);
	
	// Si on a au moins l'intitulé et le tarif, créer la ligne de service
	dol_syslog("Auto-add service line CHECK: intitule=".$intitule_formation.", tarif=".$tarif_global_ht.", condition=".(!empty($intitule_formation) && $tarif_global_ht > 0 ? 'OK' : 'KO'));
	
	if (!empty($intitule_formation) && $tarif_global_ht > 0) {
		dol_syslog("Auto-add service line: Creating service line...");
		// Construire la description
		$desc = "La prestation de ".($nombre_jours > 0 ? $nombre_jours : 'X')." jours pour la formation \"".$intitule_formation."\"";
		
		// Ajouter les objectifs pédagogiques
		if (!empty($objectifs_pedagogiques)) {
			$desc .= "\n\nObjectifs pédagogiques :\n".strip_tags($objectifs_pedagogiques);
		}
		
		// Ajouter le lieu prévisionnel si renseigné
		if (!empty($lieu_previsionnel)) {
			$desc .= "\n\nLieu prévisionnel : ".$lieu_previsionnel;
		}
		
		// Ajouter le type de formation si renseigné
		if (!empty($type_formation)) {
			$desc .= "\n\nType de formation : ".$type_formation;
		}
		
		// Récupérer le taux de TVA par défaut
		$tva_tx = 0;
		if (isModEnabled('product')) {
			// Recharger le tiers si nécessaire
			if (empty($object->thirdparty->id) && !empty($object->socid)) {
				require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
				$object->thirdparty = new Societe($db);
				$object->thirdparty->fetch($object->socid);
			}
			// Utiliser le taux de TVA par défaut du tiers ou 20%
			if (!empty($object->thirdparty->tva_assuj)) {
				$tva_tx = $object->thirdparty->tva_assuj;
			} else {
				$tva_tx = getDolGlobalInt('FACTURE_TVAOPTION', 20);
			}
		}
		
		// Ajouter la ligne de service
		$result = $object->addline(
			$desc,                    // Description
			$tarif_global_ht,         // Prix unitaire HT
			1,                        // Quantité
			$tva_tx,                  // Taux TVA
			0,                        // Local tax 1
			0,                        // Local tax 2
			0,                        // fk_product (0 = service)
			0,                        // Remise %
			'HT',                     // Price base type
			0,                        // Prix unitaire TTC (calculé automatiquement)
			0,                        // Info bits
			0,                        // Type (0 = produit/service)
			0,                        // Rang
			0,                        // Special code
			0,                        // fk_parent_line
			0,                        // fk_fournprice
			0,                        // pa_ht
			'',                       // Label
			null,                     // Date début
			null,                     // Date fin
			array(),                  // Array options
			null                      // fk_unit (null = pas d'unité)
		);
		
		if ($result > 0) {
			dol_syslog("Ligne de service ajoutée automatiquement à la propal ".$id);
			@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - SUCCESS: Ligne de service ajoutée (result=".$result.")\n", FILE_APPEND);
		} else {
			dol_syslog("Erreur lors de l'ajout de la ligne de service : ".$object->error, LOG_ERR);
			@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - ERROR: ".$object->error."\n", FILE_APPEND);
		}
	}
	
	// Ajouter les PDFs des programmes prévisionnels en fichiers joints
	@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - Starting PDF attachment process...\n", FILE_APPEND);
	require_once DOL_DOCUMENT_ROOT."/custom/class/programme_previsionnel.class.php";
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
	
	$sql = "SELECT fk_programme_previsionnel FROM ".MAIN_DB_PREFIX."propal_programme_previsionnel WHERE fk_propal = ".((int) $id);
	$resql = $db->query($sql);
	
	@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - SQL query executed, num_rows=".($resql ? $db->num_rows($resql) : 0)."\n", FILE_APPEND);
	
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - Processing programme previsionnel ID=".$obj->fk_programme_previsionnel."\n", FILE_APPEND);
			$programme = new ProgrammePrevisionnel($db);
			$result = $programme->fetch($obj->fk_programme_previsionnel);
			
			if ($result > 0 && !empty($programme->file_path)) {
				$file_path_full = DOL_DATA_ROOT.'/'.$programme->file_path;
				@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - Programme found, file_path=".$programme->file_path.", file_path_full=".$file_path_full."\n", FILE_APPEND);
				
				if (file_exists($file_path_full)) {
					// Créer le répertoire de la propal si nécessaire
					$dest_dir = DOL_DATA_ROOT.'/propal/'.dol_sanitizeFileName($object->ref);
					dol_mkdir($dest_dir);
					
					$dest_file = $dest_dir.'/'.dol_sanitizeFileName($programme->file_name);
					
					@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - Copying file from ".$file_path_full." to ".$dest_file."\n", FILE_APPEND);
					
					if (@copy($file_path_full, $dest_file)) {
						@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - File copied successfully, creating ECM entry...\n", FILE_APPEND);
						// Utiliser la classe ECMFiles pour ajouter le fichier
						$ecmfile = new EcmFiles($db);
						$ecmfile->label = $programme->file_name;
						$ecmfile->entity = $conf->entity;
						$ecmfile->filepath = 'propal/'.dol_sanitizeFileName($object->ref).'/'.dol_sanitizeFileName($programme->file_name);
						$ecmfile->filename = $programme->file_name;
						$ecmfile->filesize = filesize($dest_file);
						$ecmfile->filetype = 'application/pdf';
						$ecmfile->position = 0;
						$ecmfile->gen_or_uploaded = 'uploaded';
						$ecmfile->extraparams = json_encode(array('fk_propal' => $id));
						
						$result_ecm = $ecmfile->create($user);
						
						if ($result_ecm > 0) {
							dol_syslog("PDF du programme prévisionnel ".$programme->id." ajouté à la propal ".$id);
							@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - SUCCESS: PDF ".$programme->file_name." added to ECM (result=".$result_ecm.")\n", FILE_APPEND);
						} else {
							dol_syslog("Erreur lors de l'enregistrement du fichier PDF dans ECM : ".$ecmfile->error, LOG_ERR);
							@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - ERROR adding PDF to ECM: ".$ecmfile->error."\n", FILE_APPEND);
						}
					} else {
						dol_syslog("Erreur lors de la copie du fichier PDF : ".$file_path_full, LOG_ERR);
						@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - ERROR copying file: ".$file_path_full."\n", FILE_APPEND);
					}
				} else {
					@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - ERROR: File not found at ".$file_path_full."\n", FILE_APPEND);
				}
			} else {
				@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - Programme not found or file_path empty (result=".$result.", file_path=".(isset($programme->file_path) ? $programme->file_path : 'NOT SET').")\n", FILE_APPEND);
			}
		}
	} else {
		@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - ERROR: SQL query failed\n", FILE_APPEND);
	}
} else {
	@file_put_contents('/tmp/auto_add_service_line_debug.log', date('Y-m-d H:i:s')." - Conditions NOT MET: id=".(isset($id) ? $id : 'NOT SET').", object=".(isset($object) && is_object($object) ? 'SET' : 'NOT SET')."\n", FILE_APPEND);
}

