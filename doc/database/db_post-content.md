Table post-content
===========
Content for all posts

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned | YES | PRI | NULL |  |    
| title | item title | varchar(255) | YES |  |  |  |    
| content-warning |  | varchar(255) | YES |  |  |  |    
| body | item body content | mediumtext | NO |  | NULL |  |    
| raw-body | Body without embedded media links | mediumtext | NO |  | NULL |  |    
| location | text location where this item originated | varchar(255) | YES |  |  |  |    
| coord | longitude/latitude pair representing location where this item originated | varchar(255) | YES |  |  |  |    
| language | Language information about this post | text | NO |  | NULL |  |    
| app | application which generated this item | varchar(255) | YES |  |  |  |    
| rendered-hash |  | varchar(32) | YES |  |  |  |    
| rendered-html | item.body converted to html | mediumtext | NO |  | NULL |  |    
| object-type | ActivityStreams object type | varchar(100) | YES |  |  |  |    
| object | JSON encoded object structure unless it is an implied object (normal post) | text | NO |  | NULL |  |    
| target-type | ActivityStreams target type if applicable (URI) | varchar(100) | YES |  |  |  |    
| target | JSON encoded target structure if used | text | NO |  | NULL |  |    
| resource-id | Used to link other tables to items, it identifies the linked resource (e.g. photo) and if set must also set resource_type | varchar(32) | YES |  |  |  |    
| plink | permalink or URL to a displayable copy of the message at its source | varchar(255) | YES |  |  |  |    

Return to [database documentation](help/database)
