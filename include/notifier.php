<?php




/*
The reason for the MySQL "Lost Connection during query" issue when forking is the fact that the child process inherits the parent's database connection. When the child exits, the connection is closed. If the parent is performing a query at this very moment, it is doing it on an already closed connection, hence the error.

An easy way to avoid this is to create a new database connection in parent immediately after forking. Don't forget to force a new connection by passing true in the 4th argument of mysql_connect():




$pid = pcntl_fork();
            
if ( $pid == -1 ) {       
    // Fork failed           
    exit(1);
} else if ( $pid ) {
	// parent process - regenerate our connections in case the kid kills them
	@include(".htconfig.php");
	$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
	unset($db_host, $db_user, $db_pass, $db_data);
	session_write_close();
	session_start();

	return;
} else {
    // We are the child
    // Do something with the inherited connection here
    // It will get closed upon exit
    exit(0);
?>


If you want to execute some code after your php page has been returned to the user. Try something like this -

<?php
function index()
{
        function shutdown() {
            posix_kill(posix_getpid(), SIGHUP);
        }

        // Do some initial processing

        echo("Hello World");

        // Switch over to daemon mode.

        if ($pid = pcntl_fork())
            return;     // Parent

        ob_end_clean(); // Discard the output buffer and close

        fclose(STDIN);  // Close all of the standard
        fclose(STDOUT); // file descriptors as we
        fclose(STDERR); // are running as a daemon.

        register_shutdown_function('shutdown');

        if (posix_setsid() < 0)
            return;

        if ($pid = pcntl_fork())
            return;     // Parent

        // Now running as a daemon. This process will even survive
        // an apachectl stop.

        sleep(10);

        $fp = fopen("/tmp/sdf123", "w");
        fprintf($fp, "PID = %s\n", posix_getpid());
        fclose($fp);

        return;
}
?>

while(count($this->currentJobs) >= $this->maxProcesses){
    echo "Maximum children allowed, waiting...\n";
    sleep(1);
}
duerra at yahoo dot com
02-Jul-2010 02:06
Using pcntl_fork() can be a little tricky in some situations.  For fast jobs, a child can finish processing before the parent process has executed some code related to the launching of the process.  The parent can receive a signal before it's ready to handle the child process' status.  To handle this scenario, I add an id to a "queue" of processes in the signal handler that need to be cleaned up if the parent process is not yet ready to handle them. 

I am including a stripped down version of a job daemon that should get a person on the right track.

<?php
declare(ticks=1);
//A very basic job daemon that you can extend to your needs.
class JobDaemon{

    public $maxProcesses = 25;
    protected $jobsStarted = 0;
    protected $currentJobs = array();
    protected $signalQueue=array();  
    protected $parentPID;
  
    public function __construct(){
        echo "constructed \n";
        $this->parentPID = getmypid();
        pcntl_signal(SIGCHLD, array($this, "childSignalHandler"));
    }
  
    /**
    * Run the Daemon
    */
    public function run(){
        echo "Running \n";
        for($i=0; $i<10000; $i++){
            $jobID = rand(0,10000000000000);
            $launched = $this->launchJob($jobID);
        }
      
        //Wait for child processes to finish before exiting here
        while(count($this->currentJobs)){
            echo "Waiting for current jobs to finish... \n";
            sleep(1);
        }
    }
  
    /**
    * Launch a job from the job queue
    */
    protected function launchJob($jobID){
        $pid = pcntl_fork();
        if($pid == -1){
            //Problem launching the job
            error_log('Could not launch new job, exiting');
            return false;
        }
        else if ($pid){
            // Parent process
            // Sometimes you can receive a signal to the childSignalHandler function before this code executes if
            // the child script executes quickly enough!
            //
            $this->currentJobs[$pid] = $jobID;
          
            // In the event that a signal for this pid was caught before we get here, it will be in our signalQueue array
            // So let's go ahead and process it now as if we'd just received the signal
            if(isset($this->signalQueue[$pid])){
                echo "found $pid in the signal queue, processing it now \n";
                $this->childSignalHandler(SIGCHLD, $pid, $this->signalQueue[$pid]);
                unset($this->signalQueue[$pid]);
            }
        }
        else{
            //Forked child, do your deeds....
            $exitStatus = 0; //Error code if you need to or whatever
            echo "Doing something fun in pid ".getmypid()."\n";
            exit($exitStatus);
        }
        return true;
    }
  
    public function childSignalHandler($signo, $pid=null, $status=null){
      
        //If no pid is provided, that means we're getting the signal from the system.  Let's figure out
        //which child process ended
        if(!$pid){
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
      
        //Make sure we get all of the exited children
        while($pid > 0){
            if($pid && isset($this->currentJobs[$pid])){
                $exitCode = pcntl_wexitstatus($status);
                if($exitCode != 0){
                    echo "$pid exited with status ".$exitCode."\n";
                }
                unset($this->currentJobs[$pid]);
            }
            else if($pid){
                //Oh no, our job has finished before this parent process could even note that it had been launched!
                //Let's make note of it and handle it when the parent process is ready for it
                echo "..... Adding $pid to the signal queue ..... \n";
                $this->signalQueue[$pid] = $status;
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
        return true;
    }
}



*/



function notifier(&$a,$item_id,$parent_id) {

	$pid = pcntl_fork();
            
	if ($pid == (-1)) {
		notice("Failed to launch background notifier." . EOL );
		return;
	}       

	if ($pid > 0) {
		// parent process - regenerate our connections in case the kid kills them
	
		@include(".htconfig.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data, $install);
		unset($db_host, $db_user, $db_pass, $db_data);
		session_write_close();
		session_start();

		// go back and finish the page
		return;
	} 
	else {
		// We are the child

	// fetch item

	// if not parent, fetch it

	// atomify

	// expand list of recipients

	// grab the contact records

	// foreach recipient

	// if no dfrn-id continue

	// fetch_url dfrn-notify

	// decrypt challenge

	// post result

	// continue

		killme();
	}
}
