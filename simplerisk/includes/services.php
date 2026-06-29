<?php

/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

// Include required configuration files
require_once(realpath(__DIR__ . '/alerts.php'));
require_once(realpath(__DIR__ . '/functions.php'));
require_once(realpath(__DIR__ . '/extras.php'));
require_once(realpath(__DIR__ . '/connectivity.php'));
require_once(realpath(__DIR__ . '/../vendor/autoload.php'));

/*************************************
 * FUNCTION: SIMPLERISK SERVICE CALL *
 *************************************/
function simplerisk_service_call($parameters)
{
    write_debug_log('SimpleRisk cloud services are disabled; no outbound service call made.', 'debug');
    return false;
}

/****************************
 * FUNCTION: DOWNLOAD EXTRA *
 ****************************/
function download_extra($name, $streamed_response = false) {

    global $escaper;

    $message = 'Extras are self-managed. Install under extras/' . $name . '/ on the host bind mount, then refresh.';
    if ($streamed_response) {
        stream_write_error($message);
    } else {
        set_alert(true, "bad", $escaper->escapeHtml($message));
    }
    return 0;
}

/**************************
 * FUNCTION: RECURSE COPY *
 **************************/
function recurse_copy($src, $dst) {
    // Get the source directory
    $dir = opendir($src);
    $result = ($dir === false ? false : true);

    // If the source exists
    if ($result !== false){
        // If the destination does not exist
        if (!is_dir($dst))
        {
            // Create the destination
            $result = @mkdir($dst);
        }

        // If the destination exists
        if ($result === true){
            // Iterate through the source directory
            while(false !== ( $file = readdir($dir)) ) {
                if (( $file != '.' ) && ( $file != '..' ) && $result) {
                    // If it is a directory
                    if ( is_dir($src . '/' . $file) ) {
                        // Recursive copy the files in it
                        $result = recurse_copy($src . '/' . $file,$dst . '/' . $file);
                    }
                    // Otherwise, just copy the files
                    else {
                        $result = copy($src . '/' . $file,$dst . '/' . $file);
                    }
                }
            }
            // Close the directory
            closedir($dir);
        }
    }
    // Return a success or failure
    return $result;
}


/**
 * Just a small helper function to be able to have the exact same json response format we have everywhere else
 * without using the rest of the logic the json_response() function have.
 *
 *
 * @param int $status Status code of the response
 * @param string $status_message Status message
 * @param array $data Additional data
 * @return array The response as an array in the format of ['status' => $status, 'status_message' => $status_message, 'data' => $data]
 */
 function create_json_response_array($status, $status_message, $data=array()) {
     return ['status' => $status, 'status_message' => $status_message, 'data' => $data];
 }

/***************************
 * FUNCTION: JSON RESPONSE *
 ***************************/
function json_response($status, $status_message, $data=array())
{
	// HTTP Header
	header("HTTP/1.1 $status");
	header("Content-Type: application/json");

	// Response
	$response = create_json_response_array($status, $status_message, $data);

	// JSON Response fixing any invalid utf8 characters
	$json_response = json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);

	// Display the response
	echo $json_response;
    exit;
}

/******************************************
 * FUNCTION: CALL EXTRA API FUNCTIONALITY *
 ******************************************/
function call_extra_api_functionality($extra, $functionality, $target) {

    $uri = "";

    if ($extra === 'upgrade') {
        if ($functionality === 'upgrade') {
            $uri .= 'upgrade/';
            switch($target) {
                case 'app':
                    $uri .= 'app';
                    break;
                case 'core_app':
                    $uri .= 'simplerisk/app';
                    break;
                case 'core_db':
                    $uri .= 'simplerisk/db';
                    break;
                default: // return false on invalid target
                    return false;
            }
        } elseif ($functionality === 'backup') {
            $uri .= 'backup/';
            switch($target) {
                case 'app':
                    $uri .= 'app';
                    break;
                case 'db':
                    $uri .= 'db';
                    break;
                default: // return false on invalid target
                    return false;
            }
        } elseif ($functionality === 'version') {
            $uri .= 'version';
            switch($target) {
                case 'app':
                    $uri .= '/app';
                    break;
            }
        } else {
            // return false on invalid functionality
            return false;
        }
    } else {
        if ($functionality === 'upgrade') {
            $uri .= 'upgrade/';
            switch($target) {
                case 'app':
                    $uri .= 'app';
                    break;
                case 'db':
                    $uri .= 'db';
                    break;
                default: // return false on invalid target
                    return false;
            }
        } else {
            // extras other than the 'upgrade' only have the upgrade functionality
            return false;
        }
    }

    $url = build_url("api", $extra, $uri);
    //error_log("URL: " . json_encode($url));
    $http_options = [
        'method' => 'GET',
        'header' => [
            "Cookie: " . session_name() . "=" . session_id(),
            "Content-Type: application/json",
            "Accept: application/json",
        ],
        'ignore_errors' => true,
        'timeout' => 600,
    ];

    // If SSL certificate checks are enabled
    if (get_setting('ssl_certificate_check_simplerisk') == 1)
    {
        // Verify the SSL host and peer
        $validate_ssl = true;
    }
    else
    {
        // Do not verify the SSL host and peer
        $validate_ssl = false;
    }

    //error_log("url: " . json_encode($url));
    //error_log("context: " . json_encode($context));
    $result = fetch_url_content("stream", $http_options, $validate_ssl, $url);
    //error_log("header: " . json_encode($http_response_header));
    //error_log("result: " . json_encode($result));

    if (!is_array($result)) {
        return [0, null];
    }
    return [$result['return_code'], json_decode($result['response'], true)];
}

/******************************************
 * FUNCTION: CALL SIMPLERISK API ENDPOINT *
 ******************************************/
function call_simplerisk_api_endpoint($endpoint, $method = "GET", $system_token = false, $timeout = 600)
{
    // If no system token was provided
    if (!$system_token)
    {
        // Try to use a cookie for authentication
        $authentication = "Cookie: " . session_name() . "=" . session_id();
    }
    // If a system token was provided
    else
    {
        // Send the token for authentication
        $authentication = "X-SYSTEM-TOKEN: {$system_token}";
    }

    $url = build_url($endpoint);
    //error_log("URL: " . json_encode($url));
    $http_options = [
        'method' => $method,
        'header' => [
            $authentication,
            "Content-Type: application/json",
            "Accept: application/json",
        ],
        'ignore_errors' => true,
        'timeout' => $timeout,
    ];

    // If SSL certificate checks are enabled
    if (get_setting('ssl_certificate_check_simplerisk') == 1)
    {
        // Verify the SSL host and peer
        $validate_ssl = true;
    }
    else
    {
        // Do not verify the SSL host and peer
        $validate_ssl = false;
    }

    //error_log("url: " . json_encode($url));
    //error_log("context: " . json_encode($context));
    $result = fetch_url_content("stream", $http_options, $validate_ssl, $url);
    //error_log("header: " . json_encode($http_response_header));
    //error_log("result: " . json_encode($result));

    if (!is_array($result)) {
        return null;
    }

    // If we got a successful result
    if ($result['return_code'] == 200)
    {
        // Return the data array
        // @phan-suppress-next-line PhanTypeArraySuspiciousNullable -- json_decode of valid 200 response should be array; null gracefully degrades
        return json_decode($result['response'], true)['data'];
    }
    // Otherwise return an empty array
    else return [];
}

/******************************
 * FUNCTION: GET SYSTEM TOKEN *
 ******************************/
function get_system_token()
{
    // Generate a 100 character system token
    $token = generate_token(100);

    // Open a database connection
    $db = db_open();

    // Insert the token into the system_tokens table
    $stmt = $db->prepare("INSERT IGNORE INTO `system_tokens` (`token`) VALUES (:token);");
    $stmt->bindParam(":token", $token, PDO::PARAM_STR);
    $stmt->execute();

    // Close the database connection
    db_close($db);

    // Return the token
    return $token;
}

/********************************
 * FUNCTION: CHECK SYSTEM TOKEN *
 ********************************/
function check_system_token()
{
    // Get the HTTP Headers for the request
    $headers = getallheaders();

    // If a system token was provided
    if (isset($headers['X-SYSTEM-TOKEN']))
    {
        // Open a database connection
        $db = db_open();

        // Delete system tokens over a minute old
        $stmt = $db->prepare("DELETE FROM `system_tokens` WHERE timestamp < (NOW() - INTERVAL 1 MINUTE);");
        $stmt->execute();

        // Atomically consume the token by deleting it in a single operation.
        // If the DELETE affects exactly one row, the token was valid; if zero rows
        // are affected it was already used or never existed. This eliminates the
        // SELECT/DELETE race that would otherwise allow two concurrent requests to
        // both pass the SELECT before either DELETE executes.
        $stmt = $db->prepare("DELETE FROM `system_tokens` WHERE token=:token;");
        $stmt->bindParam(":token", $headers['X-SYSTEM-TOKEN'], PDO::PARAM_STR);
        $stmt->execute();

        // Close the database connection
        db_close($db);

        // If we deleted a matching token
        if ($stmt->rowCount() > 0)
        {
            // Return true
            return true;
        }
    }

    // If we get back to this point, return false
    return false;
}

?>