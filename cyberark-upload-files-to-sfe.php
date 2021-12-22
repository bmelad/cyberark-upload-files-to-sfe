<?php

    error_reporting(E_ERROR | E_PARSE);
    $session_id = '';
    $username = '{username}';
    $password = '{password}';
    $safe_name = '{safe-name}';
    $host = '{cyberark-sfe-address}';
    $base_url = 'https://'.$host.'/SFE/';
    $folder_path = '{folder_path}';
    $ignore_ssl_issues = false;

    function post($url, $headers, $payload) {
        global $ignore_ssl_issues;
        $options = array('http' => array('method' => 'POST', 'header' => $headers, 'content' => $payload));
        if ($ignore_ssl_issues) $options['ssl'] = array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true);
        $context = stream_context_create($options);
        $response = file_get_contents($url, FALSE, $context);
        if (isset($http_response_header)) return array('status' => explode(' ', $http_response_header[0])[1], 'data' => $response);
    }

    function login($user, $pass) {

        global $base_url, $session_id;
        $url = $base_url.'WebServices/auth/Cyberark/CyberArkAuthenticationService.svc/Logon';
        $payload = array('username' => $user, 'password' => $pass);
        $result = post($url, "Content-Type: application/json\r\nAuthorization: \r\n", json_encode($payload));
        if ($result['status'] == '200') {
            $session_id = json_decode($result['data'], TRUE)['CyberArkLogonResult'];
            return true;
        }
        return false;
    }

    function upload_file($safe_name, $file_name, $folder_name) {
        global $base_url, $folder_path, $session_id;
        $payload = file_get_contents($folder_path.$file_name);
        $url = $base_url.'WebServices/API.svc/Upload/Safes/'.$safe_name.'/Files/'.$file_name.'?folder='.$folder_name;
        $result = post($url, "Authorization: $session_id\r\n", $payload);
        if ($result['status'] == '201') echo 'success.'."\r\n";
        else if ($result['status'] == '409') echo 'failed (file already exists).'."\r\n";
        else echo 'failed ('.$result['status'].').'."\r\n";

    }

    function logout() {
        global $base_url, $session_id;
        $url = $base_url.'WebServices/auth/Cyberark/CyberArkAuthenticationService.svc/Logoff';
        $payload = array();
        return post($url, "Content-Type: application/json\r\nAuthorization: $session_id\r\n", json_encode($payload))['status'] == '200';
    }

    function error_handler($errno, $errstr, $errfile, $errline)
    {
        if (strpos($errstr, 'SSL') !== false) echo 'SSL Error, you may try to set the \'ignore_ssl_issues\' flag to true...'."\r\n";
        return true;
    }

    set_error_handler("error_handler");
    if (!is_dir($folder_path)) echo 'The directory '.$folder_path.' does not exist.'."\r\n";
    else {
        if (!login($username, $password)) echo 'login failed.'."\r\n";
        else {
            echo 'logged-in successfully.'."\r\n";
            echo 'uploading files from \''.$folder_path.'\' to safe \''.$safe_name.'\'...'."\r\n";
            $files = scandir($folder_path);
            foreach($files as $file) {
                if ($file != '.' && $file != '..') {
                    echo '   uploading file \''.$file.'\'... ';
                    upload_file($safe_name, $file, 'root');
                }
            }
            if (logout()) echo 'logged-out successfully.'."\r\n";
        }
    }

?>
