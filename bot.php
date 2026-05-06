<?php
// bot.php
require 'connection.php';

if($conn && isset($_POST['text'])){
    $user_messages = $conn->real_escape_string($_POST['text']);
    
    // Mas maayo nga matching: I-check kung ang gisulat sa user nag-match ba directly, 
    // o kaha kung naay keyword sa database nga nakasulod sa sentence sa user (gi-apilan ug TRIM para safe sa spaces).
    $check_data = "SELECT replies FROM messages WHERE queries LIKE '%$user_messages%' OR LOWER('$user_messages') LIKE CONCAT('%', LOWER(TRIM(queries)), '%') ORDER BY LENGTH(queries) DESC LIMIT 1";
    $run_query = $conn->query($check_data);
    
    if($run_query && $run_query->num_rows > 0){
        $fetch_data = $run_query->fetch_assoc();
        $replay = $fetch_data['replies'];
        echo $replay;
    }else{
        // Gi-English ang default reply
        echo "Sorry, I didn't quite understand that. Could you please rephrase or contact the administrator?";
    }
}else{
    echo "Connection Failed.";
}
?>