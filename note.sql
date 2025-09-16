SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `note` (
  `ID_note` INT(11) NOT NULL AUTO_INCREMENT,
  `ID_eleve` INT(11) NOT NULL,
  `ID_matiere` INT(11) NOT NULL,
  `ID_exam` INT(11) NOT NULL,
  `note` DECIMAL(5,2) NOT NULL,
  `ID_semestre` INT(11) NOT NULL,
  PRIMARY KEY (`ID_note`),
  UNIQUE KEY `unique_note` (`ID_eleve`,`ID_matiere`,`ID_exam`),
  FOREIGN KEY (`ID_eleve`) REFERENCES `login`(`ID`) ON DELETE CASCADE,
  FOREIGN KEY (`ID_matiere`) REFERENCES `matiere`(`ID_matiere`) ON DELETE CASCADE,
  FOREIGN KEY (`ID_exam`) REFERENCES `examen`(`ID_examen`) ON DELETE CASCADE,
  FOREIGN KEY (`ID_semestre`) REFERENCES `semestre`(`ID_semestre`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



COMMIT;





