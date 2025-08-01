<?php
// upload_template.php -- stub for pptx upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pptx'])) {
    $name = basename($_POST['template_name']);
    $tpl_dir = __DIR__ . "/templates/" . $name;
    if (!is_dir($tpl_dir)) mkdir($tpl_dir, 0755, true);
    move_uploaded_file($_FILES['pptx']['tmp_name'], $tpl_dir . "/template.pptx");
    echo "Template uploaded.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Template</title>
</head>
<body>
    <form method="post" enctype="multipart/form-data">
        Template Name: <input type="text" name="template_name" required><br>
        PPTX File: <input type="file" name="pptx" required><br>
        <input type="submit" value="Upload">
    </form>
</body>
</html>
