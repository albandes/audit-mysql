<?php

namespace Albandes;
use \PDO;

class DB{
    protected $connection;
    protected $user;
    protected $password;
    protected $dsn;
    protected $options = Array( PDO::ATTR_ERRMODE=> PDO::ERRMODE_EXCEPTION );
    protected $maxReconnectTries = 100;
    protected $reconnectErrors = [ 1317 // interrupted
                                  ,2002 // refused
                                  ,2006 // gone away
                                 ];
    protected $reconnectTries = 0;
    protected $reconnectDelay = 400; // in ms
   
    
    public function __construct($hostname, $dbname, $port, $user = null, $password = null, $charset = null, $options = null){
        $this->dsn = "mysql:dbname={$dbname};port={$port};host={$hostname};charset=$charset";
        $this->user = $user;
        $this->password = $password;
        if($options){
            $this->options = $options;
        }
        
    }

    public function getConnection(){
        if(!$this->connection){
            try {
                $this->connection = new PDO($this->dsn, $this->user, $this->password, $this->options);
            } catch (\PDOException $e) {
                die($e->getMessage());
            }
            
        }
        return $this->connection;
    }


    public function exec($query)
    {
        $conn = $this->getConnection();
        try{
            if(is_string($query)){
                return $conn->exec($query);
            }
        }catch(\PDOException $e){
            if(isset($e->errorInfo) && in_array($e->errorInfo[1], $this->reconnectErrors)){
                try{
                    $this->reconnect();
                }catch(\PDOException $e2){}
                return $this->exec($query->queryString);
            }
            throw $e;
        }

    }


    public function query($query, $params = Array()){
        $conn = $this->getConnection();
        if(is_string($query) && $params){
            $query = $conn->prepare($query);
        }
        try{
            if(is_string($query)){
                return $conn->query($query);
            }else{
                $query->execute($params);
                return $query;
            }
        }catch(\PDOException $e){
            if(isset($e->errorInfo) && in_array($e->errorInfo[1], $this->reconnectErrors)){
                try{
                    $this->reconnect();
                }catch(\PDOException $e2){}
                return $this->query($query->queryString, $params);
            }
            throw $e;
        }
    }

    public function insert($query, $params = Array()){
        $conn = $this->getConnection();
        if(is_string($query) && $params){
            $query = $conn->prepare($query);
        }
        try{
            if(is_string($query)){
                $conn->query($query);
                return $conn->lastInsertId();
            }else{
                $query->execute($params);
                return $conn->lastInsertId();
            }
        }catch(\PDOException $e){
            if(isset($e->errorInfo) && in_array($e->errorInfo[1], $this->reconnectErrors)){
                try{
                    $this->reconnect();
                }catch(\PDOException $e2){}
                return $this->insert($query->queryString, $params);
            }
            throw $e;
        }
    }
    public function reconnect(){
        $connected = false;
        $this->reconnectTries = 0;
        while(!$connected && $this->reconnectTries < $this->maxReconnectTries){
            usleep($this->reconnectDelay * 1000);
            ++$this->reconnectTries;
            $this->connection = null;
            echo 'Tentativa: ' . $this->reconnectTries . PHP_EOL;
            try{
                if($this->getConnection()){
                    $connected = true;
                }
            }catch(\PDOException $e){}
        }
        if(!$connected){
            throw $e;
        }
    }
}