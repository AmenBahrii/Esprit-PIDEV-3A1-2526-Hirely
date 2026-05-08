USE `hirely`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `onboardingtask`;
DROP TABLE IF EXISTS `onboardingplan`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `onboardingplan` (
  `planId` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `deadline` date DEFAULT NULL,
  `qr_token` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`planId`),
  KEY `idx_onboardingplan_user` (`user_id`),
  KEY `idx_onboardingplan_qr_token` (`qr_token`),
  KEY `idx_onboardingplan_status_deadline` (`status`, `deadline`),
  CONSTRAINT `fk_onboardingplan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `onboardingtask` (
  `taskId` int(11) NOT NULL AUTO_INCREMENT,
  `planId` int(11) NOT NULL,
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

SET @candidate_one := (
  SELECT u.user_id
  FROM users u
  INNER JOIN role r ON r.role_id = u.role_id
  WHERE LOWER(r.name) = 'candidate'
  ORDER BY u.user_id ASC
  LIMIT 1
);

SET @candidate_two := (
  SELECT candidate_pool.user_id
  FROM (
    SELECT u.user_id
    FROM users u
    INNER JOIN role r ON r.role_id = u.role_id
    WHERE LOWER(r.name) = 'candidate'
    ORDER BY u.user_id ASC
    LIMIT 1 OFFSET 1
  ) candidate_pool
);

SET @candidate_two := COALESCE(@candidate_two, @candidate_one);

INSERT INTO `onboardingplan` (`user_id`, `status`, `deadline`, `qr_token`)
SELECT @candidate_one, 'in_progress', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'demo-plan-primary'
WHERE @candidate_one IS NOT NULL;
SET @plan_one := LAST_INSERT_ID();

INSERT INTO `onboardingtask` (`planId`, `title`, `description`, `status`, `deadline`, `filePath`, `original_file_name`, `content_type`)
SELECT @plan_one, 'Upload signed NDA', 'Send the signed NDA before equipment handoff.', 'completed', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'https://res.cloudinary.com/demo/image/upload/sample.jpg', 'signed-nda.jpg', 'image/jpeg'
WHERE @candidate_one IS NOT NULL;

INSERT INTO `onboardingtask` (`planId`, `title`, `description`, `status`, `deadline`)
SELECT @plan_one, 'Attend team kickoff meeting', 'Join the first onboarding call and confirm access needs.', 'in_progress', DATE_ADD(CURDATE(), INTERVAL 5 DAY)
WHERE @candidate_one IS NOT NULL;

INSERT INTO `onboardingtask` (`planId`, `title`, `description`, `status`, `deadline`)
SELECT @plan_one, 'Choose equipment pickup slot', 'Pick a day to collect the laptop, badge, and welcome kit.', 'not_started', DATE_ADD(CURDATE(), INTERVAL 10 DAY)
WHERE @candidate_one IS NOT NULL;

INSERT INTO `onboardingplan` (`user_id`, `status`, `deadline`, `qr_token`)
SELECT @candidate_two, 'on_hold', DATE_ADD(CURDATE(), INTERVAL 9 DAY), 'demo-plan-risk'
WHERE @candidate_two IS NOT NULL;
SET @plan_two := LAST_INSERT_ID();

INSERT INTO `onboardingtask` (`planId`, `title`, `description`, `status`, `deadline`)
SELECT @plan_two, 'Resolve access blocker', 'The candidate cannot access the internal workspace yet.', 'blocked', DATE_ADD(CURDATE(), INTERVAL 1 DAY)
WHERE @candidate_two IS NOT NULL;

INSERT INTO `onboardingtask` (`planId`, `title`, `description`, `status`, `deadline`)
SELECT @plan_two, 'Submit bank account details', 'Share bank account confirmation for payroll setup.', 'not_started', DATE_ADD(CURDATE(), INTERVAL 7 DAY)
WHERE @candidate_two IS NOT NULL;
