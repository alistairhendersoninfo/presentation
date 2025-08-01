<?php
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Presentation Builder Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f6f9fc; }
        .wizard-step { background: #fff; border-radius: 1.5rem; box-shadow: 0 6px 24px #c1d8fd44; padding: 2rem; margin-bottom: 2rem; }
        .wizard-header { color: #205081; font-weight: 700; font-size: 2rem; margin-bottom: 0.5rem; }
        .wizard-desc { font-size: 1.1rem; margin-bottom: 1.5rem; color: #334; }
        .wizard-progress { margin-bottom: 2.5rem; }
        .btn-wizard { background: #205081; color: #fff; font-weight: 600; font-size: 1.2rem; border-radius: 1.5rem; padding: 0.7rem 2.5rem; box-shadow: 0 4px 12px #20508133;}
        .btn-wizard:hover { background: #1651a5; color: #fff; }
        .wizard-list li { margin-bottom: 0.6rem; }
        .step-badge { background: #205081; color: #fff; border-radius: 50%; width: 2.4rem; height: 2.4rem; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.25rem; margin-right: 1rem; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="text-center mb-4">
        <div style="font-size:2.8rem;font-weight:800;color:#1651a5;letter-spacing:-1px;">Presentation Builder</div>
        <div style="font-size:1.3rem; color:#3a4a6b;">Your AI-powered slide deck generator</div>
    </div>

    <div class="wizard-progress d-flex justify-content-between align-items-center mb-5">
        <div><span class="step-badge">1</span>Upload Template</div>
        <div style="flex:1;height:3px;background:#d7e3fa;margin:0 0.5rem;"></div>
        <div><span class="step-badge">2</span>Audit Layout</div>
        <div style="flex:1;height:3px;background:#d7e3fa;margin:0 0.5rem;"></div>
        <div><span class="step-badge">3</span>New Project</div>
        <div style="flex:1;height:3px;background:#d7e3fa;margin:0 0.5rem;"></div>
        <div><span class="step-badge">4</span>Build Slides</div>
    </div>

    <!-- Step 1: Upload Template -->
    <div class="wizard-step">
        <div class="wizard-header">Step 1: Upload & Audit Template</div>
        <div class="wizard-desc">
            Start by uploading a new PowerPoint template (.pptx). The system will automatically audit the template and extract all slide layouts and tags for automation.
        </div>
        <a href="handlers/TemplateHandler.php?action=upload" class="btn btn-wizard">Upload & Audit Template</a>
    </div>

    <!-- Step 2: Review Templates -->
    <div class="wizard-step">
        <div class="wizard-header">Step 2: Review Templates</div>
        <div class="wizard-desc">Templates available in your system:</div>
        <ul class="wizard-list">
            <?php foreach ($templates as $t): ?>
                <li>
                    <span style="font-weight:600;color:#1651a5;"><?= htmlspecialchars($t) ?></span>
                    <?php if (file_exists(__DIR__ . "/templates/$t/template_audit.md")): ?>
                        (<a href="templates/<?= htmlspecialchars($t) ?>/template_audit.md" target="_blank">View Audit</a>)
                    <?php endif; ?>
                    <?php if (file_exists(__DIR__ . "/templates/$t/slide_tags.json")): ?>
                        (<a href="templates/<?= htmlspecialchars($t) ?>/slide_tags.json" target="_blank">slide_tags.json</a>)
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Step 3: Create Project -->
    <div class="wizard-step">
        <div class="wizard-header">Step 3: Create New Project</div>
        <div class="wizard-desc">
            Create a new project using one of your uploaded templates. (You may want a form here that lets the user choose a template and project name.)
        </div>
        <a href="handlers/ProjectHandler.php?action=create" class="btn btn-wizard">Create New Project</a>
    </div>

    <!-- Step 4: Review/Resume Projects -->
    <div class="wizard-step">
        <div class="wizard-header">Step 4: Review or Resume Projects</div>
        <div class="wizard-desc">
            Continue working on an existing project, or review completed presentations.
        </div>
        <ul class="wizard-list">
            <?php foreach ($projects as $p): ?>
                <li>
                    <span style="font-weight:600;color:#205081;"><?= htmlspecialchars($p) ?></span>
                    (<a href="handlers/ProjectHandler.php?action=open&project=<?= urlencode($p) ?>">Open</a>)
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

</div>
</body>
</html>
