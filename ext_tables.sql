CREATE TABLE tx_in2publishcore_log
(
    uid        int(11) UNSIGNED                NOT NULL AUTO_INCREMENT,
    pid        int(11) UNSIGNED    DEFAULT '0' NOT NULL,

    request_id varchar(13)         DEFAULT ''  NOT NULL,
    time_micro decimal(15, 4)                  NOT NULL DEFAULT '0.0000',
    component  varchar(255)        DEFAULT ''  NOT NULL,
    level      tinyint(1) UNSIGNED DEFAULT '0' NOT NULL,
    message    text,
    data       text,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY request (request_id)
) ENGINE = InnoDB;

CREATE TABLE tx_in2code_in2publish_task
(
    uid             int(11) UNSIGNED        NOT NULL AUTO_INCREMENT,
    task_type       varchar(255) DEFAULT '' NOT NULL,

    configuration   longtext,
    creation_date   datetime     DEFAULT NULL,
    execution_begin datetime     DEFAULT NULL,
    execution_end   datetime     DEFAULT NULL,
    messages        longtext,

    PRIMARY KEY (uid)
) ENGINE = InnoDB;

CREATE TABLE tx_in2publishcore_running_request
(
    uid             int(11) UNSIGNED             NOT NULL AUTO_INCREMENT,

    record_id       varchar(255)     DEFAULT ''  NOT NULL,
    table_name      varchar(255)     DEFAULT ''  NOT NULL,
    request_token   char(32)         DEFAULT ''  NOT NULL,
    timestamp_begin int(11) UNSIGNED DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid)
) ENGINE = InnoDB;

CREATE TABLE tx_in2code_rpc_request
(
    uid     int(11) UNSIGNED        NOT NULL AUTO_INCREMENT,

    command varchar(255) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid)
) ENGINE = InnoDB;

CREATE TABLE tx_in2code_rpc_data
(
    request   int(11) UNSIGNED NOT NULL,
    data_type varchar(10)      NOT NULL,
    payload   text,
    sorting   int(11) UNSIGNED NOT NULL,

    KEY request_and_type (request, data_type)
) ENGINE = InnoDB;

CREATE TABLE tx_in2publishcore_remotefaldriver_file
(
    -- Properties for data management
    request_token    char(32)                        NOT NULL,
    crdate           int(11) UNSIGNED                NOT NULL,
    tstamp           int(11) UNSIGNED    DEFAULT '0' NOT NULL,

    -- Values from Local
    storage_uid      int(11)                         NOT NULL,
    identifier       text,
    identifier_hash  char(40)                        NOT NULL,

    -- Values from foreign
    attr_size        bigint(20) UNSIGNED DEFAULT '0' NOT NULL,
    attr_mimetype    varchar(255)        DEFAULT ''  NOT NULL,
    attr_name        tinytext,
    attr_extension   varchar(255)        DEFAULT ''  NOT NULL,
    attr_folder_hash char(40)            DEFAULT ''  NOT NULL,
    UNIQUE INDEX id (request_token, storage_uid, identifier_hash)
) ENGINE = InnoDB;

CREATE TABLE tx_in2publishcore_filepublisher_task
(
    -- Properties for data management
    request_token        char(32)                     NOT NULL,
    crdate               int(11) UNSIGNED             NOT NULL,
    tstamp               int(11) UNSIGNED DEFAULT '0' NOT NULL,

    -- Values from Local
    storage_uid          int(11)                      NOT NULL,
    identifier           text,
    identifier_hash      char(40)                     NOT NULL,
    temp_identifier_hash char(40)         DEFAULT NULL,
    previous_identifier  text,
    -- One of "insert", "delete", "update"
    file_action          char(6)          DEFAULT NULL,
    folder_action        char(6)          DEFAULT NULL,
    UNIQUE INDEX id (request_token, storage_uid, identifier_hash)
) ENGINE = InnoDB;
