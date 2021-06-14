Table auth_codes
===========

OAuth usage

Fields
------

| Field        | Description | Type         | Null | Key | Default | Extra |
| ------------ | ----------- | ------------ | ---- | --- | ------- | ----- |
| id           |             | varchar(40)  | NO   | PRI | NULL    |       |
| client_id    |             | varchar(20)  | NO   |     |         |       |
| redirect_uri |             | varchar(200) | NO   |     |         |       |
| expires      |             | int          | NO   |     | 0       |       |
| scope        |             | varchar(250) | NO   |     |         |       |

Indexes
------------

| Name | Fields |
|------|---------|
| PRIMARY | id |
| client_id | client_id |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| client_id | [clients](help/database/db_clients) | client_id |

Return to [database documentation](help/database)
