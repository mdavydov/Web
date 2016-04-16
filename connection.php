<?php
    error_reporting(E_ALL);

    class UserTable
    {
        var $SECRET = "diu7ajksf8sj,vKLDHliewudksfj"; //  place this in WebApp settings
        var $conn = NULL;
        
        function UserTable()
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
                print_r($e);
                die("Database connection error");
            }
        }
        
        function createTables()
        {
            $conn = $this->conn;
            if (!$conn) die("Database connection is closed");
        
            $sqlcreate ="CREATE TABLE usertable( ".
                 "ID INT NOT NULL IDENTITY(1,1) PRIMARY KEY,".
                 "firstname     VARCHAR( 64 ) NOT NULL,".
                 "lastname      VARCHAR( 64 ) NOT NULL,".
                 "email         VARCHAR( 64 ) NOT NULL UNIQUE,".
                 "password      VARCHAR( 128 ) NOT NULL,".
                 "admin         BIT  NOT NULL".
                 ")";
            
            try
            {
                $conn->exec($sqlcreate);
            }
            catch ( PDOException $e )
            {
                echo "Create table error. May be it exists.<br>"; die(print_r($e));
            }
            
            print("The table was created.<br>");
        }
        
        function passwordHash($passwd)
        {
            echo "Line10<br>";
            try
            {
                return hash( "whirlpool", $SECRET.$passwd.$SECRET, false );
            }
            catch(Exception $e)
            {
                print_r($e);
            }
        }
        
        function addUser($firstname, $lastname, $email, $passwd)
        {
            $conn = $this->conn;
            if (!$conn) die("Database connection is closed");
            
            $sqlinsert = "insert into usertable (firstname, lastname, email, password, admin) values (?, ?, ?, ?, ?)";
            
            echo "Line1<br>";
            $insertquery = $conn->prepare($sqlinsert);
            echo "Line2<br>";
            $passhash = passwordHash($passwd);
            echo "Line3<br>";
            $isAdmin=0;
            
            try
            {
                echo "Line4<br>";
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
            if (!$conn) die("Database connection is closed");
            
            $sqlselect = "select count(*) from usertable where email=? AND password=?";
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
            $conn = $this->conn;
            if (!$conn) die("Database connection is closed");
            
            print "Dropping the table...<br>";
            $sqldrop ="DROP TABLE usertable";
            try
            {
                $conn->exec($sqldrop);
                print "The table was dropped <br>";
            }
            catch ( PDOException $e )
            {
                echo "The table was not dropped <br>";
                // TODO: There is a security problem here. Do not do this in production!!!
                //print( "PDO Error : " );
                //die(print_r($e));
            }
        }
    }
    
    $users = new UserTable();
    
    $users->drop();
    
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
    
    $users->drop();
?>
