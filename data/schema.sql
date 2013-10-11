-- BicBucStriim SQL schema for the internal DB
create table config (id integer primary key autoincrement, name varchar(30), val varchar(255));
create unique index config_names on config(name);
create table user (id integer primary key autoincrement, username varchar(30), password char(255), email varchar(255), languages varchar(255), tags varchar(255), role integer);
create unique index user_names on user(username);
create table calibrething (id integer primary key autoincrement, ctype integer, cid integer, cname varchar(255), refctr integer);
create index index_calibrething_cid on calibrething(cid);
create table artefact (id integer primary key autoincrement, atype integer, url varchar(255), calibrething_id integer, foreign key(calibrething_id) references calibrething(id) on delete set null on update set null);
create index index_foreignkey_artefact_calibrething on artefact(calibrething_id);
create table link (id integer primary key autoincrement, ltype integer, label varchar(255), url varchar(255), calibrething_id integer, foreign key(calibrething_id) references calibrething(id) on delete set null on update set null);
create index index_foreignkey_link_calibrething on link(calibrething_id);
create table note (id integer primary key autoincrement, ntype integer, mime varchar(255), ntext text, calibrething_id integer, foreign key(calibrething_id) references calibrething(id) on delete set null on update set null);
create index index_foreignkey_note_calibrething on note(calibrething_id);
create table idtemplate (id integer primary key autoincrement, name varchar(255), val varchar(255), label varchar(255));
	