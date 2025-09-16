CREATE TABLE `profil` (
  `ID` VARCHAR(255) PRIMARY KEY,  -- Changed to VARCHAR
  `login_id` VARCHAR(255) UNIQUE, -- Ensure this is VARCHAR to match login table
  `Photo` VARCHAR(255) NOT NULL,
  `Prénom` TEXT NOT NULL,
  `Nom` TEXT NOT NULL,
  `Email` VARCHAR(255) NOT NULL,
  `Date de naissance` DATE NOT NULL,
  `Annee scolaire` DATE NOT NULL,
  `Classe` TEXT NOT NULL,
  FOREIGN KEY (`login_id`) REFERENCES `login`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
