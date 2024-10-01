ALTER TABLE `properties` ADD `main_type` VARCHAR(15) NOT NULL DEFAULT 'agentbox' AFTER `id`;
ALTER TABLE `properties` ADD `street_address` VARCHAR(50) NULL AFTER `address`, ADD `suburb` VARCHAR(50) NULL AFTER `street_address`;
ALTER TABLE `properties` ADD `state` VARCHAR(50) NULL AFTER `suburb`, ADD `postcode` INT NULL AFTER `state`;