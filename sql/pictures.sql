CREATE TABLE `pictures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) COLLATE utf8_bin NOT NULL,
  `body` mediumblob NOT NULL,
  `created_at` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  `updated_at` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
