<?php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    print_r($_POST);}
// Start or resume the session

// Start or resume the session
session_start();

if (!isset($_SESSION["username"])) {
    header("location: login.php");
}
// Oracle database connection settings
$host = 'localhost';
$port = '1521';
$db_service_name = 'xe';
$db_username = 'system';
$db_password = 'data';

// Create a connection to the Oracle database
$conn = oci_connect($db_username, $db_password, "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SERVICE_NAME=$db_service_name)))");

// SQL query to retrieve unit codes from the "unit" table
$unitSql = "SELECT unit_code FROM unit";
$unitStatement = oci_parse($conn, $unitSql);
oci_execute($unitStatement);

// Fetch the unit codes and store them in an array
$unitCodes = array();
while ($row = oci_fetch_array($unitStatement, OCI_ASSOC)) {
    $unitCodes[] = $row['UNIT_CODE'];
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
 while ($row1 = oci_fetch_array($statementUserInfo, OCI_ASSOC)) {
     $userInfo[] = $row1;
 }

// Get the case_id from the URL
$caseIdFromURL = $_GET['case_id'];

// SQL query to retrieve week_title and case_title based on case_id
$sqlCaseDetails = "SELECT week_title, case_title, unit_code FROM study WHERE case_id = :case_id";

// Prepare and execute the SQL statement
$statementCaseDetails = oci_parse($conn, $sqlCaseDetails);
oci_bind_by_name($statementCaseDetails, ":case_id", $caseIdFromURL); // Bind the parameter
oci_execute($statementCaseDetails);

// Fetch the data
$row = oci_fetch_array($statementCaseDetails, OCI_ASSOC);

if ($row) {
    $weekTitle = $row['WEEK_TITLE'];
    $caseTitle = $row['CASE_TITLE'];
    $unitCode = $row['UNIT_CODE'];

    // Now you can display $weekTitle and $caseTitle as needed
} else {
    // Handle the case where no data was found for the given case_id
    echo "No data found for the specified case_id.";
}

$currentDescription = '';
$currentanswer = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save"])) {
    // Retrieve form data
    $caseId = $_POST["case_id"];
    $stepDescription = $_POST["step_description"];

    // Get the next value from the step_id sequence
    $stepIdSql = "SELECT step_id_sequence.NEXTVAL FROM DUAL";
    $stepIdStmt = oci_parse($conn, $stepIdSql);
    oci_execute($stepIdStmt);
    $rowStep = oci_fetch_assoc($stepIdStmt);
    $stepId = $rowStep["NEXTVAL"];

    // Insert the new step description into the database with the generated step_id
    $insertSql = "INSERT INTO step (step_description, step_id, case_id) VALUES (:step_description, :step_id, :case_id)";
    $stmt = oci_parse($conn, $insertSql);
    oci_bind_by_name($stmt, ":step_description", $stepDescription);
    oci_bind_by_name($stmt, ":step_id", $stepId);
    oci_bind_by_name($stmt, ":case_id", $caseId);

    oci_execute($stmt);
}

// SQL query to retrieve step_description based on case_id
$sqlStepDescription = "SELECT step_description, step_id FROM step WHERE case_id = :case_id ORDER BY step_id ASC";

// Prepare and execute the SQL statement
$statementStepDescription = oci_parse($conn, $sqlStepDescription);
oci_bind_by_name($statementStepDescription, ":case_id", $caseIdFromURL); // Bind the parameter
oci_execute($statementStepDescription);

// Initialize an array to store step descriptions
$stepDescriptions = array();

// Fetch all step descriptions for the given case_id
while ($rowStep = oci_fetch_array($statementStepDescription, OCI_ASSOC)) {
    $stepId = $rowStep['STEP_ID'];
    $stepDescription = $rowStep['STEP_DESCRIPTION'];
    
    // Store both step_id and step_description together in an array
    $stepDescriptions[] = array('step_id' => $stepId, 'step_description' => $stepDescription);
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_step_description"])) {
    $stepIdToUpdate = $_POST["step_id"];
    $updatedStepDescription = $_POST["step_description"];

    if (!empty($stepIdToUpdate) && !empty($updatedStepDescription)) {
        // Update the step description in the database
        $updateSql = "UPDATE step SET step_description = :updated_step_description WHERE step_id = :step_id";
        $stmt = oci_parse($conn, $updateSql);
        oci_bind_by_name($stmt, ":updated_step_description", $updatedStepDescription);
        oci_bind_by_name($stmt, ":step_id", $stepIdToUpdate);

        if (oci_execute($stmt)) {
            echo "Step description updated successfully for step_id: $stepIdToUpdate";
        } else {
            echo "Error updating step description: " . oci_error($conn);
        }
    } else {
        echo "Step ID or description is missing or empty.";
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_answer"])) {
    $stepIdToUpdate = $_POST["step_id"];
    $updatedAnswer = $_POST["answer"];

    // Check if step_id and answer are not empty
    if (!empty($stepIdToUpdate)) {
        // Update the answer in the database
        $updateAnswerSql = "UPDATE step SET answer = :updated_answer WHERE step_id = :step_id";
        $stmt = oci_parse($conn, $updateAnswerSql);
        oci_bind_by_name($stmt, ":updated_answer", $updatedAnswer);
        oci_bind_by_name($stmt, ":step_id", $stepIdToUpdate);

        if (oci_execute($stmt)) {
            echo "Answer updated successfully for step_id: $stepIdToUpdate";
        } else {
            echo "Error updating answer: " . oci_error($conn);
        }
    } else {
        echo "Step ID or answer is missing or empty.";
    }
}

foreach ($stepDescriptions as $stepData) {
    $stepId = $stepData['step_id'];

    // Check if student_answer for this step exists in POST data
    if (isset($_POST["student_answer"][$stepId])) {
        // Get the new student_answer for this step from the form
        $newStudentAnswer = $_POST["student_answer"][$stepId];

        // Update the session with the new student_answer
        $sessionKey = "student_answer_$stepId";
        $_SESSION[$sessionKey] = $newStudentAnswer;

        // Check if a record already exists in the "STUDENT_STEP_SOLUTION" table for this step and user
        $checkQuery = "SELECT COUNT(*) FROM student_step_solution WHERE user_id = :user_id AND step_id = :step_id";
        $checkStmt = oci_parse($conn, $checkQuery);
        oci_bind_by_name($checkStmt, ":user_id", $user_id);
        oci_bind_by_name($checkStmt, ":step_id", $stepId);
        oci_execute($checkStmt);

        $rowC = oci_fetch_assoc($checkStmt);
        $recordExists = $rowC["COUNT(*)"] > 0;

        if ($recordExists) {
            // Update the existing record
            $updateQuery = "UPDATE student_step_solution SET student_answer = :new_student_answer WHERE user_id = :user_id AND step_id = :step_id";
            $updateStmt = oci_parse($conn, $updateQuery);
            oci_bind_by_name($updateStmt, ":user_id", $user_id);
            oci_bind_by_name($updateStmt, ":step_id", $stepId);
            oci_bind_by_name($updateStmt, ":new_student_answer", $newStudentAnswer);

            if (oci_execute($updateStmt)) {
                echo "Student answer updated successfully for step_id: $stepId";
            } else {
                echo "Error updating student answer: " . oci_error($conn);
            }
        } else {
            // Insert a new record
            $insertQuery = "INSERT INTO student_step_solution (user_id, step_id, student_answer) VALUES (:user_id, :step_id, :new_student_answer)";
            $insertStmt = oci_parse($conn, $insertQuery);
            oci_bind_by_name($insertStmt, ":user_id", $user_id);
            oci_bind_by_name($insertStmt, ":step_id", $stepId);
            oci_bind_by_name($insertStmt, ":new_student_answer", $newStudentAnswer);

            if (oci_execute($insertStmt)) {
                echo "Student answer inserted successfully for step_id: $stepId";
            } else {
                echo "Error inserting student answer: " . oci_error($conn);
            }
        }
        
    }
}

// Check if username is present in the URL parameters
if(isset($_GET['username'])) {
    $username = $_GET['username'];

    // Assuming you have a database connection established
    // You need to modify this query based on your actual database schema
    $getUserIDQuery = "SELECT user_id FROM users WHERE username = :username";

    // Prepare and execute the SQL statement to get user_id
    $stmt = oci_parse($conn, $getUserIDQuery);
    oci_bind_by_name($stmt, ":username", $username);
    oci_execute($stmt);

    // Fetch the user_id from the query result
    $rowSo = oci_fetch_assoc($stmt);
    if ($rowSo) {
        $user_id = $rowSo['USER_ID']; // Retrieve the user_id
    } else {
        // Handle the case where the username is not found in the database
        echo "Username not found in the database.";
        exit(); // Exit the script if username not found
    }

    // Now you have the $user_id, use it to fetch data from the database
    // Perform your database query and display the data as needed
} else {
    echo "Username not provided in the URL parameters.";
}


// Assuming you have the step_mark value and step_id from your form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_marks"])) {
    $case_id = $_GET["case_id"];
    // Assuming you have a database connection established
    // Iterate through the submitted marks and update the database
    foreach ($_POST["step_mark"] as $stepId => $mark) {
        // Validate and sanitize the input if needed
        // Update the marks in the student_step_solution table
        $updateMarkSql = "UPDATE student_step_solution 
                            SET individual_step_mark = :mark 
                            WHERE step_id = :step_id 
                            AND case_id = :case_id 
                            AND user_id = :user_id";

        
        $updateMarkStmt = oci_parse($conn, $updateMarkSql);
        oci_bind_by_name($updateMarkStmt, ":mark", $mark);
        oci_bind_by_name($updateMarkStmt, ":step_id", $stepId);
        oci_bind_by_name($updateMarkStmt, ":case_id", $case_id);
        oci_bind_by_name($updateMarkStmt, ":user_id", $user_id);
        
        if (oci_execute($updateMarkStmt)) {
            echo "Mark updated successfully for Step ID: $stepId";
        } else {
            echo "Error updating mark: " . oci_error($conn);
        }
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
            grid-template-columns: 5% 50% 45%;
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
        .buttons {
             margin-left: auto; 
        }
  
        .button {
            margin-left: 10px; 
        }
        .flex-container {
            display: flex;
            align-items: center; 
        }
        .buttonG {
            padding: 4px 15px;
            border-radius: 12px; 
        }
        .buttonW {
            padding: 4px 15px;
            border-radius: 12px; 
            background-color: white;
        }
        .buttonB{
            padding: 4px 15px;
            border-radius: 12px; 
            background-color: #008CBA;
            color: white;
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

<?php
// Include the navbar.php file
require_once('navbar.php');
?>

<html>
<body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    <h3><?php echo $row['UNIT_CODE']; ?></h3>
    <h2 style="display: inline;"><?php echo $row['WEEK_TITLE']; ?></h2>
    <h2 style="display: inline;">:</h2>
    <h2 style="display: inline;"><?php echo $row['CASE_TITLE']; ?></h2><br><br>
    <?php
        // SQL query to retrieve total step_mark based on case_id
        $sqlTotalStepMark = "SELECT SUM(step_mark) as total_mark FROM step WHERE case_id = :case_id";

        // Prepare and execute the SQL statement
        $statementTotalStepMark = oci_parse($conn, $sqlTotalStepMark);
        oci_bind_by_name($statementTotalStepMark, ":case_id", $caseIdFromURL); // Bind the parameter
        oci_execute($statementTotalStepMark);

        // Fetch the total step_mark for the given case_id
        $totalMarks = oci_fetch_assoc($statementTotalStepMark)['TOTAL_MARK'];

        // SQL query to retrieve total step_mark based on case_id
        $sqlTotalStepMark = "SELECT SUM(individual_step_mark) as total_student_mark FROM student_step_solution WHERE case_id = :case_id";

        // Prepare and execute the SQL statement
        $statementTotalStudentMark = oci_parse($conn, $sqlTotalStepMark);
        oci_bind_by_name($statementTotalStudentMark, ":case_id", $caseIdFromURL); // Bind the parameter
        oci_execute($statementTotalStudentMark);

        // Fetch the total step_mark for the given case_id
        $totalStudentMarks = oci_fetch_assoc($statementTotalStudentMark)['TOTAL_STUDENT_MARK'];
        

        // Output the total marks
        echo '<h4>Student Total Marks: ' . $totalStudentMarks .'/'. $totalMarks . '</h4><br><br>';
    ?>

    <?php
        // Fetch case_id and user_id from URL
        $case_id = $_GET["case_id"];

        // Query to fetch step_description from step table based on step_id
        $fetchStepDescriptionSql = "SELECT step_id, step_description FROM step WHERE case_id = :case_id ORDER BY step_id ASC";
        $fetchStepDescriptionStmt = oci_parse($conn, $fetchStepDescriptionSql);
        oci_bind_by_name($fetchStepDescriptionStmt, ":case_id", $case_id);
        oci_execute($fetchStepDescriptionStmt);


        // Loop through the results and display step_description from step table
        while ($stepRow = oci_fetch_assoc($fetchStepDescriptionStmt)) {
            $stepId = $stepRow['STEP_ID'];
            $stepDescription = $stepRow['STEP_DESCRIPTION'];

            // Query to fetch student_answer from student_step_solution table based on case_id, user_id, and step_id
            $fetchStudentAnswerSql = "SELECT student_answer, individual_step_mark FROM student_step_solution WHERE case_id = :case_id AND user_id = :user_id AND step_id = :step_id";
            $fetchStudentAnswerStmt = oci_parse($conn, $fetchStudentAnswerSql);
            oci_bind_by_name($fetchStudentAnswerStmt, ":case_id", $case_id);
            oci_bind_by_name($fetchStudentAnswerStmt, ":user_id", $user_id);
            oci_bind_by_name($fetchStudentAnswerStmt, ":step_id", $stepId);
            oci_execute($fetchStudentAnswerStmt);

            // Fetch student_answer and individual_step_mark
            $studentAnswer = "";
            $mark = "";
            if ($studentRow = oci_fetch_assoc($fetchStudentAnswerStmt)) {
                $studentAnswer = $studentRow['STUDENT_ANSWER'];
                $mark = $studentRow['INDIVIDUAL_STEP_MARK'];
            }

            // Display step_description and student_answer
            echo '<form method="post" action="">';
            echo '<h5>Step Description:</h5><p>' . $stepDescription . '</p><br>';
            echo '<h5>Student Answer:</h5><p>' . $studentAnswer . '</p><br>';
            echo '<h5>Mark:</h5><input type="number" name="step_mark[' . $stepId . ']" id="mark' . $stepId . '" value="' . $mark . '"><br>';
            echo '<input type="submit" name="save_marks" value="Save Mark"><br><br><br>';
            echo '</form>';
        }

        ?>


</main>


<main>
        <h1>&nbsp;Example Answer</h1><br><br><br>
       

   
    <?php
        // Fetch step_description based on the case_id from the step table and order by step_id
        $fetchStepDataSql = "SELECT step_id, step_description, answer
        FROM step
        WHERE case_id = :case_id
        ORDER BY step_id ASC";


        $fetchStepDataStmt = oci_parse($conn, $fetchStepDataSql);
        oci_bind_by_name($fetchStepDataStmt, ":case_id", $case_id);
        oci_execute($fetchStepDataStmt);

        // Loop through the results and display the step_description for each step
        while ($row = oci_fetch_assoc($fetchStepDataStmt)) {
            $stepId = $row['STEP_ID'];
            $stepDescription = $row['STEP_DESCRIPTION'];
            $Answer = $row['ANSWER'];

            echo '<form method="post" action="">';
            echo '<h5>Step Description:</h5><p>' . $stepDescription . '</p>';
            echo '<h5>Example Answer:</h5><p>' .$Answer . '</p><br><br>';
            echo '</form>';
        }
    ?>


    <script>
            document.addEventListener("DOMContentLoaded", function () {
                const sortableHeaders = document.querySelectorAll(".sortable");
                const searchInputs = document.querySelectorAll("[data-search-input]");

                sortableHeaders.forEach(header => {
                    header.addEventListener("click", function () {
                        const sortKey = header.getAttribute("data-sort");
                    });
                });

                searchInputs.forEach(input => {
                    input.addEventListener("input", function () {
                    });
                });
            });
    </script>
    </table>
    
</main>






</div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
</body>
</html>
<!DOCTYPE html>