<?php
// Container-only platform config, mounted over include/config_plateform.php by
// docker-compose. Only the values that genuinely differ per deployment come from
// the environment (DB credentials + public URLs) so no secrets are committed.
//
// Everything else stays a hard-coded literal on purpose: config.php interpolates
// INIT_T directly into a SQL query without escaping (see config.php:77), and that
// is only safe while it cannot be influenced from outside the codebase.

// config.php uses require(), not require_once(), so guard the declaration.
if (!function_exists('hp_env'))
{
    function hp_env(string $key, ?string $default = null): string
    {
        $value = getenv($key);

        if ($value === false || $value === '')
        {
            if ($default === null)
            {
                // Fail loudly at boot rather than connecting somewhere unintended.
                throw new RuntimeException("Required environment variable {$key} is not set");
            }

            return $default;
        }

        return $value;
    }
}

define('DB_SERVER', hp_env('DB_SERVER', 'db'));
define('DB_SERVER_USERNAME', hp_env('DB_SERVER_USERNAME'));
define('DB_SERVER_PASSWORD', hp_env('DB_SERVER_PASSWORD'));
define('DB_DATABASE', hp_env('DB_DATABASE', 'hp-data-fj'));

// Public base URLs. The app builds absolute links and redirects from these, so
// they must match how users actually reach the server, not the container port.
define('HTTP_SERVER', hp_env('HP_HTTP_SERVER'));
define('HTTPS_SERVER', hp_env('HP_HTTPS_SERVER'));

define('INIT_T', 'Pacific');
define('HP_VERSION', 'Serveur');
define('HP_ACCES', 'Open');
define('HP_SERVEUR', 'Hydro Pacifique');
define('TITRE_SMALL', 'Hydro Pacifique');
define('BACKGROUND_LOG', 'image/fond_index.jpg');
define('BACKGROUND_LOG_NOMAD', 'image/fond_index.jpg');
define('BACKGROUND_LOG_FOOTER', 'image/bkgd_footer.jpg');
define('LOGO_IMG', '');
