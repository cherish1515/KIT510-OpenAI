<?php
// Start or resume the session
session_start();

if(!isset ($_SESSION["username"])) 
{ 
    header("location:login.php"); 
}

// Oracle database connection settings
$host = 'localhost';
$port = '1521';
$db_service_name = 'xe';
$db_username = 'system';
$db_password = 'data';

// Create a connection to the Oracle database
$conn = oci_connect($db_username, $db_password, "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SERVICE_NAME=$db_service_name)))");

// SQL query to retrieve unit codes from the "UNIT" table
$sql = "SELECT unit_code FROM unit";

// Prepare and execute the SQL statement
$statement = oci_parse($conn, $sql);
oci_execute($statement);

$sessionUsername = $_SESSION['username'];
  
  // SQL query to retrieve user information based on username
  $sqlUserInfo = "SELECT * FROM users WHERE username = :username";
  
  // Prepare and execute the SQL statement for user information
  $statementUserInfo = oci_parse($conn, $sqlUserInfo);
  oci_bind_by_name($statementUserInfo, ":username", $sessionUsername); // Bind the parameter
  oci_execute($statementUserInfo);
  
 // Fetch all rows from the "STUDY" table and store them in an array
 $userInfo = array();
 while ($row = oci_fetch_array($statementUserInfo, OCI_ASSOC)) {
     $userInfo[] = $row;
 }

// Fetch the unit codes and store them in an array
$unitCodes = array();
while ($row = oci_fetch_array($statement, OCI_ASSOC)) {
    $unitCodes[] = $row['UNIT_CODE'];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Get user data (for example, from a database)
  $username = $_SESSION["username"]; // Assuming you store the username in the session
  $oldPassword = $_POST["old_password"];
  $newPassword = $_POST["new_password"];
  $confirmPassword = $_POST["confirm_password"];

  // Retrieve the user's password from the database (plaintext, not hashed)
  // Example SQL query: SELECT password FROM users WHERE username = :username
  $sql = "SELECT password FROM users WHERE username = :username";
  $stmt = oci_parse($conn, $sql);
  oci_bind_by_name($stmt, ":username", $username);
  oci_execute($stmt);
  $row = oci_fetch_assoc($stmt);
  $storedPassword = $row["PASSWORD"]; 
  
  // Validate old password
  if ($oldPassword === $storedPassword) {
      // Validate and update the new password
      if ($newPassword === $confirmPassword) {
          // Update the user's password in the database
          // Example SQL query: UPDATE users SET password = :newPassword WHERE username = :username
          $updateSql = "UPDATE users SET password = :newPassword WHERE username = :username";
          $updateStmt = oci_parse($conn, $updateSql);
          oci_bind_by_name($updateStmt, ":newPassword", $newPassword);
          oci_bind_by_name($updateStmt, ":username", $username);

          if (oci_execute($updateStmt)) {
              echo "Password changed successfully!";
          } else {
              echo "Error updating password: " . oci_error($conn);
          }
      } else {
          echo "New password and confirm password do not match.";
      }
  } else {
      echo "Invalid old password.";
  }
}


// Close the Oracle connection
oci_close($conn);
?>

<!DOCTYPE html>
<style>
        .navbar {
            background-color: #E1DAE3;
            color: #E1DAE3; 
        }
        .buttonR {
            background-color: #f44336;
        }
        .body2 {
            margin: 0;
            padding: 0;
            display: grid;
            grid-template-columns: 5% 95%;
            min-height: 100vh;
        }
        
        aside {
            padding: 20px;
            border-right: 1px solid #999;
        }

        .unit-header {
            text-align: center;
        }
        
        main {
            padding: 20px;
        }
        .container {
            display: flex;
            flex-direction: column;
            justify-content: center; 
            align-items: center; 
            height: 15vh; 
        }
        .container2 {
            display: flex;
            flex-direction: column;
            justify-content: center; 
            align-items: center; 
        }
        .button1 {
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

<div class="collapse" id="navbarToggleExternalContent" data-bs-theme="dark">
  <div class="bg-dark p-4">
    <?php
  foreach ($userInfo as $user) {
        if (isset($user['ROLE'])) {
            $userRole = $user['ROLE'];
            break; // Once we find the role, no need to continue searching
        }
    }
        // Define the links based on the user's role
        $homeLink = 'main_page.php';
        $studentLink = 'student_page.php';
        $tutorLink = 'tutor_page.php';

        // Determine the link to display based on the user's role
        $redirectLink = '';
        if ($userRole == 'Student') {
            $redirectLink = $studentLink;
        } elseif ($userRole == 'Tutor') {
            $redirectLink = $tutorLink;
        } else {
            $redirectLink = $homeLink; // Default link
        }

        // Generate the link
        echo '<a href="' . $redirectLink . '" class="text-body-emphasis h4">Home</a><br><br>';
    
    ?>
  </div>
</div>

<div class="collapse" id="navbarToggleExternalContent2" data-bs-theme="dark" >
  <div class="bg-dark p-4 text-end" >
    <span class="text-body-emphasis h4"><?php echo $_SESSION["username"]; ?></span><br><br>
    <a href="change_pw.php" class="text-body-emphasis h4">Change Password</a><br><br>
    <a href="login.php" class="btn btn-outline-danger log-off-button">Log off</a>
  </div>
</div>

<nav class="navbar">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarToggleExternalContent" aria-controls="navbarToggleExternalContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
      </button>

      <div class="d-flex">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarToggleExternalContent2" aria-controls="navbarToggleExternalContent2" aria-expanded="false" aria-label="Toggle navigation">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
  <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3Zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/> 
    </svg>
      </button>
      
  </div>
  </nav>

<html>
<body>
<div class="body2">

<aside>
  <div class="unit-header">
    <h4>Units</h4><br>

    <?php
    foreach ($unitCodes as $unitCode) {
      // Add the unit_code as a query parameter to the link
      $unitPageURL = $unitCode . '.php?unit_code=' . $unitCode;
      echo '<a href="' . $unitPageURL . '">' . $unitCode . '</a><br><br>';
    }
    ?>
  </div>
</aside>

<main>
  <div class="container">
        <h1>Change Password</h1>
  </div>

  <div class="container2">

  <form action="" method="post">
        <label for="old_password"></label>
        <input type="password" name="old_password" placeholder="Old Password"required><br><br><br>
        
        <label for="new_password"></label>
        <input type="password" name="new_password" placeholder="New Password"required><br><br>
        
        <label for="confirm_password"></label>
        <input type="password" name="confirm_password" placeholder="Confirm New Password"required><br><br>
        
        <div class="container2">

        <input type="submit" value="Confirm" class= "button1 buttonB">
    
    </div>
    </form>
    
  
  </div>
</main>






</div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
</body>
</html>
<!DOCTYPE html>