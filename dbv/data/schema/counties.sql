CREATE TABLE `counties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `county` varchar(30) NOT NULL,
  `kenya_map_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1