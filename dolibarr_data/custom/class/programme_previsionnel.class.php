<?php
/**
 * Classe pour gérer les programmes prévisionnels
 */

/**
 * Class ProgrammePrevisionnel
 */
class ProgrammePrevisionnel
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var int ID du programme
	 */
	public $id;

	/**
	 * @var string Référence du programme
	 */
	public $ref;

	/**
	 * @var string Libellé/titre du programme
	 */
	public $label;

	/**
	 * @var string Description du programme
	 */
	public $description;

	/**
	 * @var string Chemin vers le fichier PDF
	 */
	public $file_path;

	/**
	 * @var string Nom du fichier original
	 */
	public $file_name;

	/**
	 * @var int Taille du fichier en octets
	 */
	public $file_size;

	/**
	 * @var int Statut actif/inactif (1=actif, 0=inactif)
	 */
	public $active;

	/**
	 * @var int ID de l'utilisateur créateur
	 */
	public $fk_user_creation;

	/**
	 * @var int ID de l'utilisateur modificateur
	 */
	public $fk_user_modification;

	/**
	 * @var array Tableau des erreurs
	 */
	public $errors = array();

	/**
	 * @var string Message d'erreur
	 */
	public $error;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Créer un nouveau programme prévisionnel
	 *
	 * @param  User   $user   User qui crée
	 * @return int            ID du programme créé, <0 si erreur
	 */
	public function create($user)
	{
		global $conf;

		$error = 0;

		// Générer une référence automatique si non fournie
		if (empty($this->ref)) {
			$this->ref = $this->getNextRef();
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."programme_previsionnel (";
		$sql .= "ref, label, description, file_path, file_name, file_size,";
		$sql .= "date_creation, fk_user_creation, active, entity";
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->escape($this->ref)."',";
		$sql .= "'".$this->db->escape($this->label)."',";
		$sql .= "'".$this->db->escape($this->description)."',";
		$sql .= "'".$this->db->escape($this->file_path)."',";
		$sql .= "'".$this->db->escape($this->file_name)."',";
		$sql .= "".((int) $this->file_size).",";
		$sql .= "NOW(),";
		$sql .= "".((int) $user->id).",";
		$sql .= "1,";
		$sql .= "".((int) $conf->entity);
		$sql .= ")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."programme_previsionnel");
			return $this->id;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Charger un programme depuis la base de données
	 *
	 * @param  int    $id     ID du programme
	 * @return int            <0 si erreur, >0 si OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT rowid, ref, label, description, file_path, file_name, file_size,";
		$sql .= " date_creation, date_modification, fk_user_creation, fk_user_modification, active, entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."programme_previsionnel";
		$sql .= " WHERE rowid = ".((int) $id);

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$this->id = $obj->rowid;
				$this->ref = $obj->ref;
				$this->label = $obj->label;
				$this->description = $obj->description;
				$this->file_path = $obj->file_path;
				$this->file_name = $obj->file_name;
				$this->file_size = $obj->file_size;
				$this->active = $obj->active;
				$this->fk_user_creation = $obj->fk_user_creation;
				$this->fk_user_modification = $obj->fk_user_modification;
				return 1;
			} else {
				$this->error = "Programme not found";
				return -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Mettre à jour un programme
	 *
	 * @param  User   $user   User qui modifie
	 * @return int            <0 si erreur, >0 si OK
	 */
	public function update($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."programme_previsionnel SET";
		$sql .= " ref = '".$this->db->escape($this->ref)."',";
		$sql .= " label = '".$this->db->escape($this->label)."',";
		$sql .= " description = '".$this->db->escape($this->description)."',";
		if (!empty($this->file_path)) {
			$sql .= " file_path = '".$this->db->escape($this->file_path)."',";
			$sql .= " file_name = '".$this->db->escape($this->file_name)."',";
			$sql .= " file_size = ".((int) $this->file_size).",";
		}
		$sql .= " fk_user_modification = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Supprimer un programme
	 *
	 * @return int    <0 si erreur, >0 si OK
	 */
	public function delete()
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."programme_previsionnel";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Lister tous les programmes actifs
	 *
	 * @param  int    $active     1=actifs seulement, 0=tous
	 * @return array              Tableau des programmes
	 */
	public function listAll($active = 1)
	{
		$programmes = array();

		$sql = "SELECT rowid, ref, label, description, file_path, file_name";
		$sql .= " FROM ".MAIN_DB_PREFIX."programme_previsionnel";
		$sql .= " WHERE entity IN (1)"; // Temporairement fixé à 1 car getEntity ne reconnaît pas cette entité
		if ($active == 1) {
			$sql .= " AND active = 1";
		}
		$sql .= " ORDER BY label ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			for ($i = 0; $i < $num; $i++) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$programmes[] = array(
						'id' => $obj->rowid,
						'ref' => $obj->ref,
						'label' => $obj->label,
						'description' => $obj->description,
						'file_path' => $obj->file_path,
						'file_name' => $obj->file_name
					);
				}
			}
		} else {
			dol_syslog("Erreur SQL dans listAll(): ".$this->db->lasterror(), LOG_ERR);
		}

		return $programmes;
	}

	/**
	 * Générer la prochaine référence automatique
	 *
	 * @return string    Référence suivante
	 */
	private function getNextRef()
	{
		$sql = "SELECT MAX(CAST(SUBSTRING_INDEX(ref, '-', -1) AS UNSIGNED)) as max_ref";
		$sql .= " FROM ".MAIN_DB_PREFIX."programme_previsionnel";
		$sql .= " WHERE ref LIKE 'PROG-%'";

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$num = ($obj->max_ref ? $obj->max_ref : 0) + 1;
			return "PROG-".str_pad($num, 4, "0", STR_PAD_LEFT);
		}
		return "PROG-0001";
	}
}

