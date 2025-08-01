<?php
// handlers/ProjectHandler.php

// Ensure logs directory is loaded for logging if needed
$templatesDir = dirname(__DIR__) . "/templates";
$projectsDir = dirname(__DIR__) . "/projects";

// List templates
function listDirs($dir) {
    $dirs = [];
    foreach (glob($dir . '/*', GLOB_ONLYDIR) as $folder) {
        $dirs[] = basename($folder);
    }
    return $dirs;
}
$templates = listDirs($templatesDir);

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $message = '';
    $error = '';
    $output = [];
    $resultTable = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $project = preg_replace('/[^a-zA-Z0-9_-]/', '_', $_POST['project'] ?? '');
        $template = basename($_POST['template'] ?? '');

        if (!$project || !$template) {
            $error = "Please enter a project name and select a template.";
        } else {
            $projectDir = $projectsDir . "/" . $project;
            if (!is_dir($projectDir)) mkdir($projectDir, 0755, true);

            // Copy relevant files to project (optional: expand as needed)
            @copy("$templatesDir/$template/template_audit.md", "$projectDir/template_audit.md");
            @copy("presentation_flow.json", "$projectDir/presentation_flow.json");

            // Call the Python script (assumes the script is in python_web and executable)
            $cmd = sprintf(
                'cd %s && python3 %s/python_web/analyse_presentation_layouts.py 2>&1',
                escapeshellarg($projectDir),
                escapeshellarg(dirname(__DIR__))
            );
            exec($cmd, $output, $retval);

            if ($retval === 0) {
                $message = "Project created and layouts analyzed!";
                // Show only the summary table from output
                $start = false;
                foreach ($output as $line) {
                    if (strpos($line, '| Tag/Slide Type') !== false) $start = true;
                    if ($start) $resultTable .= htmlspecialchars($line) . "\n";
                }
                if (!$resultTable) $resultTable = "<pre>".htmlspecialchars(implode("\n", $output))."</pre>";
            } else {
                $error = "Analysis script failed.<br><pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Create New Project</title>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="/static/style.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body>
    <div class="container py-4">
        <div class="card-upload" style="max-width:600px;">
            <h2>Create New Project</h2>
            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
                <div class="mb-3"><pre><?= $resultTable ?></pre></div>
                <div class="mt-2"><a href="../index.php" class="btn btn-wizard">Return to Dashboard</a></div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
                <div class="mt-2"><a href="../index.php" class="btn btn-wizard">Return to Dashboard</a></div>
            <?php else: ?>
                <form method="post" class="mt-3">
                    <div class="mb-3">
                        <label for="project" class="form-label">Project Name</label>
                        <input type="text" name="project" id="project" class="form-control" maxlength="40" required>
                    </div>
                    <div class="mb-3">
                        <label for="template" class="form-label">Choose Template</label>
                        <select name="template" id="template" class="form-select" required>
                            <option value="">Select Template</option>
                            <?php foreach ($templates as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-upload w-100">Create Project &amp; Analyze</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// You can add more actions (open, delete, etc) here.
