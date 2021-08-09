<?php
namespace BaseCode\QueryModel;

use PDO;
use stdClass;

Abstract Class QueryModel
{
    protected $conn;

    protected $table;
    protected $primary = "id";
    protected $timestamp = true;
    protected $required;

    private $data;

    private $prepare;
    private $params;
    private $limit;
    private $orderBy;

    private $error;

    public function __construct()
    {
        $this->conn = Connection::get();

        if (!$this->conn) {
            die(Connection::error());
        }

        if (empty($this->table)) {
            $table = strtolower(get_class($this));
            $this->table = "{$table}s";
        }
        
    }

    public function __isset(string $name)
    {
        return isset($this->data->$name);
    }

    public function __get(string $name)
    {
        if (isset($this->data->$name)) {
            return $this->data->$name;
        }
        return null;
    }

    public function __set(string $name, $value)
    {
        $this->data($name, $value);
    }

    private function data(string $name, $value)
    {
        if (empty($this->data)) {
            $this->data = new stdClass();
        }
        $this->data->$name = $value;
    }

    public function params(array $params): QueryModel
    {
        $this->params = $params;
        return $this;
    }

    public function prepare(string $prepare, array $params = null): QueryModel
    {
        $this->prepare = $prepare;
        if ($params) {
            $this->params($params);
        }
        return $this;
    }

    public function limit(int $init, int $end = null): QueryModel
    {
        $limit = (is_null($end) ? "0, {$init}" : "{$init}, {$end}");
        $this->limit = "LIMIT {$limit}";
        return $this;
    }

    public function orderBy(string $orderBy): QueryModel
    {
        $this->orderBy = "ORDER BY {$orderBy}";
        return $this;
    }

    public function execute($mode = PDO::FETCH_OBJ): ?array
    {
        if (empty($this->prepare)) {
            return null;
        }

        $prepare = "{$this->prepare} {$this->orderBy} {$this->limit}";
        $statement = $this->conn->prepare($prepare);

        if ($statement->execute(($this->params ?: null))) {
            return $statement->fetchAll($mode);
        }

        $this->error = $statement->errorInfo();
        return null;
    }

    public function fill(): bool
    {
        $fetch = $this->execute();
        if ($fetch) {
            foreach (array_shift($fetch) as $name => $value) {
                $this->data($name, $value);
            }
            return true;
        }
        return false;
    }

    public function all($mode = PDO::FETCH_OBJ): QueryModel
    {
        $this->prepare("SELECT * FROM {$this->table}");
        return $this;
    }

    public function findBy(string $columns, string $cond, ?array $params = null): QueryModel
    {
        $this->prepare("SELECT {$columns} FROM {$this->table} WHERE {$cond}", $params);
        return $this;
    }

    public function findById(int $id, $mode = PDO::FETCH_OBJ): QueryModel
    {
        $prepare = "SELECT * FROM {$this->table} WHERE {$this->primary} = :id";
        $this->prepare($prepare, ["id" => $id]);
        return $this;
    }

    public function save(): bool
    {
        if (!$this->require()) {
            return false;
        }

        $primary = $this->primary;
        $data = $this->filter((array) $this->data);

        if (isset($data[$primary])) {
            $prepare = $this->transform($this->unset($data, $primary), "update");
            $prepare = "UPDATE {$this->table} SET {$prepare} WHERE {$primary} = :{$primary}";
            $statement = $this->conn->prepare($prepare);
        }else{
            $prepare = $this->transform($data, "create");
            $statement = $this->conn->prepare("INSERT INTO {$this->table} {$prepare}");
        }
        
        if ($statement->execute($data)) {
            return true;
        }
        $this->error = $statement->errorInfo();
        return false;
    }

    private function transform(array $data, string $format)
    {
        switch ($format) {
            case 'create':
                $columns = implode(", ", array_keys($data));
                $values = implode(", :", array_keys($data));
                return "({$columns}) VALUES (:{$values})";
            break;

            case 'update':
                $update = [];
                foreach (array_keys($data) as $name) {
                    $update[] = "{$name} = :{$name}";
                }
                return implode(", ", $update);
            break;

        }
    }

    private function require()
    {
        if ($this->required) {
            foreach ($this->required as $name) {
                if (!isset($this->data->$name)) {
                    $require[] = $name;
                }
            }
            
            if (isset($require)) {
                $this->error = ["require values ( ".implode(", ", $require)." )"];
                return false;
            }
            return true;
        }
        return true;
    }

    private function unset(array $array, string $key): ?array
    {
        if (isset($array[$key])) {
            unset($array[$key]);
        }
        return $array;
    }

    private function filter(array $array): array
    {
        foreach ($array as $key => $value) {
            $array[$key] = (filter_var($value, FILTER_SANITIZE_STRING) ?: null);
        }
        return $array;
    }

    public function error(): ?array
    {
        return $this->error;
    }

}
?>