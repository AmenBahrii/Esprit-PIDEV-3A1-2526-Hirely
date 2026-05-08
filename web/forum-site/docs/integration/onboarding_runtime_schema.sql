-- Safe dev/test schema patch for the integrated onboarding runtime slice.
-- Source reference: Hirely v3 database/onboarding_schema_patch.sql.
-- This version is intentionally non-destructive: it does not DROP tables and does not import the full team database.
-- Run on a disposable dev/test database only after reviewing current data.

CREATE TABLE IF NOT EXISTS `onboardingplan` (
  `planId` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `deadline` date DEFAULT NULL,
  `qr_token` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`planId`),
  KEY `idx_onboardingplan_user` (`user_id`),
  KEY `idx_onboardingplan_qr_token` (`qr_token`),
  KEY `idx_onboardingplan_status_deadline` (`status`, `deadline`),
  CONSTRAINT `fk_onboardingplan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `onboardingtask` (
  `taskId` int(11) NOT NULL AUTO_INCREMENT,
  `planId` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'not_started',
  `deadline` date DEFAULT NULL,
  `filePath` varchar(255) DEFAULT NULL,
  `cloudinary_public_id` varchar(255) DEFAULT NULL,
  `original_file_name` varchar(120) DEFAULT NULL,
  `content_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`taskId`),
  KEY `idx_onboardingtask_plan` (`planId`),
  KEY `idx_onboardingtask_plan_task` (`planId`, `taskId`),
  KEY `idx_onboardingtask_status_deadline` (`status`, `deadline`),
  CONSTRAINT `fk_onboardingtask_plan` FOREIGN KEY (`planId`) REFERENCES `onboardingplan` (`planId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @demo_user := (
  SELECT `user_id`
  FROM `users`
  ORDER BY `user_id` ASC
  LIMIT 1
);

INSERT INTO `onboardingplan` (`user_id`, `status`, `deadline`, `qr_token`)
SELECT @demo_user, 'in_progress', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'demo-onboarding-runtime'
WHERE @demo_user IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `onboardingplan` WHERE `qr_token` = 'demo-onboarding-runtime'
  );

SET @demo_plan := (
  SELECT `planId`
  FROM `onboardingplan`
  WHERE `qr_token` = 'demo-onboarding-runtime'
  LIMIT 1
);

INSERT INTO `onboardingtask` (`planId`, `title`, `description`, `status`, `deadline`, `filePath`, `original_file_name`, `content_type`)
SELECT @demo_plan, 'Upload signed NDA', 'Send the signed NDA before equipment handoff.', 'completed', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'https://example.com/signed-nda.pdf', 'signed-nda.pdf', 'application/pdf'
WHERE @demo_plan IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `onboardingtask` WHERE `planId` = @demo_plan AND `title` = 'Upload signed NDA'
  );

INSERT INTO `onboardingtask` (`planId`, `title`, `description`, `status`, `deadline`)
SELECT @demo_plan, 'Attend kickoff meeting', 'Join the first onboarding call and confirm access needs.', 'in_progress', DATE_ADD(CURDATE(), INTERVAL 5 DAY)
WHERE @demo_plan IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `onboardingtask` WHERE `planId` = @demo_plan AND `title` = 'Attend kickoff meeting'
  );

INSERT INTO `onboardingtask` (`planId`, `title`, `description`, `status`, `deadline`)
SELECT @demo_plan, 'Choose equipment pickup slot', 'Pick a day to collect the laptop, badge, and welcome kit.', 'not_started', DATE_ADD(CURDATE(), INTERVAL 10 DAY)
WHERE @demo_plan IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `onboardingtask` WHERE `planId` = @demo_plan AND `title` = 'Choose equipment pickup slot'
  );
