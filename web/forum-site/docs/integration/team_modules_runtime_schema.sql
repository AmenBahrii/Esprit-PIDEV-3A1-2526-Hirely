-- Safe dev/test schema patch for the team module runtime shells.
-- This is non-destructive and does not import any full team database.
-- Run only on a dev/test database if you want the Job Offer, Application and Evaluation pages to show demo rows.

CREATE TABLE IF NOT EXISTS `joboffer` (
  `jobOfferId` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `contractType` varchar(64) NOT NULL,
  `salary` double NOT NULL DEFAULT 0,
  `location` varchar(255) NOT NULL,
  `experienceRequired` int(11) NOT NULL DEFAULT 0,
  `publicationDate` date NOT NULL,
  `status` varchar(64) NOT NULL DEFAULT 'Open',
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`jobOfferId`),
  KEY `idx_joboffer_status` (`status`),
  KEY `idx_joboffer_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `application` (
  `applicationId` int(11) NOT NULL AUTO_INCREMENT,
  `applicationDate` date NOT NULL,
  `coverLetter` longtext NOT NULL,
  `currentStatus` varchar(50) NOT NULL DEFAULT 'pending',
  `resumePath` varchar(255) DEFAULT NULL,
  `lastUpdateDate` datetime NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `jobOfferId` int(11) DEFAULT NULL,
  `expectedSalary` double NOT NULL DEFAULT 0,
  `availabilityDate` date NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `experienceYears` int(11) NOT NULL DEFAULT 0,
  `portfolioUrl` varchar(255) DEFAULT NULL,
  `score` double DEFAULT NULL,
  `reviewNote` longtext DEFAULT NULL,
  PRIMARY KEY (`applicationId`),
  KEY `idx_application_status` (`currentStatus`),
  KEY `idx_application_joboffer` (`jobOfferId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `interview_evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `overallRating` double DEFAULT NULL,
  `recommendation` varchar(50) DEFAULT NULL,
  `hireDecision` varchar(50) DEFAULT NULL,
  `strengths` longtext DEFAULT NULL,
  `weaknesses` longtext DEFAULT NULL,
  `comments` longtext DEFAULT NULL,
  `nextSteps` longtext DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_interview_eval_decision` (`hireDecision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `joboffer` (`title`, `description`, `contractType`, `salary`, `location`, `experienceRequired`, `publicationDate`, `status`)
SELECT 'Symfony Backend Intern', 'Maintain Symfony modules and improve forum integration quality gates.', 'Internship', 900, 'Tunis', 1, CURDATE(), 'Open'
WHERE NOT EXISTS (SELECT 1 FROM `joboffer` WHERE `title` = 'Symfony Backend Intern');

INSERT INTO `joboffer` (`title`, `description`, `contractType`, `salary`, `location`, `experienceRequired`, `publicationDate`, `status`)
SELECT 'HR Platform Developer', 'Build recruitment dashboards and module integration pages.', 'CDI', 2400, 'Remote', 2, CURDATE(), 'Open'
WHERE NOT EXISTS (SELECT 1 FROM `joboffer` WHERE `title` = 'HR Platform Developer');

SET @demo_job := (SELECT `jobOfferId` FROM `joboffer` WHERE `title` = 'Symfony Backend Intern' LIMIT 1);

INSERT INTO `application` (`applicationDate`, `coverLetter`, `currentStatus`, `lastUpdateDate`, `jobOfferId`, `expectedSalary`, `availabilityDate`, `phone`, `email`, `experienceYears`, `portfolioUrl`, `score`, `reviewNote`)
SELECT CURDATE(), 'I am interested in improving Symfony modules and automated proof reports.', 'pending', NOW(), @demo_job, 850, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '+21600000000', 'candidate@hirely.local', 1, 'https://example.com/portfolio', 78, 'Demo application for runtime integration.'
WHERE @demo_job IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `application` WHERE `email` = 'candidate@hirely.local' AND `jobOfferId` = @demo_job);

-- Evaluation demo data depends on the team evaluation schema shape.
-- Some team snapshots use columns such as overallRating/hireDecision, while the current local database uses
-- interview_id/criteria_id/score/comments. Keep this patch non-destructive and add evaluation demo rows manually
-- after checking the current schema if needed.
