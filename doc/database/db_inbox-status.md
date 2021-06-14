Table inbox-status
===========
Status of ActivityPub inboxes

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| url | URL of the inbox | varbinary(255) | YES | PRI | NULL |  |    
| created | Creation date of this entry | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| success | Date of the last successful delivery | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| failure | Date of the last failed delivery | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| previous | Previous delivery date | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| archive | Is the inbox archived? | boolean | YES |  | 0 |  |    
| shared | Is it a shared inbox? | boolean | YES |  | 0 |  |    

Return to [database documentation](help/database)
