var allWidget = {
    "mediaComponents": [
        {
            "type": "imgSlide",
            "name": "图片轮播",
            "value": {
                "duration": 2500,
                "list": [{
                    "image": default_banner,
                    "linkType": '',
                    "linkValue": '',
                    "status": 1
                },
                {
                    "image": default_banner,
                    "linkType": '',
                    "linkValue": '',
                    "status": 1
                }
                ]
            },
            "icon": "icon-lunbotu"
        },
        {
            "type": "imgSingle",
            "name": "图片",
            "value": {
                "list": [{
                    "image": default_banner,
                    "status": 1,
                    "linkType": '',
                    "linkValue": '',
                    "buttonShow": false,
                    "buttonText": '',
                    "buttonColor": "#FFFFFF",
                    "textColor": "#000000"
                }]
            },
            "icon": "icon-tupian_huaban"
        },
        {
            "type": "imgWindow",
            "name": "图片分组",
            "value": {
                "style": 2,  // 0 橱窗  2 两列 3三列 4四列
                "margin": 0,
                "list": [
                    {
                        "image": default_banner,
                        "linkType": '',
                        "linkValue": '',
                        "status": 1
                    },
                    {
                        "image": default_banner,
                        "linkType": '',
                        "linkValue": '',
                        "status": 1
                    }, {
                        "image": default_banner,
                        "linkType": '',
                        "linkValue": '',
                        "status": 1
                    },
                    {
                        "image": default_banner,
                        "linkType": '',
                        "linkValue": '',
                        "status": 1
                    }
                ]
            },
            "icon": "icon-changjingfenzu"
        },
        {
            "type": "video",
            "name": "视频组",
            "value": {
                "autoplay": "false",
                "list": [{
                    "image": default_banner,
                    "url": "http://wxsnsdy.tc.qq.com/105/20210/snsdyvideodownload?filekey=30280201010421301f0201690402534804102ca905ce620b1241b726bc41dcff44e00204012882540400",
                    "linkType": '',
                    "linkValue": '',
                    "status": 1
                }]
            },
            "icon": "icon-shipin"
        },
        {
            "type": "article",
            "name": "文章组",
            "value": {
                "list": [
                    {
                        "title": '',
                        "thumb":''
                    }
                ]
            },
            "icon": "icon-wenzhang_huaban"
        },
        {
            "type": "articleList",
            "name": "文章列表",
            "value": {
                "limit": 3,
                "articleSortId": '',
                "list": [],
            },
            "icon": "icon-wenzhangfenlei"
        }
    ],
    "storeComponents": [
        {
            "type": "search",
            "name": "搜索框",
            "value": {
                "keywords": '请输入关键字搜索',
                "style": 'round' // round:圆弧 radius:圆角 square:方形
            },
            "icon": "icon-sousuo"
        },
        {
            "type": "notice",
            "name": "公告组",
            "value": {
                "type": 'auto', //choose手动选择， auto 自动获取
                "list": [
                    {
                        "title": "这里是第一条公告的标题",
                        "content": "",
                        "id": ''
                    }
                ]
            },
            "icon": "icon-gonggao"
        },
        {
            "type": "navBar",
            "name": "导航组",
            "value": {
                "limit": 4,
                "style": "square",
                "list": [
                    {
                        "image": default_img,
                        "text": "按钮1",
                        "linkType": '',
                        "linkValue": '',
                        "status": 1
                    },
                    {
                        "image": default_img,
                        "text": "按钮2",
                        "linkType": '',
                        "linkValue": '',
                        "status": 1
                    },
                    {
                        "image": default_img,
                        "text": "按钮3",
                        "linkType": '',
                        "linkValue": '',
                        "status": 1
                    },
                    {
                        "image": default_img,
                        "text": "按钮4",
                        "linkType": '',
                        "linkValue": '',
                        "status": 1
                    }
                ]
            },
            "icon": "icon-daohangzu"
        },
        {
            "type": "resource",
            "name": "资源组",
            "icon": "icon-ziyuan386",
            "value": {
                "title": '资源组名称',
                "lookMore": "true",
                "is_top": '0',//是否推荐
                "type": "auto", //auto自动获取  choose 手动选择
                "classifyId": 0, //所选分类id
                "limit": 10,
                "display": "list", //list , slide
                "column": '2', //分裂数量
                "fresh": '0', //是否为最后一个资源组
                "List":[],
                "list": [
                    {
                        "thumb": default_banner,
                        "title": '',
                        "price": '',
                        "dis_price": '',
                        "status": 1
                    },
                    {
                        "thumb": default_banner,
                        "title": '',
                        "price": '',
                        "dis_price": '',
                        "status": 1
                    },
                    {
                        "thumb": default_banner,
                        "title": '',
                        "price": '',
                        "dis_price": '',
                        "status": 1
                    },
                    {
                        "thumb": default_banner,
                        "title": '',
                        "price": '',
                        "dis_price": '',
                        "status": 1
                    }
                ]
            },
        },
        {
            "type": "resourceSort",
            "name": "资源分类",
            "value": {
                "title": "分类推荐",
                "is_title": "true",//显示标题栏
                "lookMore": "true",//显示更多
                "type": "auto",
                "limit": 10,//显示数量
                "style": "round",// round:圆弧 radius:圆角 square:方形
                "list": [],
            },
            "icon": "icon-leimu2",
        }
    ],
    "utilsComponents": [
        {
            "type": "blank",
            "name": "辅助空白",
            "icon": 'icon-fuzhuxian',
            "value": {
                "height": 20,
                "backgroundColor": "#FFFFFF"
            },
        },
        {
            "type": "textarea",
            "name": "文本域",
            "value": '',
            "icon": 'icon-wenbenyu',
        }, {
            "type": "tabbar",
            "name": "底部导航",
            "value": {
                "limit": 5,
                "list": [
                    {
                        "image": tarbar_path + 'home.png',
                        "image_on": tarbar_path + 'home_on.png',
                        "text": "首页",
                        "linkType": '6',
                        "linkValue": '/pages/tabbar/home/index',
                        "status": 1
                    },
                    {
                        "image": tarbar_path + 'sort.png',
                        "image_on": tarbar_path + 'sort_on.png',
                        "text": "资源",
                        "linkType": '6',
                        "linkValue": '/pages/tabbar/sort/index',
                        "status": 1
                    },
                    {
                        "image": tarbar_path + 'vip.png',
                        "image_on": tarbar_path + 'vip_on.png',
                        "text": "会员",
                        "linkType": '6',
                        "linkValue": '/pages/tabbar/vip/index',
                        "status": 1
                    },
                    {
                        "image": tarbar_path + 'my.png',
                        "image_on": tarbar_path + 'my_on.png',
                        "text": "我的",
                        "linkType": '6',
                        "linkValue": '/pages/tabbar/my/index',
                        "status": 1
                    },
                ]
            },
            "icon": 'icon-daohangzu',
        }],
};

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

Vue.component('layout', {
    template: '#layout',
    name: 'layout',
    data() {
        return {
            pageData: [],
            selectWg: {}
        }
    },
    computed: {
        getNumber(val) {
            return function (val) {
                return Number(val);
            }
        }
    },
    mounted() {
        if (pageConfig.length > 0) {
            for (var i = 0; i < pageConfig.length; i++) {
                var item = pageConfig[i];
                var elKey = Date.now() + '_' + Math.ceil(Math.random() * 1000000);
                item.key = item.type + '_' + elKey;
            }
            this.pageData = pageConfig;
        }
    },
    methods: {
        setSelectWg(data) {
            this.selectWg = data;
            this.bus.$emit('changeSelectWg', data);
        },
        handleWidgetAdd: function (evt) {
            var newIndex = evt.newIndex;
            var elKey = Date.now() + '_' + Math.ceil(Math.random() * 1000000);
            var newObj = deepClone(this.pageData[newIndex]);
            newObj.key = this.pageData[newIndex].type + '_' + elKey;
            this.$set(this.pageData, newIndex, newObj);
            this.setSelectWg(this.pageData[newIndex]);
        },
        handleClickAdd: function (obj) {
            var elKey = Date.now() + '_' + Math.ceil(Math.random() * 1000000);
            var newObj = deepClone(obj);
            newObj.key = obj.type + '_' + elKey;
            var newIndex = this.pageData.length || 0;
            this.$set(this.pageData, newIndex, newObj);
            this.setSelectWg(this.pageData[newIndex]);

        },
        handleSelectWidget(index) {
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
        handleWidgetDelete(deleteIndex) {
            var that = this;
            layer.open({
                title: '提示',
                content: '确定要删除吗？',
                btn: ['确定', '取消'],
                yes: function (index, layero) {
                    that.deleteWidget(deleteIndex);
                    layer.close(index);
                },
                btn2: function () {
                    return;
                }
            });

        },
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
        datadragEnd: function (evt) {
            console.log(evt, 'end');
        }
    }
});
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
          area: ['80%', '550px'],
          content: `${upload_manage}?type=1&name=${this.index}&prename=${this.tags}`
        });
      },
    }
  });
Vue.component('select-link', {
    template: '#select-link',
    props: ['type', 'id'],
    data: function () {
        return {
            linkType: linkType,
            linkUrl: this.id || '',
            selectType: this.type ? '' + this.type : Object.keys(linkType)[0]
        }
    },
    watch: {
        type(newVal, oldVal) {
            this.selectType = newVal;
            if (newVal === 1) {
                this.linkUrl = this.id;
            }
        }
    },
    mounted() {
        if (!this.type) {
            this.$emit('update:type', Object.keys(linkType)[0]);
        }
    },
    methods: {
        selectLink: function () {
            this.$emit('choose-link');
        },
        changeSelect: function () {
            this.$emit('update:type', this.selectType);
            this.$emit("update:id", '');
        },
        updateLinkValue: function () {
            this.$emit("update:id", this.linkUrl);
        },
        updateSelect: function () {
            this.$emit("update:id", this.id);
        }
    }
});
Vue.component('layout-config', {
    template: '#layout-config',
    name: 'LayoutConfig',
    data: function () {
        return {
            mainConfig: {
                name: pageTitle,
                code: pageCode,
            },
            showWidget: false,//切换页面配置页
            selectWg: {},
            _editocoverr: null,
            maxSelectGoods: 10, //选择商品最大数量
            maxNoticeNums: 5, //选择公告最多数量
            maxSelectSorts: 15,
            catList: catList,
            topCatList: topSortList,
            hasChooseGoods: [],
            hasChooseSorts: [],
            hasChooseGroupGoods: [],
            linkType: linkType,
            linkName: '',
            currentItemIndex: '',
            editor: null,
            defaultGoods: [
                {
                    "thumb": default_banner,
                    "title": '',
                    "price": '',
                    "dis_price": ''
                },
                {
                    "thumb": default_banner,
                    "title": '',
                    "price": '',
                    "dis_price": ''
                },
                {
                    "thumb": default_banner,
                    "title": '',
                    "price": '',
                    "dis_price": ''
                },
                {
                    "thumb": default_banner,
                    "title": '',
                    "price": '',
                    "dis_price": ''
                }
            ],
            defaultSorts: [
                {
                    "image": default_banner,
                    "name": '',
                },
                {
                    "image": default_banner,
                    "name": '',
                },
                {
                    "image": default_banner,
                    "name": '',
                },
                {
                    "image": default_banner,
                    "name": '',
                },
                {
                    "image": default_banner,
                    "name": '',
                }
            ],
            imgWindowStyle: [
                {
                    "title": '1行2个',
                    "value": 2,
                    "image": imgWindowArr[0]
                },
                {
                    "title": '1行3个',
                    "value": 3,
                    "image": imgWindowArr[1]
                },
                {
                    "title": '1行4个',
                    "value": 4,
                    "image": imgWindowArr[2]
                },
                {
                    "title": '1左3右',
                    "value": 0,
                    "image": imgWindowArr[3]
                },
            ]
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
        },
        'mainConfig.name': {
            handler: function (newValue) {
                this.bus.$emit('pageTitle', newValue);
            },
            deep: true
        },
    },
    computed: {
        getSelectWgName: function (type) {
            return function (type) {
                switch (type) {
                    case 'imgSlide':
                        return '图片轮播';
                        break;
                    case 'imgSingle':
                        return '图片';
                        break;
                    case 'imgWindow':
                        return '图片分组';
                        break;
                    case 'video':
                        return '视频组';
                        break;
                    case 'article':
                        return '文章组';
                        break;
                    case 'articleList':
                        return '文章分类';
                        break;
                    case 'search':
                        return '搜索框';
                        break;
                    case 'notice':
                        return '公告组';
                        break;
                    case 'navBar':
                        return '导航组';
                        break;
                    case 'resource':
                        return '资源组';
                        break;
                    case 'blank':
                        return '辅助空白';
                        break;
                    case 'textarea':
                        return '文本域';
                        break;
                    case 'resourceSort':
                        return '资源分类';
                        break;
                    case 'tabbar':
                        return '底部导航组';
                        break;
                    default:
                        return '';
                        break;
                }
            }
        }
    },
    mounted() {
        var that = this;
        this.bus.$on('changeSelectWg', function (data) {
            that.selectWg = data;
            that.showWidget = true;
            $('.model-title').removeClass('active');
            if (that.selectWg.type === 'resourceSort') {
                if(that.selectWg.value.type==='auto'){
                    that.selectWg.value.list = topSortList.slice(0, that.selectWg.value.limit);
                }else{
                    that.hasChooseSorts=that.selectWg.value.list;
                }
            }
        }); 
        // 监听自定义事件
        window.addEventListener("attachmentIn", function (e) {
            const url=e.detail.org_url;
            const index=e.detail.type;
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
                    that.$set(that.selectWg.value.list[that.currentItemIndex], 'linkValue', data.id + '|' + data.type);
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
            //监听文章列表页工具条
            table.on('tool(articleTable)', function (obj) { //注：tool是工具条事件名，test是table原始容器的属性 lay-filter="对应的值"
                var data = obj.data; //获得当前行数据
                var layEvent = obj.event; //获得 lay-event 对应的值（也可以是表头的 event 参数对应的值）
                var tr = obj.tr; //获得当前行 tr 的DOM对象
                if (layEvent === 'selectArticle') { //选择
                    if (that.selectWg.type === 'article') {
                        that.$set(that.selectWg.value.list, 0, data);
                    } else {
                        that.$set(that.selectWg.value.list[that.currentItemIndex], 'linkValue', data.id);
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
                    if (that.selectWg.type === 'articleList') {
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
    },
    methods: {
        inputLimit: function (e) {
            this.selectWg.value.list = topSortList.slice(0, this.selectWg.value.limit);
        },
        changeNavTitle: function (val) {
            this.selectWg.value.is_title = val;
        },
        slectTplStyle: function (item) {
            this.selectWg.value.style = item.value;
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
                    this.pages_list(this.currentItemIndex);
                    break;
                default:
                    break;
            }
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
        pages_list: function (currentItemIndex) {
            var that = this;
            layui.use(['form', 'table'], function () {
                layui.layer.open({
                    type: 2,
                    content: api_getPages,
                    area: ["800px", "550px"],
                    title: "选择链接",
                    btn: ["完成", "取消"],
                    yes: function (index, layero) {
                        var pageLinks = layer.getChildFrame('#pageLinkVal', index).val();
                        if (pageLinks) {
                            that.$set(that.selectWg.value.list[currentItemIndex], 'linkValue', pageLinks);
                            layer.close(index);
                        } else {
                            layer.msg('请至少选择一个');
                        }
                    }
                });
            });
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
        resetColor: function () {
            this.selectWg.value.backgroundColor = '#FFFFFF';
        },
        handleSlideRemove: function (index) {
            this.selectWg.value.list.splice(index, 1);
        },
        handleAddSlide: function () {
            this.selectWg.value.list.push({
                image: default_banner,
                    linkType: '',
                    linkValue: '',
                    status: 1
            });
        },
        handleAddNav: function () {
            if (this.selectWg.type == 'tabbar') {
                if (this.selectWg.value.list.length < this.selectWg.value.limit) {
                    this.selectWg.value.list.push({
                        linkType: '6',
                        linkValue: '/pages/tabbar/home/index',
                        image_on: default_img,
                        image: default_img,
                        text: '导航文字',
                        status:1
                    });
                    return false;
                }
                layer.msg('最多添加5个导航按钮');
                return false;
            } else {
                this.selectWg.value.list.push({
                    url: '',
                    image: default_img,
                    text: '按钮文字',
                    status:1
                });
            }
        },
        changeSMore: function (val) {
            this.$set(this.selectWg.value, 'lookMore', val);
        },
        changeSort: function (val) {
            if (val === 'auto') {
                this.selectWg.value.list = this.topCatList.slice(0, this.selectWg.value.limit);
            } else {
                this.selectWg.value.list =  this.hasChooseSorts;
            }
        },
        changeStyle: function (val) {
            this.selectWg.value.style = val;
        },
        changeGoodsType: function (val) {
            if (val === 'auto') {
                this.hasChooseGoods = this.selectWg.value.list;
                this.selectWg.value.list = this.defaultGoods;
            } else {
                this.selectWg.value.list = this.hasChooseGoods.length > 0 ? this.hasChooseGoods : this.defaultGoods;
            }
        },
        handleDeleteNotice: function (index) {
            this.selectWg.value.list.splice(index, 1);
        },
        handleDeleteGoods: function (index) {
            this.selectWg.value.list.splice(index, 1);
        },
        handleDeleteSorts: function (index) {
            this.selectWg.value.list.splice(index, 1);
        },
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
                        that.$set(that.selectWg.value, 'list', list);
                        layer.close(index);
                    }
                });
            });
        },
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
                        that.$set(that.selectWg.value, 'list', list);
                        layer.close(index);
                    }
                })
            });
        },
        selectSorts: function () {
            var that = this;
            layui.use(['form', 'table'], function () {
                layui.layer.open({
                    type: 2,
                    content: api_tagSorts,
                    area: ["800px", "480px"],
                    title: "选择分类",
                    btn: ["完成", "取消"],
                    yes: function (index, layero) {
                        var valList = layer.getChildFrame('#vallist', index).val();
                        if (valList) {
                            valList = JSON.parse(valList);
                        }
                        //判断个数是否满足
                        if (Object.getOwnPropertyNames(valList).length > that.maxSelectSorts) {
                            layer.msg("最多只能选择" + that.maxSelectSorts + "个");
                            return false;
                        }
                        var list = [];
                        for (let i in valList) {
                            list.push(valList[i]);
                        }
                        if(that.hasChooseSorts.length>0){
                            that.hasChooseSorts = that.hasChooseSorts.concat(list);
                            console.log(that.hasChooseSorts);
                            that.$set(that.selectWg.value, 'list', that.hasChooseSorts);
                        }else{
                            that.hasChooseSorts = list;
                            that.$set(that.selectWg.value, 'list', list);
                        }
                        layer.close(index);
                    }
                });
            });
        }
    }
});
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
                    saveUrl: saveUrl,
                    pageTitle: pageTitle
                }
            },
            methods: {
                handlePage: function () {
                    this.$refs.LayoutConfig.showWidget = false;
                    $('.model-title').addClass('active');
                    $('.layout-main').removeClass('active');
                },
                goToBack: function () {
                    history.back();
                },
                savePage: function () {
                    var data = {
                        data: JSON.stringify(this.$refs.layout.pageData),
                        pageConfig: this.$refs.LayoutConfig.mainConfig,
                        layout: pageLayout
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
                this.bus.$on('pageTitle', function (data) {
                    that.pageTitle = data;
                });
                layui.use(['form', 'laytpl'], function () {
                    form = layui.form;
                });
            },
        }
    }
});