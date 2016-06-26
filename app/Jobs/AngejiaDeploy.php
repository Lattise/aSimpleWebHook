<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Log;
use Symfony\Component\Process\Process;

class AngejiaDeploy extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $cmd;

    /**
     * Create a new job instance.
     *
     * @param $env_name
     * @param $playbook
     * @param array $extra_vars
     */
    public function __construct($env_name, $playbook, $extra_vars = [])
    {
        $this->cmd = implode(' ',
            ['ansible-playbook', '-i', 'inventory.' . $env_name, $playbook]);
        foreach ($extra_vars as $key => $value) {
            $this->cmd .= " --extra-vars=$key=$value";
        }
        Log::debug('AngejiaDeploy is initialized with cmd: ' . $this->cmd);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::debug('AngejiaDeploy executing: ' . $this->cmd);
        $process = new Process($this->cmd, env('ANGEL_PATH'), ['HOME' => '/tmp']);
        $process->setTimeout(15 * 60);
        if ($process->run() != 0) {
            Log::error('output:');
            Log::error($process->getOutput());
            Log::error('error output:');
            Log::error($process->getErrorOutput());
            Log::error('AngejiaDeploy failed with exit code ' . $process->getExitCode());
        }
    }
}
