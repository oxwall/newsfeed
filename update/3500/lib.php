<?php

class NEWSFEED_Update
{

    /**
     *
     * @var OW_Database
     */
    private $dbo;

    public function __construct()
    {
        $this->dbo = Updater::getDbo();
    }

    /**
     *
     * @param $tables
     * @return NEWSFEED_TableList
     */
    public function getSource( $tables )
    {
        $tbList = new NEWSFEED_TableList('source', $this->dbo, $tables);

        return $tbList;
    }

    /**
     *
     * @param $tables
     * @return NEWSFEED_TableList
     */
    public function getDistination()
    {
        $tbList = new NEWSFEED_TableList('distination', $this->dbo, array(), OW_DB_PREFIX . 'newsfeed_temp_');

        return $tbList;
    }
}

class NEWSFEED_TableList
{
    private $tables = array(), $prefix, $name;

    /**
     *
     * @var OW_Database
     */
    private $dbo;

    public function __construct( $name, OW_Database $dbo )
    {
        $this->name = $name;
        $this->dbo = $dbo;
        $this->prefix = OW_DB_PREFIX . 'newsfeed_';
    }

    public function setPrefix( $prefix )
    {
        $this->prefix = OW_DB_PREFIX . $prefix;
    }

    public function addTable( $table )
    {
        if ( in_array($table, $this->tables) )
        {
            throw new Exception('Table `' . $table . '` already exists in `' . $this->name . '` list');
        }

        $this->tables[] = $table;
    }

    public function dropTable( $table )
    {
        $this->query('DROP TABLE `%' . $table . '%`');
    }

    public function dropTables()
    {
        foreach ( $this->tables as $table )
        {
            $this->dropTable($table);
        }
    }

    public function changePrefix( $prefix )
    {
        if ( empty($this->tables) )
        {
            throw new Exception('No tables in `' . $this->name . '` list');
        }

        $rename = array();
        foreach ( $this->tables as $table )
        {
            $rename[] = $this->prefix . $table . ' TO ' . OW_DB_PREFIX . $prefix . $table;
        }

        $query = 'RENAME TABLE ' . implode(', ', $rename);
        $this->query($query);

        $this->setPrefix($prefix);
    }

    public function getTableName( $table )
    {
        return $this->prefix . $table;
    }

    private function prepareQuery( $query )
    {
        return preg_replace('/%(.*?)%/', $this->prefix . '$1', $query);
    }

    public function createTable( $table, $sql )
    {
        $this->addTable($table);
        $this->query($sql);
    }

    public function insert( $query, $params = array() )
    {
        return $this->dbo->insert($this->prepareQuery($query), $params);
    }

    public function insertRow( $table, $fields )
    {
        $tableName = $this->getTableName($table);
        $set = array();
        $params = array();
        foreach ( $fields as $k => $v )
        {
            $set[] = $k . '=:' . $k;
            $params[$k] = $v;
        }

        return $this->insert('INSERT INTO `' . $tableName . '` SET ' . implode(', ', $set), $params);
    }

    public function query( $query, $params = array() )
    {
        return $this->dbo->query($this->prepareQuery($query), $params);
    }

    public function queryForList( $query, $params = array() )
    {
        return $this->dbo->queryForList($this->prepareQuery($query), $params);
    }

    public function mysqlQuery($query)
    {
        static $mysqlNativeQuery = null;
        if ( $mysqlNativeQuery === null )
        {
            $mysqlNativeQuery = mysql_connect(OW_DB_HOST, OW_DB_USER, OW_DB_PASSWORD );
            mysql_select_db(OW_DB_NAME, $mysqlNativeQuery);
        }

        return mysql_query($this->prepareQuery($query), $mysqlNativeQuery);
    }
}


