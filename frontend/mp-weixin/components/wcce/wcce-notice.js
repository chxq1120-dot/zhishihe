(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/wcce/wcce-notice"],{"2c2b":function(t,n,e){"use strict";var c=e("ec26"),i=e.n(c);i.a},"3a23":function(t,n,e){"use strict";e.d(n,"b",(function(){return c})),e.d(n,"c",(function(){return i})),e.d(n,"a",(function(){}));var c=function(){var t=this.$createElement;this._self._c},i=[]},"61e8":function(t,n,e){"use strict";e.r(n);var c=e("ce00"),i=e.n(c);for(var o in c)["default"].indexOf(o)<0&&function(t){e.d(n,t,(function(){return c[t]}))}(o);n["default"]=i.a},8616:function(t,n,e){"use strict";e.r(n);var c=e("3a23"),i=e("61e8");for(var o in i)["default"].indexOf(o)<0&&function(t){e.d(n,t,(function(){return i[t]}))}(o);e("2c2b");var u=e("828b"),a=Object(u["a"])(i["default"],c["b"],c["c"],!1,null,null,null,!1,c["a"],void 0);n["default"]=a.exports},ce00:function(t,n,e){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;n.default={name:"wccenotice",data:function(){return{noticeList:null,noticeInfo:null}},props:{wdata:{required:!0}},created:function(){this.getNotice()},computed:{count:function(){return this.wdata.params.list.length>0}},methods:{getNotice:function(){var t=this;t.$api.getNotice({sort_id:1},(function(n){200==n.code&&(t.noticeList=n.data.list)}))},showNotice:function(t){this.$common.navigateTo("/pages/article/show?id="+t)}}}},ec26:function(t,n,e){}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/wcce/wcce-notice-create-component',
    {
        'components/wcce/wcce-notice-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('df3c')['createComponent'](__webpack_require__("8616"))
        })
    },
    [['components/wcce/wcce-notice-create-component']]
]);
