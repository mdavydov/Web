<?php
    error_reporting(E_ALL);
    
    function myErrorHandler($errno, $errstr, $errfile, $errline)
    {
//        if (!(error_reporting() & $errno))
//        {
//            // This error code is not included in error_reporting
//            return;
//        }

        echo "<b>Error</b> [$errno] $errstr in <b>$errfile</b> line $errline <br />\n";

        /* Don't execute PHP internal error handler */
        return false;
    }
    
    set_error_handler("myErrorHandler");
    
    class LoginFailedException extends Exception {}
    class SessionExpiredException extends Exception {}

    class UserTable
    {
        var $SECRET = "diu7ajksf8sj,vKLDHliewudksfj"; //  place this in WebApp settings
        var $conn = NULL;
        
        function UserTable()
        {
            $connenv = getenv("SQLAZURECONNSTR_defaultConnection");
            // There will be a problem if you have & or ; in your password
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
        
            try
            {
                $conn->exec( "CREATE TABLE usertable( ".
                             "ID INT NOT NULL IDENTITY(1,1) PRIMARY KEY,".
                             "firstname     VARCHAR( 64 ) NOT NULL,".
                             "lastname      VARCHAR( 64 ) NOT NULL,".
                             "email         VARCHAR( 64 ) NOT NULL UNIQUE,".
                             "password      VARCHAR( 128 ) NOT NULL,".
                             "admin         BIT  NOT NULL".
                             ")");
                print("Table 'usertable' was created.<br>");
            }
            catch ( PDOException $e )
            {
                echo "Create table 'usertable' error. May be it already exists.<br>"; print_r($e); echo "<br>";
            }
            
            try
            {
                $conn->exec("CREATE TABLE sessions( ".
                             "sessionid     VARCHAR( 64 ) NOT NULL PRIMARY KEY,".
                             "email         VARCHAR( 64 ) NOT NULL UNIQUE,".
                             "time          DATETIME NOT NULL DEFAULT GETDATE()".
                             ")");
                print("Table 'sessions' was created.<br>");
            }
            catch ( PDOException $e )
            {
                echo "Create table 'sessions' error. May be it already exists.<br>"; print_r($e); echo "<br>";
            }
        }
        
        function passwordHash($email, $passwd)
        {
            try
            {
                return hash( "whirlpool", $SECRET.$email.$SECRET.$passwd.$SECRET, false );
            }
            catch(Exception $e)
            {
                print_r($e);
                die("Can't encode string");
            }
        }
        
        function newSessionHash($email)
        {
            try
            {
                return hash( "sha256", $SECRET.$email.$SECRET.time().$SECRET, false );
            }
            catch(Exception $e)
            {
                print_r($e);
                die("Can't encode session id");
            }
        }
        
        function addUser($firstname, $lastname, $email, $passwd)
        {
            $conn = $this->conn;
            if (!$conn) die("Database connection is closed");
            
            $passhash = $this->passwordHash($email, $passwd);
            $isAdmin=0;
            
            try
            {
                echo "Before insert<br>";
                $q = $conn->prepare("insert into usertable (firstname, lastname, email, password, admin) ".
                                "values (?, ?, ?, ?, ?)");
                echo "Before execute<br>";
                
                $q->execute(array($firstname, $lastname, $email, $passhash, $isAdmin));
                
                echo "Insert error code = ".$insertquery->errorCode()." "; // Five zeros are good like this 00000
                echo "Number of rows inserted = ".$insertquery->rowCount()."<br>";
                
                if ($insertquery->errorCode() === "00000" && $insertquery->rowCount()==1) return true;
            }
            catch ( PDOException $e )
            {
                print_r($e);
                return false;
            }
        }
        
        // returns user's ($firstname, $lastname) or throws LoginFailedException, PDOException
        function checkLoginAndGetName($email, $passwd)
        {
            $conn = $this->conn;
            if (!$conn) die("Database connection is not set");
            
            $sqlselect = "select firstname, lastname from usertable where email=? AND password=?";
            $query = $conn->prepare($sqlselect);
            $passhash = $this->passwordHash($email, $passwd);
            
            foreach( $query->query(array($email, $passhash)) as $row)
            {
                // There is such a user
                return array($row[0], $row[1]);
            }
            // incorrect login/password
            throw new LoginFailedException();
        }
        
        // returns ($sessionid, $name) or throws LoginFailedException, PDOException
        function loginAndGetSessionIDandName($email, $passwd)
        {
            $conn = $this->conn;
            if (!$conn) die("Database connection is not set");
            
            list($firstname, $lastname) = $this->checkLoginAndGetName($email, $password);

            $conn->prepare("delete from sessions where email=?")->execute(array($email));
            
            $new_session_id = newSessionHash($email);
            $conn->prepare("insert into sessions(sessionid, email) values(?,?)")->
                        execute(array($new_session_id, $email));

            return array($new_session_id, $firstname." ".$lastname);
        }
        
        // returns email or throws SessionExpiredException.
        function getEmailBySessionId($session_id, $expire_seconds)
        {
            $conn = $this->conn;
            if (!$conn) die("Database connection is not set");
            
            $query = $conn->prepare("select email from sessions where sessionid=? and DATEDIFF(second, time, GETDATE()) > ?");
            foreach($query->query(array($session_id, $expire_seconds)) as $row)
            {
                // There is such a session
                return $row[0];
            }
            // session is expired or was never created
            throw new SessionExpiredException();
        }
        
        function drop()
        {
            $conn = $this->conn;
            if (!$conn) die("Database connection is closed");
            
            try
            {
                print "Dropping usertable...<br>";
                $conn->exec("DROP TABLE usertable");
                echo "Table usertable was dropped <br>";
            }
            catch ( PDOException $e )
            {
                echo "Table usertable was not dropped <br>";
            }
            
            try
            {
                print "Dropping sessions...<br>";
                $conn->exec("DROP TABLE sessions");
                print "Table sessions was dropped <br>";
            }
            catch ( PDOException $e )
            {
                echo "Table sessions was not dropped <br>";
            }
        }
        
        function dumpUsers()
        {
            $conn = $this->conn;
            if (!$conn) die("Database connection is not set");
            
            print "Dumping usertabe:<br>";
            foreach($conn->query("select * from usertable") as $row)
            {
                print_r($row); print "<br>";
            }
            print "<br>";
        }
        
        function dumpSessions()
        {
            $conn = $this->conn;
            if (!$conn) die("Database connection is not set");
            
            print "Dumping sessions:<br>";
            foreach($conn->query("select * from sessions") as $row)
            {
                print_r($row); print "<br>";
            }
            print "<br>";
        }
    }
    try
    {
    $users = new UserTable();
    
    $users->drop();
    
    $users->createTables();
    
    $users->addUser("User1", "Login1", "mail@user.com", "passwd");
    $users->addUser("User2", "Login2", "mail1@user.com", "passwd1");
    $users->addUser("SameUser", "SameUser", "mail@user.com", "passwd1");
    
    $users->dumpUsers();
    $users->dumpSessions();
    
    list($sess_id, $name) = $users->loginAndGetSessionIDandName("mail@user.com", "passwd");
    print "User ".$name." is logged in";
    $users->dumpSessions();
    
    $email = getEmailBySessionId($sess_id,10);
    assert($email == "mail@user.com");
    sleep(2);
    try
    {
        getEmailBySessionId($sess_id, 1);
        assert(false, "The session should be already expired");
    }
    catch(SessionExpiredException $e)
    {
        print "OK. Session is expired <br>";
    }
    
    $users->dumpSessions();
    
    $users->drop();
    
    }
    catch (Exception $e)
    {
        print "Exception!!! <br>";
        print_r($e); print "<br>";
    }
?>
