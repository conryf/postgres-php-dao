postgres-php-dao
================

A Postgres PHP Data Access Object

This is a super bare bones dao for Postgres in PHP. It requires that a history table be created (you could easily comment this out). It works as follows:

$USERNAME = 'username;
$PASSWORD = 'password';

$conn = pg_connect('dbname=iopengov user=' . $USERNAME . '  password=' . $PASSWORD) or die(pg_last_error());

$db = new dao($conn);
$db->set_action('select');
$db->set_table('table_name');
$db->set_where('field=value');
$res = $db->execute(Array('field1','field2'));

This is then a normal postgres resource object that you can loop through with pg_fetch_row or pg_fetch_object.
