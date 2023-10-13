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