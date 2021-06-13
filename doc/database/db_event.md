Table event
===========
Events

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| guid |  | varchar(255) | YES |  |  |  |    
| uid | Owner User id | mediumint unsigned | YES |  | 0 |  |    
| cid | contact_id (ID of the contact in contact table) | int unsigned | YES |  | 0 |  |    
| uri |  | varchar(255) | YES |  |  |  |    
| created | creation time | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| edited | last edit time | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| start | event start time | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| finish | event end time | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| summary | short description or title of the event | text | NO |  |  |  |    
| desc | event description | text | NO |  |  |  |    
| location | event location | text | NO |  |  |  |    
| type | event or birthday | varchar(20) | YES |  |  |  |    
| nofinish | if event does have no end this is 1 | boolean | YES |  | 0 |  |    
| adjust | adjust to timezone of the recipient (0 or 1) | boolean | YES |  | 1 |  |    
| ignore | 0 or 1 | boolean | YES |  | 0 |  |    
| allow_cid | Access Control - list of allowed contact.id &#039;&lt;19&gt;&lt;78&gt;&#039; | mediumtext | NO |  |  |  |    
| allow_gid | Access Control - list of allowed groups | mediumtext | NO |  |  |  |    
| deny_cid | Access Control - list of denied contact.id | mediumtext | NO |  |  |  |    
| deny_gid | Access Control - list of denied groups | mediumtext | NO |  |  |  |    

Return to [database documentation](help/database)
