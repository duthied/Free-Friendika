Table cache
===========
Stores temporary data

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| k | cache key | varbinary(255) | NO | PRI | NULL |  |    
| v | cached serialized value | mediumtext | YES |  | NULL |  |    
| expires | datetime of cache expiration | datetime | NO |  | 0001-01-01 00:00:00 |  |    
| updated | datetime of cache insertion | datetime | NO |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
