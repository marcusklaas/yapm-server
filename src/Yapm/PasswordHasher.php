<?php

namespace Yapm;

class PasswordHasher {
    static public function hashPassword($password) {
        return sha1($password);
    }
}
