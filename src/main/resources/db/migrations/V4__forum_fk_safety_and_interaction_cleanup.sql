-- Make role deletion safer: keep users and null their role instead of deleting accounts.
ALTER TABLE `users`
  DROP FOREIGN KEY `fk_users_role`;

ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role`
  FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

-- Allow deleting a user without breaking/deleting forum history.
ALTER TABLE `forum_post`
  MODIFY `author_id` int(11) DEFAULT NULL;

ALTER TABLE `forum_post`
  DROP FOREIGN KEY `fk_forum_post_author`,
  DROP FOREIGN KEY `fk_forum_post_edited_by`;

ALTER TABLE `forum_post`
  ADD CONSTRAINT `fk_forum_post_author`
    FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_forum_post_edited_by`
    FOREIGN KEY (`edited_by`) REFERENCES `users` (`user_id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

ALTER TABLE `forum_comment`
  MODIFY `author_id` int(11) DEFAULT NULL;

ALTER TABLE `forum_comment`
  DROP FOREIGN KEY `fk_forum_comment_author`,
  DROP FOREIGN KEY `fk_forum_comment_edited_by`;

ALTER TABLE `forum_comment`
  ADD CONSTRAINT `fk_forum_comment_author`
    FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_forum_comment_edited_by`
    FOREIGN KEY (`edited_by`) REFERENCES `users` (`user_id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- forum_interaction is polymorphic (POST/COMMENT), so enforce cleanup with triggers.
DROP TRIGGER IF EXISTS `trg_forum_post_cleanup_interactions`;
CREATE TRIGGER `trg_forum_post_cleanup_interactions`
AFTER DELETE ON `forum_post`
FOR EACH ROW
DELETE FROM `forum_interaction`
WHERE `target_type` = 'POST' AND `target_id` = OLD.`id`;

DROP TRIGGER IF EXISTS `trg_forum_comment_cleanup_interactions`;
CREATE TRIGGER `trg_forum_comment_cleanup_interactions`
AFTER DELETE ON `forum_comment`
FOR EACH ROW
DELETE FROM `forum_interaction`
WHERE `target_type` = 'COMMENT' AND `target_id` = OLD.`id`;
