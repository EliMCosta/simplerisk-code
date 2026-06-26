<?php
	/* This Source Code Form is subject to the terms of the Mozilla Public
	* License, v. 2.0. If a copy of the MPL was not distributed with this
	* file, You can obtain one at http://mozilla.org/MPL/2.0/. */

	// Render the header and sidebar
	require_once(realpath(__DIR__ . '/../includes/renderutils.php'));
	render_header_and_sidebar(['tabs:logic', 'multiselect', 'datetimerangepicker', 'CUSTOM:common.js'], ['check_admin' => true], 'CustomizationExtra', 'Configure', 'Extras');

	// If the extra directory exists
	if (is_dir(realpath(__DIR__ . '/../extras/customization'))) {

		// Include the Customization Extra
		require_once(realpath(__DIR__ . '/../extras/customization/index.php'));

		// If the user wants to activate the extra
		if (isset($_POST['activate'])) {

			// Enable the Customization Extra
			enable_customization_extra();
			
			refresh();
		
		// If the user wants to deactivate the extra
		} else if (isset($_POST['deactivate'])) {
			
			// Disable the Customization Extra
			disable_customization_extra();
			
			refresh();
		
		// If the user wants to deactivate the extra
		} else if (isset($_POST['restore'])) {

			$fgroup = get_param("POST", "fgroup", "risk");
			$template_group_id = get_param("POST", "template_group_id", "1");

			// Set default main fields
			set_default_main_fields($fgroup, $template_group_id);

			refresh();
			
		// If user wants to update custom field
		} else if (isset($_POST['update-custom-field'])) {

			$id = get_param("POST", "id");
			$name = get_param("POST", "name");
			$required = get_param("POST", "required", 0);
			$encryption = get_param("POST", "encryption", 0);
			$alphabetical_order = get_param("POST", "alphabetical_order", 0);
			$template_group_id = get_param("POST", "template_group_id", "");
			$row_id = get_param("POST", "row_id", 0);
			$panel_name = get_param("POST", "panel_name", "left");
			$ordering = get_param("POST", "ordering", 0);

			if (!$id || !$name) {

				// Display an alert
				set_alert(true, "bad", $escaper->escapeHtml($lang['TheNameFieldIsRequired']));

			} else {

				if (update_custom_field($id, $name, $required, $encryption, $alphabetical_order)) {

					// Persist the column (panel) + ordering for this group.
					if ($template_group_id && $row_id) {
						update_template_field_placement($template_group_id, $row_id, $panel_name, $ordering);
					}

					$_SESSION['custom_field_id'] = (int)$id;

					set_alert(true, "good", $escaper->escapeHtml($lang['SuccessfullyUpdatedCustomField']));

				}
			}

			refresh();
		
		// Check if creating field was submitted
		} else if (isset($_POST['create_field'])) {

			$fgroup = $_POST['fgroup'];
			$name = $_POST['name'];
			$type = $_POST['type'];
			$required = isset($_POST['required']) ? 1 : 0;
			$encryption = isset($_POST['encryption']) ? 1 : 0;
			$alphabetical_order = isset($_POST['alphabetical_order']) ? 1 : 0;
			$panel_name = get_param("POST", "panel_name", "left");

			// Create the new field
			if ($field_id = create_field($fgroup, $name, $type, $required, $encryption, $alphabetical_order, $panel_name)) {

				// Set field_id as Session variable for auto select of custom fields dropdown
				$_SESSION['custom_field_id'] = $field_id;
				
				// Audit log
				$risk_id = 1000;
				$message = "A custom field named \"" . $name . "\" was added by the \"" . $_SESSION['user'] . "\" user.";
				write_log($risk_id, $_SESSION['uid'], $message);

				// Display an alert
				set_alert(true, "good", "The new custom field was created successfully.");

			}
			
			refresh();
		
		// If add template group submitted
		} else if (isset($_POST['add_template_group'])) {

			$name = get_param("POST", "name");
			$fgroup = get_param("POST", "fgroup", "risk");
			$old_group = get_custom_template_group_by_name($name,$fgroup);

			if (!$name) {

				// Display an alert
				set_alert(true, "bad", $escaper->escapeHtml($lang['TheNameFieldIsRequired']));

			} else if ($old_group) { 

				set_alert(true, "bad", $escaper->escapeHtml($lang['TheNameAlreadyExists']));

			} else {

				add_custom_template_group($name, $fgroup);
				set_alert(true, "good", $escaper->escapeHtml($lang['AddedSuccess']));

			}

			refresh();
			
		// If update template group submitted
		} else if (isset($_POST['update_template_group'])) {

			$id = get_param("POST", "id");
			$name = get_param("POST", "name");
			$fgroup = get_param("POST", "fgroup", "risk");
			$old_group = get_custom_template_group_by_name($name,$fgroup);

			if (!$id || !$name) {

				// Display an alert
				set_alert(true, "bad", $escaper->escapeHtml($lang['TheNameFieldIsRequired']));

			} else if ($old_group && (int)$old_group['id'] !== (int)$id) { 
				
				set_alert(true, "bad", $escaper->escapeHtml($lang['TheNameAlreadyExists']));

			} else {

				update_custom_template_group($id, $name);
				set_alert(true, "good", $escaper->escapeHtml($lang['SavedSuccess']));

			}

			refresh();
			
		// If set active template group submitted
		} else if (isset($_POST['set_active_template_group'])) {

			$id = get_param("POST", "id");
			$fgroup = get_param("POST", "fgroup", "risk");

			if (!$id) {

				set_alert(true, "bad", $escaper->escapeHtml($lang['YouNeedToSpecifyAnIdParameter']));

			} else if (set_active_template_group($id, $fgroup)) {

				set_alert(true, "good", $escaper->escapeHtml($lang['SavedSuccess']));

			} else {

				set_alert(true, "bad", $escaper->escapeHtml($lang['FailedToUpdateItem']));

			}

			refresh();

		// If delete template group submitted
		} else if (isset($_POST['delete_template_group'])) {

			$id = get_param("POST", "custom_template_group");

			if (!$id) {

				// Display an alert
				set_alert(true, "bad", $escaper->escapeHtml($lang['YouNeedToSpecifyAnIdParameter']));

			} else {

				delete_custom_template_group($id);
				set_alert(true, "good", $escaper->escapeHtml($lang['DeletedSuccess']));

			}

			refresh();

		// Save built-in field layout (show/hide + column + order)
		} else if (isset($_POST['update_field_layout'])) {

			$template_group_id = get_param("POST", "template_group_id", "1");
			$bf = get_param("POST", "bf", []);

			update_basic_field_layout($template_group_id, $bf);

			set_alert(true, "good", $escaper->escapeHtml("Field layout saved."));

			refresh();

		// Delete a custom field
		} else if (isset($_POST['delete-custom-field'])) {

			$id = get_param("POST", "id");

			if (!$id) {

				set_alert(true, "bad", $escaper->escapeHtml($lang['YouNeedToSpecifyAnIdParameter']));

			} else {

				delete_custom_field($id);

				set_alert(true, "good", $escaper->escapeHtml("Custom field deleted."));

			}

			refresh();

		}
	}

	/*********************
	 * FUNCTION: DISPLAY *
	 *********************/
	// @phan-suppress-next-line PhanRedefineFunction -- each admin page defines its own display() entry point
	function display($display = "") {

		global $lang;
		global $escaper;

		// If the extra directory exists
		if (is_dir(realpath(__DIR__ . '/../extras/customization'))) {

			// If the extra is not activated
			if (!customization_extra()) {

				echo "
					<div class='card-body my-2 border'>
				";

				// If the extra is not restricted based on the install type
				if (!restricted_extra("customization")) {
					echo "
						<div class='hero-unit'>
							<form name='activate_extra' method='post' action=''>
								<input type='submit' value='{$escaper->escapeHtml($lang['Activate'])}' name='activate' class='btn btn-submit'/>
							</form>
						</div>
					";
					
				// The extra is restricted
				} else {
					echo $escaper->escapeHtml($lang['YouNeedToUpgradeYourSimpleRiskSubscription']);
				}

				echo "
					</div>
				";
				
			// Once it has been activated
			} else {

				// Include the Customizaton Extra
				require_once(realpath(__DIR__ . '/../extras/customization/index.php'));

					display_customization();

			}
			
		// Otherwise, the Extra does not exist
		} else {
			echo "
					<div class='card-body my-2 border'>
						<a href='https://www.simplerisk.com/extras' target='_blank' class='text-info'>Purchase the Extra</a>
					</div>
			";
		}
	}
?>
<div class="row bg-white"> 
	<div class="col-12">
	<?php 
		display(); 
	?>
	</div>
</div>
<script>
	<?php prevent_form_double_submit_script(); ?>
</script>
<?php
	// Render the footer of the page. Please don't put code after this part.
	render_footer();
?>