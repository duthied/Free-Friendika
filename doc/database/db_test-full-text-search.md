Table check-full-text-search
===========

Check for a full text search match in user defined channels before storing the message in the system

Fields
------

| Field      | Description                              | Type         | Null | Key | Default | Extra |
| ---------- | ---------------------------------------- | ------------ | ---- | --- | ------- | ----- |
| pid        | Process id of the worker                 | int unsigned | NO   |     | 0       |       |
| searchtext | Simplified text for the full text search | mediumtext   | YES  |     | NULL    |       |

Indexes
------------

| Name       | Fields               |
| ---------- | -------------------- |
| PRIMARY    | pid                  |
| searchtext | FULLTEXT, searchtext |


Return to [database documentation](help/database)
