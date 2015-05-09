<?php

require_once __DIR__.'/vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Yapm\PasswordVerifier;
use Yapm\LibraryVersionExtractor;

$hashfile = 'encrypted/passhash.txt';
$pwlib = 'encrypted/passwords';
$pwlibext = 'txt';

$app = new Application();

// Library updates, master password changes
$app->post('/', function (Request $request) use ($hashfile, $pwlib, $pwlibext) {
    $password = $request->get('pwhash', '');
    $passwordVerifier = new PasswordVerifier($hashfile);

    if (!$passwordVerifier->isValidPassword($password)) {
        return new Response('Invalid password', 400);
    }

    $libraryPath = $pwlib . '.' . $pwlibext;

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
            previousVersion + 1,
            $newVersion
        );

        return new Response($message, 400);
    }

    $backupPath = $pwlib . $newVersion . '.' . $pwlibext;

    if( ! file_put_contents($backupPath, $newLibraryJson) ||
        ! file_put_contents($libraryPath, $newLibraryJson)) {
        return new Response('Failed writing new library to disk', 500);
    }

    $newPassword = $request->get('newhash', null);

    if ($newPassword) {
        $passwordHash = PasswordHasher::hashPassword($newPassword);

        // Try to write new hash to disk
        if ( ! file_put_contents($hashfile, $passwordHash)) {
            // Restore library if it failed
            $previousLibraryPath = $pwlib . $previousVersion . '.' . $pwlibext;

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
$app->get('/', function (Request $request) use ($app, $pwlib, $pwlibext) {
    $path = $pwlib . '.' . $pwlibext;

    if (!file_exists($path)) {
        $app->abort(404);
    }

    return $app->sendFile($path);
});

$app->run();
