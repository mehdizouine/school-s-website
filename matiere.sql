
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `matiere` (
  `ID_matiere` int(11) NOT NULL,
  `matiere` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE matiere
ADD PRIMARY KEY (ID_matiere);
COMMIT;