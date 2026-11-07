-- -------------------------------------------------------------
-- TablePlus 27.2.0(839)
--
-- https://tableplus.com/
--
-- Database: udTime
-- Generation Time: 2026-11-06 16:54:55.4630
-- -------------------------------------------------------------


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


CREATE TABLE `againstworktime` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `type` int(11) NOT NULL DEFAULT 1,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=350 DEFAULT CHARSET=latin1;

CREATE TABLE `apikeys` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) NOT NULL,
  `key_hash` char(64) NOT NULL,
  `scopes` longtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_hash` (`key_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

CREATE TABLE `asworktime` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT 1,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=581 DEFAULT CHARSET=latin1;

CREATE TABLE `breakdb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `starttime` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `logged` tinyint(1) NOT NULL DEFAULT 0,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3738 DEFAULT CHARSET=latin1;

CREATE TABLE `deleted` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('work','break','asworktime','againstworktime') NOT NULL,
  `original_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=530 DEFAULT CHARSET=latin1;

CREATE TABLE `lookup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(128) NOT NULL,
  `name` varchar(128) NOT NULL,
  `code` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

CREATE TABLE `lookups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(128) NOT NULL,
  `name` varchar(128) NOT NULL,
  `code` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

CREATE TABLE `monthstats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `towork` int(11) NOT NULL,
  `againstworktime` int(11) NOT NULL DEFAULT 0,
  `worked` int(11) NOT NULL DEFAULT 0,
  `asworktime` int(11) NOT NULL DEFAULT 0,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1193 DEFAULT CHARSET=latin1;

CREATE TABLE `projectdb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `description` text DEFAULT NULL,
  `color` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=86 DEFAULT CHARSET=latin1;

CREATE TABLE `userdb` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `firstname` text DEFAULT NULL,
  `lastname` text DEFAULT NULL,
  `registerdate` int(11) NOT NULL,
  `avatar` blob DEFAULT NULL,
  `dworkweek` int(11) NOT NULL,
  `daysinworkweek` int(11) NOT NULL DEFAULT 5,
  `offset` int(11) NOT NULL DEFAULT 0,
  `timezone` text NOT NULL,
  `activeperiod` int(11) DEFAULT NULL,
  `activetype` text DEFAULT NULL,
  `tempfiled` int(11) NOT NULL,
  `statsstartdate` int(11) NOT NULL,
  `admin` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`member_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

CREATE TABLE `weeksdb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `week` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `Monday` int(11) NOT NULL DEFAULT 0,
  `Tuesday` int(11) NOT NULL DEFAULT 0,
  `Wednesday` int(11) NOT NULL DEFAULT 0,
  `Thursday` int(11) NOT NULL DEFAULT 0,
  `Friday` int(11) NOT NULL DEFAULT 0,
  `Saturday` int(11) NOT NULL DEFAULT 0,
  `Sunday` int(11) NOT NULL DEFAULT 0,
  `holiday` int(11) NOT NULL DEFAULT 0,
  `worktime` int(11) NOT NULL DEFAULT 0,
  `total` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=125 DEFAULT CHARSET=latin1;

CREATE TABLE `weekstats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `week` int(11) NOT NULL,
  `towork` int(11) NOT NULL,
  `againstworktime` int(11) NOT NULL DEFAULT 0,
  `worked` int(11) NOT NULL,
  `asworktime` int(11) NOT NULL DEFAULT 0,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5242 DEFAULT CHARSET=latin1;

CREATE TABLE `workdb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `starttime` bigint(11) DEFAULT NULL,
  `endtime` bigint(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `logged` tinyint(1) NOT NULL DEFAULT 0,
  `project_id` int(11) DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4034 DEFAULT CHARSET=latin1;



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;