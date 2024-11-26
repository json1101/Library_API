# Library API Documentation

## Features

- **Slim Framework Integration**: Provides a lightweight, flexible routing system.
- **JWT Authentication**: Implements secure JSON Web Token (JWT) authentication using Firebase's JWT library.
- **Session Management**: Ensures security by tracking and invalidating used tokens.
- **RESTful API Responses**: Returns structured JSON responses for errors and success.

## User Endpoints
  ## User Register
  - **Method**: `POST`
  - **EndPoint**: `/user/register`
- **Payload**:
  ```json
  {
  "username": "your_username",
  "password": "your_password"
  }
  ```

- **Succesful Response**
  ```json
  {
  "status": "success",
  "data": null
  }
  ```

- **Failed Response (Duplicated Username)**
  ```json
  {
  "status": "fail",
  "data": "Username already exists"
  }
  ```
- **Failed Response (Invalid input)**
  ```json
  {
  "status": "fail",
  "data": "Username already exists"
  }
  ```

## User Authenticate
  - **Method**: `POST`
  - **EndPoint**: `/user/authenticate`
- **Payload**:
  ```json
  {
  "username": "your_username",
  "password": "your_password"
  }
  ```

- **Succesful Response**
  ```json
  {
  "status": "success",
  "token":<TOKEN>
  "data": null
  }
  ```

- **Failed Response (Failed Authentication)**
  ```json
  {
  "status": "fail",
  "data": {
    "title": "Authentication Failed!"
  }
  ```
## User Read
  - **Method**: `GET`
  - **EndPoint**: `/user/read`
  -  **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
- **Payload**: no request needed.
- **Succesful Response**
  ```json
  {
  "status": "success",
  "token": <NEW_TOKEN>,
  "data": [
    {
      "userid": 1,
      "username": "your_username"
    },
    {
      "userid": 2,
      "username": "Jayson Paul"
    }
  ```

- **Failed Response (Failed Authentication)**
  ```json
  {
    "status": "fail",
    "data": "Unauthorized: Token not provided"
  }
  ```
## User Update
- **Method**: `PUT`
- **Endpoint**: `/user/update`
-  **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
-  **Payload**
  ```json
 {
  "userid":"1",
   "username": "new_username",
  "password": "new_password"
 }
 ```
- **Successful Response**
  ```json
  {
    "status": "success",
    "token": "<NEW_TOKEN>",
    "data": "User updated successfully"
  }
  ```
- **Failed Response (User not Found)**
  ```json
  {
    "status": "fail",
    "data": "User with ID 1 does not exist."
  } 
  ```
 - **Failed Response (Invalid input)**
  ```json
  {
    "status": "fail",
    "data": "Username and password must be at least 5 characters long and not empty"
  } 
  ```
## User Delete
- **Method**: `DELETE`
- **Enpoint**:`/user/delete`
- **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
 -  **Payload**
  ```json
 {
  "userid": "1"
 }
 ```
- **Successful Response**
  ```json
  {
    "status": "success",
    "token": "<NEW_TOKEN>",
    "data": "User deleted successfully"
  }
  ```
- **Failed Response (User not Found)**
  ```json
  {
    "status": "fail",
    "data": "User with ID 1 does not exist."
  } 
  ```
 - **Failed Response (Invalid Input)**
  ```json
  {
    "status": "fail",
    "data": "User ID must be provided."
  } 
  ```
## Authors Create
- **Method**: `POST`
- **Enpoint**:`/authors/Create`
- **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
 -  **Payload**
  ```json
 {
  "username: J.K Rowling"
 }
 ```
- **Successful Response**
  ```json
  {
    "status": "success",
    "token": "<NEW_TOKEN>",
    "data": "Author created successfully"
  }
  ```
 - **Failed Response (Invalid Input)**
  ```json
  {
    "status": "fail",
    "data": "Author name must be more than 4 characters and cannot be blank."
  } 
  ```
## Authors Read
  - **Method**: `GET`
  - **EndPoint**: `/authors/read`
  -  **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
- **Payload**: no request needed.
- **Succesful Response**
  ```json
  {
   "status": "success",
    "token": "<NEW_Token>",
    "data": [
        {
            "authorid": 1,
            "name": "J.K Rowling"
        },
        {
            "authorid": 2,
            "name": "Charles Dickens"
        }
    }
  ```

- **Failed Response (Failed Authentication)**
  ```json
  {
    "status": "fail",
    "data": "Unauthorized: Token not provided"
  }
  ```
  - **Failed Response (No Authors Found)**
  ```json
  {
    "status": "fail",
    "data": "No authors found"
  }
## Authors Update
- **Method**: `PUT`
- **Endpoint**: `/authors/update`
-  **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
-  **Payload**
  ```json
 {
  "authorid": "2",
    "name": "Suzanne Collins"
 }
 ```
- **Successful Response**
  ```json
  {
    "status": "success",
    "token": "<NEW_TOKEN>",
    "data": "User updated successfully"
  }
  ```
- **Failed Response (Input Error)**
  ```json
  {
   "status": "fail",
    "data": "Author name must be more than 4 characters and cannot be blank."
  } 
  ```
 - **Failed Response (Invalid Author ID)**
  ```json
  {
    "status": "fail",
    "data": "Author with ID 2 does not exist."
  } 
  ```
## Authors Delete
- **Method**: `DELETE`
- **Enpoint**:`/authors/delete`
- **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
 -  **Payload**
  ```json
 {
  "userid": "2"
 }
 ```
- **Successful Response**
  ```json
  {
    "status": "success",
    "token": "<NEW_TOKEN>",
    "data": "User deleted successfully"
  }
  ```
- **Failed Response (Input Error)**
  ```json
  {
    "status": "fail",
    "data": "Author ID must be provided."
  } 
  ```
 - **Failed Response (Invalid Author)**
  ```json
  {
    "status": "fail",
    "data": "Author with ID 4 does not exist."
  } 
  ```
## Books Create
- **Method**: `POST`
- **Enpoint**:`/books/Create`
- **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
 -  **Payload**
  ```json
 {
    "title": "Harry Potter",
    "authorid": 1
 }
 ```
- **Successful Response**
  ```json
  {
    "status": "success",
    "token": "<NEW_TOKEN>",
    "data": "Book created successfully"
  }
  ```
 - **Failed Response (Invalid Book title)**
  ```json
  {
    "status": "fail",
    "data": "Book title  must be more than 4 characters and cannot be blank."
  } 
  ```
- **Failed Response (Invalid Author)**
  ```json
  {
    "status": "fail",
    "data": "Author not found"
  } 
  ```
  ## Book Read
  - **Method**: `GET`
  - **EndPoint**: `/books/read`
  -  **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
- **Payload**: no request needed.
- **Succesful Response**
  ```json
  {
   "status": "success",
    "token": "<NEW_TOKEN>",
    "data": [
        {
            "bookid": 1,
            "title": "Harry Potter",
            "authorid": 1,
            "author_name": "J.K Rowling"
        },
        {
            "bookid": 2,
            "title": "The hunger Games",
            "authorid": 2,
            "author_name": "Suzanne Collins"
        },
    ]
  ```
  - **Failed Response (No Books)**
  ```json
  {
    "status": "fail",
    "data": "No books found"
  }
  ```
  ## Books Update
- **Method**: `PUT`
- **Endpoint**: `/books/update`
-  **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
-  **Payload**
  ```json
 {
    "bookid": 1,
    "title": "The Great Gatsby",
    "authorid": 1
 }
 ```
- **Successful Response**
  ```json
  {
    "status": "success",
    "token": "<NEW_TOKEN>",
    "data": "Book updated successfully"
  }
  ```
- **Failed Response (Invalid Book ID)**
  ```json
  {
    "status": "fail",
    "data": "Book with ID 1 does not exist."
  } 
  ```
 - **Failed Response (Invalid Input)**
  ```json
  {
    "status": "fail",
    "data": "Book title and password must be at least 5 characters long and not empty"
  } 
  ```
## Books Delete
- **Method**: `DELETE`
- **Enpoint**:`/books/delete`
- **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
 -  **Payload**
  ```json
 {
   "bookid": 4
 }
 ```
- **Successful Response**
  ```json
  {
    "status": "success",
    "token": "<New_JWT_Token>",
    "data": "Book deleted successfully"
  }
  ```
- **Failed Response (Invalid BookID)**
  ```json
  {
    "status": "fail",
    "data": "Book ID must be provided."
  } 
  ```
 - **Failed Response (Invalid Book)**
  ```json
  {
   "status": "fail",
    "data": "Book with ID 4 does not exist."
  } 
  ```
## Book-Author Create
- **Method**: `POST`
- **Enpoint**:`/book-authors/Create`
- **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
 -  **Payload**
  ```json
 {
  "bookid": "1",
    "authorid": "1"
 }
 ```
- **Successful Response**
  ```json
  {
    "status": "success",
    "token": "<NEW_TOKEN>",
    "data": "Book-author relationship created successfully"
  }
  ```
 - **Failed Response (Invalid Book)**
  ```json
  {
     "status": "fail",
    "data": "Book does not exist"
  } 
  ```
 - **Failed Response (Invalid Author)**
  ```json
  {
    "status": "fail",
    "data": "Author does not exist"
  } 
  ```
## Book-Author Read
  - **Method**: `GET`
  - **EndPoint**: `/book-authors/read`
  -  **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
- **Payload**: no request needed.
- **Succesful Response**
  ```json
   {
    "status": "success",
    "token": "<NEW_TOKEN>",
    "data": [
        {
            "collectionid": "1",
            "bookid": "1",
            "authorid": "1",
            "book_title": "Book Title",
            "author_name": "Author Name"
        },
        {
            "collectionid": "2",
            "bookid": "2",
            "authorid": "2",
            "book_title": "Another Book",
            "author_name": "Another Author"
        }
    ]
  }
  ```

- **Failed Response (Invalid Book-Author Relationship)**
  ```json
  {
    "status": "fail",
    "data": "No book-author relationships found"
}
    ```
  ## Book-Author Update
- **Method**: `PUT`
- **Endpoint**: `/book-authors/update`
-  **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
-  **Payload**
  ```json
{
    "collectionid": "1",
    "bookid": "1",
    "authorid": "1"
}
 ```
- **Successful Response**
  ```json
  {
    "status": "success",
    "token": <NEW_TOKEN>",
    "data": "Book-author relationship updated successfully"
  }
  ```
 - **Failed Response (Invalid Book)**
  ```json
  {
    "status": "fail",
    "data": "Book does not exist"
  } 
  ```
  - **Failed Response (Invalid Author)**
  ```json
  {
    "status": "fail",
    "data": "Author does not exist"
}
  ```
## Book-Author Delete
- **Method**: `DELETE`
- **Enpoint**:`/book-author/delete`
- **Authentication**: requires a valid JWT TOKEN to access. The JWT TOKEN must be sent as a Bearer token in the `Authorization` header.
 -  **Payload**
  ```json
 {
  "collectionid": "1"
 }
 ```
- **Successful Response**
  ```json
  {
    "status": "success",
    "token": "<NEW_TOKEN>",
    "data": "Book-author relationship deleted successfully"
  }
  ```
- **Failed Response (Book-Author Relationship Deleted)**
  ```json
  {
    "status": "fail",
    "data": "Book ID must be provided."
  } 
  ```

  


  

  
