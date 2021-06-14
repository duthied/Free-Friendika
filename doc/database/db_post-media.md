Table post-media
===========
Attached media

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| uri-id | Id of the item-uri table entry that contains the item uri | int unsigned | YES |  | NULL |  |    
| url | Media URL | varbinary(511) | YES |  | NULL |  |    
| type | Media type | tinyint unsigned | YES |  | 0 |  |    
| mimetype |  | varchar(60) | NO |  | NULL |  |    
| height | Height of the media | smallint unsigned | NO |  | NULL |  |    
| width | Width of the media | smallint unsigned | NO |  | NULL |  |    
| size | Media size | int unsigned | NO |  | NULL |  |    
| preview | Preview URL | varbinary(255) | NO |  | NULL |  |    
| preview-height | Height of the preview picture | smallint unsigned | NO |  | NULL |  |    
| preview-width | Width of the preview picture | smallint unsigned | NO |  | NULL |  |    
| description |  | text | NO |  | NULL |  |    
| name | Name of the media | varchar(255) | NO |  | NULL |  |    
| author-url | URL of the author of the media | varbinary(255) | NO |  | NULL |  |    
| author-name | Name of the author of the media | varchar(255) | NO |  | NULL |  |    
| author-image | Image of the author of the media | varbinary(255) | NO |  | NULL |  |    
| publisher-url | URL of the publisher of the media | varbinary(255) | NO |  | NULL |  |    
| publisher-name | Name of the publisher of the media | varchar(255) | NO |  | NULL |  |    
| publisher-image | Image of the publisher of the media | varbinary(255) | NO |  | NULL |  |    

Return to [database documentation](help/database)
