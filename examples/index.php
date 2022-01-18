<?php
    date_default_timezone_set("America/Sao_Paulo");

    define("DS", DIRECTORY_SEPARATOR);
    require_once dirname(__DIR__).DS."vendor".DS."autoload.php";
    require_once dirname(__FILE__).DS."config".DS."Config.php";

    use BaseCode\QueryModel\QueryModel;

    Class User extends QueryModel
    {
        protected $table = "users";
        // protected $primary = "id";

        protected $required = [
            "name",
            "email",
            "password"
        ];

        protected $timestamp = [
            "create" => "created_at",
            "update" => "updated_at"
        ];

        public function posts()
        {
            return $this->select("users.name, posts.title")
            ->join("INNER JOIN posts ON users.id = posts.id_user")
            ->where("users.id = :id")
            ->params(["id" => $this->id])
            ->execute();
        }

        public function usersPosts()
        {
            return $this->select("users.id, users.name, posts.title")
            ->join("INNER JOIN posts ON users.id = posts.id_user")
            ->execute();
        }

    }

    Class Post extends QueryModel
    {
        protected $table = "posts";
        // protected $primary = "id";

        protected $required = [
            "id_user",
            "title",
            "message"
        ];

        protected $timestamp = [
            "create" => "created_at",
            "update" => "updated_at"
        ];

    }

    $user = new User();
    $post = new Post();

    echo "<pre>";

    // insert register
    // $user->name = "test";
    // $user->email = "test@gmail.com";
    // $user->password = md5("123");
    // $user->save();
    
    // select all
    // print_r($user->all()->execute());

    // select all set columns
    // print_r($user->select("name, email")->all()->execute());

    // select all set limit
    // print_r($user->all()->limit(1)->execute());

    // select all set order by
    // print_r($user->all()->orderBy("id DESC")->execute());

    // select all return first
    // print_r($user->all()->first());

    // select by id and fill in the model
    // $user->findById(2)->fill();
    // print_r($user);

    // select by id and return object stdClass with the data
    // print_r($user->findById(2)->first());

    // select by id and update
    // $user->findById(1)->fill();
    // $user->email = "update@gmail.com";
    // $user->save();

    // select by condition without parameters | not recommended
    // $result = $user->findBy("email = 'update@gmail.com'")->execute();
    // print_r($result);

    // select by condition with parameters | recommended
    // $result = $user->findBy("email = :email")->params([
    //     "email" => "update@gmail.com"
    // ])->execute();
    // print_r($result);

    // destroy user current
    // $user->findById(4)->fill();
    // $user->destroy();

    // delete by condition without parameters | not recommended
    // $user->delete("email = 'test@gmail.com'");

    // delete by condition with parameters | recommended
    // $user->delete("email = :email", [
    //     "email" => "test@gmail.com"
    // ]);

    // mode of use select joins (INNER JOIN, LEFT JOIN, RIGHT JOIN...)
    // $user->findById(9)->fill();
    // print_r($user->posts());


    // erros
    print_r($user->error());

    echo "<pre>";

?>