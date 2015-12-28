Table auth_codes
================

OAuth2 authorisation register - currently implemented but unused

| Field         | Description | Type         | Null | Key | Default | Extra |
| ------------- | ----------- | ------------ | ---- | --- | ------- | ----- |
| id            |             | varchar(40)  | NO   | PRI | NULL    |       |
| client_id     |             | varchar(20)  | NO   |     |         |       |
| redirect_uri  |             | varchar(200) | NO   |     |         |       |
| expires       |             | int(11)      | NO   |     | 0       |       |
| scope         |             | varchar(250) | NO   |     |         |       |

Return to [database documentation](help/database)
