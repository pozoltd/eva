<?php
use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Silex\Application;
use Silex\Provider;

require __DIR__ . '/meta.php';

$app = new Eva\Application();

$app['zdb'] = $app->share(function () {
    $zdb = \Zend_Db::factory('Pdo_Mysql', array(
        'host' => DB_HOST,
        'username' => DB_USER,
        'password' => DB_PASSWORD,
        'dbname' => DB_NAME,
        'charset' => DB_CHAR,
        'persistent' => false
    ));
    return $zdb;
});

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

$app->register(new Silex\Provider\SwiftmailerServiceProvider(), array());
$app['swiftmailer.options'] = array(
    'host' => SMTP_HOST,
    'port' => SMTP_PORT,
    'username' => SMTP_USER,
    'password' => SMTP_PASS,
    'encryption' => 'tls'
);

$app->register(new Silex\Provider\ValidatorServiceProvider(), array());
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallbacks' => array(
        'en'
    )
));

return $app;
