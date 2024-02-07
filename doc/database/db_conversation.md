Table conversation
===========

Raw data and structure information for messages

Fields
------

| Field             | Description                                                          | Type             | Null | Key | Default             | Extra |
| ----------------- | -------------------------------------------------------------------- | ---------------- | ---- | --- | ------------------- | ----- |
| item-uri          | Original URI of the item - unrelated to the table with the same name | varbinary(255)   | NO   | PRI | NULL                |       |
| reply-to-uri      | URI to which this item is a reply                                    | varbinary(255)   | NO   |     |                     |       |
| conversation-uri  | GNU Social conversation URI                                          | varbinary(255)   | NO   |     |                     |       |
| conversation-href | GNU Social conversation link                                         | varbinary(255)   | NO   |     |                     |       |
| protocol          | The protocol of the item                                             | tinyint unsigned | NO   |     | 255                 |       |
| direction         | How the message arrived here: 1=push, 2=pull                         | tinyint unsigned | NO   |     | 0                   |       |
| source            | Original source                                                      | mediumtext       | YES  |     | NULL                |       |
| received          | Receiving date                                                       | datetime         | NO   |     | 0001-01-01 00:00:00 |       |

Indexes
------------

| Name             | Fields           |
| ---------------- | ---------------- |
| PRIMARY          | item-uri         |
| conversation-uri | conversation-uri |
| received         | received         |


Return to [database documentation](help/database)
