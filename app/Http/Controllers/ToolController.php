<?php namespace App\Http\Controllers;

use App\Jobs\AngejiaDeploy;
use App\Jobs\AngejiaDockerDeploy;
use Input;
use Log;
use Response;
use Vinkla\GitLab\Facades\GitLab;

class ToolController extends Controller
{

    const ACTION_SYNC = 0;
    const ACTION_DELETE = 1;

    public function webHook()
    {
        if (Input::get('object_kind') == 'push') {
            $this->pushHook();
        } elseif (Input::get('object_kind') == 'merge_request') {
            $this->mergeRequestHook();
        }

        return Response::json([], 200);
    }

    public function pushHook()
    {
        Log::debug(json_encode(Input::all()));
        $ref = Input::get('ref');
        if (Input::get('after') != '0000000000000000000000000000000000000000') {
            $action = self::ACTION_SYNC;
        } else {
            $action = self::ACTION_DELETE;
        }

        $branch = $this->getBranch($ref);
        $env_names = $this->getEnv($branch, $action);

        if ($action == self::ACTION_DELETE) {
            $this->dispatch(new AngejiaDockerDeploy($branch, 'delete'));
        } else {
            $this->dispatch(new AngejiaDockerDeploy($branch));
        }

        $playbook = $this->getPlaybook($action);

        foreach ($env_names as $env_name) {
            $this->dispatch(new AngejiaDeploy($env_name, $playbook, ['angejia_version' => $branch]));
        }
    }

    private function mergeRequestHook()
    {
        $id = Input::get('object_attributes.id');
        $project_id = Input::get('object_attributes.target_project_id');
        $project_info = GitLab::api('merge_requests')->show($project_id, $id);


        if (in_array('reviewed', $project_info['labels'])) {
            $playbook = 'code-sync.yml';
        } else {
            $playbook = 'code-delete.yml';
        }

        $this->dispatch(new AngejiaDeploy('test', $playbook, ['angejia_version' => $project_info['source_branch']]));
    }

    public function getBranch($ref)
    {
        $branch = null;
        if (preg_match('/^refs\/heads\/(?<branch>.*)$/', $ref, $matches, PREG_OFFSET_CAPTURE)) {
            $branch = $matches['branch'][0];
        }
        return $branch;
    }

    public function getEnv($branch, $action)
    {
        if (starts_with($branch, 'feature-') || starts_with($branch, 'bug-')) {
            if ($action == self::ACTION_SYNC) {
                return ['dev'];
            } else {
                return ['dev', 'test'];
            }
        } elseif ($branch == 'master') {
            return ['dev', 'test', 'stage'];
        } elseif ($branch == 'job') {
            return ['dev', 'test'];
        }
    }

    /**
     * 在$env中删除$branch
     * @param $branch string 需要删除的分支
     * @param $env string 在哪个环境中删除
     * @return AngejiaDeploy 返回生成的任务
     */
    public function deleteCode($branch, $env)
    {
        $playbook = 'code-delete.yml';

        return new AngejiaDeploy($env, $playbook, ['angejia_version' => $branch]);
    }


    /**
     * 在$env中同步$branch的代码,若没有此分支则创建
     * @param $branch string 需要同步/创建的分支
     * @param $env string 在哪个环境中同步/创建
     * @return AngejiaDeploy 返回生成的任务
     */
    public function syncCode($branch, $env)
    {
        $playbook = 'code-sync.yml';

        return new AngejiaDeploy($env, $playbook, ['angejia_version' => $branch]);
    }

    private function getPlaybook($action)
    {
        if ($action == self::ACTION_DELETE) {
            return 'code-delete.yml';
        } elseif ($action == self::ACTION_SYNC) {
            return 'code-sync.yml';
        }
    }
}
