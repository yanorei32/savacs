CREATE USER 'savacs'@'%';
GRANT INSERT,SELECT,UPDATE,DELETE ON `savacs_db`.* TO 'savacs'@'%';

CREATE DATABASE IF NOT EXISTS `savacs_db`;

CREATE TABLE IF NOT EXISTS `savacs_db`.`photostands` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `cpu_serial_number` VARCHAR(16)   NOT NULL,
  `password_hash`     TINYTEXT      NOT NULL,
  `last_ip_address`   TINYTEXT,
  `last_access_time`  DATETIME,

  PRIMARY KEY ( `id` ),
  UNIQUE KEY `cpu_serial_number` ( `cpu_serial_number` )

);

CREATE TABLE IF NOT EXISTS `savacs_db`.`photostands__photostands` (
  `photostand_a`  INT UNSIGNED  NOT NULL,
  `photostand_b`  INT UNSIGNED  NOT NULL,

  PRIMARY KEY (`photostand_a`, `photostand_b`),

  CONSTRAINT `fk__photostands__photostands__photostands__id__a`
    FOREIGN KEY ( `photostand_a` )
    REFERENCES `savacs_db`.`photostands` ( `id` )
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk__photostands__photostands__photostands__id__b`
    FOREIGN KEY ( `photostand_b` )
    REFERENCES `savacs_db`.`photostands` ( `id` )
    ON UPDATE CASCADE
    ON DELETE CASCADE

);

CREATE TABLE IF NOT EXISTS `savacs_db`.`record_voices` (
  `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `file_name`           VARCHAR(50)   NOT NULL,
  `duration`            INT UNSIGNED  NOT NULL,
  `from_photostand_id`  INT UNSIGNED  NOT NULL,
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY ( `id` ),
  INDEX ( `created_at`, `from_photostand_id` ),
  UNIQUE KEY `file_name` ( `file_name` ),

  CONSTRAINT `fk__record_voices__photostands__id`
    FOREIGN KEY ( `from_photostand_id` )
    REFERENCES `savacs_db`.`photostands` ( `id` )
    ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `savacs_db`.`record_voices__photostands` (
  `to_photostand_id`  INT UNSIGNED  NOT NULL,
  `record_voices_id`  INT UNSIGNED  NOT NULL,

  PRIMARY KEY ( `to_photostand_id`, `record_voices_id` ),

  CONSTRAINT `fk__record_voices__photostands__photostands__id`
    FOREIGN KEY ( `to_photostand_id` )
    REFERENCES `savacs_db`.`photostands` ( `id` )
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk__record_voices__photostands__record_voices__id`
    FOREIGN KEY ( `record_voices_id` )
    REFERENCES `savacs_db`.`record_voices` ( `id` )
    ON UPDATE CASCADE
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `savacs_db`.`selfy_images` (
  `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `file_name`           VARCHAR(50)   NOT NULL,
  `thumbnail_file_name` VARCHAR(56)   NOT NULL,
  `from_photostand_id`  INT UNSIGNED  NOT NULL,
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY ( `id` ),
  INDEX ( `created_at`, `from_photostand_id` ),
  UNIQUE KEY `file_name` ( `file_name` ),
  UNIQUE KEY `thumbnail_file_name` ( `thumbnail_file_name` ),

  CONSTRAINT `fk__selfy_images__photostands__id`
    FOREIGN KEY ( `from_photostand_id` )
    REFERENCES `savacs_db`.`photostands` ( `id` )
    ON UPDATE CASCADE

);

CREATE TABLE IF NOT EXISTS `savacs_db`.`selfy_images__photostands` (
  `to_photostand_id`  INT UNSIGNED  NOT NULL,
  `selfy_image_id`    INT UNSIGNED  NOT NULL,

  PRIMARY KEY ( `to_photostand_id`, `selfy_image_id` ),

  CONSTRAINT `fk__selfy_images__photostands__photostands__id`
    FOREIGN KEY ( `to_photostand_id` )
    REFERENCES `savacs_db`.`photostands` ( `id` )
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk__selfy_images__photostands__selfy_images__id`
    FOREIGN KEY ( `selfy_image_id` )
    REFERENCES `savacs_db`.`selfy_images` ( `id` )
    ON UPDATE CASCADE
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `savacs_db`.`motion_image_groups` (
        `id`    int unsigned    not null auto_increment,
        PRIMARY KEY ( `id` )
);

CREATE TABLE IF NOT EXISTS `savacs_db`.`motion_images` (
  `id`                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `file_name`           VARCHAR(50)   NOT NULL,
  `thumbnail_file_name` VARCHAR(56)   NOT NULL,
  `from_photostand_id`  INT UNSIGNED  NOT NULL,
  `group_id`            INT UNSIGNED  NOT NULL,
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY ( `id` ),
  INDEX ( `created_at`, `from_photostand_id`, `group_id` ),
  UNIQUE KEY `file_name` ( `file_name` ),
  UNIQUE KEY `thumbnail_file_name` ( `thumbnail_file_name` ),

  CONSTRAINT `fk__motion_images__from_photostand_id__photostands__id`
    FOREIGN KEY ( `from_photostand_id` )
    REFERENCES `savacs_db`.`photostands` ( `id` )
    ON UPDATE CASCADE,
  CONSTRAINT `fk__motion_images__group_id__motion_image_groups__id`
    FOREIGN KEY ( `group_id` )
    REFERENCES `savacs_db`.`motion_image_groups` ( `id` )
    ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `savacs_db`.`sensor_datas` (
  `id`                      INT UNSIGNED  NOT NULL,
  `cds_lux`                 FLOAT         NOT NULL,
  `temperature_celsius`     FLOAT         NOT NULL,
  `infrared_centimetear`    FLOAT         NOT NULL,
  `ultrasonic_centimetear`  FLOAT         NOT NULL,
  `pyroelectric`            FLOAT         NOT NULL,
  `event_type`              INT UNSIGNED  NOT NULL,
  `from_photostand_id`      INT UNSIGNED  NOT NULL,
  `created_at`              DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY ( `id` ),
  INDEX ( `created_at`, `from_photostand_id` ),
  CONSTRAINT `fk__sensor_datas__from_photostand_id__photostands__id`
    FOREIGN KEY ( `from_photostand_id` )
    REFERENCES `savacs_db`.`photostands` ( `id` )
    ON UPDATE CASCADE
    ON DELETE CASCADE
);

