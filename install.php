<?php

/**
 * create jwt key
 */
try {
    \rex_config::set('graphql', 'key', bin2hex(random_bytes(32)));
}
catch (Exception $e) {
}
