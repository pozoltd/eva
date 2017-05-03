<?php
namespace Eva\Db;

class Table
{
    private $pdo;
    private $table;

    public function __construct(\PDO $pdo, $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
//        $this->create($table);
    }

    public function getFields() {
        $sql = 'DESCRIBE ' . $this->table;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return array_map(function ($itm) {
            return $itm['Field'];
        }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function create()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `$this->table` (
                    `id` int(11) NOT NULL AUTO_INCREMENT, 
                    `track` varchar(128) COLLATE utf8_unicode_ci NOT NULL, 
                    INDEX `TRACK` (`track` ASC), 
                    PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }

    public function addColumn($column, $attrs = '')
    {
        try {
            $sql = "ALTER TABLE $this->table 
                      ADD COLUMN `$column` $attrs";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute();
        } catch (\Exception $ex) {

        }
        return false;
    }

    public function addIndex($index, $column)
    {
        try {
            $sql = "ALTER TABLE `$this->table` 
                      ADD INDEX `$index` (`$column` ASC);";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute();
        } catch (\Exception $ex) {

        }
        return false;
    }






}