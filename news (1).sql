

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `news` (
  `ID` int(255) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `sous_titre` varchar(255) NOT NULL,
  `lien` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
COMMIT;


