<?php

class PushTest extends TestCase
{
    /**
     * 创建/更新分支时提交的数据
     * @var array
     */
    private $post_data_sync;
    /**
     * 删除分支时提交的数据
     * @var array
     */
    private $post_data_delete;
    /**
     * MR提交的数据
     * @var array
     */
    private $post_data_reviewed;

    /**
     * 测试在分支上提交
     *
     * @return void
     */
    public function testSyncBranch()
    {
        $this->makeRequest('POST', '/angejia/webhook', $this->post_data_sync)
            ->assertResponseOk();
    }

    /**
     * 测试为MR添加reviewed标签
     *
     * @return void
     */
    public function testMRReviewd()
    {
        $this->makeRequest('POST', '/angejia/webhook', $this->post_data_reviewed)
            ->assertResponseOk();
    }


    /**
     * PushTest constructor.
     * 初始化测试使用的常量
     */
    public function __construct()
    {
        $this->post_data_sync = [
            'object_kind' => 'push',
            'before' => '0000000000000000000000000000000000000000',
            'after' => '54756a8f34ca2a9b20512c286bea8445451248ce',
            'ref' => 'refs/heads/feature-test',
            'project_id' => 15,
        ];
        $this->post_data_delete = [
            [
                "object_kind" => "push",
                "before" => "54756a8f34ca2a9b20512c286bea8445451248ce",
                "after" => "0000000000000000000000000000000000000000",
                "ref" => "refs/heads/feature-test",
                "project_id" => 15,
            ]
        ];
        $this->post_data_reviewed = [
            "object_kind" => "merge_request",
            "object_attributes" => [
                "id" => 8139,
                "iid" => 5578,
                "target_project_id" => 15,
                "source_branch" => "bug-inventory-followup",
            ]
        ];
    }

}
