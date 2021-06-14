Table post-user
===========
User specific post data

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id |  | int unsigned | YES | PRI | NULL | auto_increment |    
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned | YES |  | NULL |  |    
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
| post-reason | Reason why the post arrived at the user | tinyint unsigned | YES |  | 0 |  |    
| vid | Id of the verb table entry that contains the activity verbs | smallint unsigned | NO |  | NULL |  |    
| private | 0=public, 1=private, 2=unlisted | tinyint unsigned | YES |  | 0 |  |    
| global |  | boolean | YES |  | 0 |  |    
| visible |  | boolean | YES |  | 0 |  |    
| deleted | item has been marked for deletion | boolean | YES |  | 0 |  |    
| uid | Owner id which owns this copy of the item | mediumint unsigned | YES |  | NULL |  |    
| protocol | Protocol used to deliver the item for this user | tinyint unsigned | NO |  | NULL |  |    
| contact-id | contact.id | int unsigned | YES |  | 0 |  |    
| event-id | Used to link to the event.id | int unsigned | NO |  | NULL |  |    
| unseen | post has not been seen | boolean | YES |  | 1 |  |    
| hidden | Marker to hide the post from the user | boolean | YES |  | 0 |  |    
| notification-type |  | tinyint unsigned | YES |  | 0 |  |    
| wall | This item was posted to the wall of uid | boolean | YES |  | 0 |  |    
| origin | item originated at this site | boolean | YES |  | 0 |  |    
| psid | ID of the permission set of this post | int unsigned | NO |  | NULL |  |    

Return to [database documentation](help/database)
