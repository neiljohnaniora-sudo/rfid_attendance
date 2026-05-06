<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Floating Button */
    #chat-toggle-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        background: #007bff;
        color: white;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 28px;
        cursor: pointer;
        box-shadow: 0px 4px 10px rgba(0,0,0,0.3);
        z-index: 10000;
        border: none;
        transition: transform 0.3s ease;
    }
    #chat-toggle-btn:hover {
        transform: scale(1.1);
        background: #0056b3;
    }

    /* Design para mu-float ang chatbox sa bottom-right */
    #chat-container {
        display: none; /* Naka-hide by default */
        position: fixed;
        bottom: 90px; /* Gipataas gamay para di matabonan ang button */
        right: 20px;
        width: 320px;
        background: white;
        border: 1px solid #ccc;
        box-shadow: 0px 4px 8px rgba(0,0,0,0.2);
        border-radius: 10px;
        z-index: 9999; /* Para naa siya sa ibabaw sa tanan elements */
        font-family: sans-serif;
    }
    .chat-header {
        background: #007bff;
        color: white;
        padding: 10px;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .close-chat { cursor: pointer; font-size: 18px; }
    .close-chat:hover { color: #ffcccc; }
    #chatbox {
        height: 250px;
        overflow-y: scroll;
        padding: 10px;
        background: #f9f9f9;
    }
    .user-msg { text-align: right; color: #fff; background: #007bff; padding: 5px 10px; border-radius: 10px; margin-bottom: 5px; display: inline-block; float: right; clear: both;}
    .bot-msg { text-align: left; color: #000; background: #e2e2e2; padding: 5px 10px; border-radius: 10px; margin-bottom: 5px; display: inline-block; float: left; clear: both;}
    
    .chat-suggestions {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        padding: 5px 10px;
        background: #f9f9f9;
        border-top: 1px solid #ccc;
    }
    .suggestion-chip {
        background: #e2e8f0;
        color: #1e293b;
        font-size: 11px;
        padding: 6px 10px;
        border-radius: 15px;
        cursor: pointer;
        transition: 0.2s;
    }
    .suggestion-chip:hover {
        background: #cbd5e1;
    }
    .chat-input-area {
        display: flex;
        padding: 10px;
        border-top: 1px solid #eee;
        background: #fff;
    }
    #data { flex: 1; padding: 5px; border: 1px solid #ccc; border-radius: 3px; }
    #send-btn { background: #007bff; color: white; border: none; padding: 5px 10px; margin-left: 5px; cursor: pointer; border-radius: 3px; }
</style>

<button id="chat-toggle-btn">
    <i class="fa-solid fa-headset"></i>
</button>

<div id="chat-container">
    <div class="chat-header">
        <span><i class="fa-solid fa-robot"></i> System Support Bot</span>
        <span class="close-chat" id="close-chat-btn"><i class="fa-solid fa-xmark"></i></span>
    </div>
    <div id="chatbox">
        <div class="bot-msg"><b>Bot:</b> Hello! How can I help you with the system today?</div>
    </div>
    <div class="chat-suggestions">
        <span class="suggestion-chip">Reset Password</span>
        <span class="suggestion-chip">Print Report</span>
        <span class="suggestion-chip">Export Records</span>
        <span class="suggestion-chip">Add Student</span>
    </div>
    <div class="chat-input-area">
        <input type="text" id="data" placeholder="Type here..." required>
        <button id="send-btn">Send</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function(){
        // Toggle chatbox kung i-click ang floating button
        $("#chat-toggle-btn").click(function(){
            $("#chat-container").fadeToggle("fast");
            $("#data").focus(); // Focus dayon sa input para maka-type ang user
        });
        
        // I-close ang chatbox kung i-click ang X button
        $("#close-chat-btn").click(function(){
            $("#chat-container").fadeOut("fast", function() {
                // Papason ang history sa chat inig close, ibalik ang default bot greeting
                $("#chatbox").html('<div class="bot-msg"><b>Bot:</b> Hello! How can I help you with the system today?</div>');
            });
        });

        // Ma-trigger ang send button kung i-press ang "Enter" key
        $("#data").keypress(function(e) {
            if(e.which == 13) {
                $("#send-btn").click();
            }
        });

        // Click event para sa mga suggestion chips
        $(".suggestion-chip").click(function(){
            $("#data").val($(this).text()); // Ibutang ang text sa input
            $("#send-btn").click(); // I-trigger ang send button
        });

        $("#send-btn").on("click", function(){
            $value = $("#data").val();
            if($value.trim() === "") return; // Dili mu-send kung walay text

            $msg = '<div class="user-msg">' + $value + '</div>';
            $("#chatbox").append($msg);
            $("#data").val(''); 

            $.ajax({
                url: 'bot.php',
                type: 'POST',
                data: 'text='+$value,
                success: function(result){
                    $replay = '<div class="bot-msg"><b>Bot:</b> ' + result + '</div>';
                    $("#chatbox").append($replay);
                    $("#chatbox").scrollTop($("#chatbox")[0].scrollHeight);
                }
            });
        });
    });
</script>