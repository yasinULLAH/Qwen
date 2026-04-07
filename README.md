# 🚀 FeastFlow Pro - Restaurant Management System

**The Ultimate Restaurant, POS, and Business Management SaaS Application**

## 📋 Overview

FeastFlow Pro is a comprehensive, single-file architecture restaurant management system built with PHP (vanilla/PDO), MySQL, HTML, CSS (Bootstrap 5), and JavaScript (jQuery/AJAX).

### Tagline
پرانے کھاتوں اور پرچیوں کو کہیں الوداع، اور اپنے ریسٹورنٹ کو دیں ایک شاندار ڈیجیٹل اڑان! 🦅

## ✨ Key Features

### 🔐 Security & Authentication
- Role-Based Access Control (RBAC) - Admin, Accountant, Counter, Server, Kitchen
- Math CAPTCHA on login
- CSRF tokens on all forms
- Session expiration/idle limits
- Password hashing (bcrypt)

### 💳 Point of Sale (POS)
- Smart counter billing
- Table management
- Customer selection for Khata (Credit)
- Partial payments support
- Loyalty points redemption
- Discount application

### 📱 QR Menu & Kiosk
- Dynamic QR codes for each table
- Customer self-ordering via mobile
- Secure kiosk login system
- Self-service walk-in orders

### 👨‍🍳 Kitchen Display System (KDS)
- Real-time order viewing
- Order status management (Pending → Cooking → Served)
- Push notifications to servers
- Course-wise item grouping

### 📓 Customer Khata (Ledger)
- Complete ledger calculation
- Running balances
- Opening balances
- Partial debt payments
- Transaction history

### 📦 Inventory & Recipes
- Raw ingredient tracking
- Recipe mapping (productingredients)
- Automatic stock deduction on order payment
- Low stock alerts
- Wastage logging

### 👥 HR & Payroll
- Employee management
- Time clock with PIN code
- Salary processing
- Unique month/year validation

### 💰 Finance Management
- Income tracking
- Expense management
- Salary payments
- Debt payments
- Loyalty redemptions

### 📊 Reports & Analytics
- Daily/Weekly/Monthly sales reports
- Top selling products
- Customer analytics
- Audit logs

### 🛠️ Admin Tools
- Global settings management
- Database backup (SQL export)
- Database restore (SQL import)
- User management
- Kiosk device management

## 🏗️ Architecture

```
FeastFlow Pro/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── ajax/
│   ├── auth_actions.php
│   ├── pos_actions.php
│   ├── kitchen_actions.php
│   ├── finance_actions.php
│   ├── inventory_actions.php
│   ├── customer_actions.php
│   ├── order_actions.php
│   ├── employee_actions.php
│   └── notification_actions.php
├── config/
│   └── database.php
├── includes/
│   ├── functions.php
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── views/
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── pos.php
│   ├── kitchen.php
│   ├── khata.php
│   ├── orders.php
│   ├── customers.php
│   ├── inventory.php
│   ├── products.php
│   ├── employees.php
│   ├── payroll.php
│   ├── timeclock.php
│   ├── finance.php
│   ├── reports.php
│   ├── reservations.php
│   ├── waitlist.php
│   ├── seating.php
│   ├── wastage.php
│   ├── settings.php
│   ├── backup.php
│   ├── users.php
│   ├── audit.php
│   ├── notifications.php
│   ├── kiosk_login.php
│   ├── kiosk_view.php
│   ├── qr_menu.php
│   └── print_receipt.php
├── index.php
├── database.sql
└── README.md
```

## 🚀 Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB
- Apache/Nginx web server
- PDO extension enabled

### Steps

1. **Clone/Download the project**
   ```bash
   cd /var/www/html
   ```

2. **Create Database**
   - Open phpMyAdmin
   - Create new database: `feastflow_pro`
   - Import `database.sql`

3. **Configure Database Connection**
   - Edit `/config/database.php`
   - Update credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'feastflow_pro');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

4. **Access Application**
   - Open browser: `http://localhost/feastflow-pro`
   - Login with default credentials:
     - Username: `admin`
     - Password: `admin123`

## 🗄️ Database Tables

| Table | Description |
|-------|-------------|
| `settings` | Key-value configuration |
| `users` | System users with roles |
| `customers` | Customer database with balance & loyalty |
| `categories` | Product categories |
| `products` | Menu items |
| `ingredients` | Raw materials stock |
| `productingredients` | Recipe mappings |
| `wastagelog` | Waste tracking |
| `seatingareas` | Tables/rooms management |
| `kioskdevices` | Kiosk authentication |
| `orders` | Order headers |
| `orderitems` | Order line items |
| `employees` | Staff database |
| `salarypayments` | Salary records |
| `transactions` | Financial transactions |
| `auditlogs` | Activity logs |
| `productdiscounts` | Promotional discounts |
| `notifications` | System notifications |
| `reservations` | Table bookings |
| `waitlist` | Waiting list |
| `feedback` | Customer reviews |
| `timeclock` | Attendance records |

## 🔒 Security Features

1. **CSRF Protection**: Token-based protection on all forms
2. **Password Hashing**: Bcrypt algorithm
3. **Math CAPTCHA**: Bot prevention on login
4. **Role-Based Access**: Granular permission control
5. **Session Management**: Secure session handling
6. **SQL Injection Prevention**: Prepared statements (PDO)
7. **XSS Protection**: Input sanitization and output escaping
8. **Audit Logging**: Complete activity trail

## 📱 Responsive Design

- Bootstrap 5 framework
- Mobile-friendly interface
- Touch-optimized POS
- Tablet-compatible kitchen display

## 🎯 Target Audience

- Small & Medium Restaurants
- Cafes & Coffee Shops
- Fast Food Corners
- Food Trucks
- Fine Dining Hotels

## 💡 Unique Selling Points

✅ **Single File Architecture** - Easy deployment  
✅ **No Monthly Fees** - One-time investment  
✅ **Offline Capable** - Works without internet  
✅ **Lightning Fast** - Optimized performance  
✅ **Complete Solution** - All modules included  

## 📞 Support

For issues and feature requests, please contact the development team.

---

**Built with ❤️ for Restaurant Owners**

*Version 1.0.0*
