<?php
// upload_template.php -- improved for success + redirect

$uploaded = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pptx'])) {
    $name = basename($_POST['template_name']);
    $tpl_dir = __DIR__ . "/templates/" . $name;
    if (!is_dir($tpl_dir)) mkdir($tpl_dir, 0755, true);
    if (move_uploaded_file($_FILES['pptx']['tmp_name'], $tpl_dir . "/template.pptx")) {
        $uploaded = true;
    } else {
        $error = "Upload failed.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Template</title>
    <?php if (!empty($uploaded)): ?>
        <meta http-equiv="refresh" content="2;url=index.php">
    <?php endif; ?>
</head>
<body>
    <?php if (!empty($uploaded)): ?>
        <h3>Template uploaded successfully! Redirecting to home...</h3>
    <?php elseif (!empty($error)): ?>
        <h3 style="color:red;"><?= htmlspecialchars($error) ?></h3>
    <?php endif; ?>
    <?php if (empty($uploaded)): ?>
        <form method="post" enctype="multipart/form-data">
            Template Name: <input type="text" name="template_name" required><br>
            PPTX File: <input type="file" name="pptx" required><br>
            <input type="submit" value="Upload">
        </form>
    <?php endif; ?>
</body>
</html>
