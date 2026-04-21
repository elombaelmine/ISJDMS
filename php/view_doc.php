<?php
include("database.php");
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$res = $conn->query("SELECT file_path, name FROM documents WHERE id = $id");
if($row = $res->fetch_assoc()) {
    $file = "../" . $row['file_path'];
    
    if (file_exists($file)) {
        // These headers tell the browser (and IDM) to VIEW, not download
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $row['name'] . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        readfile($file);
        exit;
    }
}
echo "File not found.";
?>