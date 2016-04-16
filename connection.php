<?php

    class UserTable
    {
        var $SECRET = "diu7ajksf8sj,vKLDHliewudksfj"; //  place this in WebApp settings
        var $conn = NULL;
        
        __construct()
        {
            $connenv = getenv("SQLAZURECONNSTR_defaultConnection");
            parse_str(str_replace(";", "&", $connenv), $connarray);
    
            $connstring = "sqlsrv:Server=".$connarray["Data_Source"].";Database=".$connarray["Initial_Catalog"];
            $user = $connarray["User_Id"];
            $pass = $connarray["Password"];
            
            try
            {
                $this->conn = new PDO( $connstring, $user, $pass );
                $this->conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            }
            catch ( PDOException $e )
            {
                echo "Database connection error";
            }
        }
        
        function createTables()
        {
            $sqlcreate ="CREATE TABLE users( ".
                 "ID INT NOT NULL IDENTITY(1,1) PRIMARY KEY,".
                 "firstname     VARCHAR( 64 ) NOT NULL,".
                 "lastname      VARCHAR( 64 ) NOT NULL,".
                 "email         VARCHAR( 64 ) NOT NULL UNIQUE KEY,".
                 "password      VARCHAR( 128 ) NOT NULL,".
                 "admin         BIT".
                 ");";
            
            try
            {
                $this->conn->exec($sqlcreate);
            }
            catch ( PDOException $e )
            {
                echo "Create table error. May be it exists.";
            }
            
            print("The table was created.<br>");
        }
        
        function passwordHash($passwd)
        {
            return hash( "whirlpool", $SECRET.$passwd.$SECRET, false );
        }
        
        function addUser($firstname, $lastname, $email, $passwd)
        {
            $conn = $this->conn;
            $sqlinsert = "insert into users (firstname, lastname, email, password, admin) values (?, ?, ?, ?, ?)";
            $insertquery = $conn->prepare($sqlinsert);
            $passhash = passwordHash($passwd);
            $isAdmin=0;
            
            try
            {
                $insertquery->execute(array($firstname, $lastname, $email, $passhash, $isAdmin));
                echo "Insert error code = ".$insertquery->errorCode()." "; // Five zeros are good like this 00000
                echo "Number of rows inserted = ".$insertquery->rowCount()."<br>";
                
                if ($insertquery->errorCode() === "00000" && $insertquery->rowCount()==1) return true;
            }
            catch ( PDOException $e )
            {
                return false;
            }
        }
        
        function checkLogin($email, $passwd)
        {
            $conn = $this->conn;
            $sqlselect = "select count(*) from users where email=? AND password=?";
            $query = $conn->prepare($sqlselect);
            
            $passhash = passwordHash($passwd);
            
            try
            {
                return $query->execute(array($email, $passhash)) && $query->fetchColumn() > 0;
            }
            catch(PDOException $e)
            {
                return false;
            }
        }
        
        function drop()
        {
            print "Dropping the table...<br>";
            $sqldrop ="DROP TABLE users";
            try
            {
                $conn->exec($sqldrop);
                print "The table was dropped <br>";
            }
            catch ( PDOException $e )
            {
                echo "Some PDO Error occured... The table was not dropped";
            
                // TODO: There is a security problem here. Do not do this in production!!!
                //print( "PDO Error : " );
                //die(print_r($e));
            }

        }
    }
    
    $users = new UserTable();
    
    $users->createTables();
    
    $users->addUser("User1", "Login1", "mail@user.com", "passwd");
    $users->addUser("User2", "Login2", "mail1@user.com", "passwd1");
    $users->addUser("SameUser", "SameUser", "mail@user.com", "passwd1");
    
    if (!$users->checkLogin("mail@user.com", "passwd"))
    {
        echo "User should be present";
    }
    
    if ($users->checkLogin("mail@user.com", "passwd1"))
    {
        echo "User should NOT be present";
    }
    
    if ($users->checkLogin("mail1@user.com", "passwd"))
    {
        echo "User should NOT be present";
    }
    
    if ($users->checkLogin("\" OR 1 OR \"", "passwd"))
    {
        echo "User should NOT be present";
    }
?>
