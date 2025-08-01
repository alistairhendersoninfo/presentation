<?php
// handlers/ProjectHandler.php

// 1. Load .env for LOGGING before anything else
$env_file = dirname(__DIR__) . '/.env';
if (file_exists($env_file)) {
    foreach (file($env_file) as $line) {
        if (trim($line) && strpos(trim($line), '#') !== 0) putenv(trim($line));
    }
}

// 2. Require logger and initialize
require_once(dirname(__DIR__)."/handlers/logging.php");
$logger = getLogger();

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
$savedMapping = null;
$savedProject = null;

// --- MAPPING SAVE HANDLER (AJAX OR CLASSIC POST) ---
if (isset($_POST['mapping_save']) && isset($_POST['project_name']) && isset($_POST['mapping_json'])) {
    $logger->log('DEBUG', 'Mapping save POST detected', [
        'project_name' => $_POST['project_name'],
        'post' => $_POST
    ]);
    $project = preg_replace('/[^a-zA-Z0-9_-]/', '_', $_POST['project_name']);
    $mapping_json = $_POST['mapping_json'];
    $projectDir = $projectsDir . "/" . $project;
    $mappingPath = "$projectDir/mapping.json";

    if (!is_dir($projectDir)) {
        $logger->log('WARNING', "Project dir does not exist, creating", ['dir' => $projectDir]);
        @mkdir($projectDir, 0755, true);
    }
    if (!is_dir($projectDir)) {
        $logger->log('ERROR', "Project dir could not be created", ['dir' => $projectDir]);
        http_response_code(400);
        echo json_encode(['error' => "Project directory could not be created: $projectDir"]);
        exit;
    }
    if (!is_writable($projectDir)) {
        $logger->log('ERROR', "Project dir not writable", ['dir' => $projectDir]);
        http_response_code(500);
        echo json_encode(['error' => "Project directory not writable: $projectDir"]);
        exit;
    }
    $writeResult = file_put_contents($mappingPath, $mapping_json);
    if ($writeResult === false) {
        $logger->log('ERROR', "Failed to write mapping.json", ['path' => $mappingPath, 'json' => $mapping_json]);
        http_response_code(500);
        echo json_encode(['error' => "Failed to write mapping.json to $mappingPath"]);
        exit;
    }
    $logger->log('INFO', "Mapping saved", ['path' => $mappingPath]);
    $savedMapping = json_decode($mapping_json, true);
    $savedProject = $project;
    echo json_encode(['success' => true, 'path' => $mappingPath]);
    exit;
}

// --- PROJECT CREATION AND ANALYSIS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['mapping_save'])) {
    $logger->log('DEBUG', 'Project creation POST received', [
        'post' => $_POST
    ]);
    $project = preg_replace('/[^a-zA-Z0-9_-]/', '_', $_POST['project'] ?? '');
    $template = basename($_POST['template'] ?? '');

    if (!$project || !$template) {
        $error = "Please enter a project name and select a template.";
        $logger->log('WARNING', "Project or template missing", ['project' => $project, 'template' => $template]);
    } else {
        $projectDir = $projectsDir . "/" . $project;
        if (!is_dir($projectDir)) mkdir($projectDir, 0755, true);

        @copy("$templatesDir/$template/template_audit.md", "$projectDir/template_audit.md");

        $flowSrc = null;
        if (file_exists(dirname(__DIR__) . "/presentation_flow.json")) {
            $flowSrc = dirname(__DIR__) . "/presentation_flow.json";
        } elseif (file_exists($configDir . "/presentation_flow.json")) {
            $flowSrc = $configDir . "/presentation_flow.json";
        }
        if ($flowSrc) {
            @copy($flowSrc, "$projectDir/presentation_flow.json");
        }

        if (!file_exists("$projectDir/template_audit.md")) {
            $error = "Template audit not found. Please re-upload the template.";
            $logger->log('ERROR', $error, ['file' => "$projectDir/template_audit.md"]);
        } elseif (!file_exists("$projectDir/presentation_flow.json")) {
            $error = "presentation_flow.json not found in root or config directory.";
            $logger->log('ERROR', $error, ['file' => "$projectDir/presentation_flow.json"]);
        } else {
            $cmd = sprintf(
                'cd %s && python3 %s/python_web/analyse_presentation_layouts_display.py 2>&1',
                escapeshellarg($projectDir),
                escapeshellarg(dirname(__DIR__))
            );
            $logger->log('DEBUG', "Running analysis script", ['cmd' => $cmd]);
            exec($cmd, $output, $retval);
            $json = json_decode(implode("\n", $output), true);

            if (isset($json["error"])) {
                $error = "Analysis script failed.<br><pre>" . htmlspecialchars($json["error"]) . "</pre>";
                $logger->log('ERROR', $error, ['output' => $output]);
            } elseif (!$json || $retval !== 0) {
                $error = "Analysis script failed.<br><pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
                $logger->log('ERROR', $error, ['retval' => $retval, 'output' => $output]);
            } else {
                $message = "Project created and layouts analyzed!";
                $logger->log('INFO', $message, ['project' => $project]);
                $resultData = $json;
                $savedProject = $project;
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
        <?php elseif ($message && !$savedMapping): ?>
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

        <!-- MAPPING WIZARD -->
        <div class="mapping-wizard">
            <h4 class="mb-3">Step 2: Map Flow Tags to Layouts</h4>
            <form id="mappingForm" autocomplete="off">
                <input type="hidden" name="project_name" id="project_name" value="<?= htmlspecialchars($savedProject ?? $project ?? $_POST['project'] ?? '') ?>">
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
            <button type="button" class="btn btn-wizard" onclick="saveMapping()">Save Mapping</button>
            </form>
            <div id="mappingSummary" class="mapping-summary"></div>
            <div id="mappingSaveMsg"></div>
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

        function saveMapping() {
            let mapping = {};
            <?php foreach ($resultData["slide_tags"] as $tagIdx => $tag): ?>
                mapping[<?= $tagIdx ?>] = [];
                document.querySelectorAll('input[name="mapping[<?= $tagIdx ?>][]"]:checked').forEach(function(cb){
                    mapping[<?= $tagIdx ?>].push(cb.value);
                });
            <?php endforeach; ?>

            const payload = new URLSearchParams();
            payload.append('mapping_save', '1');
            payload.append('mapping_json', JSON.stringify(mapping));
            payload.append('project_name', document.getElementById('project_name').value);

            fetch(window.location.href, {
                method: 'POST',
                body: payload,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(r => r.json())
            .then(resp => {
                const msg = document.getElementById('mappingSaveMsg');
                if (resp.success) {
                    msg.innerHTML = '<div class="alert alert-success mt-2">Mapping saved successfully at <b>' + resp.path + '</b></div>';
                } else if (resp.error) {
                    msg.innerHTML = '<div class="alert alert-danger mt-2">' + resp.error + '</div>';
                }
            });
        }
        </script>
    <?php endif; ?>
    <div class="mt-4"><a href="../index.php" class="btn btn-wizard">Return to Dashboard</a></div>
</div>
</body>
</html>
