Table notification
===========
notifications

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| uid | Owner User id | mediumint unsigned | NO |  | NULL |  |    
| vid | Id of the verb table entry that contains the activity verbs | smallint unsigned | NO |  | NULL |  |    
| type |  | tinyint unsigned | NO |  | NULL |  |    
| actor-id | Link to the contact table with uid=0 of the actor that caused the notification | int unsigned | NO |  | NULL |  |    
| target-uri-id | Item-uri id of the related post | int unsigned | NO |  | NULL |  |    
| parent-uri-id | Item-uri id of the parent of the related post | int unsigned | NO |  | NULL |  |    
| created |  | datetime | NO |  | NULL |  |    
| seen |  | boolean | NO |  | 0 |  |    

Return to [database documentation](help/database)
