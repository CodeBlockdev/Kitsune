-- phpMyAdmin SQL Dump
-- version 4.1.12
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2014 at 10:44 PM
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
(1, 101, 73, 21, 0, '908|725|85|2|1,2226|581|146|1|1', 6, '', 1),
(2, 102, 1, 0, 0, '', 1, '', 1);

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
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Username` (`Username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=103 ;

--
-- Dumping data for table `penguins`
--

INSERT INTO `penguins` (`ID`, `Username`, `Nickname`, `Password`, `LoginKey`, `ConfirmationHash`, `SWID`, `Avatar`, `Email`, `RegistrationDate`, `Inventory`, `Coins`, `Igloo`, `Igloos`, `Floors`, `Locations`, `Furniture`, `Color`, `Head`, `Face`, `Neck`, `Body`, `Hand`, `Feet`, `Photo`, `Flag`) VALUES
(101, 'Arthur', 'Arthur', 'DC647EB65E6711E155375218212B3964', '', '', '{de2da5a4-6d83-c05e-b774-0ab3773f5795}', '{"spriteScale":100,"spriteSpeed":100,"ignoresBlockLayer":false,"invisible":false,"floating":false}', 'lucy@kitsune.org', 1399248450, '1%2%3%4%711%712%9%9088%9262%9260%9037%210%1539%717%2151%24090%1865%1864%24089%24088%1863%24059%24060%1866%24091%4790%4533%1528%5374%2109%1867%24092%6036%1837%24056%24055%1836%6112%1840%3108%3203%3114%3111%3202%1847%1845%3206%1368%1367%1363%1360%1361%303%1373%1372%1846%1844%1853%4560%7188%501%3032%821%8006%8010%8011%8009', 979075, 1, '1|0,73|1400445126', '14|1400443221,21|1400443224,7|1400443228', '6|1400351014', '966||1,2226||1,908|1400443758|1', 4, 1840, 2109, 0, 24059, 5374, 6036, 9260, 7188),
(102, 'Blackhole', 'Blackhole', 'DC647EB65E6711E155375218212B3964', '', '', '{747e5e06-12ff-283a-6f6a-5e5e77cf7b7f}', '{"spriteScale":100,"spriteSpeed":100,"ignoresBlockLayer":false,"invisible":false,"floating":false}', 'black@hole.org', 1400118790, '4%413%221', 200000, 2, '', '', '', '', 4, 413, 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `puffles`
--

CREATE TABLE IF NOT EXISTS `puffles` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Owner` int(10) unsigned NOT NULL,
  `Name` char(12) NOT NULL,
  `Type` tinyint(3) unsigned NOT NULL,
  `Hat` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
