Samarth P31 – Employee Records & Service Book

GE-5b Advanced Web Programming · Ramanujan College, University of Delhi · 2026–27

Project Information

Field| Details
Project ID| P31
Project Title| Employee Records & Service Book
Student Name| Priyanshu Kumar
University Roll Number| 24020107050
Course| B.Voc. Software Development
Technology| PHP, MySQL, HTML, CSS, JavaScript, jQuery

1. Project Overview

The Employee Records & Service Book module is developed as part of RC Campus Connect, a college ERP system modelled on the Samarth eGov ERP.

The module provides a centralized system for maintaining teaching and non-teaching employee records and tracking important service-related events throughout an employee's career.

It is designed to support administrative operations while allowing authorized employees to view their own records.

2. Main Features

- Employee Management: Add, view, update and delete employee records.
- Employee Search: Search employees by employee code, name and other available details.
- Filters: Filter employee records by department and designation.
- Service Book: Record joining, promotion and transfer entries.
- Service History: View employee service-book history in chronological order.
- AJAX Live Search: Retrieve employee suggestions without reloading the page.
- Input Validation: Validate employee codes, PAN, email, dates and other required fields.
- Employee Profile: Provide a personal profile page for mapped employees.
- Role-Based Access: Restrict administrative operations to authorized administrators.

3. Technology Stack

Technology| Purpose
PHP| Server-side application logic
MySQL| Database management
HTML5| Page structure
CSS3| Styling and layout
JavaScript| Client-side functionality
jQuery| DOM manipulation and AJAX
XAMPP| Local development environment
Git & GitHub| Version control and repository management

4. Database Design

The module uses its own prefixed database tables to avoid conflicts with other modules.

Main tables:

- "emp_employees" – Stores employee master records.
- "emp_service_book_entries" – Stores joining, promotion and transfer history.

The module also uses the shared RC Campus Connect database and its authentication infrastructure.

Database file: "database.sql"

5. Security and Validation

The module incorporates the following security practices:

- Prepared SQL statements using MySQLi.
- HTML output escaping through the shared "e()" helper.
- CSRF protection for POST operations.
- Server-side validation of submitted employee information.
- Role-based access restrictions.
- Employee self-view restrictions for mapped accounts.
- Audit logging for important administrative operations.

6. Project Structure

P31-Employee-Records/
│
├── api/
│   └── search.php
│
├── css/
│   └── module.css
│
├── includes/
│   └── functions.php
│
├── js/
│   └── app.js
│
├── tools/
│   └── check_module.php
│
├── add.php
├── edit.php
├── delete.php
├── index.php
├── view.php
├── service_book.php
├── my_profile.php
├── map_employee.php
├── database.sql
├── module.json
├── boot.php
└── README.md

The shared portal files and configuration are maintained according to the college project guidelines.

7. Local Installation

Requirements

- XAMPP with PHP 8.x
- Apache
- MySQL
- Web browser

Setup Instructions

1. Place the project folder inside the XAMPP "htdocs" directory.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open "http://localhost/phpmyadmin".
4. Create a database named "rc_campus" with collation "utf8mb4_unicode_ci".
5. Import "shared/shared_schema.sql" first.
6. Import the module's "database.sql".
7. Configure local database credentials in "config.php", if required.
8. Open the module in your browser:

http://localhost/p31/p31-24020107050-main/

Use the local folder name in the URL if it differs.

8. Testing and Quality Checks

The project has undergone functional and validation testing, including:

- Employee creation and editing.
- Employee search and filtering.
- Service-book entry creation and history display.
- PAN and email validation.
- Future-date validation.
- Employee deletion.
- AJAX employee search.
- Access restriction for unauthorized users.

The repository also uses GitHub Actions for automatic module checks, including PHP syntax validation.

9. Future Scope

Possible future improvements include:

- Employee self-service profile access after verified account mapping.
- Additional employee reports and export options.
- Advanced search and reporting.
- More detailed role-based permissions.
- Improved administrative dashboards.

10. Conclusion

The Samarth P31 Employee Records & Service Book module aims to simplify employee record management and service-history tracking through a centralized web-based application.

It demonstrates the practical application of PHP, MySQL, jQuery, database operations, form validation and web application security as part of the RC Campus Connect ERP project.

---

Developed by: Priyanshu Kumar
Roll Number: 24020107050
Institution: Ramanujan College, University of Delhi
Academic Session: 2026–27