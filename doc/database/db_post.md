Table post
===========
Structure for all posts

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned | YES | PRI | NULL |  |    
| parent-uri-id | Id of the item-uri table that contains the parent uri | int unsigned | NO |  | NULL |  |    
| thr-parent-id | Id of the item-uri table that contains the thread parent uri | int unsigned | NO |  | NULL |  |    
| external-id | Id of the item-uri table entry that contains the external uri | int unsigned | NO |  | NULL |  |    
| created | Creation timestamp. | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| edited | Date of last edit (default is created) | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| received | datetime | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| gravity |  | tinyint unsigned | YES |  | 0 |  |    
| network | Network from where the item comes from | char(4) | YES |  |  |  |    
| owner-id | Link to the contact table with uid=0 of the owner of this item | int unsigned | YES |  | 0 |  |    
| author-id | Link to the contact table with uid=0 of the author of this item | int unsigned | YES |  | 0 |  |    
| causer-id | Link to the contact table with uid=0 of the contact that caused the item creation | int unsigned | NO |  | NULL |  |    
| post-type | Post type (personal note, image, article, ...) | tinyint unsigned | YES |  | 0 |  |    
| vid | Id of the verb table entry that contains the activity verbs | smallint unsigned | NO |  | NULL |  |    
| private | 0=public, 1=private, 2=unlisted | tinyint unsigned | YES |  | 0 |  |    
| global |  | boolean | YES |  | 0 |  |    
| visible |  | boolean | YES |  | 0 |  |    
| deleted | item has been marked for deletion | boolean | YES |  | 0 |  |    

Return to [database documentation](help/database)
