<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$checks = [];

function pass(string $name, string $detail = ''): void
{
    global $checks;
    $checks[] = ['PASS', $name, $detail];
}

function fail(string $name, string $detail): void
{
    global $checks;
    $checks[] = ['FAIL', $name, $detail];
}

function files(string $root, array $dirs, string $extension): array
{
    $out = [];
    foreach ($dirs as $dir) {
        $path = $root . DIRECTORY_SEPARATOR . $dir;
        if (!is_dir($path)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }
            if (strtolower($file->getExtension()) === ltrim($extension, '.')) {
                $out[] = $file->getPathname();
            }
        }
    }

    sort($out);

    return $out;
}

$phpFiles = files($root, ['src', 'config', 'tests', 'tools'], 'php');
$syntaxErrors = [];
foreach ($phpFiles as $file) {
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1';
    exec($command, $output, $code);
    if ($code !== 0) {
        $syntaxErrors[] = $file . ': ' . implode(' ', $output);
    }
}
$syntaxErrors === []
    ? pass('PHP syntax', count($phpFiles) . ' PHP files linted')
    : fail('PHP syntax', implode("\n", $syntaxErrors));

$debugPatterns = [
    '/(?<![A-Za-z0-9_])dd\s*\(/',
    '/(?<![A-Za-z0-9_])dump\s*\(/',
    '/(?<![A-Za-z0-9_])var_dump\s*\(/',
    '/(?<![A-Za-z0-9_])print_r\s*\(/',
];
$debugHits = [];
foreach (array_merge(files($root, ['src'], 'php'), files($root, ['templates'], 'twig')) as $file) {
    $content = file_get_contents($file) ?: '';
    foreach ($debugPatterns as $pattern) {
        if (preg_match($pattern, $content) === 1) {
            $debugHits[] = $file . ' matches debug pattern ' . $pattern;
        }
    }
}
$debugHits === []
    ? pass('No debug output left in forum code', 'Checked src PHP and Twig templates')
    : fail('No debug output left in forum code', implode("\n", $debugHits));

$sqlHits = [];
foreach (files($root, ['src'], 'php') as $file) {
    $normalized = str_replace('\\', '/', $file);
    if (str_contains($normalized, '/Repository/') || str_contains($normalized, '/Command/')) {
        continue;
    }
    $content = file_get_contents($file) ?: '';
    if (
        preg_match('/\bSELECT\s+.+\s+FROM\b/is', $content) === 1
        || preg_match('/\bINSERT\s+INTO\b/i', $content) === 1
        || preg_match('/\bUPDATE\s+[A-Za-z0-9_]+\s+SET\b/i', $content) === 1
        || preg_match('/\bDELETE\s+FROM\b/i', $content) === 1
        || preg_match('/->\s*(fetchAllAssociative|fetchFirstColumn|executeQuery|executeStatement)\s*\(/', $content) === 1
    ) {
        $sqlHits[] = $file;
    }
}
$sqlHits === []
    ? pass('SQL stays outside controllers/services', 'Direct SQL is confined to repositories')
    : fail('SQL stays outside controllers/services', implode("\n", $sqlHits));

$missingTemplates = [];
foreach (files($root, ['src/Controller'], 'php') as $file) {
    $content = file_get_contents($file) ?: '';
    preg_match_all('/->render\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
    foreach ($matches[1] ?? [] as $template) {
        $templatePath = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template);
        if (!is_file($templatePath)) {
            $missingTemplates[] = $file . ' renders missing template ' . $template;
        }
    }
}
$missingTemplates === []
    ? pass('Controller templates exist', 'All render() template references resolve')
    : fail('Controller templates exist', implode("\n", $missingTemplates));

$forumController = file_get_contents($root . '/src/Controller/ForumController.php') ?: '';
$routeExpectations = [
    'forum_comment_new is POST-only' => "name: 'forum_comment_new', methods: ['POST']",
    'forum_post_like is POST-only' => "name: 'forum_post_like', methods: ['POST']",
    'forum_comment_like is POST-only' => "name: 'forum_comment_like', methods: ['POST']",
    'forum_post_delete has POST route' => "name: 'forum_post_delete', methods: ['POST']",
    'forum_comment_delete has POST route' => "name: 'forum_comment_delete', methods: ['POST']",
];
$missingRoutes = [];
foreach ($routeExpectations as $name => $needle) {
    if (!str_contains($forumController, $needle)) {
        $missingRoutes[] = $name;
    }
}
$missingRoutes === []
    ? pass('Mutating forum routes require POST', count($routeExpectations) . ' route guards checked')
    : fail('Mutating forum routes require POST', implode("\n", $missingRoutes));

$classes = [
    App\Controller\ForumController::class,
    App\Controller\Admin\AdminForumController::class,
    App\Entity\ForumPost::class,
    App\Entity\ForumComment::class,
    App\Service\Forum\GeminiBotService::class,
    App\Service\Forum\AI\ModerationEngine::class,
    App\Service\Forum\NotificationService::class,
];
$missingClasses = array_values(array_filter($classes, static fn (string $class): bool => !class_exists($class)));
$missingClasses === []
    ? pass('Forum classes autoload', count($classes) . ' key classes resolved')
    : fail('Forum classes autoload', implode("\n", $missingClasses));

$fallbackFiles = [
    'src/Service/Forum/AI/GeminiClient.php' => ['quota/rate limit', 'invalid or not authorized', 'missing key'],
    'src/Service/Forum/AI/PythonAiClient.php' => ['http://127.0.0.1:8008', 'Python score request failed'],
    'src/Service/Forum/AI/SafeBrowsingClient.php' => ['ERROR', 'FLAGGED', 'NONE'],
];
$fallbackErrors = [];
foreach ($fallbackFiles as $relative => $needles) {
    $content = file_get_contents($root . '/' . $relative) ?: '';
    foreach ($needles as $needle) {
        if (!str_contains($content, $needle)) {
            $fallbackErrors[] = $relative . ' missing "' . $needle . '"';
        }
    }
}
$fallbackErrors === []
    ? pass('AI integrations expose fallback paths', 'Gemini, Python scoring, and Safe Browsing checked')
    : fail('AI integrations expose fallback paths', implode("\n", $fallbackErrors));

$paginationFiles = [
    'src/Controller/ForumController.php',
    'src/Controller/Admin/AdminForumController.php',
];
$paginationErrors = [];
foreach ($paginationFiles as $relative) {
    $content = file_get_contents($root . '/' . $relative) ?: '';
    foreach (['findFeed(', 'findAdminFeed(', 'findForPost('] as $needle) {
        if (str_contains($content, $needle)) {
            $paginationErrors[] = $relative . ' still paginates an already-loaded array through ' . $needle;
        }
    }
}
$repositoryContent = (file_get_contents($root . '/src/Repository/ForumPostRepository.php') ?: '')
    . (file_get_contents($root . '/src/Repository/ForumCommentRepository.php') ?: '');
foreach (['createFeedQueryBuilder', 'createAdminFeedQueryBuilder', 'createForPostQueryBuilder'] as $method) {
    if (!str_contains($repositoryContent, $method)) {
        $paginationErrors[] = 'Missing paginated repository query method ' . $method;
    }
}
$paginationErrors === []
    ? pass('Forum list pages paginate Doctrine queries', 'Feed, admin, and thread lists avoid loading full arrays first')
    : fail('Forum list pages paginate Doctrine queries', implode("\n", $paginationErrors));

foreach ($checks as [$status, $name, $detail]) {
    echo '[' . $status . '] ' . $name . ($detail !== '' ? ' - ' . $detail : '') . PHP_EOL;
}

$failed = array_filter($checks, static fn (array $check): bool => $check[0] === 'FAIL');
exit($failed === [] ? 0 : 1);
