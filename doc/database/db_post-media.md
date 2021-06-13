Table post-media
===========
Attached media

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned | YES |  |  |  |    
| url | Media URL | varbinary(511) | YES |  |  |  |    
| type | Media type | tinyint unsigned | YES |  | 0 |  |    
| mimetype |  | varchar(60) | NO |  |  |  |    
| height | Height of the media | smallint unsigned | NO |  |  |  |    
| width | Width of the media | smallint unsigned | NO |  |  |  |    
| size | Media size | int unsigned | NO |  |  |  |    
| preview | Preview URL | varbinary(255) | NO |  |  |  |    
| preview-height | Height of the preview picture | smallint unsigned | NO |  |  |  |    
| preview-width | Width of the preview picture | smallint unsigned | NO |  |  |  |    
| description |  | text | NO |  |  |  |    
| name | Name of the media | varchar(255) | NO |  |  |  |    
| author-url | URL of the author of the media | varbinary(255) | NO |  |  |  |    
| author-name | Name of the author of the media | varchar(255) | NO |  |  |  |    
| author-image | Image of the author of the media | varbinary(255) | NO |  |  |  |    
| publisher-url | URL of the publisher of the media | varbinary(255) | NO |  |  |  |    
| publisher-name | Name of the publisher of the media | varchar(255) | NO |  |  |  |    
| publisher-image | Image of the publisher of the media | varbinary(255) | NO |  |  |  |    

Return to [database documentation](help/database)
