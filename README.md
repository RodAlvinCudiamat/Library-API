# **LIBRARY API WITH TOKEN AUTHENTICATION**
## **Contents**
1. [Introduction](##INTRODUCTION)
2. [Installation](#installation)
   - [Step 1](#installation-step-1)
4. [Usage](#usage)
5. [Contributing](#contributing)

## INTRODUCTION
Welcome to the Library API! This API provides a robust and secure way to manage a collection of books and users in a library system, allowing for the creation, reading, updating, and deletion (CRUD operations) of books and user data. It also integrates token-based authentication to ensure secure access to the system.
#### KEY FEATURES
##### 1. Token-Based Authentication
- Ensures secure access through token validation, preventing unauthorized usage.
- Simplifies session management for authenticated users.

##### 2. CRUD Operations
- **Create**: Add new books, user accounts, or library records.
- **Read**: Retrieve details of books, users, or lending history.
- **Update**: Modify existing records, such as book availability or user information.
- **Delete**: Remove outdated or incorrect entries securely.

##### 3. RESTful Architecture
- Clean, consistent endpoints adhering to RESTful principles for easy integration with other systems.

##### 4. Scalability and Extensibility
- Designed to accommodate future enhancements, such as advanced search, analytics, or integration with third-party systems.

## GETTING STARTED

### PREREQUISITES
- XAMPP
- SQLyog (or phpMyAdmin)
- JWT PHP Library
- Node.js
- Composer
- PHP (version 7.2 or higher)
- Slim Framework
- ThunderClient

1. **Clone the Repository**

   ```bash
   git clone https://github.com/github_username/library_4a.git
   cd /path/to/xampp/htdocs/library_4a

   ```

2. **Install Dependencies**

   - Use Composer to install PHP dependencies:

   ```bash
   composer install

   ```

3. **Set Up Database**

   - Open SQLyog or phpMyAdmin and create a new database called `library`.
   - Run the following SQL queries to create the required tables:

   ```sql
   CREATE TABLE users (
       userid INT(9) NOT NULL AUTO_INCREMENT,
       username CHAR(255) NOT NULL,
       password TEXT NOT NULL,
       PRIMARY KEY (userid)
   );

   CREATE TABLE authors (
       authorid INT(9) NOT NULL AUTO_INCREMENT,
       name CHAR(255) NOT NULL,
       PRIMARY KEY (authorid)
   );

   CREATE TABLE books (
       bookid INT(9) NOT NULL AUTO_INCREMENT,
       title CHAR(255) NOT NULL,
       PRIMARY KEY (bookid)
   );

   CREATE TABLE books_authors (
       collectid INT(9) NOT NULL AUTO_INCREMENT,
       bookid INT(9) NOT NULL,
       authorid INT(9) NOT NULL,
       PRIMARY KEY (collectid)
   );

   CREATE TABLE tokens (
       id INT(11) PRIMARY KEY,
       token VARCHAR(255) NOT NULL,
       used TINYINT(1)
   );
   ```

4. **Configure Database Connection**

   - Modify the connection details in the index.php file as specified :

   ```php
   <?php
   $servername = "localhost";
   $username = "root";
   $password = "password";
   $dbname = "library";
   ?>
   ```
