<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407142041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // First, drop the foreign key from interviews table that references application.applicationId
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY IF EXISTS fk_int_application');
        
        // Now we can alter the application table
        $this->addSql('ALTER TABLE application ADD application_id INT NOT NULL, ADD application_date DATE NOT NULL, ADD cover_letter LONGTEXT NOT NULL, ADD current_status VARCHAR(50) NOT NULL, ADD resume_path VARCHAR(255) NOT NULL, ADD last_update_date DATETIME NOT NULL, ADD expected_salary DOUBLE PRECISION NOT NULL, ADD availability_date DATE NOT NULL, ADD experience_years INT NOT NULL, ADD portfolio_url VARCHAR(255) NOT NULL, ADD review_note LONGTEXT NOT NULL, DROP applicationId, DROP applicationDate, DROP coverLetter, DROP currentStatus, DROP resumePath, DROP lastUpdateDate, DROP expectedSalary, DROP availabilityDate, DROP experienceYears, DROP portfolioUrl, DROP reviewNote, CHANGE phone phone VARCHAR(50) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE score score DOUBLE PRECISION NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (application_id)');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC17E2E9444 FOREIGN KEY (jobOfferId) REFERENCES joboffer (jobOfferId) ON DELETE CASCADE');
        $this->addSql('DROP INDEX user_id ON application');
        $this->addSql('CREATE INDEX IDX_A45BDDC1A76ED395 ON application (user_id)');
        $this->addSql('DROP INDEX jobofferid ON application');
        $this->addSql('CREATE INDEX IDX_A45BDDC17E2E9444 ON application (jobOfferId)');
        
        // Recreate the foreign key in interviews table (now pointing to application_id)
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT fk_int_application FOREIGN KEY (application_id) REFERENCES application (application_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_scores DROP FOREIGN KEY fk_score_criteria');
        $this->addSql('ALTER TABLE evaluation_scores DROP FOREIGN KEY fk_score_criteria');
        $this->addSql('ALTER TABLE evaluation_scores DROP FOREIGN KEY fk_score_evaluation');
        $this->addSql('ALTER TABLE evaluation_scores CHANGE score_id score_id INT NOT NULL, CHANGE evaluation_id evaluation_id INT DEFAULT NULL, CHANGE criteria_id criteria_id INT DEFAULT NULL, CHANGE score score INT NOT NULL, CHANGE comments comments LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE evaluation_scores ADD CONSTRAINT FK_CD2F02A9990BEA15 FOREIGN KEY (criteria_id) REFERENCES evaluation_criteria (criteria_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_score_evaluation ON evaluation_scores');
        $this->addSql('CREATE INDEX IDX_CD2F02A9456C5646 ON evaluation_scores (evaluation_id)');
        $this->addSql('DROP INDEX fk_score_criteria ON evaluation_scores');
        $this->addSql('CREATE INDEX IDX_CD2F02A9990BEA15 ON evaluation_scores (criteria_id)');
        $this->addSql('ALTER TABLE evaluation_scores ADD CONSTRAINT fk_score_criteria FOREIGN KEY (criteria_id) REFERENCES evaluation_criteria (criteria_id)');
        $this->addSql('ALTER TABLE evaluation_scores ADD CONSTRAINT fk_score_evaluation FOREIGN KEY (evaluation_id) REFERENCES interview_evaluations (evaluation_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_comment CHANGE id id BIGINT NOT NULL, CHANGE content content LONGTEXT NOT NULL, CHANGE status status VARCHAR(20) NOT NULL, CHANGE moderation_note moderation_note LONGTEXT NOT NULL, CHANGE edited_at edited_at DATETIME NOT NULL, CHANGE edited_by edited_by INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE is_pinned is_pinned TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE forum_interaction CHANGE id id BIGINT NOT NULL, CHANGE target_type target_type VARCHAR(255) NOT NULL, CHANGE interaction_type interaction_type VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE forum_notification CHANGE id id BIGINT NOT NULL, CHANGE actor_user_id actor_user_id INT NOT NULL, CHANGE type type VARCHAR(255) NOT NULL, CHANGE post_id post_id BIGINT NOT NULL, CHANGE comment_id comment_id BIGINT NOT NULL, CHANGE is_read is_read TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE forum_post CHANGE id id BIGINT NOT NULL, CHANGE content content LONGTEXT NOT NULL, CHANGE tag tag VARCHAR(100) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL, CHANGE is_pinned is_pinned TINYINT(1) NOT NULL, CHANGE is_locked is_locked TINYINT(1) NOT NULL, CHANGE moderation_note moderation_note LONGTEXT NOT NULL, CHANGE edited_at edited_at DATETIME NOT NULL, CHANGE edited_by edited_by INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE interview_evaluations DROP FOREIGN KEY fk_eval_recruiter');
        $this->addSql('ALTER TABLE interview_evaluations DROP FOREIGN KEY fk_eval_interview');
        $this->addSql('ALTER TABLE interview_evaluations DROP FOREIGN KEY fk_eval_recruiter');
        $this->addSql('ALTER TABLE interview_evaluations CHANGE evaluation_id evaluation_id INT NOT NULL, CHANGE interview_id interview_id INT DEFAULT NULL, CHANGE recruiter_id recruiter_id INT DEFAULT NULL, CHANGE overall_rating overall_rating DOUBLE PRECISION NOT NULL, CHANGE recommendation recommendation VARCHAR(255) NOT NULL, CHANGE strengths strengths LONGTEXT NOT NULL, CHANGE weaknesses weaknesses LONGTEXT NOT NULL, CHANGE general_comments general_comments LONGTEXT NOT NULL, CHANGE hire_decision hire_decision VARCHAR(100) NOT NULL, CHANGE next_steps next_steps LONGTEXT NOT NULL, CHANGE is_draft is_draft TINYINT(1) NOT NULL, CHANGE evaluated_at evaluated_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE interview_evaluations ADD CONSTRAINT FK_E5BEC982156BE243 FOREIGN KEY (recruiter_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_eval_interview ON interview_evaluations');
        $this->addSql('CREATE INDEX IDX_E5BEC98255D69D95 ON interview_evaluations (interview_id)');
        $this->addSql('DROP INDEX fk_eval_recruiter ON interview_evaluations');
        $this->addSql('CREATE INDEX IDX_E5BEC982156BE243 ON interview_evaluations (recruiter_id)');
        $this->addSql('ALTER TABLE interview_evaluations ADD CONSTRAINT fk_eval_interview FOREIGN KEY (interview_id) REFERENCES interviews (interview_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interview_evaluations ADD CONSTRAINT fk_eval_recruiter FOREIGN KEY (recruiter_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE interview_types CHANGE interview_type_id interview_type_id INT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE typical_duration_minutes typical_duration_minutes INT NOT NULL, CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE interviewee_profiles DROP INDEX uq_interviewee_user, ADD INDEX IDX_2A463AC9A76ED395 (user_id)');
        $this->addSql('ALTER TABLE interviewee_profiles CHANGE interviewee_id interviewee_id INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE first_name first_name VARCHAR(100) NOT NULL, CHANGE last_name last_name VARCHAR(100) NOT NULL, CHANGE skills skills LONGTEXT NOT NULL, CHANGE phone phone VARCHAR(20) NOT NULL, CHANGE linkedin_url linkedin_url VARCHAR(255) NOT NULL, CHANGE portfolio_url portfolio_url VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY fk_int_recruiter');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY fk_int_type');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY fk_int_interviewee');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY fk_int_recruiter');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY fk_int_application');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY fk_int_type');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY fk_int_interviewee');
        $this->addSql('ALTER TABLE interviews CHANGE interview_id interview_id INT NOT NULL, CHANGE application_id application_id INT DEFAULT NULL, CHANGE recruiter_id recruiter_id INT DEFAULT NULL, CHANGE interviewee_id interviewee_id INT DEFAULT NULL, CHANGE interview_type_id interview_type_id INT DEFAULT NULL, CHANGE scheduled_time scheduled_time VARCHAR(255) NOT NULL, CHANGE duration_minutes duration_minutes INT NOT NULL, CHANGE location location VARCHAR(255) NOT NULL, CHANGE meeting_link meeting_link VARCHAR(500) NOT NULL, CHANGE status status VARCHAR(30) NOT NULL, CHANGE interview_round interview_round INT NOT NULL, CHANGE notes notes LONGTEXT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT FK_3A752682156BE243 FOREIGN KEY (recruiter_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT FK_3A752682B4C8B6CE FOREIGN KEY (interviewee_id) REFERENCES interviewee_profiles (interviewee_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT FK_3A752682B4D9100 FOREIGN KEY (interview_type_id) REFERENCES interview_types (interview_type_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_int_application ON interviews');
        $this->addSql('CREATE INDEX IDX_3A7526823E030ACD ON interviews (application_id)');
        $this->addSql('DROP INDEX fk_int_recruiter ON interviews');
        $this->addSql('CREATE INDEX IDX_3A752682156BE243 ON interviews (recruiter_id)');
        $this->addSql('DROP INDEX fk_int_interviewee ON interviews');
        $this->addSql('CREATE INDEX IDX_3A752682B4C8B6CE ON interviews (interviewee_id)');
        $this->addSql('DROP INDEX fk_int_type ON interviews');
        $this->addSql('CREATE INDEX IDX_3A752682B4D9100 ON interviews (interview_type_id)');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT fk_int_recruiter FOREIGN KEY (recruiter_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT fk_int_application FOREIGN KEY (application_id) REFERENCES application (applicationId) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT fk_int_type FOREIGN KEY (interview_type_id) REFERENCES interview_types (interview_type_id)');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT fk_int_interviewee FOREIGN KEY (interviewee_id) REFERENCES interviewee_profiles (interviewee_id)');
        $this->addSql('ALTER TABLE joboffer DROP FOREIGN KEY joboffer_ibfk_1');
        $this->addSql('ALTER TABLE joboffer DROP FOREIGN KEY joboffer_ibfk_1');
        $this->addSql('ALTER TABLE joboffer ADD job_offer_id INT NOT NULL, ADD contract_type VARCHAR(255) NOT NULL, ADD experience_required INT NOT NULL, ADD publication_date DATE NOT NULL, DROP jobOfferId, DROP contractType, DROP experienceRequired, DROP publicationDate, CHANGE title title VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE salary salary DOUBLE PRECISION NOT NULL, CHANGE location location VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (job_offer_id)');
        $this->addSql('ALTER TABLE joboffer ADD CONSTRAINT FK_F33F8164A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX user_id ON joboffer');
        $this->addSql('CREATE INDEX IDX_F33F8164A76ED395 ON joboffer (user_id)');
        $this->addSql('ALTER TABLE joboffer ADD CONSTRAINT joboffer_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY fk_notif_interview');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY fk_notif_interview');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY fk_notif_user');
        $this->addSql('ALTER TABLE notifications CHANGE notification_id notification_id INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE message message LONGTEXT NOT NULL, CHANGE is_read is_read TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE read_at read_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D355D69D95 FOREIGN KEY (interview_id) REFERENCES interviews (interview_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_notif_user ON notifications');
        $this->addSql('CREATE INDEX IDX_6000B0D3A76ED395 ON notifications (user_id)');
        $this->addSql('DROP INDEX fk_notif_interview ON notifications');
        $this->addSql('CREATE INDEX IDX_6000B0D355D69D95 ON notifications (interview_id)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT fk_notif_interview FOREIGN KEY (interview_id) REFERENCES interviews (interview_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX userId ON onboardingplan');
        $this->addSql('ALTER TABLE onboardingplan ADD plan_id INT NOT NULL, DROP planId, CHANGE status status VARCHAR(255) NOT NULL, CHANGE deadline deadline DATE NOT NULL, CHANGE qr_token qr_token VARCHAR(80) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (plan_id)');
        $this->addSql('DROP INDEX planId ON onboardingtask');
        $this->addSql('ALTER TABLE onboardingtask ADD plan_id INT NOT NULL, ADD file_path VARCHAR(255) NOT NULL, DROP taskId, DROP filePath, CHANGE title title VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE status status VARCHAR(255) NOT NULL, CHANGE deadline deadline DATE NOT NULL, CHANGE cloudinary_public_id cloudinary_public_id VARCHAR(255) NOT NULL, CHANGE original_file_name original_file_name VARCHAR(255) NOT NULL, CHANGE content_type content_type VARCHAR(120) NOT NULL, CHANGE planId task_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (task_id)');
        $this->addSql('ALTER TABLE password_reset_otp DROP FOREIGN KEY fk_otp_user');
        $this->addSql('ALTER TABLE password_reset_otp CHANGE id id INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE otp_code otp_code VARCHAR(6) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE used used TINYINT(1) NOT NULL');
        $this->addSql('DROP INDEX fk_otp_user ON password_reset_otp');
        $this->addSql('CREATE INDEX IDX_79FB4877A76ED395 ON password_reset_otp (user_id)');
        $this->addSql('ALTER TABLE password_reset_otp ADD CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recruiter_profiles DROP INDEX uq_recruiter_user, ADD INDEX IDX_C29FD578A76ED395 (user_id)');
        $this->addSql('ALTER TABLE recruiter_profiles CHANGE recruiter_id recruiter_id INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE first_name first_name VARCHAR(100) NOT NULL, CHANGE last_name last_name VARCHAR(100) NOT NULL, CHANGE department department VARCHAR(100) NOT NULL, CHANGE phone phone VARCHAR(20) NOT NULL');
        $this->addSql('DROP INDEX name ON role');
        $this->addSql('ALTER TABLE role CHANGE role_id role_id INT NOT NULL, CHANGE description description VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL, CHANGE default_dashboard default_dashboard VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY fk_users_role');
        $this->addSql('DROP INDEX email ON users');
        $this->addSql('DROP INDEX idx_users_google_id ON users');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY fk_users_role');
        $this->addSql('ALTER TABLE users CHANGE user_id user_id INT NOT NULL, CHANGE first_name first_name VARCHAR(100) NOT NULL, CHANGE last_name last_name VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE password password VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL, CHANGE profile_pic profile_pic VARCHAR(255) NOT NULL, CHANGE face_data face_data VARCHAR(255) NOT NULL, CHANGE google_id google_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9D60322AC FOREIGN KEY (role_id) REFERENCES role (role_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_users_role ON users');
        $this->addSql('CREATE INDEX IDX_1483A5E9D60322AC ON users (role_id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES role (role_id) ON UPDATE CASCADE ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1A76ED395');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC17E2E9444');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1A76ED395');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC17E2E9444');
        $this->addSql('ALTER TABLE application ADD applicationId INT AUTO_INCREMENT NOT NULL, ADD applicationDate DATE DEFAULT NULL, ADD coverLetter TEXT DEFAULT NULL, ADD currentStatus VARCHAR(50) DEFAULT \'Pending\', ADD resumePath VARCHAR(255) DEFAULT NULL, ADD lastUpdateDate DATETIME DEFAULT CURRENT_TIMESTAMP, ADD expectedSalary DOUBLE PRECISION DEFAULT NULL, ADD availabilityDate DATE DEFAULT NULL, ADD experienceYears INT DEFAULT NULL, ADD portfolioUrl VARCHAR(255) DEFAULT NULL, ADD reviewNote TEXT DEFAULT NULL, DROP application_id, DROP application_date, DROP cover_letter, DROP current_status, DROP resume_path, DROP last_update_date, DROP expected_salary, DROP availability_date, DROP experience_years, DROP portfolio_url, DROP review_note, CHANGE phone phone VARCHAR(50) DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE score score DOUBLE PRECISION DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (applicationId)');
        $this->addSql('DROP INDEX idx_a45bddc1a76ed395 ON application');
        $this->addSql('CREATE INDEX user_id ON application (user_id)');
        $this->addSql('DROP INDEX idx_a45bddc17e2e9444 ON application');
        $this->addSql('CREATE INDEX jobOfferId ON application (jobOfferId)');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC17E2E9444 FOREIGN KEY (jobOfferId) REFERENCES joboffer (jobOfferId) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_criteria CHANGE criteria_id criteria_id INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE max_score max_score INT DEFAULT 10 NOT NULL, CHANGE weight weight DOUBLE PRECISION DEFAULT \'1\' NOT NULL, CHANGE category category VARCHAR(100) DEFAULT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL, CHANGE display_order display_order INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE evaluation_scores DROP FOREIGN KEY FK_CD2F02A9990BEA15');
        $this->addSql('ALTER TABLE evaluation_scores DROP FOREIGN KEY FK_CD2F02A9456C5646');
        $this->addSql('ALTER TABLE evaluation_scores DROP FOREIGN KEY FK_CD2F02A9990BEA15');
        $this->addSql('ALTER TABLE evaluation_scores CHANGE score_id score_id INT AUTO_INCREMENT NOT NULL, CHANGE score score INT DEFAULT 0 NOT NULL, CHANGE comments comments TEXT DEFAULT NULL, CHANGE evaluation_id evaluation_id INT NOT NULL, CHANGE criteria_id criteria_id INT NOT NULL');
        $this->addSql('ALTER TABLE evaluation_scores ADD CONSTRAINT fk_score_criteria FOREIGN KEY (criteria_id) REFERENCES evaluation_criteria (criteria_id)');
        $this->addSql('DROP INDEX idx_cd2f02a9990bea15 ON evaluation_scores');
        $this->addSql('CREATE INDEX fk_score_criteria ON evaluation_scores (criteria_id)');
        $this->addSql('DROP INDEX idx_cd2f02a9456c5646 ON evaluation_scores');
        $this->addSql('CREATE INDEX fk_score_evaluation ON evaluation_scores (evaluation_id)');
        $this->addSql('ALTER TABLE evaluation_scores ADD CONSTRAINT FK_CD2F02A9456C5646 FOREIGN KEY (evaluation_id) REFERENCES interview_evaluations (evaluation_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_scores ADD CONSTRAINT FK_CD2F02A9990BEA15 FOREIGN KEY (criteria_id) REFERENCES evaluation_criteria (criteria_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_comment CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE content content TEXT NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'PENDING\' NOT NULL, CHANGE moderation_note moderation_note TEXT DEFAULT NULL, CHANGE edited_at edited_at DATETIME DEFAULT NULL, CHANGE edited_by edited_by INT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE is_pinned is_pinned TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE forum_interaction CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE target_type target_type ENUM(\'POST\', \'COMMENT\') DEFAULT \'POST\' NOT NULL, CHANGE interaction_type interaction_type ENUM(\'LIKE\') NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE forum_notification CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE actor_user_id actor_user_id INT DEFAULT NULL, CHANGE type type ENUM(\'POST_LIKED\', \'COMMENT_LIKED\', \'COMMENT_ADDED\', \'POST_COMMENTED\', \'POST_STATUS_CHANGED\') NOT NULL, CHANGE post_id post_id BIGINT DEFAULT NULL, CHANGE comment_id comment_id BIGINT DEFAULT NULL, CHANGE is_read is_read TINYINT(1) DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE forum_post CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE content content TEXT NOT NULL, CHANGE tag tag VARCHAR(100) DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'PENDING\' NOT NULL, CHANGE is_pinned is_pinned TINYINT(1) DEFAULT 0 NOT NULL, CHANGE is_locked is_locked TINYINT(1) DEFAULT 0 NOT NULL, CHANGE moderation_note moderation_note TEXT DEFAULT NULL, CHANGE edited_at edited_at DATETIME DEFAULT NULL, CHANGE edited_by edited_by INT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE interviewee_profiles DROP INDEX IDX_2A463AC9A76ED395, ADD UNIQUE INDEX uq_interviewee_user (user_id)');
        $this->addSql('ALTER TABLE interviewee_profiles CHANGE interviewee_id interviewee_id INT AUTO_INCREMENT NOT NULL, CHANGE first_name first_name VARCHAR(100) DEFAULT \'\' NOT NULL, CHANGE last_name last_name VARCHAR(100) DEFAULT \'\' NOT NULL, CHANGE skills skills TEXT DEFAULT NULL, CHANGE phone phone VARCHAR(20) DEFAULT NULL, CHANGE linkedin_url linkedin_url VARCHAR(255) DEFAULT NULL, CHANGE portfolio_url portfolio_url VARCHAR(255) DEFAULT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY FK_3A752682156BE243');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY FK_3A752682B4C8B6CE');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY FK_3A752682B4D9100');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY FK_3A7526823E030ACD');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY FK_3A752682156BE243');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY FK_3A752682B4C8B6CE');
        $this->addSql('ALTER TABLE interviews DROP FOREIGN KEY FK_3A752682B4D9100');
        $this->addSql('ALTER TABLE interviews CHANGE interview_id interview_id INT AUTO_INCREMENT NOT NULL, CHANGE scheduled_time scheduled_time TIME NOT NULL, CHANGE duration_minutes duration_minutes INT DEFAULT 60 NOT NULL, CHANGE location location VARCHAR(255) DEFAULT NULL, CHANGE meeting_link meeting_link VARCHAR(500) DEFAULT NULL, CHANGE status status VARCHAR(30) DEFAULT \'SCHEDULED\' NOT NULL, CHANGE interview_round interview_round INT DEFAULT 1 NOT NULL, CHANGE notes notes TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE application_id application_id INT NOT NULL, CHANGE recruiter_id recruiter_id INT NOT NULL, CHANGE interviewee_id interviewee_id INT NOT NULL, CHANGE interview_type_id interview_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT fk_int_recruiter FOREIGN KEY (recruiter_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT fk_int_type FOREIGN KEY (interview_type_id) REFERENCES interview_types (interview_type_id)');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT fk_int_interviewee FOREIGN KEY (interviewee_id) REFERENCES interviewee_profiles (interviewee_id)');
        $this->addSql('DROP INDEX idx_3a752682b4c8b6ce ON interviews');
        $this->addSql('CREATE INDEX fk_int_interviewee ON interviews (interviewee_id)');
        $this->addSql('DROP INDEX idx_3a752682b4d9100 ON interviews');
        $this->addSql('CREATE INDEX fk_int_type ON interviews (interview_type_id)');
        $this->addSql('DROP INDEX idx_3a7526823e030acd ON interviews');
        $this->addSql('CREATE INDEX fk_int_application ON interviews (application_id)');
        $this->addSql('DROP INDEX idx_3a752682156be243 ON interviews');
        $this->addSql('CREATE INDEX fk_int_recruiter ON interviews (recruiter_id)');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT FK_3A7526823E030ACD FOREIGN KEY (application_id) REFERENCES application (applicationId) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT FK_3A752682156BE243 FOREIGN KEY (recruiter_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT FK_3A752682B4C8B6CE FOREIGN KEY (interviewee_id) REFERENCES interviewee_profiles (interviewee_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interviews ADD CONSTRAINT FK_3A752682B4D9100 FOREIGN KEY (interview_type_id) REFERENCES interview_types (interview_type_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interview_evaluations DROP FOREIGN KEY FK_E5BEC982156BE243');
        $this->addSql('ALTER TABLE interview_evaluations DROP FOREIGN KEY FK_E5BEC98255D69D95');
        $this->addSql('ALTER TABLE interview_evaluations DROP FOREIGN KEY FK_E5BEC982156BE243');
        $this->addSql('ALTER TABLE interview_evaluations CHANGE evaluation_id evaluation_id INT AUTO_INCREMENT NOT NULL, CHANGE overall_rating overall_rating DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE recommendation recommendation VARCHAR(255) DEFAULT NULL, CHANGE strengths strengths TEXT DEFAULT NULL, CHANGE weaknesses weaknesses TEXT DEFAULT NULL, CHANGE general_comments general_comments TEXT DEFAULT NULL, CHANGE hire_decision hire_decision VARCHAR(100) DEFAULT NULL, CHANGE next_steps next_steps TEXT DEFAULT NULL, CHANGE is_draft is_draft TINYINT(1) DEFAULT 0 NOT NULL, CHANGE evaluated_at evaluated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE interview_id interview_id INT NOT NULL, CHANGE recruiter_id recruiter_id INT NOT NULL');
        $this->addSql('ALTER TABLE interview_evaluations ADD CONSTRAINT fk_eval_recruiter FOREIGN KEY (recruiter_id) REFERENCES users (user_id)');
        $this->addSql('DROP INDEX idx_e5bec98255d69d95 ON interview_evaluations');
        $this->addSql('CREATE INDEX fk_eval_interview ON interview_evaluations (interview_id)');
        $this->addSql('DROP INDEX idx_e5bec982156be243 ON interview_evaluations');
        $this->addSql('CREATE INDEX fk_eval_recruiter ON interview_evaluations (recruiter_id)');
        $this->addSql('ALTER TABLE interview_evaluations ADD CONSTRAINT FK_E5BEC98255D69D95 FOREIGN KEY (interview_id) REFERENCES interviews (interview_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interview_evaluations ADD CONSTRAINT FK_E5BEC982156BE243 FOREIGN KEY (recruiter_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interview_types CHANGE interview_type_id interview_type_id INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE typical_duration_minutes typical_duration_minutes INT DEFAULT 60 NOT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE joboffer DROP FOREIGN KEY FK_F33F8164A76ED395');
        $this->addSql('ALTER TABLE joboffer DROP FOREIGN KEY FK_F33F8164A76ED395');
        $this->addSql('ALTER TABLE joboffer ADD jobOfferId INT AUTO_INCREMENT NOT NULL, ADD contractType ENUM(\'CDI\', \'CDD\', \'Internship\', \'Freelance\') DEFAULT NULL, ADD experienceRequired INT DEFAULT NULL, ADD publicationDate DATE DEFAULT NULL, DROP job_offer_id, DROP contract_type, DROP experience_required, DROP publication_date, CHANGE title title VARCHAR(255) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE salary salary NUMERIC(10, 2) DEFAULT NULL, CHANGE location location VARCHAR(255) DEFAULT NULL, CHANGE status status ENUM(\'Open\', \'Closed\') DEFAULT \'Open\', DROP PRIMARY KEY, ADD PRIMARY KEY (jobOfferId)');
        $this->addSql('ALTER TABLE joboffer ADD CONSTRAINT joboffer_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_f33f8164a76ed395 ON joboffer');
        $this->addSql('CREATE INDEX user_id ON joboffer (user_id)');
        $this->addSql('ALTER TABLE joboffer ADD CONSTRAINT FK_F33F8164A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D355D69D95');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3A76ED395');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D355D69D95');
        $this->addSql('ALTER TABLE notifications CHANGE notification_id notification_id INT AUTO_INCREMENT NOT NULL, CHANGE message message TEXT NOT NULL, CHANGE is_read is_read TINYINT(1) DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE read_at read_at DATETIME DEFAULT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT fk_notif_interview FOREIGN KEY (interview_id) REFERENCES interviews (interview_id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_6000b0d3a76ed395 ON notifications');
        $this->addSql('CREATE INDEX fk_notif_user ON notifications (user_id)');
        $this->addSql('DROP INDEX idx_6000b0d355d69d95 ON notifications');
        $this->addSql('CREATE INDEX fk_notif_interview ON notifications (interview_id)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D355D69D95 FOREIGN KEY (interview_id) REFERENCES interviews (interview_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE onboardingplan ADD planId INT AUTO_INCREMENT NOT NULL, DROP plan_id, CHANGE status status ENUM(\'pending\', \'in_progress\', \'completed\', \'on_hold\') DEFAULT \'pending\' NOT NULL, CHANGE deadline deadline DATE DEFAULT NULL, CHANGE qr_token qr_token VARCHAR(80) DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (planId)');
        $this->addSql('CREATE INDEX userId ON onboardingplan (user_id)');
        $this->addSql('ALTER TABLE onboardingtask ADD taskId INT AUTO_INCREMENT NOT NULL, ADD planId INT NOT NULL, ADD filePath VARCHAR(255) DEFAULT NULL, DROP task_id, DROP plan_id, DROP file_path, CHANGE title title VARCHAR(255) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE status status ENUM(\'not_started\', \'in_progress\', \'completed\', \'blocked\', \'on_hold\') DEFAULT \'not_started\' NOT NULL, CHANGE deadline deadline DATE DEFAULT NULL, CHANGE cloudinary_public_id cloudinary_public_id VARCHAR(255) DEFAULT NULL, CHANGE original_file_name original_file_name VARCHAR(255) DEFAULT NULL, CHANGE content_type content_type VARCHAR(120) DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (taskId)');
        $this->addSql('CREATE INDEX planId ON onboardingtask (planId)');
        $this->addSql('ALTER TABLE password_reset_otp DROP FOREIGN KEY FK_79FB4877A76ED395');
        $this->addSql('ALTER TABLE password_reset_otp CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE otp_code otp_code CHAR(6) NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE used used TINYINT(1) DEFAULT 0 NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('DROP INDEX idx_79fb4877a76ed395 ON password_reset_otp');
        $this->addSql('CREATE INDEX fk_otp_user ON password_reset_otp (user_id)');
        $this->addSql('ALTER TABLE password_reset_otp ADD CONSTRAINT FK_79FB4877A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recruiter_profiles DROP INDEX IDX_C29FD578A76ED395, ADD UNIQUE INDEX uq_recruiter_user (user_id)');
        $this->addSql('ALTER TABLE recruiter_profiles CHANGE recruiter_id recruiter_id INT AUTO_INCREMENT NOT NULL, CHANGE first_name first_name VARCHAR(100) DEFAULT \'\' NOT NULL, CHANGE last_name last_name VARCHAR(100) DEFAULT \'\' NOT NULL, CHANGE department department VARCHAR(100) DEFAULT NULL, CHANGE phone phone VARCHAR(20) DEFAULT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE role CHANGE role_id role_id INT AUTO_INCREMENT NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'active\' NOT NULL, CHANGE default_dashboard default_dashboard VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX name ON role (name)');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9D60322AC');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9D60322AC');
        $this->addSql('ALTER TABLE users CHANGE user_id user_id INT AUTO_INCREMENT NOT NULL, CHANGE first_name first_name VARCHAR(100) DEFAULT NULL, CHANGE last_name last_name VARCHAR(100) DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'active\', CHANGE profile_pic profile_pic VARCHAR(255) DEFAULT NULL, CHANGE face_data face_data LONGBLOB DEFAULT NULL, CHANGE google_id google_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES role (role_id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX email ON users (email)');
        $this->addSql('CREATE UNIQUE INDEX idx_users_google_id ON users (google_id)');
        $this->addSql('DROP INDEX idx_1483a5e9d60322ac ON users');
        $this->addSql('CREATE INDEX fk_users_role ON users (role_id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9D60322AC FOREIGN KEY (role_id) REFERENCES role (role_id) ON DELETE CASCADE');
    }
}
