<?php
/**
 * Created by PhpStorm.
 * User: ajf
 * Date: 2016-01-02
 * Time: 22:22
 */
namespace ajf\PictoSwap;

interface UserManagerInterface
{
    // Finds out if a user with the given username exists
    public function usernameExists(string $username): bool;

    // Finds out if a user with the given ID exists
    public function userIdExists(int $user_id): bool;

    // Returns the ID of the user with the given username, or NULL if none
    public function findUserIdByUsername(string $username);

    // Registers a user
    public function register(string $username, string $password);

    // Logs in a user
    // Returns an array containing new session data (give to startUserSession())
    public function login(string $username, string $password): array;
}