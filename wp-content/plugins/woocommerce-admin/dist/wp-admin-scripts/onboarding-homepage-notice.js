this.wc=this.wc||{},this.wc.onboardingHomepageNotice=function(e){var t={};function o(n){if(t[n])return t[n].exports;var i=t[n]={i:n,l:!1,exports:{}};return e[n].call(i.exports,i,i.exports,o),i.l=!0,i.exports}return o.m=e,o.c=t,o.d=function(e,t,n){o.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},o.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},o.t=function(e,t){if(1&t&&(e=o(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(o.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var i in e)o.d(n,i,function(t){return e[t]}.bind(null,i));return n},o.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return o.d(t,"a",t),t},o.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},o.p="",o(o.s=840)}({1:function(e,t){!function(){e.exports=this.wp.i18n}()},25:function(e,t){!function(){e.exports=this.wc.navigation}()},27:function(e,t){!function(){e.exports=this.wp.data}()},35:function(e,t){!function(){e.exports=this.wp.apiFetch}()},840:function(e,t,o){"use strict";o.r(t);var n=o(27),i=o(1),r=o(35),c=o.n(r),s=o(25);wp.domReady((function(){var e,t;"page"===Object(n.select)("core/editor").getCurrentPostType()&&(e=Object(n.select)("core/editor").getCurrentPost(),t={wasPublishingPost:Object(n.select)("core/editor").isPublishingPost(),wasStatus:e.status},Object(n.subscribe)((function(){var o=Object(n.select)("core/editor").isPublishingPost(),r=Object(n.select)("core/editor").getCurrentPost().status,u="publish"===r&&t.wasPublishingPost&&!r.isPublishingPost;t.wasPublishingPost=o,t.wasStatus=r,u&&Object(n.select)("core/editor").didPostSaveRequestSucceed()&&(setTimeout((function(){wp.data.dispatch("core/notices").removeNotice("SAVE_POST_NOTICE_ID")}),0),c()({path:"/wc-admin/v1/options",method:"POST",data:{show_on_front:"page",page_on_front:e.id}}),Object(n.dispatch)("core/notices").createSuccessNotice(Object(i.__)("Your homepage was published.","woocommerce-admin"),{id:"WOOCOMMERCE_ONBOARDING_HOME_PAGE_NOTICE",actions:[{url:Object(s.getAdminLink)("admin.php?page=wc-admin&task=appearance"),label:Object(i.__)("Continue setup.","woocommerce-admin")}]}))})))}))}});