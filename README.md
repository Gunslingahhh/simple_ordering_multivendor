# Multi-Vendor Food Ordering System (PHP & MySQL)

A lightweight, responsive, multi-vendor food ordering system with WhatsApp direct ordering, built with PHP (PDO), MySQL, HTML, CSS, and Bootstrap 5 without unnecessary frameworks.

---

## Features

- **Customer Experience**:
  - Browse available vendors.
  - View vendor menus with images, descriptions, and prices.
  - Add items to order summary and calculate totals.
  - Direct WhatsApp order submission pre-formatted with all items and total price.

- **Vendor Dashboard**:
  - Secure login/logout.
  - Store profile management (Name, description, address, contact, WhatsApp number, logo, Open/Closed status).
  - Menu item management (Add, edit, delete, enable/disable status, image upload).
  - Strict data isolation (Vendors can only access their own store and items).

- **Administrator Dashboard**:
  - Secure login/logout.
  - Create, edit, activate/deactivate, and delete vendor accounts.
  - Monitor all registered vendors.

---

## Installation Guide (XAMPP / Local Server)

1. **Prerequisites**:
   - Install [XAMPP](https://www.apachefriends.org/) (or any Apache, PHP 8.x, MySQL stack).
   - Ensure Apache and MySQL services are running.

2. **Place Project Files**:
   - Copy the `simple_ordering_multivendor` folder into your web server's root directory (e.g., `C:/xampp/htdocs/` on Windows or `/var/www/html/` on Linux).

3. **Import Database**:
   - Open phpMyAdmin (`http://localhost/phpmyadmin/`).
   - Create a new database named `food_ordering_db`.
   - Import the provided `database.sql` file.

4. **Directory Permissions (Linux)**:
   - Ensure upload directories have correct write permissions:
     ```bash
     chmod -R 755 uploads/
     chown -R www-data:www-data uploads/
     ```

5. **Access the Application**:
   - Open your browser and navigate to:
     `http://localhost/simple_ordering_multivendor/`

6. **Default Administrator Credentials**:
   - **Username**: `admin`
   - **Password**: `admin123`

7. **Getting Started**:
   - Log in with the admin account to create vendor accounts and set their WhatsApp numbers.
   - Log in with vendor accounts to manage menus and upload items.
   - Browse the homepage as a customer to place orders via WhatsApp!
