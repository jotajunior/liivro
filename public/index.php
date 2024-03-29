<?php

// Composer autoload
require '../vendor/autoload.php';

try {
	$config = new Phalcon\Config\Adapter\Ini('../app/config/config.ini');

    //Register an autoloader
    $loader = new \Phalcon\Loader();

    $loader->registerDirs(array(
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->pluginsDir,
        $config->application->libraryDir
    ))->register();

    //Create a DI
    $di = new Phalcon\DI\FactoryDefault();

    //Setting up the view component
    $di->setShared('config', $config);

    $di->set('view', function() use ($config) {
        $view = new \Phalcon\Mvc\View();
        $view->setViewsDir($config->application->viewsDir);
        return $view;
    }, true);

	$di->setShared('url', function() use($config) {
    	$url = new Phalcon\Mvc\Url();
    	$url->setBaseUri($config->application->baseUri);
    	return $url;
	});

	$di->set('db', function() use ($config) {
        return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            "host" => $config->database->host,
            "username" => $config->database->username,
            "password" => $config->database->password,
            "dbname" => $config->database->name
        ));
    });

	$di->set('dispatcher', function() use ($di) {
	    $eventsManager = $di->getShared('eventsManager');
    	$security = new Security($di);
    	$eventsManager->attach('dispatch', $security);
	    $dispatcher = new Phalcon\Mvc\Dispatcher();
    	$dispatcher->setEventsManager($eventsManager);

    	return $dispatcher;
	});

    $di->setShared('session', function() {
        $session = new \Phalcon\Session\Adapter\Files();
        $session->start();
        return $session;
    });


    //Handle the request
    $application = new \Phalcon\Mvc\Application($di);

	$application->useImplicitView(true);

    echo $application->handle()->getContent();

} catch(\Phalcon\Exception $e) {
     echo "PhalconException: ", $e->getMessage();
}