<?php
use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Silex\Application;
use Silex\Provider;

require __DIR__ . '/metadata.php';
 
$app = new Eva\Application();

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
$driverImpl = $config->newDefaultAnnotationDriver(array(
    __DIR__ . "/../cache"
), false);

$config->setMetadataDriverImpl($driverImpl);
$config->setProxyDir(__DIR__ . '/../cache/Proxies');
$config->setProxyNamespace('Proxies');
$connectionOptions = array(
    'driver' => 'pdo_mysql',
    'host' => DB_HOST,
    'dbname' => DB_NAME,
    'user' => DB_USER,
    'password' => DB_PASS,
    'charset' => DB_CHAR
);
$app['em'] = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);

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
