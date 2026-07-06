CREATE TABLE `daily_log_images` (
	`id` bigint unsigned AUTO_INCREMENT NOT NULL,
	`daily_log_id` bigint unsigned NOT NULL,
	`path` varchar(255) NOT NULL,
	`original_name` varchar(255) NOT NULL,
	`mime` varchar(100) NOT NULL,
	`size` int unsigned NOT NULL,
	`sort_order` smallint unsigned NOT NULL DEFAULT 0,
	`created_by` bigint unsigned,
	`created_at` timestamp,
	CONSTRAINT `daily_log_images_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
ALTER TABLE `daily_log_images` ADD CONSTRAINT `daily_log_images_daily_log_id_daily_logs_id_fk` FOREIGN KEY (`daily_log_id`) REFERENCES `daily_logs`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
CREATE INDEX `daily_log_images_daily_log_id_index` ON `daily_log_images` (`daily_log_id`);