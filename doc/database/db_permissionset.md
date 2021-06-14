Table permissionset
===========


| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| uid | Owner id of this permission set | mediumint unsigned | YES |  | 0 |  |    
| allow_cid | Access Control - list of allowed contact.id &#039;&lt;19&gt;&lt;78&gt;&#039; | mediumtext | NO |  | NULL |  |    
| allow_gid | Access Control - list of allowed groups | mediumtext | NO |  | NULL |  |    
| deny_cid | Access Control - list of denied contact.id | mediumtext | NO |  | NULL |  |    
| deny_gid | Access Control - list of denied groups | mediumtext | NO |  | NULL |  |    

Return to [database documentation](help/database)
