-- ============================================================
--  LibTrack Book Borrowing System — Database Setup
--  Run this in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS libtrack_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE libtrack_db;

-- ── Admin users ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(60)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Borrowers / Students ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS borrowers (
    borrower_id  INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    contact      VARCHAR(30),
    address      VARCHAR(255),
    email        VARCHAR(100),
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Books ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS books (
    book_id    INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(200) NOT NULL,
    author     VARCHAR(150) NOT NULL,
    isbn       VARCHAR(30)  UNIQUE,
    available  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Borrowing Records ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS borrow_records (
    record_id    INT AUTO_INCREMENT PRIMARY KEY,
    borrower_id  INT NOT NULL,
    book_id      INT NOT NULL,
    borrow_date  DATE NOT NULL,
    due_date     DATE NOT NULL,
    return_date  DATE DEFAULT NULL,
    status       ENUM('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed',
    FOREIGN KEY (borrower_id) REFERENCES borrowers(borrower_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id)     REFERENCES books(book_id)         ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Sample books ─────────────────────────────────────────────
INSERT INTO books (title, author, isbn) VALUES
  ('Noli Me Tangere',     'Jose Rizal',         '978-971-10-0001-1'),
  ('El Filibusterismo',   'Jose Rizal',         '978-971-10-0002-8'),
  ('Florante at Laura',   'Francisco Balagtas', '978-971-10-0003-5'),
  ('Harry Potter and the Sorcerer''s Stone', 'J.K. Rowling', '978-0-439-70818-8'),
  ('The Alchemist',       'Paulo Coelho',       '978-0-06-231609-7'),
  ('To Kill a Mockingbird', 'Harper Lee',       '978-0-06-112008-4');

-- ============================================================
--  CREATE YOUR OWN ADMIN ACCOUNT
--  Replace  YourUsername  and  YourFullName  below.
--  The password hash below is for:  LibTrack@2024
--  Generate your own at: https://bcrypt-generator.com  (cost 10)
--  Then paste the hash in place of the value below.
-- ============================================================
INSERT INTO admins (username, password, full_name) VALUES
  ('YourUsername',
   '$2y$10$PasteYourOwnBcryptHashHerexxxxxxxxxxxxxxxxxxxxxxxxxx',
   'YourFullName');

-- ── Quick way: run this PHP snippet in your browser once ─────
-- Create a file  make_admin.php  in your project with this code,
-- open it once in your browser, then DELETE the file immediately:
--
--   <?php
--   require 'config.php';
--   $user = 'yourUsername';
--   $pass = password_hash('yourPassword', PASSWORD_BCRYPT);
--   $name = 'Your Full Name';
--   $stmt = $pdo->prepare("INSERT INTO admins (username,password,full_name) VALUES(?,?,?)");
--   $stmt->execute([$user,$pass,$name]);
--   echo 'Admin created! Delete this file now.';
