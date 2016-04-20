<?php
    //phpinfo();
    
    //error_reporting(E_ALL);
    //ini_set('display_startup_errors',1);
    //ini_set('display_errors',1);
    //error_reporting(-1);
    
    echo "Testing database PDO connection...<br>";
    
    $SECRET = "diu7ajksf8sj,vKLDHliewudksfj"; //  place this in WebApp settings
    
    $connenv = getenv("SQLAZURECONNSTR_defaultConnection");
    parse_str(str_replace(";", "&", $connenv), $connarray);
    
    $connstring = "sqlsrv:Server=".$connarray["Data_Source"].";Database=".$connarray["Initial_Catalog"];
    $user = $connarray["User_Id"];
    $pass = $connarray["Password"];
    
    //var_dump($connarray);
    //var_dump($connstring);
    //var_dump($user);
    //var_dump($pass);
    
    function printCollations($conn)
    {
        $sql = "SELECT name, description FROM sys.fn_helpcollations()";
        foreach ($conn->query($sql) as $row)
        {
            print $row['name'] . "\t";
            print $row['description'] . "<br>";
        }
    }

    function printAllTables($conn)
    {
        print "List of all tables:<br>";
        $sql = "SELECT sobjects.name FROM sysobjects sobjects WHERE sobjects.xtype = 'U'";
        foreach ($conn->query($sql) as $row)
        {
            print $row[0] . "<br>";
        }
    }
    
    function printTodoItems($conn)
    {
        print "List of all tables:<br>";
        $sql = "SELECT * FROM md.TodoItem WHERE sobjects.xtype = 'U'";
        foreach ($conn->query($sql) as $row)
        {
            print $row[0]."--".$row[1]."--".$row[2]."--".$row[3]."<br>";
        }
    }

    try
    {
        $conn = new PDO( $connstring, $user, $pass );
        
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        //printCollations($conn);
        
        print "Dropping the table...<br>";
        $sqldrop ="DROP TABLE usertable";
        try
        {
            $conn->exec($sqldrop);
            print "The table was dropped <br>";
        } catch ( PDOException $e ) { echo "Drop table error. May be it does not exist.<br>"; }
        
        $sqlcreate ="CREATE TABLE usertable( ".
                 "ID INT NOT NULL IDENTITY(1,1) PRIMARY KEY,".
                 "firstname     VARCHAR( 64 ) NOT NULL,".
                 "lastname      VARCHAR( 64 ) NOT NULL,".
                 "email         VARCHAR( 64 ) NOT NULL UNIQUE,".
                 "password      VARCHAR( 128 ) NOT NULL,".
                 "admin         BIT  NOT NULL".
                 ")";
        
        try {
            $conn->exec($sqlcreate);
            print("The table was created.<br>");
        } catch ( PDOException $e ) { echo "Create table error!!!"; die(print_r($e)); }
        
        printAllTables($conn);
        printTodoItems($conn);
        
        $sqlinsert = "insert into usertable (firstname, lastname, email, password, admin) values (?, ?, ?, ?, ?)";
        $insertquery = $conn->prepare($sqlinsert);
      
        // test set of usertable
        $myusers = array(
            array("firstname", "lastname","admin@server.com", "adminpassword", 1),
            array("firstname", "lastname","user1@server.com", "user1password", 0),
            array("firstname", "lastname","user2@server.com", "user1password", 0) );
        
        foreach($myusers as $user)
        {
            $firstname = $user[0];
            $lastname  = $user[1];
            $email     = $user[2];
            $userpasshash = hash( "whirlpool", $SECRET.$user[3].$SECRET, false );
            $isAdmin=$user[4];
            $insertquery->execute(array($firstname, $lastname, $email, $userpasshash, $isAdmin));
            
            echo "Insert error code = ".$insertquery->errorCode()." "; // Five zeros are good like this 00000 but HY001 is a common error
            echo "Number of rows inserted = ".$insertquery->rowCount()."<br>";
        }
        
        print "<br>Selecting rows from the table...<br>";
        
        $sqlselect = "SELECT email,password,admin FROM usertable";
        foreach ($conn->query($sqlselect) as $row)
        {
            print   htmlspecialchars($row['email'])." ".
                    htmlspecialchars($row['password'])." ".
                    "admin=".htmlspecialchars($row['admin'])."<br>";
        }
        
        print "Dropping the table...<br>";
        
        try
        {
            $conn->exec($sqldrop);
            print "The table was dropped <br>";
        } catch ( PDOException $e ) { echo "Drop table error. May be it does not exist.<br>"; }
    }
    catch ( PDOException $e )
    {
        echo "Some PDO Error occured...";
    
        // TODO: There is a security problem here. Do not do this in production!!!
        print( "PDO Error : " );
        die(print_r($e));
    }

?>
