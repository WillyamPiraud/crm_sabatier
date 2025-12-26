-- Table de liaison entre propositions commerciales et programmes prévisionnels
-- Permet d'associer plusieurs programmes à une proposition commerciale

CREATE TABLE IF NOT EXISTS `llx_propal_programme_previsionnel` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_propal` int(11) NOT NULL,
  `fk_programme_previsionnel` int(11) NOT NULL,
  `date_creation` datetime NOT NULL,
  `fk_user_creation` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_propal_programme` (`fk_propal`, `fk_programme_previsionnel`),
  KEY `idx_propal` (`fk_propal`),
  KEY `idx_programme` (`fk_programme_previsionnel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


