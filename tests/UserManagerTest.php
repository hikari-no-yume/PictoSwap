<?php
declare(strict_types=1);

namespace ajf\PictoSwap;

use PDO;

class UserManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $db;
    protected $manager;

    protected function setUp() {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec(file_get_contents(__DIR__ . '/../schema.sql'));

        $testData = <<<'SQL'
            INSERT INTO
                users(username, password_hash, timestamp)
            VALUES
                ('jenny_darling', '$2y$10$aTlwPOHk8juAymGptbfLbeYZoZ0PMeetBEj9GVJxLjsDYaqSm7jPW', datetime('now')), -- "you're my best friend"
                ('all_men', '$2y$10$FyWKl/5/iNO7q9RlmzvjNuhREb8tiSk0dML0Tyvd6z3kFTZ4b/KsS', datetime('now')), -- "are pigs"
                ('hes_eros', '$2y$10$O5ayUDYswGQ958GkqOfvgOLoEd20872pnIFr9TJU.XZwLYesp0Tuq', datetime('now')) -- "and he's apollo"
            ;
SQL;

        $this->db->exec($testData);

        $this->manager = new UserManager($this->db);
    }

    public function testUsernameExists() {
        $this->assertTrue($this->manager->usernameExists('jenny_darling'));
        $this->assertTrue($this->manager->usernameExists('all_men'));
        $this->assertTrue($this->manager->usernameExists('hes_eros'));

        $this->assertFalse($this->manager->usernameExists('who_is'));
        $this->assertFalse($this->manager->usernameExists('itll_make_you_feel'));
    }

    public function testUserIdExists() {
        $this->assertTrue($this->manager->userIdExists(1));
        $this->assertTrue($this->manager->userIdExists(2));
        $this->assertTrue($this->manager->userIdExists(3));

        $this->assertFalse($this->manager->userIdExists(0));
        $this->assertFalse($this->manager->userIdExists(7));
    }

    public function testFindUserIdByUsername() {
        $this->assertEquals(1, $this->manager->findUserIdByUsername('jenny_darling'));
        $this->assertEquals(2, $this->manager->findUserIdByUsername('all_men'));
        $this->assertEquals(3, $this->manager->findUserIdByUsername('hes_eros'));

        $this->assertNull($this->manager->findUserIdByUsername('who_is'));
        $this->assertNull($this->manager->findUserIdByUsername('itll_make_you_feel'));
    }

    /**
     * @expectedException ajf\PictoSwap\PictoSwapException
     */
    public function testRegisterConflict() {
        $this->manager->register("jenny_darling", "you're not my best friend?");
    }

    /**
     * @expectedException ajf\PictoSwap\PictoSwapException
     */
    public function testRegisterShortName() {
        $this->manager->register("je", "you're not my best friend?");
    }

    /**
     * @expectedException ajf\PictoSwap\PictoSwapException
     */
    public function testRegisterLongName() {
        $this->manager->register("jenny_darling_youre_my_best_friend", "but there's a few things you don't know");
    }

    /**
     * @expectedException ajf\PictoSwap\PictoSwapException
     */
    public function testRegisterIllegalCharactersName() {
        $this->manager->register("Jenny, darling", "you're my best friend");
    }

    public function testRegisterSuccess() {
        $this->manager->register("how_can_you_be", "so flawless");

        $stmt = $this->db->prepare('
            SELECT
                user_id, username, password_hash
            FROM
                users
            WHERE
                username = \'how_can_you_be\'
            ;
        ');
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(4, $row['user_id']);
        $this->assertEquals('how_can_you_be', $row['username']);
        $this->assertTrue(\password_verify('so flawless', $row['password_hash']));
    }

    /**
     * @expectedException ajf\PictoSwap\PictoSwapException
     */
    public function testLoginFailureMissingUser() {
        $this->manager->login('who_is', 'in your heart now?');
    }

    /**
     * @expectedException ajf\PictoSwap\PictoSwapException
     */
    public function testLoginFailureWrongPassword() {
        $this->manager->login('hes_eros', 'and nothing more.');
    }

    public function testLoginSuccess() {
        $result = $this->manager->login('jenny_darling', 'you\'re my best friend');

        $this->assertTrue($result['logged_in']);
        $this->assertEquals(1, $result['user_id']);
        $this->assertEquals("jenny_darling", $result['username']);
    }
}