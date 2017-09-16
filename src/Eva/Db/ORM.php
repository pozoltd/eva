<?php

namespace Eva\Db;

use Eva\Tools\Utils;

abstract class ORM
{
    abstract function getFieldMap();

    abstract function getTable();

    public function __construct(\Zend_Db_Adapter_Abstract $zdb)
    {
        $this->zdb = $zdb;
        foreach ($this->getFieldMap() as $key => $value) {
            $this->{$key} = null;
        }
    }

    public function delete()
    {
        $pdo = $this->zdb->getConnection();
        $sql = "DELETE FROM `{$this->getTable()}` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($this->id));
    }

    public function save()
    {
        $reflect = new \ReflectionClass($this);
        $this->__modelClass = $reflect->getShortName();
        if (isset($this->title)) {
            $this->__slug = Utils::slugify($this->title);
        }

        $this->__modified = date('Y-m-d H:i:s');

        $fields = $this->getFieldMap();
//        var_dump('<pre>', $fields, '</pre>');exit;

        $sql = '';
        $params = array();
        if (!isset($this->id) || !$this->id) {
            $this->__added = date('Y-m-d H:i:s');
            if (!isset($this->__active)) {
                $this->__active = 1;
            }

            $sql = "INSERT INTO `{$this->getTable()}` ";
            $part1 = '(';
            $part2 = ' VALUES (';
            foreach ($fields as $idx => $itm) {
                if ($itm == 'id') {
                    continue;
                }
                if ($itm == 'track') {
                    $this->track = md5(uniqid() . time() . rand());
                }

                if (property_exists($this, $idx)) {
                    $part1 .= "`$itm`, ";
                    $part2 .= "?, ";
                    $params[] = $this->{$idx};
                }
            }
            $part1 = rtrim($part1, ', ') . ')';
            $part2 = rtrim($part2, ', ') . ')';
            $sql = $sql . $part1 . $part2;
//            var_dump('<pre>', $sql, $params, '</pre>');exit;
        } else {
            $sql = "UPDATE `{$this->getTable()}` SET ";
            foreach ($fields as $idx => $itm) {
                if ($itm == 'id' || $itm == 'track') {
                    continue;
                }
                if (property_exists($this, $idx)) {
                    $sql .= "`$itm` = ?, ";
                    $params[] = $this->{$idx};
                }
            }
            $sql = rtrim($sql, ', ') . ' WHERE id = ?';
            $params[] = $this->id;
        }

        $pdo = $this->zdb->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!isset($this->id) || !$this->id) {
            $this->id = $pdo->lastInsertId();
        }
        return $this->id;
    }

    public static function getByCode(\Zend_Db_Adapter_Abstract $zdb, $track)
    {
        return static::getORMByField($zdb, 'track', $track);
    }

    public static function getBySlug(\Zend_Db_Adapter_Abstract $zdb, $slug)
    {
        return static::getORMByField($zdb, '__slug', $slug);
    }

    public static function getByTitle(\Zend_Db_Adapter_Abstract $zdb, $title)
    {
        return static::getORMByField($zdb, 'title', $title);
    }

    public static function getById(\Zend_Db_Adapter_Abstract $zdb, $id)
    {
        return static::getORMByField($zdb, 'id', $id);
    }

    public static function getORMByField(\Zend_Db_Adapter_Abstract $zdb, $field, $value)
    {
        $result = static::getORMsByField($zdb, $field, $value);
        return reset($result) ?: null;
    }

    public static function getORMsByField(\Zend_Db_Adapter_Abstract $zdb, $field, $value)
    {
        return static::data($zdb, array(
            'whereSql' => "m.$field = ?",
            'params' => array($value),
//            'debug' => 1,
        ));
    }

    public static function active(\Zend_Db_Adapter_Abstract $zdb, $options = array())
    {
        $options['whereSql'] = (isset($options['whereSql']) && !empty($options['whereSql']) ? '(' . $options['whereSql'] . ') AND ' : '') . 'm.__active = 1';
        return static::data($zdb, $options);
    }

    public static function data(\Zend_Db_Adapter_Abstract $zdb, $options = array())
    {
        $options['select'] = isset($options['select']) && !empty($options['select']) ? $options['select'] : 'm.*';
        $options['joins'] = isset($options['joins']) && !empty($options['joins']) ? $options['joins'] : null;
        $options['whereSql'] = isset($options['whereSql']) && !empty($options['whereSql']) ? $options['whereSql'] : null;
        $options['params'] = isset($options['params']) && gettype($options['params']) == 'array' && count($options['params']) ? $options['params'] : [];
        $options['sort'] = isset($options['sort']) && !empty($options['sort']) ? $options['sort'] : 'm.__rank';
        $options['order'] = isset($options['order']) && !empty($options['order']) ? $options['order'] : 'ASC';
        $options['groupby'] = isset($options['groupby']) && !empty($options['groupby']) ? $options['groupby'] : null;
        $options['page'] = isset($options['page']) ? $options['page'] : 1;
        $options['limit'] = isset($options['limit']) ? $options['limit'] : 0;
        $options['orm'] = isset($options['orm']) ? $options['orm'] : 1;
        $options['debug'] = isset($options['debug']) ? $options['debug'] : 0;

        $options['oneOrNull'] = isset($options['oneOrNull']) ? $options['oneOrNull'] == true : false;
        if ($options['oneOrNull']) {
            $options['limit'] = 1;
            $options['page'] = 1;
        }

        $options['count'] = isset($options['count']) ? $options['count'] == true : false;
        if ($options['count']) {
            $options['orm'] = false;
            $options['oneOrNull'] = true;
            $options['select'] = 'COUNT(*)';
            unset($options['page']);
            unset($options['limit']);
        }

        $myClass = get_called_class();
        $m = new $myClass($zdb);

        $fieldMap = $m->getFieldMap();
        $fields = array_flip($fieldMap);
        usort($fields, function ($a, $b) {
            return strlen($a) < strlen($b) ? 1 : -1;
        });

        $myMap = array();
        foreach ($fields as $itm) {
            $myMap[$itm] = $fieldMap[$itm];
        }

        $replaces = array();
        $count = 1;
        foreach ($myMap as $idx => $itm) {
            $options['whereSql'] = str_replace($idx, 'replace' . $count, $options['whereSql']);
            $replaces['replace' . $count] = $itm;
            $count++;
        }

        $backup = $replaces;
        $replaces = array_flip($replaces);
        usort($replaces, function ($a, $b) {
            return strlen($a) > strlen($b) ? -1 : 1;
        });
        $replaces = array_flip($replaces);

        foreach ($replaces as $idx => $itm) {
            $replaces[$idx] = $backup[$idx];
        }

        foreach ($replaces as $idx => $itm) {
            $options['whereSql'] = str_replace($idx, $itm, $options['whereSql']);
        }

        $reflect = new \ReflectionClass($m);
        $options['whereSql'] = $options['whereSql'] . (empty($options['whereSql']) ? '' : ' AND ') . 'm.__modelClass = ?';
        $options['params'][] = $reflect->getShortName();

        $pdo = $zdb->getConnection();
        $sql = "SELECT {$options['select']} FROM {$m->getTable()} AS m";
        $sql .= $options['joins'] ? ' ' . $options['joins'] : '';
        $sql .= $options['whereSql'] ? ' WHERE ' . $options['whereSql'] : '';
        $sql .= $options['groupby'] ? ' GROUP BY ' . $options['groupby'] : '';
        if ($options['sort']) {
            $sql .= " ORDER BY {$options['sort']} {$options['order']}";
        }
        if ($options['limit'] && $options['page']) {
            $sql .= " LIMIT " . (($options['page'] - 1) * $options['limit']) . ", " . $options['limit'];
        }

        if ($options['debug']) {
            while (@ob_end_clean());
            var_dump('<pre>', $sql, $options['params'], '</pre>');
            exit;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($options['params']);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($options['orm']) {
            $fieldMap = $m->getFieldMap();
            $orms = array();
            foreach ($result as $itm) {
                $orm = new $myClass($zdb);
                foreach ($fieldMap as $attr => $column) {
                    if (isset($itm[$column])) {
                        $orm->{$attr} = $itm[$column];
                    }
                }
                $orms[] = $orm;
            }
            $result = $orms;
        }

        if ($options['oneOrNull']) {
            $result = reset($result) ?: null;
        }

        return $result;
    }
}