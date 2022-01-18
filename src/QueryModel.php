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

    /** @var string */
    private $select = "*";

    /** @var string */
    private $action;

    /** @var stdClass */
    private $data;

    /** @var string */
    private $query;

    /** @var string */
    private $condition;

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
     * @param string $columns
     * @return QueryModel
     */
    public function select(string $columns): QueryModel
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * @param string $action
     * @return QueryModel
     */
    private function action(string $action): QueryModel
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @param string $query
     * @return QueryModel
     */
    public function query(string $query): QueryModel
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param string $condition
     * @return QueryModel
     */
    public function where(string $condition): QueryModel
    {
        $this->condition = "WHERE {$condition}";
        return $this;
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
    
            $prepare = "{$this->action} {$this->query} {$this->condition}";
    
            if ($select) {
                $prepare = "{$prepare} {$this->orderBy} {$this->limit}";
            }
    
            $statement = $this->conn()->prepare($prepare);
            $params = $this->params;

            $this->reset();
    
            if ($statement->execute(($params ?: null))) {
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

    private function reset()
    {
        $this->select = "*";
        $this->action = "";
        $this->query = "";
        $this->params = [];
        $this->condition = "";
        $this->orderBy = "";
        $this->limit = "";
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
     * @return stdClass|null
     */
    public function last(): ?stdClass
    {
        $fetch = $this->execute();
        if ($fetch) {
            return array_pop($fetch);
        }
        return null;
    }

    /**
     * @return bool
     */
    public function fill(): bool
    {
        $first = $this->first();
        if ($first) {
            foreach ($first as $name => $value) {
                $this->data($name, $value);
            }
            return true;
        }
        return false;
    }

    /**
     * @return QueryModel
     */
    public function all(): QueryModel
    {
        return $this->action("SELECT {$this->select} FROM {$this->table}");
    }

    /**
     * @param string $condition
     * @return QueryModel
     */
    public function findBy(string $condition): QueryModel
    {
        return $this->action("SELECT {$this->select} FROM {$this->table}")
        ->where($condition);
    }

    /**
     * @param int $id
     * @return QueryModel
     */
    public function findById(int $id): QueryModel
    {
        return $this->action("SELECT {$this->select} FROM {$this->table}")
        ->where("{$this->primary} = :id")
        ->params(["id" => $id]);
    }

    /**
     * @param string $query
     * @return QueryModel
     */
    public function join(string $query): QueryModel
    {
        return $this->action("SELECT {$this->select} FROM {$this->table}")
        ->query($query);
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

            $query = $this->transform($this->unset($data, $primary), "update");

            $this->action("UPDATE {$this->table} SET")
            ->query($query)
            ->where("{$primary} = :{$primary}")
            ->params($data);
        }else{
            $query = $this->transform($data, "create");

            $this->action("INSERT INTO {$this->table}")
            ->query($query)
            ->params($data);
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
     * @param string $condition
     * @param array|null $params
     * @return bool
     */
    public function delete(string $condition, array $params = null): bool
    {
        $this->action("DELETE FROM {$this->table}")->where($condition);

        if ($params) {
            $this->params($params);
        }
        
        $execute = $this->execute(false);

        if ($execute && $execute->rowCount()) {
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
        return $this->delete("{$primary} = :id", ["id" => $id]);
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