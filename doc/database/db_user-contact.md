Table user-contact
===========
User specific public contact data

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| cid | Contact id of the linked public contact | int unsigned | YES | PRI | 0 |  |    
| uid | User id | mediumint unsigned | YES | PRI | 0 |  |    
| blocked | Contact is completely blocked for this user | boolean | NO |  | NULL |  |    
| ignored | Posts from this contact are ignored | boolean | NO |  | NULL |  |    
| collapsed | Posts from this contact are collapsed | boolean | NO |  | NULL |  |    

Return to [database documentation](help/database)
