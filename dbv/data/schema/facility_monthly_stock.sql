CREATE TABLE `facility_monthly_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commodity_id` int(11) NOT NULL,
  `facility_code` int(11) NOT NULL,
  `consumption_level` int(11) NOT NULL,
  `selected_option` varchar(50) NOT NULL,
  `total_units` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_facility_monthly_stock_commodity_id` (`commodity_id`),
  KEY `idx_facility_monthly_stock_facility_code` (`facility_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1