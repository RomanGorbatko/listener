<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

if (empty($_SERVER['JETBRAINS_REMOTE_RUN'])) {
    passthru('php bin/console cache:pool:clear cache.global_clearer --env=test');
    passthru('php bin/console doctrine:cache:clear-metadata');
    //    passthru('php bin/console doctrine:database:drop --force --env=test --if-exists');
    //    passthru('php bin/console doctrine:database:create --env=test');
    //    passthru('php bin/console doctrine:migrations:migrate --no-interaction --env=test');
    //    passthru('php bin/console doctrine:fixtures:load --purge-with-truncate --no-interaction --env=test');
}
