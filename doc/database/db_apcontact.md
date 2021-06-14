Table apcontact
===========
ActivityPub compatible contacts - used in the ActivityPub implementation

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| url | URL of the contact | varbinary(255) | YES | PRI | NULL |  |    
| uuid |  | varchar(255) | NO |  | NULL |  |    
| type |  | varchar(20) | YES |  | NULL |  |    
| following |  | varchar(255) | NO |  | NULL |  |    
| followers |  | varchar(255) | NO |  | NULL |  |    
| inbox |  | varchar(255) | YES |  | NULL |  |    
| outbox |  | varchar(255) | NO |  | NULL |  |    
| sharedinbox |  | varchar(255) | NO |  | NULL |  |    
| manually-approve |  | boolean | NO |  | NULL |  |    
| nick |  | varchar(255) | YES |  |  |  |    
| name |  | varchar(255) | NO |  | NULL |  |    
| about |  | text | NO |  | NULL |  |    
| photo |  | varchar(255) | NO |  | NULL |  |    
| addr |  | varchar(255) | NO |  | NULL |  |    
| alias |  | varchar(255) | NO |  | NULL |  |    
| pubkey |  | text | NO |  | NULL |  |    
| subscribe |  | varchar(255) | NO |  | NULL |  |    
| baseurl | baseurl of the ap contact | varchar(255) | NO |  | NULL |  |    
| gsid | Global Server ID | int unsigned | NO |  | NULL |  |    
| generator | Name of the contact&#039;s system | varchar(255) | NO |  | NULL |  |    
| following_count | Number of following contacts | int unsigned | NO |  | 0 |  |    
| followers_count | Number of followers | int unsigned | NO |  | 0 |  |    
| statuses_count | Number of posts | int unsigned | NO |  | 0 |  |    
| updated |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
