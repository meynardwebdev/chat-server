<?php
require_once 'utils/Chat.php';
$Chat = new Chat();

$host = 'localhost'; //host
$port = '9000'; //port
$null = null; //null var

// Create TCP/IP stream socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// reuseable port
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

// bind socket to specified host
socket_bind($socket, 0, $port);

// listen to port
socket_listen($socket);

// create and add listening socket to the list
$clients = array($socket);

// start endless loop, so that our script doesn't stop
while (true) {
	// manage multiple connections
	$new_clients = $clients;
    
	// returns the socket resources from $new_clients
	socket_select($new_clients, $null, $null, 0, 10);
	
	// check for new socket
	if (in_array($socket, $new_clients)) {
		$new_socket = socket_accept($socket); // accpet new socket connection
		$clients[] = $new_socket; // add socket to client lists
		
        // read data sent by the socket
		$header = socket_read($new_socket, 1024);
        
        // perform web socket handshake
		$Chat->handshake($header, $new_socket, $host, $port);
		
        // get ip address of connected socket
		socket_getpeername($new_socket, $ip);
        
        // notify all users about new connection
        $message_to_clients = json_encode([
            'type' => 'system',
            'message' => "Client $ip joined the chat room."
        ]);
		$response = $Chat->encode_message($message_to_clients);
        
		$Chat->send_message($clients, $response);
		
		// make room for new socket
		$found_socket = array_search($socket, $new_clients);
		unset($new_clients[$found_socket]);
	}
	
	// loop through all connected sockets
	foreach ($new_clients as $new_client) {	
		// check for any incomming data
		while(socket_recv($new_client, $buf, 1024, 0) >= 1) {
            // decode_message data
			$received_text = $Chat->decode_message($buf);
            
			$user_name = $received_text['name']; // sender name
			$user_message = $received_text['message']; // message text
			$user_color = $received_text['color']; // color
            
            $message_type = "user-message";
            // check if user entered the command /quit
            if (substr($user_message, 0, 1) == "/") {
                if (substr($user_message, 1, 4) == "quit") {
                    socket_close($new_client);
                } elseif (substr($user_message, 1, 4) == "nick") {
                    $message_type = "change-nick";
                    $user_message = substr($user_message, 5);
                }
            }
			
			// json encode the data
            $client_message = json_encode([
                'type' => $message_type,
                'name' => $user_name,
                'message' => $user_message,
                'color' => $user_color
            ]);
			$response_text = $Chat->encode_message($client_message);
            
            // send the data to client
			$Chat->send_message($clients, $response_text);
            
            // exit this loop
			break 2;
		}
		
		$buf = @socket_read($new_clients, 1024, PHP_NORMAL_READ);
        
        // check for disconnected client
		if ($buf === false) {
			// remove client for $clients array
			$found_socket = array_search($new_clients, $clients);
			socket_getpeername($new_clients, $ip);
			unset($clients[$found_socket]);
			
			// notify all users about disconnected connection
            $message_to_clients = json_encode([
                'type' => 'system',
                'message' => "Client $ip disconnected."
            ]);
			$response = $Chat->encode_message($message_to_clients);
            
            // send the message
			$Chat->send_message($clients, $response);
		}
	}
}

// close the listening socket
socket_close($socket);
