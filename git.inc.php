<?php
/*
  @make sure installed gitolite
  @useradd -d /home/nginx nginx
  @ssh-keygen -t rsa -b 4096 
  @user add -d /home/git git
  @mv /home/nginx/.ssh/id_rsa.pub /home/git/admin.pub
  @cd /home/git/ && gl-setup admin.pub
  @git clone git@localhost:gitolite-admin /home/nginx/admin
  @
*/
define('GIT_PATH', '/home/nginx');
function git_username($username)
{
    return str_replace('@', '.', $username);
}
function git_url($username, $repo)
{
    return "git@git.yunall.net:".git_username($username)."/$repo.git";
}
function git_ready()
{
    $dir = GIT_PATH."/".getmypid();
    if(!file_exists($dir))
    {
        $cmd = "mkdir -p $dir > /dev/null "
            ." && git clone git@localhost:gitolite-admin.git $dir > /dev/null ";
    }
    else
    {
        $cmd = "pushd $dir > /dev/null && git pull > /dev/null";
    }
    @system($cmd);
    return $dir;
}

function git_cmd($dir, $cmd)
{
    $cmdline = "pushd $dir > /dev/null && $cmd";
    @system($cmdline, $ret);
    //error_log("$cmdline\n", 3, "/tmp/cmd.log");
}

function git_useradd($username)
{
    if(($username = git_username($username)) && ($dir = git_ready()))
    {
        $ret = "";
        $conf = "repo\t$username/demo\n\tC\t=\tadmin\n\tRW+\t=\t$username\n";
        file_put_contents($dir."/conf/users/$username.conf", $conf);
        $cmd = "git add 'conf/users/$username.conf' > /dev/null "
            ." && git commit -a -m 'added $username.conf' > /dev/null "
            . " && git push > /dev/null";
        git_cmd($dir, $cmd);
        return true;
    }
    return false;
}

function git_repos_rebuild($username, $repos)
{
    if(($username = git_username($username)) && count($repos) > 0 && ($dir = git_ready()))
    {
        $conf = "";
        foreach($repos as $k => $repo)
        {
            $conf .= "repo\t$username/$repo\n\tC\t=\tadmin\n\tRW+\t=\t$username\n";
        }
        file_put_contents($dir."/conf/users/$username.conf", $conf);
        $cmd = "git commit -a -m 'updated $username.conf' > /dev/null"
            ."&& git push > /dev/null";
        git_cmd($dir, $cmd);
        return true;
    }
    return false;
}

function git_key_rebuild($username, $keys)
{
    if(($username = git_username($username)) && ($dir = git_ready()))
    {
        $list = "";
        $ret = "";
        $cmd = "git rm -f --ignore-unmatch 'keydir/$username@*' > /dev/null";
        git_cmd($dir, $cmd);
        if(is_array($keys) && count($keys) > 0)
        {
            foreach($keys as $k => $key)
            {
                $filename = "keydir/".$username."@$k.pub";
                file_put_contents($dir."/$filename", $key->pubkey."\n");
                $list .= " '$filename'";
            }
            $cmd = "git add $list > /dev/null";
            git_cmd($dir, $cmd);
        }
        $cmd = "git commit -a -m 'updated pubkeys of $username' > /dev/null "
                . "&& git push > /dev/null";
        git_cmd($dir, $cmd);
        return true;
    }
    return false;
}
?>
