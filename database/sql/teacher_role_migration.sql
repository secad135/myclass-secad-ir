-- =====================================================================
-- myclass — SQL استقرار نقش معلم + سخت‌سازی آزمون
-- برای اجرا در phpMyAdmin (هاست بدون ترمینال)
--
-- ترتیب اجرا:
--   1) از دیتابیس Backup کامل بگیر.
--   2) این فایل را یک‌بار روی دیتابیس اصلی اجرا کن.
--   3) سپس کد را Pull کن و cache را پاک کن.
--
-- این فایل idempotent است: اجرای دوباره خطا نمی‌دهد و ستون/جدول موجود
-- را دوباره نمی‌سازد. نیازی به اجرای دستی بخش‌ها نیست.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- رویه کمکی: افزودن ستون فقط در صورت نبودن
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `myclass_add_column`;
DELIMITER $$
CREATE PROCEDURE `myclass_add_column`(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- ---------------------------------------------------------------------
-- رویه کمکی: افزودن کلید خارجی فقط در صورت نبودن
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `myclass_add_fk`;
DELIMITER $$
CREATE PROCEDURE `myclass_add_fk`(
    IN p_table VARCHAR(64),
    IN p_constraint VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_ref_table VARCHAR(64),
    IN p_ref_column VARCHAR(64)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND CONSTRAINT_NAME = p_constraint
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ) THEN
        SET @ddl = CONCAT(
            'ALTER TABLE `', p_table, '` ADD CONSTRAINT `', p_constraint,
            '` FOREIGN KEY (`', p_column, '`) REFERENCES `', p_ref_table,
            '` (`', p_ref_column, '`) ON DELETE SET NULL'
        );
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- =====================================================================
-- ۱) فاز نقش معلم
-- =====================================================================

-- class_subjects.teacher_id
CALL myclass_add_column('class_subjects', 'teacher_id', 'BIGINT UNSIGNED NULL AFTER `subject_id`');
CALL myclass_add_fk('class_subjects', 'class_subjects_teacher_id_foreign', 'teacher_id', 'users', 'id');

-- exams.created_by
CALL myclass_add_column('exams', 'created_by', 'BIGINT UNSIGNED NULL AFTER `class_subject_id`');
CALL myclass_add_fk('exams', 'exams_created_by_foreign', 'created_by', 'users', 'id');

-- exercises.created_by
CALL myclass_add_column('exercises', 'created_by', 'BIGINT UNSIGNED NULL AFTER `class_subject_id`');
CALL myclass_add_fk('exercises', 'exercises_created_by_foreign', 'created_by', 'users', 'id');

-- =====================================================================
-- ۲) سخت‌سازی آزمون (ضدتقلب)
-- =====================================================================

-- exam_attempt_answers.correct_option_id (snapshot پاسخ صحیح)
CALL myclass_add_column('exam_attempt_answers', 'correct_option_id', 'BIGINT UNSIGNED NULL AFTER `selected_option_id`');
CALL myclass_add_fk('exam_attempt_answers', 'exam_attempt_answers_correct_option_id_foreign', 'correct_option_id', 'question_options', 'id');

-- پر کردن snapshot برای پاسخ‌های قبلی
UPDATE `exam_attempt_answers` AS a
JOIN `question_options` AS qo
  ON qo.`question_id` = a.`question_id`
 AND qo.`is_correct` = 1
SET a.`correct_option_id` = qo.`id`
WHERE a.`correct_option_id` IS NULL;

-- exam_attempt_answers.first_answered_at / last_answered_at
CALL myclass_add_column('exam_attempt_answers', 'first_answered_at', 'TIMESTAMP NULL AFTER `is_correct`');
CALL myclass_add_column('exam_attempt_answers', 'last_answered_at', 'TIMESTAMP NULL AFTER `first_answered_at`');

-- exam_attempts.session_id / ip_address / user_agent
CALL myclass_add_column('exam_attempts', 'session_id', 'VARCHAR(64) NULL AFTER `status`');
CALL myclass_add_column('exam_attempts', 'ip_address', 'VARCHAR(45) NULL AFTER `session_id`');
CALL myclass_add_column('exam_attempts', 'user_agent', 'TEXT NULL AFTER `ip_address`');

-- جدول exam_events
CREATE TABLE IF NOT EXISTS `exam_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_attempt_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `reason` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `metadata` JSON NULL,
    `occurred_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `exam_events_exam_attempt_id_type_index` (`exam_attempt_id`, `type`),
    CONSTRAINT `exam_events_exam_attempt_id_foreign`
        FOREIGN KEY (`exam_attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- ۳) پاک‌سازی رویه‌های کمکی
-- =====================================================================
DROP PROCEDURE IF EXISTS `myclass_add_column`;
DROP PROCEDURE IF EXISTS `myclass_add_fk`;

-- =====================================================================
-- ۴) بررسی نهایی (باید همه ستون‌ها را نشان دهد)
-- =====================================================================
SHOW COLUMNS FROM `class_subjects` LIKE 'teacher_id';
SHOW COLUMNS FROM `exams` LIKE 'created_by';
SHOW COLUMNS FROM `exercises` LIKE 'created_by';
SHOW COLUMNS FROM `exam_attempt_answers` LIKE 'correct_option_id';
SHOW COLUMNS FROM `exam_attempt_answers` LIKE 'first_answered_at';
SHOW COLUMNS FROM `exam_attempts` LIKE 'session_id';
SHOW TABLES LIKE 'exam_events';
