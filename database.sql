-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 19, 2026 at 11:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ai_blogsite`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_permissions`
--

CREATE TABLE `admin_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_permissions`
--

INSERT INTO `admin_permissions` (`id`, `user_id`, `module`) VALUES
(1, 1, 'dashboard'),
(2, 1, 'settings'),
(3, 1, 'users'),
(4, 1, 'ai_writer'),
(5, 1, 'posts'),
(6, 4, 'dashboard'),
(7, 4, 'ai_writer'),
(8, 4, 'posts'),
(9, 4, 'categories');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`) VALUES
(1, 'Technology', 'technology'),
(2, 'AI & Ethics', 'ai-ethics'),
(3, 'Future Tech', 'future-tech');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `name`, `content`, `status`, `created_at`) VALUES
(1, 1, 'Rakib Hasan', 'this is the demo comment.', 'approved', '2026-04-19 05:27:19');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(60) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `actor_name` varchar(120) DEFAULT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `scope` enum('global','user') DEFAULT 'global',
  `recipient_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `actor_id`, `actor_name`, `message`, `link`, `scope`, `recipient_id`, `is_read`, `created_at`) VALUES
(1, 'post_created', 4, 'MST Onamika Jannat Ara', 'New post created: \"From Curiosity to Creation: Why I Chose Computer Science and Engineering\"', 'http://localhost/AI-Based-Blogsite/admin/edit_post.php?id=4', 'global', NULL, 1, '2026-04-19 09:09:36'),
(2, 'post_edited', 4, 'MST Onamika Jannat Ara', 'Post updated: \"From Curiosity to Creation: Why I Chose Computer Science and Engineering\"', 'http://localhost/AI-Based-Blogsite/admin/edit_post.php?id=4', 'global', NULL, 1, '2026-04-19 09:10:45'),
(3, 'post_edited', 4, 'MST Onamika Jannat Ara', 'Post updated: \"From Zero to Full-Stack: The Ultimate Guide to Mastering Web Development in 2026\"', 'http://localhost/AI-Based-Blogsite/admin/edit_post.php?id=3', 'global', NULL, 1, '2026-04-19 09:12:11'),
(4, 'post_liked', 4, 'MST Onamika Jannat Ara', 'Someone liked your post: \"From Curiosity to Creation: Why I Chose Computer Science and Engineering\"', 'http://localhost/AI-Based-Blogsite/post.php?slug=4', 'user', 4, 0, '2026-04-19 09:12:38'),
(5, 'post_liked', 4, 'MST Onamika Jannat Ara', 'Activity: \"From Curiosity to Creation: Why I Chose Computer Science and Engineering\" received a like.', NULL, 'global', NULL, 1, '2026-04-19 09:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `content`, `excerpt`, `seo_keywords`, `featured_image`, `category_id`, `author_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Best way to learn someting in modern world.', 'best-way-to-learn-someting-in-modern-world-', '<h2>The Paradigm Shift: Learning in the Twenty-First Century</h2>\r\n<p>In the contemporary landscape, the acquisition of knowledge has transitioned from a scarcity-based model to one of overwhelming abundance. Historically, learning was confined to the physical boundaries of libraries, universities, and mentorships. Today, the democratization of information through digital infrastructure has rendered the world’s collective intelligence accessible at the click of a button. However, this abundance presents a new challenge: the paradox of choice and the risk of cognitive overload. To master a new skill or subject in the modern era, one must move beyond passive consumption and adopt a structured, multi-faceted approach that leverages cognitive science and technological efficiency.</p>\r\n\r\n<h3>The Cognitive Foundation: Active Recall and Spaced Repetition</h3>\r\n<p>The most effective modern learning strategies are rooted in understanding how the human brain encodes and retains information. Scientific research consistently demonstrates that passive reading or highlighting is the least effective way to learn. Instead, the \"Active Recall\" method forces the brain to retrieve information from memory, strengthening neural pathways. This is most effectively paired with \"Spaced Repetition,\" a technique that involves reviewing information at increasing intervals to combat the forgetting curve. By utilizing digital tools such as Anki or various flashcard applications, learners can automate this process, ensuring that they focus their energy on the concepts they find most difficult while maintaining a solid foundation of previously acquired knowledge.</p>\r\n\r\n<h3>The Feynman Technique: Learning through Synthesis</h3>\r\n<p>A hallmark of deep understanding is the ability to simplify complex concepts. Named after the Nobel Prize-winning physicist Richard Feynman, this technique involves four distinct steps: choosing a concept, teaching it to a hypothetical child, identifying gaps in one\'s own explanation, and reviewing the source material to refine the understanding. In the modern world, this can be amplified through digital creation. Writing a blog post, recording a short explanatory video, or participating in niche forums allows a learner to test their knowledge against a real or imagined audience. This process of synthesis ensures that the information is not just memorized but integrated into the learner\'s existing mental models.</p>\r\n\r\n<h3>Leveraging Artificial Intelligence and Digital Curation</h3>\r\n<p>The advent of generative Artificial Intelligence (AI) has revolutionized self-directed education. AI can now serve as a personalized tutor, capable of explaining concepts in various styles, generating practice problems, and providing immediate feedback. However, the modern learner must also become an expert curator. With the proliferation of low-quality content, the ability to filter information is a critical skill. To learn effectively, one should follow a structured curriculum, whether through Massive Open Online Courses (MOOCs) like Coursera and edX or by curating a list of high-signal resources such as peer-reviewed journals, industry-leading newsletters, and expert-led podcasts. The goal is to minimize noise and maximize the density of high-quality information.</p>\r\n\r\n<h3>The Importance of Project-Based Learning</h3>\r\n<p>Theoretical knowledge remains fragile until it is applied in a practical context. Project-based learning (PBL) is perhaps the most robust method for cementing new skills. By working on a tangible project—whether it is coding an application, writing a business proposal, or constructing a physical object—the learner encounters real-world problems that textbooks often overlook. This approach fosters critical thinking and problem-solving skills. In a professional context, a portfolio of completed projects often carries more weight than formal certifications, as it provides empirical evidence of a learner\'s ability to navigate the complexities of their chosen field.</p>\r\n\r\n<h3>Metacognition and the Growth Mindset</h3>\r\n<p>Successful learning in the modern world requires a high degree of metacognition, or \"thinking about thinking.\" This involves regularly assessing one’s own learning process and making necessary adjustments. Are you spending too much time on easy tasks? Are you avoiding the most difficult aspects of the subject? Coupled with this is the \"Growth Mindset,\" a concept popularized by psychologist Carol Dweck. This mindset posits that intelligence and talent are not fixed traits but can be developed through persistence and effort. Embracing the discomfort of being a beginner is essential in an era where rapid technological shifts require constant upskilling and reskilling.</p>\r\n\r\n<h3>A Summary of Best Practices for Modern Learners</h3>\r\n<ul>\r\n    <li>Prioritize active retrieval over passive consumption to build long-term memory.</li>\r\n    <li>Utilize spaced repetition software to optimize the timing of review sessions.</li>\r\n    <li>Apply the Feynman Technique to identify and bridge gaps in conceptual understanding.</li>\r\n    <li>Use AI tools as interactive tutors to clarify complex topics and generate practice materials.</li>\r\n    <li>Focus on project-based learning to transition from theoretical knowledge to practical mastery.</li>\r\n    <li>Curate high-quality information sources to avoid the pitfalls of digital noise and misinformation.</li>\r\n    <li>Maintain a growth mindset and engage in regular metacognitive reflection to refine the learning process.</li>\r\n</ul>\r\n\r\n<h2>Conclusion: The Lifelong Learning Imperative</h2>\r\n<p>The modern world rewards those who can learn, unlearn, and relearn with agility. The traditional model of \"front-loading\" education in the first two decades of life is no longer sufficient for a career spanning forty years in a volatile economy. By combining the timeless principles of cognitive science with the powerful tools of the digital age, individuals can achieve a level of mastery that was previously unimaginable. Learning is no longer a destination; it is a continuous, iterative process that requires discipline, curiosity, and a strategic approach to information management. Those who master the art of learning will find themselves uniquely positioned to thrive in an increasingly complex and automated future.</p>', 'Drowning in data but starving for knowledge? Master the art of rapid learning in the digital age. From AI-driven tools to focused micro-learning, discover the ultimate blueprint to upgrade your brain and stay ahead. Your journey to expertise starts here!', NULL, 'assets/uploads/1776575801_ChatGPTImageMar31202610_28_25AM.png', 2, 1, 'published', '2026-04-19 05:16:41', '2026-04-19 05:16:41'),
(3, 'From Zero to Full-Stack: The Ultimate Guide to Mastering Web Development in 2026', 'from-zero-to-full-stack-the-ultimate-guide-to-mastering-web-development-in-2026', '<h2>The New Era of Web Development</h2><p>The digital landscape of 2026 is vastly different from the early 2020s. We have moved past simple responsive design into the era of spatial computing, AI-native applications, and hyper-personalized user experiences. To become a full-stack developer today, you need a blend of traditional logic, creative design, and an understanding of how to leverage machine learning at every layer of the stack. This guide provides a structured path to help you navigate this complex but rewarding journey.</p><h2>Phase 1: Mastering the Modern Frontend</h2><p>While the trio of HTML, CSS, and JavaScript remains the foundation, the way we use them has evolved. HTML is now strictly semantic, serving as the accessibility backbone for screen readers and AI crawlers alike. CSS has matured to include features like native scoping, advanced container queries, and complex color functions that previously required pre-processors. You must master these to build interfaces that are not only beautiful but performant across a myriad of devices, from foldable phones to AR headsets.</p><p>JavaScript continues its reign, but TypeScript has transitioned from an \'optional extra\' to a \'mandatory requirement\' for any professional project. In 2026, your focus should be on mastering the latest ECMAScript features, understanding memory management, and becoming proficient in a major framework like React 20 or the latest Vue iteration. These frameworks now prioritize server components and streaming, reducing the client-side footprint and improving SEO and load times.</p><h2>Phase 2: The Intelligent Backend</h2><h3>From Servers to Serverless and Edge</h3><p>The concept of a \'server\' has become increasingly abstract. While Node.js is still the primary environment for most full-stack developers, the industry has pivoted toward Edge Computing. Learning to write functions that execute at the network edge—closer to the user—is a critical skill. This involves understanding platforms like Cloudflare Workers or Deno Deploy, where latency is measured in single-digit milliseconds.</p><h3>Integrating AI and Vector Databases</h3><p>In 2026, a Full-Stack developer is often an AI-Stack developer. You need to know how to connect your backend to LLM providers and, more importantly, how to manage data for these models. This means moving beyond traditional relational databases like PostgreSQL (though they remain vital) and learning about Vector Databases. These databases allow you to store and query high-dimensional embeddings, enabling features like semantic search and personalized recommendation engines that define modern apps.</p><h2>Phase 3: DevOps, Security, and Observability</h2><p>Building the app is only half the battle; keeping it running and secure is the other half. The DevOps role has shifted toward Platform Engineering, where developers are expected to understand CI/CD pipelines, containerization with Docker, and orchestration. Security is no longer an afterthought; with the rise of AI-driven cyber threats, implementing robust OAuth 2.1 flows, end-to-end encryption, and automated vulnerability scanning is essential for every full-stack engineer.</p><h2>Phase 4: The Human Element and Career Strategy</h2><p>With AI handling more of the boilerplate code, the value of a developer in 2026 lies in their problem-solving abilities and soft skills. You must be able to translate complex business requirements into technical architectures. Your portfolio should reflect this. Instead of building another \'To-Do List\' app, build a tool that uses AI to help local businesses manage their inventory or a social platform that prioritizes user privacy and data ownership.</p><h3>Networking and Continuous Learning</h3><p>The tech world moves fast. Engage with the community through open-source contributions, attend hybrid conferences, and stay updated with the latest RFCs (Request for Comments). The ability to learn how to learn is the most important skill you can possess. By following this roadmap, you aren\'t just learning to code; you are learning to innovate in a world where the only constant is change.</p>', 'Embarking on a web development journey can feel overwhelming given the vast landscape of frameworks and languages. This guide breaks down the most effective, project-based roadmap to help you transition from a curious beginner to a job-ready developer by focusing on the fundamentals and consistent practice.', 'full-stack developer 2026, learn web development, AI integration in web, modern javascript, coding roadmap, software engineering career, edge computing', 'assets/uploads/1776586022_jjjjjjjkkkkkkkkk.png', 2, 4, 'published', '2026-04-19 08:07:02', '2026-04-19 09:12:11'),
(4, 'From Curiosity to Creation: Why I Chose Computer Science and Engineering', 'from-curiosity-to-creation-why-i-chose-computer-science-and-engineering', '<h2>The Spark of Curiosity</h2><p>Choosing a major for graduation is perhaps one of the most significant decisions a young adult has to make. For many, it is a choice driven by market trends or parental expectations. However, for me, the decision to pursue Computer Science and Engineering (CSE) was a natural evolution of a lifelong fascination with technology. It wasn\'t just about the promise of a high-paying job; it was about understanding the invisible threads that weave our digital world together.</p><p>I remember the first time I sat in front of a computer as a child. To a young mind, it felt like pure magic. You press a button, and something happens on the screen. But as I grew older, that magic transitioned into a series of burning questions. How does the computer know what I am clicking? How do millions of people use the same website simultaneously without it breaking? These questions were the seeds of my interest in engineering. I didn\'t just want to use the tools; I wanted to be the person who understood how they were built.</p><h2>The Art of Logical Problem Solving</h2><p>One of the primary reasons I was drawn to Computer Science is the inherent logic involved in the field. Unlike many other disciplines where answers can be subjective or open to interpretation, computer science offers a realm of objective truth. A piece of code either works or it doesn’t. If it doesn’t work, there is a reason—a bug, a logic error, or a syntax mistake—waiting to be discovered and corrected.</p><h3>The Puzzle-Solver’s High</h3><p>There is a unique kind of adrenaline rush that comes from debugging a complex piece of software. It is akin to solving a high-stakes puzzle. This process of breaking down a massive, intimidating problem into smaller, manageable chunks is a skill that transcends the classroom. Engineering taught me that no problem is insurmountable if you have the patience to analyze it systematically. This mindset is what I wanted to cultivate during my graduation years, knowing it would serve me well in all areas of life.</p><h2>The Power to Create from Nothing</h2><p>In most engineering branches, you need massive physical materials—bricks, steel, chemicals, or complex circuits—to build something. In Computer Science, your primary tools are your mind and a keyboard. The barrier to entry for creation is incredibly low, yet the potential impact is infinitely high. You can sit in a quiet room and build an application that reaches millions of people across the globe.</p><p>This \"superpower\" of creation is incredibly empowering. During my early days of learning to code, I realized that I wasn\'t just learning a professional skill; I was learning a new way to express my creativity. Whether it is developing a simple game or designing a database, the feeling of seeing your thoughts manifest into a functional tool is incomparable. I chose CSE because I wanted to be a builder in the most modern sense of the word, creating solutions that exist in the digital ether but have real-world consequences.</p><h2>A Catalyst for Global Change</h2><p>We live in an era where technology is the backbone of every major industry. From healthcare and education to finance and environmental conservation, Computer Science is the engine driving innovation. I realized that if I wanted to make a tangible difference in the world, having a background in CSE would provide me with the most versatile toolkit possible.</p><p>Think about the role of Artificial Intelligence in diagnosing diseases or the impact of data science on tracking climate change. By studying Computer Science and Engineering, I am positioning myself at the forefront of these revolutions. It’s not just about writing code; it’s about leveraging technology to solve human problems. This sense of purpose and the ability to contribute to the greater good was a major factor in my decision-making process.</p><h2>The Thrill of a Dynamic Field</h2><p>Finally, I chose this path because Computer Science is a field that never stands still. The languages and frameworks that are popular today might be obsolete in a decade, replaced by something even more efficient and powerful. For some, this constant change is daunting. For me, it is exhilarating. A career in CSE is a commitment to lifelong learning. It requires you to stay curious, stay humble, and keep evolving. I didn\'t want a static job; I wanted a journey that would challenge me every single day.</p>', 'Deciding on a career path is a pivotal moment in any student\'s life. For me, the choice wasn\'t just about job prospects; it was about a lifelong fascination with how the world works under the hood. Here is the story of why I chose Computer Science and Engineering.', 'computer science engineering, why choose CSE, career in technology, software engineering journey, STEM education, coding passion, engineering graduation', 'assets/uploads/1776589845_pexels-nicolas-poupart-1241079-2360569.jpg', 2, 4, 'published', '2026-04-19 09:09:36', '2026-04-19 09:10:45');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `ip_address`, `created_at`) VALUES
(1, 1, '::1', '2026-04-19 05:27:31'),
(3, 4, '::1', '2026-04-19 09:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `post_shares`
--

CREATE TABLE `post_shares` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_shares`
--

INSERT INTO `post_shares` (`id`, `post_id`, `platform`, `created_at`) VALUES
(1, 1, 'web', '2026-04-19 05:27:45'),
(2, 1, 'web', '2026-04-19 05:27:48'),
(3, 1, 'web', '2026-04-19 05:27:50'),
(4, 1, 'web', '2026-04-19 05:27:52');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key_name` varchar(50) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`) VALUES
(1, 'site_name', ' Blog Yourself'),
(2, 'logo_url', 'assets/uploads/logo_1776579624_1-removebg-preview.png'),
(3, 'theme_mode', 'dark'),
(4, 'accent_color', '#3b82f6'),
(5, 'bg_color', '#0f172a'),
(6, 'favicon_url', 'assets/uploads/favicon_1776579378_2.png');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('super_admin','admin','author') DEFAULT 'admin',
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `last_attempt_at` datetime DEFAULT NULL,
  `suspended_reason` varchar(255) DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `social_link` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `email`, `phone`, `role`, `status`, `login_attempts`, `last_attempt_at`, `suspended_reason`, `last_activity`, `bio`, `social_link`, `avatar`, `created_at`) VALUES
(1, 'Super Admin', 'super_admin', '$2y$10$4L4HDkpxRp/E6hMjBD7BwecSlWPRLoIu6rM7cNTCuzj6Cywh35hOa', 'superadmin@gmail.com', '0123654789', 'super_admin', 'active', 0, NULL, NULL, '2026-04-19 15:21:40', 'N/A', '', 'assets/uploads/avatars/1776581747_2.png', '2026-04-19 03:56:14'),
(4, 'MST Onamika Jannat Ara', 'Misty', '$2y$10$8NHCNvmpoIYSaxqyCY8/Ue1GTMe2Uf1O5./JgXxxA7elBIRdGt6q.', 'misty@gmail.com', '01732430022', 'author', 'active', 0, NULL, NULL, '2026-04-19 15:19:58', 'N/A', '', 'assets/uploads/avatars/1776581463_0-02-03-22d79ecaf310de14559e38c22156ac5d6a610a0336af5d16804812ec4bf207c1_8f8f59ca2a5e9b7a.jpg', '2026-04-19 06:40:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scope` (`scope`),
  ADD KEY `idx_recipient` (`recipient_id`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `one_like_per_ip` (`post_id`,`ip_address`);

--
-- Indexes for table `post_shares`
--
ALTER TABLE `post_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `post_shares`
--
ALTER TABLE `post_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD CONSTRAINT `admin_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_shares`
--
ALTER TABLE `post_shares`
  ADD CONSTRAINT `post_shares_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
