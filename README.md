<h1 id="library-management-system">LIBRARY API WITH TOKEN AUTHENTICATION</h1>

---
## INTRODUCTION
Welcome to the Library API! This API provides a robust and secure way to manage a collection of books and users in a library system, allowing for the creation, reading, updating, and deletion (CRUD operations) of books and user data. It also integrates token-based authentication to ensure secure access to the system.
### KEY FEATURES
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
---
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
   git clone https://github.com/github_username/Library-API.git
   cd /path/to/xampp/htdocs/Library-API

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
   $password = "";
   $dbname = "library";
   ?>
   ```
Substitute these values with your actual database settings to establish a connection to the library database.

5. **Start XAMPP Server**

   - Make sure that both Apache and MySQL are active/running in the XAMPP control panel.

6. **Testing the Application**
   - You can now test the CRUD operations and authentication endpoints using API testing tools such as Postman or Thunder Client(default testing tool i used).


## Implementation

<h3> 1. User Endpoints</h3>

**a. User Registration** - creates a new user account using a hashed password and a unique username.
- **Endpoint:** `/user/register`
- **Method:** `POST`
- **Sample Payload:**

  ```json
  {
    "username": "enter username ",
    "password": "enter password"
  }
  ```

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "data": null
    }
    ```

  - **Failure:**

    ```json
    {
      "status": "fail",
      "data": {
        "title": "(Error Message Here)"
      }
    }
    ```

**b. User Authentication** - creates a JWT token for session management and authenticates a user.

- **Endpoint:** `/user/authenticate`
- **Method:** `POST`
- **Sample Payload:**

  ```json
  {
    "username": "existing username",
    "password": "existing Password"
  }
  ```

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "token": "copy jwtToken here",
      "data": null
    }
    ```

  - **Failure:**

    ```json
    {
      "status": "fail",
      "data": {
        "title": "Authentication Failed"
      }
    }
    ```

**c. Display Users** - obtains a list of every user in the system; a valid token is needed.

- **Endpoint:** `/users`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer <Enter the jwtToken that was generated by the users here>`

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "data": [
        {
          "userid": 1,
          "username": "username"
        }
      ]
    }
    ```

  - **Failure:** Token Already Used

    ```json
    {
      "status": "fail",
      "data": {
        "title": "Token has already been used"
      }
    }
    ```

  - **Failure:** Invalid or Expired Token

    ```json
    {
      "status": "fail",
      "data": {
        "title": "Invalid or expired token"
      }
    }
    ```

**d. Update User Information** - updates the user's password and/or username; a working token is needed.

- **Endpoint:** `/user/update/{id}`
- **Method:** `PUT`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`
- **Sample Payload:**

  ```json
  {
    "username": "updated Username",
    "password": "new Password"
  }
  ```

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "data": null
    }
    ```

  - **Failure:** A suitable error notice will appear if the new username is already taken, if there is nothing to update, or if the token is invalid, expired, or already used.
    
**e. Delete User** - removes the verified user's account from the database; a working token is needed.

- **Endpoint:** `/user/delete/{id}`
- **Method:** `DELETE`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`

"Just enter the id in the endpoint"

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "Token": "generated token",
      "data": null
    }
    ```

  - **Failure:** If the user doesn’t exist, or if the token is invalid, expired, or already used, an appropriate error message.


<h3>2. Author Endpoints</h3>

**a. Add Author** - register/add a new author to the database.

- **Endpoint:** `/author/add`
- **Method:** `POST`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`
- **Sample Payload:**

  ```json
  {
  "name" : "John"
  }
  ```

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "token": "generated token",
      "data": null
    }
    ```

  - **Failure:** An suitable error message will be returned if the token is invalid, expired, already used, the name is empty, or the author is already known.

**b. Display Author** - shows the database's list of authors.

- **Endpoint:** `/authors`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "token": "generated token",
      "data": null
    }
    ```

  - **Failure:** A suitable error message will be returned if the token has expired, has been used, or is invalid.

**c. Update Author** -updates the database with an author's information.

- **Endpoint:** `/author/update/{id}`
- **Method:** `PUT`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`
- **Sample Payload:**

  ```json
  {
  "name" : "James"
  }
  ```

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "token": "generated token",
      "data": null 
    }
    ```

  - **Failure:** An suitable error message will be returned if the token has already been used, is invalid or expired, the author ID is not present or cannot be located, or there are no fields to change.

**d. Delete Author** - Deletes an author from the database.

- **Endpoint:** `/author/delete/{id}`
- **Method:** `DELETE`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`
- **Sample Payload:**

 "Just enter the id in the endpoint"


- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "Token": "Generated token",
      "data": null
    }
    ```

  - **Failure:** If there are no fields to edit, the author ID is missing or not found, the token has already been used, or it is invalid or expired, the relevant error message will be displayed.


<h3>3. Book Endpoints</h3>

**a. Add Book** - Register/add a new book to the library.

- **Endpoint:** `/book/add`
- **Method:** `POST`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`
- **Sample Payload:**

  ```json
  {
  "title" : "Ang alamat ng JSON"
  }
  ```

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "Token": "generated token",
      "data": null
    }
    ```

  - **Failure:** An appropriate error message will be returned if the token is invalid, expired, already used, the title is empty, or the book already exists.

**b. Display Books** - presents a database list of books.

- **Endpoint:** `/books`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "Token": "generated token",
      "data": [
        {
          "bookid": 1,
          "title": "Book Title"
        }
      ]
    }
    ```

  - **Failure:** The relevant error message will be displayed if the token has already been used, is invalid, or has expired.

**c. Update Book** - updates the database's information on a book.

- **Endpoint:** `/book/update/{id}`
- **Method:** `PUT`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`
- **Sample Payload:**

  ```json
  {
  "title" : "Ang alamat ni Meng"
  }
  ```

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "token": "generated token",
      "data": null
    }
    ```

  - **Failure:** A suitable error message will be supplied if the token has already been used, is invalid or expired, the book ID is missing or cannot be located, or there are no fields to change.

**d. Delete Book** - removing a book from the database..

- **Endpoint:** `/book/delete/{id}`
- **Method:** `DELETE`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`
- **Sample Payload:**

 "Just enter the id in the endpoint"

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "token": "generated token",
      "data": null
    }
    ```

  - **Failure:** A suitable error message will be supplied if the token has already been used, is invalid or expired, or if the book ID cannot be located.


<h3>4. Book-Author Relationship Endpoints</h3>

**a. Add Book-Author** - creates a new connection between a book and its author.

- **Endpoint:** `/books_authors/add`
- **Method:** `POST`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`
- **Sample Payload:**

  ```json
  {
  "bookid" : 2,
  "authorid" : 1
  }
  ```

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "token" : "generated token",
      "data": null
    }
    ```

  - **Failure:** The response will specify the precise error if the token has already been used, is invalid or expired, or if necessary fields (book ID or author ID) are absent.

**b. Display All Book-Author** - shows every book-author relationship in the database along with the ID that corresponds to it.

- **Endpoint:** `/books_authors`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "token": "generated token",
      "data": [
        {
          "collectionid": 4,
          "bookid": 3,
          "authorid": 3
        }
      ]
    }
    ```

  - **Failure:** The response will specify the precise error if the token has already been used, is invalid or expired, or there is a database problem.

**c. Update Book-Author** - modifies the book and/or author ID to update an existing book-author association.

- **Endpoint:** `/books_authors/update/{id}`
- **Method:** `PUT`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`
- **Sample Payload:**

  ```json
  {
  "bookid" : 1,
  "authorid" : 1
  }
  ```

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "token": "generated token",
      "data": null
    }
    ```

  - **Failure:** The answer will specify the precise error if the token has already been used, is invalid or expired, the collection ID is missing or not discovered, or no fields are met to update.

**d. Delete Book-Author** - removes a specific book-author relationship.

- **Endpoint:** `/books_authors/delete/{id}`
- **Method:** `DELETE`
- **Headers:** `Authorization: Bearer <insert generated jwtTokenHere from the users/authenticate>`
- **Sample Payload:**

  "Just enter the id in the endpoint"

- **Expected Response:**

  - **Success:**

    ```json
    {
      "status": "success",
      "token": "generated token",
      "data": null
    }
    ```

  - **Failure:** The response will specify the precise error if the token has already been used, is invalid or expired, the collection ID is missing, or there is no association for the provided ID.

## Token Management

**Validate Token** 

```php
// Check if the token has been used
        $conn = new PDO("mysql:host=localhost;dbname=library", "root", "");
        $stmt = $conn->prepare("SELECT used FROM tokens WHERE token = :token");
        $stmt->execute(['token' => $token]);
        $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tokenRecord && $tokenRecord['used']) {
            return $response->withJson(["status" => "fail", "data" => ["title" => "Token has already been used"]], 403);
        }

        // Mark token as used
        if ($tokenRecord) {
            $stmt = $conn->prepare("UPDATE tokens SET used = TRUE WHERE token = :token");
            $stmt->execute(['token' => $token]);
        }
```
## Project Information

This project is developed as part of a midterm requirement for the ITPC 115(System Integration and Architecture) subject, showcasing the ability to build secure API endpoints and manage tokens effectively.

## Contact Information

If you need assistance or have any questions, feel free to reach out to me. Below are my contact details:

- **Name:** Rod Alvin Cudiamat
- **University:** Don Mariano Marcos Memorial State University (Mid-La Union Campus)
- **Email:** rcudiamat21482@student.dmmmsu.edu.ph
- **Phone:** 09993475665
<p align="left">(<a href="#library-management-system">back to top</a>)</p>