# Appserv Tools

## Intro
Appserv Tools is a bunch of commands to automate all of the general laravel app
setup stuff. This tool can:
1. Create your app's directory
2. Setup nginx configs
3. Make necessary directories writable
4. Show you config info for setting up envoyer (IP, user, etc. etc.)
5. Let you input your envoyer worker public key
6. Delete an existing site
7. Setup MySQL credentials for you (create the db, user, etc.)
8. Secure the site

The available commands are listed below. They can be run either using Artisan
directly by `cd`ing into this apps directory and replacing `appserv` with
`php artisan cm:`, or you can run them from anywhere using the `appserv`
utility. Either way, some tools need sudoer permissions to run, so you may be
prompted for your user's password. Your user should be in the sudoers group.

## TODO
* "guide" mode
* "help" updates
* readme updates
* general cleanup and refactoring
* normalize name ("Appserv", "App Server Tools", "Appserv Tools")

## Commands
--todo--
