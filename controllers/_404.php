<?php

class _404_Controller {
	
	public function index() {
		
		header('HTTP/1.1 404 File Not Found');
		echo '<html><head><title>404 Not Found</title></head>
		<body><h1>404 Not Found</h1>
		<p>The requested URL was not found on this server.</p>
		</body></html>';
		
	}
	
}