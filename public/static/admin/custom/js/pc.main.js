
// 左边组件
var allWidget = {

    // 媒体组件
    "mediaComponents": [
        {
            "type": "imgSlide",
            "name": "图片轮播",
            "value": {
                "radio": '1', //图片宽度是否显示
                "duration": 2500, //自动切换时间
                "height": 308,//高度
                "width": 10,//宽度
                "gradientDirection": "bottom",  //渐变方向
                "startColor": "#fff", //起始颜色
                "endColor": "#fff", //结束颜色
                "list": [{
                    "image": default_banner,
                    "linkValue": '2', "url": '', "cat": [2], "art": 1,
                    "status": 1
                },
                {
                    "image": default_banner,
                    "linkValue": '3', "url": '', "cat": [2], "art": 2,
                    "status": 1
                }
                ]
            },
            "icon": "icon-tupianlunbo"
        },
        {
            "type": "imgSingle",
            "name": "图片",
            "value": {
                "list": [{
                    "image": default_banner,
                    "status": 1
                }],
                "linkValue": '1',
                "url": '',
                "cat": [1],
                "art": 1,
                "buttonShow": false, //是否显示按钮
                "buttonText": '', //按钮文字
                "gradientDirection": "bottom", //渐变方向
                "startColor": "#fff", //起始颜色
                "endColor": "#fff", //结束颜色
                "textColor": "#000000", //文字颜色
                "width": "5", //宽度
                "radio": "1", //是否显示宽度
                "height": "300", //高度
                "color": {
                    "gradientDirection": "bottom",//渐变方向
                    "startColor": "#fff", //起始颜色
                    "endColor": "#fff",//结束颜色
                    "height": "26",//按钮高度
                    "width": "100",//按钮宽度
                },

            },
            "icon": "icon-tupian"
        },
        {
            "type": "imgWindow",
            "name": "图片分组",
            "value": {
                "style": 2, // 两列=2 三列=3 四列=4 五列=5
                "margin": 6,
                "title": '精选图片',
                "radio": '1', //是否显示布局
                "lookMore": '1', //是否显示更多
                "list": [
                    {
                        "image": default_banner,
                        "status": 1,
                        "linkValue": '1', "url": '', "cat": [2], "art": 3,
                    },
                    {
                        "image": default_banner,
                        "status": 1,
                        "linkValue": '2', "url": '', "cat": [5], "art": 1,
                    }, {
                        "image": default_banner,
                        "status": 1,
                        "linkValue": '3', "url": '', "cat": '', "art": '',
                    },
                    {
                        "image": default_banner,
                        "status": 1,
                        "linkValue": '1', "url": '', "cat": '', "art": '',
                    },
                    {
                        "image": default_banner,
                        "status": 1,
                        "linkValue": '2', "url": '', "cat": '', "art": '',
                    }
                ]
            },
            "icon": "icon-fenzu"
        },
        {
            "type": "video",
            "name": "视频组",
            "value": {
                "radio": '1',//是否显示宽度
                "width": '5',//宽度
                "height": '350',//高度
                "autoplay": false,//是否自动播放
                "icon": 'el-icon-link',
                "gradientDirection": "right",//渐变方向
                "startColor": "#fff",//起始颜色
                "endColor": "#fff", //结束颜色
                "list": [{
                    "image": default_banner,
                    "url": "http://wxsnsdy.tc.qq.com/105/20210/snsdyvideodownload?filekey=30280201010421301f0201690402534804102ca905ce620b1241b726bc41dcff44e00204012882540400",
                    "linkValue": '1',
                    "link": '',
                    "cat": [],
                    "art": '',"status": 1
                }]
            },
            "icon": "icon-m_pinjie"
        },
        {
            "type": "article",
            "name": "文章组",
            "value": {
                "limit": '2',//显示数量
                "style": '2',//  class：article-wrap =2 class：ArticleWrap =3
                "show": '1',//是否显示布局
                "choose": '1', //自动选择=1 手动选择=2
                "art": '',
                "title": '精选文章',
                "lookMore": '1',//是否显示更多
                "radio": '1',//是否显示标题
                "list": [{
                    "thumb": '',
                    "title": '',
                    "discrible": '',
                    "ctime": '',
                    "writer": '',
                    "reader": '',
                    "linkValue": '',"status": 1
                }],
                "List": []
            },
            "icon": "icon-file"
        },
        {
            "type": "problem",
            "name": "常见问题",
            "value": {
                "title": "常见问题",
                "limit": 4,//一排4个：4，一排5个：5
                "radio": 1,//是否显示布局
                "list": [
                    { title: '请选择常见问题1' },
                    { title: '请选择常见问题2' },
                    { title: '请选择常见问题3' },
                    { title: '请选择常见问题4' },
                ],
                "List": []
            },
            "icon": "icon-wenti"
        }
    ],
    // 资源组件
    "storeComponents": [
        {
            "type": "search",
            "name": "搜索框",
            "value": {
                "radio": "1",//是否显示背景图，标题，描述
                "radio1": "1",//是否显示标题信息
                "radio2": "1",//是否显示描述信息
                "width": "7",//搜索框宽度
                "title": "站码网打造亲测精品源码平台",//标题
                "describle": "本站所有资源均为高质量资源，各种姿势下载。",//描述
                "list": [{ "image": default_search,"status":1 }],//背景图片
                "keywords": '请输入关键字搜索',
                "style": 'round' // round:圆弧 radius:圆角 square:方形
            },
            "icon": "icon-search"
        },
        {
            "type": "notice",
            "name": "公告组",
            "value": {
                "lineHeight": "40",//公告高度
                "FontSize": "16",//字体大小
                "gradientDirection": "bottom",//渐变方向
                "startColor": "#fff",//起始颜色
                "endColor": "#fff",//结束颜色
                "color2": "#333",//字体颜色
                "pope": "yes",//是否弹窗
                "type": 'auto', //choose手动选择， auto 自动获取
                "list": [
                    {
                        "title": "这里是第一条公告的标题",
                        "ctime": "","ctime_text":'',
                        "id": '',

                    }
                ],
                "List": [],
            },
            "icon": "icon-gonggao"
        },

        {
            "type": "resource",
            "name": "资源组",
            "icon": "icon-a-33ziyuan",
            "value": {
                "classifyId": '', //所选分类id
                "show": "1",//是否推荐
                "radio": "1",//是否显示布局
                "title": '为你推荐',
                "lookMore": "1",//是否显示更多
                "is_top": '0',//是否推荐
                "type": "auto", //auto自动获取  choose 手动选择
                "classifyId": 0, //所选分类id
                "width": '4',
                "limit": 4,//显示数量
                "style": "list", //list , slide
                "column": '2', //分裂数量
                "from": '1',//自动获取=1 手动选择=2
                "List": [],
                // 推荐分类
                "radios": [{ "label": '专项提升', "value": '1', "url": '', "cat": [2], "art": 1 },
                { "label": '小升初', "value": '2', "url": '', "cat": '', "art": '' },
                { "label": '艺术养成', "value": '3', "url": '', "cat": '', "art": '' },
                { "label": '教资教案', "value": '1', "url": '', "cat": '', "art": '' },],
                "list": [
                    {
                        "thumb": '',
                        "title": '',
                        "price": '1',
                        "level_name": '高级',
                        "sort_list": [{ name: '抖音' }, { name: '快手' }], "collect": false,
                        "dis_price": '1', "sales": 23,
                    },
                    {
                        "thumb": '',
                        "title": '',
                        "level_name": '高级',
                        "price": '2',
                        "dis_price": '2',
                        "sort_list": [{ name: '抖音' }, { name: '快手' }], "sales": 23, "collect": false,
                    },
                    {
                        "thumb": '',
                        "title": '',
                        "level_name": '高级',
                        "price": '3', "sales": 23, "collect": false,
                        "dis_price": '3'
                    },
                    {
                        "thumb": '',
                        "title": '', "level_name": '高级',
                        "price": '4', "sales": 23, "collect": false,
                        "dis_price": '4'
                    },
                    {
                        "thumb": '',
                        "title": '', "level_name": '高级',
                        "price": '5', "sales": 23, "collect": false,
                        "dis_price": '5'
                    }, {
                        "thumb": '',
                        "title": '', "level_name": '高级',
                        "price": '6', "sales": 23, "collect": false,
                        "dis_price": '6'
                    }, {
                        "thumb": '',
                        "title": '', "sales": 23,
                        "price": '7', "level_name": '高级', "collect": false,
                        "dis_price": '7'
                    }
                ]
            },
        },
        {
            "type": "resourceSort",
            "name": "资源分类",
            "value": {
                "limit": 8,//显示数量
                "L_height": '300',//一级菜单高度
                "R_height": '278',//二三级菜单高度
                "L_Bk_color": '#fff',//背景色
                "L_Txt_color": '#333',//字体色
                "L_Hover_bk": '#e8f2fe',//悬停背景色
                "R_Bk_color": '#fff',//背景色
                "R_Txt_color": '#333',//字体色
                "Size": '14',//字体大小
                "catList": catList,
            },
            "icon": "icon-ziyuan",
        }
    ],
    // 工具组件
    "utilsComponents": [{
        "type": "grid",
        "name": "栅格",
        "value": {
            "gutter": 3,//间隔
            "rows": [{
                "list": [],
                "col": 6, //宽度
                "height": '50',//高度
                "gradientDirection": "right",//渐变方向
                "endColor": "#fff",//结束颜色
                "startColor": "#fff",//起始颜色
            }, {
                "list": [],
                "col": 18, //宽度
                "height": '50', //高度
                "gradientDirection": "right",//渐变方向
                "endColor": "#fff",//结束颜色
                "startColor": "#fff",//起始颜色
            }],
        },
        "icon": "icon-grid"
    },
    {
        "type": "nav",
        "name": "导航栏",
        "value": {
            "height": 40,//组件高度
            "color": "#333",//字体颜色
            "gradientDirection": "right",//渐变方向
            "endColor": "#fff",//结束颜色
            "startColor": "#fff",//起始颜色
            "active_color": "#ff9933",//激活颜色
            "activeIndex": '1',
            "list": [
                // logo
                {
                    "image": default_logo,"status": 1
                },
                // 导航项
                {
                    "hobbies": [
                        {
                            "name": "首页",
                            "url": 'http://baidu.com',
                            'art': '',
                            "cat": '',
                            "tasks": [

                            ]
                        },
                        {
                            "name": "全部课程", "url": '2', "art": '',
                            "cat": '',
                            "tasks": [
                                {
                                    "name": "首页2-1", "url": '2-1', "art": '',
                                    "cat": '',
                                    "tasks": []
                                }
                            ]
                        },
                        {
                            "name": "会员特权", "url": '3', 'art': '',
                            "cat": '',
                            "tasks": []
                        },
                        {
                            "name": "精选文章", "url": '4', "art": '',
                            "cat": '',
                            "tasks": []
                        },
                        {
                            "name": "帮助中心", url: '5', "art": '',
                            "cat": '',
                            "tasks": []
                        }
                    ]
                },
                // 搜索框
                {
                    "input": {
                        "value": '请输入',
                        "radio": '1',//是否显示
                    }
                },
                // 登录/注册
                {
                    "login": {
                        "text": '登录注册',
                        "gradientDirection": "right",//渐变方向
                        "endColor": "#FFA500",//结束颜色
                        "startColor": "#FF8C00",//起始颜色
                        "color1": '#fff',//字体颜色
                        "size": 14,//字体
                        "radio": '1',//是否显示
                    }
                }
            ],

        },
        "icon": "icon-daohangzu"
    },

    {
        "type": "blank",
        "name": "辅助空白",
        "icon": 'icon-kongbai',
        "value": {
            "height": 20,//高度
            "gradientDirection": "right",//渐变方向
            "startColor": "#fff",//起始颜色
            "endColor": "#fff",//结束颜色
        },
    },
    {
        "type": "textarea",
        "name": "文本域",
        "value": '',
        "icon": 'icon-wenbenyu',
    },

    {
        "type": "Link",
        "name": "友情链接",
        "icon": 'icon-link',
        "value": {
            "list": YLink,
            "title": "友情链接",
            "limit": 3,//显示数量
            "lineHight": 26,//行高
            "gradientDirection": "right",//渐变方向
            "startColor": "#fff",//起始颜色
            "endColor": "#fff",//结束颜色
        },

    },
    {
        "type": "tabbar",
        "name": "底部",
        "value": {
            // 简介
            "intro": {
                "name": '关于',
                "radio": '1',//是否显示
                "list": [
                    { "value": '网站介绍', "linkValue": '1', "url": '', "cat": '', "art": '', },
                    { "value": '法律协议', "linkValue": '1', "url": '', "cat": '', "art": '', },
                    { "value": '隐私条款', "linkValue": '1', "url": '', "cat": '', "art": '', },
                ]
            },
            // 合作
            "partner": {
                "name": '合作',
                "radio": '1',//是否显示
                "list": [
                    { "value": '加入我们', "linkValue": '1', "url": '', "cat": '', "art": '', },
                    { "value": '联系我们', "linkValue": '1', "url": '', "cat": '', "art": '', },
                    { "value": '友情链接', "linkValue": '1', "url": '', "cat": '', "art": '', },
                ]
            },
            // 帮助
            "help": {
                "name": '帮助',
                "radio": '1',//是否显示
                "list": [
                    { "value": '帮助中心', "linkValue": '1', "url": '', "cat": '', "art": '', },
                    { "value": '常见问题', "linkValue": '1', "url": '', "cat": '', "art": '', },
                    { "value": '新手引导', "linkValue": '1', "url": '', "cat": '', "art": '', },
                ]
            },
            // 联系方式
            "contact": {
                "name": '联系方式',
                "radio": '1',//是否显示
                "list": [
                    { "name": '手机号:', "linkValue": '1', "url": '', "cat": '', "art": '', "value": '12556432', },
                    { "name": 'QQ号:', "linkValue": '1', "url": '', "cat": '', "art": '', "value": 'sddadffdczxc' },
                    { "name": '微信号:', "linkValue": '1', "url": '', "cat": '', "art": '', "value": '55454545' },]
            },
            "list": [
                // 联系客服
                {
                    "type": 'service',
                    "name": '联系客服',
                    "image": default_banner,
                    "status": 1,
                    "show": '1'//是否显示
                },
                // 微信公众号
                {
                    "type": 'servipublicce',
                    "name": '微信公众号',
                    "image": default_banner,
                    "status": 1,
                    "show": '1'//是否显示
                },
            ],
            "color": {
                "gradientDirection": "bottom",//渐变方向
                "startColor": "#fff", //起始颜色
                "endColor": "#fff",//结束颜色
            },
            // 版权
            "copy": {
                "CopyRight": ' Copyright © 2009-2015 智派科技 网络营销倡导者 www.zhipall.com All RightsReserved蜀ICP备19028294号-12'
            },
        },

        "icon": 'icon-list',
    },
    ],

};
// 深拷贝
var deepClone = function (obj) {
    let result = Array.isArray(obj) ? [] : {};
    for (let key in obj) {
        if (obj.hasOwnProperty(key)) {
            if (typeof obj[key] === 'object') {
                result[key] = deepClone(obj[key]); //递归复制
            } else {
                result[key] = obj[key];
            }
        }
    }
    return result;
};
Vue.prototype.bus = new Vue();
// 导航项
Vue.component('menu-item', {
    props: ['item'],
    template: '#menu-item',
    name: 'menu-item',
    data() {
        return {
            activeIndex: "1",
            menuData: {}
        };
    },
});
// 拖拽嵌套
Vue.component('nested-draggable', {
    name: 'nested-draggable',
    template: '#nested-draggable-template',
    props: {
        tasks: []
    },
    data() {
        return {
            props: { multiple: true },
            dialogVisible: false,
            activeIndex: null,
            formData: {
                value: '',
                art: '',
                cat: '',
                name: '',
                url: '',
            },
            form: {
                value: '1',
                art: '',
                cat: '',
                name: '',
                url: '',
            },
        };
    },
    methods: {
        // 编辑
        toggleInput(index) {
            if (this.activeIndex === index) {
                this.activeIndex = null;
            } else {
                this.activeIndex = index;
                this.formData.name = this.tasks[index].name;
                this.formData.url = this.tasks[index].url;
                this.formData.value = '1';
                this.formData.cat = this.tasks[index].cat;
                this.formData.art = this.tasks[index].art;
            }
        },
        // 保存
        save(index) {
            this.activeIndex = null;
            // 这里获取表单数据，并保存
            this.tasks[index].name = this.formData.name;
            this.tasks[index].value = '1';
            this.tasks[index].url = this.formData.url;
            this.tasks[index].cat = this.formData.cat;
            this.tasks[index].art = this.formData.art;
            this.$message({
                message: '保存成功!',
                type: 'success'
            });
        },
        // 取消编辑
        cancel(index) {
            this.activeIndex = null;
            this.$message({
                message: '取消编辑',
                type: 'info'
            });
        },
        // 删除
        removeName(index) {
            this.tasks.splice(index, 1);
        },
        // 添加
        AddName(index) {
            this.dialogVisible = true;
        },
        // 立即添加
        onSubmit(index) {
            this.dialogVisible = false;
            const name = this.form.name;
            /* splice(index - 1, 0, {})  参数1：要插入新元素的位置   参数2：0：表示不删除任何元素，只是插入新元素   参数3：要插入的新元素 */
            if (name !== '') {
                this.tasks.splice(index - 1, 0, {
                    name: name,
                    tasks: [],
                    value: this.form.value,
                    url: this.form.url,
                    cat: this.form.cat,
                    art: this.form.art
                });
                this.$message({
                    message: '添加成功',
                    type: 'success'
                });
                this.form.name = '';
                this.form.url = '';
                this.form.cat = '';
                this.form.art = '';
                this.form.value = '1';
            } else {
                this.form.name = '';
                this.form.url = '';
                this.form.cat = '';
                this.form.art = '';
                this.form.value = '1';
                this.$message({
                    message: '添加失败',
                    type: 'info'
                });
            }

        },
        // 取消添加
        canceled() {
            this.form.name = '';
            this.form.url = '';
            this.form.cat = '';
            this.form.art = '';
            this.form.value = '1';
            this.dialogVisible = false;
            this.$message({
                message: '添加失败',
                type: 'info'
            });
        },


    }
});
// 栅格
Vue.component('Grid', {
    template: '#Grid',
    name: 'Grid',
    props: ['list'],
    data() {
        return {
            GridData: [],
            selectWg: {},
            activeIndex: '1',
            pageConfig: [],
            openKey: '',
            clickKey: '',
            defaultProps: {
                children: 'tasks',
                label: 'name'
            },
        }
    },
    computed: {
        getNumber(val) {
            return function (val) {
                return Number(val);
            }
        },
        selected() {
            return this.selectWg.id == this.selected;
        },
    },
    mounted() {
        if (this.list) {
            for (var i = 0; i < this.list.length; i++) {
                var item = this.list[i];
                var elKey = Date.now() + '_' + Math.ceil(Math.random() * 1000000);
                item.key = item.type + '_' + elKey;
            }
            this.GridData = this.list;
        }
    },
    methods: {
        // 导航跳转
        handleSelect(key) {
            window.location.href = key;
        },
        // 资源分类 鼠标悬停
        handleMouseEnter(event) {
            const childNode = event.currentTarget.parentNode.querySelector('.child');
            const childone = event.currentTarget.parentNode.querySelector('.one');
            childNode.style.display = 'block';
            childone.style.backgroundColor = this.selectWg.value && this.selectWg.value.L_Hover_bk ? this.selectWg.value.L_Hover_bk : '#E8F2FE';

        },
        handleMouseLeave(event) {
            const childNode = event.currentTarget.parentNode.querySelector('.child');
            const childone = event.currentTarget.parentNode.querySelector('.one');
            childNode.style.display = 'none';
            childone.style.backgroundColor = this.selectWg.value && this.selectWg.value.L_Bk_color ? this.selectWg.value.L_Bk_color : '#FFFFFF';
        },
        handleChildMouseEnter(event) {
            event.currentTarget.style.display = 'block';
        },
        handleChildMouseLeave(event) {
            event.currentTarget.style.display = 'none';
        },
        // 选中项
        setSelectWg(data) {
            this.selectWg = data;
            this.bus.$emit('changeSelectWg', data);
            this.$emit('object-selected', data);
            // 资源组
            if (this.selectWg.type === 'resource') {
                this.selectWg.value.radio = '2';
            }
            // 图片轮播
            if (this.selectWg.type === 'imgSlide') {
                this.selectWg.value.radio = '2';
            }
            // 搜索框
            if (this.selectWg.type === 'search') {
                this.selectWg.radio = '2';
            }
            // 图片
            if (this.selectWg.type === 'imgSingle') {
                this.selectWg.value.radio = '2';
            }
            // 视频组
            if (this.selectWg.type === 'video') {
                this.selectWg.value.radio = '2';
            }
            // 文章组
            if (this.selectWg.type === 'article') {
                this.selectWg.value.show = '2';
            }
            // 常见问题
            if (this.selectWg.type === 'problem') {
                this.selectWg.value.radio = '2';
            }
        },
        // 拖拽添加
        handleWidgetAdd: function (evt) {
            var newIndex = evt.newIndex;
            var elKey = Date.now() + '_' + Math.ceil(Math.random() * 1000000);
            var newObj = deepClone(this.GridData[newIndex]);
            if (!newObj) {
                return;
            }
            newObj.key = this.GridData[newIndex].type + '_' + elKey;
            this.$set(this.GridData, newIndex, newObj);
            this.setSelectWg(this.GridData[newIndex]);
        },
        // 点击添加
        handleClickAdd: function (obj) {
            var elKey = Date.now() + '_' + Math.ceil(Math.random() * 1000000);
            var newObj = deepClone(obj);
            newObj.key = obj.type + '_' + elKey;
            var newIndex = this.GridData.length || 0;
            this.$set(this.GridData, newIndex, newObj);
            this.setSelectWg(this.GridData[newIndex]);
        },
        handleSelectWidget(index, event) {
            event.stopPropagation();
            this.setSelectWg(this.GridData[index]);
        },
        handleSelectRecord(index) {
            this.setSelectWg(this.GridData[index]);
        },
        deleteWidget(index) {
            if (this.GridData.length - 1 === index) {
                if (index === 0) {
                    this.setSelectWg([]);
                } else {
                    this.setSelectWg(this.GridData[index - 1]);
                }
            } else {
                this.setSelectWg(this.GridData[index + 1]);
            }
            this.$nextTick(() => {
                this.GridData.splice(index, 1);
            })
        },
        //中间 删除组件
        handleWidgetDelete: function (deleteIndex) {
            this.$confirm('此操作将永久删除该小部件, 是否继续?', '提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning'
            }).then(() => {
                this.GridData.splice(deleteIndex, 1);
                this.$message({
                    type: 'success',
                    message: '删除成功!'
                });
            }).catch(() => {
                this.$message({
                    type: 'info',
                    message: '已取消删除'
                });
            });
        },
        //中间 复制组件
        handleWidgetClone(index) {
            let cloneData = deepClone(this.GridData[index]);
            cloneData.key = this.GridData[index].type + '_' + Date.now() + '_' + Math.ceil(Math.random() * 1000000);
            this.GridData.splice(index, 0, cloneData);
            this.$nextTick(() => {
                this.setSelectWg(this.GridData[index + 1]);
            })
        },
        handleDragRemove: function (evt) {
            this.setSelectWg({});
        },

    }
});
// 中间
Vue.component('layout', {
    template: '#layout',
    name: 'layout',
    data() {
        return {
            menu_item_index: -1,
            pageData: [],
            selectWg: {},
            pageConfig: [],
            activeIndex: '',
            openKey: '',
            clickKey: '',
            defaultProps: {
                children: 'tasks',
                label: 'name'
            },
        }
    },

    computed: {
        getNumber(val) {
            return function (val) {
                return Number(val);
            }
        },
        selected() {
            return this.selectWg.id == this.selected;
        },

    },
    mounted() {
        if (pageConfig.length > 0) {
            for (var i = 0; i < pageConfig.length; i++) {
                var item = pageConfig[i];
                var elKey = Date.now() + '_' + Math.ceil(Math.random() * 1000000);
                item.key = item.type + '_' + elKey;
            }
            this.pageData = pageConfig;
        };
    },
    methods: {
        // 导航跳转
        handleSelect(key) {
            window.location.href = key;
        },
        // 资源分类 鼠标悬停
        handleMouseEnter(event) {
            const childNode = event.currentTarget.parentNode.querySelector('.child');
            const childone = event.currentTarget.parentNode.querySelector('.one');
            childNode.style.display = 'block';
            childone.style.backgroundColor = this.selectWg.value && this.selectWg.value.L_Hover_bk ? this.selectWg.value.L_Hover_bk : '#E8F2FE';

        },
        handleMouseLeave(event) {
            const childNode = event.currentTarget.parentNode.querySelector('.child');
            const childone = event.currentTarget.parentNode.querySelector('.one');
            childNode.style.display = 'none';
            childone.style.backgroundColor = this.selectWg.value && this.selectWg.value.L_Bk_color ? this.selectWg.value.L_Bk_color : '#FFFFFF';
        },
        handleChildMouseEnter(event) {
            event.currentTarget.style.display = 'block';
        },
        handleChildMouseLeave(event) {
            event.currentTarget.style.display = 'none';
        },
        // 选中项
        setSelectWg(data) {
            this.selectWg = data;
            this.bus.$emit('changeSelectWg', data);
            // 资源组
            if (this.selectWg.type === 'resource') {
                this.selectWg.value.radio = '1';
            }
            // 图片轮播
            if (this.selectWg.type === 'imgSlide') {
                this.selectWg.value.radio = '1';
            }
            // search
            if (this.selectWg.type === 'search') {
                this.selectWg.radio = '1';
            }
            // 图片
            if (this.selectWg.type === 'imgSingle') {
                this.selectWg.value.radio = '1';
            }
            // 视频组
            if (this.selectWg.type === 'video') {
                this.selectWg.value.radio = '1';
            }
            // 文章组
            if (this.selectWg.type === 'article') {
                this.selectWg.value.radio = '1';
            }
            // 常见问题
            if (this.selectWg.type === 'problem') {
                this.selectWg.value.radio = '1';
            }
        },
        // 拖拽添加
        handleWidgetAdd: function (evt) {
            var newIndex = evt.newIndex;
            var elKey = Date.now() + '_' + Math.ceil(Math.random() * 1000000);
            var newObj = deepClone(this.pageData[newIndex]);
            newObj.key = this.pageData[newIndex].type + '_' + elKey;
            this.$set(this.pageData, newIndex, newObj);
            this.setSelectWg(this.pageData[newIndex]);
        },
        // 点击添加
        handleClickAdd: function (obj) {
            var elKey = Date.now() + '_' + Math.ceil(Math.random() * 1000000);
            var newObj = deepClone(obj);
            newObj.key = obj.type + '_' + elKey;
            var newIndex = this.pageData.length || 0;
            this.$set(this.pageData, newIndex, newObj);
            this.setSelectWg(this.pageData[newIndex]);
        },
        handleSelectWidget(index, event) {
            this.setSelectWg(this.pageData[index]);
        },
        handleSelectRecord(index) {
            this.setSelectWg(this.pageData[index]);
        },
        deleteWidget(index) {
            if (this.pageData.length - 1 === index) {
                if (index === 0) {
                    this.setSelectWg([]);
                } else {
                    this.setSelectWg(this.pageData[index - 1]);
                }
            } else {
                this.setSelectWg(this.pageData[index + 1]);
            }
            this.$nextTick(() => {
                this.pageData.splice(index, 1);
            })
        },
        //中间 删除组件
        handleWidgetDelete: function (deleteIndex) {
            this.$confirm('此操作将永久删除该小部件, 是否继续?', '提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning'
            }).then(() => {
                this.pageData.splice(deleteIndex, 1);
                this.$message({
                    type: 'success',
                    message: '删除成功!'
                });
            }).catch(() => {
                this.$message({
                    type: 'info',
                    message: '已取消删除'
                });
            });
        },
        //中间 复制组件
        handleWidgetClone(index) {
            let cloneData = deepClone(this.pageData[index]);
            cloneData.key = this.pageData[index].type + '_' + Date.now() + '_' + Math.ceil(Math.random() * 1000000);
            this.pageData.splice(index, 0, cloneData);
            this.$nextTick(() => {
                this.setSelectWg(this.pageData[index + 1]);
            })
        },
        handleDragRemove: function (evt) {
            this.setSelectWg({});
        },

    }
});
// 图片上传
Vue.component('upload-img', {
    template: "#upload-img",
    props: ['index', 'item', 'tags'],
    methods: {
      upload: function () {
        this.$emit('upload-img');
      },
      uploadImg() {
        layer.open({
          title: '选择图片',
          type: 2,
          maxmin: true,
          area: ['100%', '100%'],
          content: `${upload_manage}?index=${this.index}&tags=${this.tags}`
        });
      },
    }
  });
// 右边
Vue.component('layout-config', {
    template: '#layout-config',
    name: 'LayoutConfig',

    data: function () {
        return {
            props: {
                tasks: {
                    required: true,
                    type: Array
                }
            },
            referrerIndex: -1,
            problemIndex: -1,
            contactIndex: -1,
            introIndex: -1,
            helpIndex: -1,
            partnerIndex: -1,
            activeIndex: null,
            visible: false,
            dialogVisible: false,
            selectWg: {},
            _editocoverr: null,
            editor: null,
            WidgetType: {
                nav: '导航栏',
                grid: '栅格',
                problem: '常见问题',
                imgSlide: '图片轮播',
                imgSingle: '图片',
                imgWindow: '图片分组',
                video: '视频组',
                article: '文章组',
                search: '搜索框',
                notice: '公告组',
                resource: '资源组',
                blank: '辅助空白',
                textarea: '文本域',
                resourceSort: '资源分类',
                tabbar: '底部',
                Link: '友情链接',
            },
            selectedIndex: '',
            multipleSelection: [],
            maxSelection: 10,
            maxNoticeNums: 5, //选择公告最多数量
            selectedValue: [], // 存储选中的value值
            selectedLabels: [], // 存储选中的label值
            isGoodsItemVisible: false,
            contactData: {
                linkValue: '1',
                art: '',
                cat: '',
                tempName: '',
                value: '',
                url: '',
            },
            introItemData: {
                linkValue: '1',
                art: '',
                cat: '',
                value: '',
                url: '',
            },
            partnerData: {
                linkValue: '1',
                art: '',
                cat: '',
                value: '',
                url: '',
            },
            helpItemData: {
                linkValue: '1',
                art: '',
                cat: '',
                value: '',
                url: '',
            },
            problemData: {
                linkValue: '1',
                tempName: '',
                art: '',
                cat: '',
                value: '',
                url: '',
            },
            referrerData: {
                tempName: '',
                art: '',
                cat: '',
                value: '1',
                url: '',
            },
        }
    },
    watch: {
        selectWg(newVal, oldVal) {
            if (newVal.type === 'textarea') {
                var that = this;
                this.$nextTick(function () {
                    if (!this.editor) {
                        this.editor = UE.getEditor('container');
                    }
                    this.editor.ready(function () {
                        that.editor.setContent(that.selectWg.value);
                        that.editor.addListener("contentChange", function () {
                            var content = that.editor.getContent();
                            that.selectWg.value = content;
                        }.bind(that))
                    }.bind(this))
                })
            } else {
                if (this.editor) {
                    this.editor.destroy();
                    this.editor = null;
                }
            }
        }
    },
    mounted() {
        var that = this;
        this.bus.$on('changeSelectWg', function (data) {
            that.selectWg = data;
        });
        // 富文本图片
        that.$nextTick(function () {
            // var _editocoverr = UE.getEditor("edit_cover", {
            //     initialFrameWidth: 800,
            //     initialFrameHeight: 300,
            //     zIndex: 19891026,
            //     single: false
            // });
            // that._editocoverr = _editocoverr;
            // that._editocoverr.ready(function () {
            //     that._editocoverr.hide();
            //     that._editocoverr.addListener('beforeInsertImage', function (t, arg) {
            //         var obj = that._editocoverr.queryCommandValue("serverparam");
            //         if (arg[0].src.indexOf('http') > '-1') {
            //             that.$set(that.selectWg.value.list[obj.index], obj.tags, arg[0].src);
            //         } else {
            //             var domain = window.location.protocol + '//' + window.location.host;
            //             that.$set(that.selectWg.value.list[obj.index], obj.tags, domain + arg[0].src);
            //         }
            //     }.bind(that));
            // });
              // 监听自定义事件
        window.addEventListener("userLoggedIn", function (e) {
            const url=e.detail.url;
            const index=e.detail.index;
            const tags=e.detail.tags;
            if(tags=='image_on'){
                that.selectWg.value.list[index].image_on=url;
            }else{
                that.selectWg.value.list[index].image=url;
            }
        });
            layui.use(['table'], function () {
                var table = layui.table;
                //监听资源列表页工具条
                table.on('tool(goodsTable)', function (obj) { //注：tool是工具条事件名，test是table原始容器的属性 lay-filter="对应的值"
                    var data = obj.data; //获得当前行数据
                    var layEvent = obj.event; //获得 lay-event 对应的值（也可以是表头的 event 参数对应的值）
                    var tr = obj.tr; //获得当前行 tr 的DOM对象
                    if (layEvent === 'selectGoods') { //选择
                        that.$set(that.selectWg.value.List[that.currentItemIndex], 'linkValue', data.id + '|' + data.type);
                        layer.close(window.box);
                    }
                });
                //监听资源分类列表页工具条
                table.on('tool(sortTable)', function (obj) { //注：tool是工具条事件名，test是table原始容器的属性 lay-filter="对应的值"
                    var data = obj.data; //获得当前行数据
                    var layEvent = obj.event; //获得 lay-event 对应的值（也可以是表头的 event 参数对应的值）
                    var tr = obj.tr; //获得当前行 tr 的DOM对象
                    if (layEvent === 'selectSorts') { //选择
                        that.$set(that.selectWg.value.list[that.currentItemIndex], 'linkValue', data.id);
                        layer.close(window.box);
                    }
                });

                // 监听文章列表页工具条
                table.on('tool(articleTable)', function (obj) { //注：tool是工具条事件名，test是table原始容器的属性 lay-filter="对应的值"
                    var data = obj.data; //获得当前行数据
                    var layEvent = obj.event; //获得 lay-event 对应的值（也可以是表头的 event 参数对应的值）
                    var tr = obj.tr; //获得当前行 tr 的DOM对象
                    if (layEvent === 'selectArticle') { //选择
                        // 文章
                        if (that.selectWg.type === 'article') {
                            if (that.selectWg.value.List && Array.isArray(that.selectWg.value.List)) {
                                var index = that.selectWg.value.List.length;
                                that.$set(that.selectWg.value.List, index, data);
                            }
                        }
                        // 常见问题
                        if (that.selectWg.type === 'problem') {
                            if (that.selectWg.value.List && Array.isArray(that.selectWg.value.List)) {
                                var index = that.selectWg.value.List.length;
                                that.$set(that.selectWg.value.List, index, data);
                            }
                        } else {
                            // that.$set(that.selectWg.value.list[that.currentItemIndex], 'linkValue', data.id);
                        }
                        layer.close(window.box);
                    }
                });

                //监听前端页面列表页工具条
                table.on('tool(pagesTable)', function (obj) { //注：tool是工具条事件名，test是table原始容器的属性 lay-filter="对应的值"
                    var data = obj.data; //获得当前行数据
                    var layEvent = obj.event; //获得 lay-event 对应的值（也可以是表头的 event 参数对应的值）
                    var tr = obj.tr; //获得当前行 tr 的DOM对象
                    if (layEvent === 'selectPages') { //选择
                        that.$set(that.selectWg.value.list[that.currentItemIndex], 'linkValue', data.path);
                        layer.close(window.box);
                    }
                });

                // 监听文章分类列表页工具条
                table.on('tool(articleTypeTable)', function (obj) {
                    var data = obj.data;
                    var layEvent = obj.event;
                    var tr = obj.tr;
                    if (layEvent === 'selectType') { //选择
                        if (that.selectWg.type === 'article') {
                            that.selectWg.value.articleSortId = data.id
                        } else {
                            that.$set(that.selectWg.value.list[that.currentItemIndex], 'linkValue', data.id)
                        }
                        layer.close(window.box);
                    }
                });

                // 监听表单列表页工具条
                table.on('tool(formTable)', function (obj) {
                    var data = obj.data;
                    var layEvent = obj.event;
                    var tr = obj.tr;
                    if (layEvent === 'selectform') { //选择
                        that.$set(that.selectWg.value.list[that.currentItemIndex], 'linkValue', data.id);
                        layer.close(window.box);
                    }
                });
            });
        });
        // 分类数据 递归
        // function removeEmptyChild(obj) {
        //     for (var i = 0; i < obj.length; i++) {
        //         const child = obj[i].childlist;
        //         if (Array.isArray(child) && child.length < 1) {
        //             delete obj[i].childlist;
        //         } else if (Array.isArray(child)) {
        //             removeEmptyChild(child); // 递归调用自身处理嵌套的数组
        //         }
        //     }
        // }
        // removeEmptyChild(catList);
    },

    methods: {
        // 文章组
        Article(index) {
            this.selectWg.value.List.splice(index, 1);
        },
        /*** 资源组  */
        //   保存
        selectResource(index) {
            this.selectedLabels[index] = this.selectWg.value.catList[index].childlist
                .filter(item => this.selectedValue[index].includes(item.id))
                .map(item => item);
            this.selectWg.value.catList[index].group = this.selectedLabels[index];
            this.activeIndex = null;
            this.selectedValue[index] = '';
            this.$message({
                message: '保存成功!',
                type: 'success'
            });
        },
        //   取消
        CancelResource(index) {
            this.activeIndex = null;
            this.selectedValue[index] = '';
            this.$message({
                message: '取消编辑',
                type: 'info'
            });
        },
        // 切换
        ResourceBtn(index) {
            if (this.activeIndex === index) {
                this.activeIndex = null;
            } else {
                this.activeIndex = index;
            }
        },
        // 栅格
        Movecol(index) {
            this.selectWg.value.rows.splice(index, 1);
        },
        Addcol() {
            this.selectWg.value.rows.push({
                list: [], col: '24', height: '50', "gradientDirection": "right",
                "endColor": "#fff",
                "startColor": "#fff",
            })
        },
        /*** 资源组 为你推荐 */
        // 删除
        removeRerrer(index) {
            this.selectWg.value.radios.splice(index, 1);
        },
        // 添加
        AddReferrer() {
            this.selectWg.value.radios.push({ label: '', value: '1', url: '', cat: '', art: '' });
        },
        // 切换
        ToggleReferrer(index) {
            if (this.referrerIndex === index) {
                this.referrerIndex = -1;
            } else {
                this.referrerIndex = index;
                this.referrerData.tempName = this.selectWg.value.radios[index].label;
                this.referrerData.value = this.selectWg.value.radios[index].value;
                this.referrerData.url = this.selectWg.value.radios[index].url;
                this.referrerData.art = this.selectWg.value.radios[index].art;
                this.referrerData.cat = this.selectWg.value.radios[index].cat;
            }
        },
        // 保存
        SaveReferrer(index) {
            this.referrerIndex = -1;
            this.selectWg.value.radios[index].label = this.referrerData.tempName;
            this.selectWg.value.radios[index].value = this.referrerData.value;
            this.selectWg.value.radios[index].url = this.referrerData.url;
            this.selectWg.value.radios[index].art = this.referrerData.art;
            this.selectWg.value.radios[index].cat = this.referrerData.cat;
            this.$message({
                message: '保存成功!',
                type: 'success'
            });
        },
        // 取消
        CancelReferrer(index) {
            this.referrerIndex = -1;
            this.$message({
                message: '取消编辑',
                type: 'info'
            });
        },
        /***资源组 资源来源*/
        //删除
        RemoveForm(index) {
            this.selectWg.value.List.splice(index, 1);
        },

        //常见问题 删除
        removeProblem(index) {
            this.selectWg.value.List.splice(index, 1);
        },
        // 切换
        Toggleproblem(index) {
            if (this.problemIndex === index) {
                this.problemIndex = -1;
            } else {
                this.problemIndex = index;
                this.problemData.tempName = this.selectWg.value.list[index].label;
                this.problemData.value = this.selectWg.value.list[index].value;
                this.problemData.linkValue = this.selectWg.value.list[index].linkValue;
                this.problemData.url = this.selectWg.value.list[index].url;
                this.problemData.art = this.selectWg.value.list[index].art;
                this.problemData.cat = this.selectWg.value.list[index].cat;
            }
        },
        // 保存
        SaveProblem(index) {
            this.problemIndex = -1;
            this.selectWg.value.list[index].label = this.problemData.tempName;
            this.selectWg.value.list[index].value = this.problemData.value;
            this.selectWg.value.list[index].linkValue = this.problemData.linkValue;
            this.selectWg.value.list[index].url = this.problemData.url;
            this.selectWg.value.list[index].art = this.problemData.art;
            this.selectWg.value.list[index].cat = this.problemData.cat;
            this.$message({
                message: '保存成功',
                type: 'success'
            });
        },
        // 取消
        CancelProblem(index) {
            this.problemIndex = -1;
            this.$message({
                message: '取消编辑',
                type: 'info'
            });
        },
        /***底部 联系方式  */
        // 添加
        AddContact() {
            this.selectWg.value.contact.list.push({ name: '', linkValue: '1', url: '', art: '', cat: '', value: '' })
        },
        // 删除
        RemoveContact(index) {
            this.selectWg.value.contact.list.splice(index, 1);
        },
        // 切换
        ToggleContact(index) {
            if (index === this.contactIndex) {
                this.contactIndex = -1;
            } else {
                this.contactIndex = index;
                this.contactData.tempName = this.selectWg.value.contact.list[index].name;
                this.contactData.url = this.selectWg.value.contact.list[index].url;
                this.contactData.art = this.selectWg.value.contact.list[index].art;
                this.contactData.cat = this.selectWg.value.contact.list[index].cat;
                this.contactData.linkValue = this.selectWg.value.contact.list[index].linkValue;
                this.contactData.value = this.selectWg.value.contact.list[index].value;
            }
        },
        // 保存
        SaveContact(index) {
            this.contactIndex = -1;
            this.selectWg.value.contact.list[index].name = this.contactData.tempName;
            this.selectWg.value.contact.list[index].url = this.contactData.url;
            this.selectWg.value.contact.list[index].art = this.contactData.art;
            this.selectWg.value.contact.list[index].cat = this.contactData.cat;
            this.selectWg.value.contact.list[index].linkValue = this.contactData.linkValue;
            this.selectWg.value.contact.list[index].value = this.contactData.value;
            this.$message({
                message: '保存成功',
                type: 'success'
            });
        },
        // 取消
        CancelContact(index) {
            this.contactIndex = -1;
            this.$message({
                message: '取消编辑',
                type: 'info'
            });
        },
        /***底部 简介  */
        //  添加
        AddIntro() {
            this.selectWg.value.intro.list.push({ value: '', "linkValue": '1', "url": '', "cat": '', "art": '', })
        },
        // 删除
        RemoveIntro(index) {
            this.selectWg.value.intro.list.splice(index, 1);
        },
        // 切换
        ToggleIntro(index) {
            if (index === this.introIndex) {
                this.introIndex = -1;
            } else {
                this.introIndex = index;
                this.introItemData.art = this.selectWg.value.intro.list[index].art
                this.introItemData.cat = this.selectWg.value.intro.list[index].cat
                this.introItemData.linkValue = this.selectWg.value.intro.list[index].linkValue
                this.introItemData.url = this.selectWg.value.intro.list[index].url
                this.introItemData.value = this.selectWg.value.intro.list[index].value
            }
        },
        // 保存
        SaveIntro(index) {
            this.introIndex = -1;
            this.selectWg.value.intro.list[index].art = this.introItemData.art
            this.selectWg.value.intro.list[index].cat = this.introItemData.cat
            this.selectWg.value.intro.list[index].linkValue = this.introItemData.linkValue
            this.selectWg.value.intro.list[index].url = this.introItemData.url
            this.selectWg.value.intro.list[index].value = this.introItemData.value
            this.$message({
                message: '保存成功!',
                type: 'success'
            });
        },
        // 取消
        CancelIntro(index) {
            this.introIndex = -1;
            this.$message({
                message: '取消编辑',
                type: 'info'
            });
        },
        /***底部 合作  */
        //  添加
        AddPartner() {
            this.selectWg.value.partner.list.push({ value: '', "linkValue": '1', "url": '', "cat": '', "art": '', })
        },
        // 删除
        RemovePartner(index) {
            this.selectWg.value.partner.list.splice(index, 1);
        },
        // 切换
        TogglePartner(index) {
            if (index === this.partnerIndex) {
                this.partnerIndex = -1;
            } else {
                this.partnerIndex = index;
                this.partnerData.art = this.selectWg.value.partner.list[index].art;
                this.partnerData.cat = this.selectWg.value.partner.list[index].cat;
                this.partnerData.linkValue = this.selectWg.value.partner.list[index].linkValue;
                this.partnerData.url = this.selectWg.value.partner.list[index].url;
                this.partnerData.value = this.selectWg.value.partner.list[index].value;
            }
        },
        // 保存
        SavePartner(index) {
            this.partnerIndex = -1;
            this.selectWg.value.partner.list[index].art = this.partnerData.art;
            this.selectWg.value.partner.list[index].cat = this.partnerData.cat;
            this.selectWg.value.partner.list[index].linkValue = this.partnerData.linkValue;
            this.selectWg.value.partner.list[index].url = this.partnerData.url;
            this.selectWg.value.partner.list[index].value = this.partnerData.value;
            this.$message({
                message: '保存成功!',
                type: 'success'
            });
        },
        // 取消
        CancelPartner(index) {
            this.partnerIndex = -1;
            this.$message({
                message: '取消编辑',
                type: 'info'
            });
        },
        /***底部 帮助  */
        //添加
        AddHelp() {
            this.selectWg.value.help.list.push({ value: '', "linkValue": '1', "url": '', "cat": '', "art": '', })
        },
        //删除
        RemoveHelp(index) {
            this.selectWg.value.help.list.splice(index, 1);
        },
        // 切换
        ToggleHelp(index) {
            if (index === this.helpIndex) {
                this.helpIndex = -1;
            } else {
                this.helpIndex = index;
                this.helpItemData.art = this.selectWg.value.help.list[index].art;
                this.helpItemData.cat = this.selectWg.value.help.list[index].cat;
                this.helpItemData.linkValue = this.selectWg.value.help.list[index].linkValue;
                this.helpItemData.url = this.selectWg.value.help.list[index].url;
                this.helpItemData.value = this.selectWg.value.help.list[index].value;
            }
        },
        // 保存
        SaveHelp(index) {
            this.helpIndex = -1;
            this.selectWg.value.help.list[index].art = this.helpItemData.art;
            this.selectWg.value.help.list[index].cat = this.helpItemData.cat;
            this.selectWg.value.help.list[index].linkValue = this.helpItemData.linkValue;
            this.selectWg.value.help.list[index].url = this.helpItemData.url;
            this.selectWg.value.help.list[index].value = this.helpItemData.value;
            this.$message({
                message: '保存成功!',
                type: 'success'
            });
        },
        // 取消
        CancelHelp(index) {
            this.helpIndex = -1;
            this.$message({
                message: '取消编辑',
                type: 'info'
            });
        },

        // 右边组件 名字
        getSelectWgName: function (type) {
            return this.WidgetType[type] || '';
        },
        setSelectWg(data) {
            this.selectWg = data;
            this.bus.$emit('changeSelectWg', data);
        },
        // 导航栏 添加导航项
        AddNav() {
            const hobbiesIndex = this.selectWg.value.list.findIndex(item => Array.isArray(item.hobbies));
            if (hobbiesIndex !== -1) {
                this.selectWg.value.list[hobbiesIndex].hobbies.push({ value: '' });
            }
        },
        //导航栏  删除导航项
        removeNav(index) {
            const hobbiesIndex = this.selectWg.value.list.findIndex(item => Array.isArray(item.hobbies));
            if (hobbiesIndex !== -1) {
                this.selectWg.value.list[hobbiesIndex].hobbies.splice(index, 1);
            }
        },

        //导航栏 登录注册 字体
        checkSize() {
            const loginIndex = this.selectWg.value.list.findIndex(item => item.hasOwnProperty('login'));
            let loginSize = loginIndex !== -1 ? this.selectWg.value.list[loginIndex].login.size : null;

            if (isNaN(loginSize) || loginSize < 12 || loginSize > 20) {
                loginSize = 12;
                this.selectWg.value.list[loginIndex].login.size = loginSize;
                console.log(loginSize); // 输出：12
                this.$message('尺寸必须在 12 到 20 之间');
            }
        },
        // 图片轮播 删除按钮
        handleSlideRemove: function (index) {
            this.selectWg.value.list.splice(index, 1);
        },
        // 图片轮播 添加按钮
        handleAddSlide: function () {
            this.selectWg.value.list.push({
                linkValue: '1',
                url: '',
                cat: '',
                art: '',
                image: default_banner,
                status: 1
            });
        },
        // 图片分组 添加按钮
        handleAddPic: function () {
            this.selectWg.value.list.push({
                linkValue: '1',
                url: '',
                cat: '',
                art: '',
                image: default_banner,
                status: 1
            });
        },
        // 图片分组 删除按钮
        handleRemove: function (index) {
            this.selectWg.value.list.splice(index, 1);
        },
        chooseLink: function (index, type) {
            this.currentItemIndex = index;
            this.$set(this.selectWg.value.list[index], 'linkType', type);
            switch (+type) {
                case 2://课程资源
                    this.spread_list();
                    break;
                case 3://资源分类
                    this.spread_sort();
                    break;
                case 4://文章信息
                    this.article_list();
                    break;
                case 6://前端页面
                    this.pages_list();
                    break;
                default:
                    break;
            }
        },
        // 资源组
        selectGoods: function () {
            var that = this;
            layui.use(['form', 'table'], function () {
                layui.layer.open({
                    type: 2,
                    content: api_tagSpread,
                    area: ["800px", "480px"],
                    title: "选择资源",
                    btn: ["完成", "取消"],
                    yes: function (index, layero) {
                        var valList = layer.getChildFrame('#vallist', index).val();
                        if (valList) {
                            valList = JSON.parse(valList);
                        }
                        //判断个数是否满足
                        if (Object.getOwnPropertyNames(valList).length > that.maxSelectGoods) {
                            layer.msg("最多只能选择" + that.maxSelectGoods + "个");
                            return false;
                        }
                        var list = [];
                        for (let i in valList) {
                            list.push(valList[i]);
                        }
                        that.hasChooseGoods = list;
                        that.$set(that.selectWg.value, 'List', list);
                        layer.close(index);
                    }
                })
            });
        },
        spread_list: function () {
            JsGet(api_ResourceList, function (e) {
                window.box = layer.open({
                    type: 1,
                    content: e,
                    area: ['700px', '450px'],
                    title: '资源列表'
                });
            }, false, true, 'html');
        },
        spread_sort: function () {
            JsGet(api_ResourceSort, function (e) {
                window.box = layer.open({
                    type: 1,
                    content: e,
                    area: ['700px', '450px'],
                    title: '资源分类'
                });
            }, false, true, 'html');
        },
        help_list: function () {
            JsGet(api_getArticle + '?type=1', function (e) {
                window.box = layer.open({
                    type: 1,
                    content: e,
                    area: ['800px', '450px'],
                    title: '常见问题'
                });
            }, false, true, 'html');
        },
        article_list: function () {
            JsGet(api_getArticle, function (e) {
                window.box = layer.open({
                    type: 1,
                    content: e,
                    area: ['800px', '450px'],
                    title: '文章列表'
                });
            }, false, true, 'html');
        },
        pages_list: function () {
            JsGet(api_getPages, function (e) {
                window.box = layer.open({
                    type: 1,
                    content: e,
                    area: ['800px', '450px'],
                    title: '前端页面'
                });
            }, false, true, 'html');
        },
        article_sort_list: function () {
            JsGet(api_getArticleSort, function (e) {
                window.box = layer.open({
                    type: 1,
                    content: e,
                    area: ['700px', '450px'],
                    title: '文章分类列表'
                })
            })
        },
        // 公告组删除
        handleDeleteNotice: function (index) {
            this.selectWg.value.List.splice(index, 1);
        },
        // 选择公告
        selectNotice: function () {
            var that = this;
            layui.use(['form', 'table'], function () {
                layui.layer.open({
                    type: 2,
                    content: api_tagNotice,
                    area: ["800px", "500px"],
                    title: "选择公告",
                    btn: ["完成", "取消"],
                    yes: function (index, layero) {
                        var valList = layer.getChildFrame('#vallist', index).val();
                        if (valList) {
                            valList = JSON.parse(valList);
                        }
                        var list = [];
                        for (let i in valList) {
                            list.push(valList[i]);
                        }
                        that.$set(that.selectWg.value, 'List', list);
                        layer.close(index);
                    }
                });
            });
        },
        handleClose(done) {
            this.$confirm('确认关闭？')
                .then(_ => {
                    done();
                })
                .catch(_ => { });
        },
    }
});

// 全局
new Vue({
    el: '#app',
    data: {},
    components: {
        "home": {
            template: "#home",
            data() {
                return {
                    storeComponents: allWidget.storeComponents,
                    utilsComponents: allWidget.utilsComponents,
                    mediaComponents: allWidget.mediaComponents,
                    saveUrl: saveUrl
                }
            },
            methods: {
                // 保存页面
                savePage: function () {
                    var data = {
                        data: JSON.stringify(this.$refs.layout.pageData),
                        pageCode: pageCode
                    };
                    JsPost(this.saveUrl, data, function (res) {
                        if (res.status) {
                            layer.msg(res.msg, { time: 1300 });
                        } else {
                            layer.msg(res.msg);
                        }
                    });
                },
                selectWidget: function (type) {
                    for (var key in allWidget) {
                        for (var index = 0; index < allWidget[key].length; index++) {
                            var element = allWidget[key][index];
                            if (element.type === type) {
                                this.$refs.layout.handleClickAdd(element);
                            }
                        }
                    }
                }
            },
            mounted() {
                var that = this;
                layui.use(['form', 'laytpl'], function () {
                    form = layui.form;
                });
            },
        }
    }
});