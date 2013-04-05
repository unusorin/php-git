<?php
/**
 * src/unusorin/Git/Repository.php
 * @author Sorin Badea <sorin.badea91@gmail.com>
 */
namespace unusorin\Git;

use unusorin\Git\Exceptions\GitException;

class Repository
{
    protected $localPath;
    protected $gitPath = '/usr/bin/git';

    /**
     * @param $localPath
     * @throws \InvalidArgumentException
     */
    public function __construct($localPath)
    {
        $this->checkGitInstallation();
        if (!is_string($localPath)) {
            throw new \InvalidArgumentException('$localPath must be a string');
        }

        if (is_dir($localPath) && !is_writable($localPath)) {
            throw new \InvalidArgumentException($localPath . ' must be writable');
        }
        $this->localPath = $localPath;
    }

    public function isInited()
    {
        $this->executeOSCommand('status', $exitStatus);
        if ($exitStatus == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function init()
    {
        if (!is_dir($this->localPath)) {
            mkdir($this->localPath);
        }
        if (!$this->isInited()) {
            $output = $this->executeOSCommand('init', $exitStatus);
            if ($exitStatus != 0) {
                throw new GitException($output, GitException::INIT);
            }
        }
    }

    public function fetch()
    {
        $output = $this->executeOSCommand('fetch', $exitStatus);
        if ($exitStatus != 0) {
            throw new GitException($output, GitException::FETCH);
        }
        echo $output;
    }

    public function pull()
    {
        $output = $this->executeOSCommand('pull', $exitStatus);
        if ($exitStatus != 0) {
            throw new GitException($output, GitException::PULL);
        }
        if (trim($output) == 'Already up-to-date.') {
            return GitStatus::PULL_UP_TO_DATE;
        } else {
            return GitStatus::UNKNOWN;
        }
    }

    public function push()
    {
        $output = $this->executeOSCommand('pull', $exitStatus);
        if ($exitStatus != 0) {
            throw new GitException($output, GitException::PULL);
        }
    }

    public function getAllRemotes()
    {
        $output = $this->executeOSCommand('remote -v', $exitStatus);
        if ($exitStatus != 0) {
            throw new GitException($output, GitException::REMOTES_LIST);
        }
        $remotes = array();
        array_map(
            function ($remote) use (&$remotes) {
                //TODO: optimize this
                $parts = explode("\t", $remote);
                if (count($parts) == 2) {
                    $actions = explode(" ", $parts[1]);
                    if (count($actions) == 2) {
                        if (!isset($remotes[$parts[0]])) {
                            $remotes[$parts[0]]          = new \stdClass();
                            $remotes[$parts[0]]->url     = $actions[0];
                            $remotes[$parts[0]]->actions = array();
                        }
                        $remotes[$parts[0]]->actions[] = str_replace(array("(", ")"), "", $actions[1]);
                    }

                }
            },
            explode("\n", trim($output))
        );
        return $remotes;
    }

    public function getAllBranches()
    {
        $output = $this->executeOSCommand('branch --no-color -a', $exitStatus);
        if ($exitStatus != 0) {
            throw new GitException($output, GitException::BRANCH_LIST);
        }

        $branches = array_map(
            function ($branchName) {
                $branchName = trim($branchName);
                if (strlen($branchName) > 0) {
                    $branch = new \stdClass();
                    if ($branchName[0] == '*') {
                        $branchName      = trim(str_replace("*", "", $branchName));
                        $branch->current = true;
                    } else {
                        $branch->current = false;
                    }
                    $branch->name = $branchName;
                    return $branch;
                }

            },
            explode("\n", trim($output))
        );
        return $branches;
    }

    public function getAllCommits()
    {
        $output = $this->executeOSCommand("log --pretty=format:\"" . $this->getLogOutputFormat() . "\"", $exitStatus);
        if ($exitStatus != 0) {
            throw new GitException($output, GitException::LOG_LIST);
        }
        $output  = str_replace(",]", "]", "[" . $output . ']');
        $commits = array_map(
            function ($commit) {
                $commit->parentHashes = explode(" ", $commit->parentHashes);
                if (empty($commit->parentHashes[0])) {
                    $commit->parentHashes = array();
                }
                $commit->shortParentHashes = explode(" ", $commit->shortParentHashes);
                if (empty($commit->shortParentHashes [0])) {
                    $commit->shortParentHashes = array();
                }
                return $commit;
            },
            json_decode($output)
        );
        print_r($commits);
    }

    protected function checkGitInstallation()
    {
        $response = shell_exec('which ' . $this->gitPath);
        if (empty($response)) {
            throw new GitException('Git not found ', GitException::INSTALLATION_NOT_FOUND);
        }
    }

    protected function executeOSCommand($command, &$exitStatus)
    {
        $command = $this->gitPath . ' ' . $command;
        /*
         * from https://github.com/kbjr/Git.php
         */
        $descriptorSpec = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $pipes          = array();
        $resource       = proc_open($command, $descriptorSpec, $pipes, $this->localPath);

        $output      = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $exitStatus = trim(proc_close($resource));
        if ($exitStatus) {
            return $errorOutput;
        }

        return $output;
    }

    protected function getLogOutputFormat()
    {
        return '{
            \"hash\":\"%H\",
            \"shortHash\":\"%h\",
            \"treeHash\":\"%T\",
            \"shortTreeHash\":\"%t\",
            \"parentHashes\":\"%P\",
            \"shortParentHashes\":\"%p\",
            \"authorName\":\"%an\",
            \"authorEmail\":\"%ae\",
            \"authorDate\":\"%ad\",
            \"commiterName\":\"%cn\",
            \"commiterEmail\":\"%ce\",
            \"commiterDate\":\"%cd\",
            \"subject\":\"%s\"
        },';
    }
}