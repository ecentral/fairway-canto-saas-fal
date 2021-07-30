CREATE TABLE sys_file_canto_album (
    file int(11) unsigned DEFAULT '0' NOT NULL,
    album varchar(15) DEFAULT '' NOT NULL,
    KEY file (file),
    KEY album (album)
);
