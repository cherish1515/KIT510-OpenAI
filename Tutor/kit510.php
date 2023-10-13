<?php
// Start or resume the session
session_start();

    
// Oracle database connection settings
$host = 'localhost';
$port = '1521';
$db_service_name = 'xe';
$db_username = 'system';
$db_password = 'data';

// Create a connection to the Oracle database
$conn = oci_connect($db_username, $db_password, "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SERVICE_NAME=$db_service_name)))");

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

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


// SQL query to retrieve unit codes from the "UNIT" table
$sqlUnit = "SELECT unit_code FROM unit";

// Prepare and execute the SQL statement for unit codes
$statementUnit = oci_parse($conn, $sqlUnit);
oci_execute($statementUnit);

// Fetch the unit codes and store them in an array
$unitCodes = array();
while ($row = oci_fetch_array($statementUnit, OCI_ASSOC)) {
    $unitCodes[] = $row['UNIT_CODE'];
}


// Get the unit code from the URL
$unitCodeFromURL = $_GET['unit_code'];

// SQL query to retrieve data from the "STUDY" table for the specific unit_code
$sqlStudy = "SELECT * FROM study WHERE unit_code = :unit_code";

// Prepare and execute the SQL statement for study data
$statementStudy = oci_parse($conn, $sqlStudy);
oci_bind_by_name($statementStudy, ":unit_code", $unitCodeFromURL); // Bind the parameter
oci_execute($statementStudy);

// Fetch all rows from the "STUDY" table and store them in an array
$studyData = array();
while ($row = oci_fetch_array($statementStudy, OCI_ASSOC)) {
    $studyData[] = $row;
}

// Insert data from form into Oracle
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Retrieve data from the form
  $unitCode = $_POST["unit_code"];
  $weekTitle = $_POST["week_title"];
  $caseTitle = $_POST["case_title"];
  $startDate = $_POST["start_date"];
  $endDate = $_POST["end_date"];

// SQL query with placeholders
$sqlInsert = "INSERT INTO study (case_id, unit_code, week_title, case_title, start_date, end_date)
        VALUES (study_sequence.nextval, :unit_code, :week_title, :case_title, TO_DATE(:start_date, 'yyyy-mm-dd'), TO_DATE(:end_date, 'yyyy-mm-dd'))";

$stmt = oci_parse($conn, $sqlInsert);
  
    oci_bind_by_name($stmt, ":unit_code", $unitCode);
    oci_bind_by_name($stmt, ":week_title", $weekTitle);
    oci_bind_by_name($stmt, ":case_title", $caseTitle);
    oci_bind_by_name($stmt, ":start_date", $startDate);
    oci_bind_by_name($stmt, ":end_date", $endDate);

    oci_execute($stmt);
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
        }
        .buttonB{
            padding: 4px 15px;
            border-radius: 12px; 
            background-color: #008CBA;
            color: white;
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
    <br><br><h1><?php echo $_GET['unit_code']; ?></h1><br>
    <table style="width:100%;text-align: center"> 
    <!-- Add a new row with input fields for adding data -->
    <?php
$userRole = ''; // Initialize the user's role variable

// Assuming $userInfo is an array containing user information
foreach ($userInfo as $user) {
    if (isset($user['ROLE'])) {
        $userRole = $user['ROLE'];
        break; // Once we find the role, no need to continue searching
    }
}

if ($userRole === "Tutor") {
    // Show the form for Tutors
    ?>
    <form method="post" action="">
        <tr style="height:75px">
            <input type="hidden" name="unit_code" value="<?php echo $_GET['unit_code']; ?>">
            <td><input type="text" name="week_title" required placeholder="Week Title"></td>
            <td><input type="text" name="case_title" required placeholder="Case Title"></td>
            <td><input type="text" name="start_date" required placeholder="Start Date (yyyy-mm-dd)"></td>
            <td><input type="text" name="end_date" required placeholder="End Date (yyyy-mm-dd)"></td>
            <td><input type="submit" name="submit" value="Add" class="buttonB"></td>
        </tr>
    </form>
    <?php
}
?>


<tr style="border:1px solid;height:70px">
    <th style="border:1px solid">Week</th>
    <th style="border:1px solid">Case Title</th>
    <th style="border:1px solid">Start Date</th>
    <th style="border:1px solid">End Date</th>
    <th style="border:1px solid">Steps</th>

    <?php
    // Check the user's role and display buttons accordingly
    if ($userRole == 'Student') {
        // User has role 'Student', display the "Status" column
        echo "<th style='border:1px solid'>Status</th>";
    } elseif ($userRole == 'Tutor') {
        // User has role 'Tutor', do not display the "Status" column
    }
    ?>

    <th style="border:1px solid">Action</th>
</tr>

    <?php
        // Retrieve USER_ID based on the session username
        $sqlGetUserId = "SELECT USER_ID FROM users WHERE username = :username";
        $stmtGetUserId = oci_parse($conn, $sqlGetUserId);
        oci_bind_by_name($stmtGetUserId, ":username", $sessionUsername);
        oci_execute($stmtGetUserId);
        
        $userId = oci_fetch_assoc($stmtGetUserId)['USER_ID'];
        oci_free_statement($stmtGetUserId);

        foreach ($studyData as $row) {
            echo "<tr style='border:1px solid;height:50px'>";
            echo "<td style='border:1px solid'>" . $row['WEEK_TITLE'] . "</td>";
            echo "<td style='border:1px solid'>" . $row['CASE_TITLE'] . "</td>";
            echo "<td style='border:1px solid'>" . $row['START_DATE'] . "</td>";
            echo "<td style='border:1px solid'>" . $row['END_DATE'] . "</td>";
        
            // Get total step_id for the current CASE_ID from the step table
            $caseId = $row['CASE_ID'];
            $getTotalStepsQuery = "SELECT COUNT(step_id) as total_steps FROM step WHERE CASE_ID = :case_id";
            $stmtTotalSteps = oci_parse($conn, $getTotalStepsQuery);
            oci_bind_by_name($stmtTotalSteps, ":case_id", $caseId);
            oci_execute($stmtTotalSteps);
            $totalSteps = 0;
            if ($rowTotalSteps = oci_fetch_assoc($stmtTotalSteps)) {
                $totalSteps = $rowTotalSteps['TOTAL_STEPS']; // Retrieve the total steps
            }
            oci_free_statement($stmtTotalSteps);
            
            // Iterate through $userInfo to find the user's role
            $userRole = ''; // Initialize the user's role variable
            foreach ($userInfo as $user) {
                if (isset($user['ROLE'])) {
                    $userRole = $user['ROLE'];
                    break; // Once we find the role, no need to continue searching
                }
            }
        
            // Get count of non-null student_answer for the current CASE_ID and USER_ID from the student_step_solution table
            $getStudentAnswerCountQuery = "SELECT COUNT(*) as student_answer_count FROM student_step_solution WHERE CASE_ID = :case_id AND USER_ID = :user_id AND student_answer IS NOT NULL";
            $stmtAnswerCount = oci_parse($conn, $getStudentAnswerCountQuery);
            oci_bind_by_name($stmtAnswerCount, ":case_id", $caseId);
            oci_bind_by_name($stmtAnswerCount, ":user_id", $userId); // Bind USER_ID here
            oci_execute($stmtAnswerCount);
            $studentAnswerCount = 0;
            if ($rowAnswerCount = oci_fetch_assoc($stmtAnswerCount)) {
                $studentAnswerCount = $rowAnswerCount['STUDENT_ANSWER_COUNT']; // Retrieve the non-null student answer count
            }
            oci_free_statement($stmtAnswerCount);

            // Get solution_submitted_time for the current CASE_ID and USER_ID from the student_step_solution table
            $getSubmittedTimeQuery = "SELECT MAX(solution_submitted_time) as submitted_time FROM student_step_solution WHERE CASE_ID = :case_id AND USER_ID = :user_id";
            $stmtSubmittedTime = oci_parse($conn, $getSubmittedTimeQuery);
            oci_bind_by_name($stmtSubmittedTime, ":case_id", $caseId);
            oci_bind_by_name($stmtSubmittedTime, ":user_id", $userId); // Bind USER_ID here
            oci_execute($stmtSubmittedTime);
            $submittedTime = oci_fetch_assoc($stmtSubmittedTime)['SUBMITTED_TIME'];
            oci_free_statement($stmtSubmittedTime);
    
             // Check the user's role and display buttons accordingly
            if ($userRole == 'Student') {
                echo "<td style='border:1px solid'>" . $studentAnswerCount . "/" . $totalSteps . "</td>";
                echo "<td style='border:1px solid'>" . ($submittedTime != null ? "Submitted" : "Pending") . "</td>";
            } elseif ($userRole == 'Tutor') {
                echo "<td style='border:1px solid'>" . $totalSteps . "</td>";
            } 
    

            // Assuming you have a database connection established ($conn)
            // Query to fetch START_DATE from the study table based on the specific case_id
            $getStartDateQuery = "SELECT START_DATE FROM study WHERE CASE_ID = :case_id";

            // Prepare the statement
            $stmtGetStartDate = oci_parse($conn, $getStartDateQuery);

            // Bind parameters
            oci_bind_by_name($stmtGetStartDate, ":case_id", $row['CASE_ID']); // Assuming $row['CASE_ID'] contains the specific case_id

            // Execute the query
            oci_execute($stmtGetStartDate);

            // Fetch the START_DATE from the result
            if ($startDateRow = oci_fetch_assoc($stmtGetStartDate)) {
                $start_date = $startDateRow['START_DATE'];
            }

            // Get the current system date and time
            $current_date = date("Y-m-d H:i:s"); // Format: 'YYYY-MM-DD HH:MM:SS'

            // Check the user's role and display buttons accordingly
            if ($userRole == 'Student' && $current_date >= $start_date) {
                // User has role 'Student' and current date is later than or equal to START_DATE, display the "Enter" button
                echo "<td><a href='student_answer.php?case_id=" . $row['CASE_ID'] . "' class='buttonB' style='border: 2px solid black'>Enter</a></td>";
            } elseif ($userRole == 'Tutor') {
                // User has role 'Tutor', and current date is later than or equal to START_DATE, display the "Edit" button
                echo "<td><a href='edit.php?case_id=" . $row['CASE_ID'] . "' class='buttonB' style='border: 2px solid black'>Edit</a></td>";
            } else {
                // User has role 'Tutor', but current date is earlier than START_DATE, disable the "Edit" button
                echo "<td><span class='disabledButton' style='border: 2px solid black'>Edit</span></td>";
            }

            echo "</tr>";
        }
    ?>






    </table>

   
  </div>
</main>

</div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
</body>
</html>
<!DOCTYPE html>