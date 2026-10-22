CREATE TABLE IF NOT EXISTS portal_chat_policies (
 cohort_id BIGINT UNSIGNED PRIMARY KEY, enabled TINYINT NOT NULL DEFAULT 1, direct_enabled TINYINT NOT NULL DEFAULT 1,
 files_enabled TINYINT NOT NULL DEFAULT 1, meetings_enabled TINYINT NOT NULL DEFAULT 1,
 FOREIGN KEY(cohort_id) REFERENCES portal_cohorts(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_access (
 cohort_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, allowed TINYINT NOT NULL,
 PRIMARY KEY(cohort_id,user_id), FOREIGN KEY(cohort_id) REFERENCES portal_cohorts(id), FOREIGN KEY(user_id) REFERENCES portal_users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_conversations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, cohort_id BIGINT UNSIGNED NOT NULL,
 kind ENUM('cohort','direct','group') NOT NULL, direct_key VARCHAR(80) NOT NULL UNIQUE, title VARCHAR(120) NOT NULL,
 updated_at DATETIME NOT NULL, FOREIGN KEY(cohort_id) REFERENCES portal_cohorts(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_participants (
 conversation_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, last_read_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY(conversation_id,user_id), INDEX(user_id,conversation_id),
 FOREIGN KEY(conversation_id) REFERENCES portal_chat_conversations(id), FOREIGN KEY(user_id) REFERENCES portal_users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, conversation_id BIGINT UNSIGNED NOT NULL, sender_id BIGINT UNSIGNED NOT NULL,
 content TEXT NOT NULL, client_key VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL,
 UNIQUE(sender_id,client_key), INDEX(conversation_id,id), FOREIGN KEY(conversation_id) REFERENCES portal_chat_conversations(id),
 FOREIGN KEY(sender_id) REFERENCES portal_users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_files (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, message_id BIGINT UNSIGNED NOT NULL UNIQUE, stored_name VARCHAR(100) NOT NULL,
 original_name VARCHAR(190) NOT NULL, mime VARCHAR(100) NOT NULL, size INT UNSIGNED NOT NULL,
 FOREIGN KEY(message_id) REFERENCES portal_chat_messages(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_meetings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, conversation_id BIGINT UNSIGNED NOT NULL, creator_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(120) NOT NULL, starts_at DATETIME NOT NULL, room CHAR(48) NOT NULL UNIQUE,
 FOREIGN KEY(conversation_id) REFERENCES portal_chat_conversations(id), FOREIGN KEY(creator_id) REFERENCES portal_users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS portal_chat_tickets (
 token_hash CHAR(64) PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, activation_version INT NOT NULL, expires_at DATETIME NOT NULL,
 FOREIGN KEY(user_id) REFERENCES portal_users(id)
) ENGINE=InnoDB;
INSERT IGNORE INTO portal_chat_conversations(cohort_id,kind,direct_key,title,updated_at)
 SELECT id,'cohort',CONCAT('cohort:',id),name,UTC_TIMESTAMP() FROM portal_cohorts;
INSERT IGNORE INTO portal_chat_messages(conversation_id,sender_id,content,client_key,created_at)
 SELECT c.id,m.author_id,m.body,CONCAT('legacy:',m.id),m.created_at FROM portal_messages m
 JOIN portal_chat_conversations c ON c.cohort_id=m.cohort_id AND c.kind='cohort';
