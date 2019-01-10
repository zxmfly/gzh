<?php
namespace gzh;
use PDO;
use PDOException;
use Exception;

class Mysql
{
    /**
     * 单例对象
     *
     * @var PDO
     */
    protected static $obj;

    /**
     * @var \PDOStatement
     */
    private static $sth;

    /**
     * @var int
     */
    public static $rowCount;

    /**
     * @return PDO
     * @throws Exception
     */
    public static function getInstance(){
        $config = getMysqlConf();
	    if(!static::$obj){ 
            $dsn = "mysql:dbname={$config['DBNAME']};host={$config['HOST']};port={$config['PORT']};charset:{$config['CHARSET']}";
            $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,);//设置错误处理模式为抛出异常模式
            try {
                self::$obj = new PDO($dsn, $config['USER'], $config['PASSWORD'], $options);
                self::$obj->exec("set names utf8");//设置utf8字符集
            } catch (PDOException $e) {
                throw new Exception('数据库连接失败: ' . $e->getMessage());
            }
        }
	    return self::$obj;
    }

    /**
     * 插入数据到数据库
     * @param string $tableName 数据库名
     * @param array  $data      要插入的数据
     * @return mixed
     * @throws Exception
     */
    public static function insert ($tableName, $data) {
        if (gettype($tableName) !== 'string' || gettype($data) !== 'array') {
            throw new Exception('PARAM_WRONG');
        }

        $prepareData = self::prepareData($data);
        $prepareFieldsStr = implode(', ', array_keys($prepareData));
        $fieldsStr = implode(', ', array_keys($data));
        $sql = "INSERT INTO `$tableName` ($fieldsStr) VALUES ($prepareFieldsStr)";

        // 执行 SQL 语句
        $query = self::raw($sql, $prepareData);
        return self::getInstance()->lastInsertId();
    }

    /**
     * 查询多行数据
     * @param string        $tableName 数据库名
     * @param array         $columns   查询的列名数组
     * @param array|string  $conditions 查询条件，若为字符串则会被直接拼接进 SQL 语句中，支持键值数组
     * @param string        $operator  condition 连接的操作符：and|or
     * @param string        $suffix    SQL 查询后缀，例如 order, limit 等其他操作
     * @return array
     * @throws Exception
     */
    public static function select ($tableName, $columns = ['*'], $conditions = '', $operator = 'and', $suffix = '') {
        if (   gettype($tableName)  !== 'string'
            || (gettype($conditions)!== 'array' && gettype($conditions) !== 'string')
            || gettype($columns)    !== 'array'
            || gettype($operator)   !== 'string'
            || gettype($suffix)     !== 'string') {
            throw new Exception('PARAM_WRONG');
        }

        list($condition, $execValues) = array_values(self::conditionProcess($conditions, $operator));

        $column = implode(', ', $columns);
        // 拼接 SQL 语句
        $sql = "SELECT $column FROM `$tableName`";

        // 如果有条件则拼接 WHERE 关键则
        if ($condition) {
            $sql .= " WHERE $condition";
        }

        // 拼接后缀
        $sql .= " $suffix";

        // 执行 SQL 语句
        $query = self::raw($sql, $execValues);
        $allResult = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $allResult === NULL ? [] : $allResult;
    }

    /**
     * 查询单行数据
     * @param string        $tableName 数据库名
     * @param array         $columns   查询的列名数组
     * @param array|string  $conditions 查询条件，若为字符串则会被直接拼接进 SQL 语句中，支持键值数组
     * @param string        $operator  condition 连接的操作符：and|or
     * @param string        $suffix    SQL 查询后缀，例如 order, limit 等其他操作
     * @return object
     * @throws Exception
     */
    public static function row ($tableName, $columns = ['*'], $conditions = '', $operator = 'and', $suffix = '') {
        $rows = self::select($tableName, $columns, $conditions, $operator, $suffix);
        return count($rows) === 0 ? [] : $rows[0];
    }

    /**
     * 更新数据库
     * @param string        $tableName 数据库名
     * @param array         $updates   更新的数据对象
     * @param array|string  $conditions 查询条件，若为字符串则会被直接拼接进 SQL 语句中，支持键值数组
     * @param string        $operator  condition 连接的操作符：and|or
     * @param string        $suffix    SQL 查询后缀，例如 order, limit 等其他操作
     * @return number 受影响的行数
     * @throws Exception
     */
    public static function update ($tableName, $updates, $conditions = '', $operator = 'and', $suffix = '') {
        if (   gettype($tableName)  !== 'string'
            || gettype($updates)    !== 'array'
            || (gettype($conditions)!== 'array' && gettype($conditions) !== 'string')
            || gettype($operator)   !== 'string'
            || gettype($suffix)     !== 'string') {
            throw new Exception('PARAM_WRONG');
        }

        // 处理要更新的数据
        list($processedUpdates, $execUpdateValues) = array_values(self::conditionProcess($updates, ','));

        // 处理条件
        list($condition, $execValues) = array_values(self::conditionProcess($conditions, $operator));

        // 拼接 SQL 语句
        $sql = "UPDATE `$tableName` SET $processedUpdates";

        // 如果有条件则拼接 WHERE 关键则
        if ($condition) {
            $sql .= " WHERE $condition";
        }

        // 拼接后缀
        $sql .= " $suffix";

        // 执行 SQL 语句
        $query = self::raw($sql, array_merge($execUpdateValues, $execValues));
        return $query->rowCount();
    }

    /**
     * 删除数据
     * @param string        $tableName 数据库名
     * @param array|string  $conditions 查询条件，若为字符串则会被直接拼接进 SQL 语句中，支持键值数组
     * @param string        $operator  condition 连接的操作符：and|or
     * @param string        $suffix    SQL 查询后缀，例如 order, limit 等其他操作
     * @return number 受影响的行数
     * @throws Exception
     */
    public static function delete ($tableName, $conditions, $operator = 'and', $suffix = '') {
        if (   gettype($tableName)  !== 'string'
            || ($conditions && gettype($conditions)!== 'array' && gettype($conditions) !== 'string')
            || gettype($operator)   !== 'string'
            || gettype($suffix)     !== 'string') {
            throw new Exception('PARAM_WRONG');
        }

        // 处理条件
        list($condition, $execValues) = array_values(self::conditionProcess($conditions, $operator));

        // 拼接 SQL 语句
        $sql = "DELETE FROM `$tableName` WHERE $condition $suffix";

        // 执行 SQL 语句
        $query = self::raw($sql, $execValues);
        return $query->rowCount();
    }

    /**
     * 执行原生 SQL 语句
     * @param string $sql  要执行的 SQL 语句
     * @param array  $execValues SQL 语句的参数值
     * @return \PDOStatement
     * @throws Exception
     */
    public static function raw ($sql, $execValues = []) {
        $query = self::getInstance()->prepare($sql);
        $result = $query->execute($execValues);

        if ($result) {
            return $query;
        } else {
            $error = $query->errorInfo();
            throw new Exception('执行sql错误: ' . $error[2]);
        }
    }

    /**
     * 按照指定的规则处理条件数组
     * @example ['a' => 1, 'b' => 2] 会被转换为 ['a = :a and b = :b', [':a' => 1, ':b' => 2]]
     * @param array|string $conditions 条件数组或字符串
     * @param string       $operator  condition 连接的操作符：and|or
     * @return array
     */
    private static function conditionProcess ($conditions, $operator = 'and') {
        $execValues = [];
        if (gettype($conditions) === 'array') {
            $cdt = [];

            foreach ($conditions as $key => $value) {
                if (gettype($value) === 'number') {
                    array_push($cdt, $value);
                } else {
                    array_push($cdt, $key . ' = :' . $key);
                    $execValues[$key] = $value;
                }
            }

            $condition = implode(' ' . $operator . ' ', $cdt);
        } else {
            $condition = $conditions;
        }

        return [
            $condition,
            self::prepareData($execValues)
        ];
    }

    /**
     * 转换数据为 PDO 支持的 prepare 过的数据
     * @example ['a' => 1] 会被转换为 [':a' => 1]
     * @param array $dataArray 要转换的数据
     * @return array
     */
    private static function prepareData ($dataArray) {
        $prepareData = [];

        foreach ($dataArray as $field => $value) {
            $prepareData[':' . $field] = $value;
        }

        return $prepareData;
    }

    /**
     * @return mixed
     */
	public static function getOne()
	{
		return self::$sth->fetch(PDO::FETCH_ASSOC);
	}

    /**
     * @return array
     */
	public static function getAll()
	{
		return self::$sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * 获取自增id
     * @return string
     */
    public static function getAutoId(){
        return self::getInstance()->lastInsertId();
    }

    /**
     * 执行语句
     * @param $sql
     * @param $arr
     * @return \PDOStatement
     * @throws Exception
     */
	public static function query($sql, $arr=[])
	{
		try {
			if(is_array($arr) && !empty($arr)){
				self::$sth = self::getInstance()->prepare($sql);
				self::$sth->execute($arr);
			}
			else{
				self::$sth = self::getInstance()->query($sql);
			}
		} catch (PDOException $e) {
			throw new Exception("执行语句{$sql}失败: " . $e->getMessage());
		}
		return self::$sth;
	}

    /**
     * 获取结果集
     * @param $sql
     * @param array $arr
     * @return array
     */
	public static function fetchAll($sql, $arr=[])
	{
		self::query($sql, $arr);
		return self::getAll();
	}

    /**
     * 获取第一行
     * @param $sql
     * @param array $arr
     * @return mixed
     */
	public static function fetchOne($sql, $arr=[])
	{
		self::query($sql, $arr);
		return self::getOne();
	}

    /**
     * 查询缓存，不存在结果则执行SQL查询，获取结果集第一行
     * @param $sql
     * @param int $ttl
     * @param array $arr
     * @return mixed
     */
    public static function fetchOneOrMemcache($sql, $ttl=-1, $arr = array()){
        $arr_str = json_encode($arr);
        $key = 'SQL:ONE'.$sql.$arr_str;
        $rs = getCache($key);
        if ($rs === false) {
            $rs = self::fetchOne($sql, $arr);
            setCache($key, $rs, $ttl);
        }
        return $rs;
    }

    /**
     * 查询缓存，不存在结果则执行SQL查询，获取结果集的全部
     * @param string $sql
     * @param int $ttl
     * @param array $arr
     * @return array
     */
    public static function fetchAllOrMemcache($sql, $ttl=-1, $arr = array()){
        $arr_str = json_encode($arr);
        $key = 'SQL:ALL'.$sql.$arr_str;
        $rs = getCache($key);
        if ($rs === false) {
            $rs = self::fetchAll($sql, $arr);
            setCache($key, $rs, $ttl);
        }
        return $rs;
    }

    /**
     * 查询多行数据
     * @param string        $tableName 数据库名
     * @param array         $columns   查询的列名数组
     * @param array|string  $conditions 查询条件，若为字符串则会被直接拼接进 SQL 语句中，支持键值数组
     * @param string        $operator  condition 连接的操作符：and|or
     * @param string        $suffix    SQL 查询后缀，例如 order, limit 等其他操作
     * @param int           $ttl        缓存时长（秒）
     * @return array
     * @throws Exception
     */
    public static function selectMemcache($tableName, $columns = ['*'], $conditions = '', $operator = 'and', $suffix = '', $ttl = -1){
        $conditions_str = is_array($conditions) ? json_encode($conditions) : $conditions;
        $key = 'SQL:ALL'.$tableName.json_encode($columns).$conditions_str.$operator.$suffix;
        $rs = getCache($key);
        if ($rs === false) {
            $rs = self::select($tableName, $columns, $conditions, $operator, $suffix);
            setCache($key, $rs, $ttl);
        }
        return $rs;
    }

    /**
     * 查询单行数据
     * @param string        $tableName 数据库名
     * @param array         $columns   查询的列名数组
     * @param array|string  $conditions 查询条件，若为字符串则会被直接拼接进 SQL 语句中，支持键值数组
     * @param string        $operator  condition 连接的操作符：and|or
     * @param string        $suffix    SQL 查询后缀，例如 order, limit 等其他操作
     * @param int           $ttl        缓存时长（秒）
     * @return object
     * @throws Exception
     */
    public static function rowMemcache($tableName, $columns = ['*'], $conditions = '', $operator = 'and', $suffix = '', $ttl = -1){
        $conditions_str = is_array($conditions) ? json_encode($conditions) : $conditions;
        $key = 'SQL:ONE'.$tableName.json_encode($columns).$conditions_str.$operator.$suffix;
        $rs = getCache($key);
        if ($rs === false) {
            $rs = self::row($tableName, $columns, $conditions, $operator, $suffix);
            setCache($key, $rs, $ttl);
        }
        return $rs;
    }
}