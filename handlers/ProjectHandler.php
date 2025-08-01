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

$message = '';
$error = '';
$output = [];
$resultData = null;

// Form is always shown, two-column output and mapping is shown if $resultData is set
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
        .mapping-wizard { max-width: 1050px; margin: 2.5rem auto 2rem auto; background:#fff; border-radius:1.3rem; box-shadow:0 2px 14px #20508114; padding:2.2rem 2rem 2.6rem 2rem;}
        .mapping-wizard h4 { color:#1651a5;font-weight:700; }
        .mapping-wizard label { font-weight:500;}
        .mapping-summary { font-size:1.02em; }
        @media (max-width: 900px) {
            .twocols { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="card-upload" style="max-width:600px;">
        <h2>Create New Project</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php elseif ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
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
    </div>
    <?php if ($resultData): ?>
        <div class="twocols mt-5">
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
                <h4>Layouts (Placeholders & Shape Descriptions)</h4>
                <?php foreach ($resultData["layouts"] as $layout): ?>
                    <div class="layout-metadata mb-4">
                        <div class="layout-title"><?= "Layout {$layout['layout_index']}: " . htmlspecialchars($layout['layout_name']) ?></div>
                        <?php if (!empty($layout['placeholders'])): ?>
                            <ul>
                                <?php foreach ($layout['placeholders'] as $p): ?>
                                    <li>
                                        <b><?= htmlspecialchars($p['title']) ?></b>
                                        <?php if (!empty($p['description'])): ?>
                                            : <?= htmlspecialchars($p['description']) ?>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($layout['shape_descriptions'])): ?>
                            <div style="margin-bottom:0.5em;">
                                <b>Shape Descriptions:</b>
                                <ul>
                                    <?php foreach ($layout['shape_descriptions'] as $desc): ?>
                                        <li><?= htmlspecialchars($desc) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <?php if (empty($layout['placeholders']) && empty($layout['shape_descriptions'])): ?>
                            <div style="color:#888;">No content detected.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- MAPPING WIZARD STARTS HERE -->
        <div class="mapping-wizard">
            <h4 class="mb-3">Step 2: Map Flow Tags to Layouts</h4>
            <form id="mappingForm" autocomplete="off">
            <?php foreach ($resultData["slide_tags"] as $tagIdx => $tag): ?>
                <div class="mb-4 border-bottom pb-2">
                    <div style="font-weight:600; color:#205081;">
                        <?= htmlspecialchars($tag["tag"]) ?>
                        <?php if ($tag["mandatory"]): ?>
                            <span class="badge bg-success">Mandatory</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Optional</span>
                        <?php endif; ?>
                    </div>
                    <div style="color:#444;"><?= htmlspecialchars($tag["description"]) ?></div>
                    <div class="mt-2 ms-2">
                        <?php foreach ($resultData["layouts"] as $layout): ?>
                            <label class="me-3">
                                <input type="checkbox"
                                       name="mapping[<?= $tagIdx ?>][]"
                                       value="<?= $layout['layout_index'] ?>"
                                       data-tagidx="<?= $tagIdx ?>"
                                       data-tagname="<?= htmlspecialchars($tag["tag"]) ?>"
                                       data-layoutname="<?= htmlspecialchars($layout["layout_name"]) ?>"
                                       onchange="updateSummary()"
                                >
                                Layout <?= $layout['layout_index'] ?>: <?= htmlspecialchars($layout["layout_name"]) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </form>
            <div id="mappingSummary" class="mapping-summary"></div>
        </div>
        <script>
        function updateSummary() {
            const summaryDiv = document.getElementById('mappingSummary');
            let html = '<h5 class="mt-3">Your Mapping</h5><table class="table table-sm"><tr><th>Tag</th><th>Layouts Selected</th></tr>';
            <?php foreach ($resultData["slide_tags"] as $tagIdx => $tag): ?>
                let selected = [];
                document.querySelectorAll('input[name="mapping[<?= $tagIdx ?>][]"]:checked').forEach(function(cb){
                    selected.push(cb.value + ' (' + cb.getAttribute('data-layoutname') + ')');
                });
                html += '<tr><td><b><?= htmlspecialchars($tag["tag"]) ?></b></td><td>' + (selected.length ? selected.join(', ') : '<span style="color:#bbb;">None</span>') + '</td></tr>';
            <?php endforeach; ?>
            html += '</table>';
            summaryDiv.innerHTML = html;
        }
        document.addEventListener('DOMContentLoaded', updateSummary);
        </script>
    <?php endif; ?>
    <div class="mt-4"><a href="../index.php" class="btn btn-wizard">Return to Dashboard</a></div>
</div>
</body>
</html>
