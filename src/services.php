<?php

namespace Albandes;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;


/**
 * services
 *
 * PHP class services 
 *
 * @author  Rogério Albandes <rogerio.albandes@gmail.com>
 * @version 0.1
 * @package albandes
 * @example example.php
 * @link    https://github.com/albandes/mqtt
 * @license GNU License
 *
 */

class services
{
        
    /**
     * _logger
     *
     * @var mixed
     */
    protected $_applogger;

    /**
     * __construct
     *
     * @param  mixed $db
     * @return void
     */
    
    public function __construct()
    {

        $this->makeLogger();
        
    }
    
    /**
     * makeLogger
     *
     * @return void
     */
    public function makeLogger()
    {
        // create a log channel
        $formatter = new LineFormatter(null, "d/m/Y H:i:s");
        $stream = new StreamHandler( $_ENV['LOG_FILE'], Logger::DEBUG);
        $stream->setFormatter($formatter);
        $logger = new Logger('exehda');
        $logger->pushHandler($stream);
        $this->set_applogger($logger);
    }

    public function errorConnection($errorNumber)
    {
        $arrayConnect=array(
            "0" => "Success",
            "1" => "Connection refused (unacceptable protocol version)",
            "2" => "Connection refused (identifier rejected)",
            "3" => "Connection refused (broker unavailable )"
        );

        return $arrayConnect[$errorNumber];        

    }

    /**
     * Get applogger
     *
     * @return  object
     */ 
    public function get_applogger()
    {
        return $this->_applogger;
    }

    /**
     * Set applogger
     *
     * @param  object  $_applogger  applogger
     *
     * @return  self
     */ 
    public function set_applogger(object $_applogger)
    {
        $this->_applogger = $_applogger;
        return $this;
    }

}    