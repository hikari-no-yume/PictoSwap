<?php
declare(strict_types=1);

namespace ajf\PictoSwap;

use PDO;

class UserManager implements UserManagerInterface
{
    private $db;

    public function __construct(PDO $connection) {
        $this->db = $connection;
    }

    // Finds out if a user with the given username exists
    public function usernameExists(string $username): bool {
        $stmt = $this->db->prepare('
            SELECT
                COUNT(*)
            FROM
                users
            WHERE
                username = :username
            ;
        ');
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        // If 0 rows, no such user exists -> false
        // If 1 row, user exists -> true
        return (bool)($row['COUNT(*)']);
    }

    // Finds out if a user with the given ID exists
    public function userIdExists(int $user_id): bool {
        $stmt = $this->db->prepare('
            SELECT
                COUNT(*)
            FROM
                users
            WHERE
                user_id = :user_id
            ;
        ');
        $stmt->execute([':user_id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        // If 0 rows, no such user exists -> false
        // If 1 row, user exists -> true
        return (bool)($row['COUNT(*)']);
    }

    // Returns the ID of the user with the given username, or NULL if none
    public function findUserIdByUsername(string $username) {
        // Look up user
        $stmt = $this->db->prepare('
            SELECT
                user_id
            FROM
                users
            WHERE
                username = :username
            LIMIT
                1
            ;
        ');
        $stmt->execute([':username' => $username]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            return NULL;
        } else {
            return $rows[0]['user_id'];
        }
    }

    // Registers a user
    public function register(string $username, string $password) {
        if ($this->usernameExists($username)) {
            throw new PictoSwapException("There is already a user with that username");
        }

        if (!\preg_match(VALID_USERNAME_REGEX, $username)) {
            throw new PictoSwapException("Username can only be 3-18 characters in length, composed only of lowercase letters, numbers and underscores");
        }

        $password_hash = \password_hash($password, PASSWORD_BCRYPT);

        $this->db->beginTransaction();
        $stmt = $this->db->prepare('
            INSERT INTO
                users(username, password_hash, timestamp)
            VALUES
                (:username, :password_hash, datetime(\'now\'))
            ;
        ');
        $stmt->execute([
            ':username' => $username,
            ':password_hash' => $password_hash
        ]);
        $user_id = $this->db->lastInsertId();
        $this->db->commit();
    }

    // Logs in a user
    // Returns an array containing new session data (give to startUserSession())
    public function login(string $username, string $password): array {
        $stmt = $this->db->prepare('
            SELECT
                user_id,
                password_hash
            FROM
                users
            WHERE
                username = :username
            LIMIT
                1
            ;
        ');
        $stmt->execute([':username' => $username]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            throw new PictoSwapException("No such user.", 404);
        }
        $password_hash = $rows[0]['password_hash'];
        if (!\password_verify($password, $password_hash)) {
            throw new PictoSwapException("Incorrect password.");
        }
        return [
            'logged_in' => TRUE,
            'user_id' => (int)$rows[0]['user_id'],
            'username' => $username
        ];
    }
}
