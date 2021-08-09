<?php
    define("DS", DIRECTORY_SEPARATOR);
    require_once dirname(__DIR__).DS."vendor".DS."autoload.php";
    require_once dirname(__FILE__).DS."config".DS."Config.php";

    use BaseCode\QueryModel\QueryModel;

    Class User extends QueryModel
    {
        // protected $table = "users";
        protected $primary = "idUser";
        protected $timestamp = false;

        protected $required = [
            "nameUser",
            "emailUser",
            "passwordUser"
        ];

        public function table()
        {
            return $this->table;
        }

    }

    $user = new User();
    $user->idUser = 2;

    echo "<pre>";
    
    // $test = $user->findBy(
    //     "*",
    //     "idUser = :id OR emailUser = :email",
    //     ["id" => $user->idUser, "email" => $user->emailUser]
    // )->execute();

    // print_r($user->findBy(
    //     "*",
    //     "idUser = :id",
    //     ["id" => $user->idUser]
    // )->execute());
 
    // print_r($user->all()->orderBy("nameUser ASC")->limit(2)->execute());

    // print_r($user->findBy(
    //     "*",
    //     "nameUser LIKE :name",
    //     ["name" => "%henrique"])->execute()
    // );

    // $user->save();

    print_r($user->all()->orderBy("nameUser ASC")->limit(3, 1)->execute());
    
    print_r($user->error());
    
    echo "<pre>";

?>