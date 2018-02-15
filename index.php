<?php
    $matches = array();
    $requestUri = $_SERVER['REQUEST_URI'];
    preg_match_all('/(?<controller>(?<=^\/)\w+)|(?<method>(?<=\/)\w+)/', $requestUri, $matches);
    $controllerName = empty($matches[0][0]) ? 'Index' : ucfirst(strtolower($matches[0][0]));
    $action = empty($matches[0][1]) ? 'index' : strtolower($matches[0][1]);
    $controllersFolder = "controller";
    $controllerInstance = $controllersFolder . DIRECTORY_SEPARATOR .  $controllerName . "Controller.php";
    $viewClassFile = "View.php";
    require_once($viewClassFile);
    $view = new View(strtolower($controllerName), $action);
    if(file_exists($controllerInstance)) {
        include_once($controllerInstance);
        $controllerClass = $controllerName . "Controller";
        $controllerObject = new $controllerClass();
        $functionName = $action . "Action";
        if(method_exists($controllerClass, $functionName)) {
            $controllerObject->$functionName($view, array());
        } else {
            header("Location: http://bees.cloudvps.bg/file");
        }

        $view->render();
    } else {
        header("Location: http://bees.cloudvps.bg/file");
    }

