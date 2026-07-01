CREATE TABLE `daily_logs` (
	`id` bigint unsigned AUTO_INCREMENT NOT NULL,
	`user_id` bigint unsigned,
	`log_date` date,
	`title` varchar(255) NOT NULL,
	`description` text,
	`status` varchar(50) NOT NULL DEFAULT 'draft',
	`created_by` bigint unsigned,
	`updated_by` bigint unsigned,
	`deleted_by` bigint unsigned,
	`created_at` timestamp,
	`updated_at` timestamp,
	`deleted_at` timestamp,
	CONSTRAINT `daily_logs_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
ALTER TABLE `daily_logs` ADD CONSTRAINT `daily_logs_user_id_users_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE no action ON UPDATE no action;--> statement-breakpoint
CREATE INDEX `daily_logs_user_id_index` ON `daily_logs` (`user_id`);--> statement-breakpoint
CREATE INDEX `daily_logs_log_date_index` ON `daily_logs` (`log_date`);--> statement-breakpoint
CREATE INDEX `daily_logs_status_index` ON `daily_logs` (`status`);