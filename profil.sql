
CREATE TABLE `profil` (
  `ID` varchar(255) NOT NULL,
  `Photo` varchar(255) NOT NULL,
  `Prénom` text NOT NULL,
  `Nom` text NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Date_de_naissance` date NOT NULL,
  `Année_scolaire` date NOT NULL,
  `Classe` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



