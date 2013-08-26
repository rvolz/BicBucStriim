-- BicBucStriim SQL schema for the internal DB
create table config (id integer primary key autoincrement, name varchar(30), val varchar(255));
create unique index config_names on config(name);
create table user (id integer primary key autoincrement, username varchar(30), password char(255), email varchar(255), languages varchar(255), tags varchar(255), role integer);
create unique index user_names on user(username);