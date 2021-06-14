Table profile
===========
user profiles data

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI | NULL | auto_increment |    
| uid | Owner User id | mediumint unsigned | YES |  | 0 |  |    
| profile-name | Deprecated | varchar(255) | NO |  | NULL |  |    
| is-default | Deprecated | boolean | NO |  | NULL |  |    
| hide-friends | Hide friend list from viewers of this profile | boolean | YES |  | 0 |  |    
| name |  | varchar(255) | YES |  |  |  |    
| pdesc | Deprecated | varchar(255) | NO |  | NULL |  |    
| dob | Day of birth | varchar(32) | YES |  | 0000-00-00 |  |    
| address |  | varchar(255) | YES |  |  |  |    
| locality |  | varchar(255) | YES |  |  |  |    
| region |  | varchar(255) | YES |  |  |  |    
| postal-code |  | varchar(32) | YES |  |  |  |    
| country-name |  | varchar(255) | YES |  |  |  |    
| hometown | Deprecated | varchar(255) | NO |  | NULL |  |    
| gender | Deprecated | varchar(32) | NO |  | NULL |  |    
| marital | Deprecated | varchar(255) | NO |  | NULL |  |    
| with | Deprecated | text | NO |  | NULL |  |    
| howlong | Deprecated | datetime | NO |  | NULL |  |    
| sexual | Deprecated | varchar(255) | NO |  | NULL |  |    
| politic | Deprecated | varchar(255) | NO |  | NULL |  |    
| religion | Deprecated | varchar(255) | NO |  | NULL |  |    
| pub_keywords |  | text | NO |  | NULL |  |    
| prv_keywords |  | text | NO |  | NULL |  |    
| likes | Deprecated | text | NO |  | NULL |  |    
| dislikes | Deprecated | text | NO |  | NULL |  |    
| about | Profile description | text | NO |  | NULL |  |    
| summary | Deprecated | varchar(255) | NO |  | NULL |  |    
| music | Deprecated | text | NO |  | NULL |  |    
| book | Deprecated | text | NO |  | NULL |  |    
| tv | Deprecated | text | NO |  | NULL |  |    
| film | Deprecated | text | NO |  | NULL |  |    
| interest | Deprecated | text | NO |  | NULL |  |    
| romance | Deprecated | text | NO |  | NULL |  |    
| work | Deprecated | text | NO |  | NULL |  |    
| education | Deprecated | text | NO |  | NULL |  |    
| contact | Deprecated | text | NO |  | NULL |  |    
| homepage |  | varchar(255) | YES |  |  |  |    
| xmpp |  | varchar(255) | YES |  |  |  |    
| photo |  | varchar(255) | YES |  |  |  |    
| thumb |  | varchar(255) | YES |  |  |  |    
| publish | publish default profile in local directory | boolean | YES |  | 0 |  |    
| net-publish | publish profile in global directory | boolean | YES |  | 0 |  |    

Return to [database documentation](help/database)
