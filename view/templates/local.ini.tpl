<?php return <<<INI

[database]
hostname = {{$dbhost}}
username = {{$dbuser}}
password = {{$dbpass}}
database = {{$dbdata}}
charset = utf8mb4

; ****************************************************************
; Some config values below can be overruled from the admin settings
; ****************************************************************

[config]
php_path = {{$phpath}}

admin_email = {{$adminmail}}

sitename = Friendica Social Network

register_policy = REGISTER_OPEN
register_text =

max_import_size = 200000

[system]
urlpath = {{$urlpath}}

default_timezone = {{$timezone}}

language = {{$language}}

allowed_themes = vier,quattro,duepuntozero,smoothly,frio
theme = vier

allowed_link_protocols[] = ftp
allowed_link_protocols[] = ftps
allowed_link_protocols[] = mailto
allowed_link_protocols[] = cid
allowed_link_protocols[] = gopher

maximagesize = 800000

no_regfullname = true

block_local_dir = false

directory = https://dir.friendica.social

auth_cookie_lifetime = 7

INI;
// Keep this line