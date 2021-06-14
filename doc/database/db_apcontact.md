Table apcontact
===========

ActivityPub compatible contacts - used in the ActivityPub implementation

| Field            | Description                  | Type           | Null | Key | Default             | Extra |
| ---------------- | ---------------------------- | -------------- | ---- | --- | ------------------- | ----- |
| url              | URL of the contact           | varbinary(255) | NO   | PRI | NULL                |       |
| uuid             |                              | varchar(255)   | YES  |     | NULL                |       |
| type             |                              | varchar(20)    | NO   |     | NULL                |       |
| following        |                              | varchar(255)   | YES  |     | NULL                |       |
| followers        |                              | varchar(255)   | YES  |     | NULL                |       |
| inbox            |                              | varchar(255)   | NO   |     | NULL                |       |
| outbox           |                              | varchar(255)   | YES  |     | NULL                |       |
| sharedinbox      |                              | varchar(255)   | YES  |     | NULL                |       |
| manually-approve |                              | boolean        | YES  |     | NULL                |       |
| nick             |                              | varchar(255)   | NO   |     |                     |       |
| name             |                              | varchar(255)   | YES  |     | NULL                |       |
| about            |                              | text           | YES  |     | NULL                |       |
| photo            |                              | varchar(255)   | YES  |     | NULL                |       |
| addr             |                              | varchar(255)   | YES  |     | NULL                |       |
| alias            |                              | varchar(255)   | YES  |     | NULL                |       |
| pubkey           |                              | text           | YES  |     | NULL                |       |
| subscribe        |                              | varchar(255)   | YES  |     | NULL                |       |
| baseurl          | baseurl of the ap contact    | varchar(255)   | YES  |     | NULL                |       |
| gsid             | Global Server ID             | int unsigned   | YES  |     | NULL                |       |
| generator        | Name of the contact&#039;s system | varchar(255)   | YES  |     | NULL                |       |
| following_count  | Number of following contacts | int unsigned   | YES  |     | 0                   |       |
| followers_count  | Number of followers          | int unsigned   | YES  |     | 0                   |       |
| statuses_count   | Number of posts              | int unsigned   | YES  |     | 0                   |       |
| updated          |                              | datetime       | NO   |     | 0001-01-01 00:00:00 |       |

Return to [database documentation](help/database)
