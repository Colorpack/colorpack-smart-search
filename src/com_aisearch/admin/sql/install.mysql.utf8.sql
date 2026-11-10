CREATE TABLE IF NOT EXISTS `#__aisearch_docs` (
  `id` int unsigned NOT NULL,
  `catid` int unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `category` varchar(255) NOT NULL DEFAULT '',
  `tags` text,
  `created` datetime DEFAULT NULL,
  `snippet` varchar(400) NOT NULL DEFAULT '',
  `image` varchar(400) NOT NULL DEFAULT '',
  `image_kind` varchar(4) NOT NULL DEFAULT '',
  `publish_up` datetime DEFAULT NULL,
  `publish_down` datetime DEFAULT NULL,
  `access` int unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__aisearch_index` (
  `id` tinyint unsigned NOT NULL,
  `data` longblob,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__aisearch_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `q` varchar(200) NOT NULL DEFAULT '',
  `results` int NOT NULL DEFAULT 0,
  `ms` float NOT NULL DEFAULT 0,
  `mode` varchar(10) NOT NULL DEFAULT 'keyword',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created`),
  KEY `idx_q` (`q`(60))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__aisearch_text` (
  `id` int unsigned NOT NULL,
  `body` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__aisearch_clicks` (
  `h` char(32) NOT NULL,
  `q` varchar(200) NOT NULL DEFAULT '',
  `doc_id` int unsigned NOT NULL,
  `clicks` int unsigned NOT NULL DEFAULT 0,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`h`, `doc_id`),
  KEY `idx_updated` (`updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
