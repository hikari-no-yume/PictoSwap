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

CREATE TABLE IF NOT EXISTS letter_recipients (
    user_id         INTEGER NOT NULL,
    letter_id       INTEGER NOT NULL,
    timestamp       DATETIME NOT NULL,
    read            BOOLEAN NOT NULL,
    PRIMARY KEY(user_id, letter_id),
    CONSTRAINT letter_recipients_user_id_to_users_user_id FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT letter_recipients_letter_id_to_letters_letter_id FOREIGN KEY (letter_id) REFERENCES letters(letter_id)
);

CREATE TABLE IF NOT EXISTS friendships (
    user_id_1       INTEGER NOT NULL,
    user_id_2       INTEGER NOT NULL,
    timestamp       DATETIME NOT NULL,
    provisional     BOOLEAN NOT NULL,
    PRIMARY KEY(user_id_1, user_id_2),
    CONSTRAINT friendships_user_id_1_to_users_user_id FOREIGN KEY (user_id_1) REFERENCES users(user_id),
    CONSTRAINT friendships_user_id_2_to_users_user_id FOREIGN KEY (user_id_2) REFERENCES users(user_id)
);
