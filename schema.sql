CREATE TABLE `action` (
  `ActionID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `ActionTypeID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `ActionDate` date NOT NULL DEFAULT '0000-00-00',
  `Description` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ActionID`),
  KEY `PersonID` (`PersonID`),
  KEY `ActionDate` (`ActionDate`),
  KEY `ActionTypeID` (`ActionTypeID`),
  CONSTRAINT `action_ibfk_1` FOREIGN KEY (`ActionTypeID`) REFERENCES `actiontype` (`ActionTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `actiontype` (
  `ActionTypeID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `ActionType` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `BGColor` char(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'FFFFFF',
  `Template` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ActionTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `addrprint` (
  `ListOrder` tinyint(4) unsigned NOT NULL DEFAULT 0 COMMENT 'Order for selection pulldown',
  `AddrPrintName` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `DefaultStamp` varchar(20) CHARACTER SET ascii NOT NULL DEFAULT 'none' COMMENT 'Default post office stamp setting',
  `PaperHeight` smallint(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Long size (height in portrait)',
  `PaperWidth` smallint(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Short size (width in portrait)',
  `PaperBottomMargin` smallint(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Space below return address block',
  `PaperLeftMargin` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Space to left of return address block',
  `PCPointSize` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `PCTopMargin` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'From paper bottom edge to PC baseline',
  `PCLeftMargin` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'From paper left edge to PC left side',
  `PCSpacing` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'Offset for each digit',
  `PCExtraSpace` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'Extra offset at hyphen',
  `Tategaki` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Affects the address and name for Japan addresses',
  `AddrPointSize` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `AddrLineLength` smallint(6) unsigned NOT NULL DEFAULT 0 COMMENT 'Will wrap text lines at this size',
  `AddrPositionX` smallint(6) unsigned NOT NULL DEFAULT 0 COMMENT 'From paper left edge to start of address',
  `AddrPositionY` smallint(6) unsigned NOT NULL DEFAULT 0 COMMENT 'From paper bottom edge to top of address block',
  `NamePointSize` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `NameLineLength` smallint(6) unsigned NOT NULL DEFAULT 0 COMMENT 'Will wrap the name at this size',
  `NameWidth` smallint(6) unsigned NOT NULL DEFAULT 0 COMMENT 'Width of the name box',
  `NamePositionX` smallint(6) unsigned NOT NULL DEFAULT 0 COMMENT 'To the center of the name box',
  `NamePositionY` smallint(6) unsigned NOT NULL DEFAULT 0 COMMENT 'To the top of the name box',
  `RetAddrContent` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Any legal LaTeX commands',
  `NJAddrPointSize` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `NJAddrHeight` smallint(6) unsigned NOT NULL DEFAULT 0 COMMENT 'Long dimension of area for Non-Japan address',
  `NJAddrPositionX` smallint(6) unsigned NOT NULL DEFAULT 0 COMMENT 'From paper left edge to start of address',
  `NJAddrPositionY` smallint(6) unsigned NOT NULL DEFAULT 0 COMMENT 'From paper bottom edge to top of address block',
  `NJRetAddrLeftMargin` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `NJRetAddrTopMargin` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `NJRetAddrContent` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`AddrPrintName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `attendance` (
  `PersonID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `EventID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `AttendDate` date NOT NULL DEFAULT '0000-00-00',
  `StartTime` time NOT NULL DEFAULT '00:00:00' COMMENT 'Only used if event.UseTimes=1',
  `EndTime` time NOT NULL DEFAULT '00:00:00' COMMENT 'Only used if event.UseTimes=1',
  PRIMARY KEY (`PersonID`,`EventID`,`AttendDate`),
  KEY `EventsAttendance` (`EventID`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`PersonID`) REFERENCES `person` (`PersonID`),
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`EventID`) REFERENCES `event` (`EventID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `category` (
  `CategoryID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `Category` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `UseFor` enum('OP','P','O') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'OP' COMMENT 'Whether the category can be used for people, orgs, or both',
  PRIMARY KEY (`CategoryID`),
  KEY `Category` (`Category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `config` (
  `Parameter` varchar(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  `Value` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`Parameter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `custom` (
  `CustomName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `SQL` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `CSS` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `IsTable` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`CustomName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `donation` (
  `DonationID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `PledgeID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `DonationDate` date NOT NULL DEFAULT '0000-00-00',
  `DonationTypeID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `Amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Gifts in kind, foreign currency, etc.',
  `Processed` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`DonationID`),
  KEY `PersonID` (`PersonID`),
  KEY `DonationDate` (`DonationDate`),
  KEY `DonationTypeID` (`DonationTypeID`),
  CONSTRAINT `donation_ibfk_1` FOREIGN KEY (`PersonID`) REFERENCES `person` (`PersonID`),
  CONSTRAINT `donation_ibfk_2` FOREIGN KEY (`DonationTypeID`) REFERENCES `donationtype` (`DonationTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `donationtype` (
  `DonationTypeID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `DonationType` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `BGColor` char(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'FFFFFF',
  PRIMARY KEY (`DonationTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `event` (
  `EventID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `Event` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `EventStartDate` date NOT NULL DEFAULT '0000-00-00',
  `EventEndDate` date NOT NULL DEFAULT '0000-00-00',
  `UseTimes` tinyint(1) NOT NULL DEFAULT 0,
  `Active` tinyint(1) NOT NULL DEFAULT 0,
  `Remarks` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`EventID`),
  KEY `Event` (`Event`),
  KEY `EventStartDate` (`EventStartDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `household` (
  `HouseholdID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `NonJapan` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Address is foreign, so no reference to postal code table',
  `PostalCode` varchar(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  `Address` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `AddressComp` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Copy of CONCAT(PostalCode,Prefecture,ShiKuCho,Address)',
  `RomajiAddress` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `RomajiAddressComp` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Copy of CONCAT(RomajiAddress,space,Romaji,space,PostalCode)',
  `Phone` varchar(20) CHARACTER SET ascii NOT NULL DEFAULT '',
  `FAX` varchar(20) CHARACTER SET ascii NOT NULL DEFAULT '',
  `LabelName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Photo` tinyint(1) NOT NULL DEFAULT 0,
  `PhotoCaption` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `UpdDate` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`HouseholdID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `labelprint` (
  `LabelType` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `PaperSize` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a4' COMMENT 'used in documentclass - values typically "a4" or "letter"',
  `NumRows` tinyint(4) unsigned NOT NULL DEFAULT 0 COMMENT 'Number of labels down the page',
  `NumCols` tinyint(4) unsigned NOT NULL DEFAULT 0 COMMENT 'Number of labels across the page',
  `PageMarginTop` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'From paper top edge to top labels',
  `PageMarginLeft` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'From paper left edge to left-most labels',
  `LabelWidth` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'Width of a whole single label',
  `LabelHeight` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'Height of a whole single label',
  `GutterX` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'Space between labels, if any',
  `GutterY` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'Space between labels, if any',
  `AddrMarginLeft` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'From edge of label to text block',
  `AddrMarginRight` decimal(4,1) unsigned NOT NULL DEFAULT 0.0 COMMENT 'From edge of label to text block (where text will wrap)',
  `AddrPointSize` tinyint(4) unsigned NOT NULL DEFAULT 0 COMMENT 'Font size for Japan addresses',
  `NJAddrPointSize` tinyint(4) unsigned NOT NULL DEFAULT 0 COMMENT 'Font size for non-Japan addresses',
  `NamePointSize` tinyint(4) unsigned NOT NULL DEFAULT 0 COMMENT 'For Japan addresses, name can be larger',
  PRIMARY KEY (`LabelType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `loginlog` (
  `UserID` varchar(16) COLLATE ascii_bin NOT NULL DEFAULT '',
  `LoginTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `IPAddress` char(15) COLLATE ascii_bin NOT NULL DEFAULT '',
  `UserAgent` varchar(255) COLLATE ascii_bin NOT NULL DEFAULT '',
  `Languages` varchar(100) COLLATE ascii_bin NOT NULL DEFAULT '',
  PRIMARY KEY (`UserID`,`LoginTime`),
  KEY `IPAddress` (`IPAddress`),
  KEY `LoginTime` (`LoginTime`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='Log of logins to Contacts DB';


CREATE TABLE `output` (
  `Class` varchar(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  `Header` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `OutputSQL` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`Class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `outputset` (
  `SetName` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ForHousehold` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `OrderNum` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `Class` varchar(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  `CSS` text CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  PRIMARY KEY (`SetName`,`OrderNum`,`ForHousehold`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `percat` (
  `PersonID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `CategoryID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`PersonID`,`CategoryID`),
  KEY `CategoryID` (`CategoryID`),
  CONSTRAINT `percat_ibfk_1` FOREIGN KEY (`PersonID`) REFERENCES `person` (`PersonID`),
  CONSTRAINT `percat_ibfk_2` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;


CREATE TABLE `perorg` (
  `PersonID` mediumint(8) unsigned NOT NULL,
  `OrgID` mediumint(8) unsigned NOT NULL,
  `Leader` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Designates leader/pastor of org',
  PRIMARY KEY (`PersonID`,`OrgID`),
  KEY `OrgID` (`OrgID`),
  CONSTRAINT `perorg_ibfk_1` FOREIGN KEY (`PersonID`) REFERENCES `person` (`PersonID`),
  CONSTRAINT `perorg_ibfk_2` FOREIGN KEY (`OrgID`) REFERENCES `person` (`PersonID`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='Belonging relationship of person to organization';


CREATE TABLE `person` (
  `PersonID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `FullName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Furigana` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Sex` enum('','M','F') CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `HouseholdID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `Relation` varchar(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  `Title` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `CellPhone` varchar(30) CHARACTER SET ascii NOT NULL DEFAULT '',
  `Email` varchar(70) CHARACTER SET ascii NOT NULL DEFAULT '',
  `Birthdate` date NOT NULL DEFAULT '0000-00-00',
  `Country` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `URL` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Organization` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If TRUE, allows record to be an Org in perorg',
  `Remarks` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Photo` tinyint(1) NOT NULL DEFAULT 0,
  `UpdDate` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`PersonID`),
  KEY `Furigana` (`Furigana`),
  KEY `FullName` (`FullName`),
  KEY `Email` (`Email`),
  KEY `Organization` (`Organization`,`Furigana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `photoprint` (
  `PhotoPrintName` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `PaperSizeName` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `PaperHeight` smallint(6) unsigned NOT NULL DEFAULT 0,
  `PaperWidth` smallint(6) unsigned NOT NULL DEFAULT 0,
  `PaperTopMargin` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `PaperBottomMargin` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `PaperLeftMargin` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `PaperRightMargin` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `PhotoHeight` smallint(8) unsigned NOT NULL DEFAULT 0,
  `PhotoWidth` smallint(8) unsigned NOT NULL DEFAULT 0,
  `Gutter` tinyint(4) unsigned NOT NULL DEFAULT 0,
  `Font` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `PointSize` tinyint(4) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`PhotoPrintName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `pledge` (
  `PledgeID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `DonationTypeID` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `StartDate` date NOT NULL DEFAULT '0000-00-00',
  `EndDate` date NOT NULL DEFAULT '0000-00-00',
  `Amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `TimesPerYear` tinyint(3) unsigned NOT NULL DEFAULT 12,
  `PledgeDesc` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`PledgeID`),
  KEY `PersonID` (`PersonID`),
  KEY `DonationTypeID` (`DonationTypeID`),
  CONSTRAINT `pledge_ibfk_1` FOREIGN KEY (`PersonID`) REFERENCES `person` (`PersonID`),
  CONSTRAINT `pledge_ibfk_2` FOREIGN KEY (`DonationTypeID`) REFERENCES `donationtype` (`DonationTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `postalcode` (
  `PostalCode` varchar(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  `Prefecture` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ShiKuCho` varchar(54) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Romaji` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`PostalCode`),
  KEY `Prefecture` (`Prefecture`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `preselect` (
  `PSID` char(13) COLLATE ascii_bin NOT NULL DEFAULT '' COMMENT 'Generated by PHP uniqid(), then passed from page to page',
  `Pids` text COLLATE ascii_bin NOT NULL COMMENT 'Comma-delimited list of PersonIDs',
  `CreateTime` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Used for cleanup',
  PRIMARY KEY (`PSID`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='For storing results sets temporarily';


CREATE TABLE `upload` (
  `UploadID` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `PersonID` mediumint(8) unsigned NOT NULL,
  `UploadTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `FileName` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`UploadID`),
  KEY `PersonID` (`PersonID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `uploadtype` (
  `Extension` varchar(8) COLLATE ascii_bin NOT NULL,
  `MIME` varchar(100) COLLATE ascii_bin NOT NULL,
  `BinaryFile` tinyint(1) NOT NULL DEFAULT 1,
  `InBrowser` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`Extension`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;


CREATE TABLE `user` (
  `UserID` varchar(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
  `Password` char(41) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `UserName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Admin` tinyint(1) NOT NULL DEFAULT 0,
  `Language` varchar(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'ja_JP',
  `HideDonations` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'User-level control for when config table contains "donations=yes"',
  `Dashboard` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Comma-delimited list of filename roots of dashboard modules',
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;