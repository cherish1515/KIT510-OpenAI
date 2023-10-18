<?php
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        print_r($_POST);}
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

    // SQL query to retrieve user details from the "users" table
    $userSql = "SELECT * FROM users";
    $userStatement = oci_parse($conn, $userSql);
    oci_execute($userStatement);

    // Fetch user details and store them in an array
    $userDetails = array();
    while ($row = oci_fetch_array($userStatement, OCI_ASSOC)) {
        $userDetails[] = $row;
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


    // Check if the form is submitted
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_answers"])) {
        // Assuming you have a database connection established
        // Get step_id and case_id from the form submission
        $step_id = $_POST["step_id"];
        $case_id = $_POST["case_id"];
        
        // Update solution_updated_time with the current timestamp
        $updateTimeSql = "UPDATE student_step_solution 
                        SET solution_updated_time = SYSTIMESTAMP
                        WHERE step_id = :step_id 
                        AND case_id = :case_id";
        
        $updateTimeStmt = oci_parse($conn, $updateTimeSql);
        oci_bind_by_name($updateTimeStmt, ":step_id", $step_id);
        oci_bind_by_name($updateTimeStmt, ":case_id", $case_id);
        
        if (oci_execute($updateTimeStmt)) {
            echo "Time updated, ";
        } else {
            echo "Error updating solution: " . oci_error($conn);
        }

        // Get the username and case_id from session and URL parameters
        $username = $_SESSION["username"]; 
        $case_id = $_GET["case_id"]; 

        // Assuming you have a database connection established
        // You need to modify this query based on your actual database schema
        $getUserIDQuery = "SELECT user_id FROM users WHERE username = :username";

        // Prepare and execute the SQL statement to get user_id
        $stmt = oci_parse($conn, $getUserIDQuery);
        oci_bind_by_name($stmt, ":username", $username);
        oci_execute($stmt);

        // Fetch the user_id from the query result
        $rowAA = oci_fetch_assoc($stmt);
        if ($rowAA) {
            $user_id = $rowAA['USER_ID']; // Retrieve the user_id
        } else {
            // Handle the case where the username is not found in the database
            echo "Username not found in the database.";
        }

    // Iterate through the submitted student answers for each step
    foreach ($_POST["student_answer"] as $stepId => $newStudentAnswer) {
        // Check if a record already exists in the "STUDENT_STEP_SOLUTION" table for this step and user
        $checkQuery = "SELECT COUNT(*) FROM student_step_solution WHERE user_id = :user_id AND step_id = :step_id AND case_id = :case_id";
        $checkStmt = oci_parse($conn, $checkQuery);
        oci_bind_by_name($checkStmt, ":user_id", $user_id);
        oci_bind_by_name($checkStmt, ":step_id", $stepId);
        oci_bind_by_name($checkStmt, ":case_id", $case_id);
        oci_execute($checkStmt);
        
        $rowC = oci_fetch_assoc($checkStmt);
        $recordExists = $rowC["COUNT(*)"] > 0;

        if ($recordExists) {
            // Update the existing record
            $updateQuery = "UPDATE student_step_solution SET student_answer = :new_student_answer WHERE user_id = :user_id AND step_id = :step_id AND case_id = :case_id";
            $updateStmt = oci_parse($conn, $updateQuery);
            oci_bind_by_name($updateStmt, ":user_id", $user_id);
            oci_bind_by_name($updateStmt, ":step_id", $stepId);
            oci_bind_by_name($updateStmt, ":case_id", $case_id);
            oci_bind_by_name($updateStmt, ":new_student_answer", $newStudentAnswer);
    
            if (oci_execute($updateStmt)) {
                echo "Student answer updated successfully for step_id: $stepId";
            } else {
                echo "Error updating student answer: " . oci_error($conn);
            }
        } else {
            // Insert a new record
            $insertQuery = "INSERT INTO student_step_solution (user_id, step_id, student_answer, case_id) VALUES (:user_id, :step_id, :new_student_answer, :case_id)";
            $insertStmt = oci_parse($conn, $insertQuery);
            oci_bind_by_name($insertStmt, ":user_id", $user_id);
            oci_bind_by_name($insertStmt, ":step_id", $stepId);
            oci_bind_by_name($insertStmt, ":case_id", $case_id);
            oci_bind_by_name($insertStmt, ":new_student_answer", $newStudentAnswer);
    
            if (oci_execute($insertStmt)) {
                echo "Student answer inserted successfully for step_id: $stepId";
            } else {
                echo "Error inserting student answer: " . oci_error($conn);
            }
        }
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_solution_description"])) {
    $username = $_SESSION["username"]; // Retrieve the username from the session
    
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
    }
    
    foreach ($stepDescriptions as $stepData) {
        $stepId = $stepData['step_id'];
    
        // Check if solution_description for this step exists in POST data
        if (isset($_POST["solution_description"][$stepId])) {
            $newSolutionDescription = $_POST["solution_description"][$stepId];
            
            // Prepare and execute the SQL statement to insert into student_step_solution
            $insertSolutionSql = "INSERT INTO student_step_solution (user_id, step_id, solution_description) VALUES (:user_id, :step_id, :solution_description)";
            $stmtInsertSolution = oci_parse($conn, $insertSolutionSql);
            oci_bind_by_name($stmtInsertSolution, ":user_id", $user_id);
            oci_bind_by_name($stmtInsertSolution, ":step_id", $stepId);
            oci_bind_by_name($stmtInsertSolution, ":solution_description", $newSolutionDescription);

            if (oci_execute($stmtInsertSolution)) {
                echo "Solution description inserted successfully for step_id: $stepId";
            } else {
                $error = oci_error($stmtInsertSolution); // Get detailed error message
                echo "Error inserting solution description for step_id: $stepId. " . $error['message'];
            }
            
        }
    }
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_answer"])) {
    // Assuming you have a database connection established
    // Get step_id and case_id from the form submission
    $step_id = $_POST["step_id"];
    $case_id = $_POST["case_id"];
    
    // Update solution_updated_time and solution_submitted_time with the current timestamp
    $updateTimeSql = "UPDATE student_step_solution 
                      SET solution_submitted_time = SYSTIMESTAMP
                      WHERE case_id = :case_id";
    
    $updateTimeStmt = oci_parse($conn, $updateTimeSql);
    oci_bind_by_name($updateTimeStmt, ":case_id", $case_id);
    
    if (oci_execute($updateTimeStmt)) {
        echo "Solution updated time and submitted time updated successfully.";
    } else {
        echo "Error updating solution: " . oci_error($conn);
    }
    
    // Iterate through the submitted student answers for each step
    foreach ($_POST["student_answer"] as $stepId => $newStudentAnswer) {
        // Check if a record already exists in the "STUDENT_STEP_SOLUTION" table for this step and user
        $checkQuery = "SELECT COUNT(*) FROM student_step_solution WHERE user_id = :user_id AND step_id = :step_id AND case_id = :case_id";
        $checkStmt = oci_parse($conn, $checkQuery);
        oci_bind_by_name($checkStmt, ":user_id", $user_id);
        oci_bind_by_name($checkStmt, ":step_id", $stepId);
        oci_bind_by_name($checkStmt, ":case_id", $case_id);
        oci_execute($checkStmt);
        
        $rowC = oci_fetch_assoc($checkStmt);
        $recordExists = $rowC["COUNT(*)"] > 0;

        // Rest of your code to update or insert student answers...
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
        .result-container {
        border-bottom: 2px solid #000; /* Black horizontal line */
        margin-bottom: 20px; /* Margin at the bottom for spacing */
        }
</style>

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
        
         // Calculate the percentage
        $percentage = 0;
        if ($totalMarks > 0) {
            $percentage = ($totalStudentMarks / $totalMarks) * 100;
        }

        // Determine the grade
        $grade = '';
        if ($percentage >= 80) {
            $grade = 'HD';
        } elseif ($percentage >= 70) {
            $grade = 'DN';
        } elseif ($percentage >= 60) {
            $grade = 'CR';
        } elseif ($percentage >= 50) {
            $grade = 'PP';
        } else {
            $grade = 'NN';
        }

    ?>
        <div class="result-container">
        <h4 style="display: inline-block; margin-right: 20px;">Total Marks: <?php echo $totalStudentMarks . '/' . $totalMarks; ?></h4>
        <h4 style="display: inline-block; margin-right: 20px;">Grade: <?php echo number_format($percentage, 2) . '%'; ?></h4>
        <h4 style="display: inline-block;"><?php echo $grade; ?></h4>
        </div><br>
    
    <?php
        // Initialize flag variables
        $submitButtonDisplayed = false;
        $solutionSubmitted = false; // Flag to check if solution_submitted_time is not null

        // Get the last step ID from the array
        $lastStepId = end($stepDescriptions)['step_id'];

        // Assuming you have a loop to iterate through steps
        foreach ($stepDescriptions as $step) {
            $stepId = $step['step_id']; // Retrieve the step_id from the array
            $stepDescription = $step['step_description']; // Retrieve the step_description from the array
            $stepDescriptionsArray[$stepId] = $stepDescription;

            // Prepare and execute a query to check if solution_submitted_time is not null
            $checkSolutionSubmittedQuery = "SELECT COUNT(*) as solution_count FROM student_step_solution WHERE step_id = :step_id AND case_id = :case_id AND solution_submitted_time IS NOT NULL";

            $stmtCheckSolutionSubmitted = oci_parse($conn, $checkSolutionSubmittedQuery);
            oci_bind_by_name($stmtCheckSolutionSubmitted, ":step_id", $stepId);
            oci_bind_by_name($stmtCheckSolutionSubmitted, ":case_id", $_GET['case_id']); // Bind case_id directly from $_GET
            oci_execute($stmtCheckSolutionSubmitted);

            $row = oci_fetch_assoc($stmtCheckSolutionSubmitted);
            $isSolutionSubmitted = $row['SOLUTION_COUNT'] > 0;
            oci_free_statement($stmtCheckSolutionSubmitted);

            // Set the $solutionSubmitted flag to true if solution_submitted_time is not null
            if ($isSolutionSubmitted) {
                $solutionSubmitted = true;
            }

            // Prepare the SQL query to fetch individual_step_mark from student_step_solution table
            $getIndividualStepMarkQuery = "SELECT individual_step_mark FROM student_step_solution WHERE step_id = :step_id AND case_id = :case_id";

            // Prepare the statement
            $stmtGetIndividualStepMark = oci_parse($conn, $getIndividualStepMarkQuery);

            // Bind parameters
            oci_bind_by_name($stmtGetIndividualStepMark, ":step_id", $stepId);
            oci_bind_by_name($stmtGetIndividualStepMark, ":case_id", $_GET['case_id']);

            // Execute the query
            oci_execute($stmtGetIndividualStepMark);

            // Fetch the individual_step_mark from the result
            if ($rowSt = oci_fetch_assoc($stmtGetIndividualStepMark)) {
                $individual_step_mark = $rowSt['INDIVIDUAL_STEP_MARK'];
            }

            // Fetch the current answer value for the step
            $currentStAnswer = ''; // Initialize with an empty string
            $fetchStAnswerSql = "SELECT student_answer FROM student_step_solution WHERE step_id = :step_id";
            $fetchStAnswerStmt = oci_parse($conn, $fetchStAnswerSql);
            oci_bind_by_name($fetchStAnswerStmt, ":step_id", $stepId);
            oci_execute($fetchStAnswerStmt);
            $row = oci_fetch_assoc($fetchStAnswerStmt);
            if ($row) {
                $currentStAnswer = $row['STUDENT_ANSWER'];
            }

            $sessionKey = "student_answer_$stepId";
            $studentAnswer = isset($_SESSION[$sessionKey]) ? $_SESSION[$sessionKey] : ''; // Get the stored student answer

            echo '<form method="post" action="" id="form' . $stepId . '">';
            echo '<input type="hidden" name="step_id" value="' . $stepId . '">';
            echo '<input type="hidden" name="case_id" value="' . $_GET['case_id'] . '">'; // Hidden input for case_id
            echo '<p>' . $stepDescription . '</p>';
            echo '<textarea name="student_answer[' . $stepId . ']" rows="4" cols="100" ' . ($isSolutionSubmitted ? 'readonly' : '') . '>' . $currentStAnswer . '</textarea><br>';
            echo 'Individual Step Mark: ' . $individual_step_mark . '<br>';
            echo '<input type="submit" name="save_answers" value="Save Answers" ' . ($isSolutionSubmitted ? 'disabled' : '') . '><br><br>';

            // Check if the current step is the last one
            if ($stepId === $lastStepId) {
                echo '<br><br><input type="submit" name="submit_answer" value="Submit" ' . ($isSolutionSubmitted ? 'disabled' : '') . '>';
                // Set the flag variable to true to indicate that the Submit button has been displayed
                $submitButtonDisplayed = true;
            }

            echo '</form>';

        }

        //Show Solutions button at the end of the page, enable after the answer submitted
        echo '<br><button type="button" onclick="sendAllDescriptions();" ' . ($solutionSubmitted ? '' : 'disabled') . '>Show Solutions</button>';
    ?>



<script>
function sendAllDescriptions() {
    var requestData = [];
    
    <?php foreach ($stepDescriptionsArray as $stepId => $stepDescription) { ?>
        var StudentAnswer = document.forms['form<?php echo $stepId; ?>']['student_answer[<?php echo $stepId; ?>]'].value;
        var description = <?php echo json_encode($stepDescription); ?>;
        var stepId = <?php echo $stepId; ?>;
        requestData.push({ step_id: stepId, step_description: description, student_answer: StudentAnswer });
    <?php } ?>

    $.ajax({
        type: 'POST',
        url: 'http://localhost:5000/process_data',  // Update with your Flask API endpoint
        contentType: 'application/json',  // Set content type to JSON
        data: JSON.stringify(requestData),  // Convert data to JSON string
        success: function(response) {
            console.log("Response received:", response);
            for (var stepId in response) {
                if (response.hasOwnProperty(stepId)) {
                    // Assuming you have an element with ID 'solution_description' + stepId
                    document.getElementById('solution_description' + stepId).value = response[stepId];
                }
            }
        },
        error: function(xhr, status, error) {
            console.error("Error occurred: " + error);
        }
    });
}

</script>
</main>



<main>
    <h1>&nbsp;Solution</h1><br><br><br>
    <?php
    foreach ($stepDescriptions as $step) {
        $stepId = $step['step_id']; // Retrieve the step_id from the array
        $stepDescription = $step['step_description']; // Retrieve the step_description from the array
        $stepDescriptionsArray[$stepId] = $stepDescription;

        $sessionKey = "solutionDescription_$stepId";
        $solutionDescription = isset($_SESSION[$sessionKey]) ? $_SESSION[$sessionKey] : ''; // Get the stored solution description

        echo '<form method="post" action="" id="form' . $stepId . '">';
        echo '<input type="hidden" name="step_id" value="' . $stepId . '">';
        echo '<p>' . $stepDescription . '</p>';
        echo '<textarea id="solution_description' . $stepId . '" 
        name="solution_description[' . $stepId . ']" rows="4" cols="100">' . $solutionDescription . '</textarea><br><br><br>';
        echo '</form>';
    }
    ?>
</main>







</div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
</body>
</html>
<!DOCTYPE html>
