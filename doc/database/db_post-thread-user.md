Table post-thread-user
===========
Thread related data per user

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned | YES | PRI | NULL |  |    
| owner-id | Item owner | int unsigned | YES |  | 0 |  |    
| author-id | Item author | int unsigned | YES |  | 0 |  |    
| causer-id | Link to the contact table with uid=0 of the contact that caused the item creation | int unsigned | NO |  | NULL |  |    
| network |  | char(4) | YES |  |  |  |    
| created |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| received |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| changed | Date that something in the conversation changed, indicating clients should fetch the conversation again | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| commented |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| uid | Owner id which owns this copy of the item | mediumint unsigned | YES | PRI | 0 |  |    
| pinned | The thread is pinned on the profile page | boolean | YES |  | 0 |  |    
| starred |  | boolean | YES |  | 0 |  |    
| ignored | Ignore updates for this thread | boolean | YES |  | 0 |  |    
| wall | This item was posted to the wall of uid | boolean | YES |  | 0 |  |    
| mention |  | boolean | YES |  | 0 |  |    
| pubmail |  | boolean | YES |  | 0 |  |    
| forum_mode |  | tinyint unsigned | YES |  | 0 |  |    
| contact-id | contact.id | int unsigned | YES |  | 0 |  |    
| unseen | post has not been seen | boolean | YES |  | 1 |  |    
| hidden | Marker to hide the post from the user | boolean | YES |  | 0 |  |    
| origin | item originated at this site | boolean | YES |  | 0 |  |    
| psid | ID of the permission set of this post | int unsigned | NO |  | NULL |  |    
| post-user-id | Id of the post-user table | int unsigned | NO |  | NULL |  |    

Return to [database documentation](help/database)
