Table pagecache
===========

Stores temporary data

Fields
------

| Field   | Description                         | Type           | Null | Key | Default | Extra |
| ------- | ----------------------------------- | -------------- | ---- | --- | ------- | ----- |
| page    | Page                                | varbinary(255) | NO   | PRI | NULL    |       |
| content | Page content                        | mediumtext     | YES  |     | NULL    |       |
| fetched | date when the page had been fetched | datetime       | YES  |     | NULL    |       |

Indexes
------------

| Name    | Fields  |
| ------- | ------- |
| PRIMARY | page    |
| fetched | fetched |


Return to [database documentation](help/database)
