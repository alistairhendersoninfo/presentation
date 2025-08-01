<?php
// handlers/ProjectHandler.php

function listDirs($dir) {
    $dirs = [];
    foreach (glob($dir . '/*', GLOB_ONLYDIR) as $folder) {
        $dirs[] = basename($folder);
    }
    return $dirs;
}

$templatesDir = dirname(__DIR__) . "/templates";
$projectsDir = dirname(__DIR__) . "/projects";
$configDir = dirname(__DIR__) . "/config";
$templates = listDirs($templatesDir);

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $message = '';
    $error = '';
    $output = [];
    $resultData = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $project = preg_replace('/[^a-zA-Z0-9_-]/', '_', $_POST['project'] ?? '');
        $template = basename($_POST['template'] ?? '');

        if (!$project || !$template) {
            $error = "Please enter a project name and select a template.";
        } else {
            $projectDir = $projectsDir . "/" . $project;
            if (!is_dir($projectDir)) mkdir($projectDir, 0755, true);

            // Copy the audit markdown
            @copy("$templatesDir/$template/template_audit.md", "$projectDir/template_audit.md");

            // Copy presentation_flow.json (from root or config/)
            $flowSrc = null;
            if (file_exists(dirname(__DIR__) . "/presentation_flow.json")) {
                $flowSrc = dirname(__DIR__) . "/presentation_flow.json";
            } elseif (file_exists($configDir . "/presentation_flow.json")) {
                $flowSrc = $configDir . "/presentation_flow.json";
            }
            if ($flowSrc) {
                @copy($flowSrc, "$projectDir/presentation_flow.json");
            }

            // Check if files exist before proceeding
            if (!file_exists("$projectDir/template_audit.md")) {
                $error = "Template audit not found. Please re-upload the template.";
            } elseif (!file_exists("$projectDir/presentation_flow.json")) {
                $error = "presentation_flow.json not found in root or config directory.";
            } else {
                // Call new display Python script
                $cmd = sprintf(
                    'cd %s && python3 %s/python_web/analyse_presentation_layouts_display.py 2>&1',
                    escapeshellarg($projectDir),
                    escapeshellarg(dirname(__DIR__))
                );
                exec($cmd, $output, $retval);
                $json = json_decode(implode("\n", $output), true);

                if (isset($json["error"])) {
                    $error = "Analysis script failed.<br><pre>" . htmlspecialchars($json["error"]) . "</pre>";
                } elseif (!$json || $retval !== 0) {
                    $error = "Analysis script failed.<br><pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
                } else {
                    $message = "Project created and layouts analyzed!";
                    $resultData = $json;
                }
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
        <style>
        .twocols { display: flex; gap: 2rem; margin-top: 2rem; flex-wrap: wrap;}
        .twocols > div { background: #fff; border-radius: 1.3rem; box-shadow: 0 4px 18px #20508118; padding:1.4rem 2rem 2rem 2rem;}
        .col1 { flex: 1.1 1 340px; min-width: 320px; }
        .col2 { flex: 1.2 1 420px; min-width: 350px; }
        .layout-metadata { font-size:0.97em; color: #444;}
        .layout-metadata .layout-title { font-weight:600; color: #205081;}
        .layout-metadata ul { margin-bottom: 0.2em;}
        </style>
    </head>
    <body>
    <div class="container py-4">
        <div class="card-upload" style="max-width:600px;">
            <h2>Create New Project</h2>
            <?php if ($message && $resultData): ?>
                <div class="alert alert-success"><?= $message ?></div>
                <div class="twocols">
                    <div class="col1">
                        <h4>Slide Tags (Flow Structure)</h4>
                        <ul>
                        <?php foreach ($resultData["slide_tags"] as $tag): ?>
                            <li>
                                <span style="font-weight:600; color:#205081"><?= htmlspecialchars($tag["tag"]) ?></span>
                                <?php if ($tag["mandatory"]): ?>
                                    <span class="badge bg-success">Mandatory</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Optional</span>
                                <?php endif; ?>
                                <br>
                                <span style="color:#444;font-size:0.99em"><?= htmlspecialchars($tag["description"]) ?></span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col2">
                        <h4>Layouts (with Metadata)</h4>
                        <?php foreach ($resultData["layouts"] as $layout): ?>
                            <div class="layout-metadata mb-4">
                                <div class="layout-title"><?= "Layout {$layout['layout_index']}: " . htmlspecialchars($layout['layout_name']) ?></div>
                                <?php if (!empty($layout['placeholders'])): ?>
                                    <b>Placeholders:</b>
                                    <ul>
                                        <?php foreach ($layout['placeholders'] as $p): ?>
                                            <li>
                                                <?= isset($p['title']) ? "<b>" . htmlspecialchars($p['title']) . "</b>: " : "" ?>
                                                <?= isset($p['description']) ? htmlspecialchars($p['description']) : htmlspecialchars($p['name'] ?? '') ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?php if (!empty($layout['shapes'])): ?>
                                    <b>Shapes:</b>
                                    <ul>
                                        <?php foreach ($layout['shapes'] as $s): ?>
                                            <li><?= htmlspecialchars($s['title']) ?>
                                                <?php if (!empty($s['description'])): ?>
                                                    : <?= htmlspecialchars($s['description']) ?>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?php if (!empty($layout['summary'])): ?>
                                    <div class="text-muted" style="font-size:0.96em;">
                                        <b>Summary:</b> <?= htmlspecialchars(implode('; ', $layout['summary'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mt-4"><a href="../index.php" class="btn btn-wizard">Return to Dashboard</a></div>
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
?>
