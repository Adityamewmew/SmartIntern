CREATE TABLE `holidays` (
	`id` bigint unsigned AUTO_INCREMENT NOT NULL,
	`holiday_date` date NOT NULL,
	`holiday_name` varchar(255) NOT NULL,
	`is_national_holiday` tinyint NOT NULL DEFAULT 1,
	`created_by` bigint unsigned,
	`updated_by` bigint unsigned,
	`deleted_by` bigint unsigned,
	`created_at` timestamp,
	`updated_at` timestamp,
	`deleted_at` timestamp,
	CONSTRAINT `holidays_id` PRIMARY KEY(`id`)
);
--> statement-breakpoint
CREATE INDEX `holidays_holiday_date_index` ON `holidays` (`holiday_date`);