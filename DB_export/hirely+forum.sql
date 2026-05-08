-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 02, 2026 at 08:04 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hirely`
--

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE `application` (
  `applicationId` int(11) NOT NULL,
  `applicationDate` date DEFAULT NULL,
  `coverLetter` text DEFAULT NULL,
  `currentStatus` enum('Pending','Reviewed','Accepted','Rejected') DEFAULT 'Pending',
  `resumePath` varchar(255) DEFAULT NULL,
  `lastUpdateDate` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `jobOfferId` int(11) DEFAULT NULL,
  `expectedSalary` double DEFAULT NULL,
  `availabilityDate` date DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `experienceYears` int(11) DEFAULT NULL,
  `portfolioUrl` varchar(255) DEFAULT NULL,
  `score` double DEFAULT NULL,
  `reviewNote` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application`
--

INSERT INTO `application` (`applicationId`, `applicationDate`, `coverLetter`, `currentStatus`, `resumePath`, `lastUpdateDate`, `user_id`, `jobOfferId`, `expectedSalary`, `availabilityDate`, `phone`, `email`, `experienceYears`, `portfolioUrl`, `score`, `reviewNote`) VALUES
(25, '2026-03-01', 'I am highly motivated to join your company and contribute with my technical skills.', 'Pending', 'resumes\\Application_Example.pdf', '2026-03-01 20:27:11', 7, 9, 3500, '2026-04-01', '99123456', 'souhaib.b@example.com', 2, 'https://github.com/souhaib-dev', NULL, NULL),
(29, '2026-01-03', 'I am highly motivated to join your company and contribute with my technical skills.', 'Pending', 'resumes\\Application_Example.pdf', '2026-03-02 17:25:47', 7, 10, 3500, '2026-04-01', '99123456', 'souhaib.b@example.com', 2, 'https://github.com/souhaib-dev', NULL, NULL),
(30, '2026-01-03', 'I am highly motivated to join your company and contribute with my technical skills.', 'Pending', 'resumes\\Application_Example.pdf', '2026-03-02 17:45:09', 8, 10, 3500, '2026-04-01', '99123456', 'boualleguisouhaib@gmail.com', 2, 'https://github.com/souhaib-dev', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `forum_comment`
--

CREATE TABLE `forum_comment` (
  `id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `author_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `moderation_note` text DEFAULT NULL,
  `edited_at` datetime DEFAULT NULL,
  `edited_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_comment`
--

INSERT INTO `forum_comment` (`id`, `post_id`, `author_id`, `content`, `status`, `moderation_note`, `edited_at`, `edited_by`, `created_at`, `updated_at`, `is_pinned`) VALUES
(4, 5, 1, 'Keep it 1 page, strong projects, quantified impact. Happy to review.', 'APPROVED', NULL, NULL, NULL, '2026-02-14 19:39:52', '2026-02-14 19:39:52', 0),
(5, 5, 2, 'Thanks Ali! I will update it and share results.', 'APPROVED', NULL, NULL, NULL, '2026-02-14 19:39:52', '2026-02-14 19:39:52', 0),
(6, 4, 2, 'This should be hidden until admin approves (PENDING).', 'PENDING', 'AI: Duplicate=0.22 | Toxicity=0.03 | Relevance=0.11 | LinkThreat=NONE (Resume)', NULL, NULL, '2026-02-14 19:39:52', '2026-03-02 11:04:36', 0),
(17, 7, 99, 'Only 5 new posts !!!', 'APPROVED', NULL, NULL, NULL, '2026-02-15 18:43:17', '2026-02-15 18:54:02', 0),
(18, 7, 2, 'Good News!', 'APPROVED', NULL, NULL, NULL, '2026-02-15 18:50:29', '2026-02-15 18:53:28', 0),
(20, 7, 1, 'Great!', 'APPROVED', NULL, NULL, NULL, '2026-02-15 19:02:19', '2026-02-15 19:05:01', 0),
(22, 7, 1, 'Great!', 'APPROVED', NULL, NULL, NULL, '2026-02-15 19:05:17', '2026-02-15 19:05:17', 0),
(23, 7, 1, 'Great!', 'APPROVED', NULL, NULL, NULL, '2026-02-15 19:05:28', '2026-02-15 19:05:28', 0),
(24, 7, 1, 'Great!', 'APPROVED', NULL, NULL, NULL, '2026-02-15 19:05:43', '2026-02-15 19:05:43', 0),
(26, 6, 1, 'no works just fine', 'APPROVED', NULL, NULL, NULL, '2026-02-16 17:28:39', '2026-02-16 17:28:39', 0),
(27, 5, 1, 'of course anytime !', 'PENDING', 'AI: Duplicate=0.46 | Toxicity=0.01 | Relevance=0.16 | LinkThreat=NONE (Resume)', NULL, NULL, '2026-02-17 09:24:12', '2026-03-02 11:04:36', 0),
(28, 5, 2, 'Thank you !', 'PENDING', 'AI: Duplicate=0.46 | Toxicity=0.01 | Relevance=0.19 | LinkThreat=NONE (Resume)', NULL, NULL, '2026-02-17 09:51:30', '2026-03-02 11:04:35', 0),
(30, 4, 1, 'I like this post', 'APPROVED', NULL, NULL, NULL, '2026-02-22 09:23:01', '2026-02-22 09:23:01', 0),
(31, 6, 1, 'this so disgusting !', 'PENDING', 'AI: Duplicate=0.32 | Toxicity=0.48 | Relevance=0.05 | LinkThreat=NONE (Applications)', NULL, NULL, '2026-02-22 09:55:34', '2026-03-02 11:04:35', 0),
(32, 4, 1, 'shit', 'PENDING', 'AI: Duplicate=0.28 | Toxicity=0.79 | Relevance=0.10 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-22 09:56:58', '2026-03-02 11:04:35', 0),
(33, 4, 1, 'I love this post', 'APPROVED', NULL, NULL, NULL, '2026-02-22 09:57:44', '2026-02-22 09:57:44', 0),
(34, 4, 1, 'need more info plz', 'APPROVED', 'AI: Duplicate=0.27 | Toxicity=0.02 | Relevance=0.10 | LinkThreat=NONE (Interview)', NULL, NULL, '2026-02-22 09:57:55', '2026-03-02 10:08:31', 0),
(35, 6, 2, 'Kill yourself', 'REJECTED', 'AI: Duplicate=0.31 | Toxicity=0.85 | Relevance=0.09 | LinkThreat=NONE (Interview)', NULL, NULL, '2026-02-24 09:14:27', '2026-03-02 11:04:35', 0),
(37, 5, 3, '@gemini respond', 'PENDING', 'AI: Duplicate=0.25 | Toxicity=0.04 | Relevance=0.12 | LinkThreat=NONE (Resume)', NULL, NULL, '2026-03-02 10:00:01', '2026-03-02 11:04:35', 0),
(38, 5, 100, 'A clean and impactful 1-page CV focuses on conciseness and strong presentation. Consider these best practices:\n\n*   **Prioritize Relevance:** Only include experience and achievements directly pertinent to the jobs you\'re targeting. Every word should add value.\n*   **Clear Structure:** Use distinct headings (e.g., Contact, Summary/Objective, Education, Experience, Skills) and consistent formatting.\n*   **Quantify Achievements:** Whenever possible, use numbers and data to demonstrate the impact of your contributions.\n*   **Tailor Keywords:** Integrate keywords from target job descriptions to optimize for applicant tracking systems (ATS).\n*   **Readability:** Utilize ample white space, a professional font, and bullet points for easy scanning.\n*   **Meticulous Proofreading:** Ensure your CV is free of any typos or grammatical errors.\n*   **Save as PDF:** This preserves your formatting across all viewing platforms.', 'APPROVED', NULL, NULL, NULL, '2026-03-02 10:00:12', '2026-03-02 10:00:12', 0),
(39, 4, 99, '@gemini provide help', 'APPROVED', NULL, NULL, NULL, '2026-03-02 10:10:25', '2026-03-02 10:10:25', 0),
(40, 4, 100, 'Happy to provide guidance regarding internships! To make the advice most relevant, feel free to share your specific questions or areas of interest. In the meantime, here are some common topics prospective interns often seek help with:\n\n*   **Finding and applying:** Strategies for identifying relevant internships and submitting strong applications.\n*   **Resume/cover letter:** Tips for tailoring your application materials to stand out to recruiters.\n*   **Interview preparation:** How to prepare for common internship interview questions and scenarios.\n*   **Making the most of the experience:** Advice on networking, learning, and contributing effectively during your internship.\n*   **Converting to full-time:** Strategies for leveraging an internship into a full-time job offer.', 'APPROVED', NULL, NULL, NULL, '2026-03-02 10:10:31', '2026-03-02 10:10:31', 0);

-- --------------------------------------------------------

--
-- Table structure for table `forum_interaction`
--

CREATE TABLE `forum_interaction` (
  `id` bigint(20) NOT NULL,
  `target_type` enum('POST','COMMENT') NOT NULL DEFAULT 'POST',
  `target_id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `interaction_type` enum('LIKE') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_interaction`
--

INSERT INTO `forum_interaction` (`id`, `target_type`, `target_id`, `user_id`, `interaction_type`, `created_at`) VALUES
(47, 'POST', 6, 2, 'LIKE', '2026-02-25 18:20:19'),
(48, 'POST', 5, 2, 'LIKE', '2026-02-25 18:20:31'),
(52, 'POST', 9, 2, 'LIKE', '2026-02-27 06:23:44'),
(54, 'POST', 9, 1, 'LIKE', '2026-02-27 09:23:14'),
(55, 'POST', 7, 1, 'LIKE', '2026-02-27 09:23:18'),
(56, 'POST', 7, 2, 'LIKE', '2026-02-27 09:23:26'),
(61, 'POST', 7, 3, 'LIKE', '2026-03-02 06:41:04'),
(62, 'POST', 9, 3, 'LIKE', '2026-03-02 06:41:07'),
(63, 'POST', 6, 3, 'LIKE', '2026-03-02 06:41:09'),
(64, 'POST', 5, 3, 'LIKE', '2026-03-02 06:41:10');

-- --------------------------------------------------------

--
-- Table structure for table `forum_notification`
--

CREATE TABLE `forum_notification` (
  `id` bigint(20) NOT NULL,
  `recipient_user_id` int(11) NOT NULL,
  `actor_user_id` int(11) DEFAULT NULL,
  `type` enum('POST_LIKED','COMMENT_LIKED','COMMENT_ADDED','POST_COMMENTED','POST_STATUS_CHANGED') NOT NULL,
  `post_id` bigint(20) DEFAULT NULL,
  `comment_id` bigint(20) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_notification`
--

INSERT INTO `forum_notification` (`id`, `recipient_user_id`, `actor_user_id`, `type`, `post_id`, `comment_id`, `message`, `is_read`, `created_at`) VALUES
(1, 99, 1, 'POST_LIKED', 9, NULL, 'Ali Ben Salah liked your post (#9).', 0, '2026-02-25 18:10:10'),
(2, 99, 2, 'POST_LIKED', 9, NULL, 'Mohammed Rhim liked your post (#9).', 0, '2026-02-25 18:10:15'),
(3, 99, 2, 'POST_LIKED', 9, NULL, 'Mohammed Rhim liked your post (#9).', 0, '2026-02-25 18:10:38'),
(6, 99, 2, 'POST_LIKED', 9, NULL, 'Mohammed Rhim liked your post (#9).', 0, '2026-02-25 18:13:43'),
(7, 99, 1, 'POST_LIKED', 9, NULL, 'Ali Ben Salah liked your post (#9).', 0, '2026-02-27 06:23:38'),
(8, 99, 2, 'POST_LIKED', 9, NULL, 'Mohammed Rhim liked your post (#9).', 0, '2026-02-27 06:23:44'),
(9, 1, 2, 'POST_LIKED', 7, NULL, 'Mohammed Rhim liked your post (#7).', 1, '2026-02-27 06:23:45'),
(10, 99, 1, 'POST_LIKED', 9, NULL, 'Ali Ben Salah liked your post (#9).', 0, '2026-02-27 09:23:14'),
(11, 1, 2, 'POST_LIKED', 7, NULL, 'Mohammed Rhim liked your post (#7).', 1, '2026-02-27 09:23:26'),
(12, 2, 1, 'POST_LIKED', 6, NULL, 'Ali Ben Salah liked your post (#6).', 1, '2026-03-01 10:31:42'),
(13, 1, 3, 'POST_LIKED', 7, NULL, 'youssef kaddech liked your post (#7).', 0, '2026-03-02 06:41:04'),
(14, 99, 3, 'POST_LIKED', 9, NULL, 'youssef kaddech liked your post (#9).', 0, '2026-03-02 06:41:07'),
(15, 2, 3, 'POST_LIKED', 6, NULL, 'youssef kaddech liked your post (#6).', 0, '2026-03-02 06:41:09'),
(16, 2, 3, 'POST_LIKED', 5, NULL, 'youssef kaddech liked your post (#5).', 0, '2026-03-02 06:41:10'),
(17, 2, 3, 'POST_COMMENTED', 5, 37, 'youssef kaddech commented on your post (#5).', 0, '2026-03-02 10:00:02'),
(18, 99, 99, 'POST_STATUS_CHANGED', 30, NULL, 'AI feedback: Post #30 (Analyze).', 1, '2026-03-02 10:21:38'),
(19, 99, 99, 'POST_STATUS_CHANGED', 28, NULL, 'AI feedback: Post #28 (Analyze).', 0, '2026-03-02 10:22:22'),
(20, 99, 99, 'POST_STATUS_CHANGED', 30, NULL, 'AI feedback: Post #30 (Audit).', 0, '2026-03-02 10:25:50'),
(21, 99, 99, 'POST_STATUS_CHANGED', 25, NULL, 'AI feedback: Post #25 (Audit).', 0, '2026-03-02 10:25:51'),
(22, 99, 99, 'POST_STATUS_CHANGED', 24, NULL, 'AI feedback: Post #24 (Audit).', 0, '2026-03-02 10:25:51'),
(23, 99, 99, 'POST_STATUS_CHANGED', 23, NULL, 'AI feedback: Post #23 (Audit).', 0, '2026-03-02 10:25:51'),
(24, 99, 99, 'POST_STATUS_CHANGED', 22, NULL, 'AI feedback: Post #22 (Audit).', 0, '2026-03-02 10:25:52'),
(25, 99, 99, 'POST_STATUS_CHANGED', 21, NULL, 'AI feedback: Post #21 (Audit).', 0, '2026-03-02 10:25:52'),
(26, 99, 99, 'POST_STATUS_CHANGED', 20, NULL, 'AI feedback: Post #20 (Audit).', 0, '2026-03-02 10:25:52'),
(27, 99, 99, 'POST_STATUS_CHANGED', 19, NULL, 'AI feedback: Post #19 (Audit).', 0, '2026-03-02 10:25:52'),
(28, 99, 99, 'POST_STATUS_CHANGED', 18, NULL, 'AI feedback: Post #18 (Audit).', 0, '2026-03-02 10:25:53'),
(29, 99, 99, 'POST_STATUS_CHANGED', 17, NULL, 'AI feedback: Post #17 (Audit).', 0, '2026-03-02 10:25:53'),
(30, 99, 99, 'POST_STATUS_CHANGED', 16, NULL, 'AI feedback: Post #16 (Audit).', 0, '2026-03-02 10:25:53'),
(31, 99, 99, 'POST_STATUS_CHANGED', 15, NULL, 'AI feedback: Post #15 (Audit).', 0, '2026-03-02 10:25:53'),
(32, 99, 99, 'POST_STATUS_CHANGED', 14, NULL, 'AI feedback: Post #14 (Audit).', 0, '2026-03-02 10:25:54'),
(33, 99, 99, 'POST_STATUS_CHANGED', 13, NULL, 'AI feedback: Post #13 (Audit).', 0, '2026-03-02 10:25:54'),
(34, 99, 99, 'POST_STATUS_CHANGED', 12, NULL, 'AI feedback: Post #12 (Audit).', 0, '2026-03-02 10:25:54'),
(35, 99, 99, 'POST_STATUS_CHANGED', 11, NULL, 'AI feedback: Post #11 (Audit).', 0, '2026-03-02 10:25:54'),
(36, 99, 99, 'POST_STATUS_CHANGED', NULL, 37, 'AI feedback: Comment #37 (Audit).', 0, '2026-03-02 10:25:54'),
(37, 99, 99, 'POST_STATUS_CHANGED', NULL, 35, 'AI feedback: Comment #35 (Audit).', 0, '2026-03-02 10:25:55'),
(38, 99, 99, 'POST_STATUS_CHANGED', NULL, 32, 'AI feedback: Comment #32 (Audit).', 0, '2026-03-02 10:25:55'),
(39, 99, 99, 'POST_STATUS_CHANGED', NULL, 31, 'AI feedback: Comment #31 (Audit).', 0, '2026-03-02 10:25:55'),
(40, 99, 99, 'POST_STATUS_CHANGED', NULL, 28, 'AI feedback: Comment #28 (Audit).', 0, '2026-03-02 10:25:55'),
(41, 99, 99, 'POST_STATUS_CHANGED', NULL, 27, 'AI feedback: Comment #27 (Audit).', 0, '2026-03-02 10:25:56'),
(42, 99, 99, 'POST_STATUS_CHANGED', NULL, 6, 'AI feedback: Comment #6 (Audit).', 0, '2026-03-02 10:25:56');

-- --------------------------------------------------------

--
-- Table structure for table `forum_post`
--

CREATE TABLE `forum_post` (
  `id` bigint(20) NOT NULL,
  `author_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `tag` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `moderation_note` text DEFAULT NULL,
  `edited_at` datetime DEFAULT NULL,
  `edited_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_post`
--

INSERT INTO `forum_post` (`id`, `author_id`, `title`, `content`, `tag`, `status`, `is_pinned`, `is_locked`, `moderation_note`, `edited_at`, `edited_by`, `created_at`, `updated_at`) VALUES
(3, 1, 'urgent', 'bitcoin value decreased', '#investment', 'APPROVED', 0, 0, NULL, NULL, NULL, '2026-02-12 20:54:30', '2026-02-15 18:16:52'),
(4, 1, 'Internship Questions', 'Ask anything about internships here. Mohammed, drop your questions!', '#internships', 'APPROVED', 0, 0, NULL, NULL, NULL, '2026-02-14 19:39:41', '2026-02-14 19:39:41'),
(5, 2, 'Need help with CV format', 'Any recommendations for a clean 1-page CV?', '#career', 'APPROVED', 0, 0, NULL, NULL, NULL, '2026-02-14 19:39:41', '2026-02-14 19:39:41'),
(6, 2, 'Bug: comment refresh', 'Sometimes I need to press refresh twice to see new comments. Anyone else?', '#bugs', 'APPROVED', 0, 0, NULL, NULL, NULL, '2026-02-14 19:39:41', '2026-02-14 19:39:41'),
(7, 1, 'New softoware developers posts', 'the company Itech is offering new posts', '#job', 'APPROVED', 1, 1, 'AI: Duplicate=0.36 | Toxicity=0.50 | Relevance=0.18 | LinkThreat=NONE (Job Offer)', NULL, NULL, '2026-02-15 18:16:17', '2026-03-02 10:04:25'),
(9, 99, 'Welcome to Hirely', 'Dear users all content here should be professional !', '#Hirely', 'APPROVED', 1, 1, 'AI: Duplicate=0.27 | Toxicity=0.03 | Relevance=0.32 | LinkThreat=NONE (Resume)', NULL, NULL, '2026-02-15 19:31:45', '2026-03-02 10:03:50'),
(11, 1, 'DS internships', '“Looking for a summer internship in data science. Any openings in Tunis?”', '#internships', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.55 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-23 17:16:08', '2026-03-02 11:04:34'),
(12, 1, 'DS internship', '“Looking for a summer internship in data science. Any openings in Tunis?”', '#internship', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.56 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-23 17:29:54', '2026-03-02 11:04:34'),
(13, 1, 'DS internship', '“Looking for a summer internship in data science. Any openings in Tunis?”', '#internship', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.56 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-23 17:37:34', '2026-03-02 11:04:34'),
(14, 1, 'DS internship', '“Looking for a summer internship in data science. Any openings in Tunis?”', '#internship', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.56 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-23 17:42:27', '2026-03-02 11:04:34'),
(15, 1, 'DS internship', '“Looking for a summer internship in data science. Any openings in Tunis?”', '#internship', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.56 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-23 18:00:43', '2026-03-02 11:04:33'),
(16, 1, 'DS internship', '“Looking for a summer internship in data science. Any openings in Tunis?”', '#internship', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.56 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-23 18:10:39', '2026-03-02 11:04:33'),
(17, 1, 'internship', '“Looking for a summer internship in data science. Any openings in Tunis?”', '#internship', 'PENDING', 0, 0, 'AI: Duplicate=0.98 | Toxicity=0.01 | Relevance=0.61 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-23 18:18:49', '2026-03-02 11:04:33'),
(18, 1, 'Summer Internship (Data Science) — Tunis / Remote', 'Hi everyone, I’m looking for a summer internship in Data Science / Machine Learning in Tunis (or remote).\nI’m comfortable with Python, pandas, scikit-learn, and basic SQL, and I’ve built a small project using data cleaning + model training.\nIf your company is hiring interns (or if you know of openings), I’d really appreciate:\n\nthe role title\n\nrequired skills\n\napplication link / email\n\nwhether it’s remote/hybrid\n\nThanks in advance!', '#Internship', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.60 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-24 06:23:27', '2026-03-02 11:04:33'),
(19, 1, 'Summer Internship (Data Science) — Tunis / Remote', 'Hi everyone, I’m looking for a summer internship in Data Science / Machine Learning in Tunis (or remote).\nI’m comfortable with Python, pandas, scikit-learn, and basic SQL, and I’ve built a small project using data cleaning + model training.\nIf your company is hiring interns (or if you know of openings), I’d really appreciate:\n\nthe role title\n\nrequired skills\n\napplication link / email\n\nwhether it’s remote/hybrid\n\nThanks in advance!', '#Internship', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.60 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-24 06:28:53', '2026-03-02 11:04:33'),
(20, 1, 'DS internship', 'I\'m looking for internships in Tunis I\'m a student looking for remote ones', '#internship', 'PENDING', 0, 0, 'AI: Duplicate=0.81 | Toxicity=0.02 | Relevance=0.56 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-24 09:03:53', '2026-03-02 11:04:32'),
(21, 2, 'new jobs', 'Company Y is hiring 50 new jobs', '#job', 'PENDING', 0, 0, 'AI: Duplicate=0.83 | Toxicity=0.01 | Relevance=0.48 | LinkThreat=NONE (Job Offer)', NULL, NULL, '2026-02-24 09:24:55', '2026-03-02 11:04:32'),
(22, 1, 'New Jobs', 'Company YY is hiring 70 new jobs', '#job', 'PENDING', 0, 0, 'AI: Duplicate=0.83 | Toxicity=0.01 | Relevance=0.48 | LinkThreat=NONE (Job Offer)', NULL, NULL, '2026-02-24 10:02:14', '2026-03-02 11:04:32'),
(23, 2, 'Bug: comment refresh', 'Sometimes I need to press refresh twice to see new comments. Anyone else?', '#bugs', 'PENDING', 0, 0, 'AI: Duplicate=0.14 | Toxicity=0.02 | Relevance=0.07 | LinkThreat=NONE (Resume)', NULL, NULL, '2026-02-24 10:07:19', '2026-03-02 11:04:32'),
(24, 2, 'Data Science Internship – Summer 2026 (Tunis / Remote)', 'Hi everyone,\nI’m currently looking for a summer internship in Data Science or Machine Learning in Tunis (or remote).\nI have experience with Python, pandas, scikit-learn, and basic SQL. I’ve completed a small ML project involving data cleaning and model evaluation.\n\nIf your company is hiring interns, I’d appreciate any details about:\n\nRequired skills\n\nApplication link\n\nRemote/hybrid options\n\nThank you!', '#internship', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.61 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-24 10:32:38', '2026-03-02 11:04:31'),
(25, 2, 'Data Science Internship – Summer 2026 (Tunis / Remote)', 'Hi everyone,\nI’m currently looking for a summer internship in Data Science or Machine Learning in Tunis (or remote).\nI have experience with Python, pandas, scikit-learn, and basic SQL. I’ve completed a small ML project involving data cleaning and model evaluation.\n\nIf your company is hiring interns, I’d appreciate any details about:\n\nRequired skills\n\nApplication link\n\nRemote/hybrid options\n\nThank you!', '#internship', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.61 | LinkThreat=NONE (Internship)', NULL, NULL, '2026-02-24 10:34:43', '2026-03-02 11:04:31'),
(28, 2, 'this site has many good software or media', 'www.1337x.to if u want any service u can find it here', '#service', 'PENDING', 0, 0, 'AI: Duplicate=0.51 | Toxicity=0.02 | Relevance=0.03 | LinkThreat=NONE (Salary)', NULL, NULL, '2026-03-01 16:36:49', '2026-03-02 11:04:31'),
(30, 3, 'great site', '1337x.to best site ever will be great help', '#service', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.03 | Relevance=0.03 | LinkThreat=NONE (Career Advice)', NULL, NULL, '2026-03-02 10:02:54', '2026-03-02 11:05:51'),
(31, 99, 'Urgent', 'Urgent account verification:\nhttps://testsafebrowsing.appspot.com/s/phishing.html', '#Urgent', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.50 | LinkThreat=SOCIAL_ENGINEERING (General)', NULL, NULL, '2026-03-02 19:36:14', '2026-03-02 19:48:41'),
(32, 99, 'tool', 'Check this tool:\nhttps://testsafebrowsing.appspot.com/s/malware.html', '#tool', 'PENDING', 0, 0, 'AI: Duplicate=1.00 | Toxicity=0.01 | Relevance=0.50 | LinkThreat=MALWARE (General)', NULL, NULL, '2026-03-02 19:49:09', '2026-03-02 19:49:13');

-- --------------------------------------------------------

--
-- Table structure for table `joboffer`
--

CREATE TABLE `joboffer` (
  `jobOfferId` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `contractType` enum('CDI','CDD','Internship','Freelance') DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `experienceRequired` int(11) DEFAULT NULL,
  `publicationDate` date DEFAULT NULL,
  `status` enum('Open','Closed') DEFAULT 'Open',
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `joboffer`
--

INSERT INTO `joboffer` (`jobOfferId`, `title`, `description`, `contractType`, `salary`, `location`, `experienceRequired`, `publicationDate`, `status`, `user_id`) VALUES
(4, 'forsa', 'so goood', 'CDI', 2000.00, 'tunis', 2, '2026-02-09', 'Open', 5),
(6, 'forsa', 'hahahhahaha', 'CDI', 4500.00, 'tunis', 2, '2026-02-17', 'Open', 5),
(7, 'hfhhfhfh', 'hfhhfhfhf', 'CDI', 4500.00, 'tunis', 2, '2026-02-25', 'Open', 9),
(8, 'megrine', 'hghghhghgh', 'CDI', 4500.00, 'tunis', 4, '2026-03-02', 'Open', 4),
(9, 'cleaner', 'good job', 'CDD', 1000.00, 'tunis', 2, '2026-03-01', 'Open', 4),
(10, 'hr', 'hfhfhhfhfhfhfh', 'CDD', 4500.00, 'El Guettar, Tunisia', 2, '2026-03-02', 'Open', 4);

-- --------------------------------------------------------

--
-- Table structure for table `onboardingplan`
--

CREATE TABLE `onboardingplan` (
  `planId` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','in_progress','completed','on_hold') NOT NULL DEFAULT 'pending',
  `deadline` date DEFAULT NULL,
  `qr_token` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `onboardingplan`
--

INSERT INTO `onboardingplan` (`planId`, `user_id`, `status`, `deadline`, `qr_token`) VALUES
(25, 7, 'in_progress', '2026-02-23', 'SWplf1E2t_i_1jy1ReO6IiUHwn6FRPRp'),
(27, 4, 'pending', '2026-03-01', 'wOmmUawWTWlNM19NDJkWbTFkfYNSXAUl'),
(29, 3, 'pending', '2026-02-26', 'F3o-eEmMQObxbMAkzr9xGmXvi-xvDpLt'),
(30, 1, 'pending', '2026-02-27', 'IpvU1xCGVLv4ZMZgw4hUJpvFDhP27pOc'),
(31, 3, 'pending', '2026-03-06', 'KGO2HoPR3CR5z5e3F_R2JsZ_hxX8J2wl'),
(32, 5, 'completed', '2026-03-05', '_V-yFXi_tyHgRsmW-XrjdpMkznqyBDHn');

-- --------------------------------------------------------

--
-- Table structure for table `onboardingtask`
--

CREATE TABLE `onboardingtask` (
  `taskId` int(11) NOT NULL,
  `planId` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('not_started','in_progress','completed','blocked','on_hold') NOT NULL DEFAULT 'not_started',
  `deadline` date DEFAULT NULL,
  `filePath` varchar(255) DEFAULT NULL,
  `cloudinary_public_id` varchar(255) DEFAULT NULL,
  `original_file_name` varchar(255) DEFAULT NULL,
  `content_type` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `onboardingtask`
--

INSERT INTO `onboardingtask` (`taskId`, `planId`, `title`, `description`, `status`, `deadline`, `filePath`, `cloudinary_public_id`, `original_file_name`, `content_type`) VALUES
(25, 16, 'samirr', 'ezf', 'not_started', NULL, 'https://res.cloudinary.com/dtkb3tazw/image/upload/v1772327320/hirely/tasks/25/f971efbe-39df-4df9-8afc-36aff9145ae8.png', NULL, NULL, NULL),
(27, 30, 'sc', 'qscsqc', 'in_progress', NULL, 'https://res.cloudinary.com/dtkb3tazw/image/upload/v1772327402/hirely/tasks/27/ea75fdd1-f69e-4c9c-8df0-bab6ce963061.png', 'hirely/tasks/27/ea75fdd1-f69e-4c9c-8df0-bab6ce963061', 'Screenshot 2026-03-01 020916.png', 'image/png'),
(28, 30, 'qsc', 'qscqc', 'on_hold', NULL, 'https://res.cloudinary.com/dtkb3tazw/image/upload/v1772327986/hirely/tasks/28/dafa0af8-15cf-4e94-a968-db63b02e6783.png', 'hirely/tasks/28/dafa0af8-15cf-4e94-a968-db63b02e6783', 'Screenshot 2026-03-01 020916.png', 'image/png'),
(29, 16, 'sdv', 'dsvsv', 'blocked', NULL, NULL, NULL, NULL, NULL),
(31, 25, 'salhoub', 'asdad', 'not_started', NULL, 'https://res.cloudinary.com/dtkb3tazw/image/upload/v1772468406/hirely/onboarding/iazhfxs34frlozrw8sqg.jpg', 'hirely/onboarding/iazhfxs34frlozrw8sqg', 'IMG_0989.JPG', 'image/jpeg'),
(32, 16, 'salhoub', 'ezf', 'not_started', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_otp`
--

CREATE TABLE `password_reset_otp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_code` char(6) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_otp`
--

INSERT INTO `password_reset_otp` (`id`, `user_id`, `otp_code`, `created_at`, `expires_at`, `used`) VALUES
(1, 4, '932411', '2026-03-02 17:42:12', '2026-03-02 17:52:12', 1);

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `role_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `default_dashboard` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `name`, `description`, `status`, `default_dashboard`) VALUES
(1, 'candidate', 'dfbsfjsdbfsdhfbshbdf', 'Active', 'hello'),
(2, 'recruiter', 'The Recruiter is responsible for creating and managing job opportunities, reviewing applications, and selecting candidates for hiring.', 'Active', 'good'),
(3, 'Admin', 'The Administrator is responsible for managing the entire Hirely platform.\nThis role ensures system integrity, user management, and overall application control.', 'Active', 'ddddddddd'),
(999, 'system', 'System/Imported placeholder users', 'active', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `profile_pic` varchar(255) DEFAULT NULL,
  `face_data` longblob DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `role_id`, `status`, `profile_pic`, `face_data`, `google_id`) VALUES
(1, 'Imported', 'User1', 'imported_user1@local', 'imported', 999, 'active', NULL, NULL, NULL),
(2, 'Imported', 'User2', 'imported_user2@local', 'imported', 999, 'active', NULL, NULL, NULL),
(3, 'Imported', 'User3', 'imported_user3@local', 'imported', 999, 'active', NULL, NULL, NULL),
(4, 'souhaib', 'bouallegui', 'boualleguisouhaib@gmail.com', 'samir', 2, 'active', NULL, NULL, '103148277848386129060'),
(5, 'souhaib', 'bouallegui', 'souhaib@gmail.com', '123123', 3, 'active', NULL, 0x2f396a2f34414151536b5a4a5267414241674141415141424141442f327742444141674742676347425167484277634a4351674b4442514e4441734c44426b534577385548526f6648683061484277674a43346e49434973497877634b4463704c4441784e44513048796335505467795043347a4e444c2f7741414c4341426b41475142415245412f38514148774141415155424151454241514541414141414141414141414543417751464267634943516f4c2f3851417452414141674544417749454177554642415141414146394151494441415152425249684d5545474531466842794a7846444b426b61454949304b78775256533066416b4d324a7967676b4b4668635947526f6c4a69636f4b536f304e5459334f446b3651305246526b644953557054564656575631685a576d4e6b5a575a6e61476c7163335231646e643465587144684957476834694a69704b546c4a57576c35695a6d714b6a704b576d7036697071724b7a744c57327437693575734c44784d584778386a4a79744c54314e585731396a5a32754869342b546c3575666f3665727838765030396662332b506e362f396f41434145424141412f414f692b7875583572526873784776705555376c4468633571474c7a476b2b627057675371783746366d6f3434666d796157596268744179616f434239354a4661467047664c4f6568716a6457494d6d3443694e523933754b526f44754741636531585937594642566c6b4b386a7456576134663777464e683333444134344654744467302b4f5063775431705a454558465051414c7570544235677a696d456553655070536d4a70454a72447564384d726451547856327a6d63773764315337794f72567074486c5438745a376f4e32306a46545237496c34787a534d32376764716b6832687752676b552b5642496574435245594855564f4d71704146565a41474e535175414e6f4e5562753257562f6d4847616969574648786e6756484a4a46764f4457384766626731546d514f3155726c32694941714b4334626553336572537a4c47654f7071654a7a4a566c6e3874435231703862677153545764643353525345314444632b61666b504e506b696d6c444148417857624b72514442596e6d72554d4d5478426a3372624d774b2f4a7a56546551334934707374755a6c334b7563566d7a4938626a434872556b73524d61754f6364617657484b3437314a632b59755141667056564a354d6b437163397531314a67486e7255396e614e41636c5457674a5156594564425659785279416c313571654f4e5651415248465374473053314247504d4f5456314346474d63566e58555a655435616149574741527855747544484a6b56596d6350795053716355587a4d534b456a506d456970694741786d6f4a466b5a67452f4770434173655736315461366b444541385673744b4a52787a55586c74487a6a464a357935326e72544842567761615a475a67414b6d415544357574524d6a73654f6c50694956534431715347506378785433557143434b6f7954474d354136385535564d7935363145625442354658556a4b6b556b376b6a41714b4f4d6b35705a464e51427a76414171327135475361694a4f386a50464a4770354f545669324c495353654b6c6b634d4d56424a456a6a746d6b685462774f6c4f5a43577a5467776270543434642f58464d6c51786e696d4b47626b30786c6a4441393643357867455647764c315a6a556844794b62796f786e6d6f70485072554b5049376e4272517468387557484e4e6556517847442b565634517746586f75687073796b39615969345771397975355342317854494c6554487a6331497967484851303164346247616e38736c63393667654d6b6d6b6a684b4f50513166586169385538526f526b675a717242437a41594971394441564757494e4a4e486e6f514b6a574967456d6f576959766b564b71375678566156506e7a6b55355641487651684963357047516b39616377776e765459517a5a7a55705238384d4b74517171696e795442525549486d4872785470564b7078556350504237303934754f4b714e47512b5430706163715a47614e6a656c4e5a57474f4f4b6d51694d6330475253633039447a545a787852626e4a713434796c5a716b69354148544e584b727966644e5244725670414f7453716f49365532525238764865714e783936705555465154582f32513d3d, NULL),
(7, 'samir', 'samir', 'souhaib.bouallegui@esprit.tn', '123123', 1, 'active', NULL, 0x2f396a2f34414151536b5a4a5267414241674141415141424141442f327742444141674742676347425167484277634a4351674b4442514e4441734c44426b534577385548526f6648683061484277674a43346e49434973497877634b4463704c4441784e44513048796335505467795043347a4e444c2f7741414c4341426b41475142415245412f38514148774141415155424151454241514541414141414141414141414543417751464267634943516f4c2f3851417452414141674544417749454177554642415141414146394151494441415152425249684d5545474531466842794a7846444b426b61454949304b78775256533066416b4d324a7967676b4b4668635947526f6c4a69636f4b536f304e5459334f446b3651305246526b644953557054564656575631685a576d4e6b5a575a6e61476c7163335231646e643465587144684957476834694a69704b546c4a57576c35695a6d714b6a704b576d7036697071724b7a744c57327437693575734c44784d584778386a4a79744c54314e585731396a5a32754869342b546c3575666f3665727838765030396662332b506e362f396f41434145424141412f41504f486c41485773363466636574565375615171467068634436314c62326c7a64484555544e373434726f4e4d384a5333546272746a47673638566f4c6f454e76496673344c4266346a7761764a7062716f593570306c7068687878554a6a387331444975526d7178515a726d704a736b3831585a386e725464394e355934484a7266307277387433454a70335941486c4b377a5472434f4f464972534d674164786a4a725774744a5a337a4f7a672b67365670786548347070465552676570785573756978522f4b5647425758646158475156433437316a7936596d3835724e766264596f6d774f6c596a4d5178726a486d7a5464394f6a4453534c476f4a5a6a675632476a364a623236476138356e41796939683961323761533267624a4e613176724e76626b4f44794b367a5264546776724e70534275335a725269756358456a4b654e6f7074316466753834726e37712f436b3536566a54366768596a4e5a4e2f6471305256547961353135506e4e6359446d7046354e62756a57364b786b5a666d374531756561534f70704e2f765368383936362f773763474b784b2b7072657472734c4f6374314171334c65524e45564c444e63376673484441593961357562687a3831554a322b556e4e596a7a4865613573437034563353415630646b7578414b757161526c4c48696e4a473449726f62464a6f62634e6734714b545535556c4f4363696f7a716477656435714b532b6c6b474378716f5862504a717664534259574a394b776a4f704a7248787a5632786933544c5852787867415971634a7854774d565a7464766e7075365a727376744e74396a41554c774b3436375a5775484b394d3141473755754b6a6b4a724f31467a396e624870584e376a6d6d4d75477252307a2f5843756a547055713034697049467a494b31643445525545315261334c7363413073576e7537644b6d6c734367724e6e6a4b7351617a6232457443333072424d4850536f376d505a4a6970374a747367726f59474a4147617552697048413756505a77737a354171383062627542557356724e4977774d412b316164705a50757752567558544d72797736656c637671647573637841484e5a453057564950657378726451783472487646506d45306c6f50337772704c524b326f72556557474e4d57414d2b436132624951774a67344f61756f62636e7457726276626241416f7a54336e69694f5142554d6c36724963567a6d6f6f4a4a39315939326741774b7a4758356a57506552676a4f4b6f516e5a4d445854327367324b525731464c6d444f656653716a75642f424e583763755938354e575953513372577a614b7a41635970397970486571596b774e704e517a62584250657353394179546d73776a4a724e75492f6c785753385a57577461776e55454b5457394449416d44336f56643867413961365377303170495238704f617678365155666b635675576c6769516b6e6a4659657176736b49587057616e7a4850656f333442724976656c5a704e56706c34724a7534384e6d71795465564947485556304e6a6543654d5a507a56733261695364414f75613957385036556b6b61422f6b4147547537317158476b52764a2b34474237383151754c5637654e305070584a5836466d624e5a61686c664a7147566867316b586a5a474b7a794f616a6b4149724d75526e69736d6268367457557270497544337273744e592f624966393456376c6149707359546a42387674555563386763674e306f752f33674a626e69755875346b4c746b566a7a52714e324233724d75464850465931324b6f6b63312f2f5a, NULL),
(8, 'sal', 'sal', 'salhoub@gmail.com', '123123', 1, 'active', NULL, NULL, NULL),
(9, 'samira', 'samira', 'samira@gmail.com', '123123', 1, 'active', NULL, NULL, NULL),
(10, 'samir', 'samir', 'bouallegui@gmail.com', '123123', 1, 'active', NULL, NULL, NULL),
(99, 'Admin', 'Hirely', 'admin@hirely.tn', 'dummy_hash', 3, 'active', NULL, NULL, NULL),
(100, 'Gemini', 'Assistant', 'gemini@hirely.local', 'system_bot', 999, 'active', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`applicationId`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `jobOfferId` (`jobOfferId`);

--
-- Indexes for table `forum_comment`
--
ALTER TABLE `forum_comment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forum_comment_post_created` (`post_id`,`created_at`),
  ADD KEY `idx_forum_comment_author` (`author_id`),
  ADD KEY `idx_forum_comment_created` (`created_at`),
  ADD KEY `fk_forum_comment_edited_by` (`edited_by`);

--
-- Indexes for table `forum_interaction`
--
ALTER TABLE `forum_interaction`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_interaction` (`target_type`,`target_id`,`user_id`,`interaction_type`),
  ADD KEY `idx_fpi_user_type` (`user_id`,`interaction_type`),
  ADD KEY `idx_target` (`target_type`,`target_id`,`interaction_type`),
  ADD KEY `idx_user` (`user_id`,`target_type`,`interaction_type`);

--
-- Indexes for table `forum_notification`
--
ALTER TABLE `forum_notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fn_recipient_read_created` (`recipient_user_id`,`is_read`,`created_at`),
  ADD KEY `idx_fn_post` (`post_id`),
  ADD KEY `idx_fn_comment` (`comment_id`),
  ADD KEY `fk_fn_actor` (`actor_user_id`);

--
-- Indexes for table `forum_post`
--
ALTER TABLE `forum_post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forum_post_feed` (`status`,`is_pinned`,`created_at`),
  ADD KEY `idx_forum_post_author` (`author_id`),
  ADD KEY `idx_forum_post_created` (`created_at`),
  ADD KEY `fk_forum_post_edited_by` (`edited_by`);

--
-- Indexes for table `joboffer`
--
ALTER TABLE `joboffer`
  ADD PRIMARY KEY (`jobOfferId`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `onboardingplan`
--
ALTER TABLE `onboardingplan`
  ADD PRIMARY KEY (`planId`),
  ADD KEY `userId` (`user_id`);

--
-- Indexes for table `onboardingtask`
--
ALTER TABLE `onboardingtask`
  ADD PRIMARY KEY (`taskId`),
  ADD KEY `planId` (`planId`);

--
-- Indexes for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_otp_user` (`user_id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_users_google_id` (`google_id`),
  ADD KEY `fk_users_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `applicationId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `forum_comment`
--
ALTER TABLE `forum_comment`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `forum_interaction`
--
ALTER TABLE `forum_interaction`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `forum_notification`
--
ALTER TABLE `forum_notification`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `forum_post`
--
ALTER TABLE `forum_post`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `joboffer`
--
ALTER TABLE `joboffer`
  MODIFY `jobOfferId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `onboardingplan`
--
ALTER TABLE `onboardingplan`
  MODIFY `planId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `onboardingtask`
--
ALTER TABLE `onboardingtask`
  MODIFY `taskId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1009;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `application`
--
ALTER TABLE `application`
  ADD CONSTRAINT `application_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `application_ibfk_2` FOREIGN KEY (`jobOfferId`) REFERENCES `joboffer` (`jobOfferId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `forum_comment`
--
ALTER TABLE `forum_comment`
  ADD CONSTRAINT `fk_forum_comment_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_forum_comment_edited_by` FOREIGN KEY (`edited_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_forum_comment_post` FOREIGN KEY (`post_id`) REFERENCES `forum_post` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_interaction`
--
ALTER TABLE `forum_interaction`
  ADD CONSTRAINT `fk_forum_interaction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_notification`
--
ALTER TABLE `forum_notification`
  ADD CONSTRAINT `fk_fn_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fn_comment` FOREIGN KEY (`comment_id`) REFERENCES `forum_comment` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fn_post` FOREIGN KEY (`post_id`) REFERENCES `forum_post` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fn_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_post`
--
ALTER TABLE `forum_post`
  ADD CONSTRAINT `fk_forum_post_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_forum_post_edited_by` FOREIGN KEY (`edited_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `joboffer`
--
ALTER TABLE `joboffer`
  ADD CONSTRAINT `joboffer_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  ADD CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
