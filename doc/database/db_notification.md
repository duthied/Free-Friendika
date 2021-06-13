Table notification
===========
notifications

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| uid | Owner User id | mediumint unsigned | NO |  |  |  |    
| vid | Id of the verb table entry that contains the activity verbs | smallint unsigned | NO |  |  |  |    
| type |  | tinyint unsigned | NO |  |  |  |    
| actor-id | Link to the contact table with uid=0 of the actor that caused the notification | int unsigned | NO |  |  |  |    
| target-uri-id | Item-uri id of the related post | int unsigned | NO |  |  |  |    
| parent-uri-id | Item-uri id of the parent of the related post | int unsigned | NO |  |  |  |    
| created |  | datetime | NO |  |  |  |    
| seen |  | boolean | NO |  | 0 |  |    

Return to [database documentation](help/database)
