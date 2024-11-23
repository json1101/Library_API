<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require '../src/vendor/autoload.php';

session_start();
$app = new \Slim\App();

// JWT middleware for token validation
$jwtMiddleware = function (Request $request, Response $response, callable $next) {
    $authHeader = $request->getHeader('Authorization');

    if ($authHeader) {
        $token = str_replace('Bearer ', '', $authHeader[0]);

        // Check if token has been used
        if (isset($_SESSION['used_tokens']) && in_array($token, $_SESSION['used_tokens'])) {
            return $response->withStatus(401)->write(json_encode(array("status" => "fail", "data" => array("title" => "Token has already been used"))));
        }

        try {
            $decoded = JWT::decode($token, new Key('server_hack', 'HS256'));
            $request = $request->withAttribute('decoded', $decoded);
            $_SESSION['used_tokens'][] = $token; // Revoke the token after using it
        } catch (\Exception $e) {
            return $response->withStatus(401)->write(json_encode(array("status" => "fail", "data" => array("title" => "Unauthorized: " . $e->getMessage()))));
        }
    } else {
        return $response->withStatus(401)->write(json_encode(array("status" => "fail", "data" => array("title" => "Token not provided"))));
    }

    return $next($request, $response);
};

// User authentication or Login
$app->post('/user/authenticate', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $usr = $data->username;
    $pass = $data->password;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM users WHERE username = :username AND password = :password";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':username' => $usr,
            ':password' => hash('SHA256', $pass)
        ]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $key = 'server_hack';
            $iat = time();
            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $iat,
                'exp' => $iat + 3600,
                'data' => array("userid" => $data['userid'])
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');
            $response->getBody()->write(json_encode(array("status" => "success", "token" => $jwt, "data" => null)));
        } else {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => "Authentication Failed!"))));
        }
    } catch(PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }

    return $response;
});

// CREATE NEW USER
$app->post('/user/register', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());

    $usr = trim($data->username);
    $pass = trim($data->password);

    // Validation: Check if username and password are provided and are at least 5 characters long
    if (empty($usr) || empty($pass) || strlen($usr) < 5 || strlen($pass) < 5) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Username and password must be at least 5 characters long and not empty")));
        return $response->withStatus(400);
    }

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if username already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $usr]);

        if ($stmt->rowCount() > 0) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Username already exists")));
            return $response->withStatus(400);
        }

        // Insert new user
        $sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':username' => $usr, ':password' => hash('SHA256', $pass)]);

        $response->getBody()->write(json_encode(array("status" => "success", "data" => null)));

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
    return $response;
});

// VIEW USER
$app->get('/user/read', function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tokenUserId = $request->getAttribute('decoded')->data->userid;

        $stmt = $conn->prepare("SELECT userid, username FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($users) > 0) {
            $key = 'server_hack';
            $iat = time();
            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $iat,
                'exp' => $iat + 3600, 
                'data' => array("userid" => $tokenUserId)
            ];
            $new_jwt = JWT::encode($payload, $key, 'HS256');

            return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => $users)));
        } else {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "No users found")));
        }

    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);

// UPDATE A USER
$app->put('/user/update', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $userId = trim($data->userid); // Get the user ID from the payload
    $newUsername = trim($data->username);
    $newPassword = trim($data->password);

    // Validation: Check if new username and password are provided and are at least 5 characters long
    if (empty($newUsername) || empty($newPassword) || strlen($newUsername) < 5 || strlen($newPassword) < 5) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Username and password must be at least 5 characters long and not empty")));
        return $response->withStatus(400);
    }

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the user exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE userid = :userid");
        $checkStmt->execute([':userid' => $userId]);
        $userExists = $checkStmt->fetchColumn();

        if (!$userExists) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "User with ID $userId does not exist.")));
        }

        // Proceed with updating the user
        $hashedPassword = hash('SHA256', $newPassword);
        $sql = "UPDATE users SET username = :username, password = :password WHERE userid = :userid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':username' => $newUsername,
            ':password' => $hashedPassword,
            ':userid' => $userId
        ]);

        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("userid" => $userId)
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');

        return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => "User updated successfully")));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);

// DELETE A USER
$app->delete('/user/delete', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $userId = trim($data->userid); // Get the user ID from the payload

    // Validation: Check if user ID is provided
    if (empty($userId)) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "User ID must be provided.")));
    }

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the user exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE userid = :userid");
        $checkStmt->execute([':userid' => $userId]);
        $userExists = $checkStmt->fetchColumn();

        if (!$userExists) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "User with ID $userId does not exist.")));
        }

        // Proceed with deletion if the user exists
        $stmt = $conn->prepare("DELETE FROM users WHERE userid = :userid");
        $stmt->execute([':userid' => $userId]);

        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("userid" => $userId)
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');

        return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => "User deleted successfully")));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);








// Create a new author
$app->post('/authors/create', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $name = trim($data->name);

    // Validate input
    if (empty($name) || strlen($name) <= 4) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Author name must be more than 4 characters and cannot be blank.")));
    }

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("INSERT INTO authors (name) VALUES (:name)");
        $stmt->execute([':name' => $name]);

        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("name" => $name)
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');

        return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => "Author created successfully")));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);

// Update author information
$app->put('/authors/update', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $authorId = trim($data->authorid); // Get the author ID from the payload
    $newName = trim($data->name);

    // Validate input
    if (empty($newName) || strlen($newName) <= 4) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Author name must be more than 4 characters and cannot be blank.")));
    }

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the author exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM authors WHERE authorid = :authorid");
        $checkStmt->execute([':authorid' => $authorId]);
        $authorExists = $checkStmt->fetchColumn();

        if (!$authorExists) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Author with ID $authorId does not exist.")));
        }

        // Proceed with the update if the author exists
        $stmt = $conn->prepare("UPDATE authors SET name = :name WHERE authorid = :authorid");
        $stmt->execute([':name' => $newName, ':authorid' => $authorId]);

        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("authorid" => $authorId)
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');

        return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => "Author updated successfully")));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);

// Get all authors
$app->get('/authors/read', function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT authorid, name FROM authors");
        $stmt->execute();
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($authors) > 0) {
            $key = 'server_hack';
            $iat = time();
            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $iat,
                'exp' => $iat + 3600,
                'data' => array("authorCount" => count($authors))
            ];
            $new_jwt = JWT::encode($payload, $key, 'HS256');

            return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => $authors)));
        } else {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "No authors found")));
        }
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);

// Delete an author
$app->delete('/authors/delete', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $authorId = trim($data->authorid); // Get the author ID from the payload

    // Validation: Check if author ID is provided
    if (empty($authorId)) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Author ID must be provided.")));
    }

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the author exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM authors WHERE authorid = :authorid");
        $checkStmt->execute([':authorid' => $authorId]);
        $authorExists = $checkStmt->fetchColumn();

        if (!$authorExists) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Author with ID $authorId does not exist.")));
        }

        // Proceed with the deletion if the author exists
        $stmt = $conn->prepare("DELETE FROM authors WHERE authorid = :authorid");
        $stmt->execute([':authorid' => $authorId]);

        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("authorid" => $authorId)
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');

        return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => "Author deleted successfully")));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);





// Create a new book
$app->post('/books/create', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $title = trim($data->title);
    $authorId = trim($data->authorid);

    // Validate input
    if (empty($title) || strlen($title) <= 4) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Book title must be more than 4 characters and cannot be blank.")));
    }

    // Database connection setup
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the author exists
        $stmt = $conn->prepare("SELECT * FROM authors WHERE authorid = :authorid");
        $stmt->execute([':authorid' => $authorId]);

        if ($stmt->rowCount() === 0) {
            // If author does not exist, return an error
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Author not found")));
        }

        // Proceed with inserting the new book
        $stmt = $conn->prepare("INSERT INTO books (title, authorid) VALUES (:title, :authorid)");
        $stmt->execute([':title' => $title, ':authorid' => $authorId]);

        // Generate a new token
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("title" => $title, "authorid" => $authorId)
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');

        return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => "Book created successfully")));

    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);

// Get all books
$app->get('/books/read', function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch all books along with their authors
        $stmt = $conn->prepare("SELECT b.bookid, b.title, b.authorid, a.name AS author_name FROM books b LEFT JOIN authors a ON b.authorid = a.authorid");
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($books) > 0) {
            // Generate a new token
            $key = 'server_hack';
            $iat = time();
            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $iat,
                'exp' => $iat + 3600,
                'data' => array("bookCount" => count($books))
            ];
            $new_jwt = JWT::encode($payload, $key, 'HS256');

            return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => $books)));
        } else {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "No books found")));
        }
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);

// Update book information
$app->put('/books/update', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $bookId = trim($data->bookid);
    $newTitle = trim($data->title);
    $newAuthorId = trim($data->authorid);

    // Validate input
    if (empty($newTitle) || strlen($newTitle) <= 4) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Book title must be more than 4 characters and cannot be blank.")));
    }

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the book exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM books WHERE bookid = :bookid");
        $checkStmt->execute([':bookid' => $bookId]);
        $bookExists = $checkStmt->fetchColumn();

        if (!$bookExists) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Book with ID $bookId does not exist.")));
        }

        // Check if the author exists
        $authorCheckStmt = $conn->prepare("SELECT * FROM authors WHERE authorid = :authorid");
        $authorCheckStmt->execute([':authorid' => $newAuthorId]);

        if ($authorCheckStmt->rowCount() === 0) {
            // If author does not exist, return an error
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Author not found")));
        }

        // Proceed with the update if the author exists
        $stmt = $conn->prepare("UPDATE books SET title = :title, authorid = :authorid WHERE bookid = :bookid");
        $stmt->execute([':title' => $newTitle, ':authorid' => $newAuthorId, ':bookid' => $bookId]);

        // Generate a new token
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("bookid" => $bookId)
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');

        return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => "Book updated successfully")));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);

// Delete a book
$app->delete('/books/delete', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $bookId = trim($data->bookid);

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the book exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM books WHERE bookid = :bookid");
        $checkStmt->execute([':bookid' => $bookId]);
        $bookExists = $checkStmt->fetchColumn();

        if (!$bookExists) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Book with ID $bookId does not exist.")));
        }

        // Proceed with the deletion if the book exists
        $stmt = $conn->prepare("DELETE FROM books WHERE bookid = :bookid");
        $stmt->execute([':bookid' => $bookId]);

        // Generate a new token
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("bookid" => $bookId)
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');

        return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => "Book deleted successfully")));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);









// Create a new book-author relationship
$app->post('/book_authors/create', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $bookId = $data->bookid;
    $authorId = $data->authorid;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the book exists
        $bookStmt = $conn->prepare("SELECT COUNT(*) FROM books WHERE bookid = :bookid");
        $bookStmt->execute([':bookid' => $bookId]);
        $bookExists = $bookStmt->fetchColumn();

        // Check if the author exists
        $authorStmt = $conn->prepare("SELECT COUNT(*) FROM authors WHERE authorid = :authorid");
        $authorStmt->execute([':authorid' => $authorId]);
        $authorExists = $authorStmt->fetchColumn();

        if (!$bookExists) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Book does not exist")));
        }

        if (!$authorExists) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Author does not exist")));
        }

        // Insert book-author relationship
        $stmt = $conn->prepare("INSERT INTO book_authors (bookid, authorid) VALUES (:bookid, :authorid)");
        $stmt->execute([':bookid' => $bookId, ':authorid' => $authorId]);

        // Generate a new token
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("bookid" => $bookId, "authorid" => $authorId)
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');

        return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => "Book-author relationship created successfully")));
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            // Foreign key constraint violation
            return $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Foreign key constraint violation", "details" => $e->getMessage())
            )));
        } else {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
        }
    }
})->add($jwtMiddleware);

// Get all book-author relationships
$app->get('/book_authors/read', function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch all book-author relationships along with book and author details
        $stmt = $conn->prepare("
            SELECT ba.collectionid, ba.bookid, ba.authorid, b.title AS book_title, a.name AS author_name 
            FROM book_authors ba 
            LEFT JOIN books b ON ba.bookid = b.bookid 
            LEFT JOIN authors a ON ba.authorid = a.authorid
        ");
        $stmt->execute();
        $bookAuthors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($bookAuthors) > 0) {
            // Generate a new token
            $key = 'server_hack';
            $iat = time();
            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $iat,
                'exp' => $iat + 3600,
                'data' => array("bookAuthorCount" => count($bookAuthors))
            ];
            $new_jwt = JWT::encode($payload, $key, 'HS256');

            return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => $bookAuthors)));
        } else {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "No book-author relationships found")));
        }
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);

// Update book-author relationship
$app->put('/book_authors/update', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $collectionId = $data->collectionid;
    $newBookId = $data->bookid;
    $newAuthorId = $data->authorid;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the book exists
        $bookStmt = $conn->prepare("SELECT COUNT(*) FROM books WHERE bookid = :bookid");
        $bookStmt->execute([':bookid' => $newBookId]);
        $bookExists = $bookStmt->fetchColumn();

        // Check if the author exists
        $authorStmt = $conn->prepare("SELECT COUNT(*) FROM authors WHERE authorid = :authorid");
        $authorStmt->execute([':authorid' => $newAuthorId]);
        $authorExists = $authorStmt->fetchColumn();

        if (!$bookExists) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Book does not exist")));
        }

        if (!$authorExists) {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => "Author does not exist")));
        }

        // Update book-author relationship
        $stmt = $conn->prepare("UPDATE book_authors SET bookid = :bookid, authorid = :authorid WHERE collectionid = :collectionid");
        $stmt->execute([':bookid' => $newBookId, ':authorid' => $newAuthorId, ':collectionid' => $collectionId]);

        // Generate a new token
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("collectionid" => $collectionId)
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');

        return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => "Book-author relationship updated successfully")));
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            // Foreign key constraint violation
            return $response->getBody()->write(json_encode(array(
                "status" => "fail",
                "data" => array("title" => "Foreign key constraint violation", "details" => $e->getMessage())
            )));
        } else {
            return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
        }
    }
})->add($jwtMiddleware);

// Delete a book-author relationship
$app->delete('/book_authors/delete', function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());
    $collectionId = $data->collectionid;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("DELETE FROM book_authors WHERE collectionid = :collectionid");
        $stmt->execute([':collectionid' => $collectionId]);

        // Generate a new token
        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("collectionid" => $collectionId)
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');

        return $response->getBody()->write(json_encode(array("status" => "success", "token" => $new_jwt, "data" => "Book-author relationship deleted successfully")));
    } catch (PDOException $e) {
        return $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
    }
})->add($jwtMiddleware);



$app->run();
?>
