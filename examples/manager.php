<?php

/*

    This example shows how to use a process manager to handle multiple processes

 */

require __DIR__.'/../vendor/autoload.php';

use \Phcco\Phpplib\Manager;

class Worker extends \Phcco\Phpplib\Process
{
    public function run()
    {
        echo "Hi, I'm ".$this->getPid()." child of ".$this->getParentPid()."\n";
        sleep(rand(5, 8));
        echo "End of ".$this->getPid()." child of ".$this->getParentPid()."...\n";
    }
}


echo "Creating manager...\n";
$manager = new Manager();

echo "Adding worker 1...\n";
$manager->addProcess(
    'Worker', [], microtime(), function () use ($manager) {
        // Never restart this worker
        return false;
    }
);

echo "Adding worker 2...\n";
$manager->addProcess(
    'Worker', [], microtime(), function () use ($manager) {
        // Restart only if equals = 1
        return rand(1, 5)==1;
    }
);

$manager->run();

echo "Process Manager End...\n";