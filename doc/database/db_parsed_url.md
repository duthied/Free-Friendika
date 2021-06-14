Table parsed_url
===========
cache for &#039;parse_url&#039; queries

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| url_hash | page url hash | binary(64) | YES | PRI | NULL |  |    
| guessing | is the &#039;guessing&#039; mode active? | boolean | YES | PRI | 0 |  |    
| oembed | is the data the result of oembed? | boolean | YES | PRI | 0 |  |    
| url | page url | text | YES |  | NULL |  |    
| content | page data | mediumtext | NO |  | NULL |  |    
| created | datetime of creation | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| expires | datetime of expiration | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
