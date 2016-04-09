<?php
    //phpinfo();
    
    error_reporting(E_ALL);
    ini_set('display_startup_errors',1);
    ini_set('display_errors',1);
    error_reporting(-1);
    
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

    try
    {
        $conn = new PDO( $connstring, $user, $pass );
        
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        
        //printCollations($conn);
        
        
        $sqlcreate ="CREATE TABLE users( ID INT( 11 ) AUTO_INCREMENT PRIMARY KEY,".
                 "login         VARCHAR( 250 ) NOT NULL,".
                 "password      VARCHAR( 128 ) NOT NULL,".
                 "admin         BIT);";
        
        $conn->exec($sqlcreate);
        
        print("Created $table Table.<br>");
        
        $sqlinsert = "insert into users (login,password,admin) values (?, ?, ?)";
        $insertquery = $conn->prepare($sqlinsert);
      
        // test set of users
        $myusers = array(
            array("admin", "adminpassword", 1),
            array("user1", "user1password", 0),
            array("user2", "user1password", 0) );
        
        foreach($myusers as $user)
        {
            $username = $user[0];
            $userpasshash = hash( "whirlpool", $SECRET.$user[1].$SECRET, false );
            $isAdmin=$user[2];
            $insertquery->execute(array($username, $userpasshash, $isAdmin));
            
            echo $insertquery->errorCode().'<br><br>'; // Five zeros are good like this 00000 but HY001 is a common error
            echo $insertquery->rowCount();
        }
        
        print("Values where inserted<br>");
        
        $sqlselect = "SELECT login,password,admin FROM users";
        foreach ($conn->query($sql) as $row)
        {
            print   htmlspecialchars($row['login'])." ".
                    htmlspecialchars($row['password'])." ".
                    "admin=".htmlspecialchars($row['admin'])."<br>";
        }
        print("Values where selected...");
        
        $sqldrop ="DROP TABLE users";
        
        $conn->exec($sqldrop);
    }
    catch ( PDOException $e )
    {
        // TODO: There is a security problem here. Do not do this in production!!!
        print( "PDO Error : " );
        die(print_r($e));
    }

?>
