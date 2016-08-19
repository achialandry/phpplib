<?php

namespace Phcco\Phpplib;

/**
 * Manage multiple processors
 */
class Manager extends Process
{

    const RESTART_ON_EXIT = 1;

    const RESTART_ON_ERROR = 2;

    /**
     * If the worker must spawn new process or not, and if it must end the others
     *
     * @var boolean
     */
    public $working = true;

    /**
     * Active process managed
     *
     * @var array
     */
    public $processes = array();

    /**
     * Timeout to shutdown
     *
     * @var boolean
     */
    public $shutdown_timeout = false;

    /**
     * Create a manager and set the default handlers
     * SIGINT will stop this manager
     */
    public function __construct($options = [])
    {

        parent::__construct($options);

        declare (ticks = 1);

        $process_manager = $this;

        $this->setSignalHandler(
            SIGINT, function ($signal) use ($process_manager) {
                $process_manager->working = false;
            }
        );

    }

    /**
     * Add a process to this manager
     *
     * @param string $class    Process class name
     * @param array  $options  Options
     * @param string $name     Friendly name
     * @param mixed  $behavior Behavior to process stop
     */
    public function addProcess($class, $options = null, $name = null , $behavior = 0)
    {
        $process = $this->spawn($class, $options);
        $proc = array(
                'pid'      => $process->getPid(),
                'class'    => $class,
                'options'  => $options,
                'name'     => $name === null ? 'anonymous' : $name,
                'behavior' => $behavior,
            );
        // $this->log($proc['pid']." spawned for ".$proc['class'].'/'.$proc['name']);
        $this->processes[] = $proc;
        return $process;
    }

    /**
     * Start, manage and stop the children processes
     *
     * @return null
     */
    public function run()
    {
        while (count($this->processes)) {
            $status = null;
            $myId = pcntl_waitpid(-1, $status, WNOHANG);
            foreach ($this->processes as $key => $process) {
                if ($myId == $process['pid']) {
                    if (!$this->working) {
                        $proc = $this->processes[$key];
                        unset($this->processes[$key]);
                        $lfnum = count($this->processes);
                        // $this->log("PID {$myId} ({$proc['class']}/{$proc['name']}) picked, $lfnum left...");
                    } else{
                        $restart = false;
                        $success = pcntl_wifexited($status);
                        $exit_status = pcntl_wexitstatus($status);
                        if(is_numeric($process['behavior'])) {
                            if(($success  && (($process['behavior'] && self::RESTART_ON_EXIT) == self::RESTART_ON_EXIT))
                                || (!$success && (($process['behavior'] && self::RESTART_ON_ERROR) == self::RESTART_ON_ERROR))
                            ) {
                                $restart = true;
                            }
                        }elseif(is_callable($process['behavior'])) {
                            $restart = call_user_func($process['behavior'], $process, $exit_status);
                        }
                        if($restart) {
                            $new_process = $this->spawn($process['class'], $process['options']);
                            $this->processes[$key]['pid'] = $new_process->getPid();
                            $this->processes[$key]['proc'] = $new_process;
                            // $this->log(
                            //     "PID {$myId} picked, ".
                            //     $this->processes[$key]['pid']." spawned for ".
                            //     $process['class'].'/'.$process['name']
                            // );
                        }else{
                            unset($this->processes[$key]);
                            //$this->log(
                            //    "PID {$myId} finished with ".($success?'success':'error')." code ".$exit_status
                            // );
                        }
                    }
                }
            }
            if (!$this->working) {
                if ($this->shutdown_timeout && $this->shutdown_timeout<time()) {
                    // $this->log("We have some immortals...");
                    foreach ($this->processes as $process) {
                        $pid = $process['pid'];
                        //$this->log("Killing $pid ({$process['class']}/{$process['name']})...");
                        posix_kill($pid, SIGKILL);
                    }
                    break;
                } elseif (!$this->shutdown_timeout) {
                    $this->shutdown_timeout = time()+5;
                }
            }
            usleep(10000); // Manage only sometimes
        }
    }

    /**
     * Cria um novo processo com base em uma classe de Processo
     *
     * @param  string $class   Classe do processo
     * @param  array  $options Opções
     * @return int Pid do novo processo
     */
    public function spawn($class, $options)
    {
        if (!class_exists($class)) {
            throw new \Exception("Class '{$class}' not exists!");
        }
        $process = new $class($options);
        if (!is_subclass_of($process, '\\Phcco\\Phpplib\\Process')) {
            throw new \Exception("Class '{$class}' is not a Process object");
        }
        $process->fork();
        return $process;
    }


}