<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CmBase extends Command
{
    static $MSG_NO_DOMAIN="Domain not provided!";
    static $MSG_ALREADY_EXISTS="%s already exists!";
    static $MSG_CRON_SUCCESS="crontab successfully updated";
    static $MSG_NGINX_FAILED="nginx config test failed! Check %s.";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a base class and does nothing.';

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
    public function handle(){}

    public function wd($path){
        $wd = base_path();
        return "$wd/$path";
    }

    public function get_env(){
        $user =  env('SYS_USER');
        $group = env('SYS_GROUP');
        $sites_dir = env('SYS_SITES_ROOT');
        $conf_dir = env('SYS_SITES_CONF');

        if(!$user)
            $this->error('Env User "SYS_USER" not configured! See .env file.');
        if(!$group)
            $this->error('Env Group "SYS_GROUP" not configured! See .env file.');
        if(!$sites_dir)
            $this->error('Env Site Root "SYS_SITES_ROOT" not configured! See .env file.');
        if(!$conf_dir)
            $this->error('Env Site Root "SYS_SITES_CONF" not configured! See .env file.');

        $domain_dir = sprintf('%s/%s', $sites_dir, $domain);

        return compact('user', 'group', 'sites_dir', 'conf_dir', 'domain_dir');
    }

    public function command($command, $return=false){
        $verbose = false;
        $process = new Process($command);
        $process->run();
        if($process->isSuccessful()){
            if($verbose){
                $this->good($process->getOutput());
            }
            if($return){
                return $process->getOutput();
            }
            return true;
        }else{
            $this->error(sprintf("Command `%s` Failed!", $command));
        }
    }

    public function error($message, $verbosity = NULL){
        $message = sprintf("Error! %s Aborting!", $message);
        $this->bad($message);
        exit;
    }

    public function bad($message){
        echo sprintf("\033[1;30m\033[41m%s\033[0m\n", $message);
    }

    public function good($message){
        echo sprintf("%s\n", $message);
    }
}
