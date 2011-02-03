-- MySQL dump 10.11
--
-- Host: localhost		Database: test_admin
-- ------------------------------------------------------
-- Server version	5.0.45-community-nt

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table api_keys
--

DROP TABLE IF EXISTS api_keys;
CREATE TABLE api_keys (
	id int unsigned NOT NULL auto_increment,
	api_key char(24) NOT NULL,
	active tinyint default 1,
	PRIMARY KEY (id),
	UNIQUE KEY (api_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Table structure for table group_meta
--

DROP TABLE IF EXISTS account_meta;
CREATE TABLE account_meta (
	id int unsigned NOT NULL auto_increment,
	fk_id int unsigned NOT NULL,
	meta_key varchar(255) NOT NULL default '',
	meta_value varchar(255) NOT NULL default '',
	PRIMARY KEY (id,fk_id),
	UNIQUE KEY (fk_id,meta_key),
	CONSTRAINT fk_ameta_fk_id FOREIGN KEY (fk_id)
		REFERENCES accounts(id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Table structure for table accounts
--

DROP TABLE IF EXISTS accounts;
CREATE TABLE accounts (
	id int unsigned NOT NULL auto_increment,
	uid int unsigned NOT NULL,
	status	smallint unsigned default '800',
	created_on int unsigned default '0',
	PRIMARY KEY (id),
	CONSTRAINT fk_auid FOREIGN KEY (uid)
		REFERENCES users(id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Table structure for table account_managers
--

DROP TABLE IF EXISTS account_managers;
CREATE TABLE account_managers (
	user_id int unsigned NOT NULL default '0',
	account_id int unsigned NOT NULL default '0',
	PRIMARY KEY (user_id,account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Table structure for table emails
--

DROP TABLE IF EXISTS dumpster;
CREATE TABLE dumpster (
	id int unsigned NOT NULL auto_increment,
	table_name varchar(255) NOT NULL,
	primary_key varchar(255) NOT NULL default 'id',
	key_value varchar(255) NOT NULL,
	added datetime,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


--
-- Table structure for table permissions
--

DROP TABLE IF EXISTS permissions;
CREATE TABLE permissions (
	id smallint unsigned NOT NULL auto_increment,
	code varchar(30) UNIQUE NOT NULL,
	user_type tinyint unsigned NOT NULL default 4,
	description varchar(30) NOT NULL default '',
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Table structure for table user_account_relation
--

DROP TABLE IF EXISTS user_account_relation;
CREATE TABLE user_account_relation (
	user_id int unsigned NOT NULL default '0',
	account_id int unsigned NOT NULL default '0',
	PRIMARY KEY	(user_id,account_id),
	CONSTRAINT fk_uar_uid FOREIGN KEY (user_id)
		REFERENCES users(id)
		ON DELETE CASCADE,
	CONSTRAINT fk_uar_aid FOREIGN KEY (account_id)
		REFERENCES accounts(id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Table structure for table user_permission_relation
--

DROP TABLE IF EXISTS user_permission_relation;
CREATE TABLE user_permission_relation (
	user_id int unsigned NOT NULL,
	permission_id smallint unsigned NOT NULL,
	value smallint unsigned NOT NULL default 0,
	PRIMARY KEY (user_id,permission_id),
	CONSTRAINT fk_upr_uid FOREIGN KEY (user_id)
		REFERENCES users(id)
		ON DELETE CASCADE,
	CONSTRAINT fk_upr_pid FOREIGN KEY (permission_id)
		REFERENCES permissions(id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Table structure for table user_meta
--

DROP TABLE IF EXISTS user_meta;
CREATE TABLE user_meta (
	id int unsigned NOT NULL auto_increment,
	fk_id int unsigned NOT NULL,
	meta_key varchar(255) NOT NULL default '',
	meta_value varchar(255) NOT NULL default '',
	PRIMARY KEY (id,fk_id),
	UNIQUE KEY (fk_id,meta_key),
	CONSTRAINT fk_umeta_fk_id FOREIGN KEY (fk_id)
		REFERENCES users(id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Table structure for table users
--

DROP TABLE IF EXISTS users;
CREATE TABLE users (
	id int unsigned NOT NULL auto_increment,
	username varchar(24) NOT NULL,
	password char(32) NOT NULL,
	email varchar(60) NOT NULL,
	type smallint NOT NULL default '2',
	enabled tinyint NOT NULL default '1',
	added int unsigned default '0',
	last_login int unsigned default '0',
	last_modified int unsigned default '0',
	PRIMARY KEY (id),
	UNIQUE KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2007-10-25 18:44:17