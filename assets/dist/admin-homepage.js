(()=>{"use strict";var e={n:t=>{var r=t&&t.__esModule?()=>t.default:()=>t;return e.d(r,{a:r}),r},d:(t,r)=>{for(var n in r)e.o(r,n)&&!e.o(t,n)&&Object.defineProperty(t,n,{enumerable:!0,get:r[n]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};(()=>{const t=window.wp.domReady;var r=e.n(t);const n=window.wp.element,a=window.wp.i18n;function o(e){return o="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},o(e)}function c(e,t){for(var r=0;r<t.length;r++){var n=t[r];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,i(n.key),n)}}function i(e){var t=function(e,t){if("object"!=o(e)||!e)return e;var r=e[Symbol.toPrimitive];if(void 0!==r){var n=r.call(e,"string");if("object"!=o(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(e)}(e);return"symbol"==o(t)?t:String(t)}function l(e,t,r){return t=u(t),function(e,t){if(t&&("object"===o(t)||"function"==typeof t))return t;if(void 0!==t)throw new TypeError("Derived constructors may only return object or undefined");return function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e)}(e,s()?Reflect.construct(t,r||[],u(e).constructor):t.apply(e,r))}function s(){try{var e=!Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){})))}catch(e){}return(s=function(){return!!e})()}function u(e){return u=Object.setPrototypeOf?Object.getPrototypeOf.bind():function(e){return e.__proto__||Object.getPrototypeOf(e)},u(e)}function p(e,t){return p=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(e,t){return e.__proto__=t,e},p(e,t)}var m=function(e){function t(e){var r;return function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,t),(r=l(this,t,[e])).state={hasError:!1,error:null,errorInfo:null},r}var r,n,a;return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),Object.defineProperty(e,"prototype",{writable:!1}),t&&p(e,t)}(t,e),r=t,a=[{key:"getDerivedStateFromError",value:function(e){return{hasError:!0}}}],(n=[{key:"componentDidCatch",value:function(e,t){this.setState({error:e,errorInfo:t})}},{key:"render",value:function(){return this.state.hasError?React.createElement("div",null,React.createElement("h2",null,"Something went wrong."),React.createElement("details",{style:{whiteSpace:"pre-wrap"}},this.state.error&&this.state.error.toString(),React.createElement("br",null),this.state.errorInfo&&this.state.errorInfo.componentStack)):this.props.children}}])&&c(r.prototype,n),a&&c(r,a),Object.defineProperty(r,"prototype",{writable:!1}),t}(n.Component);window.wp.data;const y=window.wp.components,f=window.wp.hooks,d=function(e){return e.TripzzyData,React.createElement(React.Fragment,null,React.createElement("div",{className:"tripzzy-panel tripzzy-dashboard-info"},React.createElement("div",{className:"tripzzy-card-box"},React.createElement("div",{className:"tripzzy-card-box-body"},React.createElement("div",{className:"tripzzy-card-box-row"},React.createElement("div",{className:"tripzzy-card-box-col",style:{"--width":"50%"}},React.createElement("div",{class:"tripzzy-card-box-container"},React.createElement("div",{className:"tripzzy-card-box-title"},React.createElement("h3",null,React.createElement("span",{className:"tripzzy-card-box-title-icon"},React.createElement("i",{className:"fa-solid fa-book"})),"View Documentation")),React.createElement("div",{className:"tripzzy-card-box-content"},React.createElement("p",null,"Stuck somewhere, please refer to our official documentation for assistence. It will help you to build a travel website using tripzzzy."),React.createElement("div",{className:"tripzzy-card-box-btns tripzzy-button-group"},React.createElement("a",{className:"tripzzy-more-info",href:"https://docs.wptripzzy.com",rel:"nofollow",target:"_blank"},"Explore Documentation"))))),React.createElement("div",{className:"tripzzy-card-box-col",style:{"--width":"50%"}},React.createElement("div",{class:"tripzzy-card-box-container"},React.createElement("div",{className:"tripzzy-card-box-title"},React.createElement("h3",null,React.createElement("span",{className:"tripzzy-card-box-title-icon"},React.createElement("i",{className:"fa-solid fa-circle-question"})),"Need Help?")),React.createElement("div",{className:"tripzzy-card-box-content"},React.createElement("p",null,"If you need assistance in Tripzzy plugin, please submit your query without hesitation. Our support representative will answer your query as soon as possible."),React.createElement("div",{className:"tripzzy-card-box-btns tripzzy-button-group"},React.createElement("a",{className:"tripzzy-more-info",href:"https://wordpress.org/support/plugin/tripzzy/",rel:"nofollow",target:"_blank"},"Submit your query"))))))))))};var b=function(){var e=(0,f.applyFilters)("TripzzyHomePageTabs",[{name:"home",title:(0,a.__)("Home","tripzzy"),className:"tab-home",content:d}]);return React.createElement(React.Fragment,null,React.createElement("div",{className:"tripzzy-tabs-wrapper tripzzy-form-wrapper"},e.length>0&&React.createElement(y.TabPanel,{className:"tripzzy-tabs",activeClass:"active-tab",tabs:e},(function(e){return void 0!==e.content?React.createElement(m,null,React.createElement(e.content,{TripzzyData:""})):React.createElement(React.Fragment,null,(0,a.__)("Content not found","tripzzy"))}))))};r()((function(){if(void 0!==document.getElementById("tripzzy-home-page")&&null!==document.getElementById("tripzzy-home-page")){var e=document.getElementById("tripzzy-home-page");void 0!==n.createRoot?(0,n.createRoot)(e).render(React.createElement(b,null)):(0,n.render)(React.createElement(b,null),e)}}))})()})();