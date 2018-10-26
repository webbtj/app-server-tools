<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class MysqlBind extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm:sqlbind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Binds the mysql server to its local network IP. This should ONLY be run on the DB server';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ip = $this->internal_ip();
        $mysql_conf_file = '/etc/mysql/mysql.conf.d/mysqld.cnf';
        $mysql_conf = file($mysql_conf_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach($mysql_conf as &$line){
            if(strpos($line, 'bind-address') !== false){
                $line = "bind-address = $ip";
            }
        }
        $mysql_conf = implode("\n", $mysql_conf);

        $this->command(sprintf('echo "%s" | sudo tee %s > /dev/null', $mysql_conf, $mysql_conf_file));
        $this->command('sudo systemctl restart mysql');
        echo "Done.\nBy the way, the internal IP of this DB server is $ip\n";
    }

    public function internal_ip(){
        $process = new Process('ip addr show eth1 | grep \'inet \' | awk \'{print $2}\' | cut -f1 -d\'/\'');
        $process->run();
        if(!$process->isSuccessful()){
            echo "Could not get internal IP.\n";
            exit;
        }
        return trim($process->getOutput());
    }

    public function command($command, $return=false){
        $verbose = false;
        $process = new Process($command);
        $process->run();
        if($process->isSuccessful()){
            if($verbose){
                echo sprintf("%s\n", $process->getOutput());
            }
            if($return){
                return $process->getOutput();
            }
        }else{
            $this->error(sprintf("\033[1;30m\033[41mCommand `%s` Failed!\033[0m\n", $command));
        }
    }

    public function error($message, $verbosity = NULL){
        echo sprintf("\033[1;30m\033[41mError! %s Aborting!\033[0m\n", $message);
        exit;
    }
}
