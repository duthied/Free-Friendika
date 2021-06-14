Table process
===========

Currently running system processes

Fields
------

| Field   | Description | Type          | Null | Key | Default             | Extra |
| ------- | ----------- | ------------- | ---- | --- | ------------------- | ----- |
| pid     |             | int unsigned  | NO   | PRI | NULL                |       |
| command |             | varbinary(32) | NO   |     |                     |       |
| created |             | datetime      | NO   |     | 0001-01-01 00:00:00 |       |

Indexes
------------

| Name | Fields |
|------|--------|
| PRIMARY | pid |
| command | command |


Return to [database documentation](help/database)
