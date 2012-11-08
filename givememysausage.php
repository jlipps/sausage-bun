<?php

// givememysausage.php
//
// Usage:
//
// php givememysausage.php <sauceusername> <sauceaccesskey>
//
// You can find or change your Sauce access key on your Sauce account page:
//
// https://saucelabs.com/account

$COMPOSER_INSTALLER = "http://getcomposer.org/installer";
$DEMO_URL = "http://raw.github.com/jlipps/sausage/master/WebDriverDemo.php";
$RC_DEMO_URL = "http://raw.github.com/jlipps/sausage/master/SeleniumRCDemo.php";
$APP_DEMO_URL = "http://raw.github.com/jlipps/sausage/master/WebDriverDemoShootout.php";
$BASE = getcwd();

$WIN_UNAMES = array(
    'MINGW32_NT-6.0',
    'UWIN-W7',
    'WIN32',
    'WINNT',
    'Windows',
    'Windows NT'
);

$IS_WIN = in_array(php_uname('s'), $WIN_UNAMES);
$PHP_BIN = PHP_BINDIR.($IS_WIN ? '\\' : '/').'php'.($IS_WIN ? '.exe' : '');

if (count($argv) == 3) {
    $SAUCE_USERNAME = $argv[1];
    $SAUCE_ACCESS_KEY = $argv[2];
}

main($argv);

function main($argv)
{
    global $IS_WIN, $FIX;

    $opts = getopt("m::");
    $minimal_run = isset($opts['m']);
    $msg1 = <<<EOF

Welcome to the Sausage installer!
---------------------------------
EOF;
    $msg2 = <<<EOF

    ( \                 / )
     \ \.-------------./ /
      \(    hot dog!   )/
        `.___________.'

---------------------------------
EOF;
    out($msg1, 'info');
    if (!$minimal_run)
        out($msg2, 'info');
    checkPHP();
    checkInitialRequirements();
    startComposer();
    installPackages($minimal_run);
    if (!$minimal_run) {
        configureSauce();
        downloadDemo();
        if (!$FIX) {
            out("- You're all set!");
        } else {
            out("- Oops! Found an issue...please fix the issue and try again!");
        }
        if (!$IS_WIN) {
            out("Try running 'vendor/bin/paraunit --processes=8 --path=WebDriverDemo.php'", 'success');
            out("  (change to: --path=SeleniumRCDemo.php for Selenium 1)", 'success');
            out("Then load https://saucelabs.com/account to see your tests running in parallel", 'success');
            out("Get the most out of Sausage: https://github.com/jlipps/sausage/blob/master/README.md", 'info');
        }
    } else {
        out("Sausage successfuly set up!", 'success');
    }
    out('');
}

function checkPHP()
{
    global $PHP_BIN;
    out("- Checking for PHP...", NULL, false);
    if (!is_file($PHP_BIN)) {
        out("failed", 'error');
        out("Could not find any PHP binary at $PHP_BIN. Please make sure PHP is installed!");
        exit(1);
    } else {
        out("done", 'success');
    }
}

function checkInitialRequirements()
{
    out("- Checking initial system requirements...", NULL, false);
    $errors = array();
    $solutions = array();

    if(!ini_get('allow_url_fopen')) {
        $errors['fopen'] = "You don't have fopen() URL support.";
        $solutions['fopen'] = "Set allow_url_fopen=1 in your php.ini";
    }

    if(ini_get('safe_mode')) {
        $errors['safe_mode'] = "You have safe mode enabled.";
        $solutions['safe_mode'] = "Set safe_mode=0 in your php.ini";
    }

    $extensions = array('curl', 'dom', 'pcre', 'Phar', 'SPL', 'Reflection', 'openssl');
    foreach ($extensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[$ext] = "You don't have the $ext PHP extension loaded.";
            $solutions[$ext] = "Install the $ext extension.";
        }
    }

    if (count($errors)) {
        out("failed", 'error');
        out('');
        out("Your system didn't meet our requirements check. Here's what's wrong:", 'info');
        foreach ($errors as $err_type => $error) {
            $solution = isset($solutions[$err_type]) ? $solutions[$err_type] : NULL;
            out("- $error", 'error');
            if ($solution)
                out("  - To fix: $solution", 'info');
        }
        exit(1);
    } else {
        out("done", 'success');
    }
}

function startComposer()
{
    global $COMPOSER_INSTALLER, $BASE, $PHP_BIN;
    if (is_file("$BASE/composer.phar")) {
        out("- Composer already installed", 'success');
    } else {
        out("- Downloading Composer install script...", NULL, false);
        $php = file_get_contents($COMPOSER_INSTALLER);
        out("done", 'success');
        file_put_contents("$BASE/getcomposer.php", $php);
        out("- Installing Composer...", NULL, false);
        list($output, $exitcode) = runProcess("$PHP_BIN $BASE/getcomposer.php");
        if ($exitcode !== 0) {
            $msg = <<<EOF
Uh oh! Installing Composer didn't go smoothly.
Composer has a variety of PHP requirements, so you might need to update your
system or your php.ini file in order to get it to work. We'll show you the
ouput from the Composer install attempt below. Check it out to see how you
might be able to see what's going on and fix it (then retry installing Sausage):
==============================================================================
EOF;
            out('failed', 'error');
            out($msg, "error");
            out($output);
            out("==============================================================================", 'error');
            exit($exitcode);
        }
        out("done", 'success');
    }
    if (is_file("$BASE/getcomposer.php")) {
        unlink("$BASE/getcomposer.php");
    }
    out("- Making sure Composer is up to date...", NULL, false);
    list($output, $exitcode) = runProcess("$PHP_BIN $BASE/composer.phar self-update");
    if ($exitcode !== 0) {
        out('failed', 'error');
        out("Darn. Composer failed its own self-update! Here's what it had to say:");
        out($output);
        exit($exitcode);
    }
    out('done', 'success');
}

function installPackages($minimal_run = false)
{
    global $BASE, $PHP_BIN, $IS_WIN;
    out("- Downloading and unpacking Sausage and dependencies (this may take a while)...", NULL, false);

$json = <<<EOF
{
    "require": {
        "sauce/sausage": ">=0.5.0"
    }
}
EOF;
    file_put_contents("$BASE/composer.json", $json);
    list($output, $exitcode) = runProcess("$PHP_BIN $BASE/composer.phar install");
    if ($exitcode !== 0) {
        $msg = <<<EOF
Oops. Installing the Sausage and PHPUnit packages didn't work.
Below is the output from the Composer script so you can see what went wrong.
Feel free to e-mail this to help@saucelabs.com so we can make sure this install
script doesn't bomb out here in the future!
===============================================================================
EOF;
        out('failed', 'error');
        out($msg, "error");
        out($output);
        out("===============================================================================", 'error');
        exit($exitcode);
    }
    out("done", 'success');
    if (!$minimal_run) {
        if (!$IS_WIN) {
            out("  (You might also want Sauce Connect: add sauce/connect to your composer.json)", 'info');
        }
    }
    if (is_dir("$BASE/vendor")) {
        out("- Updating packages...", NULL, false);
        list($output, $exitcode) = runProcess("$PHP_BIN $BASE/composer.phar update");
        if ($exitcode !== 0) {
            $msg = <<<EOF
Uh oh. There was a problem updating the Composer packages. The only reason we
are trying to update them is that you had a vendor/ directory here. Not sure
what went wrong, but maybe the Composer output below will help you. If all else
fails, delete composer.phar, composer.json, and the vendor/ directory and try
running this script again.
===============================================================================
EOF;
            out('failed', 'error');
            out($msg, 'error');
            out($output);
            out("===============================================================================", 'error');
            exit($exitcode);
        }
        out("done", 'success');
    }
}

function configureSauce()
{
    global $BASE, $IS_WIN, $SAUCE_USERNAME, $SAUCE_ACCESS_KEY;

    out("- Configuring Sauce...", NULL, false);

    list($output, $exitcode) = runProcess("$BASE/vendor/bin/sauce_config $SAUCE_USERNAME $SAUCE_ACCESS_KEY");
    if ($exitcode !== 0) {
        out('failed', 'error');
        $FIX = TRUE;
        if (!$IS_WIN) {
            out("  Sauce configuration failed. Please run vendor/bin/sauce_config USERNAME ACCESS_KEY manually.", 'info');
        } else {
            out("  Sauce configuration failed. Usage: php givememysausage.php <sauceusername> <sauceaccesskey>", 'info');
        }
    } else {
        out('done', 'success');
    }
}

function downloadDemo()
{
    global $DEMO_URL, $RC_DEMO_URL, $APP_DEMO_URL, $BASE;
    out("- Downloading demo test files...", NULL, false);
    file_put_contents("$BASE/WebDriverDemo.php", file_get_contents($DEMO_URL));
    file_put_contents("$BASE/SeleniumRCDemo.php", file_get_contents($RC_DEMO_URL));
    file_put_contents("$BASE/WebDriverDemoShootout.php", file_get_contents($APP_DEMO_URL));
    out("done", 'success');
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

function runProcess($cmd)
{
    $process = proc_open($cmd, array(array("pipe", "r"), array("pipe", "w"), array("pipe", "w")), $pipes, NULL);
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
    $status = proc_get_status($process);
    $output = '';

    while ($status['running']) {
        $out_streams = array($pipes[1], $pipes[2]);
        $e = NULL; $f = NULL;
        $num_changed = stream_select($out_streams, $e, $f, 0, 20000);
        if ($num_changed) {
            foreach ($out_streams as $changed_stream) {
                $output .= stream_get_contents($changed_stream);
            }
        }
        $status = proc_get_status($process);
    }

    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return array($output, $status['exitcode']);
}
