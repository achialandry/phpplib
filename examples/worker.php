<?php

/*
    This example shows how to create parallel workers to one task, and how to wait all workers
 */

require __DIR__.'/../vendor/autoload.php';

class Worker extends \Phcco\Phpplib\Process
{
    public function run()
    {
        echo "Hi, I'm ".$this->getPid()." child of ".$this->getParentPid()."\n";
        $to = rand(3, 5);
        for($i=1; $i<=$to;$i++){
            echo $this->getPid()." -> Counting 1 to $i\n";
            sleep(1);
        }
        echo "End of ".$this->getPid()." child of ".$this->getParentPid()."...\n";
    }
}

echo "Creating children...\n";
$worker1 = new Worker();
$worker2 = new Worker();

echo "Forking...\n";
$worker1->fork();
$worker2->fork();

echo "Doing something...\n";
sleep(2);
echo "I'm ready, waiting on children...\n";

$worker1->wait();
echo "Child 1 OK\n";

$worker2->wait();
echo "Child 2 OK\n";

echo "OK! All done!\n";
