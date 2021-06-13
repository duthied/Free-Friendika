Table contact
===========
contact table

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| id | sequential ID | int unsigned | YES | PRI |  | auto_increment |    
| uid | Owner User id | mediumint unsigned | YES |  | 0 |  |    
| created |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| updated | Date of last contact update | datetime | NO |  | 0001-01-01 00:00:00 |  |    
| self | 1 if the contact is the user him/her self | boolean | YES |  | 0 |  |    
| remote_self |  | boolean | YES |  | 0 |  |    
| rel | The kind of the relation between the user and the contact | tinyint unsigned | YES |  | 0 |  |    
| duplex |  | boolean | YES |  | 0 |  |    
| network | Network of the contact | char(4) | YES |  |  |  |    
| protocol | Protocol of the contact | char(4) | YES |  |  |  |    
| name | Name that this contact is known by | varchar(255) | YES |  |  |  |    
| nick | Nick- and user name of the contact | varchar(255) | YES |  |  |  |    
| location |  | varchar(255) | NO |  |  |  |    
| about |  | text | NO |  |  |  |    
| keywords | public keywords (interests) of the contact | text | NO |  |  |  |    
| gender | Deprecated | varchar(32) | YES |  |  |  |    
| xmpp |  | varchar(255) | YES |  |  |  |    
| attag |  | varchar(255) | YES |  |  |  |    
| avatar |  | varchar(255) | YES |  |  |  |    
| photo | Link to the profile photo of the contact | varchar(255) | NO |  |  |  |    
| thumb | Link to the profile photo (thumb size) | varchar(255) | NO |  |  |  |    
| micro | Link to the profile photo (micro size) | varchar(255) | NO |  |  |  |    
| site-pubkey |  | text | NO |  |  |  |    
| issued-id |  | varchar(255) | YES |  |  |  |    
| dfrn-id |  | varchar(255) | YES |  |  |  |    
| url |  | varchar(255) | YES |  |  |  |    
| nurl |  | varchar(255) | YES |  |  |  |    
| addr |  | varchar(255) | YES |  |  |  |    
| alias |  | varchar(255) | YES |  |  |  |    
| pubkey | RSA public key 4096 bit | text | NO |  |  |  |    
| prvkey | RSA private key 4096 bit | text | NO |  |  |  |    
| batch |  | varchar(255) | YES |  |  |  |    
| request |  | varchar(255) | NO |  |  |  |    
| notify |  | varchar(255) | NO |  |  |  |    
| poll |  | varchar(255) | NO |  |  |  |    
| confirm |  | varchar(255) | NO |  |  |  |    
| subscribe |  | varchar(255) | NO |  |  |  |    
| poco |  | varchar(255) | NO |  |  |  |    
| aes_allow |  | boolean | YES |  | 0 |  |    
| ret-aes |  | boolean | YES |  | 0 |  |    
| usehub |  | boolean | YES |  | 0 |  |    
| subhub |  | boolean | YES |  | 0 |  |    
| hub-verify |  | varchar(255) | YES |  |  |  |    
| last-update | Date of the last try to update the contact info | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| success_update | Date of the last successful contact update | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| failure_update | Date of the last failed update | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| failed | Connection failed | boolean | NO |  |  |  |    
| name-date |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| uri-date |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| avatar-date |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| term-date |  | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| last-item | date of the last post | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| last-discovery | date of the last follower discovery | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| priority |  | tinyint unsigned | YES |  | 0 |  |    
| blocked | Node-wide block status | boolean | YES |  | 1 |  |    
| block_reason | Node-wide block reason | text | NO |  |  |  |    
| readonly | posts of the contact are readonly | boolean | YES |  | 0 |  |    
| writable |  | boolean | YES |  | 0 |  |    
| forum | contact is a forum | boolean | YES |  | 0 |  |    
| prv | contact is a private group | boolean | YES |  | 0 |  |    
| contact-type |  | tinyint | YES |  | 0 |  |    
| manually-approve |  | boolean | NO |  |  |  |    
| hidden |  | boolean | YES |  | 0 |  |    
| archive |  | boolean | YES |  | 0 |  |    
| pending |  | boolean | YES |  | 1 |  |    
| deleted | Contact has been deleted | boolean | YES |  | 0 |  |    
| rating |  | tinyint | YES |  | 0 |  |    
| unsearchable | Contact prefers to not be searchable | boolean | YES |  | 0 |  |    
| sensitive | Contact posts sensitive content | boolean | YES |  | 0 |  |    
| baseurl | baseurl of the contact | varchar(255) | NO |  |  |  |    
| gsid | Global Server ID | int unsigned | NO |  |  |  |    
| reason |  | text | NO |  |  |  |    
| closeness |  | tinyint unsigned | YES |  | 99 |  |    
| info |  | mediumtext | NO |  |  |  |    
| profile-id | Deprecated | int unsigned | NO |  |  |  |    
| bdyear |  | varchar(4) | YES |  |  |  |    
| bd |  | date | YES |  | 0001-01-01 |  |    
| notify_new_posts |  | boolean | YES |  | 0 |  |    
| fetch_further_information |  | tinyint unsigned | YES |  | 0 |  |    
| ffi_keyword_denylist |  | text | NO |  |  |  |    

Return to [database documentation](help/database)
