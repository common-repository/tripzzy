(()=>{"use strict";const e=window.wp.blocks,t=window.wp.element,r=(window.wp.i18n,JSON.parse("{\"UU\":\"tripzzy/slide\",\"DD\":\"Slide\",\"Kk\":\"<svg width='32' height='32' viewBox='0 0 32 32' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M29 6H3V26H29V6Z' stroke='#FF6811' fill='none' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M23 13L25 16' stroke='#FF6811' stroke-linecap='round' stroke-linejoin='round'/><path d='M23 19L25 16' stroke='#FF6811' stroke-linecap='round' stroke-linejoin='round'/><path d='M9 19L7 16' stroke='#FF6811' stroke-linecap='round' stroke-linejoin='round'/><path d='M9 13L7 16' stroke='#FF6811' stroke-linecap='round' stroke-linejoin='round'/></svg>\",\"h_\":\"Single Slide block to be used with Slider block\"}")),o=window.wp.blockEditor,n=window.wp.data;function i(e){return i="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},i(e)}function s(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,o)}return r}var l=r.UU,c=r.Kk,u=r.attributes,a=r.DD,p=r.h_;(0,e.registerBlockType)(l,{title:a,icon:React.createElement(t.RawHTML,null,c),attributes:u,description:p,edit:function(e){var t=e.attributes,r=t.templateLock,l=void 0!==r&&r,c=(t.allowedBlocks,e.setAttributes,e.clientId),u=(0,o.useBlockProps)({className:"swiper-slide"}),a=(0,n.useSelect)((function(e){var t=e(o.store),r=t.getBlockOrder,n=(0,t.getBlockRootClientId)(c);return{hasChildBlocks:r(c).length>0,rootClientId:n,columnsIds:r(n)}}),[c]).hasChildBlocks,p=((0,n.useDispatch)(o.store).updateBlockAttributes,(0,o.useInnerBlocksProps)(function(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?s(Object(r),!0).forEach((function(t){var o,n,s,l;o=e,n=t,s=r[t],l=function(e,t){if("object"!=i(e)||!e)return e;var r=e[Symbol.toPrimitive];if(void 0!==r){var o=r.call(e,"string");if("object"!=i(o))return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(e)}(n),(n="symbol"==i(l)?l:String(l))in o?Object.defineProperty(o,n,{value:s,enumerable:!0,configurable:!0,writable:!0}):o[n]=s})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):s(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}({},u),{templateLock:l,allowedBlocks:["core/cover"],renderAppender:a?void 0:o.InnerBlocks.ButtonBlockAppender}));return React.createElement("div",p)},save:function(e){e.attributes;var t=o.useBlockProps.save({className:"swiper-slide"}),r=o.useInnerBlocksProps.save(t);return React.createElement("div",r)}})})();