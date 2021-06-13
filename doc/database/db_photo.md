Table photo
===========
photo storage

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| uid | Owner User id | mediumint unsigned | YES |  | 0 |  |    
| contact-id | contact.id | int unsigned | YES |  | 0 |  |    
| guid | A unique identifier for this photo | char(16) | YES |  |  |  |    
| resource-id |  | char(32) | YES |  |  |  |    
| hash | hash value of the photo | char(32) | NO |  |  |  |    
| created | creation date | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| edited | last edited date | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| title |  | varchar(255) | YES |  |  |  |    
| desc |  | text | NO |  |  |  |    
| album | The name of the album to which the photo belongs | varchar(255) | YES |  |  |  |    
| filename |  | varchar(255) | YES |  |  |  |    
| type |  | varchar(30) | YES |  | image/jpeg |  |    
| height |  | smallint unsigned | YES |  | 0 |  |    
| width |  | smallint unsigned | YES |  | 0 |  |    
| datasize |  | int unsigned | YES |  | 0 |  |    
| data |  | mediumblob | YES |  |  |  |    
| scale |  | tinyint unsigned | YES |  | 0 |  |    
| profile |  | boolean | YES |  | 0 |  |    
| allow_cid | Access Control - list of allowed contact.id &#039;&lt;19&gt;&lt;78&gt;&#039; | mediumtext | NO |  |  |  |    
| allow_gid | Access Control - list of allowed groups | mediumtext | NO |  |  |  |    
| deny_cid | Access Control - list of denied contact.id | mediumtext | NO |  |  |  |    
| deny_gid | Access Control - list of denied groups | mediumtext | NO |  |  |  |    
| accessible | Make photo publicly accessible, ignoring permissions | boolean | YES |  | 0 |  |    
| backend-class | Storage backend class | tinytext | NO |  |  |  |    
| backend-ref | Storage backend data reference | text | NO |  |  |  |    
| updated |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
