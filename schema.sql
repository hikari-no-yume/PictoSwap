CREATE TABLE IF NOT EXISTS users (
    user_id         INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp       DATETIME NOT NULL,
    username        STRING UNIQUE NOT NULL,
    password_hash   STRING NOT NULL
);

CREATE TABLE IF NOT EXISTS letters (
    letter_id       INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id         INTEGER NOT NULL,
    timestamp       DATETIME NOT NULL,
    content         STRING NOT NULL,
    CONSTRAINT letters_user_id_to_users_user_id FOREIGN KEY (user_id) REFERENCES users(user_id)
);
