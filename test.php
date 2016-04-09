<?php

    echo htmlspecialchars("Hello");
 
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
        $conn = new PDO ( "sqlsrv:server = tcp:mdavydovlab4.database.windows.net,1433; Database = MDavydovLab4", "mdavydov", "{your_password_here}");
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    }
    catch ( PDOException $e )
    {
        print( "Error connecting to SQL Server." );
        die(print_r($e));
    }

?>
