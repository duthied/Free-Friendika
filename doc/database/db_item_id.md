Table item_id
=============

| Field   | Description                                                                                                                                               | Type         | Null | Key | Default | Extra           |
| ------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------ | ---- | --- | ------- | --------------- |
| id      | sequential ID                                                                                                                                             | int(11)      | NO   | PRI | NULL    | auto_increment  |
| iid     | item.id of the referenced item                                                                                                                            | int(11)      | NO   | MUL | 0       |                 |
| uid     | user.id of the owner of this data                                                                                                                         | int(11)      | NO   | MUL | 0       |                 |
| sid     | an additional identifier to attach or link to the referenced item (often used to store a message_id from another system in order to suppress duplicates)  | varchar(255) | NO   | MUL |         |                 |
| service | the name or description of the service which generated this identifier                                                                                    | varchar(255) | NO   | MUL |         |                 |

Return to [database documentation](help/database)
