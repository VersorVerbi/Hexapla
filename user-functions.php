<?php

/**
 * @return int
 */
function CURRENT_USER(): int {
    // TODO: handle this differently once we have logins, et al
    return 1;
}

/**
 * Indicates that the user can read texts and that texts can be read; always on
 * @return int 0000 0001
 */
function CAN_READ(): int {
    return 1;
}

/**
 * Indicates that the user can take notes and that texts can have associated notes; always on for logged-in users
 * @return int 0000 0010
 */
function CAN_NOTE(): int {
    return 2;
}

/**
 * Indicates that the user can diff text and that texts can be diffed against other texts in the same language;
 * depends on copyright issues and allowances by publishers
 * @return int 0000 0100
 */
function CAN_DIFF(): int {
    return 4;
}

/**
 * Indicates that the user can focus on a particular word and see what other translations used for the same original;
 * requires lemma or Strong's identification in the text
 * @return int 0000 1000
 */
function CAN_FOCUS(): int {
    return 8;
}

/**
 * Indicates that the user is qualified to upload new texts to the database and get them entered properly; based on
 * credentials and user training. Does not apply to texts.
 * @return int 0001 0000
 */
function CAN_UPLOAD(): int {
    return 16;
}

/**
 * Indicates that the user can parse lemmas from source texts and modify the database to identify or assign lemmas;
 * based on credentials and user training. Does not apply to texts.
 * @return int 0010 0000
 */
function CAN_PARSE(): int {
    return 32;
}