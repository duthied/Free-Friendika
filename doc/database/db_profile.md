Table profile
=============

| Field        | Description                                   | Type         | Null | Key | Default             | Extra          |
|--------------|-----------------------------------------------|--------------|------|-----|---------------------|----------------|
| id           | sequential ID                                 | int(11)      | NO   | PRI | NULL                | auto_increment |
| uid          | user.id of the owner of this data             | int(11)      | NO   |     | 0                   |                |
| profile-name | Name of the profile                           | varchar(255) | NO   |     |                     |                |
| is-default   | Mark this profile as default profile          | tinyint(1)   | NO   |     | 0                   |                |
| hide-friends | Hide friend list from viewers of this profile | tinyint(1)   | NO   |     | 0                   |                |
| name         |                                               | varchar(255) | NO   |     |                     |                |
| pdesc        | Deprecated                                    | varchar(255) | NO   |     |                     |                |
| dob          | Day of birth                                  | varchar(32)  | NO   |     | 0001-01-01          |                |
| address      |                                               | varchar(255) | NO   |     |                     |                |
| locality     |                                               | varchar(255) | NO   |     |                     |                |
| region       |                                               | varchar(255) | NO   |     |                     |                |
| postal-code  |                                               | varchar(32)  | NO   |     |                     |                |
| country-name |                                               | varchar(255) | NO   |     |                     |                |
| hometown     | Deprecated                                    | varchar(255) | NO   | MUL |                     |                |
| gender       | Deprecated                                    | varchar(32)  | NO   |     |                     |                |
| marital      | Deprecated                                    | varchar(255) | NO   |     |                     |                |
| with         | Deprecated                                    | text         | NO   |     | NULL                |                |
| howlong      | Deprecated                                    | datetime     | NO   |     | 0001-01-01 00:00:00 |                |
| sexual       | Deprecated                                    | varchar(255) | NO   |     |                     |                |
| politic      | Deprecated                                    | varchar(255) | NO   |     |                     |                |
| religion     | Deprecated                                    | varchar(255) | NO   |     |                     |                |
| pub_keywords |                                               | text         | NO   |     | NULL                |                |
| prv_keywords |                                               | text         | NO   |     | NULL                |                |
| likes        | Deprecated                                    | text         | NO   |     | NULL                |                |
| dislikes     | Deprecated                                    | text         | NO   |     | NULL                |                |
| about        | Profile description                           | text         | NO   |     |                     |                |
| summary      | Deprecated                                    | varchar(255) | NO   |     |                     |                |
| music        | Deprecated                                    | text         | NO   |     | NULL                |                |
| book         | Deprecated                                    | text         | NO   |     | NULL                |                |
| tv           | Deprecated                                    | text         | NO   |     | NULL                |                |
| film         | Deprecated                                    | text         | NO   |     | NULL                |                |
| interest     | Deprecated                                    | text         | NO   |     | NULL                |                |
| romance      | Deprecated                                    | text         | NO   |     | NULL                |                |
| work         | Deprecated                                    | text         | NO   |     | NULL                |                |
| education    | Deprecated                                    | text         | NO   |     | NULL                |                |
| contact      | Deprecated                                    | text         | NO   |     | NULL                |                |
| homepage     |                                               | varchar(255) | NO   |     |                     |                |
| photo        |                                               | varchar(255) | NO   |     |                     |                |
| thumb        |                                               | varchar(255) | NO   |     |                     |                |
| publish      | publish default profile in local directory    | tinyint(1)   | NO   |     | 0                   |                |
| net-publish  | publish profile in global directory           | tinyint(1)   | NO   |     | 0                   |                |

Return to [database documentation](help/database)
