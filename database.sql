-- Restaurant POS & Business Management SaaS Database Schema
-- MySQL 5.7+ / MariaDB 10.3+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table: settings
-- Key-value pairs for application configuration
-- --------------------------------------------------------
CREATE TABLE `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('hotel_name', 'My Restaurant'),
('tagline', 'Welcome to our restaurant'),
('address', '123 Main Street, City'),
('phone', '+1234567890'),
('whatsapp', '+1234567890'),
('logo_base64', ''),
('online_ordering_enabled', '1'),
('loyalty_points_per_dollar', '1'),
('loyalty_points_value', '0.10'),
('currency_symbol', '$'),
('timezone', 'UTC');

-- --------------------------------------------------------
-- Table: users
-- System users with role-based access control
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('Admin', 'Accountant', 'Counter', 'Server', 'Kitchen') NOT NULL,
  `pincode` VARCHAR(10) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`name`, `username`, `password`, `role`, `pincode`) VALUES
('System Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', '1234');

-- --------------------------------------------------------
-- Table: customers
-- Customer database with loyalty and credit tracking
-- --------------------------------------------------------
CREATE TABLE `customers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL UNIQUE,
  `balance` DECIMAL(10,2) DEFAULT 0.00,
  `opening_balance` DECIMAL(10,2) DEFAULT 0.00,
  `loyaltypoints` INT(11) DEFAULT 0,
  `birthday` DATE DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categories
-- Product categories
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: products
-- Menu items / products
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `categoryid` INT(11) DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `cost` DECIMAL(10,2) DEFAULT 0.00,
  `image` VARCHAR(255) DEFAULT NULL,
  `description` TEXT,
  `stockstatus` ENUM('In Stock', 'Low Stock', 'Out of Stock') DEFAULT 'In Stock',
  `is_available` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_category` (`categoryid`),
  CONSTRAINT `fk_category` FOREIGN KEY (`categoryid`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: ingredients
-- Raw materials for inventory tracking
-- --------------------------------------------------------
CREATE TABLE `ingredients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `unitofmeasure` VARCHAR(50) NOT NULL COMMENT 'kg, g, L, mL, pcs, etc.',
  `costperunit` DECIMAL(10,2) DEFAULT 0.00,
  `stocklevel` DECIMAL(10,2) DEFAULT 0.00,
  `minstocklevel` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Reorder point',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: productingredients
-- Recipe mapping: links products to ingredients with quantities
-- --------------------------------------------------------
CREATE TABLE `productingredients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `productid` INT(11) NOT NULL,
  `ingredientid` INT(11) NOT NULL,
  `quantityneeded` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_ingredient` (`productid`, `ingredientid`),
  KEY `fk_pi_product` (`productid`),
  KEY `fk_pi_ingredient` (`ingredientid`),
  CONSTRAINT `fk_pi_product` FOREIGN KEY (`productid`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pi_ingredient` FOREIGN KEY (`ingredientid`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: wastagelog
-- Track wastage of products or ingredients
-- --------------------------------------------------------
CREATE TABLE `wastagelog` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `itemtype` ENUM('product', 'ingredient') NOT NULL,
  `itemid` INT(11) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `reason` TEXT,
  `loggedby` INT(11) NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_wastage_user` (`loggedby`),
  CONSTRAINT `fk_wastage_user` FOREIGN KEY (`loggedby`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: seatingareas
-- Tables, rooms, halls with QR code support
-- --------------------------------------------------------
CREATE TABLE `seatingareas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('Table', 'Room', 'Hall', 'Bar', 'Outdoor') DEFAULT 'Table',
  `status` ENUM('Free', 'Occupied', 'Reserved', 'Maintenance') DEFAULT 'Free',
  `capacity` INT(11) DEFAULT 4,
  `qrcodeurl` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: kioskdevices
-- Self-service kiosk devices
-- --------------------------------------------------------
CREATE TABLE `kioskdevices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `seatingareaid` INT(11) DEFAULT NULL,
  `passwordhash` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_activity` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_kiosk_seating` (`seatingareaid`),
  CONSTRAINT `fk_kiosk_seating` FOREIGN KEY (`seatingareaid`) REFERENCES `seatingareas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: orders
-- Main order table
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `seatingareaid` INT(11) DEFAULT NULL,
  `guestcount` INT(11) DEFAULT 1,
  `userid` INT(11) NOT NULL,
  `customerid` INT(11) DEFAULT NULL,
  `totalamount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discountpercentage` DECIMAL(5,2) DEFAULT 0.00,
  `discountamount` DECIMAL(10,2) DEFAULT 0.00,
  `netamount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paidamount` DECIMAL(10,2) DEFAULT 0.00,
  `paymentmethod` ENUM('Cash', 'Card', 'UPI', 'Wallet', 'Credit', 'Mixed') DEFAULT 'Cash',
  `status` ENUM('Pending', 'Cooking', 'Served', 'Paid', 'Cancelled', 'Credit') DEFAULT 'Pending',
  `isqrorder` TINYINT(1) DEFAULT 0,
  `isonlineorder` TINYINT(1) DEFAULT 0,
  `kioskid` INT(11) DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_order_seating` (`seatingareaid`),
  KEY `fk_order_user` (`userid`),
  KEY `fk_order_customer` (`customerid`),
  KEY `fk_order_kiosk` (`kioskid`),
  KEY `idx_order_status` (`status`),
  KEY `idx_order_created` (`created_at`),
  CONSTRAINT `fk_order_seating` FOREIGN KEY (`seatingareaid`) REFERENCES `seatingareas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_order_user` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_customer` FOREIGN KEY (`customerid`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_order_kiosk` FOREIGN KEY (`kioskid`) REFERENCES `kioskdevices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: orderitems
-- Individual items within an order
-- --------------------------------------------------------
CREATE TABLE `orderitems` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `orderid` INT(11) NOT NULL,
  `productid` INT(11) NOT NULL,
  `qty` INT(11) NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL,
  `finalprice` DECIMAL(10,2) NOT NULL,
  `status` ENUM('Pending', 'Prepared', 'Ready', 'Served') DEFAULT 'Pending',
  `course` ENUM('Appetizer', 'Main', 'Dessert', 'Drink', 'Side') DEFAULT 'Main',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_oi_order` (`orderid`),
  KEY `fk_oi_product` (`productid`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`orderid`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`productid`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: employees
-- Employee records for HR and salary management
-- --------------------------------------------------------
CREATE TABLE `employees` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `basesalary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT,
  `joiningdate` DATE DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: salarypayments
-- Salary payment records
-- --------------------------------------------------------
CREATE TABLE `salarypayments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employeeid` INT(11) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `monthyear` VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `date` DATE NOT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_month` (`employeeid`, `monthyear`),
  KEY `fk_salary_employee` (`employeeid`),
  CONSTRAINT `fk_salary_employee` FOREIGN KEY (`employeeid`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: transactions
-- Financial transactions (income, expense, salary, etc.)
-- --------------------------------------------------------
CREATE TABLE `transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `type` ENUM('income', 'expense', 'salary', 'debtpayment', 'sale', 'loyaltyredemption', 'refund') NOT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `description` TEXT,
  `referenceid` INT(11) DEFAULT NULL COMMENT 'Links to order_id, salary_id, etc.',
  `referencetype` VARCHAR(50) DEFAULT NULL COMMENT 'order, salary, customer, etc.',
  `userid` INT(11) DEFAULT NULL,
  `date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_transaction_user` (`userid`),
  KEY `idx_transaction_type` (`type`),
  KEY `idx_transaction_date` (`date`),
  CONSTRAINT `fk_transaction_user` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: auditlogs
-- System audit trail
-- --------------------------------------------------------
CREATE TABLE `auditlogs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `userid` INT(11) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT,
  `ipaddress` VARCHAR(45) DEFAULT NULL,
  `useragent` VARCHAR(255) DEFAULT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_audit_user` (`userid`),
  KEY `idx_audit_timestamp` (`timestamp`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: productdiscounts
-- Time-based product discounts
-- --------------------------------------------------------
CREATE TABLE `productdiscounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `productid` INT(11) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `discountpercentage` DECIMAL(5,2) NOT NULL,
  `startdate` DATE NOT NULL,
  `enddate` DATE NOT NULL,
  `createdby` INT(11) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pd_product` (`productid`),
  KEY `fk_pd_user` (`createdby`),
  CONSTRAINT `fk_pd_product` FOREIGN KEY (`productid`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pd_user` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: notifications
-- In-app notifications
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `recipientrole` VARCHAR(50) DEFAULT NULL COMMENT 'Specific role or all',
  `recipientuserid` INT(11) DEFAULT NULL COMMENT 'Specific user if needed',
  `message` TEXT NOT NULL,
  `relatedid` INT(11) DEFAULT NULL,
  `relatedtype` VARCHAR(50) DEFAULT NULL,
  `type` ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
  `isread` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notification_user` (`recipientuserid`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`recipientuserid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: reservations
-- Table/area reservations
-- --------------------------------------------------------
CREATE TABLE `reservations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customerid` INT(11) DEFAULT NULL,
  `customername` VARCHAR(100) NOT NULL,
  `customerphone` VARCHAR(20) NOT NULL,
  `seatingareaid` INT(11) DEFAULT NULL,
  `reservationtime` DATETIME NOT NULL,
  `guestcount` INT(11) DEFAULT 1,
  `status` ENUM('Pending', 'Confirmed', 'Seated', 'Completed', 'Cancelled', 'NoShow') DEFAULT 'Pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_reservation_customer` (`customerid`),
  KEY `fk_reservation_seating` (`seatingareaid`),
  CONSTRAINT `fk_reservation_customer` FOREIGN KEY (`customerid`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reservation_seating` FOREIGN KEY (`seatingareaid`) REFERENCES `seatingareas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: waitlist
-- Customer waitlist for busy periods
-- --------------------------------------------------------
CREATE TABLE `waitlist` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customername` VARCHAR(100) NOT NULL,
  `customerphone` VARCHAR(20) NOT NULL,
  `guestcount` INT(11) NOT NULL,
  `preferredtime` DATETIME DEFAULT NULL,
  `status` ENUM('Waiting', 'Seated', 'Cancelled', 'Expired') DEFAULT 'Waiting',
  `addedby` INT(11) DEFAULT NULL,
  `seatingareaid` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_waitlist_user` (`addedby`),
  KEY `fk_waitlist_seating` (`seatingareaid`),
  CONSTRAINT `fk_waitlist_user` FOREIGN KEY (`addedby`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_waitlist_seating` FOREIGN KEY (`seatingareaid`) REFERENCES `seatingareas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: feedback
-- Customer feedback and ratings
-- --------------------------------------------------------
CREATE TABLE `feedback` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `orderid` INT(11) DEFAULT NULL,
  `customerid` INT(11) DEFAULT NULL,
  `rating` TINYINT(1) NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comments` TEXT,
  `isresponded` TINYINT(1) DEFAULT 0,
  `response` TEXT,
  `respondedby` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_feedback_order` (`orderid`),
  KEY `fk_feedback_customer` (`customerid`),
  KEY `fk_feedback_user` (`respondedby`),
  CONSTRAINT `fk_feedback_order` FOREIGN KEY (`orderid`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_customer` FOREIGN KEY (`customerid`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_user` FOREIGN KEY (`respondedby`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: timeclock
-- Employee time clock in/out records
-- --------------------------------------------------------
CREATE TABLE `timeclock` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `userid` INT(11) NOT NULL,
  `clockin` DATETIME NOT NULL,
  `clockout` DATETIME DEFAULT NULL,
  `ipaddress` VARCHAR(45) DEFAULT NULL,
  `status` ENUM('Clocked In', 'Clocked Out') DEFAULT 'Clocked Out',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_timeclock_user` (`userid`),
  CONSTRAINT `fk_timeclock_user` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
