-- ============================================================
-- PANTHERVERSE — Complete Database Dump
-- JRMSU Academic Community Platform
-- ============================================================
-- HOW TO IMPORT:
--   1. Open HeidiSQL (from Laragon tray)
--   2. Create a new database called: pantherverse_db
--   3. Select pantherverse_db, click File > Run SQL File
--   4. Select this file and click Open
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- DATABASE
-- ============================================================
CREATE DATABASE IF NOT EXISTS `pantherverse_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pantherverse_db`;

-- ============================================================
-- TABLE: migrations (Laravel tracking table)
-- ============================================================
CREATE TABLE `migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` VALUES
(1, '2024_01_01_000001_create_campuses_table', 1),
(2, '2024_01_01_000002_create_programs_table', 1),
(3, '2024_01_01_000003_create_users_table', 1),
(4, '2024_01_01_000004_create_qa_tables', 1),
(5, '2024_01_01_000005_create_community_tables', 1);

-- ============================================================
-- TABLE: campuses
-- ============================================================
CREATE TABLE `campuses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(20) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campuses_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `campuses` VALUES
(1, 'JRMSU Main Campus',       'MAIN', 'Dapitan City, Zamboanga del Norte',  1, NOW(), NOW()),
(2, 'JRMSU Dipolog Campus',    'DIP',  'Dipolog City, Zamboanga del Norte',   1, NOW(), NOW()),
(3, 'JRMSU Tampilisan Campus', 'TAMP', 'Tampilisan, Zamboanga del Norte',     1, NOW(), NOW()),
(4, 'JRMSU Katipunan Campus',  'KAT',  'Katipunan, Zamboanga del Norte',      1, NOW(), NOW()),
(5, 'JRMSU Siocon Campus',     'SIO',  'Siocon, Zamboanga del Norte',         1, NOW(), NOW());

-- ============================================================
-- TABLE: programs
-- ============================================================
CREATE TABLE `programs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `programs_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `programs` VALUES
(1, 'Bachelor of Science in Computer Science',    'BSCS', NOW(), NOW()),
(2, 'Bachelor of Science in Information Systems', 'BSIS', NOW(), NOW()),
(3, 'Bachelor of Science in Information Technology','BSIT',NOW(), NOW());

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(200) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student','instructor','admin') NOT NULL DEFAULT 'student',
  `campus_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `program_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `profile_photo` VARCHAR(255) NULL DEFAULT NULL,
  `bio` TEXT NULL DEFAULT NULL,
  `reputation` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_seen_at` TIMESTAMP NULL DEFAULT NULL,
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_campus_id_foreign` (`campus_id`),
  KEY `users_program_id_foreign` (`program_id`),
  CONSTRAINT `users_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Passwords are bcrypt hashed:
-- admin        -> Admin@12345
-- instructor   -> Instructor@12345
-- students     -> Student@12345
INSERT INTO `users` VALUES
(1, 'System Administrator', 'admin',       'admin@pantherverse.jrmsu.edu.ph',              NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',      1, 2, NULL, 'Platform administrator for PANTHERVERSE.',                                       9999, 1, NULL, NULL, NOW(), NOW()),
(2, 'Prof. Maria Santos',   'prof_santos', 'msantos@pantherverse.jrmsu.edu.ph',            NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 1, 1, NULL, 'CS Instructor, JRMSU Main Campus. Specializes in algorithms and data structures.', 1250, 1, NULL, NULL, NOW(), NOW()),
(3, 'Juan dela Cruz',       'juandc',      'juan.delacruz@pantherverse.jrmsu.edu.ph',      NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    1, 1, NULL, 'BSCS student passionate about web development and AI.',                          350,  1, NULL, NULL, NOW(), NOW()),
(4, 'Ana Reyes',            'ana_reyes',   'ana.reyes@pantherverse.jrmsu.edu.ph',          NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    2, 2, NULL, 'BSIS student from Dipolog Campus. Loves databases and system analysis.',          185,  1, NULL, NULL, NOW(), NOW()),
(5, 'Mark Villanueva',      'markv',       'mark.villanueva@pantherverse.jrmsu.edu.ph',    NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    3, 3, NULL, 'BSIT student interested in networking and cybersecurity.',                        90,   1, NULL, NULL, NOW(), NOW()),
(6, 'Liza Gomez',           'lizag',       'liza.gomez@pantherverse.jrmsu.edu.ph',         NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    1, 3, NULL, 'BSIT student who loves front-end development and UI/UX design.',                  75,   1, NULL, NULL, NOW(), NOW()),
(7, 'Carlo Mendoza',        'carlom',      'carlo.mendoza@pantherverse.jrmsu.edu.ph',      NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student',    4, 1, NULL, 'BSCS student from Katipunan. Into mobile app development.',                       120,  1, NULL, NULL, NOW(), NOW()),
(8, 'Prof. Ryan Bautista',  'prof_bautista','rbautista@pantherverse.jrmsu.edu.ph',         NOW(), '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 2, 2, NULL, 'IS Instructor at Dipolog Campus. Specializes in systems analysis and databases.',  890,  1, NULL, NULL, NOW(), NOW());

-- ============================================================
-- TABLE: password_reset_tokens
-- ============================================================
CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(200) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: sessions
-- ============================================================
CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` TEXT NULL DEFAULT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: tags
-- ============================================================
CREATE TABLE `tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `usage_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_name_unique` (`name`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tags` VALUES
(1,  'Java',           'java',            'Questions about Java programming language',             24, NOW(), NOW()),
(2,  'Python',         'python',          'Python programming, scripting, and frameworks',          31, NOW(), NOW()),
(3,  'PHP',            'php',             'PHP web development and scripting',                      18, NOW(), NOW()),
(4,  'Laravel',        'laravel',         'Laravel PHP framework questions',                        15, NOW(), NOW()),
(5,  'JavaScript',     'javascript',      'JavaScript frontend and Node.js',                        27, NOW(), NOW()),
(6,  'MySQL',          'mysql',           'MySQL database design and queries',                      22, NOW(), NOW()),
(7,  'HTML/CSS',       'html-css',        'Web markup and styling',                                 16, NOW(), NOW()),
(8,  'Algorithms',     'algorithms',      'Algorithm design, complexity, and data structures',      19, NOW(), NOW()),
(9,  'Networking',     'networking',      'Computer networks and protocols',                        11, NOW(), NOW()),
(10, 'Cybersecurity',  'cybersecurity',   'Security concepts and ethical hacking',                   9, NOW(), NOW()),
(11, 'Database Design','database-design', 'ERD, normalization, and schema design',                  14, NOW(), NOW()),
(12, 'OOP',            'oop',             'Object-Oriented Programming concepts',                   20, NOW(), NOW()),
(13, 'Data Structures','data-structures', 'Arrays, linked lists, trees, graphs, etc.',              17, NOW(), NOW()),
(14, 'Web Development','web-development', 'Full-stack and frontend/backend web dev',                25, NOW(), NOW()),
(15, 'Git',            'git',             'Version control with Git and GitHub',                     8, NOW(), NOW()),
(16, 'Linux',          'linux',           'Linux OS, shell scripting, and server admin',             7, NOW(), NOW()),
(17, 'C++',            'cpp',             'C and C++ programming language',                         13, NOW(), NOW()),
(18, 'React',          'react',           'React.js frontend library',                              10, NOW(), NOW());

-- ============================================================
-- TABLE: questions
-- ============================================================
CREATE TABLE `questions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(350) NOT NULL,
  `body` LONGTEXT NOT NULL,
  `slug` VARCHAR(400) NOT NULL,
  `status` ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
  `is_solved` TINYINT(1) NOT NULL DEFAULT 0,
  `accepted_answer_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `view_count` INT NOT NULL DEFAULT 0,
  `vote_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `questions_slug_unique` (`slug`),
  KEY `questions_user_id_foreign` (`user_id`),
  FULLTEXT KEY `questions_title_body_fulltext` (`title`,`body`),
  CONSTRAINT `questions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `questions` VALUES
(1, 3, 'How do I fix a NullPointerException in Java when accessing object methods?',
 '<p>I keep getting a <code>NullPointerException</code> in my Java program when I try to call a method on an object. Here is my code:</p><pre><code>Student s = getStudent(id);\nSystem.out.println(s.getName());</code></pre><p>The error says the object is null but I expected <code>getStudent()</code> to always return something. What causes this and how do I fix it?</p>',
 'how-do-i-fix-nullpointerexception-in-java', 'answered', 1, 1, 143, 12, NOW(), NOW(), NULL),

(2, 4, 'What is the difference between INNER JOIN and LEFT JOIN in MySQL?',
 '<p>I am designing a database for a student enrollment system and I need to retrieve student records along with their enrolled subjects. Some students may not be enrolled yet. Should I use INNER JOIN or LEFT JOIN? What is the actual difference between these two?</p>',
 'difference-between-inner-join-and-left-join-mysql', 'answered', 1, 2, 267, 18, NOW(), NOW(), NULL),

(3, 5, 'How does the OSI model relate to real-world networking protocols?',
 '<p>My professor keeps mentioning the OSI model in class but I am having trouble understanding how each layer actually maps to real protocols we use every day like HTTP, TCP, and Ethernet. Can someone explain with concrete examples?</p>',
 'osi-model-real-world-networking-protocols', 'open', 0, NULL, 89, 7, NOW(), NOW(), NULL),

(4, 3, 'What is Big O notation and how do I calculate time complexity?',
 '<p>I am studying algorithms and I struggle with Big O notation. How do I determine if a function is O(n), O(n²), or O(log n)? Can someone give a practical explanation with code examples?</p>',
 'big-o-notation-time-complexity-explained', 'open', 0, NULL, 201, 15, NOW(), NOW(), NULL),

(5, 6, 'How do I center a div vertically and horizontally in CSS?',
 '<p>I have a div inside a container and I want to center it both vertically and horizontally. I have tried using <code>margin: auto</code> but it only centers it horizontally. What is the modern CSS way to do this?</p>',
 'how-to-center-div-vertically-horizontally-css', 'answered', 1, 3, 312, 22, NOW(), NOW(), NULL),

(6, 7, 'What is the difference between a stack and a queue in data structures?',
 '<p>I understand that both stack and queue are linear data structures but I am confused about when to use each one. Can someone explain with real-world examples and also show how to implement them in Java?</p>',
 'difference-stack-queue-data-structures', 'open', 0, NULL, 95, 9, NOW(), NOW(), NULL),

(7, 4, 'How do I normalize a database to Third Normal Form (3NF)?',
 '<p>I have a table with the following columns: StudentID, StudentName, CourseID, CourseName, InstructorID, InstructorName, InstructorEmail. My professor said this violates normalization rules. How do I normalize this to 3NF step by step?</p>',
 'how-to-normalize-database-third-normal-form', 'open', 0, NULL, 156, 11, NOW(), NOW(), NULL),

(8, 5, 'What is the purpose of subnetting in computer networks?',
 '<p>I keep hearing about subnetting in our networking class and I know it involves dividing a network into smaller parts but I do not fully understand why we do it and how to calculate subnet masks. Can someone explain with a practical example?</p>',
 'purpose-of-subnetting-in-computer-networks', 'open', 0, NULL, 73, 6, NOW(), NOW(), NULL),

(9, 3, 'How do I use Git branches for collaborative development?',
 '<p>My capstone group is starting to use Git for our project but we are confused about how to use branches properly. How do we create branches, switch between them, and merge changes without causing conflicts?</p>',
 'how-to-use-git-branches-collaborative-development', 'open', 0, NULL, 134, 14, NOW(), NOW(), NULL),

(10, 7, 'What is recursion and when should I use it instead of loops?',
 '<p>I understand that recursion is a function calling itself but I am not sure when it is better to use recursion versus a regular loop. Can someone explain with examples of problems that are naturally recursive?</p>',
 'what-is-recursion-when-to-use-instead-of-loops', 'open', 0, NULL, 187, 16, NOW(), NOW(), NULL);

-- ============================================================
-- TABLE: answers
-- ============================================================
CREATE TABLE `answers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `body` LONGTEXT NOT NULL,
  `is_accepted` TINYINT(1) NOT NULL DEFAULT 0,
  `is_instructor_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `vote_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `answers_question_id_foreign` (`question_id`),
  KEY `answers_user_id_foreign` (`user_id`),
  CONSTRAINT `answers_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `answers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add FK for accepted_answer_id now that answers table exists
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_accepted_answer_id_foreign`
  FOREIGN KEY (`accepted_answer_id`) REFERENCES `answers` (`id`) ON DELETE SET NULL;

INSERT INTO `answers` VALUES
(1, 1, 2,
 '<p>A <strong>NullPointerException</strong> (NPE) occurs when your code tries to use a reference that points to no object — it is <code>null</code>.</p><p>In your case, <code>getStudent(id)</code> is returning <code>null</code> when no student is found for that ID. The fix is to always null-check before using the object:</p><pre><code>Student s = getStudent(id);\nif (s != null) {\n    System.out.println(s.getName());\n} else {\n    System.out.println("Student not found.");\n}</code></pre><p>Modern Java (8+) offers <code>Optional</code> as a cleaner approach:</p><pre><code>Optional&lt;Student&gt; s = Optional.ofNullable(getStudent(id));\ns.ifPresentOrElse(\n    student -> System.out.println(student.getName()),\n    () -> System.out.println("Not found.")\n);</code></pre>',
 1, 1, 14, NOW(), NOW(), NULL),

(2, 1, 3,
 '<p>To add to the instructor answer above — another common cause of NPE is forgetting to initialize an object before using it:</p><pre><code>// Wrong\nString[] names;\nSystem.out.println(names.length); // NPE!\n\n// Correct\nString[] names = new String[5];\nSystem.out.println(names.length); // Works</code></pre><p>Always initialize your variables before use.</p>',
 0, 0, 5, NOW(), NOW(), NULL),

(3, 2, 2,
 '<p><strong>INNER JOIN</strong> returns only rows where there is a match in BOTH tables. <strong>LEFT JOIN</strong> returns ALL rows from the left table and matching rows from the right table (NULL if no match).</p><p>For your enrollment system where some students may not be enrolled yet, use LEFT JOIN:</p><pre><code>SELECT s.name, e.subject_name\nFROM students s\nLEFT JOIN enrollments e ON s.id = e.student_id;</code></pre><p>This returns all students. Those without enrollments will show NULL for subject_name. If you used INNER JOIN, unenrolled students would be excluded entirely.</p><p><strong>Visual summary:</strong><br>INNER JOIN = only matching rows (intersection)<br>LEFT JOIN = all left rows + matches from right</p>',
 1, 1, 20, NOW(), NOW(), NULL),

(4, 5, 2,
 '<p>The modern CSS way is to use <strong>Flexbox</strong>. Apply this to the container:</p><pre><code>.container {\n    display: flex;\n    justify-content: center;  /* horizontal */\n    align-items: center;      /* vertical */\n    height: 100vh;            /* full viewport height */\n}</code></pre><p>Or use <strong>CSS Grid</strong>:</p><pre><code>.container {\n    display: grid;\n    place-items: center;\n    height: 100vh;\n}</code></pre><p><code>place-items: center</code> is shorthand for both <code>align-items</code> and <code>justify-items</code> set to center.</p>',
 1, 1, 18, NOW(), NOW(), NULL),

(5, 4, 2,
 '<p><strong>Big O notation</strong> describes how the running time or space of an algorithm grows relative to the input size <em>n</em>.</p><p><strong>Common complexities:</strong></p><pre><code>O(1)      - Constant: array[0] access\nO(log n)  - Logarithmic: binary search\nO(n)      - Linear: single loop through array\nO(n log n)- Linearithmic: merge sort\nO(n²)     - Quadratic: nested loops\nO(2ⁿ)     - Exponential: recursive fibonacci</code></pre><p><strong>How to calculate:</strong> Count the operations relative to n. Nested loops = multiply. Separate loops = add. Always drop constants and lower-order terms.</p><pre><code>// O(n²) - nested loops\nfor (int i = 0; i < n; i++) {        // n times\n    for (int j = 0; j < n; j++) {    // n times each\n        // O(1) work\n    }\n}</code></pre>',
 0, 1, 12, NOW(), NOW(), NULL),

(6, 10, 2,
 '<p><strong>Recursion</strong> is best used when a problem can be broken into smaller versions of itself. Classic examples:</p><pre><code>// Factorial - naturally recursive\npublic int factorial(int n) {\n    if (n == 0) return 1;           // base case\n    return n * factorial(n - 1);    // recursive case\n}\n\n// Fibonacci\npublic int fib(int n) {\n    if (n <= 1) return n;\n    return fib(n-1) + fib(n-2);\n}</code></pre><p>Use recursion when: the problem has a natural recursive structure (trees, graphs), the solution is clearer recursively, or you are using divide-and-conquer. Use loops when performance is critical since recursion has function call overhead and risk of stack overflow.</p>',
 0, 1, 8, NOW(), NOW(), NULL);

-- ============================================================
-- TABLE: question_tag
-- ============================================================
CREATE TABLE `question_tag` (
  `question_id` BIGINT UNSIGNED NOT NULL,
  `tag_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`question_id`,`tag_id`),
  KEY `question_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `question_tag_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `question_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `question_tag` VALUES
(1,1),(1,12),
(2,6),(2,11),
(3,9),
(4,8),(4,13),
(5,7),(5,14),
(6,13),(6,1),
(7,11),(7,6),
(8,9),
(9,15),
(10,8),(10,1);

-- ============================================================
-- TABLE: comments
-- ============================================================
CREATE TABLE `comments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `commentable_id` BIGINT UNSIGNED NOT NULL,
  `commentable_type` VARCHAR(100) NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_user_id_foreign` (`user_id`),
  KEY `comments_commentable_type_commentable_id_index` (`commentable_type`,`commentable_id`),
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `comments` VALUES
(1, 4, 1, 'App\\Models\\Question', 'Great question! I had the same issue last semester.', NOW(), NOW(), NULL),
(2, 5, 1, 'App\\Models\\Answer',   'This Optional approach is really clean. Thanks prof!', NOW(), NOW(), NULL),
(3, 3, 2, 'App\\Models\\Question', 'This is very helpful for our database subject.', NOW(), NOW(), NULL),
(4, 6, 3, 'App\\Models\\Answer',   'Thank you for the detailed explanation with code examples!', NOW(), NOW(), NULL);

-- ============================================================
-- TABLE: votes
-- ============================================================
CREATE TABLE `votes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `voteable_id` BIGINT UNSIGNED NOT NULL,
  `voteable_type` VARCHAR(100) NOT NULL,
  `value` TINYINT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `votes_user_voteable_unique` (`user_id`,`voteable_id`,`voteable_type`),
  KEY `votes_user_id_foreign` (`user_id`),
  KEY `votes_voteable_type_voteable_id_index` (`voteable_type`,`voteable_id`),
  CONSTRAINT `votes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `votes` VALUES
(1,  4, 1, 'App\\Models\\Question', 1, NOW(), NOW()),
(2,  5, 1, 'App\\Models\\Question', 1, NOW(), NOW()),
(3,  6, 1, 'App\\Models\\Question', 1, NOW(), NOW()),
(4,  7, 1, 'App\\Models\\Question', 1, NOW(), NOW()),
(5,  8, 1, 'App\\Models\\Answer',   1, NOW(), NOW()),
(6,  3, 1, 'App\\Models\\Answer',   1, NOW(), NOW()),
(7,  5, 1, 'App\\Models\\Answer',   1, NOW(), NOW()),
(8,  6, 1, 'App\\Models\\Answer',   1, NOW(), NOW()),
(9,  3, 2, 'App\\Models\\Question', 1, NOW(), NOW()),
(10, 5, 2, 'App\\Models\\Question', 1, NOW(), NOW()),
(11, 6, 3, 'App\\Models\\Answer',   1, NOW(), NOW()),
(12, 7, 3, 'App\\Models\\Answer',   1, NOW(), NOW()),
(13, 4, 4, 'App\\Models\\Question', 1, NOW(), NOW()),
(14, 6, 4, 'App\\Models\\Question', 1, NOW(), NOW()),
(15, 7, 4, 'App\\Models\\Question', 1, NOW(), NOW());

-- ============================================================
-- TABLE: forum_categories
-- ============================================================
CREATE TABLE `forum_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `icon` VARCHAR(100) NULL DEFAULT 'bi-chat-dots',
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forum_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `forum_categories` VALUES
(1, 'Programming Help',       'programming-help',    'General programming questions, tips, and discussions',     'bi-code-slash',    1, NOW(), NOW()),
(2, 'Database & SQL',         'database-sql',        'Database design, queries, and optimization',               'bi-database',      2, NOW(), NOW()),
(3, 'Web Development',        'web-development',     'Frontend, backend, and full-stack web development',        'bi-globe',         3, NOW(), NOW()),
(4, 'Networking & Security',  'networking-security', 'Computer networks, protocols, and cybersecurity',          'bi-shield-check',  4, NOW(), NOW()),
(5, 'Academic Life',          'academic-life',       'Study tips, career advice, and campus life',               'bi-mortarboard',   5, NOW(), NOW()),
(6, 'Project Collaboration',  'project-collaboration','Find teammates and discuss capstone or personal projects', 'bi-people',        6, NOW(), NOW());

-- ============================================================
-- TABLE: forum_posts
-- ============================================================
CREATE TABLE `forum_posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(350) NOT NULL,
  `body` LONGTEXT NOT NULL,
  `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
  `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
  `view_count` INT NOT NULL DEFAULT 0,
  `reply_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `forum_posts_category_id_foreign` (`category_id`),
  KEY `forum_posts_user_id_foreign` (`user_id`),
  CONSTRAINT `forum_posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `forum_posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `forum_posts` VALUES
(1, 5, 2, 'Welcome to PANTHERVERSE — Tips for New Members',
 '<p>Hello everyone! Welcome to PANTHERVERSE, the official academic community platform for JRMSU computing students.</p><p>Here are some tips to get started:</p><ul><li>Use the <strong>Q&A section</strong> for specific technical questions</li><li>Use the <strong>Forums</strong> for open discussions like this one</li><li>Tag your questions properly so others can find them</li><li>Upvote helpful answers to reward contributors</li><li>Earn reputation points by helping your fellow students</li></ul><p>Let us build a strong computing community together. Go Panthers!</p>',
 1, 1, 245, 3, NOW(), NOW(), NULL),

(2, 1, 3, 'Best resources for learning Python as a complete beginner?',
 '<p>Hi everyone! I am a first-year BSCS student and we just started our programming subject using Python. Can anyone recommend good free resources for learning Python from scratch? Books, websites, YouTube channels — anything helps!</p>',
 0, 0, 87, 2, NOW(), NOW(), NULL),

(3, 6, 7, 'Looking for groupmates for capstone project — BSCS Main Campus',
 '<p>Hello! I am a 3rd year BSCS student from the Main Campus looking for 2-3 more groupmates for our capstone project. My proposed topic is a web-based inventory management system for small businesses using Laravel and Vue.js.</p><p>Requirements: Must be BSCS 3rd year, willing to meet twice a week, familiar with basic web development.</p><p>Comment below or message me if interested!</p>',
 0, 0, 134, 4, NOW(), NOW(), NULL),

(4, 2, 4, 'Understanding database indexes — when should we use them?',
 '<p>Our database professor mentioned indexes today and said they speed up queries but also slow down INSERT and UPDATE operations. I am confused about when it is worth adding an index. Can anyone explain with practical examples?</p>',
 0, 0, 93, 1, NOW(), NOW(), NULL),

(5, 5, 6, 'How do you manage study time during finals week?',
 '<p>Finals week is coming up and I have 5 subjects with exams and 2 project submissions all in the same week. How do you all manage your time and avoid burning out? Any tips for surviving finals week as a CS student?</p>',
 0, 0, 201, 5, NOW(), NOW(), NULL);

-- ============================================================
-- TABLE: resources
-- ============================================================
CREATE TABLE `resources` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `download_count` INT NOT NULL DEFAULT 0,
  `is_instructor_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resources_user_id_foreign` (`user_id`),
  CONSTRAINT `resources_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `resources` VALUES
(1, 2, 'Data Structures and Algorithms — Complete Study Guide',
 'A comprehensive review guide covering arrays, linked lists, stacks, queues, trees, and sorting algorithms with Java examples. Perfect for midterm and final exam review.',
 'resources/sample-dsa-guide.pdf', 'DSA_Study_Guide.pdf', 'pdf', 2457600, 47, 1, NOW(), NOW(), NULL),

(2, 2, 'Database Normalization Cheat Sheet (1NF to 3NF)',
 'Quick reference card explaining database normalization steps from First Normal Form to Third Normal Form with examples and common violations.',
 'resources/sample-normalization.pdf', 'DB_Normalization_Cheatsheet.pdf', 'pdf', 512000, 63, 1, NOW(), NOW(), NULL),

(3, 8, 'MySQL Query Practice Problems — 50 Exercises with Solutions',
 'A collection of 50 SQL practice problems ranging from basic SELECT queries to complex JOINs and subqueries. Solutions included.',
 'resources/sample-sql-exercises.pdf', 'MySQL_Practice_Problems.pdf', 'pdf', 1024000, 38, 1, NOW(), NOW(), NULL),

(4, 3, 'Laravel CRUD Tutorial — Step by Step for Beginners',
 'Complete walkthrough of creating a CRUD application using Laravel 10. Covers routes, controllers, models, migrations, and Blade views.',
 'resources/sample-laravel-crud.pdf', 'Laravel_CRUD_Tutorial.pdf', 'pdf', 3145728, 29, 0, NOW(), NOW(), NULL),

(5, 5, 'OSI Model and TCP/IP Reference Card',
 'Visual reference card showing all 7 OSI layers with protocols and functions at each layer, plus comparison with the TCP/IP model.',
 'resources/sample-osi-reference.pdf', 'OSI_Model_Reference.pdf', 'pdf', 256000, 55, 1, NOW(), NOW(), NULL);

-- ============================================================
-- TABLE: resource_tag
-- ============================================================
CREATE TABLE `resource_tag` (
  `resource_id` BIGINT UNSIGNED NOT NULL,
  `tag_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`resource_id`,`tag_id`),
  KEY `resource_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `resource_tag_resource_id_foreign` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resource_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `resource_tag` VALUES
(1,8),(1,13),(1,1),
(2,11),(2,6),
(3,6),(3,11),
(4,3),(4,4),(4,14),
(5,9);

-- ============================================================
-- TABLE: projects
-- ============================================================
CREATE TABLE `projects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `tech_stack` TEXT NULL DEFAULT NULL,
  `repo_url` VARCHAR(500) NULL DEFAULT NULL,
  `demo_url` VARCHAR(500) NULL DEFAULT NULL,
  `thumbnail` VARCHAR(500) NULL DEFAULT NULL,
  `is_endorsed` TINYINT(1) NOT NULL DEFAULT 0,
  `like_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_user_id_foreign` (`user_id`),
  CONSTRAINT `projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `projects` VALUES
(1, 3, 'JRMSU Course Registration Portal',
 'A web-based course registration system for JRMSU students. Features include subject enrollment, schedule viewing, grade tracking, and instructor management. Built as a capstone project for BSCS.',
 'PHP, Laravel, MySQL, Bootstrap 5, JavaScript',
 'https://github.com/example/jrmsu-registration', NULL, NULL, 1, 24, NOW(), NOW(), NULL),

(2, 4, 'Inventory Management System for Small Businesses',
 'A simple yet complete inventory tracking system designed for small retail businesses. Supports product management, stock alerts, sales recording, and basic reporting with chart visualizations.',
 'PHP, MySQL, Bootstrap 5, Chart.js',
 'https://github.com/example/inventory-system', NULL, NULL, 0, 12, NOW(), NOW(), NULL),

(3, 7, 'Campus Lost & Found Mobile App',
 'A mobile-friendly web app where JRMSU students can report lost items and browse found items across all campuses. Includes photo upload and contact features.',
 'Laravel, Vue.js, MySQL, Tailwind CSS',
 NULL, NULL, NULL, 0, 8, NOW(), NOW(), NULL);

-- ============================================================
-- TABLE: project_likes
-- ============================================================
CREATE TABLE `project_likes` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`project_id`),
  KEY `project_likes_project_id_foreign` (`project_id`),
  CONSTRAINT `project_likes_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_likes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `project_likes` VALUES
(2,1,NOW()),(4,1,NOW()),(5,1,NOW()),(6,1,NOW()),
(3,2,NOW()),(5,2,NOW()),(7,2,NOW()),
(3,3,NOW()),(4,3,NOW());

-- ============================================================
-- TABLE: announcements
-- ============================================================
CREATE TABLE `announcements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(350) NOT NULL,
  `body` LONGTEXT NOT NULL,
  `campus_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `priority` ENUM('normal','important','urgent') NOT NULL DEFAULT 'normal',
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_user_id_foreign` (`user_id`),
  KEY `announcements_campus_id_foreign` (`campus_id`),
  CONSTRAINT `announcements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcements_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `announcements` VALUES
(1, 1, 'Welcome to PANTHERVERSE!',
 '<p>Welcome to <strong>PANTHERVERSE</strong> — the official academic community platform for JRMSU computing students and instructors!</p><p>Start by posting a question, sharing a resource, or joining a forum discussion. Earn reputation points and badges as you contribute to the community.</p><p>If you have any questions, reach out to the admin team.</p>',
 NULL, 'important', NULL, NOW(), NOW(), NULL),

(2, 2, 'Capstone Project Proposal Deadline',
 '<p>All BSIS students: Please submit your capstone project proposals through the platform Project Showcase by end of semester. Include your GitHub repository link and project description. Late submissions will not be accepted.</p>',
 1, 'urgent', DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW(), NULL),

(3, 8, 'New: Database Design Resources Now Available',
 '<p>I have uploaded new study materials for our Database Management subject to the Resources section. This includes normalization exercises, ER diagram templates, and sample exam questions. Please download and review before our next class.</p>',
 2, 'normal', DATE_ADD(NOW(), INTERVAL 14 DAY), NOW(), NOW(), NULL);

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE `notifications` (
  `id` CHAR(36) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `data` JSON NOT NULL,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_read_at_index` (`user_id`,`read_at`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifications` VALUES
('a1b2c3d4-0001-0000-0000-000000000001', 3, 'new_answer',      '{"question_title":"How do I fix a NullPointerException in Java?","question_slug":"how-do-i-fix-nullpointerexception-in-java","answerer_name":"Prof. Maria Santos","answer_id":1}', NOW(), NOW(), NOW()),
('a1b2c3d4-0002-0000-0000-000000000002', 3, 'answer_accepted',  '{"question_title":"How do I fix a NullPointerException in Java?","question_slug":"how-do-i-fix-nullpointerexception-in-java"}', NULL, NOW(), NOW()),
('a1b2c3d4-0003-0000-0000-000000000003', 4, 'new_answer',       '{"question_title":"Difference between INNER JOIN and LEFT JOIN","question_slug":"difference-between-inner-join-and-left-join-mysql","answerer_name":"Prof. Maria Santos","answer_id":3}', NULL, NOW(), NOW()),
('a1b2c3d4-0004-0000-0000-000000000004', 3, 'badge_earned',     '{"badge_name":"First Steps","badge_icon":"bi-star"}', NULL, NOW(), NOW()),
('a1b2c3d4-0005-0000-0000-000000000005', 2, 'new_comment',      '{"commenter_name":"Ana Reyes","comment_body":"This is very helpful for our database subject.","type":"question"}', NULL, NOW(), NOW());

-- ============================================================
-- TABLE: badges
-- ============================================================
CREATE TABLE `badges` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `icon` VARCHAR(100) NOT NULL DEFAULT 'bi-award',
  `color` VARCHAR(20) NOT NULL DEFAULT 'gold',
  `criteria_type` VARCHAR(50) NOT NULL,
  `criteria_value` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `badges_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `badges` VALUES
(1, 'First Steps',      'Posted your first question',               'bi-star',         '#6B7280', 'questions_asked',  1,    NOW(), NOW()),
(2, 'Helper',           'Had 10 answers accepted by the community', 'bi-hand-thumbs-up','#3B82F6', 'answers_accepted', 10,   NOW(), NOW()),
(3, 'Knowledge Keeper', 'Shared 5 learning resources',              'bi-book',          '#10B981', 'resources_shared', 5,    NOW(), NOW()),
(4, 'Top Contributor',  'Reached 500 reputation points',            'bi-trophy',        '#F59E0B', 'reputation',       500,  NOW(), NOW()),
(5, 'Mentor',           'Had 50 answers accepted',                  'bi-mortarboard',   '#8B5CF6', 'answers_accepted', 50,   NOW(), NOW()),
(6, 'Panther Legend',   'Reached 2000 reputation — highest honor',  'bi-award-fill',    '#EF4444', 'reputation',       2000, NOW(), NOW());

-- ============================================================
-- TABLE: user_badges
-- ============================================================
CREATE TABLE `user_badges` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `badge_id` BIGINT UNSIGNED NOT NULL,
  `awarded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`badge_id`),
  KEY `user_badges_badge_id_foreign` (`badge_id`),
  CONSTRAINT `user_badges_badge_id_foreign` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_badges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user_badges` VALUES
(2, 1, NOW()), (2, 2, NOW()), (2, 4, NOW()), (2, 5, NOW()), (2, 6, NOW()),
(3, 1, NOW()),
(4, 1, NOW()),
(5, 1, NOW()),
(7, 1, NOW()),
(8, 1, NOW()), (8, 2, NOW()), (8, 4, NOW());

-- ============================================================
-- TABLE: reputation_logs
-- ============================================================
CREATE TABLE `reputation_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `amount` INT NOT NULL,
  `reason` VARCHAR(150) NOT NULL,
  `source_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `source_type` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reputation_logs_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `reputation_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reputation_logs` VALUES
(1, 2, 25,  'Answer accepted',            1, 'App\\Models\\Answer',   NOW(), NOW()),
(2, 2, 20,  'Answer instructor-verified', 1, 'App\\Models\\Answer',   NOW(), NOW()),
(3, 2, 10,  'Upvote received on answer',  1, 'App\\Models\\Answer',   NOW(), NOW()),
(4, 3, 5,   'Upvote received on question',1, 'App\\Models\\Question', NOW(), NOW()),
(5, 4, 25,  'Answer accepted',            3, 'App\\Models\\Answer',   NOW(), NOW()),
(6, 3, 3,   'Posted an answer',           2, 'App\\Models\\Answer',   NOW(), NOW());

-- ============================================================
-- TABLE: reports
-- ============================================================
CREATE TABLE `reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_id` BIGINT UNSIGNED NOT NULL,
  `reportable_id` BIGINT UNSIGNED NOT NULL,
  `reportable_type` VARCHAR(100) NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `notes` TEXT NULL DEFAULT NULL,
  `status` ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `resolved_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reports_reporter_id_foreign` (`reporter_id`),
  KEY `reports_reportable_type_reportable_id_index` (`reportable_type`,`reportable_id`),
  CONSTRAINT `reports_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: suggestions
-- ============================================================
CREATE TABLE `suggestions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `status` ENUM('pending', 'planned', 'implemented', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `suggestions_user_id_foreign` (`user_id`),
  CONSTRAINT `suggestions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suggestions` VALUES
(1, 3, 'Add Dark/Light Mode Toggle', 'It would be great to have a light theme option for reading during the day. The current dark theme is awesome but sometimes hard to read under direct sunlight.', 'planned', NOW()),
(2, 4, 'Direct Messaging System', 'A way to send private messages to other users directly, instead of just public forum posts. Would be very useful for capstone group discussions.', 'pending', NOW()),
(3, 7, 'Mobile App Version', 'Please create a mobile app version of Pantherverse for Android and iOS. It would make it much easier to stay updated on announcements.', 'pending', NOW());

-- ============================================================
-- TABLE: suggestion_votes
-- ============================================================
CREATE TABLE `suggestion_votes` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `suggestion_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`,`suggestion_id`),
  KEY `suggestion_votes_suggestion_id_foreign` (`suggestion_id`),
  CONSTRAINT `suggestion_votes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suggestion_votes_suggestion_id_foreign` FOREIGN KEY (`suggestion_id`) REFERENCES `suggestions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suggestion_votes` VALUES 
(2,1), (4,1), (5,1), (6,1), (8,1),
(3,2), (5,2), (7,2),
(2,3), (3,3), (4,3), (5,3), (6,3), (8,3);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONE!
-- ============================================================
-- Demo Accounts:
--   Admin:      admin@pantherverse.jrmsu.edu.ph       | Admin@12345
--   Instructor: msantos@pantherverse.jrmsu.edu.ph     | Instructor@12345
--   Instructor: rbautista@pantherverse.jrmsu.edu.ph   | Instructor@12345
--   Student:    juan.delacruz@pantherverse.jrmsu.edu.ph | Student@12345
--   Student:    ana.reyes@pantherverse.jrmsu.edu.ph     | Student@12345
--   Student:    mark.villanueva@pantherverse.jrmsu.edu.ph | Student@12345
--   Student:    liza.gomez@pantherverse.jrmsu.edu.ph    | Student@12345
--   Student:    carlo.mendoza@pantherverse.jrmsu.edu.ph | Student@12345
-- ============================================================