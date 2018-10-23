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
    protected $signature = 'cm:mysql {domain}';

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

        $conf_dir = env('SYS_SITES_CONF');
        if(!$conf_dir){
            echo "Env Conf Dir 'SYS_SITES_CONF' not configured! See .env file.\n";
            exit;
        }

        if(!file_exists(sprintf('%s/%s', $sites_dir, $domain))){
            echo sprintf("Domain not yet installed.\n Run `appserv site %s` first.\n", $domain);
            exit;
        }

        $root_pass = $this->secret("I can set up your MySQL user and table for you. First thing I need is your MySQL ROOT user's password");

        $auto_creds_options = ['Do it for me', 'I\'ll give you credentials'];
        $auto_creds = $this->choice("OK, do you want me to generate random credentials for you or do you want to do it yourself?", ['Do it for me', 'I\'ll give you credentials'], 0);

        if($auto_creds == $auto_creds_options[0]){
            $db = 'cm_' . str_random(8);
            $user = 'cm_' . str_random(8);
            $password = str_random(18);
        }else{
            $db = $this->ask("What is the name of the new database?");
            $user = $this->ask("What is the name of the new user?");
            $password = $this->secret("What is the new password?");
        }

        $sql = "CREATE DATABASE $db DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;\n";
        $sql .= "GRANT ALL ON $db.* TO '$user'@'localhost' IDENTIFIED BY '$password';\n";
        $sql .= "FLUSH PRIVILEGES;\n";

        $command = "mysql -u --password='$root_pass' << END\n\n$sql\nEND";
        dump([
            $root_pass,
            $auto_creds,
            $db,
            $user,
            $password,
            $command
        ]);

        $process = new Process($command);
        $process->run();
        if(!$process->isSuccessful()){
            echo "\033[1;30m\033[41mCould not create db credentials.\033[0m\n";
        }else{
            echo "Credentials created!\n";
        }
    }
}
