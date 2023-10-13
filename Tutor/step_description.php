<?php
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

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_step_mark'])) {
    // Get step_id and step_mark from the form submission
    $stepId = $_POST['step_id'];
    $stepMark = $_POST['step_mark'];

    // SQL query to update step_mark in the step table based on step_id
    $updateStepMarkQuery = "UPDATE step SET step_mark = :step_mark WHERE step_id = :step_id";

    // Prepare the SQL statement
    $stmt = oci_parse($conn, $updateStepMarkQuery);

    // Bind the parameters
    oci_bind_by_name($stmt, ':step_mark', $stepMark);
    oci_bind_by_name($stmt, ':step_id', $stepId);

    // Execute the statement
    if (oci_execute($stmt)) {
        echo "Step Mark updated successfully!";
    } else {
        echo "Error updating Step Mark: " . oci_error($stmt)['message'];
    }

    // Free the statement
    oci_free_statement($stmt);
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

        // Output the total marks
        echo '<h4>Total Marks: ' . $totalMarks . '</h4><br><br>';
    ?>




    <form method="post" action="">
    <h4>Add a New Description:</h4>
    <input type="hidden" name="case_id" value="<?php echo $_GET['case_id']; ?>">
    <textarea name="step_description" rows="8" cols="150"><?php echo $currentDescription; ?></textarea>
    <br>
    <input type="submit" name="save" value="Save">
    <br><br>
    </form>

    <?php
    
    foreach ($stepDescriptions as $step) {
        $stepId = $step['step_id']; // Retrieve the step_id from the array
        $currentAnswer = ''; // Fetch current answer logic remains the same as in your original PHP code
    
        // Fetch the current step_description value for the step
        $currentDescription = ''; // Initialize with an empty string
        $fetchDescriptionSql = "SELECT step_description FROM step WHERE step_id = :step_id";
        $fetchDescriptionStmt = oci_parse($conn, $fetchDescriptionSql);
        oci_bind_by_name($fetchDescriptionStmt, ":step_id", $stepId);
        oci_execute($fetchDescriptionStmt);
        $row = oci_fetch_assoc($fetchDescriptionStmt);
        if ($row) {
            $currentDescription = $row['STEP_DESCRIPTION'];
        }

        // Fetch the current step_mark value for the step
        $currentMark = ''; // Initialize with an empty string
        $fetchMarkSql = "SELECT step_mark FROM step WHERE step_id = :step_id";
        $fetchMarketStmt = oci_parse($conn, $fetchMarkSql);
        oci_bind_by_name($fetchMarketStmt, ":step_id", $stepId);
        oci_execute($fetchMarketStmt);
        $row = oci_fetch_assoc($fetchMarketStmt);
        if ($row) {
            $currentMark = $row['STEP_MARK'];
        }

        // Fetch the current answer value for the step
        $currentAnswer = ''; // Initialize with an empty string
        $fetchAnswerSql = "SELECT answer FROM step WHERE step_id = :step_id";
        $fetchAnswerStmt = oci_parse($conn, $fetchAnswerSql);
        oci_bind_by_name($fetchAnswerStmt, ":step_id", $stepId);
        oci_execute($fetchAnswerStmt);
        $row = oci_fetch_assoc($fetchAnswerStmt);
        if ($row) {
            $currentAnswer = $row['ANSWER'];
        }
    
        echo '<form method="post" action="" id="form' . $stepId . '">';
        echo '<input type="hidden" name="step_id" value="' . $stepId . '">';
        echo '<h5>Step Description:</h5><textarea name="step_description" rows="10" cols="150">' . $currentDescription . '</textarea><br>';
        echo '<input type="submit" name="save_step_description" value="Save Description"><br><br>';
         // Input field for STEP_MARK
        echo '<h5>Step Mark:</h5>';
        echo '<input type="number" name="step_mark" id="step_mark' . $stepId . '" value="' . $currentMark . '"><br>'; 
        echo '<input type="submit" name="save_step_mark" value="Save Mark"><br><br>';
        echo '<h5>Answer:</h5><textarea name="answer" id="answer' . $stepId . '" rows="10" cols="180">' . $currentAnswer . '</textarea><br>';
        echo '<input type="submit" value="Send to OpenAI" onclick="submitForm(' . $stepId . '); return false;" style="margin-right: 20px;">';
        echo '<input type="submit" name="save_answer" value="Save Answer"><br><br><br>';

        echo '</form>';
    }
    ?>
    <script>
    function submitForm(stepId) {
    console.log("Submitting form for stepId: " + stepId);
    var formId = 'form' + stepId;
    var stepDescription = document.forms[formId]['step_description'].value;
    console.log("Step description: " + stepDescription);

    $.ajax({
        type: 'POST',
        url: 'http://localhost:5000/process_data',  // Update with your Flask API endpoint
        data: {step_description: stepDescription, step_id: stepId},
        success: function(response) {
            console.log("Response received: " + response.generated_answer);
            document.getElementById('answer' + stepId).value = response.generated_answer;
        },
        error: function(xhr, status, error) {
            console.error("Error occurred: " + error);
        }
    });
}

</script>
</main>

<main>
        <h1>&nbsp;Student Management</h1><br><br><br>
    <table style="width:100%;height:300px;text-align: center"> 
    <tr>
        <th></th>
        <th class="sortable" data-sort="first_name">First name</th>
        <th class="sortable" data-sort="last_name">Second name</th>
        <th class="sortable" data-sort="username">Username</th>
        <th class="sortable" data-sort="updated_at">Updated at</th>
        <th class="sortable" data-sort="status">Status</th>
    </tr>
        
    <?php
        foreach ($userDetails as $user) {
            // Check if the user's role is "student" before displaying
            if ($user['ROLE'] == 'Student') {
                // Get user_id from the current $user array
                $user_id = $user['USER_ID'];

                $getTimeQuery = "SELECT solution_updated_time, solution_submitted_time FROM student_step_solution WHERE user_id = :user_id AND case_id = :case_id";
                $getTimeStmt = oci_parse($conn, $getTimeQuery);
                // Bind both user_id and case_id parameters
                oci_bind_by_name($getTimeStmt, ":user_id", $user_id);
                oci_bind_by_name($getTimeStmt, ":case_id", $_GET['case_id']); // Assuming case_id is sent in the URL
                oci_execute($getTimeStmt);


                // Initialize solution_updated_time and solution_submitted_time
                $solution_updated_time = "N/A";
                $solution_submitted_time = "N/A";

                // Fetch the solution_updated_time and solution_submitted_time if a row is fetched
                if ($row = oci_fetch_assoc($getTimeStmt)) {
                    $solution_updated_time = $row['SOLUTION_UPDATED_TIME'];
                    $solution_submitted_time = $row['SOLUTION_SUBMITTED_TIME'];
                }

                // Determine the status based on solution_submitted_time
                $status = ($solution_submitted_time != "N/A") ? "Submitted" : "Pending";

                echo "<tr style='border:1px solid'>";
                echo "<td></td>"; 
                echo "<td>{$user['FIRST_NAME']}</td>";
                echo "<td>{$user['LAST_NAME']}</td>";
                echo "<td>{$user['USERNAME']}</td>";
                echo "<td>{$solution_updated_time}</td>"; // Display solution_updated_time
                echo "<td>{$status}</td>"; // Display status (Submitted or Pending)
                echo "<td><a href='mark.php?username={$user['USERNAME']}&case_id=" . urlencode($_GET['case_id']) . "' class='buttonB' style='border: 2px solid black'>Enter</a></td>";
                echo "</tr>";
            }
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