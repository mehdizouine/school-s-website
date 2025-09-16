
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";




CREATE TABLE `examen` (
  `ID_examen` int(11) NOT NULL,
  `nom_examen` varchar(255) NOT NULL,
  `semestre` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `examen` (`ID_examen`, `nom_examen`, `semestre`) VALUES
(1, 'Exam 1', 1),
(2, 'Exam 2', 1),
(3, 'Exam 3', 1),
(4, 'Exam 4', 1),
(5, 'Activités', 1),
(6, 'N.semestre', 1),
(7, 'Exam 1', 2),
(8, 'Exam 2', 2),
(9, 'Exam 3', 2),
(10, 'Exam 4', 2),
(11, 'Activités', 2),
(12, 'N.semestre', 2);
COMMIT;

