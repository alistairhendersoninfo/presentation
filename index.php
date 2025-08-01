<?php
// index.php -- MVP landing/menu stub
function listDirs($dir) {
    $dirs = [];
    foreach (glob($dir . '/*', GLOB_ONLYDIR) as $folder) {
        $dirs[] = basename($folder);
    }
    return $dirs;
}
$templates = listDirs(__DIR__ . "/templates");
$projects = listDirs(__DIR__ . "/projects");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Presentation Builder</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/static/style.css">
</head>
<body class="container">
    <h1>Presentation Builder MVP</h1>
    <h2>Templates</h2>
    <ul>
        <?php foreach ($templates as $t): ?>
        <li><?= htmlspecialchars($t) ?> (<a href="/templates/<?= htmlspecialchars($t) ?>/slide_tags.json">slide_tags.json</a>)</li>
        <?php endforeach; ?>
    </ul>
    <a href="upload_template.php" class="btn btn-primary">Upload New Template</a>
    <h2>Projects</h2>
    <ul>
        <?php foreach ($projects as $p): ?>
        <li><?= htmlspecialchars($p) ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
