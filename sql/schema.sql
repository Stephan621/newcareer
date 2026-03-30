-- NewCareer Database Schema
-- PHP/MySQL Career Management Application

CREATE DATABASE IF NOT EXISTS newcareer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE newcareer;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(50)  NOT NULL,
    `email`         VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email`    (`email`),
    UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resumes table
CREATE TABLE IF NOT EXISTS `resumes` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `title`      VARCHAR(255) NOT NULL,
    `summary`    TEXT,
    `skills`     TEXT,
    `experience` TEXT,
    `education`  TEXT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_resumes_user_id` (`user_id`),
    CONSTRAINT `fk_resumes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jobs table
CREATE TABLE IF NOT EXISTS `jobs` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(255) NOT NULL,
    `company`      VARCHAR(255) NOT NULL,
    `location`     VARCHAR(255) NOT NULL,
    `description`  TEXT NOT NULL,
    `requirements` TEXT,
    `salary_range` VARCHAR(100),
    `job_type`     ENUM('full-time', 'part-time', 'contract', 'remote') NOT NULL DEFAULT 'full-time',
    `status`       ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_jobs_status`    (`status`),
    KEY `idx_jobs_job_type`  (`job_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Applications table
CREATE TABLE IF NOT EXISTS `applications` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `job_id`       INT UNSIGNED NOT NULL,
    `resume_id`    INT UNSIGNED,
    `cover_letter` TEXT,
    `status`       ENUM('pending', 'reviewed', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_applications_user_job` (`user_id`, `job_id`),
    KEY `idx_applications_user_id`   (`user_id`),
    KEY `idx_applications_job_id`    (`job_id`),
    KEY `idx_applications_resume_id` (`resume_id`),
    CONSTRAINT `fk_applications_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_applications_job`    FOREIGN KEY (`job_id`)     REFERENCES `jobs`    (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_applications_resume` FOREIGN KEY (`resume_id`)  REFERENCES `resumes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------
-- Seed data
-- -----------------------------------------------------------------------

-- Default admin user  (password: Admin123!)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
(
    'admin',
    'admin@newcareer.com',
    '$2y$12$ddnJc7Xxmi/3y0vG0ANy/.94wysC.k5M4OezeJUVR3GGzjvjOmGoy',
    'admin'
);

-- Sample job listings
INSERT INTO `jobs` (`title`, `company`, `location`, `description`, `requirements`, `salary_range`, `job_type`, `status`) VALUES
(
    'Senior PHP Developer',
    'TechCorp Solutions',
    'San Francisco, CA',
    'We are looking for an experienced PHP developer to join our growing team. You will be responsible for building and maintaining high-performance web applications, collaborating with front-end developers, and contributing to architectural decisions.\n\nYou will work on a modern stack using PHP 8, Laravel, MySQL, and Redis in a fully Agile environment.',
    '5+ years of PHP development experience\nStrong knowledge of Laravel or Symfony\nExperience with MySQL and Redis\nFamiliarity with Docker and CI/CD pipelines\nExcellent problem-solving skills',
    '$120,000 - $150,000',
    'full-time',
    'active'
),
(
    'Frontend React Developer',
    'StartupHub Inc.',
    'Remote',
    'Join our dynamic startup and build beautiful, responsive user interfaces using React and TypeScript. You will collaborate closely with our design and backend teams to deliver exceptional user experiences.\n\nThis is a remote-first position with flexible working hours.',
    '3+ years of React experience\nProficiency in TypeScript\nExperience with REST APIs and GraphQL\nKnowledge of CSS-in-JS or Tailwind CSS\nStrong communication skills',
    '$90,000 - $120,000',
    'remote',
    'active'
),
(
    'Full Stack Developer',
    'Digital Agency Pro',
    'New York, NY',
    'Digital Agency Pro is seeking a talented Full Stack Developer to work on exciting client projects across various industries. You will handle everything from database design to pixel-perfect front-end implementation.\n\nWe offer a collaborative environment and opportunities for rapid career growth.',
    'Proficiency in PHP/Node.js and JavaScript\nExperience with React or Vue.js\nMySQL and PostgreSQL knowledge\nVersion control with Git\nAbility to manage multiple projects',
    '$100,000 - $130,000',
    'full-time',
    'active'
),
(
    'DevOps Engineer',
    'CloudBase Systems',
    'Austin, TX',
    'CloudBase Systems is hiring a DevOps Engineer to manage and improve our cloud infrastructure. You will automate deployment pipelines, monitor system performance, and ensure high availability of our services.\n\nWe run on AWS with Kubernetes and Terraform.',
    'Experience with AWS or GCP\nStrong knowledge of Kubernetes and Docker\nTerraform or Ansible experience\nCI/CD pipeline design (GitHub Actions, Jenkins)\nLinux administration',
    '$130,000 - $160,000',
    'full-time',
    'active'
),
(
    'UX/UI Designer',
    'CreativeMinds Studio',
    'Los Angeles, CA',
    'CreativeMinds Studio is looking for a UX/UI Designer who is passionate about creating intuitive and visually stunning digital products. You will work directly with product managers and developers to define user journeys, wireframes, and high-fidelity prototypes.\n\nPortfolio review required.',
    'Portfolio showcasing UX/UI work\nProficiency in Figma or Sketch\nUnderstanding of accessibility standards\nExperience with usability testing\nBasic HTML/CSS knowledge is a plus',
    '$85,000 - $110,000',
    'full-time',
    'active'
),
(
    'Data Analyst (Contract)',
    'Analytics Plus',
    'Chicago, IL',
    'Analytics Plus is seeking a contract Data Analyst to help interpret complex datasets and provide actionable business insights. You will develop dashboards, write SQL queries, and present findings to stakeholders.\n\n6-month contract with possibility of extension.',
    'Strong SQL skills\nExperience with Tableau or Power BI\nProficiency in Python or R for data analysis\nExcellent presentation skills\nExperience in e-commerce or finance preferred',
    '$65 - $85 per hour',
    'contract',
    'active'
),
(
    'Part-Time Customer Support Specialist',
    'HelpDesk Heroes',
    'Remote',
    'HelpDesk Heroes is hiring a part-time Customer Support Specialist to assist our customers via email and live chat. Flexible schedule — ideal for students or those seeking supplemental income.\n\nNo prior experience required; full training provided.',
    'Excellent written communication skills\nEmpathy and patience\nBasic computer proficiency\nAvailability for at least 20 hours per week\nPrevious customer service experience is a plus',
    '$18 - $22 per hour',
    'part-time',
    'active'
);
