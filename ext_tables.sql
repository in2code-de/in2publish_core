CREATE TABLE tx_in2publishcore_log
(
    uid        int(11) unsigned                NOT NULL auto_increment,
    pid        int(11) unsigned    DEFAULT '0' NOT NULL,

    request_id varchar(13)         DEFAULT ''  NOT NULL,
    time_micro decimal(15, 4)                  NOT NULL default '0.0000',
    component  varchar(255)        DEFAULT ''  NOT NULL,
    level      tinyint(1) unsigned DEFAULT '0' NOT NULL,
    message    text,
    data       text,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY request (request_id)
) ENGINE = InnoDB;

CREATE TABLE tx_in2code_in2publish_task
(
    uid             int(11) unsigned        NOT NULL auto_increment,
    task_type       varchar(255) DEFAULT '' NOT NULL,

    configuration   longtext,
    creation_date   datetime     default NULL,
    execution_begin datetime     default NULL,
    execution_end   datetime     default NULL,
    messages        longtext,

    PRIMARY KEY (uid)
) ENGINE = InnoDB;

CREATE TABLE tx_in2publishcore_running_request
(
    uid              int(11) unsigned   NOT NULL auto_increment,

    record_id        int(11) unsigned   NOT NULL,
    table_name       varchar(255)       DEFAULT '' NOT NULL,
    request_token    char(32)           DEFAULT '' NOT NULL,
    timestamp_begin  int(11)            unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid)
) ENGINE = InnoDB;

CREATE TABLE tx_in2code_in2publish_envelope
(
    uid      int(11) unsigned        NOT NULL auto_increment,

    command  varchar(255) DEFAULT '' NOT NULL,
    request  text,
    response longtext,

    PRIMARY KEY (uid)
) ENGINE = InnoDB;

CREATE TABLE tx_in2publishcore_pages_slug_data
(
    page_uid      int(11) unsigned        NOT NULL,
    page_slug     varchar(255) DEFAULT '' NOT NULL,
    page_language int(11) unsigned        NOT NULL,
    url           varchar(255) DEFAULT '' NOT NULL,
    site_id       varchar(255) DEFAULT '' NOT NULL,
    KEY page (page_uid, page_slug)
) ENGINE = InnoDB;
