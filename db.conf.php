<?php
/**
 * Created by PhpStorm.
 * User: ale10
 * Date: 11.09.2017
 * Time: 0:39
 */
class MysqlWrapper {
    private $server_name = 'localhost';
    private $db_name = 'vm_stat';
    private $db_user = 'vm_stat';
    private $db_pass = 'vm_stat';
    private $db_port = 3306;

    private $conn;
    private $connected = false;

    /**
     * MysqlWrapper constructor.
     * @param string $server_name
     * @param string $db_name
     * @param string $db_user
     * @param string $db_pass
     * @param int $db_port
     */
    public function __construct($server_name=null, $db_name=null, $db_user=null, $db_pass=null, $db_port=null)
    {
        if($server_name!=null) $this->server_name = $server_name;
        if($db_name!=null) $this->db_name = $db_name;
        if($db_user!=null) $this->db_user = $db_user;
        if($db_pass!=null) $this->db_pass = $db_pass;
        if($db_port!=null) $this->db_port = $db_port;
    }

    private function reconnect(){
        $this->conn = new mysqli($this->server_name, $this->db_user, $this->db_pass, $this->db_name, $this->db_port);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        $this->connected = true;
        $this->conn->set_charset('utf8');
        return true;
    }
    public function query($sql,$empty_is_error=TRUE) {
        while (!$this->connected){
            $this->reconnect();
        }
        $result = null;
        if (!($result = $this->conn->query($sql))){
            if ($empty_is_error ){
                printf("SQL ERROR: Query '%s' FAILED: %s\n\r", $sql, $this->conn->error);

            } else {
                return null;
            }
        }
        return $result;
    }

    public function digital_value( $val ){
        return $val=="" ? 0 : $val;
    }

    public function disconnect(){
        if (!($this === null || $this->conn === null)) {
            $this->conn->close();
        }
    }

    /**
     * Returns the last created autoincrement field value or -1 if nothing was inserted in the connection
     * @return int
     */
    public function get_last_id(){
        $res=$this->query("SELECT LAST_INSERT_ID()");
        return (null!=$res && null!=($row=$res->fetch_row())) ? $row[0] : -1;
    }

}