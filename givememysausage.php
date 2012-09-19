<?php

$COMPOSER_INSTALLER = "http://getcomposer.org/installer";
$BASE = getcwd();

main($argv);

function main($argv)
{
    global $COMPOSER_INSTALLER, $BASE;
    $msg = <<<EOF
Welcome to the Sausage installer!
---------------------------------
EOF;
//We're first going to install Composer (http://getcomposer.org) in this
//directory. Composer has a script which will check your system's readiness
//to run it. Pay attention to any errors or warnings it spits out, and fix them
//if it does so. Then re-run this installer. Here we go...
//EOF;
    out($msg, 'info');
    out("Checking initial system requirements...");
    checkInitialRequirements();
    startComposer();

}

function checkInitialRequirements()
{
    $fail = false;
    if(!ini_get('allow_url_fopen')) {
        out("Looks like you don't have fopen() URL support. To enable this set allow_url_fopen=1 in your php.ini", 'error');
        $fail = true;
    }
    if(ini_get('safe_mode')) {
        out("Looks like you have safe mode enabled. Let's live a little more dangerously. Set safe_mode=0 in your php.ini", 'error');
        $fail = true;
    }

    if ($fail) {
        exit(1);
    }
}

function startComposer()
{
    out("Downloading Composer install script...");
    $php = file_get_contents($COMPOSER_INSTALLER);
    file_put_contents("$BASE/getcomposer.php", $php);
    out("Installing composer...");
    exec("php $BASE/getcomposer.php", $output, $return_var);
    if ($return_var !== 0) {
        out("Uh oh! Installing composer didn't go smoothly. Here's the output so you can see what went wrong and retry installing Sausage:", "error");
        foreach ($output as $line) {
            echo "\t$line\n";
        }
        exit($return_var);
    }
}

function out($text, $color = null, $newLine = true)
{
    if (DIRECTORY_SEPARATOR == '\\') {
        $hasColorSupport = false !== getenv('ANSICON');
    } else {
        $hasColorSupport = true;
    }

    $styles = array(
        'success' => "\033[0;32m%s\033[0m",
        'error' => "\033[31;31m%s\033[0m",
        'info' => "\033[33;33m%s\033[0m"
    );

    $format = '%s';

    if (isset($styles[$color]) && $hasColorSupport) {
        $format = $styles[$color];
    }

    if ($newLine) {
        $format .= PHP_EOL;
    }

    printf($format, $text);
}
