/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addrprint` (
  `AddrPrintName` varchar(40) NOT NULL default '',
  `PaperHeight` smallint(5) unsigned NOT NULL default '0' COMMENT 'Long size (height in portrait)',
  `PaperWidth` smallint(5) unsigned NOT NULL default '0' COMMENT 'Short size (width in portrait)',
  `PaperBottomMargin` smallint(5) unsigned NOT NULL default '0' COMMENT 'Space below return address block',
  `PaperLeftMargin` tinyint(3) unsigned NOT NULL default '0' COMMENT 'Space to left of return address block',
  `PCPointSize` tinyint(4) unsigned NOT NULL default '0',
  `PCTopMargin` smallint(5) unsigned NOT NULL default '0' COMMENT 'From paper bottom edge to PC baseline',
  `PCLeftMargin` smallint(5) unsigned NOT NULL default '0' COMMENT 'From paper left edge to PC left side',
  `PCSpacing` decimal(4,1) unsigned NOT NULL default '0.0' COMMENT 'Offset for each digit',
  `PCExtraSpace` decimal(4,1) unsigned NOT NULL default '0.0' COMMENT 'Extra offset at hyphen',
  `AddrPointSize` tinyint(4) unsigned NOT NULL default '0',
  `AddrHeight` smallint(6) NOT NULL default '0' COMMENT 'Will wrap text lines at this size',
  `AddrPositionX` smallint(6) NOT NULL default '0' COMMENT 'From paper left edge to start of address',
  `AddrPositionY` smallint(6) NOT NULL default '0' COMMENT 'From paper bottom edge to top of address block',
  `NamePointSize` tinyint(4) unsigned NOT NULL default '0',
  `RetAddrContent` text NOT NULL COMMENT 'Any legal LaTeX commands',
  `NJAddrPointSize` tinyint(4) unsigned NOT NULL default '0',
  `NJAddrHeight` smallint(6) NOT NULL default '0' COMMENT 'Long dimension of area for Non-Japan address',
  `NJAddrPositionX` smallint(6) NOT NULL default '0' COMMENT 'From paper left edge to start of address',
  `NJAddrPositionY` smallint(6) NOT NULL default '0' COMMENT 'From paper bottom edge to top of address block',
  `NJRetAddrLeftMargin` tinyint(4) unsigned NOT NULL default '0',
  `NJRetAddrTopMargin` tinyint(4) unsigned NOT NULL default '0',
  `NJRetAddrContent` text NOT NULL,
  PRIMARY KEY  (`AddrPrintName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `PersonID` int(11) unsigned NOT NULL default '0',
  `EventID` int(11) unsigned NOT NULL default '0',
  `AttendDate` date NOT NULL default '0000-00-00',
  `StartTime` time default NULL COMMENT 'Only used if event.UseTimes=1',
  `EndTime` time default NULL COMMENT 'Only used if event.UseTimes=1',
  PRIMARY KEY  (`PersonID`,`EventID`,`AttendDate`),
  KEY `EventsAttendance` (`EventID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `CategoryID` int(11) unsigned NOT NULL auto_increment,
  `Category` varchar(60) NOT NULL default '',
  `UseFor` enum('OP','P','O') character set ascii NOT NULL default 'OP' COMMENT 'Whether the category can be used for people, orgs, or both',
  PRIMARY KEY  (`CategoryID`),
  KEY `Category` (`Category`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `Parameter` varchar(30) character set ascii collate ascii_bin NOT NULL default '',
  `Value` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`Parameter`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact` (
  `ContactID` int(11) unsigned NOT NULL auto_increment,
  `PersonID` int(11) unsigned NOT NULL default '0',
  `ContactTypeID` int(11) unsigned NOT NULL default '0',
  `ContactDate` date NOT NULL default '0000-00-00',
  `Description` text,
  PRIMARY KEY  (`ContactID`),
  KEY `ContactDate` (`ContactDate`),
  KEY `PersonID` (`PersonID`),
  KEY `ContactTypeID` (`ContactTypeID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contacttype` (
  `ContactTypeID` int(11) unsigned NOT NULL auto_increment,
  `ContactType` varchar(30) NOT NULL default '',
  `BGColor` char(6) character set ascii collate ascii_bin NOT NULL default 'FFFFFF',
  `Template` text NOT NULL,
  PRIMARY KEY  (`ContactTypeID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom` (
  `CustomName` varchar(50) collate utf8_unicode_ci NOT NULL,
  `SQL` text collate utf8_unicode_ci NOT NULL,
  `CSS` text collate utf8_unicode_ci NOT NULL,
  `IsTable` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`CustomName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donation` (
  `DonationID` int(11) unsigned NOT NULL auto_increment,
  `PersonID` int(11) unsigned NOT NULL default '0',
  `PledgeID` int(11) unsigned default NULL,
  `DonationDate` date NOT NULL default '0000-00-00',
  `DonationTypeID` int(11) unsigned NOT NULL default '0',
  `Amount` decimal(10,2) NOT NULL default '0.00',
  `Description` varchar(200) NOT NULL default '' COMMENT 'Gifts in kind, foreign currency, etc.',
  `Processed` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`DonationID`),
  KEY `PersonID` (`PersonID`),
  KEY `DonationDate` (`DonationDate`),
  KEY `DonationTypeID` (`DonationTypeID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donationtype` (
  `DonationTypeID` int(11) NOT NULL auto_increment,
  `DonationType` varchar(50) NOT NULL default '',
  `BGColor` char(6) character set ascii collate ascii_bin NOT NULL default 'FFFFFF',
  PRIMARY KEY  (`DonationTypeID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event` (
  `EventID` int(11) unsigned NOT NULL auto_increment,
  `Event` varchar(60) NOT NULL default '',
  `EventStartDate` date NOT NULL default '0000-00-00',
  `EventEndDate` date default NULL,
  `UseTimes` tinyint(1) NOT NULL default '0',
  `Active` tinyint(1) NOT NULL default '0',
  `Remarks` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`EventID`),
  KEY `Event` (`Event`),
  KEY `EventStartDate` (`EventStartDate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `household` (
  `HouseholdID` int(11) unsigned NOT NULL auto_increment,
  `NonJapan` tinyint(1) NOT NULL default '0' COMMENT 'Address is foreign, so no reference to postal code table',
  `PostalCode` varchar(8) NOT NULL default '',
  `Address` varchar(200) NOT NULL default '',
  `AddressComp` varchar(400) NOT NULL default '' COMMENT 'Copy of CONCAT(PostalCode,Prefecture,ShiKuCho,Address)',
  `RomajiAddress` varchar(200) NOT NULL default '',
  `RomajiAddressComp` varchar(400) NOT NULL default '' COMMENT 'Copy of CONCAT(RomajiAddress,space,Romaji,space,PostalCode)',
  `Phone` varchar(20) NOT NULL default '',
  `FAX` varchar(20) NOT NULL default '',
  `LabelName` varchar(100) NOT NULL default '',
  `Photo` tinyint(1) NOT NULL default '0',
  `PhotoCaption` varchar(100) NOT NULL default '',
  `UpdDate` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`HouseholdID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `labelprint` (
  `LabelType` varchar(50) NOT NULL default '',
  `PaperSize` varchar(10) NOT NULL default 'a4' COMMENT 'used in documentclass - values typically "a4" or "letter"',
  `NumRows` tinyint(4) unsigned NOT NULL default '0' COMMENT 'Number of labels down the page',
  `NumCols` tinyint(4) unsigned NOT NULL default '0' COMMENT 'Number of labels across the page',
  `PageMarginTop` decimal(4,1) unsigned NOT NULL default '0.0' COMMENT 'From paper top edge to top labels',
  `PageMarginLeft` decimal(4,1) unsigned NOT NULL default '0.0' COMMENT 'From paper left edge to left-most labels',
  `LabelWidth` decimal(4,1) unsigned NOT NULL default '0.0' COMMENT 'Width of a whole single label',
  `LabelHeight` decimal(4,1) unsigned NOT NULL default '0.0' COMMENT 'Height of a whole single label',
  `GutterX` decimal(4,1) unsigned NOT NULL default '0.0' COMMENT 'Space between labels, if any',
  `GutterY` decimal(4,1) unsigned NOT NULL default '0.0' COMMENT 'Space between labels, if any',
  `AddrMarginLeft` decimal(4,1) unsigned NOT NULL default '0.0' COMMENT 'From edge of label to text block',
  `AddrMarginRight` decimal(4,1) unsigned NOT NULL default '0.0' COMMENT 'From edge of label to text block (where text will wrap)',
  `AddrPointSize` tinyint(4) unsigned NOT NULL default '0' COMMENT 'Font size for Japan addresses',
  `NJAddrPointSize` tinyint(4) unsigned NOT NULL default '0' COMMENT 'Font size for non-Japan addresses',
  `NamePointSize` tinyint(4) unsigned NOT NULL default '0' COMMENT 'For Japan addresses, name can be larger',
  PRIMARY KEY  (`LabelType`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login` (
  `UserID` varchar(16) character set ascii collate ascii_bin NOT NULL default '',
  `Password` char(41) character set ascii collate ascii_bin NOT NULL,
  `UserName` varchar(50) NOT NULL default '',
  `Categories` varchar(100) NOT NULL default '',
  `Admin` tinyint(1) NOT NULL default '0',
  `Language` varchar(6) character set ascii NOT NULL default 'ja_JP',
  `HideDonations` tinyint(1) NOT NULL default '0' COMMENT 'User-level control for when config table contains "donations=yes"',
  `DashboardHead` text NOT NULL COMMENT 'PHP run in head (usually mostly Javascript)',
  `DashboardBody` text NOT NULL COMMENT 'PHP after title',
  PRIMARY KEY  (`UserID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_log` (
  `UserID` varchar(16) character set ascii NOT NULL default '',
  `LoginTime` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `IPAddress` varchar(15) character set ascii collate ascii_bin NOT NULL default '',
  `UserAgent` varchar(100) character set ascii NOT NULL default '',
  `Languages` varchar(100) character set ascii NOT NULL default '',
  PRIMARY KEY  (`UserID`,`LoginTime`),
  KEY `IPAddress` (`IPAddress`),
  KEY `LoginTime` (`LoginTime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Log of logins to Contacts DB';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `output` (
  `Class` varchar(20) character set ascii collate ascii_bin NOT NULL default '',
  `Header` varchar(255) default '',
  `OutputSQL` text character set ascii NOT NULL,
  PRIMARY KEY  (`Class`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `outputset` (
  `SetName` varchar(40) NOT NULL default '',
  `ForHousehold` tinyint(1) NOT NULL default '0',
  `OrderNum` tinyint(1) unsigned NOT NULL default '0',
  `Class` varchar(20) character set ascii collate ascii_bin NOT NULL default '',
  `CSS` text character set ascii,
  PRIMARY KEY  (`SetName`,`OrderNum`,`ForHousehold`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `percat` (
  `PersonID` int(11) unsigned NOT NULL default '0',
  `CategoryID` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`PersonID`,`CategoryID`),
  KEY `CategoryID` (`CategoryID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perorg` (
  `PersonID` int(11) unsigned NOT NULL,
  `OrgID` int(11) unsigned NOT NULL,
  `Leader` tinyint(1) NOT NULL default '0' COMMENT 'Designates leader/pastor of org',
  PRIMARY KEY  (`PersonID`,`OrgID`),
  KEY `OrgID` (`OrgID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Belonging relationship of person to organization';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `person` (
  `PersonID` int(11) unsigned NOT NULL auto_increment,
  `FullName` varchar(100) NOT NULL default '',
  `Furigana` varchar(100) NOT NULL default '',
  `Sex` enum('','M','F') character set ascii NOT NULL default '',
  `HouseholdID` int(11) unsigned NOT NULL default '0',
  `Relation` varchar(6) character set ascii NOT NULL default '',
  `Title` varchar(6) NOT NULL default '',
  `CellPhone` varchar(30) character set ascii NOT NULL default '',
  `Email` varchar(70) character set ascii NOT NULL default '',
  `Birthdate` date NOT NULL default '0000-00-00',
  `Country` varchar(30) NOT NULL default '',
  `URL` varchar(150) NOT NULL default '',
  `Organization` tinyint(1) NOT NULL default '0' COMMENT 'If TRUE, allows record to be an Org in perorg',
  `Remarks` text NOT NULL,
  `Photo` tinyint(1) NOT NULL default '0',
  `UpdDate` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`PersonID`),
  KEY `Furigana` (`Furigana`),
  KEY `FullName` (`FullName`),
  KEY `Email` (`Email`),
  KEY `Organization` (`Organization`,`Furigana`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `photoprint` (
  `PhotoPrintName` varchar(80) NOT NULL default '',
  `PaperSizeName` varchar(20) NOT NULL default '',
  `PaperHeight` smallint(6) unsigned NOT NULL default '0',
  `PaperWidth` smallint(6) unsigned NOT NULL default '0',
  `PaperTopMargin` tinyint(4) unsigned NOT NULL default '0',
  `PaperBottomMargin` tinyint(4) unsigned NOT NULL default '0',
  `PaperLeftMargin` tinyint(4) unsigned NOT NULL default '0',
  `PaperRightMargin` tinyint(4) unsigned NOT NULL default '0',
  `PhotoHeight` smallint(8) unsigned NOT NULL default '0',
  `PhotoWidth` smallint(8) unsigned NOT NULL default '0',
  `Gutter` tinyint(4) unsigned NOT NULL default '0',
  `Font` varchar(30) NOT NULL default '',
  `PointSize` tinyint(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`PhotoPrintName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pledge` (
  `PledgeID` int(11) unsigned NOT NULL auto_increment,
  `PersonID` int(11) unsigned NOT NULL default '0',
  `DonationTypeID` int(11) unsigned NOT NULL default '0',
  `StartDate` date NOT NULL default '0000-00-00',
  `EndDate` date NOT NULL default '0000-00-00',
  `Amount` decimal(10,2) NOT NULL default '0.00',
  `TimesPerYear` tinyint(4) NOT NULL default '12',
  `PledgeDesc` varchar(150) default '',
  PRIMARY KEY  (`PledgeID`),
  KEY `PersonID` (`PersonID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `postalcode` (
  `PostalCode` varchar(8) character set ascii collate ascii_bin NOT NULL default '',
  `Prefecture` varchar(12) NOT NULL default '',
  `ShiKuCho` varchar(54) NOT NULL default '',
  `Romaji` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`PostalCode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `upload` (
  `UploadID` int(11) unsigned NOT NULL auto_increment,
  `PersonID` int(11) unsigned NOT NULL,
  `UploadTime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `FileName` varchar(120) NOT NULL default '',
  `Description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`UploadID`),
  KEY `PersonID` (`PersonID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadtype` (
  `Extension` varchar(8) character set ascii NOT NULL,
  `MIME` varchar(100) character set ascii NOT NULL,
  `Binary` tinyint(1) NOT NULL default '1',
  `InBrowser` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`Extension`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
