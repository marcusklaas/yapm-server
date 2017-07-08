<?php

require_once __DIR__.'/vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Yapm\PasswordVerifier;
use Yapm\LibraryVersionExtractor;
use Yapm\PasswordHasher;
use DerAlex\Silex\YamlConfigServiceProvider;

$hashFile = 'encrypted/passhash.txt';
$passwordPathStem = 'encrypted/passwords';
$passwordFileExtension = 'txt';

$app = new Application();
$app->register(new YamlConfigServiceProvider(__DIR__ . '/config.yml'));

// Library updates, master password changes
$app->post('/', function (Request $request) use ($hashFile, $passwordPathStem, $passwordFileExtension, $app) {
    $password = $request->get('pwhash', '');
    $passwordVerifier = new PasswordVerifier($hashFile);

    if (!$passwordVerifier->isValidPassword($password)) {
        return new Response('Invalid password', 400);
    }


    $newPassword = $request->get('newhash', null);
    if ($newPassword && ! $app['config']['master_key']['allow_edit']) {
        return new Response('Changing the master key is disabled', 400);
    }

    $libraryPath = $passwordPathStem . '.' . $passwordFileExtension;

    // read current library, get version
    $currentLibraryJson = file_get_contents($libraryPath);
    $previousVersion = LibraryVersionExtractor::extractFromJson($currentLibraryJson);

    // read new library, get version
    $newLibraryJson = $request->get('newlib', '');
    $newVersion = LibraryVersionExtractor::extractFromJson($newLibraryJson);

    // make sure new version is 1 greater than old version
    if ($previousVersion + 1 !== $newVersion) {
        $message = sprintf(
            'Version mismatch. Expected %d, got %d',
            $previousVersion + 1,
            $newVersion
        );

        return new Response($message, 400);
    }

    $backupPath = $passwordPathStem . $newVersion . '.' . $passwordFileExtension;

    if( ! file_put_contents($backupPath, $newLibraryJson) ||
        ! file_put_contents($libraryPath, $newLibraryJson)) {
        return new Response('Failed writing new library to disk', 500);
    }

    if ($newPassword) {
        $passwordHash = PasswordHasher::hashPassword($newPassword);

        // Try to write new hash to disk
        if ( ! file_put_contents($hashFile, $passwordHash)) {
            // Restore library if it failed
            $previousLibraryPath = $passwordPathStem . $previousVersion . '.' . $passwordFileExtension;

            if ( ! rename($previousLibraryPath, $libraryPath)) {
                // Even that failed, abort!
                return new Response('Updated library, but could not update password', 500);
            } else {
                return new Response('Could not update password', 500);
            }
        }
    }

    return new Response('Updated library', 200);
});

// Serve the most recent library
$app->get('/', function () use ($app, $passwordPathStem, $passwordFileExtension) {
    $path = $passwordPathStem . '.' . $passwordFileExtension;

    if (!file_exists($path)) {
        $app->abort(404);
    }

    return $app->sendFile($path);
});

$app->match("{url}", function($url) use ($app) {
        return "OK";
    })
    ->assert('url', '.*')
    ->method("OPTIONS");

$app->run();
