Table hook
===========
addon hook registry

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| hook | name of hook | varbinary(100) | YES |  |  |  |    
| file | relative filename of hook handler | varbinary(200) | YES |  |  |  |    
| function | function name of hook handler | varbinary(200) | YES |  |  |  |    
| priority | not yet implemented - can be used to sort conflicts in hook handling by calling handlers in priority order | smallint unsigned | YES |  | 0 |  |    

Return to [database documentation](help/database)
