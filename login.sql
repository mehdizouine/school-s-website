

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `login` (
  `Username` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `ID` int(11) NOT NULL,
  `role` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `login` (`Username`, `Password`, `ID`, `role`) VALUES
('user1', '12345', 1, 'eleve'),
('user2', '67890', 2, 'eleve'),
('user3', '13579', 3, 'eleve'),
('user4', '09876', 4, 'admin');


ALTER TABLE `login`
  ADD PRIMARY KEY (`ID`);


ALTER TABLE `login`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;


