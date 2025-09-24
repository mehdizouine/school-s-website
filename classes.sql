

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";



CREATE TABLE `classes` (
  `ID` int(11) NOT NULL,
  `nom_de_classe` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


ALTER TABLE `classes`
  ADD PRIMARY KEY (`ID`);


ALTER TABLE `classes`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;


