-- Create Database
CREATE DATABASE IF NOT EXISTS student_finance_system;
USE student_finance_system;

-- Table: Users
CREATE TABLE users (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: Students
CREATE TABLE students (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  student_number VARCHAR(20) UNIQUE NOT NULL,
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
  amount DECIMAL(10,2) NOT NULL,
  discount_amount DECIMAL(10,2) DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Table: Fee Summaries
CREATE TABLE fee_summaries (
  summary_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  total_computed_fee DECIMAL(10,2) DEFAULT 0.00,
  amount_paid DECIMAL(10,2) DEFAULT 0.00,
  outstanding_balance DECIMAL(10,2) DEFAULT 0.00,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Table: Invoices
CREATE TABLE invoices (
  invoice_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  invoice_number VARCHAR(30) UNIQUE NOT NULL,
  date_created DATE DEFAULT CURRENT_DATE,
  status ENUM('Unpaid', 'Paid', 'Partial', 'Cancelled') DEFAULT 'Unpaid',
  total_amount DECIMAL(10,2) DEFAULT 0.00,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

CREATE INDEX idx_invoices_student_id ON invoices(student_id);
CREATE INDEX idx_invoices_date_created ON invoices(date_created);

-- Table: Invoice Items
CREATE TABLE invoice_items (
  item_id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE CASCADE
);

-- Table: Scholarship Applications
CREATE TABLE scholarship_applications (
  application_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  type ENUM('Academic', 'Athletic', 'Financial Aid', 'Other') NOT NULL,
  date_applied DATE DEFAULT CURRENT_DATE,
  status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Table: Scholarship Records
CREATE TABLE scholarship_records (
  scholarship_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  type VARCHAR(100),
  amount DECIMAL(10,2),
  date_awarded DATE DEFAULT CURRENT_DATE,
  approval_status ENUM('Approved', 'Pending', 'Revoked') DEFAULT 'Approved',
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Table: Refund Requests
CREATE TABLE refund_requests (
  refund_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  amount_requested DECIMAL(10,2) NOT NULL,
  reason TEXT,
  status ENUM('Pending', 'Approved', 'Denied') DEFAULT 'Pending',
  date_requested DATE DEFAULT CURRENT_DATE,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Table: Refund Notes
CREATE TABLE refund_notes (
  note_id INT AUTO_INCREMENT PRIMARY KEY,
  refund_id INT NOT NULL,
  admin_note TEXT,
  date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (refund_id) REFERENCES refund_requests(refund_id) ON DELETE CASCADE
);

-- Table: Financial Reports
CREATE TABLE financial_reports (
  report_id INT AUTO_INCREMENT PRIMARY KEY,
  report_type ENUM('Monthly', 'Annual') NOT NULL,
  period_from DATE,
  period_to DATE,
  total_collected DECIMAL(12,2) DEFAULT 0.00,
  total_due DECIMAL(12,2) DEFAULT 0.00,
  total_scholarships DECIMAL(12,2) DEFAULT 0.00,
  total_refunds DECIMAL(12,2) DEFAULT 0.00,
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


-- Insert into Users
INSERT INTO users (username, password_hash, role)
VALUES
('admin1', '$2y$10$PMLFgY7yxjpBFOSWpN1t9.Qz2KZQSRcGfY5fifnpHDZAXRuo8OuiG', 'admin'), -- Password: pw123
('user1', '$2y$10$PMLFgY7yxjpBFOSWpN1t9.Qz2KZQSRcGfY5fifnpHDZAXRuo8OuiG', 'user'); -- Password: pw123

-- Insert into Students
INSERT INTO students (student_number, first_name, last_name, course, year_level, status)
VALUES
('20230001', 'John', 'Doe', 'BS Computer Science', 3, 'Active'),
('20230002', 'Jane', 'Smith', 'BS Accountancy', 2, 'Active'),
('20230003', 'Alice', 'Johnson', 'BS Information Technology', 1, 'Inactive');

-- Insert into Student Fees
INSERT INTO student_fees (student_id, fee_type, amount, discount_amount)
VALUES
(1, 'Tuition', 50000.00, 5000.00),
(1, 'Miscellaneous', 10000.00, 0.00),
(2, 'Tuition', 45000.00, 10000.00),
(3, 'Laboratory', 5000.00, 0.00);

-- Insert into Fee Summaries
INSERT INTO fee_summaries (student_id, total_computed_fee, amount_paid, outstanding_balance)
VALUES
(1, 60000.00, 30000.00, 30000.00),
(2, 45000.00, 45000.00, 0.00),
(3, 5000.00, 2000.00, 3000.00);

-- Insert into Invoices
INSERT INTO invoices (student_id, invoice_number, status, total_amount)
VALUES
(1, 'INV-2023-0001', 'Partial', 60000.00),
(2, 'INV-2023-0002', 'Paid', 45000.00),
(3, 'INV-2023-0003', 'Unpaid', 5000.00);

-- Insert into Invoice Items
INSERT INTO invoice_items (invoice_id, description, amount)
VALUES
(1, 'Tuition Fee - Sem 1', 50000.00),
(1, 'Miscellaneous Fee', 10000.00),
(2, 'Tuition Fee - Full Payment', 45000.00),
(3, 'Laboratory Fee', 5000.00);

-- Insert into Scholarship Applications
INSERT INTO scholarship_applications (student_id, type, status)
VALUES
(1, 'Academic', 'Approved'),
(2, 'Financial Aid', 'Pending'),
(3, 'Athletic', 'Rejected');

-- Insert into Scholarship Records
INSERT INTO scholarship_records (student_id, type, amount, approval_status)
VALUES
(1, 'Academic Excellence', 10000.00, 'Approved');

-- Insert into Refund Requests
INSERT INTO refund_requests (student_id, amount_requested, reason, status)
VALUES
(2, 3000.00, 'Dropped subject refund', 'Pending');

-- Insert into Refund Notes
INSERT INTO refund_notes (refund_id, admin_note)
VALUES
(1, 'Request under review by finance office.');

-- Insert into Financial Reports
INSERT INTO financial_reports (report_type, period_from, period_to, total_collected, total_due, total_scholarships, total_refunds, generated_by)
VALUES
('Monthly', '2025-04-01', '2025-04-30', 120000.00, 5000.00, 10000.00, 3000.00, 'Admin');

-- Insert into Audit Trail
INSERT INTO audit_trail (user_id, action, entity, entity_id, description)
VALUES
(1, 'DELETE', 'student_fees', 1, 'Removed incorrect fee record'),
(1, 'UPDATE', 'invoices', 1, 'Updated payment status to Partial'),
(2, 'VIEw', 'scholarship_applications', 2, 'Reviewed scholarship application status');
