<?php
    //phpinfo();
    
    $connenv = getenv("SQLAZURECONNSTR_defaultConnection");
    parse_str(str_replace(";", "&", $connenv), $connarray);
    
    $connstring = "sqlsrv:Server=".$connarray["Data_Source"].";Database=".$connarray["Initial_Catalog"];
    $user = $connarray["User_Id"];
    $pass = $connarray["Password"];
    
    var_dump($connarray);
    var_dump($connstring);
    var_dump($user);
    var_dump($pass);
    
    
    echo "<br>";
    
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
        foreach($_ENV as $k => $v)
        {
            echo htmlspecialchars($k. " => ". $v)."<br>";
        }
    }
    catch(Exception $err)
    {
        echo $err->getMessage();
    }
 
    try
    {
        $conn = new PDO( $connstring, $user, $pass );
        
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        
        printCollations($conn);
    }
    catch ( PDOException $e )
    {
        print( "Error connecting to SQL Server." );
        die(print_r($e));
    }

?>
