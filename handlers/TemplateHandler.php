<?php
// handlers/TemplateHandler.php

$action = $_GET['action'] ?? '';

if ($action === 'upload') {
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pptx'])) {
        $template_name = basename($_POST['template_name']);
        $tpl_dir = dirname(__DIR__) . "/templates/" . $template_name;
        if (!is_dir($tpl_dir)) mkdir($tpl_dir, 0755, true);

        $pptx_path = $tpl_dir . "/template.pptx";
        if (move_uploaded_file($_FILES['pptx']['tmp_name'], $pptx_path)) {
            // Run the Python audit script
            $cmd = sprintf(
                'cd %s && python3 %s/audit_pptx.py template.pptx 2>&1',
                escapeshellarg($tpl_dir),
                escapeshellarg(dirname(__DIR__))
            );
            $output = [];
            $return_var = 0;
            exec($cmd, $output, $return_var);

            // Write markdown output to file
            if ($return_var === 0 && count($output)) {
                $md_start = array_search(true, array_map(fn($l)=>strpos($l, '# Audit')===0, $output));
                if ($md_start !== false) {
                    $md = implode("\n", array_slice($output, $md_start));
                    file_put_contents($tpl_dir . "/template_audit.md", $md);
                    $message = "Template uploaded and audit complete!";
                } else {
                    $message = "Template uploaded, but no audit markdown found.<br><pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
                }
            } else {
                $message = "Template uploaded, but audit failed.<br><pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
            }
        } else {
            $message = "Upload failed!";
        }
        header("Refresh: 3; url=../index.php");
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Upload & Audit Template</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="/static/style.css">
        <?php if (!empty($message)) { echo '<meta http-equiv="refresh" content="3;url=../index.php">'; } ?>
    </head>
    <body>
        <div class="card-upload">
            <h2>Upload & Audit Template</h2>
            <div class="upload-note">
                Upload a new PowerPoint (.pptx) template.<br>
                The system will extract layouts and audit your file automatically.<br>
                <span style="font-size:0.98em;color:#677;">Template names should be unique, e.g., <span style="font-family:monospace;">acme_project</span></span>
            </div>
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?= $message ?></div>
                <div class="text-muted small">Redirecting to dashboard...</div>
            <?php endif; ?>
            <?php if (empty($message)): ?>
                <form method="post" enctype="multipart/form-data" class="mt-2">
                    <div class="mb-3">
                        <label for="template_name" class="form-label">Template Name</label>
                        <input type="text" class="form-control" id="template_name" name="template_name" required autocomplete="off" maxlength="40">
                    </div>
                    <div class="mb-3">
                        <label for="pptx" class="form-label">PPTX File</label>
                        <input type="file" class="form-control" id="pptx" name="pptx" accept=".pptx" required>
                    </div>
                    <button type="submit" class="btn btn-upload w-100">Upload &amp; Audit</button>
                </form>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}
// Add more actions here as needed for future features (list, delete, etc)
?>
