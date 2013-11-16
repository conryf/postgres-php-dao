<?php

class dao {

	var $table;
	var $action;
	var $where;
	var $fields;
	var $values;
	var $allowed_actions;
	var $action_types;
	var $cid;
	var $order_by;
	var $connection;

	function __construct($conn)
	{
		// do nothing
		$this->where = false;
		$this->action = "select";
		$this->table = "dual";
		$this->cid = -1;
		$this->order_by = '';
		$this->connection = $conn;
		$this->omit_quotes = false;

		//action info
		$this->action_types = array('insert' => 'I', 'select' => 'S', 'update' => 'U', 'delete' => 'D');
		$this->allowed_actions = array_keys($this->action_types);
	}

	function set_cid($new_cid)
	{
		$this->cid = $new_cid;
	}

	function set_omit_quotes($new_omit_quotes)
	{
		$this->omit_quotes = $new_omit_quotes;
	}

	function set_table($table_name)
	{
		$this->table = $table_name;
	}

	function set_action($action_type)
	{
		$action_type = strtolower($action_type);

		if(!in_array($action_type, $this->allowed_actions))
		{
			throw new exception('dao::set_action');
		}
		else
		{
			$this->action = $action_type;

            if($action_type == "select")
            {
                $this->where = " true ";
            }
            else 
            {
                $this->where = "false";
            }
		}
	}

	function set_where($where_clause)
	{
        // false is the default, so if it's false lets just make it the string "false"
        if($where_clause === false)
        {
            $where_clause = "false";
        }

		$this->where = $where_clause;
	}

	function set_order_by($order_by_clause)
	{
		$this->order_by = $order_by_clause;
	}

	function get_sql($fields = array(), $values = array())
	{
		switch($this->action) {
			case 'insert':
				if(count($fields) != count($values))
				{
					throw new exception('dao::execute inserts must have the same number of fields as values');
				}

                if(count($fields) == 0 || count($values) == 0)
                {
                    return false;
                }

				$sql = "insert into " . $this->table . " (" . implode(",",$fields) . ") values('" . implode("','", $values) . "')";
			break;
			case 'update':
				if(count($fields) != count($values))
				{
					throw new exception('dao::execute updates must have the same number of fields as values');
				}

                if(count($fields) == 0 || count($values) == 0)
                {
                    return false;
                }


				$sql = "update " . $this->table . " set ";
				
				for($i=0; $i < count($fields); $i++)
				{
					if($this->omit_quotes === true)
					{
						$sql .= $fields[$i] . "= " . $values[$i];
					}
					else
					{
						$sql .= $fields[$i] . "= '" . $values[$i] . "'";
					}

					if($i < count($fields)-1)
					{
						$sql .= ", ";
					}
				}

				$sql .= " where " . $this->where;
			break;
			case 'delete':
				$sql = "delete from " . $this->table . " where " . $this->where; 
			break;
			case 'select':
				if(count($fields) == 0 )
				{
					$field_list = "*";
				}
				else
				{
					$field_list = implode(",", $fields);
				}

				$sql = "select " . $field_list . " from " . $this->table . " where " . $this->where . ' ' . $this->order_by;
			break;
		}

		return $sql;
	}

	function execute($fields = array(), $values = array())
	{
		if(!isset($this->action) || !isset($this->table))
		{
			throw new exception('dao::execute action or table not set.');
		}
		else
		{
			$sql = $this->get_sql($fields, $values);

            if($sql === false)
            {
                return false;
            }

			$query_success = pg_query($this->connection, $sql) or die(pg_last_error() . ' --- ' . $sql);
			$error_text =  pg_result_error($query_success);
			
			//have to get the insert id now, otherwise we get the insert id from the history table
			if($this->action == "insert")
			{
				$insert_query = pg_query("SELECT lastval();");
				$insert_row = pg_fetch_row($insert_query);
				$query_success = $insert_row[0];
			}

			if($query_success !== false)
			{
				$history_sql = "insert into history (action_type, table_name, sql_statement, customer_id, error_text, executed) values('" . $this->action_types[$this->action] . "', '" . $this->table . "', '" . pg_escape_string($sql) . "', '" . $this->cid . "', '" . $error_text  . "', NOW())";
			}
			else
			{
				
				$history_sql = "insert into history (action_type, table_name, sql_statement, customer_id, executed) values('E', '" . $this->table . "', '" . pg_escape_string($sql) . "', '" . $this->cid . "', '" . $error_text . "', NOW())";
			}

			pg_query($this->connection, $history_sql) or die(pg_last_error() . "<br><br>" . $history_sql);

			if($this->action == "select" || $this->action == "insert")
			{
				return $query_success;
			}
			else
			{
				return pg_affected_rows($query_success);
			}

		}
	}

    function truncate_table( $table_name, $user=false, $passwd=false )
    {
        if( $user !== false )
        {
            $new_conn = pg_connect('dbname=iopengov user=' . $user) or die(pg_last_error());            
        }

        $sql = "truncate table $table_name";
        $query_success = pg_query($new_conn, $sql) or die(pg_last_error());;
        $error_text =  pg_result_error($query_success);

        $history_sql = "insert into history (action_type, table_name, sql_statement, customer_id, error_text, executed) values('D', '" . $table_name . "', '" . pg_escape_string($sql) . "', '" . $this->cid . "', '" . $error_text  . "', NOW())";
        pg_query($this->connection, $history_sql) or die(pg_last_error() . "<br><br>" . $history_sql);
    }
}

?>
