<?php

namespace Yapm;

class PasswordHasher {
    static public function hashPassword($pasword) {
        return sha1($password);
    }
}
