

-- Table: Users
CREATE TABLE users (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: Students
CREATE TABLE students (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  course VARCHAR(100),
  year_level INT,
  status ENUM('Active', 'Inactive') DEFAULT 'Active'
);

-- Table: Student Fees
CREATE TABLE student_fees (
  fee_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  fee_type ENUM('Tuition', 'Miscellaneous', 'Laboratory', 'Other') NOT NULL,
  amount VARCHAR(255) NOT NULL,
  discount_amount VARCHAR(255) DEFAULT '0.00',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Table: Fee Summaries
CREATE TABLE fee_summaries (
  summary_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  total_computed_fee VARCHAR(255) DEFAULT '0.00',
  amount_paid VARCHAR(255) DEFAULT '0.00',
  outstanding_balance VARCHAR(255) DEFAULT '0.00',
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Table: Invoices
CREATE TABLE invoices (
  invoice_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  invoice_number VARCHAR(255) UNIQUE NOT NULL,
  date_created DATE DEFAULT CURRENT_DATE,
  status ENUM('Unpaid', 'Paid', 'Partial', 'Cancelled') DEFAULT 'Unpaid',
  total_amount VARCHAR(255) DEFAULT '0.00',
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Table: Refund Requests
CREATE TABLE refund_requests (
  refund_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  amount_requested VARCHAR(255) NOT NULL,
  reason TEXT,
  status ENUM('Pending', 'Approved', 'Denied') DEFAULT 'Pending',
  date_requested DATE DEFAULT CURRENT_DATE,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Table: Financial Reports
CREATE TABLE financial_reports (
  report_id INT AUTO_INCREMENT PRIMARY KEY,
  report_type ENUM('Monthly', 'Annual') NOT NULL,
  period_from DATE,
  period_to DATE,
  total_collected VARCHAR(255) DEFAULT '0.00',
  total_due VARCHAR(255) DEFAULT '0.00',
  total_refunds VARCHAR(255) DEFAULT '0.00',
  generated_by VARCHAR(100),
  date_generated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: Audit Trail
CREATE TABLE audit_trail (
  audit_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(50),
  entity VARCHAR(100),
  entity_id INT,
  description TEXT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert sample users
INSERT INTO users (username, email, password_hash, role)
VALUES
('admin1', 'danielzabat01@gmail.com', '$2y$10$PMLFgY7yxjpBFOSWpN1t9.Qz2KZQSRcGfY5fifnpHDZAXRuo8OuiG', 'admin'),
('user1', 'danielzabat00@gmail.com', '$2y$10$PMLFgY7yxjpBFOSWpN1t9.Qz2KZQSRcGfY5fifnpHDZAXRuo8OuiG', 'user');

-- Insert sample students
INSERT INTO students (first_name, last_name, course, year_level, status)
VALUES
('John', 'Doe', 'BS Computer Science', 3, 'Active'),
('Jane', 'Smith', 'BS Accountancy', 2, 'Active'),
('Alice', 'Johnson', 'BS Information Technology', 1, 'Inactive');


INSERT INTO `student`(`Student_ID`, `First_Name`, `Last_Name`, `DoB`, `Gender`, `Email`, `Address`) VALUES
('John', 'Doe', '2000-05-10', 'Male', 'johndoe@example.com', '123 Elm Street, Quezon City, Philippines'),
('Maria', 'Santos', '2001-07-22', 'Female', 'mariasantos@example.com', '456 Maple Avenue, Makati, Philippines'),
('Carlos', 'Gomez', '1999-11-30', 'Male', 'carlosgomez@example.com', '789 Oak Road, Pasig, Philippines');


-- change 'students' table name to 'student', and 'student_id' to 'Student_ID'