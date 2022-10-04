<?php

namespace Albandes;

use Albandes\services;


/**
 * audit
 *
 * PHP class to audit mysql tables
 *
 * @author  Rogério Albandes <rogerio.albandes@gmail.com>
 * @version 0.1
 * @package mysql
 * @example example.php
 * @link    https://github.com/albandes/audit
 * @license GNU License
 *
 */

class audit
{

    /**
     * applogger
     *
     * @var object
     */
    public $_applogger;

    /**
     * db
     *
     * @var PDO A PDO database connection
     */
    private $_db;    

    /**
     * debug
     *
     * @var boolean Debug status
     */
    private $_debug ;

    

    /**
     * __construct
     *
     * @param  object $db
     * @return void
     */
    public function __construct(\Albandes\DB $db)
    {
        
        $this->_db = $db;
                
        $services = new services();
        $this->_applogger = $services->get_applogger();
        
    }

    
    /**
     * createTrigger
     *
     * @param  mixed $table
     * @param  mixed $action
     * @return void
     */
    public function createTrigger($table, $action)
    {
        $newRow = $this->createNewRow($table);

        if ($action == 'INSERT') {
            $row = "JSON_OBJECT('new_row',{$newRow})";
        } elseif ($action == 'UPDATE') {
            $oldRow = $this->createOldRow($table);
            $row = "JSON_OBJECT('old_row',{$oldRow},'new_row',{$newRow})";
        } elseif ($action  == 'DELETE') {
            $oldRow = $this->createOldRow($table);
            $row = "JSON_OBJECT('old_row',{$oldRow})";
        }
        
        $lowerAction = strtolower($action);

        $trigger = 
        
        "
        CREATE TRIGGER {$table}_{$lowerAction}_audit 
        AFTER {$action} ON {$table} 
        FOR EACH ROW 
        BEGIN 
            SET @table := '{$table}';
            SELECT DATABASE() INTO @db; 
            SELECT SUBSTRING_INDEX(USER(), '@', -1) INTO @ip_address; 
            INSERT INTO tbaudit (audit) 
                VALUES (
                    JSON_OBJECT(
                        'audit',
                        json_object(
                            'id',UUID(), 
                            'database',@db,
                            'table',@table,
                            'user_app', @user_app,
                            'dml',json_object('action','{$action}','timestamp',NOW(),'user',CURRENT_USER(),'ip',@ip_address),
                            'row',{$row}
                        )
                    )
                )
            ;                      
        END;
        
        ";

        return $trigger;
        
    }
    
    /**
     * insertTrigger
     *
     * @param  mixed $trigger
     * @return string
     */
    public function insertTrigger($trigger)
    {
        $ret = $this->_db->insert($trigger);
        return $ret;
    }
            
    /**
     * dropTrigger
     *
     * @param  mixed $table
     * @param  mixed $action
     * @return void
     */
    public function dropTrigger($table,$action)
    {

        $action = strtolower($action);

        try {
            $this->_db->exec("DROP TRIGGER IF EXISTS  {$table}_{$action}_audit");
        } catch (\PDOException $e) {
            //$logger->error('Db error: ' . $e->getMessage() , ['linha' => __LINE__ ] );
            die($e->getMessage() . ' linha ' . __LINE__);
        }

    }
    
    /**
     * getColumnsName
     *
     * @param  mixed $table
     * @return array
     */
    public function getColumnsName($table)
    {

        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = ?";
        try {

            $param = array($table);
            $stmt = $this->_db->query($sql,$param);

            while($row = $stmt->fetch(\PDO::FETCH_ASSOC)){
                $output[] = $row['COLUMN_NAME'];                
            }

            return $output; 

        }
    
        catch(\PDOException $pe) {
            trigger_error('Could not connect to MySQL database. ' . $pe->getMessage() , E_USER_ERROR);
        }
    
    }
    
    /**
     * createNewRow
     *
     * @param  mixed $table
     * @return string
     */
    function createNewRow($table)
    {
        
        $row = "";
        $aColumns = $this->getColumnsName($table);
        
        $tam = count($aColumns)-1;
        foreach($aColumns as $key => $value)  {
            
            if ($key < $tam )
                $comma = ",";
            else 
                $comma = '';
            
            $row .= "'{$value}',NEW.{$value}{$comma}";
            
        }
    
        $ret = "JSON_ARRAY(JSON_OBJECT({$row}))";
        return $ret;
    
    }
        
    /**
     * createOldRow
     *
     * @param  mixed $table
     * @return string
     */
    function createOldRow($table)
    {
        
        $row = "";
        $aColumns = $this->getColumnsName($table);
        
        $tam = count($aColumns)-1;
        foreach($aColumns as $key => $value)  {
            
            if ($key < $tam )
                $comma = ",";
            else 
                $comma = '';
            
            $row .= "'{$value}',OLD.{$value}{$comma}";
            
        }
    
        $ret = "JSON_ARRAY(JSON_OBJECT({$row}))";
        return $ret;
    
    }    
    /**
     * Get debug status
     *
     * @return  boolean
     */ 
    public function get_debug()
    {
        return $this->_debug;
    }

    /**
     * Set debug status
     *
     * @param  boolean  $_debug  Debug status
     *
     * @return  self
     */ 
    public function set_debug(bool $_debug)
    {
        $this->_debug = $_debug;

        return $this;
    }
}    