Table addon
===========
registered addons

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id |  | int unsigned | YES | PRI |  | auto_increment |    
| name | addon base (file)name | varchar(50) | YES |  |  |  |    
| version | currently unused | varchar(50) | YES |  |  |  |    
| installed | currently always 1 | boolean | YES |  | 0 |  |    
| hidden | currently unused | boolean | YES |  | 0 |  |    
| timestamp | file timestamp to check for reloads | int unsigned | YES |  | 0 |  |    
| plugin_admin | 1 = has admin config, 0 = has no admin config | boolean | YES |  | 0 |  |    

Return to [database documentation](help/database)
