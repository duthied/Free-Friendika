Table oembed
===========

cache for OEmbed queries

Fields
------

| Field    | Description                    | Type               | Null | Key | Default             | Extra |
| -------- | ------------------------------ | ------------------ | ---- | --- | ------------------- | ----- |
| url      | page url                       | varbinary(383)     | NO   | PRI | NULL                |       |
| maxwidth | Maximum width passed to Oembed | mediumint unsigned | NO   | PRI | NULL                |       |
| content  | OEmbed data of the page        | mediumtext         | YES  |     | NULL                |       |
| created  | datetime of creation           | datetime           | NO   |     | 0001-01-01 00:00:00 |       |

Indexes
------------

| Name    | Fields        |
| ------- | ------------- |
| PRIMARY | url, maxwidth |
| created | created       |


Return to [database documentation](help/database)
