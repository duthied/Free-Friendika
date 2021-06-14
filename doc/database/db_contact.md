Table contact
===========

contact table

Fields
------

| Field                     | Description                                               | Type               | Null | Key | Default             | Extra          |
| ------------------------- | --------------------------------------------------------- | ------------------ | ---- | --- | ------------------- | -------------- |
| id                        | sequential ID                                             | int unsigned       | NO   | PRI | NULL                | auto_increment |
| uid                       | Owner User id                                             | mediumint unsigned | NO   |     | 0                   |                |
| created                   |                                                           | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| updated                   | Date of last contact update                               | datetime           | YES  |     | 0001-01-01 00:00:00 |                |
| self                      | 1 if the contact is the user him/her self                 | boolean            | NO   |     | 0                   |                |
| remote_self               |                                                           | boolean            | NO   |     | 0                   |                |
| rel                       | The kind of the relation between the user and the contact | tinyint unsigned   | NO   |     | 0                   |                |
| duplex                    |                                                           | boolean            | NO   |     | 0                   |                |
| network                   | Network of the contact                                    | char(4)            | NO   |     |                     |                |
| protocol                  | Protocol of the contact                                   | char(4)            | NO   |     |                     |                |
| name                      | Name that this contact is known by                        | varchar(255)       | NO   |     |                     |                |
| nick                      | Nick- and user name of the contact                        | varchar(255)       | NO   |     |                     |                |
| location                  |                                                           | varchar(255)       | YES  |     |                     |                |
| about                     |                                                           | text               | YES  |     | NULL                |                |
| keywords                  | public keywords (interests) of the contact                | text               | YES  |     | NULL                |                |
| gender                    | Deprecated                                                | varchar(32)        | NO   |     |                     |                |
| xmpp                      |                                                           | varchar(255)       | NO   |     |                     |                |
| attag                     |                                                           | varchar(255)       | NO   |     |                     |                |
| avatar                    |                                                           | varchar(255)       | NO   |     |                     |                |
| photo                     | Link to the profile photo of the contact                  | varchar(255)       | YES  |     |                     |                |
| thumb                     | Link to the profile photo (thumb size)                    | varchar(255)       | YES  |     |                     |                |
| micro                     | Link to the profile photo (micro size)                    | varchar(255)       | YES  |     |                     |                |
| site-pubkey               |                                                           | text               | YES  |     | NULL                |                |
| issued-id                 |                                                           | varchar(255)       | NO   |     |                     |                |
| dfrn-id                   |                                                           | varchar(255)       | NO   |     |                     |                |
| url                       |                                                           | varchar(255)       | NO   |     |                     |                |
| nurl                      |                                                           | varchar(255)       | NO   |     |                     |                |
| addr                      |                                                           | varchar(255)       | NO   |     |                     |                |
| alias                     |                                                           | varchar(255)       | NO   |     |                     |                |
| pubkey                    | RSA public key 4096 bit                                   | text               | YES  |     | NULL                |                |
| prvkey                    | RSA private key 4096 bit                                  | text               | YES  |     | NULL                |                |
| batch                     |                                                           | varchar(255)       | NO   |     |                     |                |
| request                   |                                                           | varchar(255)       | YES  |     | NULL                |                |
| notify                    |                                                           | varchar(255)       | YES  |     | NULL                |                |
| poll                      |                                                           | varchar(255)       | YES  |     | NULL                |                |
| confirm                   |                                                           | varchar(255)       | YES  |     | NULL                |                |
| subscribe                 |                                                           | varchar(255)       | YES  |     | NULL                |                |
| poco                      |                                                           | varchar(255)       | YES  |     | NULL                |                |
| aes_allow                 |                                                           | boolean            | NO   |     | 0                   |                |
| ret-aes                   |                                                           | boolean            | NO   |     | 0                   |                |
| usehub                    |                                                           | boolean            | NO   |     | 0                   |                |
| subhub                    |                                                           | boolean            | NO   |     | 0                   |                |
| hub-verify                |                                                           | varchar(255)       | NO   |     |                     |                |
| last-update               | Date of the last try to update the contact info           | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| success_update            | Date of the last successful contact update                | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| failure_update            | Date of the last failed update                            | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| failed                    | Connection failed                                         | boolean            | YES  |     | NULL                |                |
| name-date                 |                                                           | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| uri-date                  |                                                           | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| avatar-date               |                                                           | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| term-date                 |                                                           | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| last-item                 | date of the last post                                     | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| last-discovery            | date of the last follower discovery                       | datetime           | NO   |     | 0001-01-01 00:00:00 |                |
| priority                  |                                                           | tinyint unsigned   | NO   |     | 0                   |                |
| blocked                   | Node-wide block status                                    | boolean            | NO   |     | 1                   |                |
| block_reason              | Node-wide block reason                                    | text               | YES  |     | NULL                |                |
| readonly                  | posts of the contact are readonly                         | boolean            | NO   |     | 0                   |                |
| writable                  |                                                           | boolean            | NO   |     | 0                   |                |
| forum                     | contact is a forum                                        | boolean            | NO   |     | 0                   |                |
| prv                       | contact is a private group                                | boolean            | NO   |     | 0                   |                |
| contact-type              |                                                           | tinyint            | NO   |     | 0                   |                |
| manually-approve          |                                                           | boolean            | YES  |     | NULL                |                |
| hidden                    |                                                           | boolean            | NO   |     | 0                   |                |
| archive                   |                                                           | boolean            | NO   |     | 0                   |                |
| pending                   |                                                           | boolean            | NO   |     | 1                   |                |
| deleted                   | Contact has been deleted                                  | boolean            | NO   |     | 0                   |                |
| rating                    |                                                           | tinyint            | NO   |     | 0                   |                |
| unsearchable              | Contact prefers to not be searchable                      | boolean            | NO   |     | 0                   |                |
| sensitive                 | Contact posts sensitive content                           | boolean            | NO   |     | 0                   |                |
| baseurl                   | baseurl of the contact                                    | varchar(255)       | YES  |     |                     |                |
| gsid                      | Global Server ID                                          | int unsigned       | YES  |     | NULL                |                |
| reason                    |                                                           | text               | YES  |     | NULL                |                |
| closeness                 |                                                           | tinyint unsigned   | NO   |     | 99                  |                |
| info                      |                                                           | mediumtext         | YES  |     | NULL                |                |
| profile-id                | Deprecated                                                | int unsigned       | YES  |     | NULL                |                |
| bdyear                    |                                                           | varchar(4)         | NO   |     |                     |                |
| bd                        |                                                           | date               | NO   |     | 0001-01-01          |                |
| notify_new_posts          |                                                           | boolean            | NO   |     | 0                   |                |
| fetch_further_information |                                                           | tinyint unsigned   | NO   |     | 0                   |                |
| ffi_keyword_denylist      |                                                           | text               | YES  |     | NULL                |                |

Indexes
------------

| Name | Fields |
|------|---------|
| PRIMARY | id |
| uid_name | uid, name(190) |
| self_uid | self, uid |
| alias_uid | alias(128), uid |
| pending_uid | pending, uid |
| blocked_uid | blocked, uid |
| uid_rel_network_poll | uid, rel, network, poll(64), archive |
| uid_network_batch | uid, network, batch(64) |
| batch_contact-type | batch(64), contact-type |
| addr_uid | addr(128), uid |
| nurl_uid | nurl(128), uid |
| nick_uid | nick(128), uid |
| attag_uid | attag(96), uid |
| dfrn-id | dfrn-id(64) |
| issued-id | issued-id(64) |
| network_uid_lastupdate | network, uid, last-update |
| uid_network_self_lastupdate | uid, network, self, last-update |
| uid_lastitem | uid, last-item |
| baseurl | baseurl(64) |
| uid_contact-type | uid, contact-type |
| uid_self_contact-type | uid, self, contact-type |
| self_network_uid | self, network, uid |
| gsid | gsid |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |
| gsid | [gserver](help/database/db_gserver) | id |

Return to [database documentation](help/database)
