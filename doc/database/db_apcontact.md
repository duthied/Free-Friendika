Table apcontact
===========
ActivityPub compatible contacts - used in the ActivityPub implementation

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| url | URL of the contact | varbinary(255) | YES | PRI |  |  |    
| uuid |  | varchar(255) | NO |  |  |  |    
| type |  | varchar(20) | YES |  |  |  |    
| following |  | varchar(255) | NO |  |  |  |    
| followers |  | varchar(255) | NO |  |  |  |    
| inbox |  | varchar(255) | YES |  |  |  |    
| outbox |  | varchar(255) | NO |  |  |  |    
| sharedinbox |  | varchar(255) | NO |  |  |  |    
| manually-approve |  | boolean | NO |  |  |  |    
| nick |  | varchar(255) | YES |  |  |  |    
| name |  | varchar(255) | NO |  |  |  |    
| about |  | text | NO |  |  |  |    
| photo |  | varchar(255) | NO |  |  |  |    
| addr |  | varchar(255) | NO |  |  |  |    
| alias |  | varchar(255) | NO |  |  |  |    
| pubkey |  | text | NO |  |  |  |    
| subscribe |  | varchar(255) | NO |  |  |  |    
| baseurl | baseurl of the ap contact | varchar(255) | NO |  |  |  |    
| gsid | Global Server ID | int unsigned | NO |  |  |  |    
| generator | Name of the contact&#039;s system | varchar(255) | NO |  |  |  |    
| following_count | Number of following contacts | int unsigned | NO |  | 0 |  |    
| followers_count | Number of followers | int unsigned | NO |  | 0 |  |    
| statuses_count | Number of posts | int unsigned | NO |  | 0 |  |    
| updated |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    

Return to [database documentation](help/database)
