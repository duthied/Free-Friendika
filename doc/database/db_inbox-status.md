Table inbox-status
===========

Status of ActivityPub inboxes

Fields
------

| Field    | Description                          | Type           | Null | Key | Default             | Extra |
| -------- | ------------------------------------ | -------------- | ---- | --- | ------------------- | ----- |
| url      | URL of the inbox                     | varbinary(255) | NO   | PRI | NULL                |       |
| created  | Creation date of this entry          | datetime       | NO   |     | 0001-01-01 00:00:00 |       |
| success  | Date of the last successful delivery | datetime       | NO   |     | 0001-01-01 00:00:00 |       |
| failure  | Date of the last failed delivery     | datetime       | NO   |     | 0001-01-01 00:00:00 |       |
| previous | Previous delivery date               | datetime       | NO   |     | 0001-01-01 00:00:00 |       |
| archive  | Is the inbox archived?               | boolean        | NO   |     | 0                   |       |
| shared   | Is it a shared inbox?                | boolean        | NO   |     | 0                   |       |

Indexes
------------

| Name    | Fields |
| ------- | ------ |
| PRIMARY | url    |


Return to [database documentation](help/database)
