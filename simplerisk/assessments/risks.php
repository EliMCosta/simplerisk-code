<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
* License, v. 2.0. If a copy of the MPL was not distributed with this
* file, You can obtain one at http://mozilla.org/MPL/2.0/. */

// Include required functions file
require_once(realpath(__DIR__ . '/../includes/functions.php'));
require_once(realpath(__DIR__ . '/../includes/authenticate.php'));
require_once(realpath(__DIR__ . '/../includes/display.php'));
require_once(realpath(__DIR__ . '/../includes/assessments.php'));
require_once(realpath(__DIR__ . '/../includes/alerts.php'));
require_once(realpath(__DIR__ . '/../vendor/autoload.php'));

// Include Laminas Escaper for HTML Output Encoding
$escaper = new Laminas\Escaper\Escaper('utf-8');

// Add various security headers
add_security_headers();

// Add the session
$permissions = array(
        "check_access" => true,
        "check_assessments" => true,
);
add_session_check($permissions);

// Include the CSRF Magic library
include_csrf_magic();

// Include the SimpleRisk language file
require_once(language_file());

// Check if we should add a pending risk
if (isset($_POST['add']))
{
  // Push the pending risk to a real risk
  push_pending_risk();
}

// Check if we should delete a pending risk
if (isset($_POST['delete']))
{
  // Get the risk id to delete
  $pending_risk_id = (int)$_POST['pending_risk_id'];

  // Delete the pending risk
  delete_pending_risk($pending_risk_id);

  // Set the alert message
  set_alert(true, "good", "The pending risk was deleted successfully.");
}
?>

<!doctype html>
<html lang="<?php echo $escaper->escapehtml($_SESSION['lang']); ?>" xml:lang="<?php echo $escaper->escapeHtml($_SESSION['lang']); ?>">

<head>
  <script src="../js/jquery.min.js"></script>
  <script src="../js/jquery-ui.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>
  <script src="../js/pages/assessment.js"></script>
  <script src="../js/common.js"></script>
  <script src="../js/cve_lookup.js"></script>
  <script src="../js/jquery.blockUI.min.js"></script>
  <title>SimpleRisk: Enterprise Risk Management Simplified</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
  <link rel="stylesheet" href="../css/bootstrap.css">
  <link rel="stylesheet" href="../css/bootstrap-responsive.css">

  <link rel="stylesheet" href="../css/divshot-util.css">
  <link rel="stylesheet" href="../css/divshot-canvas.css">
  <link rel="stylesheet" href="../css/display.css">
  <link rel="stylesheet" href="../vendor/fortawesome/font-awesome/css/fontawesome.min.css">
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/side-navigation.css">
  <link rel="stylesheet" href="../css/selectize.bootstrap3.css">
  <script src="../js/selectize.min.js"></script>
  <?php
      setup_favicon("..");
      setup_alert_requirements("..");
  ?>
</head>

<body>

  <?php
  view_top_menu("Assessments");

  // Get any alerts
  get_alert();
  ?>
  <div class="container-fluid">
    <div class="row-fluid">
      <div class="span3">
        <?php view_assessments_menu("PendingRisks"); ?>
      </div>
      <div class="span9">
        <?php display_pending_risks(); ?>
      </div>
    </div>
  </div>
    <?php display_set_default_date_format_script(); ?>
</body>

</html>
