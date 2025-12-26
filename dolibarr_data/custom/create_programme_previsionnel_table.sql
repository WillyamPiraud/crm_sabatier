-- Table pour stocker les programmes prévisionnels
-- Ce fichier crée la structure de données nécessaire

CREATE TABLE IF NOT EXISTS `llx_programme_previsionnel` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(128) NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` text,
  `file_path` varchar(255) NOT NULL COMMENT 'Chemin vers le fichier PDF',
  `file_name` varchar(255) NOT NULL COMMENT 'Nom du fichier original',
  `file_size` int(11) DEFAULT NULL COMMENT 'Taille du fichier en octets',
  `date_creation` datetime NOT NULL,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_user_creation` int(11) DEFAULT NULL,
  `fk_user_modification` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1 COMMENT '1=actif, 0=inactif',
  `entity` int(11) DEFAULT 1,
  PRIMARY KEY (`rowid`),
  KEY `idx_ref` (`ref`),
  KEY `idx_active` (`active`),
  KEY `idx_entity` (`entity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


