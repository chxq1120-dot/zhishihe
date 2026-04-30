<?php

namespace PosterMaker;
class PosterMaker
{
    public $bg; // 背景图
    protected $width;
    protected $height;

    /**
     * 构造函数
     * @param $w int 宽度(px)
     * @param $w int 高度(px)
     * @param $bg_color array RGB color value
     */
    public function __construct(int $w, int $h, $bg_color = [100, 150, 150])
    {
        $this->width = $w;
        $this->height = $h;
        $this->createBg($w, $h, $bg_color);
    }

    /**
     * 填充画布背景
     * @param $w int 宽度(px)
     * @param $w int 高度(px)
     * @param $bg_color array [R,G,B] color value
     */
    protected function createBg($w, $h, $bg_color)
    {
        $this->bg = imagecreatetruecolor($w, $h);
        $c = imagecolorallocate($this->bg, $bg_color[0], $bg_color[1], $bg_color[2]);
        imagefill($this->bg, 0, 0, $c);
    }

    /**
     * 备用
     * @param $src_img
     * @param array $size
     * @param int $radius
     * @return resource
     */
    protected function drawRadius($src_img, $size = [128, 128], $radius = 15)
    {
        $img = imagecreatetruecolor($size[0], $size[1]);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r = $radius; //圆 角半径
        for ($x = 0; $x < $size[0]; $x++) {
            for ($y = 0; $y < $size[1]; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (($x >= $radius && $x <= ($size[0] - $radius)) || ($y >= $radius && $y <= ($size[1] - $radius))) {
                    //不在四角的范围内,直接画
                    imagesetpixel($img, $x, $y, $rgbColor);
                } else {
                    //在四角的范围内选择画
                    //上左
                    $y_x = $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //上右
                    $y_x = $size[0] - $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下左
                    $y_x = $r; //圆心X坐标
                    $y_y = $size[1] - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下右
                    $y_x = $size[0] - $r; //圆心X坐标
                    $y_y = $size[1] - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
            }
        }
        return $img;
    }

    /**
     * 画圆角
     * @param $radius int 圆角位置
     * @param $color_r int 色值0-255
     * @param $color_g int 色值0-255
     * @param $color_b int 色值0-255
     * @return resource 返回圆角
     */
    protected function drawRounder($radius, $color_r, $color_g, $color_b)
    {

        $img = imagecreatetruecolor($radius, $radius);    // 图像的背景
        $bgcolor = imagecolorallocate($img, $color_r, $color_g, $color_b);
        $fgcolor = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $bgcolor);    // $radius,$radius：以图像的右下角开始画弧
        imagefilledarc($img, $radius, $radius, $radius * 2, $radius * 2, 180, 270, $fgcolor, IMG_ARC_PIE);    // 将弧角图片的颜色设置为透明
        imagecolortransparent($img, $fgcolor);
        return $img;
    }

    /**
     * 添加画布背景
     * @param $w int 宽度(px)
     * @param $w int 高度(px)
     * @param $xy array 坐标[x坐标，y坐标]
     * @param $bg_color array [R,G,B] color value
     */
    public function addBg($w, $h, $xy = [0, 0], $bg_color = [255, 255, 255], $radius = 0)
    {
        $bg = imagecreatetruecolor($w, $h);
        $c = imagecolorallocate($bg, $bg_color[0], $bg_color[1], $bg_color[2]);
        imagefill($bg, 0, 0, $c);
        if ($radius) {
            $bg = $this->drawRadius($bg, [$w, $h], $radius);
        }
        imagecopyresized($this->bg, $bg, $xy[0], $xy[1], 0, 0, $w, $h, $w, $h);
        imagedestroy($bg);
        return $this;
    }

    /**
     * 添加图片
     * @param $img_path string 图片路径
     * @param $xy array 坐标[x坐标，y坐标]
     * @param $size_wh array 尺寸[width, height]
     */
    public function addImg($img_path, $xy = [0, 0], $size_wh = [100, 100], $radius = 0)
    {
        list($img,$l_w,$l_h) = $this->createImageFromFile($img_path);
        if ($radius) {
            $img = $this->drawRadius($img, [$l_w, $l_h], $radius);
        }
        imagecopyresized($this->bg, $img, $xy[0], $xy[1], 0, 0, $size_wh[0], $size_wh[1], $l_w, $l_h);
        imagedestroy($img);
        return $this;
    }

    /**
     * 添加文字
     * @param $text string 文字
     * @param $size int 文字大小
     * @param $xy array 坐标[x坐标，y坐标]
     * @param $color array [R,G,B] color value
     * @param $font_file string 字体路径
     * @param $angle int 文字旋转角度
     */
    public function addText($text, $size = 14, $xy = [0, 0], $color = [0, 0, 0], $font_file = '', $angle = 0, $max_width = 0)
    {
        if ($font_file == '') $font_file =realpath(__DIR__.'/../../../../public/static/admin/fonts/msyh.ttc');
        $font_color = ImageColorAllocate($this->bg, $color[0], $color[1], $color[2]);
        $xL = $xy[0];
        if ($max_width) {
            $text = $this->autoWrap($size, 0, $font_file, $text, $max_width); // 自动换行处理
        } elseif ($xL == 0) {
            $fontBox = imagettfbbox($size, 0, $font_file, $text);
            $xL = ceil(($this->width - $fontBox[2]) / 2);
        }
        imagettftext($this->bg, $size, $angle, $xL, $xy[1], $font_color, $font_file, $text);
        return $this;
    }

    /**
     * 自动换行
     * @param $fontsize
     * @param $angle
     * @param $fontface
     * @param $string
     * @param $width
     * @return string
     */
    protected function autoWrap($fontsize, $angle, $fontface, $string, $width)
    {
        // 这几个变量分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度
        $content = "";
        // 将字符串拆分成一个个单字 保存到数组 letter 中
        for ($i = 0; $i < mb_strlen($string); $i++) {
            $letter[] = mb_substr($string, $i, 1);
        }
        foreach ($letter as $l) {
            $teststr = $content . " " . $l;
            $testbox = imagettfbbox($fontsize, $angle, $fontface, $teststr);
            // 判断拼接后的字符串是否超过预设的宽度
            if (($testbox[2] > $width) && ($content !== "")) {
                $content .= "\n";
            }
            $content .= $l;
        }
        return $content;
    }

    /**
     * 添加二维码
     * @param $text string 文字
     * @param $xy array 坐标[x坐标，y坐标]
     * @param $size_wh array 尺寸[width, height]
     */
    public function addQrCode($text, $xy = [0, 0], $size_wh = [100, 100])
    {
        require_once "QRcode.php";
        if (!is_readable('./tempqr')) mkdir('./tempqr', 0700);
        $tmp_name = './tempqr/' . uniqid() . '.png';
        \QRcode::png($text, $tmp_name, 0, 4);
        return $this->addImg($tmp_name, $xy, $size_wh);
    }

    /**
     * 输出图片
     * @param $file_name string 最后保存海报的路径，留空表示直接向浏览器输出图片
     * @param $is_put int  是否输出图片数据
     */
    public function render($file_name = '', $is_put = 0)
    {
        if ($is_put == 1) {
            ob_start();
            imagepng($this->bg);
            $image_data = ob_get_contents();
            ob_end_clean();
            return $image_data;
        } else {
            if ($file_name != '') {
                imagepng($this->bg, $file_name);
            } else {
                Header("Content-Type: image/png");
                imagepng($this->bg);
            }
            imagedestroy($this->bg);
        }
    }

    /**
     * 从图片文件创建Image资源
     * @param $file string 图片文件，支持url
     * @return bool|resource   成功返回图片image资源，失败返回false
     */
    public function createImageFromFile($file)
    {
        $fileSuffix = pathinfo($file, PATHINFO_EXTENSION);
        if(empty($fileSuffix)){
            $fileSuffix='none';
        }
        if (!$fileSuffix) return false;
        switch (strtolower($fileSuffix)) {
            case 'jpeg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'jpg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'png':
                if(stripos($file,'http')!==false){
                    $img=$this->getImgData($file);
                    $theImage = @imagecreatefromstring($img);
                }else{
                    $theImage = @imagecreatefrompng($file);
                }
                break;
            case 'gif':
                $theImage = @imagecreatefromgif($file);
                break;
            default:
                if(stripos($file,'http')!==false) {
                    $img = $this->getImgData($file);
                    $theImage = @imagecreatefromstring($img);
                }else{
                    $theImage = @imagecreatefromstring(file_get_contents($file));
                }
                break;
        }
        $width = imagesx($theImage);
        $height = imagesy($theImage);
        return [$theImage,$width,$height];
    }
    /**
     *获取头像图片数据
     * /
     **/
    protected function getImgData($url)
    {
        $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); //设置需要获取的URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//返回结果而不是输出
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
        if ($ssl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        }
        $imgdata=curl_exec($ch); // 执行curl会话
        curl_close($ch); // 关闭资源连接
        return $imgdata;
    }
}