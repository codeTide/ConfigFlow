<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/UiJsonCatalog.php';
require_once dirname(__DIR__) . '/src/UiMessageRenderer.php';

use ConfigFlow\Bot\UiJsonCatalog;
use ConfigFlow\Bot\UiMessageRenderer;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertSameString(string $expected, string $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nExpected: {$expected}\nActual: {$actual}");
    }
}

$tmp = tempnam(sys_get_temp_dir(), 'renderer-test-');
if ($tmp === false) {
    throw new RuntimeException('Failed to create temporary file.');
}

$templates = [
    'tests' => [
        'base' => '<b>Title</b> {name}',
        'trusted' => '<b>Body:</b> {html}',
        'double' => 'Double={{value}}',
        'missing' => 'Hello {present} {missing}',
    ],
];

file_put_contents($tmp, json_encode($templates, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
$renderer = new UiMessageRenderer(new UiJsonCatalog($tmp));

assertSameString('<b>Title</b> &lt;ali&gt;', $renderer->render('tests.base', ['name' => '<ali>']), 'Placeholder values must be escaped.');
assertSameString('<b>Body:</b> <i>ok</i>', $renderer->render('tests.trusted', ['html' => '<i>ok</i>'], ['html']), 'trustedHtmlVars must bypass escaping.');
assertSameString('Double=42', $renderer->render('tests.double', ['value' => 42]), 'Double-brace compatibility replacement failed.');

$oldLogErrors = ini_get('log_errors');
$oldErrorLog = ini_get('error_log');
$logFile = tempnam(sys_get_temp_dir(), 'renderer-log-');
if ($logFile === false) {
    throw new RuntimeException('Failed to create temporary log file.');
}
ini_set('log_errors', '1');
ini_set('error_log', $logFile);
assertSameString('Hello hi {missing}', $renderer->render('tests.missing', ['present' => 'hi']), 'Missing placeholders should remain untouched.');
$logBody = (string) file_get_contents($logFile);
assertTrue(str_contains($logBody, 'unresolved placeholders'), 'Missing placeholders should be logged.');
ini_set('log_errors', (string) $oldLogErrors);
ini_set('error_log', (string) $oldErrorLog);
@unlink($logFile);

$prodRenderer = new UiMessageRenderer();
$panelsText = $prodRenderer->render('admin.panel_settings.messages.panels_list', ['connection_status' => '<connected>']);
assertTrue(str_contains($panelsText, '<b>مدیریت سرویس‌های پنلی</b>'), 'Migrated template key must render full trusted template text.');
assertTrue(str_contains($panelsText, '&lt;connected&gt;'), 'Migrated template placeholders must escape values.');

$previewVars = [
    'base_url' => 'https://x.test',
    'username' => '<admin>',
    'password_masked' => '***',
];
$legacyPreview = $prodRenderer->render('admin.panel_settings.wizard.preview_message', $previewVars);
$canonicalPreview = $prodRenderer->render('admin.panel_settings.messages.wizard_preview', $previewVars);
assertSameString($legacyPreview, $canonicalPreview, 'Legacy/canonical preview templates must stay compatible.');

$panelViewText = $prodRenderer->render('admin.panels.messages.panel_view', [
    'panel_id' => 7,
    'panel_title' => '<Main>',
    'summary' => 'min=10 | max=20',
    'tip_text' => 'استفاده کنید',
]);
assertTrue(str_contains($panelViewText, 'سرویس پنلی #7'), 'Panel view template should interpolate panel id.');
assertTrue(str_contains($panelViewText, '&lt;Main&gt;'), 'Panel view template should escape title placeholder.');

$packageSummaryText = $prodRenderer->render('admin.types_packages.messages.wizard_summary', ['summary' => '<raw-summary>']);
assertTrue(str_contains($packageSummaryText, '&lt;raw-summary&gt;'), 'Type/package summary template should escape summary placeholder.');

$panelWizardSummaryText = $prodRenderer->render('admin.final_modules.messages.panel_wizard_summary', ['summary' => '<raw-panel-summary>']);
assertTrue(str_contains($panelWizardSummaryText, '&lt;raw-panel-summary&gt;'), 'Panel wizard summary template should escape summary placeholder.');

$mainMenuText = $prodRenderer->render('menus.messages.main_overview');
assertTrue(str_contains($mainMenuText, '<b>به فروشگاه ConfigFlow خوش آمدید!</b>'), 'Main menu overview should render trusted template HTML.');

$profileText = $prodRenderer->render('menus.messages.profile_overview', [
    'full_name' => '<name>',
    'username' => '@user',
    'user_id' => '123',
    'balance' => '5000',
], ['user_id']);
assertTrue(str_contains($profileText, '&lt;name&gt;'), 'Profile template should escape normal placeholders.');
assertTrue(str_contains($profileText, '<code>123</code>'), 'Profile template should keep trusted user_id placeholder in code tag.');

$supportText = $prodRenderer->render('menus.messages.support_overview', [
    'support_id' => '@support',
    'support_link_line' => "\n🌐 لینک پشتیبانی: <https://t.me/support>",
    'support_link_desc_line' => '',
]);
assertTrue(str_contains($supportText, '&lt;https://t.me/support&gt;'), 'Support optional lines should be escaped through placeholders.');

@unlink($tmp);
echo "UiMessageRenderer self-test passed.\n";
