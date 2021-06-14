Table post-thread-user
===========
Thread related data per user

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| uri-id       | Id of the item-uri table entry that contains the item uri                                               | int unsigned       | NO  | PRI | NULL                |  |    
| owner-id     | Item owner                                                                                              | int unsigned       | NO  |     | 0                   |  |    
| author-id    | Item author                                                                                             | int unsigned       | NO  |     | 0                   |  |    
| causer-id    | Link to the contact table with uid=0 of the contact that caused the item creation                       | int unsigned       | YES |     | NULL                |  |    
| network      |                                                                                                         | char(4)            | NO  |     |                     |  |    
| created      |                                                                                                         | datetime           | NO  |     | 0001-01-01 00:00:00 |  |    
| received     |                                                                                                         | datetime           | NO  |     | 0001-01-01 00:00:00 |  |    
| changed      | Date that something in the conversation changed, indicating clients should fetch the conversation again | datetime           | NO  |     | 0001-01-01 00:00:00 |  |    
| commented    |                                                                                                         | datetime           | NO  |     | 0001-01-01 00:00:00 |  |    
| uid          | Owner id which owns this copy of the item                                                               | mediumint unsigned | NO  | PRI | 0                   |  |    
| pinned       | The thread is pinned on the profile page                                                                | boolean            | NO  |     | 0                   |  |    
| starred      |                                                                                                         | boolean            | NO  |     | 0                   |  |    
| ignored      | Ignore updates for this thread                                                                          | boolean            | NO  |     | 0                   |  |    
| wall         | This item was posted to the wall of uid                                                                 | boolean            | NO  |     | 0                   |  |    
| mention      |                                                                                                         | boolean            | NO  |     | 0                   |  |    
| pubmail      |                                                                                                         | boolean            | NO  |     | 0                   |  |    
| forum_mode   |                                                                                                         | tinyint unsigned   | NO  |     | 0                   |  |    
| contact-id   | contact.id                                                                                              | int unsigned       | NO  |     | 0                   |  |    
| unseen       | post has not been seen                                                                                  | boolean            | NO  |     | 1                   |  |    
| hidden       | Marker to hide the post from the user                                                                   | boolean            | NO  |     | 0                   |  |    
| origin       | item originated at this site                                                                            | boolean            | NO  |     | 0                   |  |    
| psid         | ID of the permission set of this post                                                                   | int unsigned       | YES |     | NULL                |  |    
| post-user-id | Id of the post-user table                                                                               | int unsigned       | YES |     | NULL                |  |    

Return to [database documentation](help/database)
