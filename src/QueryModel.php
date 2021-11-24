<?php
namespace BaseCode\QueryModel;

use PDO;
use PDOException;
use stdClass;

/**
 * Class QueryModel
 * @package BaseCode\QueryModel
 */
Abstract Class QueryModel
{
    /** @var string */
    protected $table;

    /** @var string */
    protected $primary = "id";

    /** @var stdClass */
    private $data;

    /** @var string */
    private $prepare;

    /** @var array */
    private $params;

    /** @var string */
    private $limit;

    /** @var string */
    private $orderBy;

    /** @var array */
    private $error;

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->data->$name);
    }

    /**
     * @param string $name
     */
    public function __get(string $name)
    {
        if (isset($this->data->$name)) {
            return $this->data->$name;
        }
        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        $this->data($name, $value);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'data' => $this->unset((array) $this->data, $this->primary)
        ];
    }

    /**
     * @return PDO
     */
    protected function conn(): PDO
    {
        $conn = Connection::get();

        if ($conn) {
            return $conn;
        }

        $error = Connection::error();
        $this->setError($error->getMessage(), $error->getCode());
        
        echo json_encode($this->error());
        exit;
    }

    /**
     * @return string
     */
    public function table(): ?string
    {
        return $this->table ?: null;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    private function data(string $name, $value)
    {
        if (empty($this->data)) {
            $this->data = new stdClass();
        }
        $this->data->$name = $value;
    }

    /**
     * @param array $params
     * @return QueryModel
     */
    public function params(array $params): QueryModel
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @param string $prepare
     * @param array $params
     * @return QueryModel
     */
    public function prepare(string $prepare, array $params = null): QueryModel
    {
        $this->prepare = $prepare;
        if ($params) {
            $this->params($params);
        }
        return $this;
    }

    /**
     * @param int $init
     * @param int $end
     * @return QueryModel
     */
    public function limit(int $init, int $end = null): QueryModel
    {
        $limit = (is_null($end) ? "0, {$init}" : "{$init}, {$end}");
        $this->limit = "LIMIT {$limit}";
        return $this;
    }

    /**
     * @param string $orderBy
     * @return QueryModel
     */
    public function orderBy(string $orderBy): QueryModel
    {
        $this->orderBy = "ORDER BY {$orderBy}";
        return $this;
    }

    /**
     * @param bool $select
     * @param int $mode
     */
    public function execute(bool $select = true, int $mode = PDO::FETCH_OBJ)
    {
        try {
            if (empty($this->prepare)) {
                return null;
            }
    
            $prepare = $this->prepare;
    
            if ($select) {
                $prepare = "{$this->prepare} {$this->orderBy} {$this->limit}";
            }
    
            $statement = $this->conn()->prepare($prepare);
    
            if ($statement->execute(($this->params ?: null))) {
                return ($select ? $statement->fetchAll($mode) : $statement);
            }
    
            $error = $statement->errorInfo();
            $this->setError($error[1], $error[2]);
            return null;

        } catch (PDOException $e) {
            $this->setError($e->getMessage(), $e->getCode());
            return null;
        }
    }

    /**
     * @return bool
     */
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

    /**
     * @return stdClass|null
     */
    public function first(): ?stdClass
    {
        $fetch = $this->execute();
        if ($fetch) {
            return array_shift($fetch);
        }
        return null;
    }

    /**
     * @param int $mode
     * @return QueryModel
     */
    public function all(int $mode = PDO::FETCH_OBJ): QueryModel
    {
        $this->prepare("SELECT * FROM {$this->table}");
        return $this;
    }

    /**
     * @param string $columns
     * @param string $cond
     * @param array|null $params
     * @return QueryModel
     */
    public function findBy(string $columns, string $cond, ?array $params = null): QueryModel
    {
        $this->prepare("SELECT {$columns} FROM {$this->table} WHERE {$cond}", $params);
        return $this;
    }

    /**
     * @param int $id
     * @param int $mode
     * @return QueryModel
     */
    public function findById(int $id, int $mode = PDO::FETCH_OBJ): QueryModel
    {
        $prepare = "SELECT * FROM {$this->table} WHERE {$this->primary} = :id";
        $this->prepare($prepare, ["id" => $id]);
        return $this;
    }

    /**
     * @param array $models
     * @param array $on
     * @param string|null $cond
     * @param string $columns
     * @return QueryModel
     */
    public function inner(array $models, array $on, string $cond = null, string $columns = "*"): QueryModel
    {
        $this->join("INNER", $models, $on, $cond, $columns);
        return $this;   
    }

    /**
     * @param array $models
     * @param array $on
     * @param string|null $cond
     * @param string $columns
     * @return QueryModel
     */
    public function left(array $models, array $on, string $cond = null, string $columns = "*"): QueryModel
    {
        $this->join("LEFT", $models, $on, $cond, $columns);
        return $this;   
    }

    /**
     * @param array $models
     * @param array $on
     * @param string|null $cond
     * @param string $columns
     * @return QueryModel
     */
    public function right(array $models, array $on, string $cond = null, string $columns = "*"): QueryModel
    {
        $this->join("RIGHT", $models, $on, $cond, $columns);
        return $this;   
    }

    /**
     * @param string $type
     * @param array $models
     * @param array $on
     * @param string|null $cond
     * @param string $columns
     */
    private function join(string $type, array $models, array $on, ?string $cond, string $columns)
    {
        $separator = "@table";
        $on = "{$separator} ".implode(" {$separator} ", $on);

        $class = static::class;
        $exists = isset($models[$class]) ? true : in_array($class, $models);

        if (!$exists) {
            $models[] = $class;
        }

        foreach($models as $indexe => $value) {
            $model = is_string($indexe) ? $indexe : $value;
            $search = $value;

            if ($model == $class) {
                $replace = $this->table();
            }else {
                $replace = (new $model())->table();
            }

            $on = preg_replace([
                "~^({$search}\.)~",
                "~(\s{$search}\.)~"
            ], ["{$replace}.", " {$replace}."], $on);

            $columns = preg_replace([
                "~^({$search}\.)~",
                "~(\s{$search}\.)~"
            ], ["{$replace}.", " {$replace}."], $columns);

            if ($model == $class) {
                continue;
            }

            $on = preg_replace("({$separator})", "{$type} JOIN {$replace} ON", $on, 1);
        }

        if ($cond) {
            $cond = "WHERE {$cond}";
        }

        $this->prepare("SELECT {$columns} FROM {$this->table} {$on} {$cond}");
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        $primary = $this->primary;

        if (!isset($this->data->$primary)) {
            if (!$this->require()) {
                return false;
            }
        }

        if (isset($this->timestamp)) {
            $timestamp = date("Y-m-d H:i:s");

            if (isset($this->timestamp["create"])) {
                $this->data($this->timestamp["create"], $timestamp);
            }

            if (isset($this->timestamp["update"])) {
                $this->data($this->timestamp["update"], $timestamp);
            }
        }

        $data = $this->filter((array) $this->data);

        if (isset($this->data->$primary)) {

            if (isset($this->timestamp["create"])) {
                $data = $this->unset($data, $this->timestamp["create"]);
            }

            $prepare = $this->transform($this->unset($data, $primary), "update");
            $prepare = "UPDATE {$this->table} SET {$prepare} WHERE {$primary} = :{$primary}";
            $this->prepare($prepare, $data);
        }else{
            $prepare = $this->transform($data, "create");
            $this->prepare("INSERT INTO {$this->table} {$prepare}", $data);
        }
        
        $execute = $this->execute(false);

        if ($execute && $execute->rowCount()) {
            $id = isset($this->data->$primary) ? $this->data->$primary : null;

            $this->findById(($id ?? $this->conn()->lastInsertId()))->fill();
            return true;
        }
        return false;
    }

    /**
     * @param string $cond
     * @param array $params
     * @return bool
     */
    public function delete(string $cond, array $params = null): bool
    {
        $prepare = "DELETE FROM {$this->table} WHERE {$cond}";
        $execute = $this->prepare($prepare, $params)->execute(false);
        if ($execute) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function destroy(): bool
    {
        $primary = $this->primary;

        if (!isset($this->data->$primary)) {
            return false;
        }

        $id = $this->data->$primary;
        return $this->delete("{$primary} = :{$primary}", [$primary => $id]);
    }

    /**
     * @param array $data
     * @param string $format
     * @return string|null
     */
    private function transform(array $data, string $format): ?string
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

            default:
                return null;
        }
    }

    /**
     * @return bool
     */
    private function require(): bool
    {
        if (isset($this->required) && is_array($this->required)) {
            foreach ($this->required as $name) {
                if (!isset($this->data->$name)) {
                    $require[] = $name;
                }
            }
            
            if (isset($require)) {
                $this->setError("require values ( ".implode(", ", $require)." )", 0);
                return false;
            }
            return true;
        }
        return true;
    }

    /**
     * @param array $array
     * @param string $key
     * @return array
     */
    private function unset(array $array, string $key): array
    {
        if (isset($array[$key])) {
            unset($array[$key]);
        }
        return $array;
    }

    /**
     * @param array $array
     * @return array
     */
    private function filter(array $array): array
    {
        foreach ($array as $key => $value) {
            $array[$key] = (filter_var($value, FILTER_SANITIZE_STRING) ?: null);
        }
        return $array;
    }

    /**
     * @param string $message
     * @param mixed $code
     */
    protected function setError(string $message, $code)
    {
        $this->error = [
            "message" => $message,
            "code" => $code
        ];
    }

    /**
     * @return array|null
     */
    public function error(): ?array
    {
        return $this->error;
    }

}