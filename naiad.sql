-- phpMyAdmin SQL Dump
-- version 
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 06, 2013 at 05:13 PM
-- Server version: 5.5.32-percona-sure1-log
-- PHP Version: 5.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `naiad`
--
CREATE DATABASE IF NOT EXISTS `naiad` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `naiad`;

-- --------------------------------------------------------

--
-- Table structure for table `general_values`
--

CREATE TABLE IF NOT EXISTS `general_values` (
  `fieldname` varchar(20) NOT NULL,
  `fieldvalue` varchar(200) NOT NULL,
  PRIMARY KEY (`fieldname`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `measurements`
--

CREATE TABLE IF NOT EXISTS `measurements` (
  `m_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mtime` datetime NOT NULL,
  `value` float NOT NULL,
  `detection_limit` tinyint(1) NOT NULL DEFAULT '0',
  `depth` float DEFAULT NULL,
  `duplicate` tinyint(1) NOT NULL DEFAULT '0',
  `collection_proc` varchar(100) DEFAULT NULL,
  `lab_id` varchar(100) NOT NULL,
  `lab_sample_id` varchar(20) DEFAULT NULL,
  `mnotes` varchar(255) DEFAULT NULL,
  `siteid` varchar(7) NOT NULL,
  `mtypeid` varchar(10) NOT NULL,
  `proj_id` varchar(16) DEFAULT NULL,
  `proc_id` varchar(10) DEFAULT NULL,
  `gear_id` varchar(16) DEFAULT NULL,
  `collected_by` varchar(15) DEFAULT NULL,
  `user_entry` int(11) DEFAULT NULL,
  `user_update` int(11) DEFAULT NULL,
  PRIMARY KEY (`m_id`),
  KEY `siteid` (`siteid`),
  KEY `mtypeid` (`mtypeid`),
  KEY `FK_proj_id` (`proj_id`),
  KEY `FK_proc_id` (`proc_id`),
  KEY `FK_gear_id` (`gear_id`),
  KEY `mtime` (`mtime`),
  KEY `depth` (`depth`),
  KEY `value` (`value`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=740802 ;

-- --------------------------------------------------------

--
-- Table structure for table `measurement_type`
--

CREATE TABLE IF NOT EXISTS `measurement_type` (
  `mtypeid` varchar(10) NOT NULL,
  `mtname` varchar(255) NOT NULL,
  `storet_header` varchar(255) NOT NULL,
  `units` varchar(10) NOT NULL,
  `lake` tinyint(1) NOT NULL DEFAULT '0',
  `stream` tinyint(1) NOT NULL DEFAULT '0',
  `l_collection_method` varchar(100) DEFAULT NULL,
  `l_lower_bound` float DEFAULT NULL,
  `l_upper_bound` float DEFAULT NULL,
  `l_profile` tinyint(1) NOT NULL DEFAULT '0',
  `l_multi_depth` tinyint(1) NOT NULL DEFAULT '0',
  `s_collection_method` varchar(100) DEFAULT NULL,
  `s_lower_bound` float DEFAULT NULL,
  `s_upper_bound` float DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `disp_order` smallint(6) NOT NULL DEFAULT '10',
  `notes` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`mtypeid`),
  UNIQUE KEY `Name` (`mtname`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
--
-- Table structure for table `monitoring_sites`
--

CREATE TABLE IF NOT EXISTS `monitoring_sites` (
  `siteid` varchar(7) NOT NULL,
  `latitude` float(9,6) DEFAULT NULL,
  `longitude` float(9,6) DEFAULT NULL,
  `site_description` varchar(255) NOT NULL,
  `monitor_start` date DEFAULT NULL,
  `monitor_end` date DEFAULT NULL,
  `monitor_type` enum('L','S','P') NOT NULL DEFAULT 'L',
  `project_station_id` varchar(100) DEFAULT NULL,
  `storet_station_id` varchar(30) NOT NULL,
  `mpca_site_id` varchar(10) DEFAULT NULL,
  `waterbody_id` int(10) unsigned NOT NULL,
  `project_site_id` varchar(50) NOT NULL,
  PRIMARY KEY (`siteid`),
  KEY `waterbody_id` (`waterbody_id`),
  KEY `monitoring_sites_projSite_id` (`project_site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mon_gear`
--

CREATE TABLE IF NOT EXISTS `mon_gear` (
  `gear_id` varchar(16) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `lake` tinyint(4) NOT NULL DEFAULT '0',
  `stream` tinyint(4) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`gear_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mon_projects`
--

CREATE TABLE IF NOT EXISTS `mon_projects` (
  `proj_id` varchar(16) NOT NULL,
  `vendor` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`proj_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `precipitation_measurements`
--

CREATE TABLE IF NOT EXISTS `precipitation_measurements` (
  `PM_ID` int(11) NOT NULL AUTO_INCREMENT,
  `pmdate` datetime NOT NULL,
  `inches` float(5,3) NOT NULL,
  `air_temp_f` float DEFAULT NULL,
  `wind_speed_mph` float DEFAULT NULL,
  `wind_dir` int(11) DEFAULT NULL,
  `pressure_mmhg` float DEFAULT NULL,
  `PS_ID` varchar(6) NOT NULL,
  PRIMARY KEY (`PM_ID`),
  UNIQUE KEY `pmdate_2` (`pmdate`,`PS_ID`),
  KEY `PS_ID` (`PS_ID`),
  KEY `pmdate` (`pmdate`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=362188 ;

-- --------------------------------------------------------

--
-- Table structure for table `precipitation_stations`
--

CREATE TABLE IF NOT EXISTS `precipitation_stations` (
  `StationID` varchar(6) NOT NULL,
  `Station_Name` varchar(50) NOT NULL,
  `Description` varchar(255) NOT NULL,
  `Latitude` float NOT NULL,
  `Longitude` float NOT NULL,
  PRIMARY KEY (`StationID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sample_procs`
--

CREATE TABLE IF NOT EXISTS `sample_procs` (
  `proc_id` varchar(10) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `lake` tinyint(4) NOT NULL DEFAULT '0',
  `stream` tinyint(4) NOT NULL DEFAULT '0',
  `sample_depth_upper` float DEFAULT NULL,
  `sample_depth_lower` float DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`proc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sitings`
--

CREATE TABLE IF NOT EXISTS `sitings` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `lat` float NOT NULL,
  `lng` float NOT NULL,
  `date` date NOT NULL COMMENT 'When the fish were sited.',
  `spotter` varchar(50) NOT NULL COMMENT 'By whom.',
  `tagColor` varchar(40) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL COMMENT 'Any interesting additional data.',
  `dateAdded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`spotter`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=42 ;

-- --------------------------------------------------------

--
-- Table structure for table `waterbodies`
--

CREATE TABLE IF NOT EXISTS `waterbodies` (
  `waterbody_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wbody_type` enum('L','S','W') NOT NULL DEFAULT 'L',
  `wbody_name` varchar(255) NOT NULL,
  `DNR_LAKE_ID` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`waterbody_id`),
  KEY `DNR_LAKE_ID` (`DNR_LAKE_ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=121 ;

-- --------------------------------------------------------

--
-- Table structure for table `waterlevels`
--

CREATE TABLE IF NOT EXISTS `waterlevels` (
  `WLVL_ID` int(11) NOT NULL AUTO_INCREMENT,
  `WLVL_Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `WLVL_Level` double NOT NULL DEFAULT '0',
  `WLVL_Depth` double DEFAULT NULL,
  `WLVL_WaterBody` varchar(50) NOT NULL DEFAULT '',
  `WLVL_SentEmail` tinyint(1) DEFAULT '0',
  `WLVL_TelemetryOn` tinyint(1) NOT NULL DEFAULT '0',
  `WLVL_Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`WLVL_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Water Level measurements' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wqdb_user_users`
--

CREATE TABLE IF NOT EXISTS `wqdb_user_users` (
  `userID` bigint(20) NOT NULL AUTO_INCREMENT,
  `fname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `created_timestamp` int(11) DEFAULT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;


-- Make the root user; grant admin status
INSERT INTO `naiad`.`wqdb_user_users` (
`userID` ,
`fname` ,
`lname` ,
`email` ,
`is_admin` ,
`created_timestamp`
)
VALUES (
1 , 'root', 'root', 'root', '1', NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `wqdb_user_user_creds`
--

CREATE TABLE IF NOT EXISTS `wqdb_user_user_creds` (
  `userID` bigint(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `pass` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- Create the password entry for the root user, pwd 'naiad_root'
INSERT INTO `naiad`.`wqdb_user_user_creds` (
`userID` ,
`email` ,
`pass`
)
VALUES (
'22', 'root', '0445e0d8552a5cde2a6d20203b5f9151'
);

--Create some dummy data, so that the database is usable/testable.
INSERT INTO `naiad`.`waterbodies` (`waterbody_id`, `wbody_type`, `wbody_name`, `DNR_LAKE_ID`) VALUES (1, 'L', 'Test Waterbody', '001234');
INSERT INTO `naiad`.`measurement_type` (`mtypeid`, `mtname`, `storet_header`, `units`, `lake`, `stream`, `l_collection_method`, `l_lower_bound`, `l_upper_bound`, `l_profile`, `l_multi_depth`, `s_collection_method`, `s_lower_bound`, `s_upper_bound`, `active`, `disp_order`, `notes`) 
  VALUES ('TP', 'Total Phosphorus', '', 'ug/L', '1', '1', NULL, '1', '250', '1', '1', NULL, '1', '500', '1', '1', 'Total Phosphorus in the water column.  Key nutrient for aquatic plants & algae.');
INSERT INTO `naiad`.`monitoring_sites` (`siteid`, `latitude`, `longitude`, `site_description`, `monitor_start`, `monitor_end`, `monitor_type`, `project_station_id`, `storet_station_id`, `mpca_site_id`, `waterbody_id`, `project_site_id`) 
  VALUES ('TST_01', '45', '-93', 'Test Monitoring Location (lake)', '2013-12-01', '2013-12-31', 'L', NULL, '', NULL, '1', '');
INSERT INTO `naiad`.`measurements` (`m_id`, `mtime`, `value`, `detection_limit`, `depth`, `duplicate`, `collection_proc`, `lab_id`, `lab_sample_id`, `mnotes`, `siteid`, `mtypeid`, `proj_id`, `proc_id`, `gear_id`, `collected_by`, `user_entry`, `user_update`) 
  VALUES ('1', '2013-12-26 00:00:00', '45', '0', '1.1', '0', NULL, '', NULL, 'The first test value (TP)', 'TST_01', 'TP', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure for view `meas_all`
--
DROP TABLE IF EXISTS `meas_all`;

CREATE  VIEW `meas_all` AS select `measurements`.`mtime` AS `timeframe`,`measurements`.`value` AS `value`,`measurements`.`detection_limit` AS `detection_limit`,`measurements`.`depth` AS `depth`,`measurements`.`siteid` AS `siteid`,`measurements`.`mtypeid` AS `mtypeid`,`measurements`.`mnotes` AS `mnotes`,`measurements`.`duplicate` AS `duplicate`,`measurements`.`proj_id` AS `proj_id` from `measurements`;

-- --------------------------------------------------------

--
-- Structure for view `meas_at_site`
--
DROP TABLE IF EXISTS `meas_at_site`;

CREATE  VIEW `meas_at_site` AS select `measurements`.`mtypeid` AS `mtypeid`,`measurements`.`siteid` AS `siteid`,`measurement_type`.`mtname` AS `mtname`,count(`measurements`.`m_id`) AS `cnt` from (`measurements` left join `measurement_type` on((`measurements`.`mtypeid` = `measurement_type`.`mtypeid`))) group by `measurements`.`mtypeid`,`measurements`.`siteid`,`measurement_type`.`mtname`;

-- --------------------------------------------------------

--
-- Structure for view `meas_monthly`
--
DROP TABLE IF EXISTS `meas_monthly`;

CREATE  VIEW `meas_monthly` AS select date_format(`measurements`.`mtime`,_utf8'%Y-%m') AS `timeframe`,avg(`measurements`.`value`) AS `value`,0 AS `detection_limit`,`measurements`.`depth` AS `depth`,`measurements`.`siteid` AS `siteid`,`measurements`.`mtypeid` AS `mtypeid`,_utf8'' AS `mnotes`,0 AS `duplicate`,`measurements`.`proj_id` AS `proj_id` from `measurements` group by date_format(`measurements`.`mtime`,_utf8'%Y-%m'),`measurements`.`depth`,`measurements`.`siteid`,`measurements`.`mtypeid`;

-- --------------------------------------------------------

--
-- Structure for view `meas_weekly`
--
DROP TABLE IF EXISTS `meas_weekly`;

CREATE  VIEW `meas_weekly` AS select date_format(`measurements`.`mtime`,_utf8'%x-%v') AS `timeframe`,avg(`measurements`.`value`) AS `value`,0 AS `detection_limit`,`measurements`.`depth` AS `depth`,`measurements`.`siteid` AS `siteid`,`measurements`.`mtypeid` AS `mtypeid`,_utf8'' AS `mnotes`,0 AS `duplicate`,`measurements`.`proj_id` AS `proj_id` from `measurements` group by date_format(`measurements`.`mtime`,_utf8'%x-%v'),`measurements`.`depth`,`measurements`.`siteid`,`measurements`.`mtypeid`;

-- --------------------------------------------------------

--
-- Structure for view `meas_daily`
--
CREATE  VIEW `meas_daily` AS 
select date_format(`measurements`.`mtime`,_utf8'%Y-%m-%d') AS `timeframe`,
  avg(`measurements`.`value`) AS `value`,
  0 AS `detection_limit`,`measurements`.`depth` AS `depth`,
  `measurements`.`siteid` AS `siteid`,
  `measurements`.`mtypeid` AS `mtypeid`,
  _utf8'' AS `mnotes`,
  0 AS `duplicate`,
  `measurements`.`proj_id` AS `proj_id` 
from `measurements` 
group by date_format(`measurements`.`mtime`,_utf8'%Y-%m-%d'),`measurements`.`depth`,`measurements`.`siteid`,`measurements`.`mtypeid`;

-- --------------------------------------------------------

--
-- Structure for view `meas_yearly`
--
DROP TABLE IF EXISTS `meas_yearly`;

CREATE  VIEW `meas_yearly` AS select date_format(`measurements`.`mtime`,_utf8'%Y') AS `timeframe`,avg(`measurements`.`value`) AS `value`,0 AS `detection_limit`,`measurements`.`depth` AS `depth`,`measurements`.`siteid` AS `siteid`,`measurements`.`mtypeid` AS `mtypeid`,_utf8'' AS `mnotes`,0 AS `duplicate`,`measurements`.`proj_id` AS `proj_id` from `measurements` group by date_format(`measurements`.`mtime`,_utf8'%Y'),`measurements`.`depth`,`measurements`.`siteid`,`measurements`.`mtypeid`;

-- --------------------------------------------------------

--
-- Structure for view `precip_daily_view`
--
DROP TABLE IF EXISTS `precip_daily_view`;

CREATE  VIEW `precip_daily_view` AS select `precipitation_measurements`.`PS_ID` AS `PS_ID`,date_format(`precipitation_measurements`.`pmdate`,_utf8'%Y-%m-%d') AS `day`,sum(`precipitation_measurements`.`inches`) AS `precip`,max(`precipitation_measurements`.`air_temp_f`) AS `max_T`,min(`precipitation_measurements`.`air_temp_f`) AS `min_T`,avg(`precipitation_measurements`.`air_temp_f`) AS `avg_T`,max(`precipitation_measurements`.`wind_speed_mph`) AS `max_wind`,min(`precipitation_measurements`.`wind_speed_mph`) AS `min_wind`,avg(`precipitation_measurements`.`wind_speed_mph`) AS `avg_wind`,avg(`precipitation_measurements`.`wind_dir`) AS `avg_wind_dir`,max(`precipitation_measurements`.`pressure_mmhg`) AS `max_press`,min(`precipitation_measurements`.`pressure_mmhg`) AS `min_press`,avg(`precipitation_measurements`.`pressure_mmhg`) AS `avg_press` from `precipitation_measurements` group by date_format(`precipitation_measurements`.`pmdate`,_utf8'%Y-%m-%d'),`precipitation_measurements`.`PS_ID`;

-- --------------------------------------------------------

--
-- Structure for view `precip_monthly_view`
--
DROP TABLE IF EXISTS `precip_monthly_view`;

CREATE  VIEW `precip_monthly_view` AS select `precipitation_measurements`.`PS_ID` AS `PS_ID`,date_format(`precipitation_measurements`.`pmdate`,_utf8'%Y-%m') AS `day`,sum(`precipitation_measurements`.`inches`) AS `precip`,max(`precipitation_measurements`.`air_temp_f`) AS `max_T`,min(`precipitation_measurements`.`air_temp_f`) AS `min_T`,avg(`precipitation_measurements`.`air_temp_f`) AS `avg_T`,max(`precipitation_measurements`.`wind_speed_mph`) AS `max_wind`,min(`precipitation_measurements`.`wind_speed_mph`) AS `min_wind`,avg(`precipitation_measurements`.`wind_speed_mph`) AS `avg_wind`,avg(`precipitation_measurements`.`wind_dir`) AS `avg_wind_dir`,max(`precipitation_measurements`.`pressure_mmhg`) AS `max_press`,min(`precipitation_measurements`.`pressure_mmhg`) AS `min_press`,avg(`precipitation_measurements`.`pressure_mmhg`) AS `avg_press` from `precipitation_measurements` group by date_format(`precipitation_measurements`.`pmdate`,_utf8'%Y-%m'),`precipitation_measurements`.`PS_ID`;

-- --------------------------------------------------------

--
-- Structure for view `sites_list`
--
DROP TABLE IF EXISTS `sites_list`;

CREATE  VIEW `sites_list` AS select `monitoring_sites`.`siteid` AS `siteid`,`monitoring_sites`.`site_description` AS `site_description`,(1 - isnull(`monitoring_sites`.`monitor_end`)) AS `active`,`monitoring_sites`.`monitor_type` AS `monitor_type`,`monitoring_sites`.`waterbody_id` AS `waterbody_id` from `monitoring_sites`;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
