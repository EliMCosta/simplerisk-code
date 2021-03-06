<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
* License, v. 2.0. If a copy of the MPL was not distributed with this
* file, You can obtain one at http://mozilla.org/MPL/2.0/. */

// Include required functions file
require_once(realpath(__DIR__ . '/../includes/functions.php'));
require_once(realpath(__DIR__ . '/../includes/authenticate.php'));
require_once(realpath(__DIR__ . '/../includes/display.php'));
require_once(realpath(__DIR__ . '/../includes/reporting.php'));

// Include Zend Escaper for HTML Output Encoding
require_once(realpath(__DIR__ . '/../includes/Component_ZendEscaper/Escaper.php'));
$escaper = new Zend\Escaper\Escaper('utf-8');

// Add various security headers
add_security_headers();

// Add the session
add_session_check();

// Include the CSRF Magic library
include_csrf_magic();

// Include the SimpleRisk language file
require_once(language_file());

// Record the page the workflow started from as a session variable
$_SESSION["workflow_start"] = $_SERVER['SCRIPT_NAME'];

// Set the status
if (isset($_POST['status']))
{
    $status = (int)$_POST['status'];
}
else if (isset($_GET['status']))
{
    $status = (int)$_GET['status'];
}
else $status = 0;

// Set the group
if (isset($_POST['group']))
{
    $group = (int)$_POST['group'];
}
else if (isset($_GET['group']))
{
    $group = (int)$_GET['group'];
}
else $group = 0;

// Set the sort
if (isset($_POST['sort']))
{
    $sort = (int)$_POST['sort'];
}
else if (isset($_GET['sort']))
{
    $sort = (int)$_GET['sort'];
}
else $sort = 0;

// Set the Tags
    $tags_filter = get_param("REQUEST", "tags_filter", []);
    if(!is_array($tags_filter)) $tags_filter = [$tags_filter];
    $tag_ids = array_map("base64_encode", $tags_filter);

// Set the locations
    $locations_filter = get_param("REQUEST", "locations_filter", []);
    if(!is_array($locations_filter)) $locations_filter = [$locations_filter];
    $location_ids = array_map("base64_encode", $locations_filter);

// Names list of Risk columns
$columns = array(
    'id',
    'risk_status',
    'subject',
    'reference_id',
    'regulation',
    'control_number',
    'location',
    'source',
    'category',
    'team',
    'additional_stakeholders',
    'technology',
    'owner',
    'manager',
    'submitted_by',
    'scoring_method',
    'calculated_risk',
    'residual_risk',
    'submission_date',
    'review_date',
    'project',
    'mitigation_planned',
    'management_review',
    'days_open',
    'next_review_date',
    'next_step',
    'affected_assets',
    'planning_strategy',
    'planning_date',
    'mitigation_effort',
    'mitigation_cost',
    'mitigation_owner',
    'mitigation_percent',
    'mitigation_team',
    'mitigation_accepted',
    'mitigation_date',
    'mitigation_controls',
    'risk_assessment',
    'additional_notes',
    'current_solution',
    'security_recommendations',
    'security_requirements',
    'risk_tags',
    'closure_date',
    'comments'
);

$custom_values = [];
$custom_selection_settings = "";
$custom_column_filters = "";
if(!empty($_GET['selection']))
{
    $selection_id = (int)$_GET['selection'];
    $selection = get_dynamic_saved_selection($selection_id);
    
    if($selection['type'] == 'private' && $selection['user_id'] != $_SESSION['uid'])
    {
        set_alert(true, "bad", $escaper->escapeHtml($lang['NoPermissionForThisSelection']));
        refresh("/reports/dynamic_risk_report.php");
    }
    else
    {
        if($selection['custom_display_settings'])
        {
            $custom_display_settings = json_decode($selection['custom_display_settings'], true);
        }
        else
        {
            $custom_display_settings = "";
        }
        if($selection['custom_selection_settings'])
        {
            $custom_selection_settings = json_decode($selection['custom_selection_settings'], true);
        }
        if($selection['custom_column_filters'])
        {
            $custom_column_filters = $selection['custom_column_filters'];
        }
    }
}
else
{
    $custom_display_settings = $_SESSION['custom_display_settings'];
}

if(is_array($custom_display_settings) && !isset($_POST['status'])){
    foreach($columns as $column){
        ${$column} = in_array($column, $custom_display_settings) ? true : false;
    }
    // Set default display settings if no selected columns
    if(!count($custom_display_settings)){
        $id = true;
        $subject = true;
        $calculated_risk = true;
        $residual_risk = true;
        $submission_date = true;
        $mitigation_planned = true;
        $management_review = true;
    }
    foreach($custom_display_settings as $custom_display_setting){
        if(stripos($custom_display_setting, "custom_field_") === 0){
            $custom_values[$custom_display_setting] = 1;
        }
    }
}elseif(isset($_POST['status'])){
    foreach($columns as $column){
        ${$column} = isset($_POST[$column]) ? true : false;
    }
    foreach($_POST as $key=>$val){
        if(stripos($key, "custom_field_") === 0){
            $custom_values[$key] = 1;
        }
    }
}else{
    $id = true;
    $subject = true;
    $calculated_risk = true;
    $residual_risk = true;
    $submission_date = true;
    $mitigation_planned = true;
    $management_review = true;
}
if(is_array($custom_selection_settings)){
    foreach($custom_selection_settings as $select=>$custom_selection_setting){
        if(!isset($_POST[$select])) ${$select} = $custom_selection_setting;
    }
}

// If there was not a POST
//if (!isset($_POST['status']))
//{
    // Set the default fields to show
//    $id = true;
//    $subject = true;
//    $calculated_risk = true;
//    $submission_date = true;
//    $mitigation_planned = true;
//    $management_review = true;
//}

// Once it has been activated
if (import_export_extra()){
    // Include the Import-Export Extra
    require_once(realpath(__DIR__ . '/../extras/import-export/index.php'));
    
    // if download request, download all risks
    if (isset($_POST['status']) && isset($_GET['option']) && $_GET['option'] == "download")
    {
        $column_filters = isset($_GET["column_filters"])?$_GET["column_filters"]:[];
        $order_column = isset($_GET["order_column"])?$_GET["order_column"]:null;
        $order_dir = isset($_GET["order_dir"])?$_GET["order_dir"]:"asc";
        download_risks_by_table($status, $group, $sort, NULL, $id, $risk_status, $subject, $reference_id, $regulation, $control_number, $location, $source, $category, $team, $additional_stakeholders, $technology, $owner, $manager, $submitted_by, $scoring_method, $calculated_risk, $residual_risk, $submission_date, $review_date, $project, $mitigation_planned, $management_review, $days_open, $next_review_date, $next_step, $affected_assets, $planning_strategy, $planning_date, $mitigation_effort, $mitigation_cost, $mitigation_owner, $mitigation_percent, $mitigation_team, $mitigation_accepted, $mitigation_date, $mitigation_controls, $risk_assessment, $additional_notes, $current_solution, $security_recommendations, $security_requirements, $risk_tags, $closure_date, $comments, $custom_values, $column_filters, $order_column, $order_dir);
    }

    // if group download request, download risks by the group
    if(isset($_GET['option']) && $_GET['option'] == "download-by-group")
    {
        $group_value = $_GET['group_value'];
        $column_filters = isset($_GET["column_filters"])?$_GET["column_filters"]:[];
        $order_column = isset($_GET["order_column"])?$_GET["order_column"]:null;
        $order_dir = isset($_GET["order_dir"])?$_GET["order_dir"]:"asc";
        download_risks_by_table($status, $group, $sort, $group_value, $id, $risk_status, $subject, $reference_id, $regulation, $control_number, $location, $source, $category, $team, $additional_stakeholders, $technology, $owner, $manager, $submitted_by, $scoring_method, $calculated_risk, $residual_risk, $submission_date, $review_date, $project, $mitigation_planned, $management_review, $days_open, $next_review_date, $next_step, $affected_assets, $planning_strategy, $planning_date, $mitigation_effort, $mitigation_cost, $mitigation_owner, $mitigation_percent, $mitigation_team, $mitigation_accepted, $mitigation_date, $mitigation_controls, $risk_assessment, $additional_notes, $current_solution, $security_recommendations, $security_requirements, $risk_tags, $closure_date, $comments, $custom_values, $column_filters, $order_column, $order_dir);
    }
}


?>
<!doctype html>
<html lang="<?php echo $escaper->escapehtml($_SESSION['lang']); ?>" xml:lang="<?php echo $escaper->escapeHtml($_SESSION['lang']); ?>">

<head>
  <script src="../js/jquery.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>
  <script src="../js/sorttable.js"></script>
  <script src="../js/obsolete.js"></script>
  <script src="../js/jquery.dataTables.js"></script>
  <script src="../js/dynamic.js"></script>
  <script src="../js/common.js"></script>
  <script src="../js/bootstrap-multiselect.js"></script>
  <title>SimpleRisk: Enterprise Risk Management Simplified</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
  <link rel="stylesheet" href="../css/bootstrap.css">
  <link rel="stylesheet" href="../css/bootstrap-responsive.css">
  <link rel="stylesheet" href="../css/jquery.dataTables.css">
  
  <link rel="stylesheet" href="../css/divshot-canvas.css">
  <link rel="stylesheet" href="../bower_components/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/side-navigation.css">

  <?php
      setup_favicon("..");
      setup_alert_requirements("..");
  ?>  
</head>
<style>
   .dataTables_scrollHead, .dataTables_scrollBody {overflow: visible !important;}
   .dataTables_scroll {overflow: auto !important;}
</style>
<body>

  <?php view_top_menu("Reporting"); ?>

  <div class="container-fluid">
    <div class="row-fluid">
      <div class="span3">
        <?php view_reporting_menu("DynamicRiskReport"); ?>
      </div>
      <div class="span9">
        <?php
            get_alert();
        ?>        
        <div class="row-fluid">
          <div id="selections" class="span12">
            <div class="well">
                <div id="selection-container">
                  <?php view_get_risks_by_selections($status, $group, $sort, $id, $risk_status, $subject, $reference_id, $regulation, $control_number, $location, $source, $category, $team, $additional_stakeholders, $technology, $owner, $manager, $submitted_by, $scoring_method, $calculated_risk, $residual_risk, $submission_date, $review_date, $project, $mitigation_planned, $management_review, $days_open, $next_review_date, $next_step, $affected_assets, $planning_strategy, $planning_date, $mitigation_effort, $mitigation_cost, $mitigation_owner, $mitigation_percent, $mitigation_team, $mitigation_accepted, $mitigation_date, $mitigation_controls, $risk_assessment, $additional_notes, $current_solution, $security_recommendations, $security_requirements, $risk_tags, $closure_date, $comments, $custom_values); ?>
                </div>
                <div id="save-container">
                    <?php
                        display_save_dynamic_risk_selections();
                    ?>
                </div>
            </div>
          </div>
        </div>
        <div class="row-fluid bottom-offset-10">
            <div class="span6 text-left top-offset-15">
                <button class="expand-all"><?php echo $lang['ExpandAll'] ?></button>
            </div>
            <?php
            // If the Import-Export Extra is installed
            if (is_dir(realpath(__DIR__ . '/../extras/import-export')))
            {
                // And the Extra is activated
                if (import_export_extra())
                {
                    // Include the Import-Export Extra
                    require_once(realpath(__DIR__ . '/../extras/import-export/index.php'));
                    // Display the download link
                    display_download_link();
                }
            }
            ?>
        </div>

        <div class="row-fluid">
          <div class="span12">
            <div id="risk-table-container">
                <?php get_risks_by_table($status, $sort, $group, $id, $risk_status, $subject, $reference_id, $regulation, $control_number, $location, $source, $category, $team, $additional_stakeholders, $technology, $owner, $manager, $submitted_by, $scoring_method, $calculated_risk, $residual_risk, $submission_date, $review_date, $project, $mitigation_planned, $management_review, $days_open, $next_review_date, $next_step, $affected_assets, $planning_strategy, $planning_date, $mitigation_effort, $mitigation_cost, $mitigation_owner, $mitigation_percent, $mitigation_team, $mitigation_accepted, $mitigation_date, $mitigation_controls, $risk_assessment, $additional_notes, $current_solution, $security_recommendations, $security_requirements, $risk_tags, $closure_date, $comments, $custom_values); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <input type="hidden" id="hidden_location_filters" value="<?php echo implode(",", $location_ids); ?>">
  <input type="hidden" id="hidden_tag_filters" value="<?php echo implode(",", $tag_ids); ?>">
  <input type="hidden" id="unassigned_option" value="<?php echo $escaper->escapeHtml($lang["Unassigned"]);?>">
  <input type="hidden" id="date_format" value="<?php echo $escaper->escapeHtml(get_setting("default_date_format"));?>">
  <input type="hidden" id="custom_column_filters" value="<?php echo $escaper->escapeHtml($custom_column_filters);?>">
</body>

</html>
