-- phpMyAdmin SQL Dump
-- version 4.1.12
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jun 21, 2014 at 02:29 AM
-- Server version: 5.6.16
-- PHP Version: 5.5.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `kitsune`
--

-- --------------------------------------------------------

--
-- Table structure for table `igloos`
--

CREATE TABLE IF NOT EXISTS `igloos` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Owner` int(10) unsigned NOT NULL,
  `Type` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `Floor` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `Music` smallint(6) NOT NULL DEFAULT '0',
  `Furniture` text NOT NULL,
  `Location` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `Likes` text NOT NULL,
  `Locked` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `igloos`
--

INSERT INTO `igloos` (`ID`, `Owner`, `Type`, `Floor`, `Music`, `Furniture`, `Location`, `Likes`, `Locked`) VALUES
(1, 101, 30, 21, 645, '574|308|207|1|1,919|512|429|1|1,2235|117|265|1|1,2235|113|359|1|1,2226|155|203|1|1,2227|381|401|1|1,2229|312|398|3|1,2231|553|185|1|3', 6, '[{"id":"{045d200e-a48d-3a28-456e-6fc4e42a5afb}","time":1403128004,"count":1,"isFriend":false}]', 0),
(2, 102, 1, 0, 0, '', 1, '[]', 1);

-- --------------------------------------------------------

--
-- Table structure for table `penguins`
--

CREATE TABLE IF NOT EXISTS `penguins` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Username` char(12) NOT NULL,
  `Nickname` char(16) NOT NULL,
  `Password` char(32) NOT NULL,
  `LoginKey` char(32) NOT NULL,
  `ConfirmationHash` char(32) NOT NULL,
  `SWID` char(38) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `Avatar` char(98) NOT NULL DEFAULT '{"spriteScale":100,"spriteSpeed":100,"ignoresBlockLayer":false,"invisible":false,"floating":false}',
  `Email` char(254) NOT NULL,
  `RegistrationDate` int(8) NOT NULL,
  `Moderator` tinyint(1) NOT NULL DEFAULT '0',
  `Inventory` text NOT NULL,
  `Coins` mediumint(7) unsigned NOT NULL DEFAULT '200000',
  `Igloo` int(10) unsigned NOT NULL COMMENT 'Current active igloo',
  `Igloos` text NOT NULL COMMENT 'Owned igloo types',
  `Floors` text NOT NULL COMMENT 'Owned floorings',
  `Locations` text NOT NULL COMMENT 'Owned locations',
  `Furniture` text NOT NULL COMMENT 'Furniture inventory',
  `Color` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `Head` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Face` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Neck` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Body` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Hand` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Feet` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Photo` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Flag` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Walking` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Puffle ID',
  `Banned` varchar(20) NOT NULL DEFAULT '0' COMMENT 'Timestamp of ban',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Username` (`Username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=103 ;

--
-- Dumping data for table `penguins`
--

INSERT INTO `penguins` (`ID`, `Username`, `Nickname`, `Password`, `LoginKey`, `ConfirmationHash`, `SWID`, `Avatar`, `Email`, `RegistrationDate`, `Moderator`, `Inventory`, `Coins`, `Igloo`, `Igloos`, `Floors`, `Locations`, `Furniture`, `Color`, `Head`, `Face`, `Neck`, `Body`, `Hand`, `Feet`, `Photo`, `Flag`, `Walking`, `Banned`) VALUES
(101, 'Steffaloo', 'Steffaloo', 'DC647EB65E6711E155375218212B3964', '', '', '{045d200e-a48d-3a28-456e-6fc4e42a5afb}', '{"spriteScale":100,"spriteSpeed":100,"ignoresBlockLayer":false,"invisible":false,"floating":false}', 'lucy@kitsune.me', 1402955932, 1, '4%24059%24060%5374%2109%6036%1840%1841%501%9259', 190140, 1, '|,30|1403128079', '|,21|1403128083', '|,6|1403128029', '||,2226|1403128043|1,2227|1403128051|1,2230|1403128039|1,2231|1403128041|1,2229|1403128049|1,2228|1403128047|1,2235|1403128056|1,919|1403128058|1,908|1403128066|1,574|1403128074|1', 4, 1840, 2109, 0, 24059, 5374, 6036, 9259, 501, 0, '0'),
(102, 'Alice', 'Alice', 'DC647EB65E6711E155375218212B3964', '', '', '{7d492acf-4aa1-3644-d0ca-635740dd1ec8}', '{"spriteScale":100,"spriteSpeed":100,"ignoresBlockLayer":false,"invisible":false,"floating":false}', 'alice@wonderla.nd', 0, 0, '4', 199950, 2, '', '', '', '', 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, '0');

-- --------------------------------------------------------

--
-- Table structure for table `postcards`
--

CREATE TABLE IF NOT EXISTS `postcards` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Recipient` int(10) unsigned NOT NULL,
  `SenderName` char(12) NOT NULL,
  `SenderID` int(10) unsigned NOT NULL,
  `Details` varchar(12) NOT NULL,
  `Date` int(8) NOT NULL,
  `Type` smallint(5) unsigned NOT NULL,
  `HasRead` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=25 ;

-- --------------------------------------------------------

--
-- Table structure for table `puffles`
--

CREATE TABLE IF NOT EXISTS `puffles` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Owner` int(10) unsigned NOT NULL,
  `Name` char(12) NOT NULL,
  `AdoptionDate` int(8) NOT NULL,
  `Type` tinyint(3) unsigned NOT NULL,
  `Subtype` smallint(5) unsigned NOT NULL,
  `Hat` smallint(5) unsigned NOT NULL,
  `Food` tinyint(3) unsigned NOT NULL DEFAULT '100',
  `Play` tinyint(3) unsigned NOT NULL DEFAULT '100',
  `Rest` tinyint(3) unsigned NOT NULL DEFAULT '100',
  `Clean` tinyint(3) unsigned NOT NULL DEFAULT '100',
  `Backyard` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `puffles`
--

INSERT INTO `puffles` (`ID`, `Owner`, `Name`, `AdoptionDate`, `Type`, `Subtype`, `Hat`, `Food`, `Play`, `Rest`, `Clean`, `Backyard`) VALUES
(1, 101, 'Howdy', 1403127843, 8, 1007, 0, 100, 100, 100, 100, 0),
(2, 101, 'Doge', 1403127876, 0, 1006, 0, 100, 100, 100, 100, 1);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
