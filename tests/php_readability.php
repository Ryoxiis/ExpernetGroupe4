<?php

declare(strict_types=1);
require __DIR__.'/../bootstrap/app.php';

use App\Support\Dates;
use App\Support\HtmlContent;
use App\Support\Text;

$checks = 0;
function verify(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}

foreach (['abc-123', 'ABC', '019'] as $value) {
    verify(Text::containsOnly($value, Text::LETTERS.Text::DIGITS.'-'), 'Valid identifier');
}
foreach (['', "abc\n", 'é', 'abc/def', 'abc.def', "a\0b"] as $value) {
    verify(!Text::containsOnly($value, Text::LETTERS.Text::DIGITS.'-'), 'Reject malformed identifier');
}
foreach (["\0", "\n", "\r", "\t", chr(31)] as $value) {
    verify(Text::hasControlCharacters('before'.$value.'after'), 'Detect hidden controls');
}
verify(!Text::hasControlCharacters('Salle Océan'), 'Ordinary spaces allowed in text');
verify(Text::hasControlCharacters('bad url', true), 'Reject spaces in URL');
foreach (['2026-10-23T12:30:45Z','2026-10-23T12:30:45.123Z','2026-10-23T12:30:45+04:00','2026-10-23T12:30:45.123-02:00'] as $value) {
    verify(Dates::isoTimestamp($value) !== null, 'ISO timestamp accepted');
}
foreach (['tomorrow','2026-02-31T12:30:45Z','2026-10-23T25:00:00Z','2026-10-23T12:30:45','2026-10-23T12:30:45.1Z',"2026-10-23T12:30:45Z\n", "2026-10-23T12:30:45Z\0"] as $value) {
    verify(Dates::isoTimestamp($value) === null, 'Malformed timestamp rejected');
}
verify(Dates::isoTimestamp('2026-10-23T12:30:45+04:00')->setTimezone(new DateTimeZone('UTC'))->format('H:i') === '08:30', 'Explicit time zone preserved');
$html = new HtmlContent([]);
foreach (["https://example.com/a\nb", 'https://example.com/a b', 'javascript:alert(1)'] as $href) {
    verify($html->safeUrl($href) === null, 'Unsafe URL rejected');
}
verify($html->safeUrl('https://example.com/test?a=1&b=2') !== null, 'Ordinary URL retained');
foreach (['../config/app', 'pedagogy/../login', "pages/error\n", 'Pages/error', ''] as $name) {
    try {
        (new App\Support\View(PROJECT_ROOT.'/app/Views'))->render($name);
        throw new LogicException('Unsafe view accepted');
    } catch (RuntimeException $error) {
        verify($error->getMessage() === 'Nom de vue invalide.', 'Unsafe view rejected');
    }
}
// Check .env parsing in an isolated folder, without reading or changing the real secrets.
$directory = sys_get_temp_dir().'/expernet-env-check-'.bin2hex(random_bytes(6));
mkdir($directory.'/bootstrap', 0700, true);
copy(PROJECT_ROOT.'/bootstrap/environment.php', $directory.'/bootstrap/environment.php');
file_put_contents($directory.'/.env', "\n# comment\n  SIMPLE_CHECK_A='hello world'\nSIMPLE_CHECK_B=a=b\ninvalid=value\nSIMPLE_CHECK_C =invalid\n");
try {
    putenv('SIMPLE_CHECK_B=terminal');
    require $directory.'/bootstrap/environment.php';
    verify(getenv('SIMPLE_CHECK_A') === 'hello world', 'Quoted env value');
    verify(getenv('SIMPLE_CHECK_B') === 'terminal', 'Exported env has priority');
    verify(getenv('invalid') === false && getenv('SIMPLE_CHECK_C') === false, 'Invalid env keys ignored');
} finally {
    foreach (['SIMPLE_CHECK_A','SIMPLE_CHECK_B','SIMPLE_CHECK_C'] as $key) {
        putenv($key);
    }
    unlink($directory.'/.env');
    unlink($directory.'/bootstrap/environment.php');
    rmdir($directory.'/bootstrap');
    rmdir($directory);
}
echo "$checks vérifications : validations, dates, URL, vues et lecture .env.\n";
