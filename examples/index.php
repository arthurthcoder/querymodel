<?php
    date_default_timezone_set("America/Sao_Paulo");

    define("DS", DIRECTORY_SEPARATOR);
    require_once dirname(__DIR__).DS."vendor".DS."autoload.php";
    require_once dirname(__FILE__).DS."config".DS."Config.php";

    use BaseCode\QueryModel\QueryModel;

    Class User extends QueryModel
    {
        protected $table = "users";
        protected $primary = "idUser";

        protected $timestamp = [
            "create" => "at_created",
            "update" => "at_updated"
        ];

        protected $required = [
            "nameUser",
            "emailUser",
            "passwordUser"
        ];

    }

    $user = new User();

    echo "<pre>";
    
    // $test = $user->findBy(
    //     "*",
    //     "idUser = :id OR emailUser = :email",
    //     ["id" => $user->idUser, "email" => $user->emailUser]
    // )->execute();

    // print_r($user->findBy(
    //     "*",
    //     "idUser = :id",
    //     ["id" => 2]
    // )->execute());


    // print_r($user->findBy(
    //     "*",
    //     "nameUser LIKE :name",
    //     ["name" => "%henrique"])->execute()
    // );

    /* example save */
    // $user->nameUser = "Thcoder";
    // $user->emailUser = "contato@thcoder.com.br";
    // $user->passwordUser = "Senha123";
    // $user->save();
    
    /* example delete */
    // $user->delete("nameUser = :name", ["names" => "coder"]);

    /* example destroy */
    // $user->idUser = 2;
    // $user->destroy();

    // print_r($user);

    // print_r($user->all()->execute());

    // print_r($user->all()->orderBy("nameUser ASC")->limit(3)->execute());
    

    $user->findById(20)->fill();

    print_r($user);
    
    
    print_r($user->error());
    
    echo "<pre>";

?>