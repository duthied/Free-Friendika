Table contact-relation
===========

Contact relations

Fields
------

| Field            | Description                                         | Type         | Null | Key | Default             | Extra |
| ---------------- | --------------------------------------------------- | ------------ | ---- | --- | ------------------- | ----- |
| cid              | contact the related contact had interacted with     | int unsigned | NO   | PRI | 0                   |       |
| relation-cid     | related contact who had interacted with the contact | int unsigned | NO   | PRI | 0                   |       |
| last-interaction | Date of the last interaction                        | datetime     | NO   |     | 0001-01-01 00:00:00 |       |
| follow-updated   | Date of the last update of the contact relationship | datetime     | NO   |     | 0001-01-01 00:00:00 |       |
| follows          |                                                     | boolean      | NO   |     | 0                   |       |

Indexes
------------

| Name         | Fields            |
| ------------ | ----------------- |
| PRIMARY      | cid, relation-cid |
| relation-cid | relation-cid      |

Foreign Keys
------------

| Field | Target Table | Target Field |
|-------|--------------|--------------|
| cid | [contact](help/database/db_contact) | id |
| relation-cid | [contact](help/database/db_contact) | id |

Return to [database documentation](help/database)
