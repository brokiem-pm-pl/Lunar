<?php

function git_hash(): string {
    return shell_exec("git rev-parse HEAD");
}

function pause(): void {
    fread(STDIN, 1);
    fflush(STDIN);
}

function new_logger(): Generator {
    while (true) {
        $r = yield;
        echo '[*] ', $r, PHP_EOL;
    }
}

$logger = new_logger();
$logger->send('Lunar anticheat build script');

$hash = substr(git_hash(), 0, 16);
$logger->send("Hash: $hash");

$phar_name = "Lunar.phar";
$logger->send("File: $phar_name");

@unlink($phar_name);

$logger->send('Press [Enter] to build');
pause();

$phar = new Phar($phar_name);
$phar->setSignatureAlgorithm(Phar::SHA512);
$phar->compressFiles(Phar::GZ);
$before = microtime(true);
$phar->startBuffering();
$phar->buildFromDirectory('./', <<<REGEXP
/\.(php|yml)/
REGEXP
);
$phar->stopBuffering();
$logger->send('Build Success!');
$logger->send(sprintf('Time Used: %.6f', microtime(true) - $before));