<?php

namespace app\manage\controller;

use app\common\model\ResourceSort;
use GuzzleHttp\Client;
use app\common\model\ResourceType;
use app\common\model\Resource;
use GuzzleHttp\Exception\RequestException;
use zp\Tree;
use app\common\model\VideoCourse;
use app\common\model\AudioCourse;
use app\common\model\ResourceInfo;

/**
 * 主资源库
 */
class Library extends Common
{
    /**
     * 获取主资源库地址
     * @return [type] [description]
     */
    public function getApiUrl()
    {
        if (empty(config('setting.master_url'))) {
            return $this->request->domain();
        } else {
            return config('setting.master_url');
        }
    }

    /**
     * 主页
     * @return [type] [description]
     */
    public function index()
    {
        if (request()->isAjax()) {
            $data = input('param.');
            $client = new Client();
            $response = $client->request('POST',
                $this->getApiUrl() . '/api/Library/getlist',
                [
                    'headers'=>[
                        'token'=>config('setting.master_token'),
                    ],
                    'form_params' => $data,
                    'verify' => false,
                    'timeout' => 30,
                ]
            );
            $code = $response->getStatusCode();
            if ($code !== 200) {
                return json(['code' => 400, 'msg' => '请稍后再试']);
            }
            $info = $response->getBody()->getContents();
            $info = json_decode($info, true);
            if ($info['code'] !== 200) {
                return json(['code' => 400, 'msg' => $info['msg']]);
            }
            $lib_ids=array_column($info['data'], 'id');
            $sync_ids = Resource::whereIn('sync_id',$lib_ids)->column('sync_id');
            foreach ($info['data'] as $k => $v) {
                $info['data'][$k]['is_sync'] = 0;
                if (in_array($v['id'], $sync_ids)) {
                    $info['data'][$k]['is_sync'] = 1;
                }
            }
            return json(['code' => 0, 'data' => $info['data'], 'count' => $info['total']]);
        }
        $client = new Client();
        $response = $client->request('POST',
            $this->getApiUrl() . '/api/Library/getSort',
            [
                'headers'=>[
                    'token'=>config('setting.master_token'),
                ],
                'form_params' => [],
                'verify' => false,
                'timeout' => 30,
            ]
        );
        $code = $response->getStatusCode();
        $sortList = [];
        $smallList = [];
        if ($code == 200) {
            $info = $response->getBody()->getContents();
            $info = json_decode($info, true);
            if ($info['code'] == 200) {
                $tree = new Tree();
                $tree->init($info['data'], 'pid');
                $sortList = $tree->getTreeArray(0);
                $sid = $this->request->param('sid/d', 0);
                if ($sid) {
                    $smallList = $tree->getTreeArray($sid);
                }
            }
        }
        $this->assign('sortList', $sortList);
        $this->assign('smallList', $smallList);
        return view();

    }

    /**
     * 获取资源分类
     */
    public function sorts()
    {
        $data = input('param.');
        $client = new Client();
        $response = $client->request('POST',
            $this->getApiUrl() . '/api/Library/getSort',
            [
                'headers'=>[
                    'token'=>config('setting.master_token'),
                ],
                'form_params' => $data,
                'verify' => false,
                'timeout' => 30,
            ]
        );
        $code = $response->getStatusCode();
        if ($code !== 200) {
            return json(['code' => 400, 'msg' => '请稍后再试']);
        }
        $info = $response->getBody()->getContents();
        $info = json_decode($info, true);
        if ($info['code'] !== 200) {
            return json(['code' => 400, 'msg' => $info['msg']]);
        }
        $tree = new Tree();
        $tree->init($info['data'], 'pid');
        $datas = $tree->getTreeArray('pid');
        return json(['code' => 0, 'data' => $datas]);
    }

    /**
     * 详情
     * @return [type] [description]
     */
    public function info()
    {
        $data = input('param.');
        $client = new Client();
        $response = $client->request('POST',
            $this->getApiUrl() . '/api/Library/getInfo',
            [
                'headers'=>[
                    'token'=>config('setting.master_token'),
                ],
                'form_params' => $data,
                'verify' => false,
                'timeout' => 30,
            ]
        );
        $code = $response->getStatusCode();
        if ($code !== 200) {
            return json(['code' => 400, 'msg' => '请稍后再试']);
        }
        $info = $response->getBody()->getContents();
        $info = json_decode($info, true);
        if ($info['code'] !== 200) {
            return json(['code' => 400, 'msg' => $info['msg']]);
        }
        return view('', ['info' => $info['data']]);
    }

    /**
     * 批量同步资源
     */
    public function synsAll()
    {
        if (request()->isAjax()) {
            $data = input('param.');
            $client = new Client();
            $response = $client->request('POST',
                $this->getApiUrl() . '/api/Library/getSelect',
                [
                    'headers'=>[
                        'token'=>config('setting.master_token'),
                    ],
                    'form_params' => $data,
                    'verify' => false,
                    'timeout' => 30,
                ]
            );
            $code = $response->getStatusCode();
            if ($code !== 200) {
                return json(['code' => 400, 'msg' => '请稍后再试']);
            }
            $info = $response->getBody()->getContents();
            $info = json_decode($info, true);
            if ($info['code'] !== 200) {
                return json(['code' => 400, 'msg' => $info['msg']]);
            }
            foreach ($info['data'] as $value) {
                $isRes = Resource::where('sync_id', $value['id'])->find();
                if (!$isRes) {
                    if ($value['type'] == 4 || $value['type'] == 5) {
                        $value['course'] = [];
                        $response = $client->request('POST',
                            $this->getApiUrl() . '/api/Library/getCourse', [
                                'headers'=>[
                                    'token'=>config('setting.master_token'),
                                ],
                                'form_params' => ['id' => $value['id'], 'type' => $value['type']],
                                'verify' => false,
                                'timeout' => 30,
                            ]
                        );
                        $code = $response->getStatusCode();
                        if ($code == 200) {
                            $courses = $response->getBody()->getContents();
                            $courses = json_decode($courses, true);
                            if ($courses['code'] === 200) {
                                $value['course'] = $courses['data'];
                            }
                        }
                    }
                    $value['sync_id'] = $value['id'];
                    $value['admin_id'] = $this->admin_uid;
                    unset($value['ctime']);
                    unset($value['id']);
                    unset($value['uid']);
                    $r = Resource::create($value, true);
                    //处理资源详情
                    ResourceInfo::create([
                        'rid'=>$r->id,
                        'free_content'=>$value['free_content'],
                        'content'=>$value['content']
                    ],true);
                     //处理分类
                    foreach ($value['sort'] as $val) {
                        $sort = ResourceSort::where('name', $val['name'])->find();
                        if ($sort) {
                            $type = [
                                'rid' => $r->id,
                                'sid' => $sort->id
                            ];
                        } else {
                            unset($val['id']);
                            #处理二级分类
                            if ($val['pid'] > 0) {
                                $response = $client->request('POST',
                                    $this->getApiUrl() . '/api/Library/getFind',
                                    [
                                        'headers'=>[
                                            'token'=>config('setting.master_token'),
                                        ],
                                        'form_params' => ['pid' => $val['pid']],
                                        'verify' => false,
                                        'timeout' => 30,
                                    ]
                                );
                                $code = $response->getStatusCode();
                                if ($code !== 200) {
                                    return json(['code' => 400, 'msg' => '请稍后再试']);
                                }
                                $m_info = $response->getBody()->getContents();
                                $m_info = json_decode($m_info, true);
                                if ($m_info['code'] !== 200) {
                                    return json(['code' => 400, 'msg' => $m_info['msg']]);
                                }
                                #查询一级分类数据
                                $m_sort = $m_info['data'];
                                if (!empty($m_sort)) {
                                    #查询一级分类是否已经同步过
                                    $p_issort = ResourceSort::where('name', $m_sort['name'])->find();
                                    if ($p_issort) {
                                        $val['pid'] = $p_issort['id'];
                                    } else {
                                        unset($m_sort['id']);
                                        $m_sort['ctime'] = time();
                                        $p_sort = ResourceSort::create($m_sort);
                                        $val['pid'] = $p_sort['id'];
                                    }
                                }
                            }
                            $s = ResourceSort::create($val, true);
                            $type = [
                                'rid' => $r->id,
                                'sid' => $s->id,
                            ];
                        }
                        ResourceType::create($type);
                    }
                    //处理课程
                    if ($value['type'] == 4 || $value['type'] == 5) {
                        if ($value['type'] == 4) {
                            $db = new VideoCourse;
                        }
                        if ($value['type'] == 5) {
                            $db = new AudioCourse;
                        }
                        foreach ($value['course'] as $val) {
                            $course = [
                                'title' => $val['title'],
                                'thumb' => $val['thumb'],
                                'url' => $val['url'],
                                'desc' => $val['desc'],
                                'rid' => $r->id,
                                'times' => $val['times'],
                                'is_try' => $val['is_try'],
                                'indexid' => $val['indexid']
                            ];
                            $db->create($course,true);
                        }
                    }
                }
            }
            return callback(200, '同步操作成功');
        }
    }
}