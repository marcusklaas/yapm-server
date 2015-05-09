<?php

namespace Yapm;

class LibraryVersionExtractor {
    static public function extractFromJson($json) {
        if (gettype($json) !== 'string') {
            throw new VersionExtractionException('Couldn\'t read library from disk');
        }

        $object = json_decode($json, true);

        if (null === $object || ! isset($object['library'])) {
            throw new VersionExtractionException('Invalid library');
        }

        $library = json_decode($object['library'], true);

        if (null === $library || ! isset($library['library_version'])) {
            throw new VersionExtractionException('Invalid library');
        }

        $version = intval($library['library_version']);

        if (0 === $version) {
            throw new VersionExtractionException('Invalid library version');
        }

        return $version;
    }
}
