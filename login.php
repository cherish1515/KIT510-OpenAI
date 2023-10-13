<?php
// Oracle Database Connection Parameters
$servername = "localhost";  // Change this to your Oracle server address
$username = "system";  // Change this to your Oracle username
$password = "data";  // Change this to your Oracle password
$service = "XE";  // Change this to your Oracle service name

// Establish Oracle Database connection
$conn = oci_connect($username, $password, "(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=$servername)(PORT=1521)))(CONNECT_DATA=(SERVICE_NAME=$service)))");

// Check connection
if (!$conn) {
    $error = oci_error();
    die("Connection failed: " . $error['message']);
}

$username = '';
$password = '';

// If the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputUsername = $_POST["username"];
    $inputPassword = $_POST["password"];
    
    // Query to check user credentials
    $query = "SELECT * FROM users WHERE username = :username AND password = :password";
    $statement = oci_parse($conn, $query);
    
    oci_bind_by_name($statement, ':username', $inputUsername);
    oci_bind_by_name($statement, ':password', $inputPassword);
    
    oci_execute($statement);
    
    // Check if user exists
    if ($row = oci_fetch_assoc($statement)) {
        session_start();
        $_SESSION["username"] = $row["USERNAME"];
        
        // Check the user's role ID and redirect accordingly
        if ($row["ROLE"] == 'Student') {
            header("Location: student_page.php");
            exit();
        } elseif ($row["ROLE"] == 'Tutor') {
            header("Location: tutor_page.php");
            exit();
        } else {
            // Handle other role IDs or scenarios here
            // For example, redirect to a default page or display an error message
        }
    } else {
        $error_message = "Invalid username or password.";
    }
}

?>


<!DOCTYPE html>
<style>
        .container {
            display: flex;
            flex-direction: column;
            justify-content: center; 
            align-items: center; 
            height: 35vh; 
        }
        .navbar {
            background-color: #E1DAE3;
            color: #E1DAE3; 
        }
        .container2 {
            display: flex;
            flex-direction: column;
            justify-content: center; 
            align-items: center; 
        }
        .button {
            border-radius: 12px;
            background-color: white; 
            color: black; 
            border: 2px solid #008CBA;
            padding: 10px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            transition-duration: 0.4s;
            font-size: 16px;
            margin: 4px 2px;
            }
        .buttonB:hover {
            border-radius: 12px;
            background-color: #008CBA;  
            border: none;
            color: white;
            padding: 15px 60px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            transition-duration: 0.4s;
            cursor: pointer;
        }
    </style>
<html>



<head>
    <nav class="navbar fixed-top">
    <div class="container-fluid">
        <a class="navbar">nothing</a>
    </div>
    </nav>
    
    <title>Login Page</title>

</head>


<body>
    <div class="container">
        <h1>Tutoring Intelligent System</h1>
        </div>

    <div class="container2">
        <h2>Log in</h2><br>
    <form action="login.php" method="POST">
        <label for="username"></label>
        <input type="text" id="username" name="username" placeholder="Username" value="<?= $username ? $username : ''?>"required><br><br>
        
        <label for="password"></label>
        <input type="password" id="password" name="password" placeholder="Password" value="<?= $password ? $password : ''?>"required><br><br>
        
    <div class="container2">

        <input type="submit" value="login" class= "button buttonB"><br>
        <p>Student: john_doe, password</p>
        <p>Tutor: mike_j, hello123</p>
    </div>
    </form>
    </div>
        
        
    
    <?php if(isset($error_message)) { echo "<p>$error_message</p>"; } ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>

</body>
</html>
<!DOCTYPE html>


