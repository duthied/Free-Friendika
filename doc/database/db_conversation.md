Table conversation
===========
Raw data and structure information for messages

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| item-uri | Original URI of the item - unrelated to the table with the same name | varbinary(255) | YES | PRI |  |  |    
| reply-to-uri | URI to which this item is a reply | varbinary(255) | YES |  |  |  |    
| conversation-uri | GNU Social conversation URI | varbinary(255) | YES |  |  |  |    
| conversation-href | GNU Social conversation link | varbinary(255) | YES |  |  |  |    
| protocol | The protocol of the item | tinyint unsigned | YES |  | 255 |  |    
| direction | How the message arrived here: 1=push, 2=pull | tinyint unsigned | YES |  | 0 |  |    
| source | Original source | mediumtext | NO |  |  |  |    
| received | Receiving date | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
