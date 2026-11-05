-- P31: Samarth Employee Records & Service Book
-- Import into rc_campus AFTER shared/shared_schema.sql.
-- All project-owned tables use the emp_ prefix.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS emp_service_book_entries;
DROP TABLE IF EXISTS emp_employees;
DROP TABLE IF EXISTS example_suggestions;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE emp_employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) NOT NULL UNIQUE,
    title ENUM('Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Prof.') NOT NULL DEFAULT 'Mr.',
    full_name VARCHAR(120) NOT NULL,
    pan VARCHAR(10) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    date_of_birth DATE NOT NULL,
    department VARCHAR(100) NOT NULL,
    designation VARCHAR(100) NOT NULL,
    joining_date DATE NOT NULL,
    employment_type ENUM('Teaching', 'Non-Teaching') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    INDEX idx_emp_department (department),
    INDEX idx_emp_designation (designation),
    INDEX idx_emp_name (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE emp_service_book_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    entry_type ENUM('Joining', 'Promotion', 'Transfer') NOT NULL,
    effective_date DATE NOT NULL,
    previous_designation VARCHAR(100) NULL,
    new_designation VARCHAR(100) NULL,
    previous_department VARCHAR(100) NULL,
    new_department VARCHAR(100) NULL,
    remarks VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp_service_date (employee_id, effective_date),
    CONSTRAINT fk_emp_service_employee
        FOREIGN KEY (employee_id)
        REFERENCES emp_employees(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fictional sample data for development and demonstration.
-- Do not use real employees' PAN or personal information.

INSERT INTO emp_employees
(employee_code, title, full_name, pan, email, date_of_birth,
 department, designation, joining_date, employment_type)
VALUES
('RC-T-0042', 'Dr.', 'Aarav Sharma', 'ABCPK1234D', 'aarav.sharma@example.com', '1982-04-12', 'Computer Science', 'Associate Professor', '2012-07-16', 'Teaching'),
('RC-T-0043', 'Prof.', 'Meera Verma', 'BCDPV2345E', 'meera.verma@example.com', '1978-09-21', 'Mathematics', 'Professor', '2008-08-01', 'Teaching'),
('RC-T-0044', 'Dr.', 'Rohan Gupta', 'CDEPG3456F', 'rohan.gupta@example.com', '1985-02-18', 'Physics', 'Assistant Professor', '2016-01-11', 'Teaching'),
('RC-T-0045', 'Ms.', 'Kavita Singh', 'DEFPS4567G', 'kavita.singh@example.com', '1988-11-06', 'English', 'Assistant Professor', '2018-07-02', 'Teaching'),
('RC-T-0046', 'Dr.', 'Neha Mishra', 'EFGPM5678H', 'neha.mishra@example.com', '1983-06-25', 'Chemistry', 'Associate Professor', '2013-02-04', 'Teaching'),
('RC-T-0047', 'Mr.', 'Vikram Das', 'FGHPD6789J', 'vikram.das@example.com', '1987-03-14', 'Administration', 'Office Assistant', '2015-09-07', 'Non-Teaching'),
('RC-T-0048', 'Mrs.', 'Pooja Yadav', 'GHJPY7890K', 'pooja.yadav@example.com', '1990-12-02', 'Accounts', 'Accounts Assistant', '2019-04-15', 'Non-Teaching'),
('RC-T-0049', 'Mr.', 'Sanjay Kumar', 'HJKPK8901L', 'sanjay.kumar@example.com', '1980-07-19', 'Library', 'Librarian', '2010-05-10', 'Non-Teaching'),
('RC-T-0050', 'Ms.', 'Anjali Rao', 'JKLPR9012M', 'anjali.rao@example.com', '1992-01-27', 'IT Services', 'Technical Assistant', '2021-08-09', 'Non-Teaching'),
('RC-T-0051', 'Dr.', 'Nitin Joshi', 'KLMPT0123N', 'nitin.joshi@example.com', '1984-10-09', 'Computer Science', 'Assistant Professor', '2014-07-21', 'Teaching');

-- At least one service-book entry for every sample employee.
INSERT INTO emp_service_book_entries
(employee_id, entry_type, effective_date, previous_designation,
 new_designation, previous_department, new_department, remarks)
VALUES
(1, 'Joining', '2012-07-16', NULL, 'Assistant Professor', NULL, 'Computer Science', 'Initial appointment'),
(1, 'Promotion', '2018-07-16', 'Assistant Professor', 'Associate Professor', 'Computer Science', 'Computer Science', 'Promotion recorded'),
(2, 'Joining', '2008-08-01', NULL, 'Assistant Professor', NULL, 'Mathematics', 'Initial appointment'),
(2, 'Promotion', '2017-08-01', 'Associate Professor', 'Professor', 'Mathematics', 'Mathematics', 'Promotion recorded'),
(3, 'Joining', '2016-01-11', NULL, 'Assistant Professor', NULL, 'Physics', 'Initial appointment'),
(4, 'Joining', '2018-07-02', NULL, 'Assistant Professor', NULL, 'English', 'Initial appointment'),
(5, 'Joining', '2013-02-04', NULL, 'Assistant Professor', NULL, 'Chemistry', 'Initial appointment'),
(6, 'Joining', '2015-09-07', NULL, 'Office Assistant', NULL, 'Administration', 'Initial appointment'),
(7, 'Joining', '2019-04-15', NULL, 'Accounts Assistant', NULL, 'Accounts', 'Initial appointment'),
(8, 'Joining', '2010-05-10', NULL, 'Librarian', NULL, 'Library', 'Initial appointment'),
(9, 'Joining', '2021-08-09', NULL, 'Technical Assistant', NULL, 'IT Services', 'Initial appointment'),
(10, 'Joining', '2014-07-21', NULL, 'Assistant Professor', NULL, 'Computer Science', 'Initial appointment'),
(9, 'Transfer', '2023-04-01', 'Technical Assistant', 'Technical Assistant', 'IT Services', 'Computer Science', 'Department transfer recorded');