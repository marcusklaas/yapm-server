<?php

namespace Yapm;

class PasswordVerifier {
    private $secretPath;

    private $secret;

    public function __construct($secretPath) {
        $this->secretPath = $secretPath;
    }

    private function fetchSecret() {
        $this->secret = trim(file_get_contents($this->secretPath));
    }

    public function isValidPassword($password) {
        if (!$this->secret) {
            $this->fetchSecret();
        }

        return PasswordHasher::hashPassword($password) === $this->secret;
    }
}
