-- Your module's tables.  Import AFTER shared/shared_schema.sql, into the database rc_campus.
--
-- Rules (the automatic check enforces them):
--   * every table name starts with your table_prefix from module.json (here: example_)
--   * do not create, alter or insert into the shared tables (users, students, employees, …)
--   * no CREATE DATABASE / USE lines
--   * foreign keys only to your own tables or to shared tables
--   * at least 10 realistic sample rows in each main table
--
-- This example is the Suggestion Box module. Replace it with your own tables.

DROP TABLE IF EXISTS example_suggestions;

CREATE TABLE example_suggestions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  category    ENUM('academics','campus','canteen','library','it','other') NOT NULL,
  title       VARCHAR(120) NOT NULL,
  body        TEXT NOT NULL,
  status      ENUM('open','reviewed','closed') NOT NULL DEFAULT 'open',
  reply       VARCHAR(255) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO example_suggestions (user_id, category, title, body, status, reply, created_at) VALUES
  (38, 'library',  'Keep the library open till 8 pm in exam weeks', 'Many of us travel back late anyway and need a quiet place to study during the end-semester exams.', 'reviewed', 'Will be discussed in the library committee meeting.', '2026-09-02 10:15:00'),
  (39, 'it',       'More charging points in the computer lab', 'Only two sockets work on the back wall of Lab 2, so laptops die during practicals.', 'closed', 'Six new sockets installed on 20 Sept.', '2026-09-03 12:40:00'),
  (40, 'canteen',  'Add a healthy breakfast option', 'Sprouts or fruit bowls in the morning would help students who come without breakfast.', 'open', NULL, '2026-09-05 09:05:00'),
  (41, 'campus',   'Drinking water cooler near Block C', 'The nearest cooler is on the ground floor of the main building.', 'open', NULL, '2026-09-08 11:30:00'),
  (42, 'academics','Upload practical lists before the semester starts', 'Getting the list early lets us set up XAMPP and Python at home in advance.', 'reviewed', 'Departments asked to upload by 1 August next year.', '2026-09-10 14:20:00'),
  (43, 'it',       'Campus Wi-Fi drops in the seminar hall', 'Connection keeps dropping during presentations in the seminar hall.', 'open', NULL, '2026-09-12 16:45:00'),
  (44, 'other',    'Lost and found board', 'A notice board near the gate for lost ID cards and bottles would help.', 'open', NULL, '2026-09-15 10:00:00'),
  (45, 'library',  'E-book access from home', 'Please share how to reach the e-resources from outside the campus network.', 'closed', 'Remote access guide added to the library page.', '2026-09-18 13:10:00'),
  (46, 'campus',   'Shaded seating near the sports ground', 'Students waiting for practice sessions have no shade in the afternoon.', 'open', NULL, '2026-09-21 15:35:00'),
  (47, 'academics','Previous year question papers in one place', 'A single folder per course with the last five years of papers.', 'reviewed', NULL, '2026-09-24 12:00:00'),
  (48, 'canteen',  'UPI payment at the juice counter', 'The juice counter only takes cash.', 'open', NULL, '2026-09-27 11:20:00');
