Table attach
===========
file attachments

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | generated index | int unsigned | YES | PRI | NULL | auto_increment |    
| uid | Owner User id | mediumint unsigned | YES |  | 0 |  |    
| hash | hash | varchar(64) | YES |  |  |  |    
| filename | filename of original | varchar(255) | YES |  |  |  |    
| filetype | mimetype | varchar(64) | YES |  |  |  |    
| filesize | size in bytes | int unsigned | YES |  | 0 |  |    
| data | file data | longblob | YES |  | NULL |  |    
| created | creation time | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| edited | last edit time | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| allow_cid | Access Control - list of allowed contact.id &#039;&lt;19&gt;&lt;78&gt; | mediumtext | NO |  | NULL |  |    
| allow_gid | Access Control - list of allowed groups | mediumtext | NO |  | NULL |  |    
| deny_cid | Access Control - list of denied contact.id | mediumtext | NO |  | NULL |  |    
| deny_gid | Access Control - list of denied groups | mediumtext | NO |  | NULL |  |    
| backend-class | Storage backend class | tinytext | NO |  | NULL |  |    
| backend-ref | Storage backend data reference | text | NO |  | NULL |  |    

Return to [database documentation](help/database)
