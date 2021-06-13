Table gserver
===========
Global servers

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| url |  | varchar(255) | YES |  |  |  |    
| nurl |  | varchar(255) | YES |  |  |  |    
| version |  | varchar(255) | YES |  |  |  |    
| site_name |  | varchar(255) | YES |  |  |  |    
| info |  | text | NO |  |  |  |    
| register_policy |  | tinyint | YES |  | 0 |  |    
| registered-users | Number of registered users | int unsigned | YES |  | 0 |  |    
| directory-type | Type of directory service (Poco, Mastodon) | tinyint | NO |  | 0 |  |    
| poco |  | varchar(255) | YES |  |  |  |    
| noscrape |  | varchar(255) | YES |  |  |  |    
| network |  | char(4) | YES |  |  |  |    
| protocol | The protocol of the server | tinyint unsigned | NO |  |  |  |    
| platform |  | varchar(255) | YES |  |  |  |    
| relay-subscribe | Has the server subscribed to the relay system | boolean | YES |  | 0 |  |    
| relay-scope | The scope of messages that the server wants to get | varchar(10) | YES |  |  |  |    
| detection-method | Method that had been used to detect that server | tinyint unsigned | NO |  |  |  |    
| created |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| last_poco_query |  | datetime | NO |  | 0001-01-01 00:00:00 |  |    
| last_contact | Last successful connection request | datetime | NO |  | 0001-01-01 00:00:00 |  |    
| last_failure | Last failed connection request | datetime | NO |  | 0001-01-01 00:00:00 |  |    
| failed | Connection failed | boolean | NO |  |  |  |    
| next_contact | Next connection request | datetime | NO |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
