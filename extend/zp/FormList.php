<?php
/**
 * Created by 智派科技(ZHIPALL.COM)
 * User: workrd 304609001@qq.com
 * Date: 2019/8/8
 * Time: 16:53
 */

namespace zp;
class FormList
{
    public $data = array();

    public function __construct($data = array())
    {
        $this->data = $data;
    }

    public function text($info)
    {
        $value = !isset($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $parseStr = '<div class="layui-input-inline from-inline-4">';
        $parseStr .= '<input type="text" lay-verType="msg" lay-reqText="请输入' . $info['title'] . '" title="' . $info['title'] . '" placeholder="请输入' . $info['title'] . '" class="layui-input" name="' . $info['name'] . '" value="' . $value . '" /> ';
        $parseStr .= '<div class="layui-word-aux" style="color:#b9b9b9!important;padding:5px 0;">' . $info['remark'] . '</div>';
        $parseStr .= '</div>';
        return $parseStr;
    }

    public function color($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $parseStr = '<div class="layui-input-inline" style="width: 145px;"><input type="text" lay-verType="msg" lay-reqText="请选择' . $info['title'] . '"  lay-verify="required" class="layui-input" id="' . $info['name'] . '" name="' . $info['name'] . '" value="' . $value . '" /></div>';
        $parseStr .= '<div class="layui-inline"><div id="sel' . $info['name'] . '" class="layui-inline"></div></div>';
        $parseStr .= "<script>            
                    layui.use('colorpicker', function(){
                    var colorpicker = layui.colorpicker,$=layui.jquery;
                    colorpicker.render({
                        elem: '#sel" . $info['name'] . "',
                        color:'" . $value . "',
                        done: function(color){
                          $('#" . $info['name'] . "').val(color);
                          //譬如你可以在回调中把得到的 color 赋值给表单
                        }
                    });
                });
                </script>";
        return $parseStr;
    }

    public function textarea($info)
    {
        $value = !isset($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $parseStr = '<div class="layui-input-inline from-inline-5">';
        $parseStr .= '<textarea lay-reqText="请输入' . $info['title'] . '" placeholder="请输入' . $info['title'] . '" lay-verify="required"  class="layui-textarea" name="' . $info['name'] . '" />' . $value . '</textarea>';
        $parseStr .= '<div class="layui-word-aux" style="color:#9c9c9c!important;padding:10px 0;">' . $info['remark'] . '</div>';
        $parseStr .= '</div>';
        return $parseStr;
    }

    public function ueditor($info)
    {
        $value = !isset($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $str = '<div class="layui-input-inline from-inline-5">';
        $str .= '<textarea name="' . $info['name'] . '" class="js-ueditor" id="ueditor_'.$info['name'].'">' . $value . '</textarea>';
        $str .= '<script>var editor = new UE.ui.Editor({serverUrl:\'' . url("ueditor/index") . '\'});editor.render("ueditor_'.$info['name'].'");</script>';
        $str .= '<div class="layui-form-mid layui-word-aux" style="color:#9c9c9c!important;padding:10px 0;">' . $info['remark'] . '</div>';
        $str .= '</div>';
        return $str;
    }

    public function datetime($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $value = $value ? toDate($value, "Y-m-d H:i:s") : toDate(time(), "Y-m-d H:i:s");
        $parseStr = '<div class="layui-input-inline from-inline-3">';
        $parseStr .= '<input type="datetime" name="' . $info['name'] . '" lay-verify="required" placeholder="请选择' . $info['title'] . '" value="' . $value . '" class="layui-input" id="ctime">';
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux" style="color:#a9a9a9!important;">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function number($info)
    {
        $value = !isset($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $parseStr = '<div class="layui-input-inline">';
        $parseStr .= '<input type="text" class="input-text layui-input" lay-reqText="请输入' . $info['title'] . '" name="' . $info['name'] . '" placeholder="请输入' . $info['title'] . '" value="' . $value . '"/> ';
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux" style="color:#a9a9a9!important;">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function select($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $options = explode("\n", $info['values']);
        foreach ($options as $row) {
            $v = explode("|", $row);
            $k = trim($v[1]);
            $values[$k] = $v[0];
        }
        $parseStr = '<div class="layui-input-inline from-inline-2">';
        $parseStr .= '<select name="' . $info['name'] . '">';
        if (is_array($values)) {
            foreach ($values as $key => $val) {
                if (!empty($value)) {
                    $selected = '';
                    if (is_array($value)) {
                        if (in_array($key, $value)) {
                            $selected = ' selected="selected"';
                        }
                    } else {
                        if ($value == $key) {
                            $selected = ' selected="selected"';
                        }
                    }
                    $parseStr .= '<option ' . $selected . ' value="' . $key . '">' . $val . '</option>';
                } else {
                    $parseStr .= '<option value="' . $key . '">' . $val . '</option>';
                }
            }
        }
        $parseStr .= '</select>';
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux" style="color:#a9a9a9!important;">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function checkbox($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $options = explode("\n", $info['values']);
        foreach ($options as $r) {
            $v = explode("|", $r);
            $k = trim($v[1]);
            $values[$k] = $v[0];
        }
        if ($value != '') $value = strpos($value, ',') ? explode(',', $value) : array($value);
        $i = 1;
        $parseStr = '<div class="layui-input-inline from-inline-5">';
        foreach ($values as $key => $r) {
            $key = trim($key);
            $checked = ($value && in_array($key, $value)) ? 'checked' : '';
            $parseStr .= '<input name="' . $info['name'] . '" ' . $checked . ' value="' . htmlspecialchars($key) . '" type="checkbox" title="' . htmlspecialchars($r) . '">';
            $i++;
        }
        $parseStr .= '</div>';
        $parseStr .= '<div class="layui-form-mid layui-word-aux" style="color:#a9a9a9!important;">' . $info['remark'] . '</div>';
        return $parseStr;
    }

    public function switchs($info)
    {
        $value = empty($this->data[$info['name']]) ? 0 : $this->data[$info['name']];
        $checked = '';
        if ($value == 1) {
            $checked = 'checked';
        }
        $parseStr = '<div class="layui-input-inline from-inline-7">';
        $parseStr .= '<input name="switch_1" ' . $checked . ' value="'.$value.'" lay-filter="' . $info['name'] . '" lay-skin="switch" type="checkbox" lay-text="' . $info['values'] . '"/>';
        $parseStr .= '<div class="layui-word-aux" style="padding:10px 0 0 0!important;color:#a9a9a9!important;">' . $info['remark'] . '</div>';
        $parseStr .= '</div>';
        $parseStr .= '<input type="hidden" id="' . $info['name'] . '" name="' . $info['name'] . '" value="'.$value.'" />';
        $parseStr .= "<script>
                layui.use(['form','jquery'], function (){
                    var form = layui.form,$=layui.jquery;
                    form.on('switch(".$info['name'].")', function(data){
                        if(data.elem.checked){
                            $('#". $info['name']."').val('1');
                        }else{
                            $('#". $info['name']."').val('0');
                        }
                    });
                });
                </script>";
        return $parseStr;
    }

    public function radio($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $parseStr = '<div class="layui-input-inline from-inline-7">';
        $parseStr .='<div class="layui-tab">';
        $options = explode("\n", $info['values']);
        foreach ($options as $r) {
            $v = explode("|", $r);
            $k = trim($v[1]);
            $values[$k] = $v[0];
        }
        $i = 1;
        foreach ($values as $key => $r) {
            $checked = trim($value) == trim($key) ? 'checked' : '';
            if (empty($value) && empty($key)) {
                $checked = 'checked';
            }
            $parseStr .= '<input name="' . $info['name'] . '" ' . $checked . '  lay-filter="' . $info['name'] . '" value="' . $key . '" type="radio" title="' . $r . '" />';
            $i++;
        }
        $parseStr .= '</div><div class="layui-word-aux" style="padding:10px 0 0 0!important;color:#a9a9a9!important;">' . $info['remark'] . '</div>';
        $parseStr .= '</div>';
        return $parseStr;
    }

    public function image($info)
    {
        $value = empty($this->data[$info['name']]) ? $info['default'] : $this->data[$info['name']];
        $thumbstr = '<div class="layui-input-inline" style="width: inherit;position: relative;">';
        $thumbstr .= '<input type="hidden" name="' . $info['name'] . '" id="' . $info['name'] . 'Val" value="' . $value . '">';

        $thumbstr .= '<div class="layui-upload">';
        if(empty($value)){
            $onClass="onshow";
            $unClass="unshow";
        }else{
            $onClass="unshow";
            $unClass="onshow";
        }
        $thumbstr .= '<div class="layui-upload-image '.$onClass.'" id="on' . $info['name'] . '">';
        $thumbstr .= '<img class="layui-upload-img" style="max-height:80px;" src="/static/admin/images/default.png">';
        $thumbstr .= '</div>';
        $thumbstr .= '<div class="layui-upload-image '.$unClass.'" id="remove' . $info['name'] . '">';
        $thumbstr .= '<div class="layui-upload-imgbar"><img class="layui-upload-img" style="max-height:80px;" id="' . $info['name'] . 'Img" src="' . $value . '"></div>';
        $thumbstr .= '<div class="upload-ctl-cover" id="upload-' . $info['name'] . '-btn">';
        $thumbstr .= '<div class="upload-ctl-btn">';
        $thumbstr .= '<span class="btn-view" id="btn-' . $info['name'] . '-view"><i class="icon icon-view"></i></span>';
        $thumbstr .= '<span class="btn-delete" id="btn-' . $info['name'] . '-delete"><i class="icon icon-delete"></i></span>';
        $thumbstr .= '</div>';
        $thumbstr .= '</div>';
        $thumbstr .= '</div>';
        $thumbstr .= '<div class="layui-word-aux" style="padding:10px 0 0 0!important;color:#a9a9a9!important;">' . $info['remark'] . '</div>';
        $thumbstr .= '</div>';
        $thumbstr .= "<script>
                       $('#remove" . $info['name'] . "').hover(function(){
                            $('#upload-" . $info['name'] . "-btn').show();
                        },function(){
                            $('#upload-" . $info['name'] . "-btn').hide();
                        });
                       $('#on" . $info['name'] . "').on('click',function(){
                            layer.open({
                                title:'上传图片',
                                type:2,
                                area:['80%','540px'],
                                content:'".createUrl('attachment/index')."?type=0&name=" . $info['name'] . "Val&prename=" . $info['name'] . "Img',
                                end:function(){
                                    $('#on" . $info['name'] . "').hide();
                                    $('#remove" . $info['name'] . "').show();
                                }
                            });
                        });
                        $('#btn-" . $info['name'] . "-view').on('click',function(){
                            layer.open({
                                title:'查看图片',
                                type:1,
                                maxWidth:350,
                                scrollbar:false,
                                maxHeight:390,
                                content:'<img src=\'".$value."\' style=\'display:block;margin:0 auto;max-width:100%;text-align:center;padding:10px;\'>',
                            });
                        });
                        $('#btn-" . $info['name'] . "-delete').on('click',function(){
                            $('#on" . $info['name'] . "').show();
                            $('#remove" . $info['name'] . "').hide();
                        });
                    </script>";
        $thumbstr .= '</div>';
        return $thumbstr;
    }
    public function file($info)
    {
        $value = empty($this->data[$info['name']]) ? '/static/common/images/file.png' : $this->data[$info['name']];
        $thumbstr = '<div class="layui-input-inline">';
        $thumbstr .= '<div class="layui-input-4"><input type="hidden" name="' . $info['name'] . '" id="' . $info['name'] . 'fval" value="' . $value . '">';
        $thumbstr .= '<div class="layui-upload">';
        $thumbstr .= '<button type="button" class="layui-btn layui-btn-primary" id="on' . $info['name'] . '"><i class="layui-icon layui-icon-upload-drag"></i>点击上传</button>';
        $thumbstr .= '<div class="layui-upload-list"><img class="layui-upload-img" id="' . $info['name'] . 'File" width="90" height="90" src="/static/common/images/file.png"><p id="thumbText"></p></div>';
        $thumbstr .= '</div></div>';
        $thumbstr .= "<script> 
                         $('#on" . $info['name'] . "').on('click',function(){
                            layer.open({
                                title:'上传文件',
                                type:2,
                                area:['80%','540px'],
                                content:'".createUrl('attachment/index')."?type=0&name=" . $info['name'] . "fval&prename=" . $info['name'] . "Img',
                            });
                        });
                    </script>";
        $thumbstr .= '</div>';
        $thumbstr .= '<div class="layui-form-mid layui-word-aux" style="color:#a9a9a9!important;">' . $info['remark'] . '</div>';
        return $thumbstr;
    }
}

?>