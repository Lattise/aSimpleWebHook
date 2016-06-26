<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Log;
use Symfony\Component\Process\Process;

class AngejiaDockerDeploy extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $cmd;

    /**
     * Create a new job instance.
     */
    public function __construct($angejia_version, $action = '')
    {
        $this->cmd .= "ansible-playbook angejia.yml -e angejia_version=$angejia_version";
        if ($action !== '') {
            $this->cmd .= " -e action=$action";
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::debug('AngejiaDockerDeploy executing: ' . $this->cmd);
        $process = new Process($this->cmd, env('DOCKER_D_PATH'), ['HOME' => '/tmp']);
        $process->setTimeout(15 * 60);
        if ($process->run() != 0) {
            Log::error('output:');
            Log::error($process->getOutput());
            Log::error('error output:');
            Log::error($process->getErrorOutput());
            Log::error('AngejiaDockerDeploy failed with exit code ' . $process->getExitCode());
        } else {
            Log::debug('AngejiaDockerDeploy ' . $this->cmd . ' success!');
        }
    }
}
