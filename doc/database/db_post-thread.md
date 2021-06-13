Table post-thread
===========
Thread related data

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned | YES | PRI |  |  |    
| owner-id | Item owner | int unsigned | YES |  | 0 |  |    
| author-id | Item author | int unsigned | YES |  | 0 |  |    
| causer-id | Link to the contact table with uid=0 of the contact that caused the item creation | int unsigned | NO |  |  |  |    
| network |  | char(4) | YES |  |  |  |    
| created |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| received |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| changed | Date that something in the conversation changed, indicating clients should fetch the conversation again | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| commented |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
