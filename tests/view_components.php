<?php

declare(strict_types=1);
require __DIR__.'/../bootstrap/app.php';

$directory = sys_get_temp_dir().'/expernet-views-'.bin2hex(random_bytes(6));
mkdir($directory, 0700);
$initialLevel = ob_get_level();
try {
    file_put_contents($directory.'/outer.php', '<?php echo $view->render("inner", ["text" => $text]);');
    file_put_contents($directory.'/inner.php', '<?php echo e($text);');
    file_put_contents($directory.'/broken.php', '<?php throw new RuntimeException("expected");');
    $view = new App\Support\View($directory);
    $output = $view->render('outer', ['text' => '<script>unsafe</script>', 'view' => new stdClass()]);
    if ($output !== '&lt;script&gt;unsafe&lt;/script&gt;') {
        throw new RuntimeException('Rendu imbriqué ou échappement incorrect.');
    }
    try {
        $view->render('broken');
        throw new LogicException('Exception masquée.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() !== 'expected') {
            throw $error;
        }
    }
    if (ob_get_level() !== $initialLevel) {
        throw new RuntimeException('Tampon de rendu non refermé.');
    }
    echo "Vues : sous-composants, échappement, variables internes et nettoyage sur erreur vérifiés.\n";
} finally {
    foreach (['outer', 'inner', 'broken'] as $name) {
        if (is_file($directory.'/'.$name.'.php')) unlink($directory.'/'.$name.'.php');
    }
    rmdir($directory);
}
