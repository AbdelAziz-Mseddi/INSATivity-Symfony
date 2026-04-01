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

        try {
            $checkQuery = 'SELECT id FROM public.users WHERE username = :username OR email = :email LIMIT 1';
            $checkStmt = $this->connection->prepare($checkQuery);
            $checkStmt->execute([
                ':username' => $username,
                ':email' => $email,
            ]);

            if ($checkStmt->fetch()) {
                throw new Exception('Username or university email already exists.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                throw new Exception('Failed to secure password.');
            }

            $insertQuery = '
                INSERT INTO public.users (full_name, username, email, major, password_hash)
                VALUES (:full_name, :username, :email, :major, :password_hash)
                RETURNING id, full_name, username, email, major, created_at
            ';

            $insertStmt = $this->connection->prepare($insertQuery);
            $insertStmt->execute([
                ':full_name' => $fullName,
                ':username' => $username,
                ':email' => $email,
                ':major' => $major,
                ':password_hash' => $passwordHash,
            ]);

            $user = $insertStmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                throw new Exception('Failed to create user account in database.');
            }

            return $user;
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23505') {
                throw new Exception('Username or university email already exists.');
            }

            throw new Exception('Failed to create user account in database.', 0, $e);
        }
    }

    public function loginUser($identifier, $password) {
        $identifier = strtolower(trim($identifier));

        try {
            $selectQuery = '
                SELECT id, full_name, username, email, major, password_hash
                FROM public.users
                WHERE username = :identifier OR email = :identifier
                LIMIT 1
            ';

            $selectStmt = $this->connection->prepare($selectQuery);
            $selectStmt->execute([
                ':identifier' => $identifier,
            ]);
            $user = $selectStmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Failed to query user account.', 0, $e);
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid username/email or password.');
        }

        # Strips the hash before returning the user array so it never leaks to the calling code.
        unset($user['password_hash']);
        return $user;
    }
}
