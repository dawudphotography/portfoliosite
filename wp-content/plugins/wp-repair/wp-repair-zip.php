<?php
include 'db_config.php';

$uID = isset($_GET['uID']) ? filter_input( INPUT_GET, 'uID', FILTER_SANITIZE_STRING ) : null;
$file = isset($_GET['file']) ? filter_input( INPUT_GET, 'file', FILTER_SANITIZE_STRING ) : null;

if ((empty($uID)) || (empty($file))) {
    $url=$_SERVER['REQUEST_URI'];
    $home=str_replace('/wp-repair','',$url);

    header("Location: ".$home."");
    die();
}
else {
    $sql = "SELECT meta_value FROM " . $table_prefix . "usermeta WHERE user_id='" . $uID . "' AND meta_key='session_tokens'";
    if ($result = $conn->query($sql)) {
        /* fetch object array */
        while ($row = $result->fetch_assoc()) {
            $sessions = unserialize($row['meta_value']);
        }
        foreach ($sessions as $session) {
            $activeIP = $session["ip"];
            break;
        }

        if ($activeIP != $_SERVER['REMOTE_ADDR']) {
            header("HTTP/1.1 403 Forbidden");
            exit;
        }
    }
    if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
        header('Content-Type: "application/octet-stream"');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Content-Transfer-Encoding: binary");
        header('Pragma: public');
        header("Content-Length: " . filesize($file));
    } else {
        header('Content-Type: "application/octet-stream"');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: no-cache');
        header("Content-Length: " . filesize($file));
    }
    readfile($file);
}
?>