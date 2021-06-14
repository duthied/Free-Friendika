Table user
===========
The local users

| Field | Description | Type | Null | Key | Default | Extra |
| ----- | ----------- | ---- | ---- | --- | ------- | ----- |
| uid | sequential ID | mediumint unsigned | YES | PRI | NULL | auto_increment |    
| parent-uid | The parent user that has full control about this user | mediumint unsigned | NO |  | NULL |  |    
| guid | A unique identifier for this user | varchar(64) | YES |  |  |  |    
| username | Name that this user is known by | varchar(255) | YES |  |  |  |    
| password | encrypted password | varchar(255) | YES |  |  |  |    
| legacy_password | Is the password hash double-hashed? | boolean | YES |  | 0 |  |    
| nickname | nick- and user name | varchar(255) | YES |  |  |  |    
| email | the users email address | varchar(255) | YES |  |  |  |    
| openid |  | varchar(255) | YES |  |  |  |    
| timezone | PHP-legal timezone | varchar(128) | YES |  |  |  |    
| language | default language | varchar(32) | YES |  | en |  |    
| register_date | timestamp of registration | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| login_date | timestamp of last login | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| default-location | Default for item.location | varchar(255) | YES |  |  |  |    
| allow_location | 1 allows to display the location | boolean | YES |  | 0 |  |    
| theme | user theme preference | varchar(255) | YES |  |  |  |    
| pubkey | RSA public key 4096 bit | text | NO |  | NULL |  |    
| prvkey | RSA private key 4096 bit | text | NO |  | NULL |  |    
| spubkey |  | text | NO |  | NULL |  |    
| sprvkey |  | text | NO |  | NULL |  |    
| verified | user is verified through email | boolean | YES |  | 0 |  |    
| blocked | 1 for user is blocked | boolean | YES |  | 0 |  |    
| blockwall | Prohibit contacts to post to the profile page of the user | boolean | YES |  | 0 |  |    
| hidewall | Hide profile details from unkown viewers | boolean | YES |  | 0 |  |    
| blocktags | Prohibit contacts to tag the post of this user | boolean | YES |  | 0 |  |    
| unkmail | Permit unknown people to send private mails to this user | boolean | YES |  | 0 |  |    
| cntunkmail |  | int unsigned | YES |  | 10 |  |    
| notify-flags | email notification options | smallint unsigned | YES |  | 65535 |  |    
| page-flags | page/profile type | tinyint unsigned | YES |  | 0 |  |    
| account-type |  | tinyint unsigned | YES |  | 0 |  |    
| prvnets |  | boolean | YES |  | 0 |  |    
| pwdreset | Password reset request token | varchar(255) | NO |  | NULL |  |    
| pwdreset_time | Timestamp of the last password reset request | datetime | NO |  | NULL |  |    
| maxreq |  | int unsigned | YES |  | 10 |  |    
| expire |  | int unsigned | YES |  | 0 |  |    
| account_removed | if 1 the account is removed | boolean | YES |  | 0 |  |    
| account_expired |  | boolean | YES |  | 0 |  |    
| account_expires_on | timestamp when account expires and will be deleted | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| expire_notification_sent | timestamp of last warning of account expiration | datetime | YES |  | 0001-01-01 00:00:00 |  |    
| def_gid |  | int unsigned | YES |  | 0 |  |    
| allow_cid | default permission for this user | mediumtext | NO |  | NULL |  |    
| allow_gid | default permission for this user | mediumtext | NO |  | NULL |  |    
| deny_cid | default permission for this user | mediumtext | NO |  | NULL |  |    
| deny_gid | default permission for this user | mediumtext | NO |  | NULL |  |    
| openidserver |  | text | NO |  | NULL |  |    

Return to [database documentation](help/database)
