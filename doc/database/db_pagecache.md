Table pagecache
===========

Stores temporary data

Fields
------

| Field   | Description                                                        | Type           | Null | Key | Default | Extra |
| ------- | ------------------------------------------------------------------ | -------------- | ---- | --- | ------- | ----- |
| page    | Page                                                               | varbinary(255) | NO   | PRI | NULL    |       |
| uri-id  | Id of the item-uri table that contains the uri the page belongs to | int unsigned   | YES  |     | NULL    |       |
| content | Page content                                                       | mediumtext     | YES  |     | NULL    |       |
| fetched | date when the page had been fetched                                | datetime       | YES  |     | NULL    |       |

Indexes
------------

| Name    | Fields  |
| ------- | ------- |
| PRIMARY | page    |
| fetched | fetched |
| uri-id  | uri-id  |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| uri-id | [item-uri](help/database/db_item-uri) | id |

Return to [database documentation](help/database)
