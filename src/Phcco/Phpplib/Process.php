<?php

namespace Phcco\Phpplib;

/**
 * Describe a process
 */
abstract class Process
{

    /**
     * Options for this process
     *
     * @var array
     */
    protected $options;

    /**
     * Signals handled by this process
     *
     * @var array
     */
    protected static $signals = [];

    /**
     * Pid file path
     *
     * @var [type]
     */
    protected $pidfilepath;

    /**
     * If is child of another process (Manager)
     *
     * @var boolean
     */
    protected $is_child = false;

    /**
     * Pid for this process
     *
     * @var [type]
     */
    protected $pid;

    /**
     * Create a process with some options
     *
     * @param array $options Options to be available
     */
    public function __construct($options = [])
    {
        $this->pid = $this->parent_pid = getmypid();
        $this->options = $options;
    }

    /**
     * Fork and start this process
     * This method doesn't "run" if is the parent context
     *
     * @see    http://linux.die.net/man/2/fork
     * @return $this
     */
    public function fork()
    {
        $this->pid = pcntl_fork();
        if(!$this->pid) {
            $this->restoreSignalDefault();
            $this->is_child = true;
            $this->pid = getmypid();
            $this->setPidFileNumber();
            $this->run();
            die;
        }
        return $this;
    }

    /**
     * Your code must be done inside this run method ;)
     *
     * @return null
     */
    abstract public function run();

    /**
     * Create the pid file with a pid number
     */
    public function setPidFileNumber()
    {
        if($this->pidfilepath!==null) {
            return file_put_contents($this->pidfilepath, $this->pid);
        }
    }

    /**
     * Define the path of the pid file
     *
     * @param string $filepath Filepath
     */
    public function setPidFile($filepath)
    {
        $this->pidfilepath = $filepath;
        return $this;
    }

    /**
     * Get the PID file filepath
     *
     * @return string Filepath
     */
    public function getPidFile()
    {
        return $this->pidfilepath;
    }

    /**
     * Get the PID of this process (if child) or the child PID (if parent)
     *
     * @return int PID
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Get the parent PID
     *
     * @return int PID
     */
    public function getParentPid()
    {
        return posix_getppid();
    }

    /**
     * Set the process title
     *
     * @param string $title Process title
     */
    public function setTitle($title)
    {
        if($this->is_child) {
            cli_set_process_title($title);
        }
        return $this;
    }

    /**
     * Check if PID exists (linux only)
     * More reliable then posix_kill, believe me
     *
     * @param  int $pid PID
     * @return boolean true if exists
     */
    public function pidExists($pid)
    {
        clearstatcache();
        return file_exists('/proc/'.intval($pid));
    }

    /**
     * Wait for child
     *
     * @see    http://php.net/pcntl_waitpid
     * @param  integer $options Options to wait this pid, read the pcntl_waitpid options
     * @return mixed wait status
     */
    public function wait($options = 0)
    {
        if(!$this->is_child) {
            pcntl_waitpid($this->pid, $status, $options);
            return $status;
        }
    }

    /**
     * Set a signal handler
     * Will override if repeated, remove it with $callback == false
     *
     * @param int      $signal   Signal number
     * @param callable $callback Callback function
     */
    public function setSignalHandler($signal, $callback)
    {
        if($callback===false) {
            if(self::$signals[$signal]) {
                pcntl_signal($signal, SIG_DFL);
                unset(self::$signals[$signal]);
            }
        }else{
            pcntl_signal($signal, array($this, 'handleSignal'));
            self::$signals[$signal] = $callback;
        }
    }

    /**
     * Handle a signal
     *
     * @param  int $signal Signal number
     * @return null
     */
    public function handleSignal($signal)
    {
        if(isset(self::$signals[$signal])) {
            call_user_func(self::$signals[$signal], $signal);
        }
    }

    /**
     * Remove all signal handlers to default process handler
     *
     * @return null
     */
    public function restoreSignalDefault()
    {
        foreach(self::$signals as $signal=>$callback){
            $this->setSignalHandler($signal, false);
        }
    }

    /**
     * Daemonize the process (make it independent)
     * @return null
     */
    public function daemonize()
    {
        if(pcntl_fork()==0){
            posix_getppid();
        }else{
            die;
        }
    }

}