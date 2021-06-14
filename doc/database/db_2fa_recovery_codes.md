Table 2fa_recovery_codes
===========

Two-factor authentication recovery codes

Fields
------

| Field     | Description                     | Type               | Null | Key | Default | Extra |
| --------- | ------------------------------- | ------------------ | ---- | --- | ------- | ----- |
| uid       | User ID                         | mediumint unsigned | NO   | PRI | NULL    |       |
| code      | Recovery code string            | varchar(50)        | NO   | PRI | NULL    |       |
| generated | Datetime the code was generated | datetime           | NO   |     | NULL    |       |
| used      | Datetime the code was used      | datetime           | YES  |     | NULL    |       |

Indexes
------------

| Name    | Fields    |
| ------- | --------- |
| PRIMARY | uid, code |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uid | [user](help/database/db_user) | uid |

Return to [database documentation](help/database)
