<?php

require_once 'Database.php';

class AuthManager {
    private $connection;

    public function __construct() {
        $this->connection = Database::connect();
    }

    public function registerUser($fullName, $username, $emailLocalPart, $major, $password) {
        $fullName = trim($fullName);
        $username = strtolower(trim($username));
        $emailLocalPart = strtolower(trim($emailLocalPart));
        $major = strtoupper(trim($major));
        $email = $emailLocalPart . '@insat.ucar.tn';

        $checkQuery = 'SELECT id FROM public.users WHERE username = $1 OR email = $2 LIMIT 1';
        $checkResult = pg_query_params($this->connection, $checkQuery, [$username, $email]);
        if ($checkResult === false) {
            throw new Exception('Failed to validate existing user account.');
        }

        if (pg_num_rows($checkResult) > 0) {
            throw new Exception('Username or university email already exists.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new Exception('Failed to secure password.');
        }

        $insertQuery = '
            INSERT INTO public.users (full_name, username, email, major, password_hash)
            VALUES ($1, $2, $3, $4, $5)
            RETURNING id, full_name, username, email, major, created_at
        ';

        $insertResult = pg_query_params(
            $this->connection,
            $insertQuery,
            [$fullName, $username, $email, $major, $passwordHash]
        );

        if ($insertResult === false) {
            throw new Exception('Failed to create user account in database.');
        }

        return pg_fetch_assoc($insertResult);
    }

    public function loginUser($identifier, $password) {
        $identifier = strtolower(trim($identifier));

        $selectQuery = '
            SELECT id, full_name, username, email, major, password_hash
            FROM public.users
            WHERE username = $1 OR email = $1
            LIMIT 1
        ';

        $selectResult = pg_query_params($this->connection, $selectQuery, [$identifier]);
        if ($selectResult === false) {
            throw new Exception('Failed to query user account.');
        }

        $user = pg_fetch_assoc($selectResult);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid username/email or password.');
        }

        unset($user['password_hash']);
        return $user;
    }
}
