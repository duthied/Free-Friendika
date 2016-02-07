<?php
require_once("include/datetime.php");

class NotificationsManager {
    private $a;
    
    public function __construct() {
        $this->a = get_app();
    }
    
    private function _set_extra($notes) {
        $rets = array();
        foreach($notes as $n) {
            $local_time = datetime_convert('UTC',date_default_timezone_get(),$n['date']);
            $n['timestamp'] = strtotime($local_time);
            $n['date_rel'] = relative_date($n['date']);
            $rets[] = $n;
        }
        return $rets;
    }


    /**
     * @brief get all notifications for local_user()
     *
     * @param array $filter optional Array "column name"=>value: filter query by columns values
     * @param string $order optional Space separated list of column to sort by. prepend name with "+" to sort ASC, "-" to sort DESC. Default to "-date"
     * @param string $limit optional Query limits
     *
     * @return array of results or false on errors
     */
    public function getAll($filter = array(), $order="-date", $limit="") {
        $filter_str = array();
        $filter_sql = "";
        foreach($filter as $column => $value) {
            $filter_str[] = sprintf("`%s` = '%s'", $column, dbesc($value));
        }
        if (count($filter_str)>0) {
            $filter_sql = "AND ".implode(" AND ", $filter_str);
        }
        
        $aOrder = explode(" ", $order);
        $asOrder = array();
        foreach($aOrder as $o) {
            $dir = "asc";
            if ($o[0]==="-") {
                $dir = "desc";
                $o = substr($o,1);
            }
            if ($o[0]==="+") {
                $dir = "asc";
                $o = substr($o,1);
            }
            $asOrder[] = "$o $dir";
        }
        $order_sql = implode(", ", $asOrder);
        
        if ($limit!="") $limit = " LIMIT ".$limit;
        
		$r = q("SELECT * from notify where uid = %d $filter_sql order by $order_sql $limit",
			intval(local_user())
		);
        if ($r!==false && count($r)>0) return $this->_set_extra($r);
        return false;
    }
    
    /**
     * @brief get one note for local_user() by $id value
     *
     * @param int $id
     * @return array note values or null if not found
     */
    public function getByID($id) {
        $r = q("select * from notify where id = %d and uid = %d limit 1",
                intval($id),
                intval(local_user())
        );
        if($r!==false && count($r)>0) {
            return $this->_set_extra($r)[0];
        }
        return null;
    }
    
    /**
     * @brief set seen state of $note of local_user()
     *
     * @param array $note
     * @param bool $seen optional true or false, default true
     * @return bool true on success, false on errors
     */
    public function setSeen($note, $seen = true) {
        return q("update notify set seen = %d where ( link = '%s' or ( parent != 0 and parent = %d and otype = '%s' )) and uid = %d",
            intval($seen),
            dbesc($note['link']),
            intval($note['parent']),
            dbesc($note['otype']),
            intval(local_user())
        );
    }
       
    /**
     * @brief set seen state of all notifications of local_user()
     *
     * @param bool $seen optional true or false. default true
     * @return bool true on success, false on error
     */
    public function setAllSeen($seen = true) {
    	return q("update notify set seen = %d where uid = %d",
            intval($seen),
			intval(local_user())
		);
    }
}
