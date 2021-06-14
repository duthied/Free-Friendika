Table session
===========
web session storage

| Field  | Description   | Type            | Null | Key | Default | Extra          |    
| ------ | ------------- | --------------- | ---- | --- | ------- | -------------- |    
| id     | sequential ID | bigint unsigned | NO   | PRI | NULL    | auto_increment |    
| sid    |               | varbinary(255)  | NO   |     |         |                |    
| data   |               | text            | YES  |     | NULL    |                |    
| expire |               | int unsigned    | NO   |     | 0       |                |    

Return to [database documentation](help/database)
