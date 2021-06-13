Table profile
===========
user profiles data

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| uid | Owner User id | mediumint unsigned | YES |  | 0 |  |    
| profile-name | Deprecated | varchar(255) | NO |  |  |  |    
| is-default | Deprecated | boolean | NO |  |  |  |    
| hide-friends | Hide friend list from viewers of this profile | boolean | YES |  | 0 |  |    
| name |  | varchar(255) | YES |  |  |  |    
| pdesc | Deprecated | varchar(255) | NO |  |  |  |    
| dob | Day of birth | varchar(32) | YES |  | 0000-00-00 |  |    
| address |  | varchar(255) | YES |  |  |  |    
| locality |  | varchar(255) | YES |  |  |  |    
| region |  | varchar(255) | YES |  |  |  |    
| postal-code |  | varchar(32) | YES |  |  |  |    
| country-name |  | varchar(255) | YES |  |  |  |    
| hometown | Deprecated | varchar(255) | NO |  |  |  |    
| gender | Deprecated | varchar(32) | NO |  |  |  |    
| marital | Deprecated | varchar(255) | NO |  |  |  |    
| with | Deprecated | text | NO |  |  |  |    
| howlong | Deprecated | datetime | NO |  |  |  |    
| sexual | Deprecated | varchar(255) | NO |  |  |  |    
| politic | Deprecated | varchar(255) | NO |  |  |  |    
| religion | Deprecated | varchar(255) | NO |  |  |  |    
| pub_keywords |  | text | NO |  |  |  |    
| prv_keywords |  | text | NO |  |  |  |    
| likes | Deprecated | text | NO |  |  |  |    
| dislikes | Deprecated | text | NO |  |  |  |    
| about | Profile description | text | NO |  |  |  |    
| summary | Deprecated | varchar(255) | NO |  |  |  |    
| music | Deprecated | text | NO |  |  |  |    
| book | Deprecated | text | NO |  |  |  |    
| tv | Deprecated | text | NO |  |  |  |    
| film | Deprecated | text | NO |  |  |  |    
| interest | Deprecated | text | NO |  |  |  |    
| romance | Deprecated | text | NO |  |  |  |    
| work | Deprecated | text | NO |  |  |  |    
| education | Deprecated | text | NO |  |  |  |    
| contact | Deprecated | text | NO |  |  |  |    
| homepage |  | varchar(255) | YES |  |  |  |    
| xmpp |  | varchar(255) | YES |  |  |  |    
| photo |  | varchar(255) | YES |  |  |  |    
| thumb |  | varchar(255) | YES |  |  |  |    
| publish | publish default profile in local directory | boolean | YES |  | 0 |  |    
| net-publish | publish profile in global directory | boolean | YES |  | 0 |  |    

Return to [database documentation](help/database)
