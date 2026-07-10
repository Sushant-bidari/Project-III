-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 12, 2026 at 01:56 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ojpcodeastro`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(3) NOT NULL,
  `adminname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `mypassword` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `adminname`, `email`, `mypassword`, `created_at`) VALUES
(1, 'CodeAstro Admin', 'admin@mail.com', '$2y$10$96YcvidLzEoi3Qp5U6232e5DsxSFh.jQBd21STRFkeM6H8ykSvXOS', '2022-11-02 16:48:05'),
(2, 'admin2@admin.com', 'admin2@admin.com', '$2y$10$Jv9hlqQPcF0SSTlG3UPFYufLLhAHHfF8OG4flVYpAoyP6wdtYpLQK', '2022-11-02 19:22:33');

-- --------------------------------------------------------

--
-- Table structure for table `availability`
--

CREATE TABLE `availability` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `monday` enum('morning','afternoon','evening','wholeday','none') NOT NULL,
  `tuesday` enum('morning','afternoon','evening','wholeday','none') NOT NULL,
  `wednesday` enum('morning','afternoon','evening','wholeday','none') NOT NULL,
  `thursday` enum('morning','afternoon','evening','wholeday','none') NOT NULL,
  `friday` enum('morning','afternoon','evening','wholeday','none') NOT NULL,
  `saturday` enum('morning','afternoon','evening','wholeday','none') NOT NULL,
  `sunday` enum('morning','afternoon','evening','wholeday','none') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `availability`
--

INSERT INTO `availability` (`id`, `user_id`, `monday`, `tuesday`, `wednesday`, `thursday`, `friday`, `saturday`, `sunday`, `created_at`, `updated_at`) VALUES
(1, 20, 'morning', 'evening', 'wholeday', 'afternoon', 'wholeday', 'afternoon', 'evening', '2023-10-26 00:11:48', '2023-10-26 00:16:07'),
(2, 21, 'none', 'none', 'none', 'none', 'none', 'none', 'none', '2023-10-26 00:17:32', '2023-10-26 00:17:32'),
(3, 23, 'morning', 'none', 'none', 'wholeday', 'wholeday', 'wholeday', 'wholeday', '2023-10-26 02:47:25', '2023-10-26 02:48:47'),
(4, 24, 'morning', 'wholeday', 'wholeday', 'wholeday', 'wholeday', 'wholeday', 'wholeday', '2024-06-26 02:55:01', '2025-09-08 03:25:14'),
(5, 27, 'wholeday', 'wholeday', 'wholeday', 'none', 'wholeday', 'wholeday', 'none', '2025-09-08 06:56:17', '2025-09-08 07:22:20'),
(6, 30, 'wholeday', 'wholeday', 'morning', 'afternoon', 'afternoon', 'afternoon', 'wholeday', '2025-09-20 04:30:19', '2025-09-22 01:32:32'),
(7, 13, 'wholeday', 'afternoon', 'afternoon', 'afternoon', 'afternoon', 'wholeday', 'wholeday', '2025-12-18 00:35:51', '2025-12-18 00:35:51'),
(9, 35, 'none', 'none', 'none', 'none', 'none', 'none', 'none', '2025-12-20 01:49:32', '2025-12-20 01:49:32'),
(10, 37, 'wholeday', 'afternoon', 'afternoon', 'wholeday', 'wholeday', 'wholeday', 'none', '2025-12-26 01:20:18', '2025-12-26 01:45:36'),
(11, 38, 'wholeday', 'wholeday', 'evening', 'afternoon', 'evening', 'wholeday', 'wholeday', '2025-12-26 05:02:50', '2025-12-29 22:41:10');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(3) NOT NULL,
  `name` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(1, 'Retail and customer services', '2023-10-17 00:05:45'),
(2, 'Transport, distribution and logistics', '2023-10-17 00:06:00'),
(5, 'Marketing and Advertising', '2025-09-15 07:06:41'),
(6, 'Administration, business and management', '2023-10-17 00:03:44'),
(7, 'Animals, land and environment', '2023-10-17 00:03:59'),
(8, 'Computing and ICT', '2023-10-17 00:04:07'),
(9, 'Construction and building', '2023-10-17 00:04:16'),
(10, 'Design, arts and crafts', '2023-10-17 00:04:25'),
(11, 'Education and training', '2023-10-17 00:04:33'),
(12, 'Engineering', '2023-10-17 00:04:41'),
(13, 'Financial services', '2023-10-17 00:04:53'),
(14, 'Hairdressing and beauty', '2023-10-17 00:05:03'),
(15, 'Healthcare', '2023-10-17 00:05:12'),
(16, 'Manufacturing and production', '2023-10-17 00:05:23'),
(17, 'Others', '2023-10-17 00:05:30'),
(18, 'Telecommunications', '2025-12-19 22:38:00');

-- --------------------------------------------------------

--
-- Table structure for table `company_details`
--

CREATE TABLE `company_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_website` varchar(255) DEFAULT NULL,
  `industry` varchar(150) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `established_year` year(4) DEFAULT NULL,
  `operating_hours` varchar(100) DEFAULT NULL,
  `business_reg_no` varchar(20) NOT NULL,
  `company_size` varchar(20) DEFAULT NULL,
  `org_type` varchar(60) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_details`
--

INSERT INTO `company_details` (`id`, `user_id`, `company_website`, `industry`, `address_line`, `postal_code`, `established_year`, `operating_hours`, `business_reg_no`, `company_size`, `org_type`, `created_at`, `updated_at`) VALUES
(1, 25, 'codeastro.com', 'Information Media and Telecommunications', '12 Testing Street, 12th Floor', '100000', 2018, 'Mon-Sat: 9AM-5PM', 'BR-2023-A1B2C3', NULL, NULL, '2025-09-15 01:48:12', '2025-12-18 00:57:43'),
(2, 26, 'codeastro.com', 'IT Services & Consulting', '22 Test Demo', '111111', 2021, 'Mon-Sat: 9:30AM- 5PM', 'BR-2021-Z9X8Y7', NULL, NULL, '2025-09-15 02:09:14', '2025-12-18 00:57:48'),
(3, 7, 'codeastro.com', 'Retail & E-commerce', '1 Sample Address', '111111', 2017, 'Mon-Fri: 10AM-4PM', 'BR-2024-M5N6P7', NULL, NULL, '2025-09-15 06:44:27', '2025-12-18 00:57:53'),
(4, 11, 'codeastro.com', 'Advertising & Marketing', '22 Testing', '1111111', 2020, 'Mon-Fri: 9AM - 5PM', 'BR-2020-K8L3Q2', NULL, NULL, '2025-09-15 07:00:15', '2025-12-18 00:57:58'),
(5, 22, 'codeastro.com', 'Finance', '5 Testing Adr', '3333333', 2012, 'Mon-Fri: 9:30AM - 4:30AM', 'BR-2022-R4T7V9', NULL, NULL, '2025-09-15 09:03:35', '2025-12-18 00:58:02'),
(6, 9, 'codeastro.com', 'Transport, distribution and logistics', '10 Testing Address', '1111111', 2019, 'Mon-Sat: 8AM-5PM', 'BR-2017-J1H8D6', NULL, NULL, '2025-09-15 10:48:31', '2025-12-18 00:58:29'),
(7, 29, 'codeastro.com', 'Manufacturing and production', '2 Demo Address', '9999999', 2010, 'Mon-Sat: 7:00AM - 3:30PM', 'BR-2023-F7G2M9', NULL, NULL, '2025-09-17 02:15:30', '2025-12-18 00:58:11'),
(8, 17, 'codeastro.com', 'Healthcare', '33 Testing Addrr', '5555555', 2007, 'Mon-Sun: 6AM-8PM', 'BR-2010-X3P5B1', NULL, NULL, '2025-09-19 06:12:07', '2025-12-18 00:58:22'),
(9, 32, 'codeastro.com', 'Artificial Intelligence', '445 Testing Street Address', '10101010', 2023, 'Mon-Sat: 8AM - 4:30PM', 'BR-2023-45AI00', '11–50', 'Medium Business', '2025-12-18 01:00:50', '2025-12-18 01:29:15'),
(10, 33, 'codeastro.com', 'Information Technology', '44444 Testing', '10101010', 2015, 'Mon-Fri: 8:30AM - 5:30PM', 'BR-2015-6996CA', '51–200', 'Enterprise', '2025-12-19 08:37:01', '2025-12-19 08:46:33'),
(11, 34, 'codeastro.com', 'Telecommunication', '6654 Demo Address', '79797979', 2018, 'Mon-Sat: 8:00AM - 5:00PM', 'BR-2018-0101CA', '501–1000', 'Enterprise', '2025-12-19 22:21:21', '2025-12-19 22:34:03'),
(12, 36, 'codeastro.com', 'Telecommunication', '4/444 Test Address Line', '11111141', 2010, 'Mon-Fri: 8:00AM - 4:00PM', 'BR-2010-7N8T66', '501–1000', 'Enterprise', '2025-12-25 05:35:13', '2025-12-25 05:41:53');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(3) NOT NULL,
  `job_title` varchar(200) NOT NULL,
  `job_region` varchar(200) NOT NULL,
  `job_type` varchar(200) NOT NULL,
  `work_arrangement` varchar(50) NOT NULL,
  `vacancy` int(3) NOT NULL,
  `job_category` varchar(200) NOT NULL,
  `experience` varchar(200) NOT NULL,
  `salary` varchar(200) NOT NULL,
  `inclusivity_notes` text DEFAULT NULL,
  `application_deadline` varchar(200) NOT NULL,
  `job_description` varchar(1000) NOT NULL,
  `responsibilities` varchar(1000) NOT NULL,
  `education_experience` varchar(1000) NOT NULL,
  `other_benefits` varchar(1000) NOT NULL,
  `company_email` varchar(200) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `company_id` int(3) NOT NULL,
  `company_image` varchar(200) NOT NULL,
  `status` int(3) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `view_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `job_title`, `job_region`, `job_type`, `work_arrangement`, `vacancy`, `job_category`, `experience`, `salary`, `inclusivity_notes`, `application_deadline`, `job_description`, `responsibilities`, `education_experience`, `other_benefits`, `company_email`, `company_name`, `company_id`, `company_image`, `status`, `created_at`, `view_count`) VALUES
(3, 'Sample Job', 'STN', 'Casual', 'On-site Only', 11, 'Others', 'No experience needed', '30 p/w', 'noneee', '2023-10-31', 'qwert tyuio fghjkl cvbn fghjk cvbnm, fghjk', 'qwert tyuio fghjkl cvbn fghjk cvbnm, fghjk', 'qwert tyuio fghjkl cvbn fghjk cvbnm, fghjk', 'qwert tyuio fghjkl cvbn fghjk cvbnm, fghjk', 'employer@mail.com', 'Employer', 16, 'ph.png', 1, '2023-10-14 01:10:17', 0),
(7, 'Customer Support Agent', 'STN', 'Full Time', 'On-site Only', 4, 'Retail and customer services', 'No experience needed', '$66,000 annually', 'No cape required, but superheroes with a heart are a plus', '2025-12-22', '&lt;p&gt;We are looking for a dedicated and empathetic &lt;strong&gt;Customer Support Agent&lt;/strong&gt; to join our growing support team. As the first point of contact for our customers, you will play a critical role in delivering an exceptional experience by resolving issues, answering inquiries, and providing guidance about our products and services.&lt;/p&gt;&lt;p&gt;The ideal candidate is a great communicator, problem solver, and thrives in a fast-paced environment. You will represent our brand in every interaction and ensure customers feel heard, supported, and satisfied.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Respond promptly to customer inquiries via live chat, email, and phone.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Provide accurate, valid, and complete information by using the right tools, methods, and internal processes.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Identify and assess customers&amp;rsquo; needs to achieve satisfaction and first-contact resolution.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Handle customer complaints, provide appropriate solutions and alternatives within set time limits, and follow up to ensure resolution.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with other departments (Sales, Product, Technical Teams) to resolve more complex cases.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain detailed and accurate records of customer interactions, feedback, and issues using CRM tools (e.g., Zendesk, Salesforce).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Continuously improve knowledge of company products, services, and policies.&lt;/p&gt;&amp', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;High school diploma or equivalent required; Associate&amp;rsquo;s or Bachelor&amp;rsquo;s degree preferred.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Minimum &lt;strong&gt;1-2 years&lt;/strong&gt; of experience in a customer support, call center, or client service role.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Experience working with customer support software (e.g., Zendesk, Freshdesk, Intercom) is a plus.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Comfortable working in a digital/remote support environment (if applicable).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Excellent verbal and written communication skills.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong active listening and interpersonal skills.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Problem-solving mindset with the ability to handle challenging situations calmly and professionally.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Competitive salary and performance-based bonuses.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Health, dental, and vision insurance.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Paid time off (vacation, sick leave, holidays).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Flexible work hours and remote work options.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Employee wellness programs.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Career development and training opportunities.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Supportive, inclusive, and dynamic work culture.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Equipment provided (for remote roles).&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'testcorp@mail.com', 'testcorp', 25, 'emp_imgplaceholder.png', 1, '2025-09-08 05:23:47', 4),
(8, 'Marketing Coordinator', 'MKC', 'Full Time', 'On-site Only', 1, 'Others', '3-6 years', '75000 per year', 'Our workplace runs on memes, mystery, and marshmallows.\" \"You bring the brain, we bring the nonsense.', '2025-12-15', '&lt;p&gt;We are seeking a creative and detail-oriented &lt;strong&gt;Marketing Coordinator&lt;/strong&gt; to support our growing marketing team. This role involves executing marketing campaigns, managing content calendars, coordinating with vendors, and analyzing campaign performance.&lt;/p&gt;&lt;p&gt;The ideal candidate is a self-starter with excellent organizational skills, strong communication abilities, and a passion for digital marketing. You&amp;#39;ll help drive brand awareness and engagement across multiple channels.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Assist in the development and execution of marketing campaigns across email, social media, digital advertising, and print.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Coordinate content creation efforts including blog posts, newsletters, and social posts in collaboration with designers and copywriters.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain and update marketing calendars and timelines.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Monitor and report on campaign performance using tools like Google Analytics, HubSpot, and social media insights.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Support event planning, trade shows, webinars, and virtual events.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Conduct market research and competitor analysis to identify trends and opportunities.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Help manage CRM and email marketing lists, ensuring data accuracy and segmentation.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with the sales team ', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Bachelor&amp;rsquo;s degree in Marketing, Communications, Business, or a related field.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;1&amp;ndash;2 years of experience in a marketing support or coordination role.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Familiarity with marketing tools such as Mailchimp, HubSpot, Canva, or Hootsuite.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Basic understanding of SEO and content marketing principles is a plus.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Internship experience in marketing or PR can be considered.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong written and verbal communication skills.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Highly organized with excellent attention to detail.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ability to manage multiple projects and meet deadlines.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Creative mindset with an eye for design and brand consistency.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Proficient in Micros', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Competitive base salary with annual performance reviews.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Comprehensive health, dental, and vision insurance.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;401(k) with company matching.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Generous paid time off (vacation, sick days, and holidays).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Professional development budget for courses, certifications, or events.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Hybrid work schedule &amp;ndash; 3 days in-office, 2 remote.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Monthly wellness stipend and employee wellness events.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Fun and inclusive team culture with regular off-site activities.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'testcorp@mail.com', 'Test Corporation', 25, 'emp_imgplaceholder.png', 1, '2025-09-08 06:23:46', 0),
(9, 'Junior Software Developer', 'STN', 'Part Time', 'Hybrid (Remote + On-site)', 3, 'Computing and ICT', '1-3 years', '$35,000–$59,000 annually', 'Apply if you love chaos, glitter, or spreadsheets with soul', '2025-12-30', '&lt;p&gt;We&amp;rsquo;re looking for a motivated and enthusiastic &lt;strong&gt;Junior Software Developer&lt;/strong&gt; to join our agile development team. In this role, you will work alongside experienced engineers to develop, test, and maintain scalable web applications. This is an excellent opportunity for early-career developers who are eager to learn, grow, and contribute to real-world projects.&lt;/p&gt;&lt;p&gt;You&amp;#39;ll have the chance to work on backend systems, APIs, front-end interfaces, and cloud-based applications&amp;mdash;all while following modern development practices like Git version control, CI/CD, and agile workflows.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Assist in designing, coding, testing, and deploying web and mobile applications.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Participate in code reviews, debugging, and troubleshooting across the tech stack.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Write clean, efficient, and well-documented code.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with designers, product managers, and senior developers to understand requirements and deliver features.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Contribute to improving development workflows and documentation.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain and update existing applications based on user feedback and bug reports.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Participate in daily stand-ups and sprint planning sessions.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Bachelor&amp;rsquo;s degree in Computer Science, Software Engineering, Information Technology, or equivalent experience.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;0&amp;ndash;2 years of experience in software development (internships, freelance, or personal projects are valid).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Familiarity with at least one programming language: &lt;strong&gt;JavaScript, Python, Java, or C#&lt;/strong&gt;.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Understanding of web technologies: HTML, CSS, REST APIs, and basic database concepts (SQL/NoSQL).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Exposure to Git, version control workflows, and development tools like VS Code or IntelliJ.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong problem-solving and logical thinking skills.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Eagerness to learn new technologies and frameworks (e.g., React, Node.js, Django, Spring).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ability to fol', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Competitive entry-level salary with growth potential.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Fully remote work with flexible hours.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Paid time off, including vacation days, holidays, and sick leave.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Equipment reimbursement (laptop, monitor, accessories).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Access to online courses, learning platforms, and tech certifications.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Regular mentorship from senior developers and structured onboarding.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Diverse and inclusive team environment with team-building activities.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Pathway for promotion to mid-level developer within 12&amp;ndash;18 months based on performance.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'testcorp@mail.com', 'Test Corporation', 25, 'emp_imgplaceholder.png', 1, '2025-09-08 06:31:00', 2),
(10, 'IT Support Trainee (Entry-Level)', 'MKC', 'Fixed Term', 'On-site Only', 5, 'Computing and ICT', 'No experience needed', '$25,000–$33,000 annually', 'If you\'ve ever tried to code with oven mitts on, we respect the hustle.\" \"Keyboard smashers and spreadsheet ninjas, unite!', '2025-12-30', '&lt;p&gt;We are seeking a tech-savvy and eager-to-learn &lt;strong&gt;IT Support Trainee&lt;/strong&gt; to join our support team. This entry-level role is perfect for individuals looking to start a career in Information Technology without previous industry experience.&lt;/p&gt;&lt;p&gt;You will receive full training on IT systems, hardware, and software support, and gain hands-on experience in troubleshooting, ticketing systems, and IT asset management. By the end of the program, successful trainees may be offered a permanent position in the team.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Provide first-line technical support to internal staff via phone, email, or helpdesk ticketing system.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Assist with diagnosing and resolving common hardware and software issues.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Set up laptops, desktops, and peripheral devices for new hires.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Help with user account creation, password resets, and access management.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Log and document support incidents and solutions for future reference.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Learn to manage printers, network devices, and communication tools.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Support the IT team with hardware inventory, updates, and office tech needs.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Shadow experienced IT technicians to develop troubleshooting and customer service skills.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;&lt;strong&gt;No previous IT experience required&lt;/strong&gt; &amp;ndash; full training provided.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;A college diploma, A-levels, or equivalent is preferred (not required).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Interest in computers, technology, and solving technical problems.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Basic understanding of Windows or macOS operating systems is a plus.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Any IT-related coursework, bootcamp, or certification (e.g., CompTIA A+) is a bonus but not mandatory.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong willingness to learn and grow in a tech role.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Friendly and helpful attitude with good verbal communication.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Problem-solving mindset and attention to detail.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ability to follow instructions and ask questions when needed.&lt;/p&gt;&lt;/li&gt;&l', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Paid, full-time trainee role with pathway to permanent employment.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Hands-on experience with real-world IT systems and issues.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;25 days paid holiday + bank holidays.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;On-the-job mentorship and one-on-one guidance.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Funded training for relevant certifications (e.g., CompTIA, Microsoft).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Company laptop and necessary equipment provided.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Friendly and supportive team environment.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Employee discounts, wellness perks, and social events.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'astroltd@mail.com', 'Astro Ltd', 26, 'demolgastr.jpg', 1, '2025-09-08 06:53:36', 2),
(11, 'Junior Data Entry Assistant', 'RGO', 'Casual', 'On-site Only', 6, 'Computing and ICT', 'No experience needed', '$29 per hour', 'Keyboard smashers and spreadsheet ninjas, unite!', '2025-12-30', '&lt;p&gt;We are seeking a highly organized and detail-oriented &lt;strong&gt;Junior Data Entry Assistant&lt;/strong&gt; to join our IT team. This is an entry-level position requiring no previous work experience &amp;mdash; full training will be provided.&lt;/p&gt;&lt;p&gt;As a Data Entry Assistant, you&amp;#39;ll support the IT department by maintaining accurate system data, updating internal databases, and ensuring digital records are consistently structured and error-free. This is a great starting point for those interested in data management, systems administration, or broader IT support roles.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Input and update data into internal IT systems with accuracy and consistency.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Verify and cross-check data for errors, inconsistencies, or duplications.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Support the migration of data from legacy systems to new platforms.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Assist with maintaining inventory of digital assets, hardware logs, and software license tracking.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Work closely with IT support staff to help document changes in systems and user configurations.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Organize and maintain digital filing systems and data folders.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Follow data entry protocols and adhere to information security guidelines.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;&lt;strong&gt;No previous work experience required&lt;/strong&gt; &amp;mdash; suitable for graduates, school leavers, or career changers.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Completion of secondary education (Leaving Certificate, GCSE, or equivalent) is required.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Basic computer literacy and familiarity with Microsoft Excel or Google Sheets.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Any coursework or certifications in IT, data management, or digital tools is a plus but not essential.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Competitive entry-level salary with structured progression path.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Full training and onboarding program.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;20 days paid annual leave plus public holidays.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Supportive team environment with mentorship opportunities.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Access to internal training on IT systems and data tools.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Company laptop and equipment provided.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Employee wellness and learning budget.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'xyz@employer.com', 'XYZ Employer', 17, 'dmoempl.png', 1, '2025-09-09 03:47:02', 1),
(12, 'Retail Store Supervisor', 'MKC', 'Full Time', 'On-site Only', 1, 'Retail and customer services', '3-6 years', '$71000 annually', 'This job post is for testing purposes. Not an active hiring notice.', '2025-12-30', '&lt;p&gt;We are seeking a motivated and experienced Retail Store Supervisor to lead daily operations at our flagship outlet. You&amp;rsquo;ll ensure excellent customer experience, manage a team, and uphold visual merchandising standards&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Supervise day-to-day store operations&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Train and manage sales associates&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Handle customer complaints and resolve issues promptly&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain store cleanliness and stock levels&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Coordinate with inventory and logistics team&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Bachelor&amp;rsquo;s degree preferred (not mandatory)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;2+ years experience in a retail environment&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong leadership and interpersonal skills&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Health insurance&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Staff discount (20%)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Annual performance bonuses&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Paid time off and sick leave&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'abc@employer.com', 'ABC Employer', 7, 'emplyyr.png', 1, '2025-09-12 09:56:35', 1),
(13, 'Customer Service Associate (Remote)', 'MKC', 'Casual', 'On-site Only', 3, 'Retail and customer services', 'No experience needed', '$29 per hour', 'This job post is for testing purposes. Not an active hiring notice.', '2025-12-30', '&lt;p&gt;As a Customer Service Associate, you will be the first point of contact for our customers. This is a remote role offering flexible shifts, perfect for those who value work-life balance.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Handle incoming calls, emails, and live chat inquiries&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Resolve customer issues professionally and promptly&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain accurate records of customer interactions&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with other departments for complex issues&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Provide feedback to improve customer experience&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;High school diploma or equivalent&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;6 months+ customer service experience&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Familiarity with CRM systems like Zendesk or Freshdesk is a plus&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Remote work flexibility&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Internet stipend&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Weekly wellness sessions&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Career growth opportunities&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'abc@employer.com', 'ABC Employer', 7, 'emplyyr.png', 1, '2025-09-12 09:58:36', 2),
(14, 'Entry-Level IT Support Technician', 'RGO', 'Full Time', 'On-site Only', 4, 'Computing and ICT', 'No experience needed', '$60000 annually', 'This job post is for testing purposes. Not an active hiring notice.', '2025-12-25', '&lt;p&gt;Join our dynamic IT team as an entry-level IT Support Technician. You will provide technical support to end-users, troubleshoot hardware/software issues, and assist in maintaining our company&amp;rsquo;s IT infrastructure.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Respond to user inquiries and provide first-level technical support&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Install, configure, and maintain computer hardware and software&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Assist with network troubleshooting and maintenance&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Document issues and resolutions in the ticketing system&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Escalate complex problems to senior IT staff&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;High school diploma or equivalent&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Basic understanding of computer systems and networks&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong problem-solving and communication skills&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;No prior professional experience required; training provided&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Competitive entry-level salary&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;On-the-job training and career development opportunities&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Health insurance and paid leave&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Supportive and inclusive work environment&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'dummyorg@mail.com', 'dummyorg', 28, 'smpleorg.jpg', 1, '2025-09-13 02:58:31', 5),
(15, 'Software Developer', 'RGO', 'Full Time', 'Hybrid (Remote + On-site)', 2, 'Computing and ICT', '1-3 years', '$79000 annually', 'This job post is for testing purposes. Not an active hiring notice.', '2025-12-26', '&lt;p&gt;We are seeking a skilled Software Developer with 1-3 years of experience to design, develop, and maintain software applications that meet our client&amp;rsquo;s business needs. You will work closely with cross-functional teams to deliver high-quality solutions.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Develop and maintain web and mobile applications&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Write clean, scalable, and efficient code&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with designers, testers, and product owners&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Participate in code reviews and contribute to team best practices&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Troubleshoot and debug issues as they arise&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Stay updated with emerging technologies and industry trends&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Bachelor&amp;rsquo;s degree in Computer Science, Software Engineering, or related field preferred&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;1-3 years of professional software development experience&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Proficiency in programming languages such as Java, Python, or JavaScript&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Familiarity with databases, version control systems, and agile methodologies&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Competitive salary and performance bonuses&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Health, dental, and vision insurance&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Flexible working hours and remote work options&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Professional development and training support&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborative and inclusive workplace culture&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'dummyorg@mail.com', 'Dummy Organization', 28, 'smpleorg.jpg', 1, '2025-09-13 03:11:20', 3),
(16, 'Marketing Coordinator', 'TLO', 'Full Time', 'On-site Only', 1, 'Marketing and Advertising', '1-3 years', '$68900 annually', 'This job post is for testing purposes. Not an active hiring notice.', '2025-12-27', '&lt;p&gt;We are looking for a highly motivated &lt;strong&gt;Marketing Coordinator&lt;/strong&gt; to join our growing team. In this role, you&amp;#39;ll support the planning and execution of multi-channel marketing campaigns, working closely with the creative, content, and performance teams to ensure consistent brand messaging and campaign performance.&lt;/p&gt;&lt;p&gt;This is an ideal role for someone with 1&amp;ndash;3 years of hands-on experience in marketing who is eager to learn, grow, and contribute to a fast-paced team.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Assist in the development, execution, and reporting of marketing campaigns (digital, email, social, and print)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Coordinate with internal teams and external vendors to ensure timely delivery of materials&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Conduct market and competitor research to support strategic planning&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Monitor campaign performance metrics and prepare weekly reports&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Help manage content calendars and schedule social media posts&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Support the development of marketing collateral including brochures, presentations, and newsletters&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain and update website content via CMS&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Bachelor&amp;rsquo;s degree in Marketing, Communications, Business, or a related field&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;1&amp;ndash;3 years of experience in a marketing, advertising, or communications role&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Familiarity with digital marketing platforms (e.g., Google Ads, Meta Business Suite)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Experience with analytics tools (e.g., Google Analytics) is a plus&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong written and verbal communication skills&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Basic knowledge of graphic design tools (e.g., Canva, Adobe Creative Suite) is an advantage&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Competitive salary and performance-based bonuses&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Flexible hybrid work model&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Access to marketing certifications and professional development&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Supportive and creative team environment&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Opportunities for career growth and internal mobility&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Paid annual leave and sick leave&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Team lunches, wellness initiatives, and social events&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'abc@mail.com', 'ABC', 11, 'emp_imgplaceholder.png', 1, '2025-09-15 07:08:16', 9),
(17, 'Financial Analyst', 'DZY', 'Contract', 'Hybrid (Remote + On-site)', 2, 'Financial services', '3-6 years', '$81000 annually', 'This job post is for testing/demo purposes only. Not an active hiring listing.', '2025-12-27', '&lt;p&gt;We are seeking a detail-oriented and analytical &lt;strong&gt;Financial Analyst&lt;/strong&gt; to join our finance team. The successful candidate will play a key role in budgeting, forecasting, financial reporting, and strategic analysis to support data-driven business decisions.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Prepare and analyze monthly, quarterly, and annual financial reports&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Support budgeting, forecasting, and long-term planning processes&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Conduct variance analysis and provide actionable insights to management&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Develop financial models to support business planning and investment decisions&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Assist with cost control and profitability analysis&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with other departments to gather data and align on financial goals&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ensure compliance with internal policies and accounting standards&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Bachelor&amp;#39;s degree in Finance, Accounting, Economics, or a related field&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;3&amp;ndash;6 years of experience in financial analysis or corporate finance&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong proficiency in Excel; knowledge of financial modeling techniques&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Experience with ERP systems (e.g., SAP, Oracle, or similar)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Solid understanding of financial statements and key performance metrics&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Excellent analytical and problem-solving skills&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong attention to detail and the ability to work under pressure&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Competitive salary based on experience&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Annual performance bonuses&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Superannuation and paid leave entitlements&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Professional development and upskilling support&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Hybrid work environment&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Opportunity to work closely with senior leadership&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Access to health and wellness programs&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'demo@employer.com', 'Demo Employer', 22, 'emp_imgplaceholder.png', 1, '2025-09-15 09:33:32', 17),
(18, 'Production Line Assistant', 'TLO', 'Casual', 'On-site Only', 7, 'Manufacturing and production', 'No experience needed', '$31 per hour', 'This is a test job posting for a job portal php project, and not a real job listing.', '2025-12-26', '&lt;p&gt;We are looking for enthusiastic and reliable &lt;strong&gt;Production Line Assistants&lt;/strong&gt; to join our dynamic team in a casual employment capacity. As a Production Line Assistant, you will play a key role in supporting the day-to-day operations of our manufacturing floor. This is an entry-level position, ideal for candidates looking to gain hands-on experience in a fast-paced environment. Full training will be provided.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Assist with setting up and operating production machinery.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Support the production team by maintaining a clean and safe work environment.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Pack and label finished products according to company standards.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Perform quality checks on products and report any defects.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Help with the unloading and organization of materials.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Assist in maintaining inventory control and product storage.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ensure compliance with safety protocols and workplace regulations.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;&lt;strong&gt;Education&lt;/strong&gt;: No formal education required; however, a keen interest in manufacturing is advantageous.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;&lt;strong&gt;Experience&lt;/strong&gt;: No prior experience is required. We provide full on-the-job training.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Flexible working hours with casual shifts available.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Opportunity to gain valuable skills and experience in the manufacturing industry.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Safe and supportive work environment.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Potential to transition into a full-time role based on performance and business needs.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'qorg@mail.com', 'Qwer Org', 29, '', 1, '2025-09-18 05:44:42', 12),
(19, 'PHP Developer', 'STN', 'Full Time', 'Hybrid (Remote + On-site)', 3, 'Computing and ICT', '1-3 years', '$65,000–$78,000 annually', 'This is a dummy job posting intended solely for testing purposes. It is not a real job listing, and no applications will be processed.', '2025-12-19', '&lt;p&gt;We are looking for a passionate and motivated PHP Developer to join our dynamic team in the IT Services &amp;amp; Consulting industry. As a PHP Developer, you will play a key role in developing and maintaining web-based applications, ensuring they meet high-quality standards and user expectations.&lt;/p&gt;&lt;p&gt;You will work with a team of skilled developers and will have the opportunity to improve your technical skills while contributing to a variety of interesting and challenging projects.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Develop, test, and deploy PHP-based web applications.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with cross-functional teams (designers, product managers, and QA engineers) to build scalable solutions.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Troubleshoot and resolve issues related to PHP code and server-side logic.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Write clean, efficient, and reusable code, ensuring optimal performance and maintainability.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Participate in code reviews and ensure adherence to coding standards.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain and update existing applications, introducing new features when necessary.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Stay up to date with the latest industry trends and technologies.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;&lt;strong&gt;Education:&lt;/strong&gt; Bachelor&amp;rsquo;s degree in Computer Science, Information Technology, or a related field (or equivalent practical experience).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;&lt;strong&gt;Experience:&lt;/strong&gt; 1-3 years of professional experience in PHP development, preferably in a consulting or agency setting.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong experience with PHP frameworks (e.g., Laravel, Symfony).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Proficiency in HTML, CSS, JavaScript, and SQL.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Familiarity with version control tools (e.g., Git).&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Competitive salary (AUD 60,000 - AUD 80,000 per annum).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Flexible working hours and remote working opportunities.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Professional development opportunities.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborative and supportive team environment.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Health and wellness benefits.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Access to the latest tools and technologies to enhance your work.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'astroltd@mail.com', 'Astro Ltd', 26, 'demolgastr.jpg', 1, '2025-09-18 09:01:32', 19),
(20, 'UX/UI Designer', 'RGO', 'Casual', 'Hybrid (Remote + On-site)', 4, 'Computing and ICT', '1-3 years', '$33–$37 per hour', 'This is a dummy job posting created for testing purposes. It is not a real job opening. No applications will be processed.', '2025-12-22', '&lt;p&gt;We are looking for a talented and creative &lt;strong&gt;UX/UI Designer&lt;/strong&gt; to join our team in the &lt;strong&gt;Information Media and Telecommunications&lt;/strong&gt; industry. You will play a critical role in shaping the user experience of our web and mobile applications, ensuring they are intuitive, visually appealing, and user-friendly.&lt;/p&gt;&lt;p&gt;You&amp;rsquo;ll collaborate closely with developers, product managers, and other designers to bring innovative concepts to life. This role offers the opportunity to contribute to high-impact projects that shape how users interact with our digital platforms.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Design and create intuitive, user-friendly interfaces for web and mobile applications.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Conduct user research and usability testing to inform design decisions.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Develop wireframes, prototypes, and mockups that clearly communicate design concepts.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Work collaboratively with cross-functional teams to iterate on designs and ensure alignment with business goals.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Translate complex requirements into clear, user-centered design solutions.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ensure designs are consistent with branding guidelines and company standards.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Stay up-to-date with industry trends and best practices in UX/UI design.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Provide feedback and input during code development to ensure design integrity.', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;&lt;strong&gt;Education:&lt;/strong&gt; Bachelor&amp;rsquo;s degree in Graphic Design, Interaction Design, Human-Computer Interaction, or a related field (or equivalent practical experience).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;&lt;strong&gt;Experience:&lt;/strong&gt; 1-3 years of experience in UX/UI design, preferably within the digital media, telecommunications, or tech industry.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Solid experience with design tools such as Sketch, Figma, Adobe XD, or similar.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Familiarity with front-end development (HTML, CSS, JavaScript) is a plus.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong portfolio showcasing UX/UI design skills and design thinking process.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Experience conducting user research, creating wireframes, prototypes, and conducting usability testing.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Excellent communication and collaboration skills, abl', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Competitive salary (AUD 70,000 - AUD 90,000 per annum).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Flexible working hours and remote working opportunities.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ongoing professional development and access to design training resources.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Health and wellness benefits.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Creative and dynamic team culture.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Opportunity to work on diverse and challenging projects in the media and telecommunications sectors.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'testcorp@mail.com', 'Test Corporation', 25, 'tsttcrpsmplelogo.jpg', 1, '2025-09-18 09:21:05', 27),
(21, 'Healthcare Data Analyst', 'TLO', 'Full Time', 'Hybrid (Remote + On-site)', 2, 'Healthcare', '3-6 years', '$75,000–$92,000 annually', 'This is a dummy job posting for testing purposes only. It is not an actual job opening.', '2025-12-23', '&lt;p&gt;We are seeking a skilled &lt;strong&gt;Healthcare Data Analyst&lt;/strong&gt; to join our growing healthcare technology team. In this role, you will help our healthcare organization leverage data to improve patient care, operational efficiency, and overall business performance. You will work closely with clinicians, IT teams, and leadership to analyze and interpret healthcare data, delivering actionable insights that can drive better decision-making across departments.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Analyze and interpret healthcare data to uncover trends, patterns, and opportunities for improvement.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Develop and maintain dashboards, reports, and performance metrics for the clinical and operational teams.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with healthcare professionals to understand data needs and provide meaningful insights to improve patient outcomes and efficiency.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ensure data quality and integrity across all healthcare datasets.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Work with IT teams to optimize data systems, including data cleansing and integration of new data sources.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Provide training and guidance on data tools to staff across various departments.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Participate in cross-functional meetings to identify business challenges and propose data-driven solutions.&lt;/p&gt;&lt;/li&gt;&lt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;&lt;strong&gt;Education&lt;/strong&gt;: Bachelor&amp;rsquo;s degree in Healthcare Analytics, Data Science, Public Health, or a related field.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;&lt;strong&gt;Experience&lt;/strong&gt;: 3-6 years of relevant experience in data analysis or a related role within the healthcare industry.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;&lt;strong&gt;Technical Skills&lt;/strong&gt;:&lt;/p&gt;&lt;ul&gt;&lt;li&gt;&lt;p&gt;Proficiency in SQL, Excel, and data visualization tools (e.g., Tableau, Power BI).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Experience with healthcare data systems (e.g., EHRs, EMRs, health information systems).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Knowledge of statistical analysis tools (e.g., R, Python, SPSS).&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Competitive salary based on experience.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Comprehensive health, dental, and vision insurance plans.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;5 weeks of paid vacation + public holidays.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Annual performance bonus.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Access to professional development programs and certifications.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Work-from-home flexibility (Hybrid work arrangement).&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Company wellness initiatives and mental health support.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'xyz@employer.com', 'XYZ Employer', 17, 'dmoempl.png', 1, '2025-09-19 06:17:40', 3),
(22, 'PHP Backend Developer', 'DZY', 'Full Time', 'Hybrid (Remote + On-site)', 4, 'Computing and ICT', '1-3 years', '$70,000–$89,000 annually', 'This is a dummy job posting used for testing purposes only and is not an actual job opening. We are using this listing to test our job portal platform.', '2025-12-31', '&lt;p&gt;We are looking for a dedicated and enthusiastic PHP Backend Developer to join our talented team. As a PHP Backend Developer, you will be responsible for building server-side applications, ensuring they are scalable, secure, and optimized. This is a great opportunity to further develop your skills while working on diverse projects for clients across various industries. You&amp;rsquo;ll collaborate with front-end developers to ensure seamless integration and contribute to the overall success of our technology solutions.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;Develop and maintain backend components for web applications using PHP.&lt;/li&gt;&lt;li&gt;Build and manage scalable, efficient APIs.&lt;/li&gt;&lt;li&gt;Integrate data storage solutions, including databases such as MySQL, PostgreSQL, or MongoDB.&lt;/li&gt;&lt;li&gt;Troubleshoot, debug, and optimize PHP applications to ensure high performance.&lt;/li&gt;&lt;li&gt;Work closely with front-end developers to integrate user-facing elements with server-side logic.&lt;/li&gt;&lt;li&gt;Participate in the design and architecture of new features.&lt;/li&gt;&lt;li&gt;Write and maintain clear, concise documentation for the systems you build.&lt;/li&gt;&lt;li&gt;Continuously improve applications by reviewing performance and suggesting optimizations.&lt;/li&gt;&lt;li&gt;Participate in code reviews and contribute to a culture of collaboration and learning.&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;Education: Bachelor&amp;rsquo;s degree in Computer Science, Software Engineering, or a related field (or equivalent work experience).&lt;/li&gt;&lt;li&gt;Experience: 1-3 years of experience working as a PHP Developer.&lt;/li&gt;&lt;li&gt;Proficient in PHP and experience with modern PHP frameworks like Laravel, Symfony, or CodeIgniter.&lt;/li&gt;&lt;li&gt;Solid understanding of relational databases (e.g., MySQL, PostgreSQL) and ORMs.&lt;/li&gt;&lt;li&gt;Experience working with RESTful APIs and third-party integrations.&lt;/li&gt;&lt;li&gt;Familiarity with version control tools (Git).&lt;/li&gt;&lt;li&gt;Knowledge of web application security principles (e.g., SQL injection, XSS, CSRF).&lt;/li&gt;&lt;li&gt;Strong communication skills and the ability to collaborate with different teams effectively.&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;Competitive salary and opportunities for career advancement.&lt;/li&gt;&lt;li&gt;Generous leave entitlements, including vacation and sick days.&lt;/li&gt;&lt;li&gt;Flexible working hours and work-from-home options.&lt;/li&gt;&lt;li&gt;Health and wellness benefits, including fitness memberships and mental health support.&lt;/li&gt;&lt;li&gt;Access to professional development resources and certification programs.&lt;/li&gt;&lt;li&gt;A collaborative, innovative, and inclusive work environment.&lt;/li&gt;&lt;li&gt;Social events and team-building activities.&lt;/li&gt;&lt;li&gt;Employee discounts and perks on technology products and services.&lt;/li&gt;&lt;/ul&gt;', 'astroltd@mail.com', 'Astro Ltd', 26, 'demolgastr.jpg', 1, '2025-09-20 01:37:37', 6),
(23, 'AI Software Engineer', 'RGO', 'Full Time', 'Hybrid (Remote + On-site)', 3, 'Computing and ICT', '1-3 years', '$90,000–$120,000 annually', 'This job posting is for testing and educational purposes only. It does not reflect a real job, real company, or real hiring process. Any resemblance to actual employers or positions is purely coincidental.', '2026-01-18', '&lt;p&gt;Kxorin Technology is seeking a passionate AI Software Engineer to join our innovative team. You will work on developing intelligent solutions using machine learning, natural language processing, and predictive analytics to create cutting-edge AI products.&lt;/p&gt;&lt;p&gt;We value creativity, collaboration, and continuous learning. This role provides an opportunity to gain hands-on experience with modern AI frameworks in a hybrid working environment.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;Design, develop, and deploy AI and machine learning models.&lt;/li&gt;&lt;li&gt;Collaborate with cross-functional teams to integrate AI solutions into real-world applications.&lt;/li&gt;&lt;li&gt;Maintain and optimize existing AI software components.&lt;/li&gt;&lt;li&gt;Participate in code reviews and ensure adherence to best practices.&lt;/li&gt;&lt;li&gt;Document and communicate findings, experiments and model performance.&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;Bachelor&amp;#39;s degree in Computer Science, AI, Data Science or related field (or equivalent experience)&lt;/li&gt;&lt;li&gt;1-3 years of experience in AI/ML software development.&lt;/li&gt;&lt;li&gt;Familiarity with Python, TensorFlow, PyTorch or similar frameworks.&lt;/li&gt;&lt;li&gt;Strong problem-solving skills and attention to detail.&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;Flexible hybrid work arrangement.&lt;/li&gt;&lt;li&gt;Learning and development support for AI techs.&lt;/li&gt;&lt;li&gt;Collaborative and inclusive work culture.&lt;/li&gt;&lt;li&gt;Paid annual leave and public holidays.&lt;/li&gt;&lt;/ul&gt;', 'kxorintech@mail.com', 'Kxorin Technology', 32, 'emp_imgplaceholder.png', 1, '2025-12-18 01:21:30', 1);
INSERT INTO `jobs` (`id`, `job_title`, `job_region`, `job_type`, `work_arrangement`, `vacancy`, `job_category`, `experience`, `salary`, `inclusivity_notes`, `application_deadline`, `job_description`, `responsibilities`, `education_experience`, `other_benefits`, `company_email`, `company_name`, `company_id`, `company_image`, `status`, `created_at`, `view_count`) VALUES
(24, 'Machine Learning Researcher', 'TLO', 'Fixed Term', 'On-site Only', 2, 'Computing and ICT', '6-9 years', '$130,000–$162,000 annually', 'This posting is for testing and educational purposes only. Not a real job.', '2026-01-14', '&lt;p&gt;Kxorin Technology is seeking experienced Machine Learning Researchers to join our onsite AI innovation lab. In this role, you will conduct advanced research on cutting-edge machine learning algorithms, design experiments, and implement prototypes to explore novel AI approaches. The ideal candidates will be passionate about AI, highly analytical, and able to translate research insights into practical AI solutions.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Design, implement, and evaluate novel ML algorithms for AI research projects.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Conduct experiments and simulations to validate models.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with engineering teams to transition research prototypes into production-ready solutions.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Publish findings in AI/ML journals and present at conferences.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Mentor junior researchers and interns, providing guidance on ML methodology.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain documentation of research experiments, datasets, and model performance.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Stay up-to-date with the latest developments in AI and ML research.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;PhD in Machine Learning, AI, Computer Science, or related field.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;6-9 years of experience in AI/ML research.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Proven record of published research or patents in AI.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong programming skills in Python, R, or similar languages.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Access to onsite AI research labs and GPU clusters.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Research grants and funding support for innovative projects.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Mentorship and professional development programs.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborative and intellectually stimulating work environment.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Opportunity to contribute to academic publications and conferences.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Paid annual leave and standard onsite employment benefits.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'kxorintech@mail.com', 'Kxorin Technology', 32, '5192574_2281636000.png', 1, '2025-12-18 04:58:02', 0),
(25, 'Data Scientist (AI Focus)', 'DRT', 'Part Time', 'Flexible', 2, 'Computing and ICT', 'No experience needed', '$40–$60 per hour', 'This posting is for testing and educational purposes only. Not a real job.', '2026-01-10', '&lt;p&gt;Kxorin Technology is looking for entry-level Data Scientists to join our AI analytics team. This is an excellent opportunity for students or recent graduates to gain hands-on experience with real-world AI projects in a flexible, part-time role. You will analyze datasets, build basic AI models, and assist senior data scientists in producing actionable insights for internal projects.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Collect, clean, and preprocess structured and unstructured datasets.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Perform exploratory data analysis to identify trends and patterns.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Build and evaluate basic machine learning models under supervision.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Generate reports and visualizations to communicate findings to the team.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Assist in documentation of data pipelines and processes.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with senior data scientists and engineers on AI experiments.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Stay updated on AI/ML best practices and emerging tools.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Bachelor&amp;rsquo;s degree or ongoing studies in Statistics, Data Science, Computer Science, or related field.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;No prior professional experience required; willingness to learn is essential.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Basic programming knowledge (Python, R, or SQL) is preferred.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Flexible working hours suitable for students or part-time professionals.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Hands-on exposure to AI and machine learning projects.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Mentorship from experienced AI and data science professionals.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Opportunity to participate in internal AI workshops and learning sessions.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborative and inclusive team environment.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Experience for building a professional portfolio for future AI/data roles.&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'kxorintech@mail.com', 'Kxorin Technology', 32, '5192574_2281636000.png', 1, '2025-12-18 05:27:10', 0),
(26, 'Network Operations Technician', 'RGO', 'Contract', 'On-site Only', 1, 'Computing and ICT', '3-6 years', '$45–$68 per hour', 'This job listing is created purely for testing and academic demonstration purposes for a Job Portal in PHP MySQL project.\r\nIt does not represent a real job opportunity, and no hiring or employment will take place.', '2026-01-10', '&lt;p&gt;DemoWorks Group is looking for a &lt;strong&gt;Network Operations Technician&lt;/strong&gt; to support and monitor enterprise network infrastructure. The role focuses on maintaining network performance, resolving connectivity issues, and ensuring system availability within an onsite IT environment.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Monitor network performance and system uptime&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Diagnose and resolve LAN/WAN connectivity issues&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Assist in configuration of routers, switches, and firewalls&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Perform routine network maintenance and updates&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain network documentation and incident logs&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Coordinate with senior engineers during outages or upgrades&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Diploma or Degree in &lt;strong&gt;Networking, Information Technology, or related field&lt;/strong&gt;&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;3&amp;ndash;6 years experience in network operations or IT infrastructure support&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Knowledge of:&lt;/p&gt;&lt;ul&gt;&lt;li&gt;&lt;p&gt;TCP/IP, DNS, DHCP&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Network monitoring tools&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Basic firewall and security concepts&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ability to work independently in an onsite environment&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Hands-on experience with real-world network environments&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Exposure to enterprise IT infrastructure&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Skill enhancement in network monitoring and troubleshooting&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Structured operational workflows&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'demoworks@mail.com', 'DemoWorks Group', 33, '444581355555.png', 1, '2025-12-19 22:11:30', 1),
(27, 'Network Operations Center (NOC) Engineer', 'TLO', 'Full Time', 'On-site Only', 2, 'Telecommunications', '3-6 years', '$82,000–$113,000 annually', 'This job posting is created strictly for testing and academic purposes as part of a Job Portal in PHP MySQL project. Wavestra is a fictional organization, and this role does not represent a real employment opportunity.', '2026-01-19', '&lt;p&gt;Wavestra Telecom Servies is&amp;nbsp;seeking a NOC Engineer to monitor and support enterprise-scale telecommunications networks. The role involves real-time monitoring, incident response, and coordination with field and engineering teams to ensure network availability.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;onitor network performance and service alarms (24/7 operations)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Respond to incidents and escalate issues as per SLA&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Perform initial diagnostics on network outages&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain logs, tickets, and operational reports&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Coordinate with field technicians and infrastructure teams&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Degree/Diploma in Telecommunications, Networking, or IT&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Experience with NOC tools and monitoring systems&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Understanding of LAN/WAN, MPLS, and basic routing concepts&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Enterprise network exposure&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Structured shift-based operations&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Skill development in telecom monitoring systems&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'wavestra@mail.com', 'Wavestra Telecom Services', 34, 'emp_imgplaceholder.png', 1, '2025-12-19 22:41:57', 3),
(28, 'Telecom Field Service Technician', 'TLO', 'Casual', 'Flexible', 5, 'Telecommunications', 'No experience needed', '$30–$40 per hour', 'This job posting is created purely for testing and educational purposes as part of a PHP & MySQL job portal project.\r\nIt does not relate to any real employer, job vacancy, or recruitment process.\r\nAll applications and data are used only for system testing and learning.', '2026-01-22', '&lt;p&gt;The &lt;strong&gt;Telecom Field Service Technician&lt;/strong&gt; role involves assisting with basic installation and maintenance tasks for telecom services in a simulated training environment. This posting exists to test job creation, application submission, and workflow features within an academic job portal system.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Assist in installing telecom equipment (simulated)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Support maintenance and service activities&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Perform basic cable and hardware checks&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Record service activities and reports&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Follow safety and operational guidelines&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Communicate with supervisors and team members&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Minimum qualification: &lt;strong&gt;High school certificate or equivalent&lt;/strong&gt;&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;No prior telecom experience required (training provided for testing)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Basic technical or IT knowledge is an advantage&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Willingness to learn and follow instructions&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Entry-level exposure to telecom services&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Flexible working hours&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Hands-on learning opportunity&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Suitable for students and fresh graduates&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Supports testing of casual job workflows&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'wavestra@mail.com', 'Wavestra Telecom Services', 34, '7777237747477.png', 1, '2025-12-21 00:31:15', 1),
(29, 'DevOps Engineer', 'DZY', 'Full Time', 'Hybrid (Remote + On-site)', 2, 'Computing and ICT', '3-6 years', '$93,000–$135,000 annually', 'This job posting is created strictly for testing and educational purposes within a PHP & MySQL job portal project.\r\nIt does not represent a real IT company or real employment opportunity.\r\nAll submissions are used only for system testing, form validation, and workflow demonstration.', '2026-01-12', '&lt;p&gt;The &lt;strong&gt;DevOps Engineer&lt;/strong&gt; will assist in maintaining CI/CD pipelines, server deployments, and cloud infrastructure in a &lt;strong&gt;simulated IT company environment&lt;/strong&gt;.&lt;br /&gt;This position allows testing of IT-specific job postings, workflow submissions, and employer-side portal features.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Build and maintain CI/CD pipelines for web applications&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Manage cloud-based servers and deployments (simulated environment)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Monitor application performance and troubleshoot issues&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with development and QA teams&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Automate repetitive deployment and testing tasks&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain documentation of infrastructure and deployment processes&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Bachelor&amp;rsquo;s degree in &lt;strong&gt;Computer Science, IT, or related field&lt;/strong&gt;&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;3&amp;ndash;6 years experience in DevOps, cloud, or infrastructure management (academic/project experience acceptable)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Familiarity with &lt;strong&gt;Linux, Docker, Jenkins, Git, AWS/Azure&lt;/strong&gt;&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Knowledge of scripting languages like &lt;strong&gt;Bash, Python, or PHP&lt;/strong&gt;&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Hybrid working model&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Hands-on experience with DevOps workflows (testing only)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Exposure to cloud and CI/CD practices&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Suitable for mid-level developers&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Safe environment for testing job portal functionality&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'astroltd@mail.com', 'Astro Ltd', 26, 'demolgastr.jpg', 1, '2025-12-21 00:51:44', 0),
(30, 'Mobile Application Performance Engineer', 'DZY', 'Full Time', 'Flexible', 1, 'Computing and ICT', '3-6 years', '$98,000–$132,000 annually', 'This job posting is created only for academic testing purposes within an educational online job portal project.\r\nIt does not represent a real job, employer, or hiring activity.', '2026-01-04', '&lt;p&gt;The Mobile Application Performance Engineer focuses on analyzing, optimizing, and improving the performance of Android and iOS applications under various load and network conditions.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Monitor mobile app performance metrics&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Identify memory leaks and bottlenecks&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Optimize app startup time and responsiveness&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Perform stress and load testing&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with mobile developers on improvements&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Degree in Computer Science or related field&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;3&amp;ndash;6 years experience in mobile app development or testing&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Familiarity with Android Studio and Xcode profiling tools&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Understanding of REST APIs and mobile networking&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Flexible working model&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Performance-focused technical exposure&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Learning-oriented development environment&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'astroltd@mail.com', 'Astro Ltd', 26, 'demolgastr.jpg', 1, '2025-12-24 05:40:28', 4),
(31, 'Administrative Operations Coordinator', 'DRT', 'Full Time', 'Hybrid (Remote + On-site)', 1, 'Administration, business and management', '1-3 years', '$60,000–$77,000 annually', 'This job posting is entirely fictional and has been created only for testing and educational purposes as part of an online job portal development project.\r\nIt does not represent a real organization, role, or hiring process, and no real-world applications are expected.', '2026-01-05', '&lt;p&gt;The Administrative Operations Coordinator supports day-to-day office operations, documentation workflows, and internal coordination between teams. This role focuses on process organization, record management, and administrative system support in a simulated work environment.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Coordinate daily administrative tasks and schedules&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain digital and physical records&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Assist with internal communication and reporting&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Support basic HR and finance documentation processes&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Monitor office supplies and administrative tools&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ensure adherence to internal procedures and policies&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Diploma or degree in Business Administration or related field&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;1&amp;ndash;3 years experience in an administrative or coordination role&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Proficiency with office productivity tools&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong organizational and communication skills&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Hybrid working arrangement&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Structured administrative workflow exposure&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Skill development in office operations&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Supportive learning-focused environment&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'testcorp@mail.com', 'Test Corporation', 25, 'tsttcrpsmplelogo.jpg', 1, '2025-12-24 05:39:00', 2),
(32, 'Network Monitoring Analyst', 'DZY', 'Contract', 'Fully Remote', 4, 'Telecommunications', '3-6 years', '$45–$66 per hour', 'This job listing is created exclusively for educational and system testing purposes within a demo job portal application. It does not represent an actual employment opportunity, does not involve real recruitment, and is not associated with any real company or organization', '2026-01-12', '&lt;p&gt;We are looking for a Network Monitoring Analyst to support continuous monitoring of network systems and services. The role focuses on identifying alerts, analyzing performance trends, and ensuring timely escalation of incidents in a remote working environment.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Monitor network dashboards and alerting tools&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Identify, log, and escalate network incidents&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Perform initial analysis of network performance issues&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Maintain accurate incident and shift handover records&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Follow escalation paths and operational procedures&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Collaborate with remote team members during incidents&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Bachelor&amp;rsquo;s degree or equivalent experience in IT or Networking&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Strong understanding of network monitoring concepts&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Experience with monitoring tools is an advantage&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ability to work independently in a remote setup&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Fully remote work arrangement&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Flexible contract-based engagement&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Consistent working hours&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Exposure to enterprise network monitoring environments&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Skill enhancement through real-time monitoring tasks&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'nng@mail.com', 'Nestquay Network Group', 36, 'nng77767.png', 1, '2025-12-25 05:48:27', 5),
(33, 'Customer Support Associate', 'DZY', 'Part Time', 'Fully Remote', 5, 'Telecommunications', 'No experience needed', '$28–$35 per hour', 'This job posting is created solely for testing and educational purposes as part of a PHP & MySQL job portal project. It does not represent a real job, employer, or hiring activity. All details are fictional and intended only to test application workflows, form validations, and database operations.', '2025-12-31', '&lt;p&gt;We are looking for a Part-Time Customer Support Associate to assist customers with general telecom service inquiries. This role is designed for flexible schedules and is ideal for testing part-time job listings, remote work filters, and hourly salary handling within the system.&lt;/p&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Handle basic customer inquiries via chat and email&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Provide information about telecom plans and services&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Log customer interactions accurately in the system&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Follow standard customer service procedures&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Escalate technical issues to senior support when required&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Minimum qualification: High School Diploma or equivalent&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;No prior experience required (training provided)&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Basic communication and computer skills&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Ability to work independently in a remote environment&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', '&lt;ul&gt;&lt;li&gt;&lt;p&gt;Flexible part-time working hours&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Fully remote work setup&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Entry-level opportunity&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Skill development and training&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Supportive virtual team&lt;/p&gt;&lt;/li&gt;&lt;/ul&gt;', 'wavestra@mail.com', 'Wavestra Telecom Services', 34, '7777237747477.png', 1, '2025-12-26 01:52:10', 4),
(34, 'Sample Job A', 'DZY', 'Fixed Term', 'Flexible', 5, 'Computing and ICT', '3-6 years', '$75,800–$110,000 annually', 'this is a demo test for testing purposes only.', '2026-01-22', '&lt;p&gt;Here goes a summary of job description.&lt;/p&gt;', '&lt;ol&gt;&lt;li&gt;Here goes a list of all the responsibilities&lt;/li&gt;&lt;li&gt;required&amp;nbsp;&lt;/li&gt;&lt;li&gt;for&lt;/li&gt;&lt;li&gt;the&lt;/li&gt;&lt;li&gt;job&lt;/li&gt;&lt;/ol&gt;', '&lt;ol&gt;&lt;li&gt;Education 11&lt;/li&gt;&lt;li&gt;Education 2&lt;/li&gt;&lt;li&gt;Education 3&lt;/li&gt;&lt;li&gt;Experience required in A&lt;/li&gt;&lt;li&gt;Experience required in B&lt;/li&gt;&lt;li&gt;and many more. . . .&lt;/li&gt;&lt;/ol&gt;', '&lt;ol&gt;&lt;li&gt;Benefits of this job posting A&lt;/li&gt;&lt;li&gt;qwerty b&lt;/li&gt;&lt;li&gt;aaaassssdddd&lt;/li&gt;&lt;li&gt;qweqwe&lt;/li&gt;&lt;li&gt;88&lt;/li&gt;&lt;/ol&gt;', 'astroltd@mail.com', 'Astro Ltd', 26, 'demolgastr.jpg', 1, '2025-12-29 22:51:37', 2);

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(3) NOT NULL,
  `fullname` varchar(155) NOT NULL,
  `username` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `contact` int(11) NOT NULL,
  `cv` varchar(200) NOT NULL,
  `worker_id` int(3) NOT NULL,
  `job_id` int(3) NOT NULL,
  `job_title` varchar(200) NOT NULL,
  `company_id` int(3) NOT NULL,
  `application_status` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `withdrawn_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`id`, `fullname`, `username`, `email`, `contact`, `cv`, `worker_id`, `job_id`, `job_title`, `company_id`, `application_status`, `created_at`, `withdrawn_at`) VALUES
(15, 'Mark', 'mark', 'mark@mail.com', 1777775454, 'dummy.pdf', 15, 3, 'Sample Job', 16, 0, '2023-10-15 05:25:46', NULL),
(16, 'Henry', 'henry', 'henry@mail.com', 1010101010, 'dummy.pdf', 13, 3, 'Sample Job', 16, 0, '2023-10-19 22:29:48', NULL),
(31, 'Sandbox User', 'sandboxuser', 'sandboxuser@mail.com', 1111111110, 'DummyCV.pdf', 27, 7, 'Customer Support Agent', 25, 3, '2025-09-08 07:20:34', NULL),
(34, 'Harry Denn', 'harryden', 'harryden@mail.com', 2147483647, 'cv_1757475245_DummyCV.pdf', 24, 10, 'IT Support Trainee (Entry-Level)', 26, 3, '2025-09-10 03:34:06', NULL),
(35, 'Harry Denn', 'harryden', 'harryden@mail.com', 2147483647, 'cv_1757475245_DummyCV.pdf', 24, 8, 'Marketing Coordinator', 25, 3, '2025-09-10 04:28:34', NULL),
(36, 'Sandbox User', 'sandboxuser', 'sandboxuser@mail.com', 1111111110, 'cv_27_1758342060_e027.pdf', 27, 22, 'PHP Backend Developer', 26, 1, '2025-09-20 04:27:16', NULL),
(37, 'Dummy User', 'dummyuser', 'dummyuser@mail.com', 2147483647, 'cv_30_1758342662_46e9.pdf', 30, 22, 'PHP Backend Developer', 26, 1, '2025-09-20 04:33:22', NULL),
(38, 'Virtual Account', 'virtualacc', 'virtualacc@mail.com', 1110101011, 'cv_35_1766276459_f32f.pdf', 35, 27, 'Network Operations Center (NOC) Engineer', 34, 0, '2025-12-25 05:13:19', NULL),
(39, 'Trial Account', 'trialaccount', 'trialacc@mail.com', 777707777, 'cv_37_1766713512_cafb.pdf', 37, 30, 'Mobile Application Performance Engineer', 26, 0, '2025-12-26 05:00:17', NULL),
(40, 'Alpha User', 'alphauser', 'alphauser@mail.com', 444414444, 'cv_38_1766725541_639c.pdf', 38, 27, 'Network Operations Center (NOC) Engineer', 34, 0, '2025-12-26 05:13:02', NULL),
(41, 'Alpha User', 'alphauser', 'alphauser@mail.com', 444414444, 'cv_38_1766725541_639c.pdf', 38, 26, 'Network Operations Technician', 33, 1, '2025-12-26 05:16:06', NULL),
(42, 'Alpha User', 'alphauser', 'alphauser@mail.com', 444414444, 'cv_38_1766725541_639c.pdf', 38, 30, 'Mobile Application Performance Engineer', 26, 3, '2025-12-29 22:42:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_app_answers`
--

CREATE TABLE `job_app_answers` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `answer_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_app_answers`
--

INSERT INTO `job_app_answers` (`id`, `application_id`, `question_id`, `answer_text`, `answer_file`, `created_at`) VALUES
(1, 37, 1, 'I have experience with Symfony and Zend Framework. I used Symfony to develop an e-commerce site for a small business, integrating payment gateways and user management. It helped streamline the process with its reusable components and built-in security features.', NULL, '2025-09-20 04:33:22'),
(2, 37, 2, 'I focus on optimizing database queries, using prepared statements to prevent redundancy. I also implement lazy loading for resources and reduce file size by using Gzip compression. I monitor app performance using tools like Xdebug and New Relic to identify bottlenecks.', NULL, '2025-09-20 04:33:22'),
(3, 37, 3, 'I once worked on a large data migration project where the PHP script timed out during long-running processes. I solved it by breaking the task into smaller chunks, using batch processing and queue systems like Laravel Horizon to handle large datasets efficiently.', NULL, '2025-09-20 04:33:22'),
(4, 38, 28, 'Yes', NULL, '2025-12-25 05:13:19'),
(5, 38, 29, '[\"Rotational\"]', NULL, '2025-12-25 05:13:19'),
(6, 38, 30, 'I handled a major network outage where multiple users lost connectivity due to a core switch failure. I identified the issue by checking network alerts and logs, isolated the faulty device, and coordinated with the team to restore services using a backup switch. After resolving the incident, I documented the root cause and helped implement preventive measures to avoid similar issues in the future', NULL, '2025-12-25 05:13:19'),
(7, 38, 31, '3-5 years', NULL, '2025-12-25 05:13:19'),
(8, 38, 32, 'Yes', NULL, '2025-12-25 05:13:19'),
(9, 39, 43, 'Android', NULL, '2025-12-26 05:00:17'),
(10, 39, 44, 'I noticed the app was taking a long time to load data from the server. I optimized the network calls by batching requests and caching responses, which reduced load time by about 40%', NULL, '2025-12-26 05:00:17'),
(11, 39, 45, 'Yes', NULL, '2025-12-26 05:00:17'),
(12, 39, 46, '[\"Android\"]', NULL, '2025-12-26 05:00:17'),
(13, 39, 47, 'Both', NULL, '2025-12-26 05:00:17'),
(14, 40, 28, 'Yes', NULL, '2025-12-26 05:13:02'),
(15, 40, 29, '[\"Rotational\"]', NULL, '2025-12-26 05:13:02'),
(16, 40, 30, 'During peak hours, a core router experienced intermittent connectivity issues, causing multiple services to go down. I quickly identified the root cause as a misconfigured routing protocol, applied the correct configuration, and monitored the network until stability was restored. The incident was resolved within 45 minutes, minimizing downtime for users', NULL, '2025-12-26 05:13:02'),
(17, 40, 31, '0-2 years', NULL, '2025-12-26 05:13:02'),
(18, 40, 32, 'Yes', NULL, '2025-12-26 05:13:02'),
(19, 41, 23, 'CCNA, CompTIA Network+', NULL, '2025-12-26 05:16:06'),
(20, 41, 24, 'Yes', NULL, '2025-12-26 05:16:06'),
(21, 41, 25, '[\"Network monitoring tools\",\"Command-line networking utilities\"]', NULL, '2025-12-26 05:16:06'),
(22, 41, 26, 'A branch office was experiencing frequent packet loss and slow connectivity. I traced the problem to a misconfigured VLAN on the switch and outdated firmware on the router. After correcting the VLAN setup and updating the firmware, network performance stabilized, and downtime was minimized', NULL, '2025-12-26 05:16:06'),
(23, 41, 27, 'Yes', NULL, '2025-12-26 05:16:06'),
(24, 42, 43, 'test a', NULL, '2025-12-29 22:42:22'),
(25, 42, 44, 'test b', NULL, '2025-12-29 22:42:22'),
(26, 42, 45, 'Yes', NULL, '2025-12-29 22:42:22'),
(27, 42, 46, '[\"Both\"]', NULL, '2025-12-29 22:42:22'),
(28, 42, 47, 'Both', NULL, '2025-12-29 22:42:22');

-- --------------------------------------------------------

--
-- Table structure for table `job_questions`
--

CREATE TABLE `job_questions` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `source` varchar(20) NOT NULL,
  `bank_id` int(11) DEFAULT NULL,
  `question_text` text NOT NULL,
  `qtype` varchar(20) NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `options` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_questions`
--

INSERT INTO `job_questions` (`id`, `job_id`, `source`, `bank_id`, `question_text`, `qtype`, `is_required`, `options`, `sort_order`, `created_at`) VALUES
(1, 22, 'custom', NULL, 'What PHP frameworks do you have experience with? Can you share a project where you used one of these frameworks?', 'textarea', 1, NULL, 100, '2025-09-20 01:37:37'),
(2, 22, 'custom', NULL, 'How do you approach optimizing the performance of a PHP-based application?', 'textarea', 1, NULL, 102, '2025-09-20 01:37:37'),
(3, 22, 'custom', NULL, 'Describe a time when you faced a difficult technical issue while developing with PHP. How did you resolve it?', 'textarea', 1, NULL, 103, '2025-09-20 01:37:37'),
(4, 23, 'custom', NULL, 'Are you legally authorized to work?', 'yesno', 1, NULL, 100, '2025-12-18 01:21:30'),
(5, 23, 'custom', NULL, 'Please describe your experience with machine learning frameworks.', 'textarea', 1, NULL, 101, '2025-12-18 01:21:30'),
(6, 23, 'custom', NULL, 'Which programming languages are you proficient in?', 'mcq', 1, '[\"Python\",\"Java\",\"C++\",\"R\",\"Others\"]', 102, '2025-12-18 01:21:30'),
(7, 23, 'custom', NULL, 'What is your expected annual salary?', 'text', 1, NULL, 103, '2025-12-18 01:21:30'),
(8, 23, 'custom', NULL, 'How soon can you start if selected?', 'dropdown', 1, '[\"Immediately\",\"2 weeks\",\"1 month\",\"2+ months\"]', 104, '2025-12-18 01:21:30'),
(9, 23, 'custom', NULL, 'Are you comfortable working in a hybrid work environment?', 'yesno', 1, NULL, 105, '2025-12-18 01:21:30'),
(10, 23, 'custom', NULL, 'Do you have experience working with cloud AI platforms?', 'yesno', 1, NULL, 106, '2025-12-18 01:21:30'),
(11, 24, 'custom', NULL, 'Do you have a PhD in relevant field?', 'yesno', 1, NULL, 100, '2025-12-18 04:58:02'),
(12, 24, 'custom', NULL, 'List your top 3 research publications', 'textarea', 1, NULL, 101, '2025-12-18 04:58:02'),
(13, 24, 'custom', NULL, 'Experience mentoring junior researchers?', 'yesno', 1, NULL, 102, '2025-12-18 04:58:02'),
(14, 24, 'custom', NULL, 'Are you willing to work onsite?', 'yesno', 1, NULL, 103, '2025-12-18 04:58:02'),
(15, 24, 'custom', NULL, 'Preferred research area?', 'dropdown', 1, '[\"NLP\",\"Computer Vision\",\"Reinforcement Learning\",\"Other\"]', 104, '2025-12-18 04:58:02'),
(16, 24, 'custom', NULL, 'Do you have experience with GPU clusters?', 'yesno', 1, NULL, 105, '2025-12-18 04:58:02'),
(17, 24, 'custom', NULL, 'Are you comfortable collaborating with multi-disciplinary teams?', 'yesno', 1, NULL, 106, '2025-12-18 04:58:02'),
(18, 25, 'custom', NULL, 'Describe any prior AI or data experience.', 'textarea', 1, NULL, 100, '2025-12-18 05:27:10'),
(19, 25, 'custom', NULL, 'Preferred working days?', 'dropdown', 1, '[\"Mon-Wed\",\"Tue-Thurs\",\"Flexible\",\"Other\"]', 101, '2025-12-18 05:27:10'),
(20, 25, 'custom', NULL, 'Are you comfortable working part-time?', 'yesno', 1, NULL, 102, '2025-12-18 05:27:10'),
(21, 25, 'custom', NULL, 'Which data analysis tools do you know?', 'mcq', 1, '[\"Python\",\"R\",\"Excel\",\"SQL\",\"Other\"]', 103, '2025-12-18 05:27:10'),
(22, 25, 'custom', NULL, 'Are you open to learning new AI frameworks?', 'yesno', 1, NULL, 104, '2025-12-18 05:27:10'),
(23, 26, 'custom', NULL, 'Which networking certifications do you currently hold (if any)?', 'text', 1, NULL, 100, '2025-12-19 22:11:30'),
(24, 26, 'custom', NULL, 'Do you have hands-on experience with routers and switches?', 'yesno', 1, NULL, 101, '2025-12-19 22:11:30'),
(25, 26, 'custom', NULL, 'Which networking tools have you used most frequently?', 'mcq', 1, '[\"Network monitoring tools\",\"Firewall management tools\",\"Command-line networking utilities\",\"None of the above\"]', 102, '2025-12-19 22:11:30'),
(26, 26, 'custom', NULL, 'Describe a network issue you have resolved in your previous role.', 'textarea', 1, NULL, 103, '2025-12-19 22:11:30'),
(27, 26, 'custom', NULL, 'Are you willing to work fully onsite as required by this role?', 'yesno', 1, NULL, 104, '2025-12-19 22:11:30'),
(28, 27, 'custom', NULL, 'Do you have experience working in a NOC environment?', 'yesno', 1, NULL, 100, '2025-12-19 22:41:57'),
(29, 27, 'custom', NULL, 'Which shift are you comfortable with?', 'mcq', 1, '[\"Day\",\"Night\",\"Rotational\"]', 101, '2025-12-19 22:41:57'),
(30, 27, 'custom', NULL, 'Describe a major network incident you handled.', 'textarea', 1, NULL, 102, '2025-12-19 22:41:57'),
(31, 27, 'custom', NULL, 'Years of NOC experience', 'dropdown', 1, '[\"0-2 years\",\"3-5 years\",\"6+ years\"]', 103, '2025-12-19 22:41:57'),
(32, 27, 'custom', NULL, 'Are you willing to work onsite in rotational shifts?', 'yesno', 1, NULL, 104, '2025-12-19 22:41:57'),
(33, 28, 'custom', NULL, 'What is your current highest level of education?', 'text', 1, NULL, 100, '2025-12-21 00:31:15'),
(34, 28, 'custom', NULL, 'Are you willing to perform field-based work when required?', 'yesno', 1, NULL, 101, '2025-12-21 00:31:15'),
(35, 28, 'custom', NULL, 'Which area are you most interested in?', 'mcq', 1, '[\"Installation\",\"Maintenance\",\"Network Support\",\"Customer Assistance\"]', 102, '2025-12-21 00:31:15'),
(36, 28, 'custom', NULL, 'Do you hold a valid driver’s license?', 'yesno', 1, NULL, 103, '2025-12-21 00:31:15'),
(37, 28, 'custom', NULL, 'Why are you interested in working in the telecom services industry?', 'textarea', 1, NULL, 104, '2025-12-21 00:31:15'),
(38, 29, 'custom', NULL, 'Which DevOps tools and technologies are you most familiar with?', 'text', 1, NULL, 100, '2025-12-21 00:51:44'),
(39, 29, 'custom', NULL, 'Describe a project where you set up or managed CI/CD pipelines.', 'textarea', 1, NULL, 101, '2025-12-21 00:51:44'),
(40, 29, 'custom', NULL, 'Do you have experience working with cloud platforms (AWS, Azure, or GCP)?', 'yesno', 1, NULL, 102, '2025-12-21 00:51:44'),
(41, 29, 'custom', NULL, 'Which area do you specialize in?', 'mcq', 1, '[\"CI\\/CD Automation\",\"Cloud Infrastructure\",\"Containerization (Docker\\/Kubernetes)\",\"Scripting\\/Automation\"]', 103, '2025-12-21 00:51:44'),
(42, 29, 'custom', NULL, 'Preferred cloud platform?', 'mcq', 1, '[\"AWS\",\"Azure\",\"GCP\",\"None\"]', 104, '2025-12-21 00:51:44'),
(43, 30, 'custom', NULL, 'Which mobile platforms have you worked with?', 'text', 1, NULL, 100, '2025-12-24 02:11:28'),
(44, 30, 'custom', NULL, 'Describe a performance issue you identified and fixed.', 'textarea', 1, NULL, 101, '2025-12-24 02:11:28'),
(45, 30, 'custom', NULL, 'Have you used profiling tools for mobile apps?', 'yesno', 1, NULL, 102, '2025-12-24 02:11:28'),
(46, 30, 'custom', NULL, 'Primary mobile platform experience', 'mcq', 1, '[\"Android\",\"iOS\",\"Both\"]', 103, '2025-12-24 02:11:28'),
(47, 30, 'custom', NULL, 'Preferred testing environment', 'dropdown', 1, '[\"Emulator\",\"Real Devices\",\"Both\"]', 104, '2025-12-24 02:11:28'),
(48, 31, 'custom', NULL, 'What administrative tools or software are you familiar with?', 'text', 1, NULL, 100, '2025-12-24 05:39:00'),
(49, 31, 'custom', NULL, 'Describe your experience handling documentation or records.', 'textarea', 1, NULL, 101, '2025-12-24 05:39:00'),
(50, 31, 'custom', NULL, 'Do you have prior experience supporting office operations?', 'yesno', 1, NULL, 102, '2025-12-24 05:39:00'),
(51, 31, 'custom', NULL, 'Which task do you feel most confident handling?', 'mcq', 1, '[\"Scheduling and Calendar Management\",\"Documentation and Filing\",\"Internal Communication\",\"Reporting and Data Entry\"]', 103, '2025-12-24 05:39:00'),
(52, 32, 'custom', NULL, 'Which network monitoring tools have you used previously?', 'text', 1, NULL, 100, '2025-12-25 05:48:27'),
(53, 32, 'custom', NULL, 'Describe how you would respond to a critical network alert.', 'textarea', 1, NULL, 101, '2025-12-25 05:48:27'),
(54, 32, 'custom', NULL, 'Have you worked in a 24/7 monitoring environment before?', 'yesno', 1, NULL, 102, '2025-12-25 05:48:27'),
(55, 32, 'custom', NULL, 'Availability to start:', 'dropdown', 1, '[\"Immediately\",\"Within 1 week\",\"Within 2 weeks\",\"Within 1 month\"]', 103, '2025-12-25 05:48:27'),
(56, 32, 'custom', NULL, 'Are you comfortable working fully remotely?', 'yesno', 1, NULL, 104, '2025-12-25 05:48:27'),
(57, 33, 'custom', NULL, 'Why are you interested in a part-time customer support role?', 'textarea', 1, NULL, 100, '2025-12-26 01:52:10'),
(58, 33, 'custom', NULL, 'Are you available to work flexible hours, including weekends if needed?', 'yesno', 1, NULL, 101, '2025-12-26 01:52:10'),
(59, 33, 'custom', NULL, 'Which shift do you prefer?', 'mcq', 1, '[\"Morning\",\"Afternoon\",\"Evening\",\"Flexible\"]', 102, '2025-12-26 01:52:10'),
(60, 33, 'custom', NULL, 'Do you have access to a stable internet connection for remote work?', 'yesno', 1, NULL, 103, '2025-12-26 01:52:10'),
(61, 34, 'custom', NULL, 'Custom Ques 1', 'textarea', 1, NULL, 100, '2025-12-29 22:51:37'),
(62, 34, 'custom', NULL, 'Custom Ques 2', 'yesno', 1, NULL, 101, '2025-12-29 22:51:37'),
(63, 34, 'custom', NULL, 'Custom Ques 3', 'dropdown', 1, '[\"A\",\"B\",\"C\",\"None\"]', 102, '2025-12-29 22:51:37');

-- --------------------------------------------------------

--
-- Table structure for table `job_regions`
--

CREATE TABLE `job_regions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `job_regions`
--

INSERT INTO `job_regions` (`id`, `name`, `code`, `status`) VALUES
(1, 'Testland One', 'TLO', 1),
(2, 'Dummy Zone Y', 'DZY', 1),
(3, 'DevRegion Test', 'DRT', 1),
(4, 'Mocktopia Central', 'MKC', 1),
(5, 'Staging Area North', 'STN', 1),
(6, 'Region Omega', 'RGO', 1);

-- --------------------------------------------------------

--
-- Table structure for table `job_views`
--

CREATE TABLE `job_views` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `viewer_user_id` int(11) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `viewed_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_views`
--

INSERT INTO `job_views` (`id`, `job_id`, `company_id`, `viewer_user_id`, `viewed_at`, `viewed_date`) VALUES
(1, 22, 26, 13, '2025-12-18 00:31:34', '2025-12-18'),
(3, 22, 26, 27, '2025-12-18 00:37:52', '2025-12-18'),
(4, 23, 32, NULL, '2025-12-20 01:38:33', '2025-12-20'),
(5, 18, 29, NULL, '2025-12-21 00:34:01', '2025-12-21'),
(6, 27, 34, 35, '2025-12-21 00:36:15', '2025-12-21'),
(8, 20, 25, 30, '2025-12-21 00:42:00', '2025-12-21'),
(9, 31, 25, 27, '2025-12-25 05:08:34', '2025-12-25'),
(10, 27, 34, 35, '2025-12-25 05:11:06', '2025-12-25'),
(13, 32, 36, NULL, '2025-12-25 05:54:46', '2025-12-25'),
(14, 32, 36, NULL, '2025-12-26 01:11:11', '2025-12-26'),
(15, 33, 34, NULL, '2025-12-26 04:40:10', '2025-12-26'),
(16, 32, 36, NULL, '2025-12-26 04:40:45', '2025-12-26'),
(17, 33, 34, 37, '2025-12-26 04:42:00', '2025-12-26'),
(18, 32, 36, 37, '2025-12-26 04:48:35', '2025-12-26'),
(21, 28, 34, 37, '2025-12-26 04:56:21', '2025-12-26'),
(24, 30, 26, 37, '2025-12-26 04:57:22', '2025-12-26'),
(27, 27, 34, 38, '2025-12-26 05:11:46', '2025-12-26'),
(29, 26, 33, 38, '2025-12-26 05:14:30', '2025-12-26'),
(32, 33, 34, NULL, '2025-12-29 02:20:59', '2025-12-29'),
(33, 32, 36, NULL, '2025-12-29 02:21:08', '2025-12-29'),
(34, 30, 26, NULL, '2025-12-29 02:21:12', '2025-12-29'),
(35, 31, 25, NULL, '2025-12-29 02:21:19', '2025-12-29'),
(36, 7, 25, 27, '2025-12-29 02:33:10', '2025-12-29'),
(37, 33, 34, 38, '2025-12-29 22:38:43', '2025-12-30'),
(38, 22, 26, 38, '2025-12-29 22:39:44', '2025-12-30'),
(40, 30, 26, 38, '2025-12-29 22:41:28', '2025-12-30'),
(42, 34, 26, NULL, '2025-12-29 22:55:13', '2025-12-30'),
(43, 34, 26, 38, '2025-12-29 22:56:16', '2025-12-30'),
(44, 30, 26, NULL, '2025-12-29 22:56:51', '2025-12-30');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `recipient_user_id` int(11) NOT NULL,
  `actor_user_id` int(11) DEFAULT NULL,
  `type` enum('new_application','app_status_update','system') NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` varchar(500) NOT NULL,
  `link_path` varchar(255) DEFAULT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT 0,
  `seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `recipient_user_id`, `actor_user_id`, `type`, `job_id`, `application_id`, `title`, `message`, `link_path`, `seen`, `seen_at`, `created_at`) VALUES
(1, 24, 26, 'app_status_update', 10, 34, 'Application update: Not Selected', 'Your application for “IT Support Trainee (Entry-Level)” at Astro Ltd was not successful this time. We appreciate your interest and the time you invested—please keep an eye on new roles that match your skills, and feel free to apply again.', 'http://localhost/online-job-portal-php-mysql/users/applied_jobs.php', 0, NULL, '2025-12-21 00:44:28'),
(2, 27, 26, 'app_status_update', 22, 36, 'Application update: In Review', 'Your application for “PHP Backend Developer” at Astro Ltd is currently under review. The employer is assessing applications and will contact shortlisted candidates.', 'http://localhost/online-job-portal-php-mysql/users/applied_jobs.php', 0, NULL, '2025-12-21 00:44:37'),
(3, 30, 26, 'app_status_update', 22, 37, 'Application update: In Review', 'Your application for “PHP Backend Developer” at Astro Ltd is currently under review. The employer is assessing applications and will contact shortlisted candidates.', 'http://localhost/online-job-portal-php-mysql/users/applied_jobs.php', 0, NULL, '2025-12-21 00:44:52'),
(4, 27, 25, 'app_status_update', 7, 31, 'Application update: Not Selected', 'Your application for “Customer Support Agent” at testcorp was not successful this time. We appreciate your interest and the time you invested—please keep an eye on new roles that match your skills, and feel free to apply again.', 'http://localhost/online-job-portal-php-mysql/users/applied_jobs.php', 0, NULL, '2025-12-24 02:16:15'),
(5, 24, 25, 'app_status_update', 8, 35, 'Application update: Not Selected', 'Your application for “Marketing Coordinator” at Test Corporation was not successful this time. We appreciate your interest and the time you invested—please keep an eye on new roles that match your skills, and feel free to apply again.', 'http://localhost/online-job-portal-php-mysql/users/applied_jobs.php', 0, NULL, '2025-12-24 02:16:24'),
(6, 34, 35, 'new_application', 27, 38, 'New application received', 'Your job \"Network Operations Center (NOC) Engineer\" has a new applicant.', 'http://localhost/online-job-portal-php-mysql/users/show-applicants.php?id=34&job_id=27', 0, NULL, '2025-12-25 05:13:19'),
(7, 26, 37, 'new_application', 30, 39, 'New application received', 'Your job \"Mobile Application Performance Engineer\" has a new applicant.', 'http://localhost/online-job-portal-php-mysql/users/show-applicants.php?id=26&job_id=30', 0, NULL, '2025-12-26 05:00:17'),
(8, 34, 38, 'new_application', 27, 40, 'New application received', 'Your job \"Network Operations Center (NOC) Engineer\" has a new applicant.', 'http://localhost/online-job-portal-php-mysql/users/show-applicants.php?id=34&job_id=27', 0, NULL, '2025-12-26 05:13:02'),
(9, 33, 38, 'new_application', 26, 41, 'New application received', 'Your job \"Network Operations Technician\" has a new applicant.', 'http://localhost/online-job-portal-php-mysql/users/show-applicants.php?id=33&job_id=26', 1, '2025-12-26 05:16:35', '2025-12-26 05:16:06'),
(10, 38, 33, 'app_status_update', 26, 41, 'Application update: In Review', 'Your application for “Network Operations Technician” at DemoWorks Group is currently under review. The employer is assessing applications and will contact shortlisted candidates.', 'http://localhost/online-job-portal-php-mysql/users/applied_jobs.php', 0, NULL, '2025-12-26 05:16:39'),
(11, 26, 38, 'new_application', 30, 42, 'New application received', 'Your job \"Mobile Application Performance Engineer\" has a new applicant.', 'http://localhost/online-job-portal-php-mysql/users/show-applicants.php?id=26&job_id=30', 0, NULL, '2025-12-29 22:42:22'),
(12, 38, 26, 'app_status_update', 30, 42, 'Application update: Not Selected', 'Your application for “Mobile Application Performance Engineer” at Astro Ltd was not successful this time. We appreciate your interest and the time you invested—please keep an eye on new roles that match your skills, and feel free to apply again.', 'http://localhost/online-job-portal-php-mysql/users/applied_jobs.php', 1, '2025-12-29 22:55:53', '2025-12-29 22:46:57');

-- --------------------------------------------------------

--
-- Table structure for table `question_bank`
--

CREATE TABLE `question_bank` (
  `id` int(11) NOT NULL,
  `question_text` varchar(255) NOT NULL,
  `qtype` varchar(20) NOT NULL,
  `options` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question_bank`
--

INSERT INTO `question_bank` (`id`, `question_text`, `qtype`, `options`, `is_active`, `created_at`) VALUES
(1, 'Are you willing to relocate?', 'yesno', NULL, 1, '2025-09-20 01:30:43'),
(2, 'What is your expected salary?', 'text', NULL, 1, '2025-09-20 01:30:43'),
(3, 'When can you start?', 'text', NULL, 1, '2025-09-20 01:30:43'),
(4, 'Are you legally authorized to work?', 'yesno', NULL, 1, '2025-09-20 01:30:43'),
(5, 'Years of professional experience?', 'dropdown', '[\"0-1\",\"1-3\",\"3-5\",\"5+\"]', 1, '2025-09-20 01:30:43'),
(6, 'Do you have a portfolio or GitHub URL?', 'text', NULL, 1, '2025-09-20 01:30:43');

-- --------------------------------------------------------

--
-- Table structure for table `resumes`
--

CREATE TABLE `resumes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resumes`
--

INSERT INTO `resumes` (`id`, `user_id`, `label`, `filename`, `original_name`, `is_primary`, `created_at`) VALUES
(1, 27, NULL, 'cv_27_1758342060_e027.pdf', 'cv_27_1758342060_e027.pdf', 1, '2025-09-20 04:21:00'),
(2, 30, NULL, 'cv_30_1758342662_46e9.pdf', 'cv_30_1758342662_46e9.pdf', 1, '2025-09-20 04:31:02'),
(3, 35, NULL, 'cv_35_1766276459_f32f.pdf', 'DummyResumeTest.pdf', 1, '2025-12-21 00:20:59'),
(4, 37, NULL, 'cv_37_1766713512_cafb.pdf', 'DummyResumeTest.pdf', 1, '2025-12-26 01:45:12'),
(5, 38, NULL, 'cv_38_1766725541_639c.pdf', 'DummyResumeTest.pdf', 1, '2025-12-26 05:05:41');

-- --------------------------------------------------------

--
-- Table structure for table `saved_jobs`
--

CREATE TABLE `saved_jobs` (
  `id` int(3) NOT NULL,
  `job_id` int(3) NOT NULL,
  `worker_id` int(3) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_jobs`
--

INSERT INTO `saved_jobs` (`id`, `job_id`, `worker_id`, `created_at`) VALUES
(11, 2, 10, '2023-10-13 09:04:44'),
(12, 3, 13, '2023-10-19 22:21:04'),
(13, 4, 23, '2023-10-26 02:47:58'),
(14, 1, 23, '2023-10-26 02:48:13'),
(21, 28, 37, '2025-12-26 04:56:22');

-- --------------------------------------------------------

--
-- Table structure for table `searches`
--

CREATE TABLE `searches` (
  `id` int(3) NOT NULL,
  `keyword` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `searches`
--

INSERT INTO `searches` (`id`, `keyword`, `created_at`) VALUES
(100, 'web developer', '2025-09-15 01:11:11'),
(101, 'designer', '2025-09-15 01:11:24'),
(102, 'customer service', '2025-09-15 01:11:30'),
(103, 'customer service', '2025-09-15 01:11:48'),
(104, 'customer service', '2025-09-16 01:05:09'),
(105, 'customer service', '2025-09-16 01:07:40'),
(106, 'customer service', '2025-09-16 01:08:19'),
(107, 'customer service', '2025-09-18 04:43:05'),
(108, 'customer service', '2025-09-18 04:43:26'),
(109, 'customer service', '2025-09-18 04:43:59'),
(110, 'customer service', '2025-09-18 04:50:02'),
(111, 'customer service', '2025-09-18 04:52:02'),
(112, 'customer service', '2025-09-18 04:52:13'),
(113, 'customer service', '2025-09-18 04:58:07'),
(114, 'customer service', '2025-09-18 04:58:46'),
(115, 'designer', '2025-09-18 04:59:05'),
(116, 'IT', '2025-09-18 04:59:29'),
(117, 'customer service', '2025-09-18 06:47:11'),
(118, 'assistant', '2025-09-18 06:52:31'),
(119, 'assistant', '2025-09-18 07:17:53'),
(120, 'assistant', '2025-09-18 07:18:15'),
(121, 'IT', '2025-09-18 08:22:16'),
(122, 'IT', '2025-09-18 08:36:45'),
(123, 'designer', '2025-09-18 09:22:41'),
(124, 'designer', '2025-09-18 09:27:16'),
(125, 'designer', '2025-09-18 09:38:40'),
(126, 'web developer', '2025-09-18 09:39:40'),
(127, 'developer', '2025-09-18 09:39:47'),
(128, 'developer', '2025-09-18 09:40:27'),
(129, 'customer service', '2025-09-18 12:00:22'),
(130, 'customer service', '2025-09-18 12:00:50'),
(131, 'customer service', '2025-09-18 12:01:41'),
(132, 'customer service', '2025-09-18 12:01:57'),
(133, 'customer service', '2025-09-18 12:02:22'),
(134, 'customer service', '2025-09-18 12:02:36'),
(135, 'developer', '2025-09-18 12:03:25'),
(136, 'customer service', '2025-09-18 12:05:29'),
(137, 'customer service', '2025-09-18 12:08:46'),
(138, 'customer support', '2025-09-18 12:21:24'),
(139, 'customer', '2025-09-18 12:21:48'),
(140, 'IT', '2025-09-18 12:22:57'),
(141, 'IT', '2025-09-18 12:47:42'),
(142, 'data', '2025-09-19 06:44:05'),
(143, 'designer', '2025-12-17 08:03:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(3) NOT NULL,
  `fullname` varchar(155) NOT NULL,
  `username` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `region_id` int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `mypassword` varchar(200) NOT NULL,
  `img` varchar(200) NOT NULL,
  `type` varchar(200) NOT NULL,
  `cv` varchar(200) DEFAULT NULL,
  `skills` varchar(255) NOT NULL,
  `education` varchar(200) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `bio` varchar(500) DEFAULT NULL,
  `facebook` varchar(200) DEFAULT NULL,
  `twitter` varchar(200) DEFAULT NULL,
  `linkedin` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `username`, `email`, `contact`, `region_id`, `address`, `mypassword`, `img`, `type`, `cv`, `skills`, `education`, `title`, `bio`, `facebook`, `twitter`, `linkedin`, `created_at`) VALUES
(7, 'ABC Employer', 'abcemployer', 'abc@employer.com', '7777777778', NULL, NULL, '$2y$10$/.zJQj4pSOYdcM74RrrNZe/VSrw1FDsZ83ofJ2nhBA/ylC1vwHe4u', 'emplyyr.png', 'Employer', 'NULL', '', '', 'NULL', 'Objectively network long-term high-impact vortals without end-to-end schemas. Rapidiously underwhelm scalable platforms vis-a-vis integrated models. Professionally myocardinate 2.0 outsourcing for resource-leveling manufactured products. ', '', '', '', '2022-11-03 17:04:14'),
(8, 'Job Seeker', 'job.seeker', 'job.seeker@mail.com', '1245789655', NULL, NULL, '$2y$10$sQJJjg2HOnqg/uk9l53Fy.YjqcnXuZrWW/BrTNTycEh6aqz1q2KhG', 'user_placeholderimg.jpg', 'Job Seeker', 'Mohamed_Hassan_Resume.pdf', '', '', '', 'Objectively unleash multidisciplinary portals whereas wireless catalysts for change. Completely productivate user friendly ROI whereas 2.0 ', '', '', '', '2022-11-03 17:10:28'),
(9, 'NoRealCo Logistics', 'norealco', 'norealco@mail.com', '2222022222', NULL, NULL, '$2y$10$XE9yOqsdQjzrBoOHTWJkXOLhIVubHnF3AiYg7WZfbV9UTf9fCprdu', 'lgsticss.jpg', 'Employer', 'NULL', '', '', 'NULL', 'NoRealCo Logistics is a leading provider of freight and last-mile delivery services across different states. With a modern fleet and real-time tracking solutions, we ensure efficient and reliable transport for retail, warehousing, and eCommerce businesses. We are committed to timely delivery, safety, and customer satisfaction.', 'codeastro.com', 'codeastro.com', 'codeastro.com', '2023-10-13 06:19:41'),
(10, 'asd', 'asd', 'asd@mail.com', '1111111111', NULL, NULL, '$2y$10$AsLhL6T7T7dRlN0nIroSReLiC5Ji5m1JbaL0noQ.rK0xcDXEEBsL2', 'images/ph.png', 'Job Seeker', NULL, '', '', NULL, NULL, NULL, NULL, NULL, '2023-10-13 08:31:24'),
(11, 'Placeholder Organization', 'phorg', 'phorg@mail.com', '1111211111', NULL, NULL, '$2y$10$1E/KjJWjSAlg.xalrFQnKunrK4Be/xHjN6s0d2JJGBmp5g5Lk/a3y', '1212121sampl.jpg', 'Employer', 'NULL', '', '', 'NULL', 'Placeholder Org. is a full-service advertising and marketing agency dedicated to helping brands grow through creative storytelling, data-driven campaigns, and measurable results. We combine traditional media, digital platforms, and brand strategy to deliver bespoke marketing solutions for businesses of all sizes.', 'codeastro.com', 'codeastro.com', 'codeastro.com', '2023-10-13 22:57:09'),
(12, 'James', 'james', 'james@mail.com', '2147483647', NULL, NULL, '$2y$10$kW6jy6eeJTkpUPYkEsF6WO.4eZc2gfO4HHMcujNjPV9cAGwtM/0Wa', 'images/ph.png', 'Job Seeker', NULL, '', '', NULL, NULL, NULL, NULL, NULL, '2023-10-13 23:07:18'),
(13, 'Henry', 'henry', 'henry@mail.com', '1010101010', 2, '8 Test Street', '$2y$10$35EsoH56qmnD2v2WV9QnU.Ug.wa48pfLD9DTpL14uXje6OyYqv9jy', 'gr2.png', 'Job Seeker', 'dummy.pdf', 'html, css, javascript, php, laravel, react', '', '', '', '', '', '', '2023-10-13 23:11:31'),
(14, 'Steve', 'steve', 'steve@mail.com', '1100000000', NULL, NULL, '$2y$10$BlLOJE5GZkVqVhV2rauDROftMqk4swvU5kpglvkLz4Nr9SZLawf9i', 'images/ph.png', 'Job Seeker', NULL, '', '', NULL, NULL, NULL, NULL, NULL, '2023-10-13 23:35:42'),
(15, 'Mark Johnson', 'mark', 'mark@mail.com', '1777775454', NULL, NULL, '$2y$10$XxARE./Yv8.s3oCdqDDWNeCbgMoVDuoUk3zeFqe5wGE7/tzejmABK', 'gr3.png', 'Job Seeker', 'dummy.pdf', '', '', '', 'demo demo demo  demo demo000', 'facebook.com', 'twitter.com', 'twitter.com', '2023-10-13 23:37:41'),
(16, 'Test Employer', 'employer', 'employer@mail.com', '1458555522', NULL, NULL, '$2y$10$BZ0NtoWTK0QRvjU5fEMWGuQgYyFm5ZFNVhXLXPP40QBXapy8Bfblq', 'plcehldr.jpg', 'Employer', 'NULL', '', '', 'NULL', '', '', '', '', '2023-10-14 00:48:51'),
(17, 'XYZ Employer', 'xyzemployer', 'xyz@employer.com', '1212111111', NULL, NULL, '$2y$10$/.zJQj4pSOYdcM74RrrNZe/VSrw1FDsZ83ofJ2nhBA/ylC1vwHe4u', 'dmoempl.png', 'Employer', 'NULL', '', '', 'NULL', 'XYZ Employer is a leading healthcare provider specializing in patient care, medical technology solutions, and healthcare staffing services. The company partners with hospitals, clinics, and health organizations to deliver high-quality services and improve healthcare delivery across the region. Our mission is to provide accessible and affordable healthcare through cutting-edge technology and compassionate care.', 'www.facebook.com', 'www.twitter.com', 'www.twitter.com', '2023-10-15 05:35:37'),
(19, 'Richards', 'richards', 'richards@mail.com', '111111111', NULL, NULL, '$2y$10$Tw.XGF1ktzxI1qIqPn4cLuoyPAJrAgjn.q7tBBArXf0yQ.f2n0YHW', 'user_placeholderimg.jpg', 'Job Seeker', 'dummy.pdf', '', '', '', 'wdsefdrtyu', '', '', '', '2023-10-24 23:40:06'),
(20, 'Perry Anderson', 'perry', 'perry@mail.com', '1234000000', NULL, NULL, '$2y$10$HCos9k2X7.BUCp8q3rhKn.8w7fjn8MtKV7Psq/5xkKlxC3pT1l6qq', 'user_placeholderimg.jpg', 'Job Seeker', 'dummy.pdf', '', '', '', '', '', '', '', '2023-10-26 00:11:47'),
(21, 'Jimmy', 'jimmy', 'jimmy@mail.com', '1100011111', NULL, NULL, '$2y$10$IwbFK3Fc9fzKLmY3b1u6wOTOD4ttOegspTmRNtQi1jtz5UIs0iO9W', 'user_placeholderimg.jpg', 'Job Seeker', NULL, '', '', NULL, NULL, NULL, NULL, NULL, '2023-10-26 00:17:32'),
(22, 'Demo Employer', 'demo_employer', 'demo@employer.com', '1000001470', NULL, NULL, '$2y$10$VkWpcGnUvIvhmbKRD0sHLOt8TCGToR4sNRs67X1BOSRUv75jMGoxi', 'smllltyupo.jpg', 'Employer', 'NULL', '', '', 'NULL', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus vestibulum bibendum magna, nec varius dui eleifend in. Proin vel semper eros. Sed placerat, elit et vulputate luctus, ligula urna blandit justo, ac iaculis erat ex eu risus. Sed hendrerit et turpis ac ullamcorper. Quisque ac tincidunt odio. In hac habitasse platea dictumst. Fusce sed bibendum lectus. Integer tincidunt massa sit amet erat rhoncus, non hendrerit lectus finibus.', 'codeastro.com', 'codeastro.com', 'codeastro.com', '2023-10-26 02:32:11'),
(23, 'John Doe', 'john', 'john@mail.com', '1111111110', NULL, NULL, '$2y$10$ipw7VfT6BvCpzsSMjS0GdOps0HovufGS/ALZKWn/SMUlmViXBz2DW', 'user_placeholderimg.jpg', 'Job Seeker', 'dummy.pdf', 'demo skills, demo, demo', 'demo, demo, demo, demo', '', '', '', '', '', '2023-10-26 02:47:24'),
(24, 'Harry Denn', 'harryden', 'harryden@mail.com', '2147483647', NULL, NULL, '$2y$10$Uw10UqXRXoz1YR/OPNA0B.ntoqFh6y..MbsSM51grb0Tc9DJ/6tgu', 'user_placeholderimg.jpg', 'Job Seeker', 'cv_1757475245_DummyCV.pdf', '', '', NULL, NULL, NULL, NULL, NULL, '2024-06-26 02:55:01'),
(25, 'Test Corporation', 'testcorp', 'testcorp@mail.com', '7774500001', NULL, NULL, '$2y$10$3M7NL49pQ.eghAu7EJe7pOpatCQAc1aE/2JiY/zKuia5kKsc.oKYG', 'tsttcrpsmplelogo.jpg', 'Employer', 'NULL', '', '', 'NULL', 'TestCorp is a fast-growing customer experience outsourcing provider, delivering exceptional support services for global clients across e-commerce, SaaS, fintech, and more. Our mission is to help companies build stronger customer relationships through human-centered support. With a focus on innovation, empathy, and operational excellence, we empower our agents to make every interaction count.', '', '', '', '2025-09-08 03:27:13'),
(26, 'Astro Ltd', 'astroltd', 'astroltd@mail.com', '8880555555', NULL, NULL, '$2y$10$9W8GxshksB.g0tdeGhroj.ZCZqWtrRBRAVTcQAATxMEiGOpaJOM5K', 'demolgastr.jpg', 'Employer', 'NULL', '', '', 'NULL', 'AstroLtd is a managed IT services provider supporting clients across education, healthcare, and finance. We are committed to nurturing new tech talent and provide structured development opportunities through our trainee programs. If you\'re curious, hands-on, and ready to kickstart your IT career, we\'d love to hear from you.', 'codeastro.com', 'codeastro.com', 'codeastro.com', '2025-09-08 06:49:44'),
(27, 'Sandbox User', 'sandboxuser', 'sandboxuser@mail.com', '1111111110', 6, '45 Demo address', '$2y$10$azBEv7oL16nRpmojnuVYBuhmvNPW.RlSsNgRgKuHpBK4fywsnRcGe', '4graccf3b.png', 'Job Seeker', 'DummyCV.pdf', 'Frontend Development: HTML5, CSS3, JavaScript, React.js, Bootstrap, Customer Support Tools: Zendesk, Intercom, LiveChat, Freshdesk, Communication: Active Listening, Conflict Resolution, Client Training, Technical Skills: Debugging, REST APIs, Git, Basic S', 'Masters in Information Technology', 'Technical Support Specialist / Frontend Developer', 'Versatile and customer-focused Technical Support Specialist with a strong foundation in web development. With 4+ years of experience in providing high-quality client support and 2+ years in frontend development, I bring a unique blend of technical expertise and empathetic communication.', 'codeastro.com', 'codeastro.com', 'codeastro.com', '2025-09-08 06:56:16'),
(28, 'Dummy Organization', 'dummyorg', 'dummyorg@mail.com', '1234444444', NULL, NULL, '$2y$10$BtdVyhhpYD7pK2ek9r/.lOPLuVoP7nDiEw6EcaoYrXkEj7oNYp/R.', 'smpleorg.jpg', 'Employer', 'NULL', '', '', 'NULL', 'At Demo Org., we are dedicated to delivering innovative solutions that empower businesses to reach their full potential. Founded in 2011, our mission is to provide exceptional services in technology, consulting, and customer support. We pride ourselves on fostering a collaborative and inclusive environment where creativity thrives and client success is our top priority. We believe in building lasting partnerships with our clients by understanding their unique needs and delivering tailored strate', 'codeastro.com', 'codeastro.com', 'codeastro.com', '2025-09-13 02:20:26'),
(29, 'Qwer Org', 'qorg', 'qorg@mail.com', '1111111112', NULL, NULL, '$2y$10$4/RRlk3Kz.33YfhwvfcGpO0juv4nsh3Bai056xlNGtJ3dYd.K6oJy', '', 'Employer', NULL, '', '', NULL, NULL, NULL, NULL, NULL, '2025-09-17 02:15:30'),
(30, 'Dummy User', 'dummyuser', 'dummyuser@mail.com', '2222322223', 2, '88 Test', '$2y$10$oJiGZHkdf5jX1a3iDW.ynuKAaCuzUT18E77ogSarJpdoHyzlR2awy', 'user_placeholderimg.jpg', 'Job Seeker', NULL, 'PHP, JavaScript, Python, Java, SQL, HTML5, CSS3, ReactJS, Node.js, Bootstrap, Laravel, Django, Express.js, Angular, MySQL, MongoDB, PostgreSQL, Git, GitHub, GitLab, Docker, Jenkins, AWS, Firebase, Agile Methodology, Unit Testing, REST APIs', 'Bachelor of Science in Computer Science, Master of Science in Software Engineering', 'Software Developer', 'Highly motivated developer with over 3 years of experience in full-stack development. Passionate about solving complex problems and creating efficient, scalable web applications. I thrive in collaborative environments and continuously seek opportunities to grow my skill set, particularly in the areas of cloud computing and machine learning. I am eager to contribute my knowledge and expertise to help innovative companies build cutting-edge solutions.', '', '', '', '2025-09-20 04:30:19'),
(32, 'Kxorin Technology', 'kxorin', 'kxorintech@mail.com', '7777777790', NULL, NULL, '$2y$10$KtuyVxbeg/XNgjlo35vZYuJhgRvE2aIf5XAYeNUOJphTtQ17XhP3m', '5192574_2281636000.png', 'Employer', NULL, '', '', 'NULL', 'Kxorin Technology is a cutting-edge artificial intelligence company focused on developing innovative AI solutions that empower businesses and individuals to make smarter, faster, and more informed decisions. Founded by a team of AI enthusiasts and tech visionaries, Kxorin specializes in machine learning, natural language processing, and predictive analytics.', '', '', '', '2025-12-18 01:00:50'),
(33, 'DemoWorks Group', 'demoworks', 'demoworks@mail.com', '7777477777', NULL, NULL, '$2y$10$0NhDIDc4e0gwOaCQ6vtH1ekcQr1KV9.7lLEWi1EQwhF9eV7tLqBZW', '444581355555.png', 'Employer', NULL, '', '', 'NULL', 'DemoWorks Group is an information technology services company focused on software development, application testing, and digital solutions. We work with modern tools and frameworks to build reliable, scalable, and user-friendly systems. Our team values collaboration, continuous learning, and practical problem-solving. DemoWorks Group provides a structured work environment where professionals can develop their skills while contributing to meaningful technology projects.', '', '', '', '2025-12-19 08:37:01'),
(34, 'Wavestra Telecom Services', 'wavestra', 'wavestra@mail.com', '1111511110', NULL, NULL, '$2y$10$q1kCFar6.Z7HURTvEh6p0OscPJwRVHWOJsqZb9Irm7ZJNyZa/39vy', '7777237747477.png', 'Employer', NULL, '', '', 'NULL', 'Wavestra Telecom Services is an enterprise-scale telecommunications organization delivering large-scale network infrastructure and connectivity services. We operate across multiple regions, supporting communication networks through structured operations, standardized processes, and enterprise-grade technologies.\r\n\r\nOur services span fiber and wireless network deployment, network operations monitoring, infrastructure maintenance, and technical support services. WaveSignal focuses on reliability, ', '', '', '', '2025-12-19 22:21:21'),
(35, 'Virtual Account', 'virtualacc', 'virtualacc@mail.com', '1110101011', 3, '8 Demo Address', '$2y$10$ZKFzr5kxOWk8Hp1Xqy9tNuWp12agxoKDD7F1Zqi2jZ5vLXLVuTyZa', 'gravvv.png', 'Job Seeker', NULL, 'Network Architecture, 5G Core Networks, Fiber Optic Systems, RF Planning and Optimization, VoIP & Unified Communications, Network Security, Microwave Transmission, Infrastructure Deployment, Advanced Troubleshooting, Team Leadership, Project Management', 'Master of Science in Telecommunications Engineering, Master of Business Administration (Technology Management), Cisco Certified Network Professional (CCNP), Certified Telecommunications Network Specia', 'Telecom Systems Analyst', 'Passionate about telecommunications and network systems, with experience in maintaining and optimizing communication infrastructures. Skilled in troubleshooting, network analysis, and deploying efficient solutions to enhance connectivity. Eager to contribute to innovative telecom projects and grow professionally in the field.', 'codeastro.com', 'codeastro.com', 'codeastro.com', '2025-12-20 01:49:32'),
(36, 'Nestquay Network Group', 'nestquay', 'nng@mail.com', '4444424444', NULL, NULL, '$2y$10$oChZsiUdRTtWIw4eEVQ65Oh70bkYvXei3i/FCo8Le6coxw1q66HDO', 'nng77767.png', 'Employer', NULL, '', '', 'NULL', 'Nest Network Group is a technology-driven telecommunications services provider focused on delivering reliable network operations, monitoring, and infrastructure support. The company specializes in network operations center (NOC) services, connectivity management, and proactive incident response for enterprise and service-based environments. With an emphasis on operational efficiency and service continuity, Nest Network Group supports organizations by ensuring stable, secure, and well-monitored n', '', '', '', '2025-12-25 05:35:13'),
(37, 'Trial Account', 'trialaccount', 'trialacc@mail.com', '777707777', 2, '99/888 Test Address', '$2y$10$09ofIaYh796nmCbHN4ZnheWplQEKQl6YfkyQzW8ATYfZXHt78NHkW', 'user_placeholderimg.jpg', 'Job Seeker', NULL, 'Communication, Teamwork, Problem Solving, Time Management, Adaptability, Basic Computer Skills, Data Entry, Customer Support, Attention to Detail, Organization, Multitasking, Microsoft Office, Email Handling, Internet Research, Documentation', 'Bachelor’s Degree in General Studies', 'Skilled Professional', 'Adaptable and motivated job seeker open to a wide range of roles. Comfortable learning new skills, working in team environments, and taking on varied responsibilities. Actively seeking suitable opportunities across different industries.', '', '', '', '2025-12-26 01:20:18'),
(38, 'Alpha User', 'alphauser', 'alphauser@mail.com', '444414444', 2, '8/805 Dummy Test Address', '$2y$10$9W8GxshksB.g0tdeGhroj.ZCZqWtrRBRAVTcQAATxMEiGOpaJOM5K', 'grav2eafef66dddf7.png', 'Job Seeker', NULL, 'Mobile Application Development, Network Optimization, Software Engineering, Android Development, iOS Development, Performance Tuning, Telecommunication Systems, Cloud Services, API Integration, Database Management, Agile Methodologies, Troubleshooting', 'Bachelor of Science in Computer Engineering, Master of Science in Telecommunications Engineering', 'IT and Telecom Specialist', 'Experienced technology professional with a strong background in software development, mobile applications, and telecommunication systems. Skilled in problem-solving, performance optimization, and working across IT and telecom projects to deliver high-quality solutions', 'codeastro.com', 'codeastro.com', 'codeastro.com', '2025-12-26 05:02:50');

-- --------------------------------------------------------

--
-- Table structure for table `user_availability`
--

CREATE TABLE `user_availability` (
  `user_id` int(11) NOT NULL,
  `monday` varchar(20) NOT NULL DEFAULT 'none',
  `tuesday` varchar(20) NOT NULL DEFAULT 'none',
  `wednesday` varchar(20) NOT NULL DEFAULT 'none',
  `thursday` varchar(20) NOT NULL DEFAULT 'none',
  `friday` varchar(20) NOT NULL DEFAULT 'none',
  `saturday` varchar(20) NOT NULL DEFAULT 'none',
  `sunday` varchar(20) NOT NULL DEFAULT 'none',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `availability`
--
ALTER TABLE `availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_details`
--
ALTER TABLE `company_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_details_brn` (`business_reg_no`),
  ADD KEY `fk_company_user` (`user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_app_answers`
--
ALTER TABLE `job_app_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `job_questions`
--
ALTER TABLE `job_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `fk_jq_bank` (`bank_id`);

--
-- Indexes for table `job_regions`
--
ALTER TABLE `job_regions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_views`
--
ALTER TABLE `job_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_jv_job_user_day` (`job_id`,`viewer_user_id`,`viewed_date`),
  ADD KEY `idx_jv_job_date` (`job_id`,`viewed_date`),
  ADD KEY `idx_jv_company` (`company_id`,`viewed_date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_actor` (`actor_user_id`),
  ADD KEY `idx_notif_rec_seen` (`recipient_user_id`,`seen`,`created_at`),
  ADD KEY `idx_notif_rec_created` (`recipient_user_id`,`created_at`);

--
-- Indexes for table `question_bank`
--
ALTER TABLE `question_bank`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resumes`
--
ALTER TABLE `resumes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_resumes_user` (`user_id`),
  ADD KEY `idx_resumes_primary` (`user_id`,`is_primary`);

--
-- Indexes for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `searches`
--
ALTER TABLE `searches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_users_region` (`region_id`);

--
-- Indexes for table `user_availability`
--
ALTER TABLE `user_availability`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `availability`
--
ALTER TABLE `availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `company_details`
--
ALTER TABLE `company_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `job_app_answers`
--
ALTER TABLE `job_app_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `job_questions`
--
ALTER TABLE `job_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `job_regions`
--
ALTER TABLE `job_regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `job_views`
--
ALTER TABLE `job_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `question_bank`
--
ALTER TABLE `question_bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `resumes`
--
ALTER TABLE `resumes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `searches`
--
ALTER TABLE `searches`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `availability`
--
ALTER TABLE `availability`
  ADD CONSTRAINT `availability_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `company_details`
--
ALTER TABLE `company_details`
  ADD CONSTRAINT `fk_company_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `job_questions`
--
ALTER TABLE `job_questions`
  ADD CONSTRAINT `fk_jq_bank` FOREIGN KEY (`bank_id`) REFERENCES `question_bank` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_jq_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notifications_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resumes`
--
ALTER TABLE `resumes`
  ADD CONSTRAINT `fk_resumes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_region` FOREIGN KEY (`region_id`) REFERENCES `job_regions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_availability`
--
ALTER TABLE `user_availability`
  ADD CONSTRAINT `fk_availability_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
