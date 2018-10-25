<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class UtilMysql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cm:mysql {domain?} {--remote} {--db=} {--user=} {--pass=} {--remoteip=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        //appserv mysql box4.codeandmortar.com --remote
        if($this->option('remote')){
            $domain = $this->argument('domain');
            if(!$domain){
                echo "Could not get Domain.\n";
                exit;
            }

            $sites_dir = env('SYS_SITES_ROOT');
            if(!$sites_dir){
                echo "Env Site Root 'SYS_SITES_ROOT' not configured! See .env file.\n";
                exit;
            }

            if(!file_exists(sprintf('%s/%s', $sites_dir, $domain))){
                echo sprintf("Domain not yet installed.\n Run `appserv site %s` first.\n", $domain);
                exit;
            }

            $env_path = sprintf('%s/%s/.env', $sites_dir, $domain);
        }else{
            $domain = null;
            $env_path = null;
        }

        $db = $this->option('db');
        $user = $this->option('user');
        $password = $this->option('pass');

        if($db && $user && $password){

        }else{
            $auto_creds_options = ['Do it for me', 'I\'ll give you credentials'];
            $auto_creds = $this->choice("OK, do you want me to generate random credentials for you or do you want to do it yourself?", $auto_creds_options, 0);

            if($auto_creds == $auto_creds_options[0]){
                $db = 'cm_' . str_random(8);
                $user = 'cm_' . str_random(8);
                $password = $this->generate_random_string(18);
            }else{
                $db = $this->ask("What is the name of the new database?");
                $user = $this->ask("What is the name of the new user?");
                $password = $this->secret("What is the new password?");
            }
        }

        if($this->option('remote')){
            $this_ip = $this->internal_ip();
            $server_ip = $this->ask("What is the (internal) IP address of the remote server?");
            $ssh_user = $this->ask("What is the name SSH user that can connect to the remote server?");

            $command = sprintf(
                'ssh %s@%s "appserv mysql --db=%s --user=%s --pass=\'%s\' --remoteip=%s"',
                $ssh_user, $server_ip, $db, $user, $password, $this_ip
            );

            $process = new Process($command);
            $process->run();

            echo $command . "\n";
            
            if(!$process->isSuccessful()){
                echo "Could not make remote connection.\n";
                echo $process->getOutput() . "\n";
                exit;
            }

        }else{
            $remote_host = $this->option('remoteip');
            $remote_host = $remote_host ? $remote_host : 'localhost';
            $root_pass = env('MYSQL_ROOT_PASS');
            $sql = "CREATE DATABASE $db DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;\n";
            $sql .= "GRANT ALL ON $db.* TO '$user'@'$remote_host' IDENTIFIED BY '$password';\n";
            $sql .= "FLUSH PRIVILEGES;\n";

            $command = "mysql -u root --password='$root_pass' << END\n\n$sql\nEND";
        }

        $process = new Process($command);
        $process->run();

        echo $command . "\n";

        if(!$process->isSuccessful()){
            echo "\033[1;30m\033[41mCould not create db credentials.\033[0m\n";
        }else{
            echo "Credentials created!\n";

            if($env_path){
                if(!file_exists($env_path)){
                    $process = new Process(sprintf('touch %s', $env_path));
                    $process->run();
                }
                $env = file_get_contents($env_path);
                $env = str_replace('DB_DATABASE', '#DB_DATABASE', $env);
                $env = str_replace('DB_USERNAME', '#DB_USERNAME', $env);
                $env = str_replace('DB_PASSWORD', '#DB_PASSWORD', $env);
                $new_env = "\n##Added by Appserv\n";
                $new_env .= "DB_DATABASE=\"$db\"\n";
                $new_env .= "DB_USERNAME=\"$user\"\n";
                $new_env .= "DB_PASSWORD=\"$password\"\n";
                $env .= $new_env;
                if(file_put_contents($env_path, $env)){
                    echo "I also updated the .env file!\n";
                }else{
                    echo "I couldn't update the .env file, you'll need to add the following yourself.\n$new_env";
                }
            }
        }
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

    public function generate_random_string($len = 18) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()-=_+';
        $char_len = strlen($chars);
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= $chars[rand(0, $char_len - 1)];
        }
        return $str;
    }
}
