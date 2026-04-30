var WCCE = {
    player: null,
    countDown: {
        disabled: false,
        btn_text: '获取验证码',
        time: 120,
        timer: 0
    },
    qrcodeTime: {
        time: 120,
        timer: 0
    },
    //手机端跳转
    mobileRedirect: function (url) {
        var device = navigator.userAgent.toLowerCase();
        if (/ipad|iphone|midp|rv:1.2.3.4|ucweb|android|windows ce|windows mobile/.test(device)) {
            window.location.href = url;
        }
    },
    //收藏操作
    handleFav: function (elem) {
        var token = $('#mytoken').val();
        if (!token) {
            layer.msg('请登录后操作', {time: 1500}, function () {
                window.location.href = '/login?fromurl=' + encodeURIComponent(window.location.href);
            });
            return false;
        }
        var rid = $('#rid').val();
        if (!rid) {
            layer.msg('操作失败，参数丢失', {time: 1500});
            return false;
        }
        $.post('/api/user/coll', {token: token, rid: rid}, function (res) {
            if (res.code == 200) {
                if (res.data.coll == 1) {
                    $(elem).children('i').removeClass('icon-shoucang11').addClass('icon-shoucang1');
                } else {
                    $(elem).children('i').removeClass('icon-shoucang1').addClass('icon-shoucang11');
                }
            } else if (res.code == 403) {
                window.location.href = '/login?fromurl=' + encodeURIComponent(window.location.href);
            } else {
                layer.msg(res.msg, {time: 1500});
                return false;
            }
        }, 'json');
    },
    //弹出提示
    handleTips: function (msg) {
        layer.msg(msg, {time: 1500});
        return false;
    },
    //弹出免费任务
    handleTask: function () {
        var token = $('#mytoken').val();
        if (!token) {
            layer.msg('请登录后操作', {time: 1500}, function () {
                window.location.href = '/login?fromurl=' + encodeURIComponent(window.location.href);
            });
            return false;
        }
        var rid = $('#rid').val();
        layer.open({
            title: '完成任务',
            type: 2,
            area: ['400px', '300px'],
            content: '/course/task?id=' + rid
        });
    },
    //下单购买页面
    handleBuy: function () {
        var rid = $('#rid').val();
        var token = $('#mytoken').val();
        if (!token) {
            layer.msg('请登录后操作', {time: 1500}, function () {
                window.location.href = '/login?fromurl=' + encodeURIComponent(window.location.href);
            });
            return false;
        }
        window.location.href = '/paycenter/order?id=' + rid;
    },
    //支付页面
    handlePay: function () {
        var token = $('#mytoken').val();
        if (!token) {
            layer.msg('请登录后操作', {time: 1500}, function () {
                window.location.href = '/login?fromurl=' + encodeURIComponent(window.location.href);
            });
            return false;
        }
        var type = $('#type').val();
        if (!type) {
            layer.msg('请选择支付套餐', {time: 1500});
            return false;
        }
        var rid = $('#rid').val();
        if (!rid) {
            layer.msg('请选择支付套餐', {time: 1500});
            return false;
        }
        var money = parseFloat($('#money').val());
        if (money<=0) {
            layer.msg('请选择支付套餐', {time: 1500});
            return false;
        }
        var paytype = $('#paytype').val();
        if (paytype==0) {
            layer.msg('请选择支付方式', {time: 1500});
            return false;
        }
        var cardno = $('#cardno').val();
        if (paytype == 3 && !cardno) {
            layer.msg('请输入卡密', {time: 1500});
            return false;
        }
        var loading = layer.load(1, {shade: [0.1, '#fff']});
        $.post('/paycenter/order', {
            type: type,
            rid: rid,
            money: money,
            paytype: paytype,
            cardno: cardno
        }, function (res) {
            layer.close(loading);
            if (res.code == 1) {
                if (paytype == 1) {
                    window.location.href = res.data.pay_url;
                } else if (paytype == 2) {
                    window.location.href = '/paycenter/wechat?ordno=' + res.data.ordno + '&code=' + encodeURIComponent(res.data.pay_url);
                } else {
                    window.location.href = '/paycenter/result?out_trade_no=' + res.data.ordno;
                }
            } else if (res.code == 403) {
                window.location.href = '/login?fromurl=' + encodeURIComponent(window.location.href);
            } else {
                layer.msg(res.msg, {time: 1500});
                return false;
            }
        }, 'json');
    },
    //检测订单支付状态
    checkOrder: function (ordno) {
        $.post('/paycenter/query', {ordno: ordno}, function (res) {
            if (res.code == 1) {
                if (res.data.pay_status == 1) {
                    $('#pay-tips').html('<span class="success"><i class="icon icon-success"></i>支付成功</span>');
                    window.location.href = res.data.url;
                }
            } else {
                layer.msg(res.msg, {time: 1500});
                return false;
            }
        }, 'json');
    },
    /*order*/
    Oricle: function () {
        // 列表项激活和显示
        $(".payitem li").click(function () {
            $(".payitem li").removeClass("on");
            $(this).addClass("on");
            var rid = $(this).data('id');
            var type = $(this).data('type');
            var price = $(this).data('price');
            $('#rid').val(rid);
            $('#type').val(type);
            $('#money').val(price);
            $('#total').text('￥' + price);
        });
        // 支付小圆点激活和显示
        $(".paytype li").click(function () {
            $(".paytype li").removeClass("on");
            $(this).addClass("on");
            var type = $(this).data('type');
            $('#paytype').val(type);
        });
    },
    //登录检测
    validLogin: function () {
        $.post('/login/checkLogin', {url: window.location.href}, function (data) {
            $('#ulogin').html(data);
        }, 'html');
    },
    //退出登录
    loginOut: function () {
        let self = this;
        $.post('/login/loginOut', {}, function (res) {
            if (res.code == 1) {
                self.validLogin();
            } else {
                layer.msg('退出登录失败');
            }
        }, 'json');
    },
    //发送验证码
    sendSms: function (skeyCode) {
        let self = this;
        var mobile = $('#reUsername').val();
        var filter = /^1(3|4|5|6|7|8|9)\d{9}$/;
        if (!mobile || !filter.test(mobile)) {
            layer.msg('请输入手机号码', {time: 1500});
            return false;
        }
        $('.btn-code').attr('disabled', true);
        $.post('/api/index/sendsms',{mobile: mobile,skeyCode:skeyCode,captchaType:'blockPuzzle'}, function (res) {
            if (res.code == 200) {
                self.hadnleCount();
                layer.msg('验证码已发送', {time: 1000});
            } else {
                layer.msg(res.msg, {time: 1500}, function () {
                    $('.btn-code').attr('disabled', false);
                });
            }
        }, 'json');
    },
    //倒计时组件
    hadnleCount: function () {
        let self = this;
        self.countDown.timer = setInterval(function () {
            if (self.countDown.time <= 0) {
                clearInterval(self.countDown.timer);
                self.countDown.disabled = false;
                self.countDown.btn_text = '获取验证码';
                self.countDown.time = 120;
                $('.btn-code').text(self.countDown.btn_text).attr('disabled', self.countDown.disabled);
            } else {
                self.countDown.disabled = true;
                self.countDown.time--;
                self.countDown.btn_text = self.countDown.time + '秒后再获取';
                $('.btn-code').text(self.countDown.btn_text).attr('disabled', self.countDown.disabled);
            }
        }, 1000);
    },
    //登录
    login: function () {
        var self = this;
        var username = $('#username').val();
        var password = $('#password').val();
        var fromUrl = $('#fromUrl').val();
        var filter = /^1(3|4|5|6|7|8|9)\d{9}$/;
        if (!username || !filter.test(username)) {
            layer.msg('请输入手机号码', {time: 1500});
            return false;
        }
        if (!password || password.length < 6 || password.length > 20) {
            layer.msg('请输入登录密码', {time: 1500});
            return false;
        }
        var checked = 0;
        if ($('#is_rem').attr('checked')) {
            checked = 1;
        }
        var loading = layer.load(1);
        $('.btn-login').attr('disabled', true);
        $.post('/login', {username: username, pwd: password, is_checked: checked}, function (res) {
            layer.close(loading);
            if (res.code == 1) {
                layer.msg('登录成功', {time: 1500}, function () {
                    if (fromUrl) {
                        window.location.href = fromUrl;
                    } else {
                        window.location.href = '/user';
                    }
                });
            } else {
                layer.msg(res.msg, {time: 1500}, function () {
                    $('.btn-login').attr('disabled', false);
                });
            }
        }, 'json');
    },
    //注册
    register: function () {
        var self = this;
        var username = $('#reUsername').val();
        var password = $('#rePassword').val();
        var code = $('#reCode').val();
        var fromUrl = $('#fromUrl').val();
        var invite_uid = $('#invite_uid').val();
        var from = $('#from').val();
        var rid = $('#rid').val();
        var filter = /^1(3|4|5|6|7|8|9)\d{9}$/;
        if (!username || !filter.test(username)) {
            layer.msg('请正确输入手机号码', {time: 1500});
            return false;
        }
        if (!password || password.length < 6 || password.length > 20) {
            layer.msg('请设置登录密码，长度为6~20个字符', {time: 1500});
            return false;
        }
        if (!code || code.length !== 6) {
            layer.msg('请正确输入短信验证码', {time: 1500});
            return false;
        }
        var loading = layer.load(1);
        $('.btn-regiter').attr('disabled', true);
        $.post('/register', {
            mobile: username,
            pwd: password,
            code: code,
            rpwd: password,
            invite_uid: invite_uid,
            from: from,
            rid: rid
        }, function (res) {
            layer.close(loading);
            if (res.code == 1) {
                layer.msg('注册成功', {time: 1500}, function () {
                    if (fromUrl) {
                        window.location.href = fromUrl;
                    } else {
                        window.location.href = '/user';
                    }
                });
            } else {
                layer.msg(res.msg, {time: 1500}, function () {
                    $('.btn-regiter').attr('disabled', false);
                });
            }
        }, 'json');
    },
    findMe: function () {
        var self = this;
        var username = $('#reUsername').val();
        var password = $('#rePassword').val();
        var rpassword = $('#rerPassword').val();
        var code = $('#reCode').val();
        var fromUrl = $('#fromUrl').val();

        var filter = /^1(3|4|5|6|7|8|9)\d{9}$/;
        if (!username || !filter.test(username)) {
            layer.msg('请正确输入手机号码', {time: 1500});
            return false;
        }
        if (!password || password.length < 6 || password.length > 20) {
            layer.msg('请设置登录密码，长度为6~20个字符', {time: 1500});
            return false;
        }
        if (password !== rpassword) {
            layer.msg('二次密码输入不一致', {time: 1500});
            return false;
        }
        if (!code || code.length !== 6) {
            layer.msg('请正确输入短信验证码', {time: 1500});
            return false;
        }
        var loading = layer.load(1);
        $('.btn-regiter').attr('disabled', true);
        $.post('/findme', {mobile: username, pwd: password, rpwd: rpassword, code: code}, function (res) {
            layer.close(loading);
            if (res.code == 1) {
                layer.msg('操作成功，请用新密码登录', {time: 1500}, function () {
                    window.location.href = '/login?fromurl=' + fromUrl;
                });
            } else {
                layer.msg(res.msg, {time: 1500}, function () {
                    $('.btn-regiter').attr('disabled', false);
                });
            }
        }, 'json');
    },
    tab: function () {
        let self = this;
        $('.sign-up-tab li').on('click', function () {
            $(this).addClass('current').siblings().removeClass('current');
            $('.sign-up-bar>div:eq(' + $(this).index() + ')').show().siblings().hide();
            var type = $(this).data('type');
            if (type == 1) {
                self.MyCoursef();//付费
            } else {
                self.MyCourseM();//任务
            }
        });
    },
    /*pwd*/
    Pwd: function () {
        var opwd = $('#opwd').val();
        var npwd = $('#npwd').val();
        var epwd = $('#epwd').val();
        if (opwd === '' || opwd.length < 6 || opwd.length > 20) {
            layer.msg('请输入原密码', {time: 1500});
            return false;
        }
        if (npwd === '' || npwd.length < 6 || npwd.length > 20) {
            layer.msg('请设置新密码', {time: 1500});
            return false;
        }
        if (epwd === '' || epwd.length < 6 || epwd.length > 20) {
            layer.msg('请确认新密码', {time: 1500});
            return false;
        }
        if (npwd !== epwd) {
            layer.msg('二次密码输入不一致', {time: 1500});
            return false;
        }
        var loading = layer.load(1);
        $.post('/user/pwd', {opwd: opwd, npwd: npwd, epwd: epwd,}, function (res) {
            layer.close(loading);
            if (res.code === 1) {
                layer.msg('密码修改成功', {time: 1500}, function () {
                    window.location.reload();
                });
            } else {
                layer.msg(res.msg, {time: 1500});
                return false;
            }
        });
    },
    /** 首页导航栏遮罩层*/
    NavBar: function () {
        const $mask = $('.mask');
        const $navbarToggleBtn = $('.navbar-toggle');
        // 点击按钮切换菜单列表的显示/隐藏
        $navbarToggleBtn.on('click', function () {
            $mask.fadeToggle(200);
        });
        // 点击遮罩层隐藏
        $mask.on('click', function (e) {
            if ($(e.target).hasClass('mask')) {
                $mask.fadeOut(200);
            }
        });
    },
    /*开通会员套餐切换*/
    taoCan: function () {
        // 轮播
        jQuery(".slideTxtBox").slide({mainCell: ".bd ul", autoPage: true, effect: "left", vis: 3});
        $('.Huiyuan li').click(function () {
            $('.Huiyuan li').removeClass('on');
            $(this).addClass('on');
            var rid = $(this).data('id');
            var price = $(this).data('price');
            $('#rid').val(rid);
            $('#money').val(price);
            $('#total').text('￥' + price);
        });
        // 支付小圆点激活和显示
        $(".paytype li").click(function () {
            $(".paytype li").removeClass("on");
            $(this).addClass("on");
            var type = $(this).data('type');
            $('#paytype').val(type);
        });
    },
    // 激活项切换
    Tiny: function () {
        // 给所有标签绑定点击事件
        $('.tiny-list a').click(function (event) {
            event.preventDefault(); // 阻止默认跳转行为
            $('.tiny-list .on').removeClass('on'); // 删除所有标签的激活状态
            $(this).addClass('on'); // 给当前被点击的标签添加激活状态

        });

    },
    //视频播放器
    Player: function (url, thumb) {
        var ext=url.substring(url.lastIndexOf('.') + 1);
        if(ext==='m3u8'){
            this.player = new HlsPlayer({
                "id": 'video',
                "url": url,
                "playsinline": true,
                "fluid": false,
                "width": "100%",
                "height": "100%",
                'playbackRate': [0.5, 0.75, 1, 1.5, 2],
                "poster": thumb,
                "volume": 1,
                'autoplay': true
            });
        }else{
            this.player = new Player({
                "id": "video",
                "url": url,
                "playsinline": true,
                "fluid": false,
                "width": "100%",
                "height": "100%",
                'playbackRate': [0.5, 0.75, 1, 1.5, 2],
                "poster": thumb,
                "volume": 1,
                'autoplay': true
            });
        }
        //监听播放事件
        this.player.on('play', function () {
            console.log('play');
        });
        //监听播放完毕时间
        this.player.on('ended', function () {
            console.log('ended');
        });
    },
    //视频课程播放
    videoPlay: function (spread_id, video_id, spread_thumb) {
        let self = this;
        $.post('/course/video/checkVideo', {rid: spread_id, vid: video_id}, function (res) {
            if (res.code === 1) {
                if (res.data.is_auth === 1 || res.data.is_try === 1) {
                    self.Player(res.data.url, spread_thumb);
                    $('#video_'+res.data.video_id).addClass('on');
                } else {
                    $('.tips-pay').show();
                    return false;
                }
            } else if (res.code === 2) {
                $('.tips-login').show();
                return false;
            } else if (res.code === 3) {
                $('.tips-pay').show();
                return false;
            } else {
                layer.msg(res.msg, {time: 1500});
                return false;
            }
        }, 'json');
    },
    //视频资源播放事件
    singlePlay: function (url) {
        this.player.start(url);
        this.player.play();
    },
    //返回顶部
    GoTop: function () {
        $('#top-back').hide();
        $(window).scroll(function () {
            if ($(this).scrollTop() > 350) {
                $('#top-back').fadeIn();
            } else {
                $('#top-back').fadeOut();
            }
        });
        $('#top-back').click(function (e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        });
    },
    // 请求接口
    avatar: function () {
        $(function () {
            $('#addPic').change(function () {
                var token = $('#mytoken').val();
                if (!token) {
                    layer.msg("头像上传失败", {time: 1500});
                    return false;
                }
                var files = this.files;
                if (files.length < 1) {
                    layer.msg("未选择图片", {time: 1500});
                }
                var formData = new FormData();
                formData.append('file', files[0]);
                formData.append('token', token);
                var loading = layer.load(1);
                $.ajax({
                    type: 'post',
                    url: '/api/user/upload',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (res) {
                        layer.close(loading);
                        if (res.code === 200) {
                            var imgUrl = res.data.url;
                            // 渲染 URL 到图片上
                            $('#avatarImg').attr('src', imgUrl);
                        } else {
                            layer.msg('头像上传失败', {time: 1500});
                        }
                    }
                });
            });
        });
    },
    // 保存信息
    saveInfo: function () {
        $('button[etap=submit_info]').on('click', function () {
            var nickname = $('#nickname').val().trim();
            var avatar = $('#avatar').val().trim();
            var token = $('#mytoken').val();
            if (!avatar) {
                layer.msg("请上传头像", {time: 1500});
                return false;
            }
            if (!nickname) {
                layer.msg("请输入昵称", {time: 1500});
                return false;
            }
            var loading = layer.load(1);
            $.ajax({
                type: 'post',
                url: '/api/user/saveInfo',
                data: {
                    token: token,
                    nickname: nickname,
                    avatar: avatar
                },
                dataType: 'json',
                success: function (res) {
                    layer.close(loading);
                    if (res.code === 200) {
                        layer.msg("保存成功", {time: 1500});
                    } else {
                        layer.msg("保存失败", {time: 1500});
                    }
                }
            });
        });
    },
    // 课程分类
    selectFav: function () {
        var sid = $('#sid').val(); // 获取当前分类 ID
        var token = $('#mytoken').val();// token
        var url = '/api/user/mycoll'; // 接口地址
        $.ajax({
            type: 'POST',
            url: url,
            data: {
                token: token,
                page: 1,
                limit: 20,
                sort_id: sid
            },
            dataType: 'json',
            success: function (res) {
                if (res.code === 200) {
                    var data = res.data.list;
                    if (data.length == 0) {
                        $('.nodata').show();
                    } else {
                        var $list = $('#course-list'); // 获取课程列表容器元素
                        for (var i = 0; i < data.length; i++) {
                            var item = data[i]; // 取模获取课程信息的索引
                            var html = `
                            <li class="recommend-item" style="width:200px;">
                                <a href="/course/show/${item.id}" class="link" title="${item.title}" target="_blank">
                                  <img  class="course-thumb lazy-img" data-src="${item.thumb}" src="${item.thumb}">
                                  <div class="course-card">
                                    <div class="course-title">${item.title}</div>
                                    <div class="course-desc">
                                      <em class="price">${item.price>0?'￥'+item.price:'免费'}</em>
                                      <em class="sales">${item.sales}人学过</em>
                                    </div>
                                  </div>
                                </a>
                              </li>`;
                            $list.append(html); // 将课程信息插入到列表容器中
                        }
                    }
                } else if (res.code === 403) {
                    window.location.href = '/login?fromurl=' + window.location.href;
                } else {
                    layer.msg("获取失败", {time: 1500});
                }
            },
        });
    },
    // 滑动加载和图片懒加载
    FavLazy: function () {
        // 然后监听窗口滚动事件
        $(window).on('scroll', function () {
            // 判断哪些图片已经滚动到了可视区域内
            $('img.lazy:not(.loaded)').each(function () {  // 只加载没有被加载过的图片
                if (isInViewport($(this))) {
                    var loading = layer.msg('正在加载中', {time: 1500});
                    // 将可视区域内需要加载的图片的 data-src 属性的值替换为 src 属性的值
                    $(this).attr('src', $(this).data('src')).addClass('loaded').on('load', function () {
                        layer.close(loading);
                    }); // 当图片加载完成后关闭正在加载的提示框
                }
            });
        });

        // 判断元素是否在可视区域内的函数
        function isInViewport(elem) {
            var viewportHeight = $(window).height(),
                scrollTop = $(window).scrollTop(),
                offsetTop = elem.offset().top;
            return (offsetTop > scrollTop && offsetTop < (scrollTop + viewportHeight));
        }
    },
    // 付费
    MyCoursef: function () {
        var sid = $('#sid').val(); // 获取当前分类 ID
        var token = $('#mytoken').val();// token
        var url = '/api/user/mybuyres'; // 接口地址
        $.ajax({
            type: 'POST',
            url: url,
            data: {
                token: token,
                page: 1,
                limit: 20,
                sid: sid
            },
            dataType: 'json',
            success: function (res) {
                if (res.code === 200) {
                    var data = res.data.list;
                    if (data.length == 0) {
                        $('.nodata').show();
                    } else {
                        var $list = $('#mycourse_list'); // 获取课程列表容器元素
                        for (var i = 0; i < data.length; i++) {
                            var item = data[i]; // 取模获取课程信息的索引
                            var status = '<span class="red">待支付</span>';
                            if (item.status == 1) {
                                status = '<span class="green">已支付</span>';
                            } else if (item.status == 2) {
                                status = '<span class="green">已取消</span>';
                            }
                            var html = `
                              <li class="course_item">
                                  <div class="thumb">
                                      <img  class="lazy" data-src="${item.thumb}" src="${item.thumb}"  title="${item.desc}" class="zp-line-img lazy-img">
                                  </div>
                                  <div class="course_info">
                                      <div class="course_title">${item.title}</div>
                                      <div class="course_price">
                                            <span class="price">￥${item.price}</span>
                                            <span class="sales">${item.sales}人已学习</span>
                                       </div>
                                      <div class="course_time">${item.ctime}</div>
                                  </div>
                                  <div class="course_other">
                                        <div class="status">${status}</div>
                                        <div class="links"><a href="/course/show/${item.rid}.html" class="go-views">去查看</a></div>
                                  </div>
                              </li>`;
                            $list.append(html); // 将课程信息插入到列表容器中
                        }
                    }
                } else if (res.code === 403) {
                    window.location.href = '/login?fromurl=' + window.location.href;
                } else {
                    layer.msg("获取失败", {time: 1500});
                }
            },
        });
    },
    // 免费
    MyCourseM: function () {
        var sid = $('#sid').val(); // 获取当前分类 ID
        var token = $('#mytoken').val();// token
        var url = '/api/user/mytaskres'; // 接口地址
        $.ajax({
            type: 'POST',
            url: url,
            data: {
                token: token,
                page: 1,
                limit: 20,
                sid: sid
            },
            dataType: 'json',
            success: function (res) {
                if (res.code === 200) {
                    var data = res.data.list;
                    if (data.length == 0) {
                        $('.nodata').show();
                    } else {
                        var $list = $('#mycourse_task'); // 获取课程列表容器元素
                        for (var i = 0; i < data.length; i++) {
                            var item = data[i]; // 取模获取课程信息的索引
                            var html = `
                        <li class="course_item">
                          <div class="thumb">
                              <img  class="lazy" data-src="${item.thumb}" src="${item.thumb}"  title="${item.desc}" class="zp-line-img lazy-img">
                          </div>
                          <div class="course_info">
                              <div class="course_title">${item.title}</div>
                              <div class="course_price">价格:<span class="price">￥${item.price}</span></div>
                              <div class="course_sales">
                                <span class="sales">已有${item.sales}人学习啦</span>
                              </div>
                          </div>
                          <div class="course_other">
                            <div class="status">已获得</div>
                            <div class="links"><a href="/course/show/${item.rid}" class="go-views">去查看</a></div>
                          </div>
                        </li>`;
                            $list.append(html); // 将课程信息插入到列表容器中
                        }
                    }
                } else if (res.code === 403) {
                    window.location.href = '/login?fromurl=' + window.location.href;
                } else {
                    layer.msg("获取失败", {time: 1500});
                }
            },
        });
    },
    /**cashlist */
    cashlist: function () {
        var token = $('#mytoken').val(); // token
        var url = '/api/user/cashlist'; // 接口地址
        var page = 1; // 当前页码
        var limit = 20; // 每页显示数量
        var isEnd = false; // 是否已加载完所有数据
        var isLoading = false; // 是否正在加载数据
        var isNoMoreData = false; // 是否没有更多数据了

        function loadData() {
            if (isEnd || isLoading) return; // 如果已加载完所有数据或正在加载数据，则不执行后续操作
            isLoading = true; // 设置正在加载数据状态为true
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    token: token,
                    page: page,
                    limit: limit,
                },
                dataType: 'json',
                success: function (res) {
                    isLoading = false; // 设置加载数据完成状态为false
                    if (res.code === 200) {
                        console.log(res)
                        var data = res.data.list;
                        var $list = $('#cashlist'); // 获取课程列表容器元素
                        if (data.length === 0 && page === 1) {
                            // 数据列表为空且为第一页
                            isEnd = true;
                            isNoMoreData = true;
                            $list.append('<li class="no-data">暂无数据</li>');
                        } else {
                            if (data.length < limit) {
                                // 数据列表数量少于请求的limit值
                                isEnd = true;
                                isNoMoreData = true;
                            }
                            for (var i = 0; i < data.length; i++) {
                                var item = data[i]; // 取模获取课程信息的索引
                                console.log(item.type)
                                var html = `
                                  <li class="zp-operation">
                                    <div class="zhi">
                                      <i class="icon ${item.type === 1 ? 'icon-alipay' :
                                    item.type === 2 ? 'icon-wechat' :
                                        item.type === 3 ? 'icon-qiamizhifu' : ''}">
                                      </i>
                                      <div>
                                        <span>${item.type === 1 ? '支付宝提现' :
                                    item.type === 2 ? '微信提现' :
                                        item.type === 3 ? '银行卡提现' :
                                            ''}</span>
                                        <span class="zp-buzu">${item.status === -1 ? '失败原因：' + item.reason : ''}</span>
                                        <div style="font-size:14px;color:#6c6c6c;padding:5px 0;">
                                            手续费${item.fee}元，实际账${item.amount}元
                                        </div>
                                        <div class="ti">
                                          <span style="color:#9c9c9c;">${item.ctime}</span>
                                        </div>
                                      </div>
                                    </div> 
                                    <div class="status" style="display: flex;align-items: center; text-align: right;">
                                      <div style="display: flex; flex-direction: column;">
                                        <span>
                                          ${item.status === -1 ? '提现失败' :
                                    item.status === 0 ? '待处理' :
                                        item.status === 1 ? '待打款' :
                                            item.status === 2 ? '打款成功' : ''
                                    }
                                        </span>
                                        <span class="money" style="color:red; margin: 5px 0;">
                                           ${item.money}
                                        </span>
                                        <span class="utime">${item.utime}</span>
                                      </div>
                                    </div>
                                  </li>`;
                                $list.append(html); // 将课程信息插入到列表容器中

                                // 显示加载提示信息
                                layer.msg("正在加载中...", {time: 1500});

                            } // 隐藏加载提示信息
                            layer.closeAll('loading');
                            page++; // 增加页码，准备请求下一页数据
                        }
                        if (isNoMoreData) {
                            layer.msg("没有更多数据了", {time: 1500});
                        }
                    } else {
                        layer.msg("获取失败", {time: 1500});
                    }
                },
            });
        }

        function loadMoreData() {
            if (isNoMoreData || isLoading || isEnd) return; // 如果没有更多数据、正在加载数据或已加载完所有数据，则不执行后续操作
            var $window = $(window);
            var windowHeight = $window.height();
            var documentHeight = $(document).height();
            var scrollTop = $window.scrollTop();
            if (scrollTop + windowHeight >= documentHeight) {
                loadData();
            }
        }

        // 页面滚动触底时加载更多数据
        $(window).scroll(function () {
            loadMoreData();
        });

        // 初次加载数据
        loadData();
    },
    // 全部提现
    withdrawAll: function () {
        var balance = parseFloat($('.money').text());
        $('#JinE').val(balance.toFixed(2)); // 将可提现余额四舍五入，并填充到文本框中
    },
    // 表单验证
    handleCash: function () {
        // 支付宝 tab 验证
        $("#alipay-tab form").submit(function (event) {
            event.preventDefault(); // 防止表单默认提交
            var jin = $("#JinE").val();
            if (jin === "" || jin === "0" || jin === "0.00") {
                layer.msg("请输入提现金额", {time: 1500});
                return false;
            }
            var truename = $("#txtTruename").val();
            var alipayAccount = $("#txtAccount").val();
            if (truename === "") {
                layer.msg("请输入支付宝账号姓名", {time: 1500});
                return false;
            }
            if (alipayAccount === "") {
                layer.msg("请输入支付宝账号", {time: 1500});
                return false;
            } else if (!/^(?:1[3-9]\d{9}|[a-zA-Z\d._-]*\@[a-zA-Z\d.-]{1,10}\.[a-zA-Z\d]{1,20})$/.test(alipayAccount)) {
                layer.msg("支付宝账号只能是11位手机号或16位数字和字母组合", {time: 1500});
                return false;
            }
            WCCE.cash();
        });

        // 银行卡 tab 验证
        $("#bank-tab form").submit(function (event) {
            event.preventDefault(); // 防止表单默认提交
            var jin = $("#JinE").val();
            if (jin === "" || jin === "0") {
                layer.msg("请输入提现金额", {time: 1500});
                return false;
            }
            var bankName = $("#bankName").val();
            var bankAccountName = $("#bankTrueName").val();
            var bankAccount = $("#bankCardNo").val();
            if (bankName === "") {
                layer.msg("请输入开户行名称", {time: 1500});
                return false;
            }
            if (bankAccountName === "") {
                layer.msg("请输入银行开户名", {time: 1500});
                return false;
            }
            if (bankAccount === "") {
                layer.msg("请输入银行卡号", {time: 1500});
                return false;
            } else if (!/^[0-9]+$/.test(bankAccount)) {
                layer.msg("银行卡号只能包含数字", {time: 1500});
                return false;
            } else if (bankAccount.length > 20) {
                layer.msg("银行卡号长度不能超过20位", {time: 1500});
                return false;
            }
            WCCE.cash();
        });
    },
    cash: function () {
        var token = $('#mytoken').val();// token
        var url = '/api/user/cash'; // 接口地址
        var type = $('.sign-up-tab .current').attr('data');
        var money = $('#JinE').val(); // 提现金额
        var realname = type == 1 ? $('#txtTruename').val() : $('#bankTrueName').val();
        var account = $('#txtAccount').val(); // 支付宝账号
        var bankname = $('#bankName').val(); // 开户行银行名称
        var bankno = $('#bankCardNo').val(); // 银行卡卡号
        var loading = layer.load(1);
        $.ajax({
            type: 'POST',
            url: url,
            data: {
                token: token,
                type: type,
                money: money,
                realname: realname,
                bankname: bankname,
                bankno: bankno,
                account: account,
            },
            dataType: 'json',
            success: function (res) {
                layer.close(loading);
                if (res.code === 200) {
                    layer.msg("提现成功", {time: 1500});
                } else if (res.code === 101) {
                    layer.msg(res.msg, {time: 1500});
                } else {
                    layer.msg("提现失败", {time: 1500});
                }
            },
        });

    },
    //收益明细
    incomelist: function () {
        var page = 1; // 当前页数
        var isLoading = false; // 是否正在加载数据
        var isEnd = false; // 是否已加载完所有数据
        var $list = $('#incomelist'); // 获取容器元素
        function loadData() {
            if (isLoading || isEnd) return; // 如果正在加载数据或已加载完所有数据，则不执行加载操作
            isLoading = true;
            var token = $('#mytoken').val(); // token
            var url = '/api/user/incomelist'; // 接口地址
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    token: token,
                    page: page,
                    limit: 20
                },
                dataType: 'json',
                success: function (res) {
                    if (res.code === 200) {
                        var data = res.data.list;
                        if (data.length == 0 && page == 1) {
                            $('.nodata').show();
                        } else {
                            for (var i = 0; i < data.length; i++) {
                                var item = data[i];
                                var html = `
                            <div class="zp-operation">
                              <div class="zhi">
                                <div class="zp-y">
                                  <span class="mode">
                                        ${item.mode === 1 ? '资源收益' :
                                    item.mode === 2 ? '会员收益' :
                                        item.mode === 3 ? '提现' :
                                            ''} 
                                        <span class="zp-bei">收益备注:${item.remark}</span>
                                  </span>
                                  <span class="ctime" >${item.ctime}</span>
                                </div>
                              </div>
                              <div class="zp-remon">
                                <span class="mon  ${item.mode === 1 ? 'mon2' :
                                    item.mode === 2 ? 'mon1' :
                                        ''
                                    }"> <span class="ti">${item.type_name}</span> ${item.mode === 1 ? '+' :
                                    item.mode === 2 ? '-' :
                                        ''
                                    }${item.money}</span>
                                <span>提现前为${item.before}元，余额为：${item.after}元</span>
                              </div>
                            </div>`;
                                layer.msg('加载数据中...', {time: 1500});
                                $list.append(html);
                            }

                            if (data.length < 20) {
                                isEnd = true; // 设置已加载完所有数据
                                layer.msg('没有更多数据了', {time: 1500});
                            } else {
                                page++;
                                isLoading = false; // 设置加载完成
                            }
                        }
                    } else {
                        layer.msg('获取失败', {time: 1500});
                        isLoading = false; // 设置加载完成
                    }
                },
                error: function () {
                    layer.msg('获取失败', {time: 1500});
                    isLoading = false; // 设置加载完成
                }
            });
        }

        $(window).on('scroll', function () {
            var scrollTop = $(this).scrollTop();
            var scrollHeight = $(document).height();
            var windowHeight = $(this).height();

            if (scrollTop + windowHeight >= scrollHeight) {
                loadData();
            }
        });

        // 初始化时加载第一页数据
        loadData();
    },
    // 收益累计
    incomeStat: function () {
        var token = $('#mytoken').val();// token
        var url = '/api/user/incomeStat'; // 接口地址
        $.ajax({
            type: 'POST',
            url: url,
            data: {
                token: token
            },
            dataType: 'json',
            success: function (res) {
                if (res.code === 200) {
                    var data = res.data;
                    var $list = $('#spread');
                    var item = data;
                    var html = `
                    <p class="earnings">可提现收益(元)</p>
                    <h2 class="money">${item.balance}</h2>
                    <a href="/user/cash" class="tiXian_btn tiXian_btner">去提现</a>`;
                    $list.append(html);
                    var $table = $('#table')
                    var html = `
                        <p class="earnings">可提现金额(元)</p>
                        <h2 class="money">${item.balance}</h2>
                        <p><a href="/user/cashlist" class="tiXian_btn tiXian_btner">提现记录</a> </p>`;
                    $table.append(html);

                    var $tui = $('#tui');
                    var html = `
                        <div class="list">
                        <div class="item">
                          <span>今日收入</span>
                          <p>${item.today} <em>(元)</em></p>
                        </div>
                        <div class="item">
                          <span>昨日收入</span>
                          <p>${item.yesday} <em>(元)</em></p>
                        </div>
                      </div>
        
                      <div class="list">
                        <div class="item">
                        <span>本月收入</span>
                        <p>${item.month} <em>(元)</em></p>
                        </div>
                        <div class="item">
                        <span>累计收入</span>
                        <p>${item.total} <em>(元)</em></p>
                        </div>
                      </div>`;
                    $tui.append(html);
                    var $shouxu = $('#shouxu')
                    var html = `说明: 手续费为5%,可提取${item.balance}元`;
                    $shouxu.append(html);
                } else {
                    layer.msg("获取失败", {time: 1500});
                }
            },
        });
    },
    /*邀请的人*/
    teamlist: function () {
        var token = $('#mytoken').val();// token
        var url = '/api/user/teamlist'; // 接口地址
        var loading = layer.msg("正在加载中...");
        var page = 1; // 当前页数
        var isLoading = false; // 是否正在加载数据
        function loadData() {
            if (isLoading) return; // 如果正在加载数据，则不执行加载操作
            isLoading = true;
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    token: token,
                    page: 1,
                    limit: 20
                },
                dataType: 'json',
                success: function (res) {
                    if (res.code === 200) {
                        layer.close(loading);
                        isLoading = false; // 设置加载数据完成状态为false
                        var data = res.data.list;
                        if (data.length == 0 && page == 1) {
                            $('.nodata').show();
                        } else {
                            var $list = $('#teamlist'); // 获取容器元素
                            for (var i = 0; i < data.length; i++) {
                                var item = data[i]; // 取模获取课程信息的索引
                                // 获取我的好友标签元素
                                var $friends = $('.newest-tags span:first-child');
                                // 将好友数量插入到标签中
                                $friends.text('我的好友(' + data.length + ')');
                                var html = `
                                    <div class="fri">
                                    <div class="ava">
                                      <img src="${item.avatar}">
                                      <div class="ye">
                                        <span>${item.nickname}</span>
                                        <div class="ti">
                                          <span>${item.ctime}</span>
                                        </div>
                                      </div>
                                    </div>
                                    <div class="biaoShi">
                                      <i class="icon icon-user"></i>
                                      <span class="dao">${item.level_name
                                    }</span>
                                    </div>
                                  </div> `;
                                $list.append(html); // 将课程信息插入到列表容器中
                            }
                            if (data.length < 20) {
                                // 如果返回的数据长度少于20，说明已经加载完所有数据
                                $(window).off('scroll'); // 禁用滑动加载功能
                                // 加载完所有数据后，显示没有更多数据了的消息
                                layer.msg("没有更多数据了", {time: 1500});
                            } else {
                                page++; // 否则页数加1
                            }
                        }
                    } else {
                        layer.msg("获取失败", {time: 1500});
                    }
                },
                error: function () {
                    layer.msg("获取失败", {time: 1500});
                },
                complete: function () {
                    isLoading = false; // 加载状态设为false
                }
            });
        }

        $(window).on('scroll', function () {
            var scrollTop = $(this).scrollTop();
            var scrollHeight = $(document).height();
            var windowHeight = $(this).height();

            if (scrollTop + windowHeight >= scrollHeight) {
                // 滚动到底部时触发加载函数
                loadData();
            }
        });
        // 初始化时加载第一页数据
        loadData();
    },
    /**收益明细 */
    income: function () {
        var token = $('#mytoken').val(); // token
        var url = '/api/user/incomelist'; // 接口地址
        var loading = layer.load(1);
        var page = 1; // 当前页数
        var isLoading = false; // 是否正在加载数据
        function loadData() {
            if (isLoading) return; // 如果正在加载数据，则不执行加载操作
            isLoading = true;
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    token: token,
                    page: page,
                    limit: 20
                },
                dataType: 'json',
                success: function (res) {
                    if (res.code === 200) {
                        layer.close(loading);
                        isLoading = false; // 设置加载数据完成状态为false
                        var data = res.data.list;
                        if (data.length == 0 && page == 1) {
                            $('.nodata').show();
                        } else {
                            var $list = $('#income'); // 获取列表容器元素
                            for (var i = 0; i < data.length; i++) {
                                var item = data[i];

                                var html = `
                            <div class="zp-operation">
                              <div class="zhi">
                                <div class="zp-y">
                                  <div class="mode">${item.type === 1 ? '资源收益' :
                                    item.type === 2 ? '会员收益' :
                                        item.type === 3 ? '提现' :
                                            ''
                                    } 
                                  </div>
                                  <div class="zp-bei">备注:${item.remark}</div>
                                </div>
                              </div>
                              <div class="zp-remon">
                                <span class="mon  ${item.mode === 1 ? 'mon2' :
                                    item.mode === 2 ? 'mon1' :
                                        ''
                                    }">${item.mode === 1 ? '+' :
                                    item.mode === 2 ? '-' :
                                        ''
                                    }${item.money}</span>
                                            <span class="ctime" >${item.ctime}</span>
                              </div>
                            </div>`;
                                $list.append(html); // 将新的数据添加到列表容器中
                            }
                            if (data.length < 20) {
                                // 如果返回的数据长度少于20，说明已经加载完所有数据
                                $(window).off('scroll'); // 禁用滑动加载功能
                                // 加载完所有数据后，显示没有更多数据了的消息
                                layer.msg("没有更多数据了", {time: 1500});
                            } else {
                                page++; // 否则页数加1
                            }
                        }
                    } else {
                        layer.msg("获取失败", {time: 1500});
                    }
                },
                error: function () {
                    layer.msg("获取失败", {time: 1500});
                },
                complete: function () {
                    isLoading = false; // 加载状态设为false
                }
            });
        }

        $(window).on('scroll', function () {
            var scrollTop = $(this).scrollTop();
            var scrollHeight = $(document).height();
            var windowHeight = $(this).height();
            if (scrollTop + windowHeight >= scrollHeight) {
                // 滚动到底部时触发加载函数
                loadData();
            }
        });
        // 初始化时加载第一页数据
        loadData();
    },
    audioPlay: function (spread_id, video_id, spread_rid) {
        let self = this;
        $.post('/course/video/checkAudio', {rid: spread_id, vid: video_id, r_id: spread_rid}, function (res) {
            if (res.code === 1) {
                if (res.data.is_auth === 1 || res.data.is_try === 1) {
                    $('.audio-wrap').show();
                    $('#sub-title').text(res.data.name);
                    self.Music(res.data.url);
                    $('#audio_'+res.data.audio_id).addClass('on');
                } else {
                    $('.tips-pay').show();
                    return false;
                }
            } else if (res.code === 2) {
                $('.tips-login').show();
                return false;
            } else if (res.code === 3) {
                $('.tips-pay').show();
                return false;
            } else {
                layer.msg(res.msg, {time: 1500});
                return false;
            }
        }, 'json');
    },
    Music: function (url) {
        let player = new window.Player({
            id: 'mse',
            url: url,
            volume: 0.8,
            width: '100%',
            height: 50,
            mediaType: 'audio',
            presets: ['default', window.MusicPreset],
            ignores: ['playbackrate'],
            controls: {
                initShow: true,
                mode: 'flex'
            },
            marginControls: true,
            videoConfig: {
                crossOrigin: "anonymous"
            }
        });
        player.crossOrigin = "anonymous";
        // document.getElementById("canvas").width = window.innerWidth;
        // document.getElementById("canvas").height = window.innerHeight * 0.25;
    },
    getCardList: function (id) {
        var token = $('#mytoken').val();// token
        $.post('/api/resource/buykmList', {id: id, token: token}, function (res) {
            if (res.code === 200) {
                var cdklist = res.data.cdklist;
                var html = '';
                for (let i = 0; i < cdklist.length; i++) {
                    html += `
                    <div class="card-item">
                        <span class="cdk-no">${cdklist[i].cdkey}</span>
                        <span class="btn-code" aria-code="${cdklist[i].cdkey}" aria-label="卡密复制成功">
                            复制
                        </span>
                    </div>`;
                }
                $('#card-list').html(html);
            } else {
                layer.msg(res.msg, {time: 1500});
                return false;
            }
        });
    },
    handleCdk: function (id) {
        var token = $('#mytoken').val();// token
        if (!token) {
            layer.msg('请登录后操作', {time: 1500}, function () {
                window.location.href = '/login?fromurl=' + encodeURIComponent(window.location.href);
            });
            return false;
        }
        $.post('/api/resource/kammiTask', {id: id, token: token}, function (res) {
            if (res.code === 200) {
                layer.prompt({
                    formType: 0,
                    value: res.data.cdkey,
                    title: '获取卡密',
                    area: ['600px', '350px'] // 自定义文本域宽高
                }, function (value, index, elem) {
                    layer.close(index); // 关闭层
                });
            } else if (res.code === 403) {
                window.location.href = '/login?fromurl=' + encodeURIComponent(window.location.href);
            } else {
                layer.msg(res.msg, {time: 1500});
                return false;
            }
        });
    },
    getUserInfo: function (callback) {
        var token = $('#mytoken').val();// token
        if (!token) {
            layer.msg('请登录后操作', {time: 1500}, function () {
                window.location.href = '/login?fromurl=' + encodeURIComponent(window.location.href);
            });
            return false;
        }
        $.post('/api/user/userinfo', {token: token}, function (res) {
            if (res.code === 200) {
                callback(res.data);
            } else {
                layer.msg(res.msg, {time: 1500});
                return false;
            }
        });
    },
    //推广链接
    spreadLink: function () {
        var invite_url = $('#invite_url').val();
        layer.prompt({
            formType: 0,
            value: invite_url,
            title: '获取推广链接',
            area: ['600px', '350px'] // 自定义文本域宽高
        }, function (value, index, elem) {
            layer.close(index); // 关闭层
        });
    },
    spreadPoster: function () {
        var token = $('#mytoken').val();// token
        if (!token) {
            layer.msg('请登录后操作', {time: 1500}, function () {
                window.location.href = '/login?fromurl=' + encodeURIComponent(window.location.href);
            });
            return false;
        }
        var invite_url = $('#invite_url').val();
        $.post('/api/user/spreadH5Poster', {token: token, links: invite_url}, function (res) {
            if (res.code === 200) {
                layer.open({
                    type: 1,
                    title: '获取推广海报',
                    area: ['300px', '580px'],
                    content: '<img width="100%" height="auto" src="' + res.data.url + '"/>'
                });
            } else {
                layer.msg(res.msg, {time: 1500});
                return false;
            }
        });
    },
    //微信扫码登录
    getQrcode: function (callback) {
        let self = this;
        var loading = layer.load(1);
        $.post('/login/loginQrcode', {}, function (res) {
            layer.close(loading);
            if (res.code === 1) {
                $('#qrcode-img').attr('src', res.data.url);
                callback();
                self.hadnleScan(res.data);
            } else {
                layer.msg(res.msg, {time: 1500});
                return false;
            }
        }, 'json');
    },
    scanQrcode: function (data) {
        let self = this;
        $.post('/login/scanQrcode', data, function (res) {
            if (res.code === 1) {
                clearInterval(self.qrcodeTime.timer);
                var fromUrl = $('#fromUrl').val();
                layer.msg('登录成功', {time: 1500}, function () {
                    if (fromUrl) {
                        window.location.href = fromUrl;
                    } else {
                        window.location.href = '/user';
                    }
                });
            }else if(res.code === 3){
                clearInterval(self.qrcodeTime.timer);
                layer.msg(res.msg, {time: 1500}, function () {
                    window.location.reload();
                });
            }else if(res.code === 4){
                clearInterval(self.qrcodeTime.timer);
                layer.msg(res.msg, {time: 1500}, function () {
                    window.location.reload();
                });
            }else if(res.code === 5){
                clearInterval(self.qrcodeTime.timer);
                layer.msg(res.msg, {time: 1500}, function () {
                    window.location.reload();
                });
            }
        },'json');
    },
    //查询扫码
    hadnleScan: function (data) {
        let self = this;
        self.qrcodeTime.timer = setInterval(function () {
            console.log(self.qrcodeTime.time);
            if (self.qrcodeTime.time <= 0) {
                clearInterval(self.qrcodeTime.timer);
                self.qrcodeTime.time = (data.expire_seconds/2);
            } else {
                self.qrcodeTime.time--;
                self.scanQrcode(data);
            }
        }, 2000);
    },
};