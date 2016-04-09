<?php
    //phpinfo();
    
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
        $conn = new PDO( getenv("SQLAZURECONNSTR_defaultConnection") );
        
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        
        printCollations($conn);
    }
    catch ( PDOException $e )
    {
        print( "Error connecting to SQL Server." );
        die(print_r($e));
    }

?>
