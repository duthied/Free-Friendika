
SELECT CONCAT('ALTER TABLE ',table_schema,'.',table_name,' engine=InnoDB;') 
FROM information_schema.tables 
WHERE engine = 'MyISAM' AND  `table_schema` = 'friendica';
