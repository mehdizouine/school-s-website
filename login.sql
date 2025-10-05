
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";




CREATE TABLE `login` (
  `Username` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `ID` int(11) NOT NULL,
  `role` text NOT NULL,
  `classe_id` int(11) DEFAULT NULL,
  `Password_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `login` (`Username`, `Password`, `ID`, `role`, `classe_id`, `Password_hash`) VALUES
('user1', '12345', 1, 'eleve', 3, NULL),
('user2', '67809', 2, 'eleve', 2, NULL),
('user3', '13570', 3, 'eleve', 4, NULL),
('user4', '09876', 4, 'admin', NULL, NULL),
('user5', '124578', 5, 'eleve', 4, NULL),
('user6', '135790', 8, 'eleve', 2, NULL),
('user7', '1290', 11, 'eleve', 3, NULL),
('prof1', '12095', 12, 'prof', NULL, NULL),
('p', '12', 13, 'prof', NULL, NULL);

ALTER TABLE `login`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_user_class` (`classe_id`);

ALTER TABLE `login`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

ALTER TABLE `login`
  ADD CONSTRAINT `fk_user_class` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`ID`);
COMMIT;



