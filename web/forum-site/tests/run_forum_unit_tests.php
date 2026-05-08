<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Entity\ForumInteraction;
use App\Entity\ForumNotification;
use App\Entity\ForumPost;
use App\Entity\ForumComment;
use App\Entity\User;
use App\Service\Forum\AI\GeminiClient;
use App\Service\Forum\AI\ModerationNoteFormatter;
use App\Service\Forum\AI\ModerationNoteParser;
use App\Service\Forum\AI\ModerationReport;
use App\Service\Forum\AI\SafeBrowsingClient;
use App\Service\Forum\GeminiBotService;
use Symfony\Component\HttpClient\MockHttpClient;

$tests = [];

function test_case(string $name, callable $test): void
{
    global $tests;
    $tests[] = [$name, $test];
}

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assert_same(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

test_case('Gemini trigger is case-insensitive and specific', function (): void {
    $reflection = new ReflectionClass(GeminiBotService::class);
    $service = $reflection->newInstanceWithoutConstructor();

    assert_true($service->hasTrigger('Can @Gemini review this CV?'), 'Expected @Gemini to trigger the bot.');
    assert_true(!$service->hasTrigger('Can the assistant review this CV?'), 'Expected plain text to avoid bot trigger.');
    assert_true(!$service->hasTrigger('email@gemini.example is not a mention'), 'Expected email-like text to avoid bot trigger.');
});

test_case('Gemini replies to approved or pending human comments only', function (): void {
    $reflection = new ReflectionClass(GeminiBotService::class);
    $service = $reflection->newInstanceWithoutConstructor();
    $post = (new ForumPost())
        ->setTitle('Interview preparation')
        ->setContent('How should I prepare?');
    $human = (new User())->setEmail('candidate@example.com');
    $bot = (new User())->setEmail('gemini@hirely.local');

    $approved = (new ForumComment())
        ->setPost($post)
        ->setAuthor($human)
        ->setStatus('APPROVED')
        ->setContent('@gemini suggest tips');
    $pending = (new ForumComment())
        ->setPost($post)
        ->setAuthor($human)
        ->setStatus('PENDING')
        ->setContent('@gemini suggest tips');
    $botComment = (new ForumComment())
        ->setPost($post)
        ->setAuthor($bot)
        ->setStatus('APPROVED')
        ->setContent('@gemini suggest tips');
    $rejected = (new ForumComment())
        ->setPost($post)
        ->setAuthor($human)
        ->setStatus('REJECTED')
        ->setContent('@gemini suggest tips');

    assert_true($service->shouldReplyToComment($approved), 'Expected approved human comment to trigger a reply.');
    assert_true($service->shouldReplyToComment($pending), 'Expected pending human comment to trigger a reply.');
    assert_true(!$service->shouldReplyToComment($rejected), 'Expected rejected comment to avoid Gemini reply.');
    assert_true(!$service->shouldReplyToComment($botComment), 'Expected Gemini bot comment to avoid self-reply.');
});

test_case('Gemini trigger token is removed before prompt building', function (): void {
    $cleaned = GeminiClient::cleanTriggerToken("  @gemini   give me 3 CV tips  ");

    assert_same('give me 3 CV tips', $cleaned, 'Gemini trigger cleanup should remove token and normalize spaces.');
});

test_case('Forum post tag normalization keeps feed filters consistent', function (): void {
    $post = (new ForumPost())->setTag('  Career Tips  ');
    assert_same('#CareerTips', $post->getTag(), 'Tags should be stored with # and without spaces.');

    $post->setTag('   ');
    assert_same(null, $post->getTag(), 'Blank tags should become null.');
});

test_case('Forum interaction normalizes like target and type values', function (): void {
    $interaction = (new ForumInteraction())
        ->setTargetType('comment')
        ->setInteractionType('like')
        ->setTargetId(42);

    assert_same('COMMENT', $interaction->getTargetType(), 'Target type should be uppercase.');
    assert_same('LIKE', $interaction->getInteractionType(), 'Interaction type should be uppercase.');
    assert_same(42, $interaction->getTargetId(), 'Target id should be retained.');
});

test_case('Forum notification messages are safely capped for database storage', function (): void {
    $message = str_repeat('A', 300);
    $notification = (new ForumNotification())->setMessage($message);

    assert_same(255, strlen($notification->getMessage()), 'Notification message should fit varchar(255).');
    assert_true(str_ends_with($notification->getMessage(), '...'), 'Trimmed notification should end with ellipsis.');
});

test_case('Moderation note formatter and parser preserve AI decision metrics', function (): void {
    $report = (new ModerationReport())
        ->setDuplicateScore(1.7)
        ->setToxicity(-0.4)
        ->setRelevance(0.81)
        ->setQualityScore(0.74)
        ->setPredictedCategory('Career')
        ->setLinkThreat('FLAGGED')
        ->setLinkThreatTypes('MALWARE')
        ->setFallbackUsed(true)
        ->setDecision('pending');

    $note = (new ModerationNoteFormatter())->buildSimpleAiNote($report);
    $parsed = (new ModerationNoteParser())->parse($note);

    assert_same('1.00', $parsed['duplicate'], 'Duplicate score should be clamped and formatted.');
    assert_same('0.00', $parsed['toxicity'], 'Toxicity score should be clamped and formatted.');
    assert_same('Career', $parsed['category'], 'Predicted category should be parseable.');
    assert_same('Yes', $parsed['fallback'], 'Fallback flag should be preserved.');
    assert_same('PENDING', $parsed['decision'], 'Decision should be normalized to uppercase.');
});

test_case('Safe Browsing does not call external API when no URL exists', function (): void {
    $client = new SafeBrowsingClient(new MockHttpClient(), '', 1000);
    $result = $client->scanTextDetailed('This comment has no links at all.');

    assert_same('NONE', $result->getLinkThreat(), 'No URL should have no link threat.');
    assert_same('NONE', $result->getThreatTypes(), 'No URL should have no threat types.');
});

$passed = 0;
foreach ($tests as [$name, $test]) {
    try {
        $test();
        ++$passed;
        echo '[PASS] ' . $name . PHP_EOL;
    } catch (Throwable $exception) {
        echo '[FAIL] ' . $name . ' - ' . $exception->getMessage() . PHP_EOL;
        exit(1);
    }
}

echo 'Passed ' . $passed . ' forum unit tests.' . PHP_EOL;
