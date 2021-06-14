Table oembed
===========
cache for OEmbed queries

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| url | page url | varbinary(255) | YES | PRI | NULL |  |    
| maxwidth | Maximum width passed to Oembed | mediumint unsigned | YES | PRI | NULL |  |    
| content | OEmbed data of the page | mediumtext | NO |  | NULL |  |    
| created | datetime of creation | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
