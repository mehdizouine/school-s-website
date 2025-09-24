-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 25 sep. 2025 à 00:26
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `website`
--

-- --------------------------------------------------------

--
-- Structure de la table `prof_classes`
--

CREATE TABLE `prof_classes` (
  `id` int(11) NOT NULL,
  `prof_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `prof_classes`
--
ALTER TABLE `prof_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prof_id` (`prof_id`),
  ADD KEY `classe_id` (`classe_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `prof_classes`
--
ALTER TABLE `prof_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `prof_classes`
--
ALTER TABLE `prof_classes`
  ADD CONSTRAINT `prof_classes_ibfk_1` FOREIGN KEY (`prof_id`) REFERENCES `login` (`ID`),
  ADD CONSTRAINT `prof_classes_ibfk_2` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
