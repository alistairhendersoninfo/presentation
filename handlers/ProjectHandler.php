<?php
// Assume this is a standalone file (e.g., test_twocols.php)
// Simulate $resultData as if parsed from the Python script:

// Normally you would get $resultData like this:
//   $cmd = sprintf('cd %s && python3 %s/python_web/analyse_presentation_layouts_display.py 2>&1', ...);
//   exec($cmd, $output, $retval);
//   $resultData = json_decode(implode("\n", $output), true);

// For demo:
$resultData = [
    "slide_tags" => [
        ["tag" => "Title Slide", "mandatory" => true, "description" => "Project name, date, presenter"],
        ["tag" => "Agenda", "mandatory" => true, "description" => "List of all sections"]
    ],
    "layouts" => [
        [
            "layout_index" => 1,
            "layout_name" => "Title Slide",
            "placeholders" => [
                ["title" => "MainTitle", "description" => "Main slide title placeholder."],
                ["title" => "SubTitle", "description" => "Main sub title placeholder."]
            ],
            "shape_descriptions" => ["Gray BackGround"]
        ],
        [
            "layout_index" => 2,
            "layout_name" => "Agenda Slide",
            "placeholders" => [],
            "shape_descriptions" => ["Green BackGround"]
        ]
    ]
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Two Column Wizard Example</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .twocols { display: flex; gap: 2.5rem; margin-top: 2rem; flex-wrap: wrap; }
        .twocols > div { background: #fff; border-radius: 1.3rem; box-shadow: 0 4px 18px #20508118; padding:1.4rem 2rem 2rem 2rem; }
        .col1 { flex: 1 1 320px; min-width: 280px; max-width: 380px;}
        .col2 { flex: 2 1 520px; min-width: 350px;}
        .layout-metadata { font-size:0.97em; color: #444;}
        .layout-metadata .layout-title { font-weight:600; color: #205081;}
        .layout-metadata ul { margin-bottom: 0.2em;}
        @media (max-width: 900px) {
            .twocols { flex-direction: column; }
        }
    </style>
</head>
<body style="background:#f6f9fc">
<div class="container py-5">
    <h2 class="mb-3" style="color:#1651a5;font-weight:700;">Project Wizard Example – Two Columns</h2>
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
            <h4>Layouts (Placeholders &amp; Shape Descriptions)</h4>
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
</div>
</body>
</html>
