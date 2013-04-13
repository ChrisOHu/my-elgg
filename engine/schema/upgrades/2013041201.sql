-- add table river_peruser
CREATE TABLE IF NOT EXISTS `prefix_river_peruser` (
	`user_guid` bigint(20) unsigned NOT NULL,
        `river_item_id` int(11) NOT NULL,
	`isCreater` int(1) NOT NULL DEFAULT '0',
	KEY `user_guid` (`user_guid`),
	KEY `river_item_id` (`river_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `prefix_entroriver` ADD `ref_count` INT(4) NOT NULL DEFAULT `1` AFTER `posted`
		
