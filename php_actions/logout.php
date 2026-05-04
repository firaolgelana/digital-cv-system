<?php
// ============================================================
//  Logout — destroys session and redirects to login page
// ============================================================
require_once __DIR__ . '/../includes/auth.php';

startSession();
logout();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="refresh" content="0;url=../index.html" />
  <title>Signing out...</title>
</head>
<body>
<script>
  window.localStorage.removeItem("digicv_current_user");
  window.localStorage.removeItem("digicv_student_cv");

  Object.keys(window.localStorage)
    .filter((key) => key.indexOf("digicv_student_cv:") === 0)
    .forEach((key) => window.localStorage.removeItem(key));

  window.location.replace("../index.html");
</script>
</body>
</html>
