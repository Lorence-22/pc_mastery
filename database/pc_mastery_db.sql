-- PC Mastery database. Import via phpMyAdmin (Import tab).
CREATE DATABASE IF NOT EXISTS pc_mastery_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pc_mastery_db;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','student') NOT NULL DEFAULT 'student',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  reset_token VARCHAR(64) NULL,
  reset_expires DATETIME NULL,
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_role_status (role,status)
) ENGINE=InnoDB;

CREATE TABLE student_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  student_id VARCHAR(30) NOT NULL UNIQUE,
  first_name VARCHAR(60) NOT NULL, middle_name VARCHAR(60) NULL, last_name VARCHAR(60) NOT NULL,
  course VARCHAR(100) NOT NULL, year_level VARCHAR(20) NOT NULL, section VARCHAR(30) NOT NULL,
  profile_picture VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_course (course,year_level,section)
) ENGINE=InnoDB;

CREATE TABLE admin_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  full_name VARCHAR(120) NOT NULL, position VARCHAR(100) NULL, profile_picture VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE computer_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE, description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE computer_parts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL UNIQUE,
  icon VARCHAR(60) NULL, image VARCHAR(255) NULL,
  description TEXT, function_text TEXT, purpose TEXT, specifications TEXT,
  install_location TEXT, install_instructions TEXT, related_parts VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES computer_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE reviewer_topics (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL, category VARCHAR(80) NOT NULL,
  content LONGTEXT NULL, related_quiz_id INT UNSIGNED NULL,
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id),
  INDEX idx_cat (category)
) ENGINE=InnoDB;

CREATE TABLE learning_materials (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic_id INT UNSIGNED NULL,
  title VARCHAR(150) NOT NULL, description TEXT NULL,
  file_name VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL,
  file_type ENUM('pdf','image') NOT NULL DEFAULT 'pdf',
  downloadable TINYINT(1) NOT NULL DEFAULT 1,
  uploaded_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (topic_id) REFERENCES reviewer_topics(id) ON DELETE SET NULL,
  FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE material_progress (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  item_type ENUM('topic','material','part') NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_prog (user_id,item_type,item_id)
) ENGINE=InnoDB;

CREATE TABLE quizzes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL, description TEXT NULL, category VARCHAR(80) NOT NULL,
  method ENUM('pdf','builder') NOT NULL DEFAULT 'builder',
  pdf_file VARCHAR(255) NULL,
  time_limit_minutes INT UNSIGNED NOT NULL DEFAULT 30,
  passing_score DECIMAL(5,2) NOT NULL DEFAULT 75.00,
  available_from DATETIME NULL, available_until DATETIME NULL,
  max_attempts INT UNSIGNED NOT NULL DEFAULT 3,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id),
  INDEX idx_cat_active (category,is_active)
) ENGINE=InnoDB;

CREATE TABLE quiz_questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT UNSIGNED NOT NULL,
  question_type ENUM('multiple_choice','multiple_answer','true_false','identification','enumeration','matching') NOT NULL,
  question_text TEXT NOT NULL,
  correct_answer TEXT NULL COMMENT 'identification/enumeration: answers separated by |',
  points DECIMAL(5,2) NOT NULL DEFAULT 1,
  topic VARCHAR(80) NULL, explanation TEXT NULL, sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
  INDEX idx_quiz (quiz_id,sort_order)
) ENGINE=InnoDB;

CREATE TABLE quiz_choices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_id INT UNSIGNED NOT NULL,
  choice_text VARCHAR(255) NOT NULL,
  match_text VARCHAR(255) NULL COMMENT 'matching: right-hand pair',
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
  attempt_no INT UNSIGNED NOT NULL DEFAULT 1,
  started_at DATETIME NOT NULL, submitted_at DATETIME NULL,
  score DECIMAL(6,2) NULL, total_points DECIMAL(6,2) NULL, percentage DECIMAL(5,2) NULL,
  status ENUM('in_progress','submitted','needs_review','graded') NOT NULL DEFAULT 'in_progress',
  passed TINYINT(1) NULL, pdf_answer_text MEDIUMTEXT NULL, admin_feedback TEXT NULL,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_attempt (quiz_id,user_id,attempt_no),
  INDEX idx_user (user_id,status)
) ENGINE=InnoDB;

CREATE TABLE quiz_answers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id INT UNSIGNED NOT NULL, question_id INT UNSIGNED NOT NULL,
  answer_text TEXT NULL, is_correct TINYINT(1) NULL,
  points_earned DECIMAL(5,2) NOT NULL DEFAULT 0, needs_review TINYINT(1) NOT NULL DEFAULT 0,
  saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
  UNIQUE KEY uq_ans (attempt_id,question_id)
) ENGINE=InnoDB;

CREATE TABLE assembly_modules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL, description TEXT NULL,
  passing_score DECIMAL(5,2) NOT NULL DEFAULT 80.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE assembly_steps (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  module_id INT UNSIGNED NOT NULL, step_no INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL, instruction TEXT NOT NULL, hint TEXT NULL, explanation TEXT NULL,
  step_type ENUM('safety','placement','cable','inspection','test') NOT NULL DEFAULT 'placement',
  points DECIMAL(5,2) NOT NULL DEFAULT 5,
  FOREIGN KEY (module_id) REFERENCES assembly_modules(id) ON DELETE CASCADE,
  UNIQUE KEY uq_step (module_id,step_no)
) ENGINE=InnoDB;

CREATE TABLE assembly_components (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  step_id INT UNSIGNED NOT NULL,
  part_id INT UNSIGNED NULL,
  label VARCHAR(100) NOT NULL, icon VARCHAR(60) NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = correct answer for this step',
  target_zone VARCHAR(60) NOT NULL COMMENT 'drop zone key this component belongs in',
  FOREIGN KEY (step_id) REFERENCES assembly_steps(id) ON DELETE CASCADE,
  FOREIGN KEY (part_id) REFERENCES computer_parts(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE assembly_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  module_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
  mode ENUM('practice','assessment') NOT NULL,
  started_at DATETIME NOT NULL, completed_at DATETIME NULL,
  score DECIMAL(6,2) NULL, total_points DECIMAL(6,2) NULL, percentage DECIMAL(5,2) NULL,
  passed TINYINT(1) NULL, current_step INT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('in_progress','completed') NOT NULL DEFAULT 'in_progress',
  FOREIGN KEY (module_id) REFERENCES assembly_modules(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_mode (user_id,mode,status)
) ENGINE=InnoDB;

CREATE TABLE assembly_attempt_details (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attempt_id INT UNSIGNED NOT NULL, step_id INT UNSIGNED NOT NULL,
  component_id INT UNSIGNED NULL, zone_dropped VARCHAR(60) NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0, in_sequence TINYINT(1) NOT NULL DEFAULT 1,
  points_earned DECIMAL(5,2) NOT NULL DEFAULT 0, hint_used TINYINT(1) NOT NULL DEFAULT 0,
  tries INT UNSIGNED NOT NULL DEFAULT 1,
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (attempt_id) REFERENCES assembly_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (step_id) REFERENCES assembly_steps(id) ON DELETE CASCADE,
  UNIQUE KEY uq_detail (attempt_id,step_id)
) ENGINE=InnoDB;

CREATE TABLE certificates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  certificate_no VARCHAR(30) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL, attempt_id INT UNSIGNED NOT NULL UNIQUE,
  title VARCHAR(150) NOT NULL, score DECIMAL(5,2) NOT NULL,
  status ENUM('valid','revoked') NOT NULL DEFAULT 'valid',
  revoked_reason VARCHAR(255) NULL,
  issued_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (attempt_id) REFERENCES assembly_attempts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL, action VARCHAR(80) NOT NULL, details TEXT NULL,
  ip_address VARCHAR(45) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE system_settings (
  setting_key VARCHAR(60) PRIMARY KEY, setting_value TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE announcements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL, body TEXT NOT NULL, created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL, message VARCHAR(255) NOT NULL, link VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_read (user_id,is_read)
) ENGINE=InnoDB;
SET FOREIGN_KEY_CHECKS=1;

-- ===== Seed data =====
-- 'INIT' is replaced with password_hash('Admin@12345') the first time auth/login.php loads.
INSERT INTO users (username,email,password_hash,role,must_change_password) VALUES ('admin','admin@pcmastery.local','INIT','admin',1);
INSERT INTO admin_profiles (user_id,full_name,position) VALUES (1,'System Administrator','Administrator');

INSERT INTO system_settings (setting_key,setting_value) VALUES
('institution_name','Your School Name'),('assembly_passing_score','80'),
('leaderboard_enabled','1'),('certificate_signatory','System Administrator'),('max_upload_mb','10');

INSERT INTO computer_categories (name,description) VALUES
('Core Components','Parts that make the computer run'),('Storage','Data storage devices'),
('Cooling','Parts that remove heat'),('Power & Cables','Power delivery and connections'),
('Peripherals','Input and output devices'),('Housing','Enclosure');

INSERT INTO computer_parts (category_id,name,slug,icon,description,function_text,purpose,specifications,install_location,install_instructions,related_parts) VALUES
(1,'Motherboard','motherboard','fa-microchip','The main circuit board that connects all components.','Provides sockets, slots and buses so parts can communicate.','Acts as the central backbone of the PC.','Form factor (ATX/mATX/ITX), CPU socket, chipset, RAM slots, PCIe slots, SATA/M.2 ports.','Mounted on standoffs inside the case.','Align the I/O shield, match standoffs to mounting holes, secure with screws without overtightening.','CPU, RAM, GPU, PSU, Computer Case'),
(1,'Processor (CPU)','cpu','fa-microchip','The central processing unit, the brain of the computer.','Executes instructions and performs calculations.','Runs the operating system and programs.','Cores/threads, clock speed (GHz), socket type, cache, TDP.','CPU socket on the motherboard.','Lift the retention lever, align the gold triangle with the socket marker, lower the CPU gently, close the lever. Never force it.','Motherboard, CPU Cooler, Heat Sink'),
(1,'Random Access Memory (RAM)','ram','fa-memory','Volatile short-term memory.','Stores data the CPU is actively using.','Allows fast multitasking.','Type (DDR4/DDR5), capacity (GB), speed (MHz), CAS latency.','DIMM slots on the motherboard.','Open the slot clips, align the notch, press firmly on both ends until the clips click.','Motherboard, CPU'),
(2,'Hard Disk Drive (HDD)','hdd','fa-hard-drive','Mechanical storage using spinning platters.','Stores data permanently.','Low-cost, high-capacity storage.','Capacity (TB), RPM (5400/7200), SATA interface, 3.5 inch.','3.5 inch drive bay.','Slide into the bay, secure with screws, connect SATA data and SATA power cables.','SSD, SATA Cables, PSU'),
(2,'Solid State Drive (SSD)','ssd','fa-hard-drive','Flash-based storage with no moving parts.','Stores the OS and data with fast access.','Fast boot and load times.','SATA 2.5 inch or NVMe M.2, capacity, read/write speed.','2.5 inch bay or M.2 slot.','SATA: mount in bay, connect SATA data and power. M.2: insert at a slight angle and secure with the screw.','HDD, Motherboard, SATA Cables'),
(4,'Power Supply Unit (PSU)','psu','fa-plug','Converts AC mains power to low-voltage DC.','Supplies regulated power to all components.','Powers the entire system.','Wattage, 80 Plus rating, modular type, connectors (24-pin, 8-pin CPU, PCIe).','PSU bay, usually bottom rear of the case.','Place with fan facing the vent, secure with four screws, then route cables.','Motherboard, GPU, Power Cables'),
(1,'Graphics Processing Unit (GPU)','gpu','fa-display','Dedicated card that renders images and video.','Processes graphics and parallel workloads.','Gaming, video editing, 3D, display output.','VRAM, GPU chip, PCIe x16, power connectors, TDP.','Top PCIe x16 slot.','Remove slot covers, press the card into the slot until the latch clicks, screw to the case, connect PCIe power.','Motherboard, PSU'),
(3,'CPU Cooler','cpu-cooler','fa-fan','Heat sink and fan assembly for the CPU.','Moves CPU heat into the air.','Prevents overheating and throttling.','Socket compatibility, TDP rating, height, fan size.','On top of the CPU.','Apply thermal paste, align the mounting bracket, tighten evenly, plug the fan into the CPU_FAN header.','CPU, Heat Sink, Cooling Fans'),
(3,'Heat Sink','heat-sink','fa-temperature-arrow-down','Metal fin block that absorbs and spreads heat.','Conducts heat away from a chip.','Passive thermal management.','Material (aluminum/copper), fin density, heat pipes.','On CPU, chipset or VRM.','Mount with thermal interface material and firm, even pressure.','CPU Cooler, CPU'),
(6,'Computer Case','computer-case','fa-computer','The enclosure for internal parts.','Protects components and provides airflow.','Houses and cools the build.','Form factor support, drive bays, fan mounts, front panel I/O.','Encloses the entire build.','Remove side panels, install standoffs, prepare the I/O shield.','Motherboard, Cooling Fans, PSU'),
(5,'Monitor','monitor','fa-desktop','Visual display output.','Shows what the computer outputs.','Primary visual interface.','Size, resolution, refresh rate, panel type, ports (HDMI/DP).','External; connects to the GPU or motherboard video port.','Connect HDMI/DisplayPort to the GPU, plug in power.','GPU'),
(5,'Keyboard','keyboard','fa-keyboard','Text input device.','Sends keystrokes to the PC.','Primary text input.','Layout, switch type, USB/wireless.','External; USB port.','Plug into a USB port.','Mouse'),
(5,'Mouse','mouse','fa-computer-mouse','Pointing device.','Controls the pointer.','Navigation and selection.','DPI, sensor, buttons, USB/wireless.','External; USB port.','Plug into a USB port.','Keyboard'),
(3,'Cooling Fans','cooling-fans','fa-fan','Case fans that move air.','Create airflow through the case.','Lower internal temperature.','Size (120/140 mm), RPM, CFM, 3-pin/4-pin PWM.','Front, top and rear mounts.','Front/bottom = intake, rear/top = exhaust. Follow airflow arrows, connect to SYS_FAN headers.','Computer Case, CPU Cooler'),
(4,'SATA Cables','sata-cables','fa-grip-lines','Cables for drive data.','Carry data between drive and motherboard.','Connect HDD/SSD to the system.','SATA III, 6 Gbps, L-shaped latch connectors.','Drive to motherboard SATA port.','Connect one end to the drive and the other to a motherboard SATA port.','HDD, SSD, Motherboard'),
(4,'Power Cables','power-cables','fa-bolt','Cables from the PSU to components.','Deliver DC power.','Power every component.','24-pin ATX, 4+4 pin CPU, PCIe 6+2 pin, SATA power.','PSU to motherboard, CPU, GPU and drives.','Match connector keys and push until latches click.','PSU, Motherboard, GPU');

INSERT INTO assembly_modules (title,description,passing_score) VALUES ('Desktop PC Assembly Certification','Assemble a desktop computer step by step in the correct order.',80);
