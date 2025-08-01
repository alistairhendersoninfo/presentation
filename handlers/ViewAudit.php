<?php
// handlers/ViewAudit.php

// STEP 1: Get template from query
$template = isset($_GET['template']) ? basename($_GET['template']) : '';
$audit_file = dirname(__DIR__) . "/templates/$template/template_audit.md";

if (!file_exists($audit_file)) {
    http_response_code(404);
    echo "<h2>Audit not found for template: " . htmlspecialchars($template) . "</h2>";
    exit;
}

// Download if requested
if (isset($_GET['download'])) {
    header('Content-Type: text/markdown');
    header('Content-Disposition: attachment; filename="template_audit.md"');
    readfile($audit_file);
    exit;
}

// Read and convert markdown to HTML
$md = file_get_contents($audit_file);

// Use Parsedown for Markdown rendering
require_once(dirname(__DIR__)."/handlers/Parsedown.php"); // Download Parsedown.php to handlers/
$Parsedown = new Parsedown();
$html = $Parsedown->text($md);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Audit for <?= htmlspecialchars($template) ?></title>
    <link rel="stylesheet" href="/static/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .audit-md { background: #fff; border-radius:1rem; box-shadow:0 2px 16px #20508109; padding:2rem; max-width:980px; margin:2.5rem auto 2rem auto; }
        .download-link { float:right; font-size:1.08em; }
        pre, code { background:#f7fafd; border-radius:6px; padding:0.3em 0.6em; }
        table { border-collapse: collapse; width:100%; margin-bottom:1.5em; }
        th, td { border:1px solid #dae3fa; padding:0.35em 0.7em; }
        th { background:#e7f0fa; }
    </style>
</head>
<body>
    <div class="audit-md">
        <div>
            <h2 style="display:inline;">Audit for <span style="color:#205081;"><?= htmlspecialchars($template) ?></span></h2>
            <a class="download-link btn btn-sm btn-wizard" href="?template=<?= urlencode($template) ?>&download=1">Download Markdown</a>
        </div>
        <hr>
        <?= $html ?>
    </div>
</body>
</html>
ß