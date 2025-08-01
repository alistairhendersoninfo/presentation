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
        <?php if (!empty($message)) { echo '<meta http-equiv="refresh" content="3;url=../index.php">'; } ?>
    </head>
    <body>
        <?php if (!empty($message)): ?>
            <h3><?= $message ?></h3>
        <?php endif; ?>
        <?php if (empty($message)): ?>
            <form method="post" enctype="multipart/form-data">
                Template Name: <input type="text" name="template_name" required><br>
                PPTX File: <input type="file" name="pptx" required><br>
                <input type="submit" value="Upload & Audit">
            </form>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}
// Add more actions here as needed for future features (list, delete, etc)
?>
