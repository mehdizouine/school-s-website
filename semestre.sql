

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";




CREATE TABLE `semestre` (
  `ID_semestre` int(11) NOT NULL,
  `semestre` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `semestre` (`ID_semestre`, `semestre`) VALUES
(1, 1),
(2, 2);
ALTER TABLE semestre
ADD PRIMARY KEY (ID_semestre);
ALTER TABLE semestre CHANGE semestre nom_semestre VARCHAR(50);
UPDATE semestre SET nom_semestre = CONCAT('Semestre ', ID_semestre);
COMMIT;
