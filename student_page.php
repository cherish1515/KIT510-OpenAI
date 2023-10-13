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
</style>

<?php
// Include the navbar.php file
require_once('navbar.php');
?>

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
        <h1>Welcome to tutoring system</h1>
  </div>

  <div class="container2">
      <p>Posted 12 August, 2023 12:04</p>
        <h2>
        Welcome <?php echo $_SESSION["username"]; ?> 
        </h2><br>
          <p>
          </p>
    
  </div>
</main>






</div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
</body>
</html>
<!DOCTYPE html>