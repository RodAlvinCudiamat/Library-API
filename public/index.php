<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require '../src/vendor/autoload.php';

$config = [
    'settings' => [
        'displayErrorDetails' => true, // Enable error details
    ],
];

$app = new \Slim\App($config);  

//endpoint for user register
$app->post('/user/register', function (Request $request, Response $response,
array $args){
    $data=json_decode($request->getBody());
    $usr=$data->username;
    $pass=$data->password;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "INSERT INTO users(username,password)
    VALUES ('".$usr."', '". hash('SHA256',$pass)."')";
    // use exec() because no results are returned
    $conn->exec($sql);
    $response->getBody()->write(json_encode(array("status"=>"success","data"=>null)));
    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail",
        "data"=>array("title"=>$e->getMessage()))));
    }

    $conn = null;

    
return $response;
});

//user authentication
$app->post('/user/authenticate',function(Request $request, Response $response,
array $args) {
    $data=json_decode($request->getBody());
    $usr=$data->username;
    $pass=$data->password;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username,$password);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM users WHERE username='". $usr ."' 
        AND password='".hash('SHA256',$pass)."'";
    // use exec() because no results are returned
    $stmt=$conn->prepare($sql);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $data=$stmt->fetchAll();

    if (count($data) == 1) {
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            "data" => array(
                "userid" => $data[0]['userid']
            )
        ];
    
        $jwt = JWT::encode($payload, $key, 'HS256');
    
        // Store the token in the database
        $sql = "INSERT INTO tokens (token, used) VALUES (:token, FALSE)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['token' => $jwt]);
    
        $response->getBody()->write(json_encode(array("status" => "success", "token" => $jwt, "data" => null)));
    }
    else{
        $response->getBody()->write(json_encode(array("status"=>"fail",
        "data"=>array("title"=>"Authentication Failed"))));
    }

    }catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status"=>"fail",
    "data"=>array("title"=>$e->getMessage()))));
    }

    return $response;
});

$TokenMiddleware = function (Request $request, Response $response, $next) {
    $token = $request->getHeaderLine('Authorization');

    if (!$token) {
        return $response->withJson(["status" => "fail", "data" => ["title" => "Token not provided"]], 401);
    }

    $token = str_replace('Bearer ', '', $token); 

    try {
        $key = 'server_hack';
        $decoded = JWT::decode($token, new Key($key, 'HS256'));

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

        

    } catch (Exception $e) {
        error_log("Token decoding error: " . $e->getMessage());
        return $response->withJson(["status" => "fail", "data" => ["title" => "Invalid Token: " . $e->getMessage()]], 401);
    }

    return $next($request, $response);
};



// Update User
$app->put('/user/update/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id']; // Get the user ID from the URL
    $data = json_decode($request->getBody());

    // Validate input data
    if (!isset($data->username) || !isset($data->password)) {
        return $response->withJson(["status" => "fail", "data" => ["title" => "Invalid input data"]], 400);
    }

    $usr = $data->username;
    $pass = $data->password;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare the SQL statement to update the user
        $sql = "UPDATE users SET username = :username, password = :password WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'username' => $usr,
            'password' => password_hash($pass, PASSWORD_DEFAULT), // Use password_hash for security
            'userid' => $id
        ]);

        // Generate a new token
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            "data" => array(
                "userid" => $id // Include the updated user's ID
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data with data set to null
        $responseData = [
            "status" => "success",
            "data" => null, // Set data to null as per your requirement
            "token" => $jwt
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Delete User
$app->delete('/user/delete/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id']; // Get the user ID from the URL

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare the SQL statement to delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE userid = :userid");
        $stmt->execute(['userid' => $id]);

        // Generate a new token after deletion
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                "userid" => $id, // Include the deleted user's ID
                "message" => "User  deleted successfully"
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => null,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Display Users
$app->get('/users', function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch Users
        $stmt = $conn->prepare("SELECT * FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate a new token after fetching users
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                "message" => "Users fetched successfully",
                "user_count" => count($users) // Include the number of users fetched
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => $users,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Add Author
$app->post('/author/add', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $name = $data->name;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL statement to insert a new author
        $stmt = $conn->prepare("INSERT INTO authors (name) VALUES (:name)");
        $stmt->execute(['name' => $name]);

        // Generate a new token after adding the author
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                
                "message" => "Author added successfully",
                "author_name" => $name // Include the name of the added author
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => null,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 201);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Update Author
$app->put('/author/update/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $data = json_decode($request->getBody());
    $name = $data->name;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL statement to update the author's name
        $stmt = $conn->prepare("UPDATE authors SET name = :name WHERE authorid = :authorid");
        $stmt->execute(['name' => $name, 'authorid' => $id]);

        // Generate a new token after updating the author
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                "message" => "Author updated successfully",
                "author_name" => $name, // Include the updated author's name
                "author_id" => $id // Include the ID of the updated author
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => null,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Delete Author
$app->delete('/author/delete/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL statement to delete the author
        $stmt = $conn->prepare("DELETE FROM authors WHERE authorid = :authorid");
        $stmt->execute(['authorid' => $id]);

        // Generate a new token after deleting the author
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                
                "message" => "Author deleted successfully",
                "author_id" => $id // Include the ID of the deleted author
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => null,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Display Authors
$app->get('/authors', function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch Authors
        $stmt = $conn->prepare("SELECT * FROM authors");
        $stmt->execute();
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate a new token after fetching authors
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                
                "message" => "Authors fetched successfully",
                "authors_count" => count($authors) // Include the number of authors fetched
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => $authors,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Add Book
$app->post('/book/add', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $title = $data->title;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL statement to insert a new book
        $stmt = $conn->prepare("INSERT INTO books (title) VALUES (:title)");
        $stmt->execute(['title' => $title]);

        // Generate a new token after adding the book
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                
                "message" => "Book added successfully",
                "book_title" => $title // Include the title of the added book
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => null,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 201);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Update Book
$app->put('/book/update/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $data = json_decode($request->getBody());
    $title = $data->title;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL statement to update the book title
        $stmt = $conn->prepare("UPDATE books SET title = :title WHERE bookid = :bookid");
        $stmt->execute(['title' => $title, 'bookid' => $id]);

        // Generate a new token after updating the book
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                
                "message" => "Book updated successfully",
                "book_id" => $id, // Include the ID of the updated book
                "book_title" => $title // Include the new title of the book
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => null,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Delete Book
$app->delete('/book/delete/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL statement to delete the book
        $stmt = $conn->prepare("DELETE FROM books WHERE bookid = :bookid");
        $stmt->execute(['bookid' => $id]);

        // Generate a new token after deleting the book
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                
                "message" => "Book deleted successfully",
                "book_id" => $id // Include the ID of the deleted book
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => null,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Display Books
$app->get('/books', function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch Books
        $stmt = $conn->prepare("SELECT * FROM books");
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate a new token after fetching books
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
               
                "message" => "Books retrieved successfully",
                "book_count" => count($books) // Include the count of retrieved books
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => $books,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Add Book Author Association
$app->post('/books_authors/add', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $bookid = $data->bookid;
    $authorid = $data->authorid;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL statement to insert the book-author relationship
        $stmt = $conn->prepare("INSERT INTO books_authors (bookid, authorid) VALUES (:bookid, :authorid)");
        $stmt->execute(['bookid' => $bookid, 'authorid' => $authorid]);

        // Generate a new token after adding the book-author relationship
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                
                "message" => "Book-Author relationship added successfully",
                "book_id" => $bookid,
                "author_id" => $authorid
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => null,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 201);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Update Book Author Association
$app->put('/books_authors/update/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $data = json_decode($request->getBody());
    $bookid = $data->bookid;
    $authorid = $data->authorid;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL statement to update the book-author relationship
        $stmt = $conn->prepare("UPDATE books_authors SET bookid = :bookid, authorid = :authorid WHERE collectid = :collectid");
        $stmt->execute(['bookid' => $bookid, 'authorid' => $authorid, 'collectid' => $id]);

        // Generate a new token after updating the book-author relationship
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                
                "message" => "Book-Author relationship updated successfully",
                "book_id" => $bookid,
                "author_id" => $authorid
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => null,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);

// Delete Book Author Association
$app->delete('/books_authors/delete/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare and execute the SQL statement to delete the book-author relationship
        $stmt = $conn->prepare("DELETE FROM books_authors WHERE collectid = :collectid");
        $stmt->execute(['collectid' => $id]);

        // Generate a new token after deleting the book-author relationship
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                
                "message" => "Book-Author relationship deleted successfully",
                "deleted_id" => $id
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => null,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);
// Display Book Author Association
$app->get('/books_authors', function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch Book Author Associations
        $stmt = $conn->prepare("SELECT * FROM books_authors");
        $stmt->execute();
        $associations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate a new token after fetching the data
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600, // Token valid for 1 hour
            "data" => array(
                
                "message" => "Fetched book-author associations successfully",
                "count" => count($associations) // Optional: include the count of associations
            )
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        // Prepare the response data
        $responseData = [
            "status" => "success",
            "data" => $associations,
            "token" => $jwt // Include the new token in the response
        ];

        return $response->withJson($responseData, 200);
    } catch (PDOException $e) {
        return $response->withJson(["status" => "fail", "data" => ["title" => $e->getMessage()]], 500);
    } finally {
        $conn = null;
    }
})->add($TokenMiddleware);



$app->run();
?>


