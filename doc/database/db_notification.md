Table notification
===========
notifications

| Field         | Description                                                                    | Type               | Null | Key | Default | Extra          |    
| ------------- | ------------------------------------------------------------------------------ | ------------------ | ---- | --- | ------- | -------------- |    
| id            | sequential ID                                                                  | int unsigned       | NO   | PRI | NULL    | auto_increment |    
| uid           | Owner User id                                                                  | mediumint unsigned | YES  |     | NULL    |                |    
| vid           | Id of the verb table entry that contains the activity verbs                    | smallint unsigned  | YES  |     | NULL    |                |    
| type          |                                                                                | tinyint unsigned   | YES  |     | NULL    |                |    
| actor-id      | Link to the contact table with uid=0 of the actor that caused the notification | int unsigned       | YES  |     | NULL    |                |    
| target-uri-id | Item-uri id of the related post                                                | int unsigned       | YES  |     | NULL    |                |    
| parent-uri-id | Item-uri id of the parent of the related post                                  | int unsigned       | YES  |     | NULL    |                |    
| created       |                                                                                | datetime           | YES  |     | NULL    |                |    
| seen          |                                                                                | boolean            | YES  |     | 0       |                |    

Return to [database documentation](help/database)
