<?php
namespace CodeDay\Controllers;

class SplunkController extends \Controller {
    public function getIndex()
    {
        $username = \Session::get('splunk.username');
        $password = \Session::get('splunk.password');
        return \View::make('splunk', ['username' => $username, 'password' => $password]);
    }

    public function postIndex()
    {
        if (\Session::get('splunk.username')) {
            $username = \Session::get('splunk.username');
            $password = \Session::get('splunk.password');
            return \View::make('splunk', ['username' => $username, 'password' => $password]);
        }
        $email = \Input::get('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Invalid email";
            exit;
        }

        $username = preg_replace('/[^a-zA-Z0-9\s]/', '-', $email);
        $username .= '-' . str_random(4);
        $password = str_random(10);

        $splunkAuth = \Config::get('splunk.admin.username').":".\Config::get('splunk.admin.password');
        $sshCommand = "ssh ".\Config::get('splunk.server.username')."@".\Config::get('splunk.server.host')
            ." -p ".\Config::get('splunk.server.port').' -o StrictHostKeyChecking=no';
        $passCommand = "sshpass -p '".\Config::get('splunk.server.password')."'";
        $splunkCommand = "sudo /usr/bin/splunk add user '$username' -password '$password' -role user -auth ".$splunkAuth;

        $command = "$passCommand $sshCommand \"$splunkCommand\"";

        $output = null;
        exec($command, $output);
        $output = implode($output);

        if (strpos($output, 'User added') === false) {
            return 'Error, could not create user. This email was probably already used. '.$output;
        } else {
            \Session::put('splunk.username', $username);
            \Session::put('splunk.password', $password);
            return \View::make('splunk', ['username' => $username, 'password' => $password]);
        }
    }
} 
