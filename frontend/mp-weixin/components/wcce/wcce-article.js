(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/wcce/wcce-article"],{"0fa8":function(t,n,e){"use strict";e.r(n);var c=e("4005"),a=e.n(c);for(var u in c)["default"].indexOf(u)<0&&function(t){e.d(n,t,(function(){return c[t]}))}(u);n["default"]=a.a},1722:function(t,n,e){"use strict";e.d(n,"b",(function(){return c})),e.d(n,"c",(function(){return a})),e.d(n,"a",(function(){}));var c=function(){var t=this.$createElement;this._self._c},a=[]},4005:function(t,n,e){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;n.default={name:"wccearticle",props:{wdata:{required:!0}},computed:{count:function(){return this.wdata.params.list.length>0}},methods:{articleDetail:function(t){this.$common.navigateTo("/pages/article/show?id="+t)}}}},6730:function(t,n,e){},dff2:function(t,n,e){"use strict";e.r(n);var c=e("1722"),a=e("0fa8");for(var u in a)["default"].indexOf(u)<0&&function(t){e.d(n,t,(function(){return a[t]}))}(u);e("e5cd");var i=e("828b"),r=Object(i["a"])(a["default"],c["b"],c["c"],!1,null,null,null,!1,c["a"],void 0);n["default"]=r.exports},e5cd:function(t,n,e){"use strict";var c=e("6730"),a=e.n(c);a.a}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/wcce/wcce-article-create-component',
    {
        'components/wcce/wcce-article-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('df3c')['createComponent'](__webpack_require__("dff2"))
        })
    },
    [['components/wcce/wcce-article-create-component']]
]);
