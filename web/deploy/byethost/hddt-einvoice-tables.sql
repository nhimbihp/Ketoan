-- Tables for "Hoa don dien tu" (migration 2026_11_27_000001_create_einvoice_tables).
-- ByetHost free has no terminal: run this in phpMyAdmin > SQL instead of "php artisan migrate".
-- If MySQL/MariaDB is too old for JSON columns, replace "json" with "longtext".

CREATE TABLE `einvoice_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `direction` varchar(10) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `invoice_status` varchar(10) NOT NULL DEFAULT 'all',
  `process_result` varchar(10) NOT NULL DEFAULT 'all',
  `company_name` varchar(255) DEFAULT NULL,
  `periods` json NOT NULL,
  `period_index` int unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'listing',
  `total` int unsigned NOT NULL DEFAULT 0,
  `downloaded` int unsigned NOT NULL DEFAULT 0,
  `failed` int unsigned NOT NULL DEFAULT 0,
  `errors` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `einvoice_batches_tenant_id_foreign` (`tenant_id`),
  KEY `einvoice_batches_user_id_foreign` (`user_id`),
  CONSTRAINT `einvoice_batches_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `einvoice_batches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `einvoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `einvoice_batch_id` bigint unsigned NOT NULL,
  `source` varchar(10) NOT NULL,
  `nbmst` varchar(20) NOT NULL,
  `khhdon` varchar(20) NOT NULL,
  `shdon` varchar(20) NOT NULL,
  `khmshdon` varchar(5) NOT NULL,
  `issued_on` date DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'pending',
  `file_name` varchar(255) DEFAULT NULL,
  `error` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `einvoice_items_key` (`einvoice_batch_id`,`source`,`nbmst`,`khhdon`,`shdon`,`khmshdon`),
  KEY `einvoice_items_einvoice_batch_id_status_index` (`einvoice_batch_id`,`status`),
  CONSTRAINT `einvoice_items_einvoice_batch_id_foreign` FOREIGN KEY (`einvoice_batch_id`) REFERENCES `einvoice_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mark the migration as done so "php artisan migrate" will not try to create the tables again
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_27_000001_create_einvoice_tables', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;

-- Undo:
-- DROP TABLE IF EXISTS `einvoice_items`;
-- DROP TABLE IF EXISTS `einvoice_batches`;
-- DELETE FROM `migrations` WHERE `migration` = '2026_11_27_000001_create_einvoice_tables';
