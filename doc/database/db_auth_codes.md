Table auth_codes
===========
OAuth usage

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id |  | varchar(40) | YES | PRI | NULL |  |    
| client_id |  | varchar(20) | YES |  |  |  |    
| redirect_uri |  | varchar(200) | YES |  |  |  |    
| expires |  | int | YES |  | 0 |  |    
| scope |  | varchar(250) | YES |  |  |  |    

Return to [database documentation](help/database)
