CREATE TABLE IF NOT EXISTS portal_locks (name VARCHAR(50) PRIMARY KEY);
INSERT IGNORE INTO portal_locks(name) VALUES('schedule');
