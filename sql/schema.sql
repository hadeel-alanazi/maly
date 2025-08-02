CREATE DATABASE IF NOT EXISTS smart_finance;
USE smart_finance;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  income DECIMAL(10,2)
);

CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  category VARCHAR(50),
  amount DECIMAL(10,2),
  date DATE,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(100),
  target_amount DECIMAL(10,2),
  current_saved DECIMAL(10,2),
  deadline DATE,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
