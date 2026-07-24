const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=[window.OC.filePath('teams', '', 'js/index-CdPnktSB.chunk.mjs'),window.OC.filePath('teams', '', 'js/logger-BmumIVPY.chunk.mjs'),window.OC.filePath('teams', '', 'css/logger-DUqJR6A9.chunk.css')])))=>i.map(i=>d[i]);
const appName = "teams";
const appVersion = "35.0.0-dev.0";
import { b1 as isArray$1, d as defineComponent, b2 as reactive, af as inject, b3 as routerKey$1, w as computed, ao as h$1, b4 as routeLocationKey$1, u as unref, b5 as noop$3, b6 as toRaw, b7 as isRef, b8 as isReactive, at as toRef, b9 as effectScope, v as ref, ba as markRaw, bb as hasInjectionContext, bc as getCurrentScope, bd as onScopeDispose, aa as watch, x as nextTick, be as toRefs, bf as useSlots, p as onMounted, bg as onBeforeUnmount, bh as provide, o as openBlock, y as createBlock, ai as resolveDynamicComponent, bi as getCurrentInstance, e as createElementBlock, k as renderSlot, T as normalizeStyle, ab as logger$1, C as loadState, r as register, bj as t27, a_ as getBuilder, a9 as getCapabilities, _ as _export_sfc, S as resolveComponent, h as toDisplayString, j as createCommentVNode, am as Fragment, U as normalizeClass, al as withModifiers, F as withDirectives, G as vShow, f as createBaseVNode, i as createVNode, z as withCtx, aH as emit, bk as useSwipe, bl as isRtl, bm as useIsMobile, R as NcIconSvgWrapper, a5 as mdiArrowRight, a as t$1, $ as NcButton, bn as t30, bo as onBeforeMount, g as createTextVNode, bp as Teleport, bq as isLegacy34, aF as shallowRef, br as shallowReactive, X as useModel, Y as useAttrs, q as useTemplateRef, Z as isLegacy, m as mergeProps, W as mdiCheck, a0 as mdiAlertCircleOutline, a1 as mergeModels, O as createElementId, c as cancelableClient, H as generateOcsUrl, J as logger$2, N as NcDialog, E as translate, K as showSuccess, I as showError, bs as t20, bt as warn, ax as watchEffect, ar as subscribe, bu as createFocusTrap, bv as getTrapStack, aL as onUnmounted, aq as unsubscribe, bw as withKeys, bx as mdiMenuOpen, by as mdiMenu, bz as t14, bA as vModelText, bB as t21, a2 as t51, bC as t23, aj as NcLoadingIcon, ak as NcActions, bD as normalizeProps, bE as guardReactiveProps, bF as t44, bG as t16, bH as useFocusWithin, bI as showConfirmation, aK as renderList, L as _export_sfc$1, bJ as translatePlural, bK as t47, bL as t48, bM as t31, bN as t15, bO as t19, a7 as mdiClose, bP as t28, bQ as t6, aw as getCanonicalLocale, bR as t35, bS as t43, bT as t37, bU as t5, n as getGettextBuilder, bV as t22, bW as t7, a4 as createSlots, bX as t38, bY as t42, bZ as t9, b_ as t8, b$ as imagePath, c0 as t46, c1 as t41, c2 as t24, c3 as t25, c4 as t32, c5 as t12, c6 as t34, c7 as t0, c8 as t50, c9 as NcPopover, ca as NOOP, cb as extend, cc as isString, cd as NO, ce as isSymbol, cf as isBuiltInDirective, cg as capitalize, ch as camelize, ci as EMPTY_OBJ, cj as isObject$1, ck as toHandlerKey, cl as isArray$2, cm as isOn, cn as isReservedProp, co as isVoidTag, cp as isHTMLTag, cq as isSVGTag, cr as isMathMLTag, cs as parseStringStyle, ct as makeMap, cu as generateCodeFrame, cv as getAugmentedNamespace, cw as runtimeDom_esmBundler, cx as shared_esmBundler, cy as getDefaultExportFromCjs, l as getLoggerBuilder, cz as NcNoteCard, cA as isVNode, cB as NcModal, cC as TransitionGroup, cD as DialogBuilder, cE as showWarning, cF as refDebounced, aQ as readonly, cG as getFilePickerBuilder, cH as FilePickerType, B as generateUrl, aC as generateRemoteUrl, aB as getCurrentUser, cI as FilePickerClosed, cJ as __vitePreload, cK as useElementSize, M as createApp } from "./logger-BmumIVPY.chunk.mjs";
import { _ as _sfc_main$W, b as NcInputField, d as debounce, a as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BVTMQSAg-CuDDI3i6.chunk.mjs";
import { b as NcActionButton, o as mdiCogOutline, p as mdiContentCopy, q as mdiExitToApp, r as mdiTrashCanOutline, s as mdiViewDashboard, t as mdiViewDashboardOutline, c as NcEmptyContent, j as mdiAccountGroupOutline, u as mdiPlus, v as mdiMagnify, w as mdiFolderMultipleOutline, n as mdiAlertCircleOutline$1 } from "./NcActionRouter-vYFtIOzD-CZ3pYYDb.chunk.mjs";
import { I as IconClose, C as ChevronDown, P as PQueue, a as NcSelect, S as ShareType } from "./index-C7Y8bBYp.chunk.mjs";
import { N as NcAvatar, a as NcActionText } from "./NcAvatar-DX-Nk9Es-BG2npiEg.chunk.mjs";
import "./index-Crbc4bej.chunk.mjs";
import "./index-CqWnHICW.chunk.mjs";
import { C as Color } from "./colors-BDeMBgfq-DzLyYZ86.chunk.mjs";
import "./NcSettingsSection-DmfxX2se-NyQBJsrf.chunk.mjs";
/*!
* vue-router v5.1.0
* (c) 2026 Eduardo San Martin Morote
* @license MIT
*/
function isSameRouteRecord$1(a2, b2) {
  return (a2.aliasOf || a2) === (b2.aliasOf || b2);
}
function isSameRouteLocationParams$1(a2, b2) {
  if (Object.keys(a2).length !== Object.keys(b2).length) return false;
  for (var key in a2) if (!isSameRouteLocationParamsValue$1(a2[key], b2[key])) return false;
  return true;
}
function isSameRouteLocationParamsValue$1(a2, b2) {
  return isArray$1(a2) ? isEquivalentArray$1(a2, b2) : isArray$1(b2) ? isEquivalentArray$1(b2, a2) : (a2 && a2.valueOf()) === (b2 && b2.valueOf());
}
function isEquivalentArray$1(a2, b2) {
  return isArray$1(b2) ? a2.length === b2.length && a2.every((value, i) => value === b2[i]) : a2.length === 1 && a2[0] === b2;
}
/*!
* vue-router v5.1.0
* (c) 2026 Eduardo San Martin Morote
* @license MIT
*/
function useLink$1(props) {
  const router2 = inject(routerKey$1);
  const currentRoute = inject(routeLocationKey$1);
  const route = computed(() => {
    const to = unref(props.to);
    return router2.resolve(to);
  });
  const activeRecordIndex = computed(() => {
    const { matched } = route.value;
    const { length } = matched;
    const routeMatched = matched[length - 1];
    const currentMatched = currentRoute.matched;
    if (!routeMatched || !currentMatched.length) return -1;
    const index = currentMatched.findIndex(isSameRouteRecord$1.bind(null, routeMatched));
    if (index > -1) return index;
    const parentRecordPath = getOriginalPath$1(matched[length - 2]);
    return length > 1 && getOriginalPath$1(routeMatched) === parentRecordPath && currentMatched[currentMatched.length - 1].path !== parentRecordPath ? currentMatched.findIndex(isSameRouteRecord$1.bind(null, matched[length - 2])) : index;
  });
  const isActive = computed(() => activeRecordIndex.value > -1 && includesParams$1(currentRoute.params, route.value.params));
  const isExactActive = computed(() => activeRecordIndex.value > -1 && activeRecordIndex.value === currentRoute.matched.length - 1 && isSameRouteLocationParams$1(currentRoute.params, route.value.params));
  function navigate(e = {}) {
    if (guardEvent$1(e)) {
      const p2 = router2[unref(props.replace) ? "replace" : "push"](unref(props.to)).catch(noop$3);
      if (props.viewTransition && typeof document !== "undefined" && "startViewTransition" in document) document.startViewTransition(() => p2);
      return p2;
    }
    return Promise.resolve();
  }
  return {
    route,
    href: computed(() => route.value.href),
    isActive,
    isExactActive,
    navigate
  };
}
function preferSingleVNode$1(vnodes) {
  return vnodes.length === 1 ? vnodes[0] : vnodes;
}
const RouterLink$1 = /* @__PURE__ */ defineComponent({
  name: "RouterLink",
  compatConfig: { MODE: 3 },
  props: {
    to: {
      type: [String, Object],
      required: true
    },
    replace: Boolean,
    activeClass: String,
    exactActiveClass: String,
    custom: Boolean,
    ariaCurrentValue: {
      type: String,
      default: "page"
    },
    viewTransition: Boolean
  },
  useLink: useLink$1,
  setup(props, { slots }) {
    const link = reactive(useLink$1(props));
    const { options } = inject(routerKey$1);
    const elClass = computed(() => ({
      [getLinkClass$1(props.activeClass, options.linkActiveClass, "router-link-active")]: link.isActive,
      [getLinkClass$1(props.exactActiveClass, options.linkExactActiveClass, "router-link-exact-active")]: link.isExactActive
    }));
    return () => {
      const children = slots.default && preferSingleVNode$1(slots.default(link));
      return props.custom ? children : h$1("a", {
        "aria-current": link.isExactActive ? props.ariaCurrentValue : null,
        href: link.href,
        onClick: link.navigate,
        class: elClass.value
      }, children);
    };
  }
});
function guardEvent$1(e) {
  if (e.metaKey || e.altKey || e.ctrlKey || e.shiftKey) return;
  if (e.defaultPrevented) return;
  if (e.button !== void 0 && e.button !== 0) return;
  if (e.currentTarget && e.currentTarget.getAttribute) {
    const target = e.currentTarget.getAttribute("target");
    if (/\b_blank\b/i.test(target)) return;
  }
  if (e.preventDefault) e.preventDefault();
  return true;
}
function includesParams$1(outer, inner) {
  for (const key in inner) {
    const innerValue = inner[key];
    const outerValue = outer[key];
    if (typeof innerValue === "string") {
      if (innerValue !== outerValue) return false;
    } else if (!isArray$1(outerValue) || outerValue.length !== innerValue.length || innerValue.some((value, i) => value.valueOf() !== outerValue[i].valueOf())) return false;
  }
  return true;
}
function getOriginalPath$1(record) {
  return record ? record.aliasOf ? record.aliasOf.path : record.path : "";
}
const getLinkClass$1 = (propClass, globalClass, defaultClass) => propClass != null ? propClass : globalClass != null ? globalClass : defaultClass;
let activePinia;
const setActivePinia = (pinia) => activePinia = pinia;
const piniaSymbol = (
  /* istanbul ignore next */
  /* @__PURE__ */ Symbol()
);
function isPlainObject(o) {
  return o && typeof o === "object" && Object.prototype.toString.call(o) === "[object Object]" && typeof o.toJSON !== "function";
}
var MutationType;
(function(MutationType2) {
  MutationType2["direct"] = "direct";
  MutationType2["patchObject"] = "patch object";
  MutationType2["patchFunction"] = "patch function";
})(MutationType || (MutationType = {}));
function createPinia() {
  const scope = effectScope(true);
  const state2 = scope.run(() => ref({}));
  let _p = [];
  let toBeInstalled = [];
  const pinia = markRaw({
    install(app2) {
      setActivePinia(pinia);
      pinia._a = app2;
      app2.provide(piniaSymbol, pinia);
      app2.config.globalProperties.$pinia = pinia;
      toBeInstalled.forEach((plugin) => _p.push(plugin));
      toBeInstalled = [];
    },
    use(plugin) {
      if (!this._a) {
        toBeInstalled.push(plugin);
      } else {
        _p.push(plugin);
      }
      return this;
    },
    _p,
    // it's actually undefined here
    // @ts-expect-error
    _a: null,
    _e: scope,
    _s: /* @__PURE__ */ new Map(),
    state: state2
  });
  return pinia;
}
const noop$2 = () => {
};
function addSubscription(subscriptions, callback, detached, onCleanup = noop$2) {
  subscriptions.add(callback);
  const removeSubscription = () => {
    const isDel = subscriptions.delete(callback);
    isDel && onCleanup();
  };
  if (!detached && getCurrentScope()) {
    onScopeDispose(removeSubscription);
  }
  return removeSubscription;
}
function triggerSubscriptions(subscriptions, ...args) {
  subscriptions.forEach((callback) => {
    callback(...args);
  });
}
const fallbackRunWithContext = (fn) => fn();
const ACTION_MARKER = /* @__PURE__ */ Symbol();
const ACTION_NAME = /* @__PURE__ */ Symbol();
function mergeReactiveObjects(target, patchToApply) {
  if (target instanceof Map && patchToApply instanceof Map) {
    patchToApply.forEach((value, key) => target.set(key, value));
  } else if (target instanceof Set && patchToApply instanceof Set) {
    patchToApply.forEach(target.add, target);
  }
  for (const key in patchToApply) {
    if (!patchToApply.hasOwnProperty(key))
      continue;
    const subPatch = patchToApply[key];
    const targetValue = target[key];
    if (isPlainObject(targetValue) && isPlainObject(subPatch) && target.hasOwnProperty(key) && !isRef(subPatch) && !isReactive(subPatch)) {
      target[key] = mergeReactiveObjects(targetValue, subPatch);
    } else {
      target[key] = subPatch;
    }
  }
  return target;
}
const skipHydrateSymbol = (
  /* istanbul ignore next */
  /* @__PURE__ */ Symbol()
);
function shouldHydrate(obj) {
  return !isPlainObject(obj) || !Object.prototype.hasOwnProperty.call(obj, skipHydrateSymbol);
}
const { assign: assign$1 } = Object;
function isComputed(o) {
  return !!(isRef(o) && o.effect);
}
function createOptionsStore(id, options, pinia, hot) {
  const { state: state2, actions: actions2, getters: getters2 } = options;
  const initialState = pinia.state.value[id];
  let store2;
  function setup() {
    if (!initialState && true) {
      pinia.state.value[id] = state2 ? state2() : {};
    }
    const localState = toRefs(pinia.state.value[id]);
    return assign$1(localState, actions2, Object.keys(getters2 || {}).reduce((computedGetters, name) => {
      computedGetters[name] = markRaw(computed(() => {
        setActivePinia(pinia);
        const store22 = pinia._s.get(id);
        return getters2[name].call(store22, store22);
      }));
      return computedGetters;
    }, {}));
  }
  store2 = createSetupStore(id, setup, options, pinia, hot, true);
  return store2;
}
function createSetupStore($id, setup, options = {}, pinia, hot, isOptionsStore) {
  let scope;
  const optionsForPlugin = assign$1({ actions: {} }, options);
  const $subscribeOptions = { deep: true };
  let isListening;
  let isSyncListening;
  let subscriptions = /* @__PURE__ */ new Set();
  let actionSubscriptions = /* @__PURE__ */ new Set();
  let debuggerEvents;
  const initialState = pinia.state.value[$id];
  if (!isOptionsStore && !initialState && true) {
    pinia.state.value[$id] = {};
  }
  ref({});
  let activeListener;
  function $patch(partialStateOrMutator) {
    let subscriptionMutation;
    isListening = isSyncListening = false;
    if (typeof partialStateOrMutator === "function") {
      partialStateOrMutator(pinia.state.value[$id]);
      subscriptionMutation = {
        type: MutationType.patchFunction,
        storeId: $id,
        events: debuggerEvents
      };
    } else {
      mergeReactiveObjects(pinia.state.value[$id], partialStateOrMutator);
      subscriptionMutation = {
        type: MutationType.patchObject,
        payload: partialStateOrMutator,
        storeId: $id,
        events: debuggerEvents
      };
    }
    const myListenerId = activeListener = /* @__PURE__ */ Symbol();
    nextTick().then(() => {
      if (activeListener === myListenerId) {
        isListening = true;
      }
    });
    isSyncListening = true;
    triggerSubscriptions(subscriptions, subscriptionMutation, pinia.state.value[$id]);
  }
  const $reset = isOptionsStore ? function $reset2() {
    const { state: state2 } = options;
    const newState = state2 ? state2() : {};
    this.$patch(($state) => {
      assign$1($state, newState);
    });
  } : (
    /* istanbul ignore next */
    noop$2
  );
  function $dispose() {
    scope.stop();
    subscriptions.clear();
    actionSubscriptions.clear();
    pinia._s.delete($id);
  }
  const action = (fn, name = "") => {
    if (ACTION_MARKER in fn) {
      fn[ACTION_NAME] = name;
      return fn;
    }
    const wrappedAction = function() {
      setActivePinia(pinia);
      const args = Array.from(arguments);
      const afterCallbackSet = /* @__PURE__ */ new Set();
      const onErrorCallbackSet = /* @__PURE__ */ new Set();
      function after(callback) {
        afterCallbackSet.add(callback);
      }
      function onError(callback) {
        onErrorCallbackSet.add(callback);
      }
      triggerSubscriptions(actionSubscriptions, {
        args,
        name: wrappedAction[ACTION_NAME],
        store: store2,
        after,
        onError
      });
      let ret;
      try {
        ret = fn.apply(this && this.$id === $id ? this : store2, args);
      } catch (error) {
        triggerSubscriptions(onErrorCallbackSet, error);
        throw error;
      }
      if (ret instanceof Promise) {
        return ret.then((value) => {
          triggerSubscriptions(afterCallbackSet, value);
          return value;
        }).catch((error) => {
          triggerSubscriptions(onErrorCallbackSet, error);
          return Promise.reject(error);
        });
      }
      triggerSubscriptions(afterCallbackSet, ret);
      return ret;
    };
    wrappedAction[ACTION_MARKER] = true;
    wrappedAction[ACTION_NAME] = name;
    return wrappedAction;
  };
  const partialStore = {
    _p: pinia,
    // _s: scope,
    $id,
    $onAction: addSubscription.bind(null, actionSubscriptions),
    $patch,
    $reset,
    $subscribe(callback, options2 = {}) {
      const removeSubscription = addSubscription(subscriptions, callback, options2.detached, () => stopWatcher());
      const stopWatcher = scope.run(() => watch(() => pinia.state.value[$id], (state2) => {
        if (options2.flush === "sync" ? isSyncListening : isListening) {
          callback({
            storeId: $id,
            type: MutationType.direct,
            events: debuggerEvents
          }, state2);
        }
      }, assign$1({}, $subscribeOptions, options2)));
      return removeSubscription;
    },
    $dispose
  };
  const store2 = reactive(partialStore);
  pinia._s.set($id, store2);
  const runWithContext = pinia._a && pinia._a.runWithContext || fallbackRunWithContext;
  const setupStore = runWithContext(() => pinia._e.run(() => (scope = effectScope()).run(() => setup({ action }))));
  for (const key in setupStore) {
    const prop = setupStore[key];
    if (isRef(prop) && !isComputed(prop) || isReactive(prop)) {
      if (!isOptionsStore) {
        if (initialState && shouldHydrate(prop)) {
          if (isRef(prop)) {
            prop.value = initialState[key];
          } else {
            mergeReactiveObjects(prop, initialState[key]);
          }
        }
        pinia.state.value[$id][key] = prop;
      }
    } else if (typeof prop === "function") {
      const actionValue = action(prop, key);
      setupStore[key] = actionValue;
      optionsForPlugin.actions[key] = prop;
    } else ;
  }
  assign$1(store2, setupStore);
  assign$1(toRaw(store2), setupStore);
  Object.defineProperty(store2, "$state", {
    get: () => pinia.state.value[$id],
    set: (state2) => {
      $patch(($state) => {
        assign$1($state, state2);
      });
    }
  });
  pinia._p.forEach((extender) => {
    {
      assign$1(store2, scope.run(() => extender({
        store: store2,
        app: pinia._a,
        pinia,
        options: optionsForPlugin
      })));
    }
  });
  if (initialState && isOptionsStore && options.hydrate) {
    options.hydrate(store2.$state, initialState);
  }
  isListening = true;
  isSyncListening = true;
  return store2;
}
/*! #__NO_SIDE_EFFECTS__ */
// @__NO_SIDE_EFFECTS__
function defineStore(id, setup, setupOptions) {
  let options;
  const isSetupStore = typeof setup === "function";
  options = isSetupStore ? setupOptions : setup;
  function useStore2(pinia, hot) {
    const hasContext = hasInjectionContext();
    pinia = // in test mode, ignore the argument provided as we can always retrieve a
    // pinia instance with getActivePinia()
    pinia || (hasContext ? inject(piniaSymbol, null) : null);
    if (pinia)
      setActivePinia(pinia);
    pinia = activePinia;
    if (!pinia._s.has(id)) {
      if (isSetupStore) {
        createSetupStore(id, setup, options, pinia);
      } else {
        createOptionsStore(id, options, pinia);
      }
    }
    const store2 = pinia._s.get(id);
    return store2;
  }
  useStore2.$id = id;
  return useStore2;
}
function storeToRefs(store2) {
  const rawStore = toRaw(store2);
  const refs = {};
  for (const key in rawStore) {
    const value = rawStore[key];
    if (value.effect) {
      refs[key] = // ...
      computed({
        get: () => store2[key],
        set(value2) {
          store2[key] = value2;
        }
      });
    } else if (isRef(value) || isReactive(value)) {
      refs[key] = // ---
      toRef(store2, key);
    }
  }
  return refs;
}
const Pe = {
  __name: "splitpanes",
  props: {
    horizontal: { type: Boolean, default: false },
    pushOtherPanes: { type: Boolean, default: true },
    maximizePanes: { type: Boolean, default: true },
    // Maximize pane on splitter double click/tap.
    rtl: { type: Boolean, default: false },
    // Right to left direction.
    firstSplitter: { type: Boolean, default: false }
  },
  emits: [
    "ready",
    "resize",
    "resized",
    "pane-click",
    "pane-maximize",
    "pane-add",
    "pane-remove",
    "splitter-click",
    "splitter-dblclick"
  ],
  setup(D, { emit: h2 }) {
    const y2 = h2, u2 = D, E2 = useSlots(), l = ref([]), M2 = computed(() => l.value.reduce((e, n) => (e[~~n.id] = n) && e, {})), m2 = computed(() => l.value.length), x2 = ref(null), S2 = ref(false), c = ref({
      mouseDown: false,
      dragging: false,
      activeSplitter: null,
      cursorOffset: 0
      // Cursor offset within the splitter.
    }), f2 = ref({
      // Used to detect double click on touch devices.
      splitter: null,
      timeoutId: null
    }), _2 = computed(() => ({
      [`splitpanes splitpanes--${u2.horizontal ? "horizontal" : "vertical"}`]: true,
      "splitpanes--dragging": c.value.dragging
    })), R2 = () => {
      document.addEventListener("mousemove", r, { passive: false }), document.addEventListener("mouseup", P2), "ontouchstart" in window && (document.addEventListener("touchmove", r, { passive: false }), document.addEventListener("touchend", P2));
    }, O2 = () => {
      document.removeEventListener("mousemove", r, { passive: false }), document.removeEventListener("mouseup", P2), "ontouchstart" in window && (document.removeEventListener("touchmove", r, { passive: false }), document.removeEventListener("touchend", P2));
    }, b2 = (e, n) => {
      const t2 = e.target.closest(".splitpanes__splitter");
      if (t2) {
        const { left: i, top: a2 } = t2.getBoundingClientRect(), { clientX: s, clientY: o } = "ontouchstart" in window && e.touches ? e.touches[0] : e;
        c.value.cursorOffset = u2.horizontal ? o - a2 : s - i;
      }
      R2(), c.value.mouseDown = true, c.value.activeSplitter = n;
    }, r = (e) => {
      c.value.mouseDown && (e.preventDefault(), c.value.dragging = true, requestAnimationFrame(() => {
        K(I2(e)), d2("resize", { event: e }, true);
      }));
    }, P2 = (e) => {
      c.value.dragging && (window.getSelection().removeAllRanges(), d2("resized", { event: e }, true)), c.value.mouseDown = false, c.value.activeSplitter = null, setTimeout(() => {
        c.value.dragging = false, O2();
      }, 100);
    }, A2 = (e, n) => {
      "ontouchstart" in window && (e.preventDefault(), f2.value.splitter === n ? (clearTimeout(f2.value.timeoutId), f2.value.timeoutId = null, U(e, n), f2.value.splitter = null) : (f2.value.splitter = n, f2.value.timeoutId = setTimeout(() => f2.value.splitter = null, 500))), c.value.dragging || d2("splitter-click", { event: e, index: n }, true);
    }, U = (e, n) => {
      if (d2("splitter-dblclick", { event: e, index: n }, true), u2.maximizePanes) {
        let t2 = 0;
        l.value = l.value.map((i, a2) => (i.size = a2 === n ? i.max : i.min, a2 !== n && (t2 += i.min), i)), l.value[n].size -= t2, d2("pane-maximize", { event: e, index: n, pane: l.value[n] }), d2("resized", { event: e, index: n }, true);
      }
    }, W = (e, n) => {
      d2("pane-click", {
        event: e,
        index: M2.value[n].index,
        pane: M2.value[n]
      });
    }, I2 = (e) => {
      const n = x2.value.getBoundingClientRect(), { clientX: t2, clientY: i } = "ontouchstart" in window && e.touches ? e.touches[0] : e;
      return {
        x: t2 - (u2.horizontal ? 0 : c.value.cursorOffset) - n.left,
        y: i - (u2.horizontal ? c.value.cursorOffset : 0) - n.top
      };
    }, J = (e) => {
      e = e[u2.horizontal ? "y" : "x"];
      const n = x2.value[u2.horizontal ? "clientHeight" : "clientWidth"];
      return u2.rtl && !u2.horizontal && (e = n - e), e * 100 / n;
    }, K = (e) => {
      const n = c.value.activeSplitter;
      let t2 = {
        prevPanesSize: $2(n),
        nextPanesSize: N2(n),
        prevReachedMinPanes: 0,
        nextReachedMinPanes: 0
      };
      const i = 0 + (u2.pushOtherPanes ? 0 : t2.prevPanesSize), a2 = 100 - (u2.pushOtherPanes ? 0 : t2.nextPanesSize), s = Math.max(Math.min(J(e), a2), i);
      let o = [n, n + 1], v2 = l.value[o[0]] || null, p2 = l.value[o[1]] || null;
      const H2 = v2.max < 100 && s >= v2.max + t2.prevPanesSize, ue = p2.max < 100 && s <= 100 - (p2.max + N2(n + 1));
      if (H2 || ue) {
        H2 ? (v2.size = v2.max, p2.size = Math.max(100 - v2.max - t2.prevPanesSize - t2.nextPanesSize, 0)) : (v2.size = Math.max(100 - p2.max - t2.prevPanesSize - N2(n + 1), 0), p2.size = p2.max);
        return;
      }
      if (u2.pushOtherPanes) {
        const j2 = Q(t2, s);
        if (!j2) return;
        ({ sums: t2, panesToResize: o } = j2), v2 = l.value[o[0]] || null, p2 = l.value[o[1]] || null;
      }
      v2 !== null && (v2.size = Math.min(Math.max(s - t2.prevPanesSize - t2.prevReachedMinPanes, v2.min), v2.max)), p2 !== null && (p2.size = Math.min(Math.max(100 - s - t2.nextPanesSize - t2.nextReachedMinPanes, p2.min), p2.max));
    }, Q = (e, n) => {
      const t2 = c.value.activeSplitter, i = [t2, t2 + 1];
      return n < e.prevPanesSize + l.value[i[0]].min && (i[0] = V(t2).index, e.prevReachedMinPanes = 0, i[0] < t2 && l.value.forEach((a2, s) => {
        s > i[0] && s <= t2 && (a2.size = a2.min, e.prevReachedMinPanes += a2.min);
      }), e.prevPanesSize = $2(i[0]), i[0] === void 0) ? (e.prevReachedMinPanes = 0, l.value[0].size = l.value[0].min, l.value.forEach((a2, s) => {
        s > 0 && s <= t2 && (a2.size = a2.min, e.prevReachedMinPanes += a2.min);
      }), l.value[i[1]].size = 100 - e.prevReachedMinPanes - l.value[0].min - e.prevPanesSize - e.nextPanesSize, null) : n > 100 - e.nextPanesSize - l.value[i[1]].min && (i[1] = Z(t2).index, e.nextReachedMinPanes = 0, i[1] > t2 + 1 && l.value.forEach((a2, s) => {
        s > t2 && s < i[1] && (a2.size = a2.min, e.nextReachedMinPanes += a2.min);
      }), e.nextPanesSize = N2(i[1] - 1), i[1] === void 0) ? (e.nextReachedMinPanes = 0, l.value.forEach((a2, s) => {
        s < m2.value - 1 && s >= t2 + 1 && (a2.size = a2.min, e.nextReachedMinPanes += a2.min);
      }), l.value[i[0]].size = 100 - e.prevPanesSize - N2(i[0] - 1), null) : { sums: e, panesToResize: i };
    }, $2 = (e) => l.value.reduce((n, t2, i) => n + (i < e ? t2.size : 0), 0), N2 = (e) => l.value.reduce((n, t2, i) => n + (i > e + 1 ? t2.size : 0), 0), V = (e) => [...l.value].reverse().find((t2) => t2.index < e && t2.size > t2.min) || {}, Z = (e) => l.value.find((t2) => t2.index > e + 1 && t2.size > t2.min) || {}, ee = () => {
      var n;
      const e = Array.from(((n = x2.value) == null ? void 0 : n.children) || []);
      for (const t2 of e) {
        const i = t2.classList.contains("splitpanes__pane"), a2 = t2.classList.contains("splitpanes__splitter");
        !i && !a2 && (t2.remove(), console.warn("Splitpanes: Only <pane> elements are allowed at the root of <splitpanes>. One of your DOM nodes was removed."));
      }
    }, F = (e, n, t2 = false) => {
      const i = e - 1, a2 = document.createElement("div");
      a2.classList.add("splitpanes__splitter"), t2 || (a2.onmousedown = (s) => b2(s, i), typeof window < "u" && "ontouchstart" in window && (a2.ontouchstart = (s) => b2(s, i)), a2.onclick = (s) => A2(s, i + 1)), a2.ondblclick = (s) => U(s, i + 1), n.parentNode.insertBefore(a2, n);
    }, ne = (e) => {
      e.onmousedown = void 0, e.onclick = void 0, e.ondblclick = void 0, e.remove();
    }, C2 = () => {
      var t2;
      const e = Array.from(((t2 = x2.value) == null ? void 0 : t2.children) || []);
      for (const i of e)
        i.className.includes("splitpanes__splitter") && ne(i);
      let n = 0;
      for (const i of e)
        i.className.includes("splitpanes__pane") && (!n && u2.firstSplitter ? F(n, i, true) : n && F(n, i), n++);
    }, ie = ({ uid: e, ...n }) => {
      const t2 = M2.value[e];
      for (const [i, a2] of Object.entries(n)) t2[i] = a2;
    }, te = (e) => {
      var t2;
      let n = -1;
      Array.from(((t2 = x2.value) == null ? void 0 : t2.children) || []).some((i) => (i.className.includes("splitpanes__pane") && n++, i.isSameNode(e.el))), l.value.splice(n, 0, { ...e, index: n }), l.value.forEach((i, a2) => i.index = a2), S2.value && nextTick(() => {
        C2(), L({ addedPane: l.value[n] }), d2("pane-add", { pane: l.value[n] });
      });
    }, ae = (e) => {
      const n = l.value.findIndex((i) => i.id === e);
      l.value[n].el = null;
      const t2 = l.value.splice(n, 1)[0];
      l.value.forEach((i, a2) => i.index = a2), nextTick(() => {
        C2(), d2("pane-remove", { pane: t2 }), L({ removedPane: { ...t2 } });
      });
    }, L = (e = {}) => {
      !e.addedPane && !e.removedPane ? le() : l.value.some((n) => n.givenSize !== null || n.min || n.max < 100) ? oe(e) : se(), S2.value && d2("resized");
    }, se = () => {
      const e = 100 / m2.value;
      let n = 0;
      const t2 = [], i = [];
      for (const a2 of l.value)
        a2.size = Math.max(Math.min(e, a2.max), a2.min), n -= a2.size, a2.size >= a2.max && t2.push(a2.id), a2.size <= a2.min && i.push(a2.id);
      n > 0.1 && q2(n, t2, i);
    }, le = () => {
      let e = 100;
      const n = [], t2 = [];
      let i = 0;
      for (const s of l.value)
        e -= s.size, s.givenSize !== null && i++, s.size >= s.max && n.push(s.id), s.size <= s.min && t2.push(s.id);
      let a2 = 100;
      if (e > 0.1) {
        for (const s of l.value)
          s.givenSize === null && (s.size = Math.max(Math.min(e / (m2.value - i), s.max), s.min)), a2 -= s.size;
        a2 > 0.1 && q2(a2, n, t2);
      }
    }, oe = ({ addedPane: e, removedPane: n } = {}) => {
      let t2 = 100 / m2.value, i = 0;
      const a2 = [], s = [];
      ((e == null ? void 0 : e.givenSize) ?? null) !== null && (t2 = (100 - e.givenSize) / (m2.value - 1));
      for (const o of l.value)
        i -= o.size, o.size >= o.max && a2.push(o.id), o.size <= o.min && s.push(o.id);
      if (!(Math.abs(i) < 0.1)) {
        for (const o of l.value)
          (e == null ? void 0 : e.givenSize) !== null && (e == null ? void 0 : e.id) === o.id || (o.size = Math.max(Math.min(t2, o.max), o.min)), i -= o.size, o.size >= o.max && a2.push(o.id), o.size <= o.min && s.push(o.id);
        i > 0.1 && q2(i, a2, s);
      }
    }, q2 = (e, n, t2) => {
      let i;
      e > 0 ? i = e / (m2.value - n.length) : i = e / (m2.value - t2.length), l.value.forEach((a2, s) => {
        if (e > 0 && !n.includes(a2.id)) {
          const o = Math.max(Math.min(a2.size + i, a2.max), a2.min), v2 = o - a2.size;
          e -= v2, a2.size = o;
        } else if (!t2.includes(a2.id)) {
          const o = Math.max(Math.min(a2.size + i, a2.max), a2.min), v2 = o - a2.size;
          e -= v2, a2.size = o;
        }
      }), Math.abs(e) > 0.1 && nextTick(() => {
        S2.value && console.warn("Splitpanes: Could not resize panes correctly due to their constraints.");
      });
    }, d2 = (e, n = void 0, t2 = false) => {
      const i = (n == null ? void 0 : n.index) ?? c.value.activeSplitter ?? null;
      y2(e, {
        ...n,
        ...i !== null && { index: i },
        ...t2 && i !== null && {
          prevPane: l.value[i - (u2.firstSplitter ? 1 : 0)],
          nextPane: l.value[i + (u2.firstSplitter ? 0 : 1)]
        },
        panes: l.value.map((a2) => ({ min: a2.min, max: a2.max, size: a2.size }))
      });
    };
    watch(() => u2.firstSplitter, () => C2()), onMounted(() => {
      ee(), C2(), L(), d2("ready"), S2.value = true;
    }), onBeforeUnmount(() => S2.value = false);
    const re = () => {
      var e;
      return h$1(
        "div",
        { ref: x2, class: _2.value },
        (e = E2.default) == null ? void 0 : e.call(E2)
      );
    };
    return provide("panes", l), provide("indexedPanes", M2), provide("horizontal", computed(() => u2.horizontal)), provide("requestUpdate", ie), provide("onPaneAdd", te), provide("onPaneRemove", ae), provide("onPaneClick", W), (e, n) => (openBlock(), createBlock(resolveDynamicComponent(re)));
  }
}, ge = {
  __name: "pane",
  props: {
    size: { type: [Number, String] },
    minSize: { type: [Number, String], default: 0 },
    maxSize: { type: [Number, String], default: 100 }
  },
  setup(D) {
    var b2;
    const h2 = D, y2 = inject("requestUpdate"), u2 = inject("onPaneAdd"), E2 = inject("horizontal"), l = inject("onPaneRemove"), M2 = inject("onPaneClick"), m2 = (b2 = getCurrentInstance()) == null ? void 0 : b2.uid, x2 = inject("indexedPanes"), S2 = computed(() => x2.value[m2]), c = ref(null), f2 = computed(() => {
      const r = isNaN(h2.size) || h2.size === void 0 ? 0 : parseFloat(h2.size);
      return Math.max(Math.min(r, R2.value), _2.value);
    }), _2 = computed(() => {
      const r = parseFloat(h2.minSize);
      return isNaN(r) ? 0 : r;
    }), R2 = computed(() => {
      const r = parseFloat(h2.maxSize);
      return isNaN(r) ? 100 : r;
    }), O2 = computed(() => {
      var r;
      return `${E2.value ? "height" : "width"}: ${(r = S2.value) == null ? void 0 : r.size}%`;
    });
    return watch(() => f2.value, (r) => y2({ uid: m2, size: r })), watch(() => _2.value, (r) => y2({ uid: m2, min: r })), watch(() => R2.value, (r) => y2({ uid: m2, max: r })), onMounted(() => {
      u2({
        id: m2,
        el: c.value,
        min: _2.value,
        max: R2.value,
        // The given size (useful to know the user intention).
        givenSize: h2.size === void 0 ? null : f2.value,
        size: f2.value
        // The computed current size at any time.
      });
    }), onBeforeUnmount(() => l(m2)), (r, P2) => (openBlock(), createElementBlock("div", {
      ref_key: "paneEl",
      ref: c,
      class: "splitpanes__pane",
      onClick: P2[0] || (P2[0] = (A2) => unref(M2)(A2, r._.uid)),
      style: normalizeStyle(O2.value)
    }, [
      renderSlot(r.$slots, "default")
    ], 4));
  }
};
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
function once(func) {
  let wasCalled = false;
  let result;
  return (...args) => {
    if (!wasCalled) {
      wasCalled = true;
      result = func(...args);
    }
    return result;
  };
}
let realAppName = "missing-app-name";
try {
  realAppName = appName;
} catch {
  logger$1.error("The `@nextcloud/vue` library was used without setting / replacing the `appName`.");
}
const APP_NAME = realAppName;
let realAppVersion = "";
try {
  realAppVersion = appVersion;
} catch {
  logger$1.error("The `@nextcloud/vue` library was used without setting / replacing the `appVersion`.");
}
function useAppName() {
  return inject("appName", APP_NAME);
}
const useLocalizedAppName = once(() => {
  const apps = loadState("core", "apps", []);
  const realAppName2 = useAppName();
  return apps.find(({ id }) => id === realAppName2)?.name ?? realAppName2;
});
register(t27);
const _sfc_main$1$4 = /* @__PURE__ */ defineComponent({
  __name: "NcAppContentDetailsToggle",
  setup(__props) {
    const isMobile = useIsMobile();
    watch(isMobile, toggleAppNavigationButton);
    onMounted(() => {
      toggleAppNavigationButton(isMobile.value);
    });
    onBeforeUnmount(() => {
      if (isMobile.value) {
        toggleAppNavigationButton(false);
      }
    });
    function toggleAppNavigationButton(hide = true) {
      const appNavigationToggle = document.querySelector(".app-navigation .app-navigation-toggle");
      if (appNavigationToggle) {
        appNavigationToggle.style.display = hide ? "none" : "";
        if (hide === true) {
          emit("toggle-navigation", { open: false });
        }
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcButton), {
        "aria-label": unref(t$1)("Go back to the list"),
        class: normalizeClass(["app-details-toggle", { "app-details-toggle--mobile": unref(isMobile) }]),
        title: unref(t$1)("Go back to the list"),
        variant: "tertiary"
      }, {
        icon: withCtx(() => [
          createVNode(unref(NcIconSvgWrapper), {
            directional: "",
            path: unref(mdiArrowRight)
          }, null, 8, ["path"])
        ]),
        _: 1
      }, 8, ["aria-label", "class", "title"]);
    };
  }
});
const NcAppContentDetailsToggle = /* @__PURE__ */ _export_sfc(_sfc_main$1$4, [["__scopeId", "data-v-a28923a1"]]);
const browserStorage = getBuilder("nextcloud").persist().build();
const instanceName = getCapabilities().theming?.name ?? "Nextcloud";
const _sfc_main$V = {
  name: "NcAppContent",
  components: {
    NcAppContentDetailsToggle,
    Pane: ge,
    Splitpanes: Pe
  },
  props: {
    /**
     * Allows to disable the control by swipe of the app navigation open state.
     */
    disableSwipe: {
      type: Boolean,
      default: false
    },
    /**
     * Allows you to set the default width of the resizable list in % on vertical-split
     * or respectively the default height on horizontal-split.
     *
     * Must be between `listMinWidth` and `listMaxWidth`.
     */
    listSize: {
      type: Number,
      default: 20
    },
    /**
     * Allows you to set the minimum width of the list column in % on vertical-split
     * or respectively the minimum height on horizontal-split.
     */
    listMinWidth: {
      type: Number,
      default: 15
    },
    /**
     * Allows you to set the maximum width of the list column in % on vertical-split
     * or respectively the maximum height on horizontal-split.
     */
    listMaxWidth: {
      type: Number,
      default: 40
    },
    /**
     * Specify the config key for the pane config sizes
     * Default is the global var appName if you use the webpack-vue-config
     */
    paneConfigKey: {
      type: String,
      default: ""
    },
    /**
     * When in mobile view, only the list or the details are shown.
     *
     * If you provide a list, you need to provide a variable
     * that will be set to true by the user when an element of
     * the list gets selected. The details will then show a back
     * arrow to return to the list that will update this prop to false.
     */
    showDetails: {
      type: Boolean,
      default: true
    },
    /**
     * Content layout used when there is a list together with content:
     * - `vertical-split` - a 2-column layout with list and default content separated vertically
     * - `no-split` - a single column layout; List is shown when `showDetails` is `false`, otherwise the default slot content is shown with a back button to return to the list.
     * - 'horizontal-split' - a 2-column layout with list and default content separated horizontally
     * On mobile screen `no-split` layout is forced.
     */
    layout: {
      type: String,
      default: "vertical-split",
      validator(value) {
        return ["no-split", "vertical-split", "horizontal-split"].includes(value);
      }
    },
    /**
     * Specify the `<h1>` page heading
     */
    pageHeading: {
      type: String,
      default: null
    },
    /**
     * Allow setting the page's `<title>`
     *
     * If a page heading is set it defaults to `{pageHeading} - {appName} - {instanceName}` e.g. `Favorites - Files - MyPersonalCloud`.
     * When the page heading and the app name is the same only one is used, e.g. `Files - Files - MyPersonalCloud` is shown as `Files - MyPersonalCloud`.
     * When setting the prop then the following format will be used: `{pageTitle} - {instanceName}`
     */
    pageTitle: {
      type: String,
      default: null
    }
  },
  emits: [
    "update:showDetails",
    "resizeList"
  ],
  setup() {
    return {
      appName: useAppName(),
      localizedAppName: useLocalizedAppName(),
      isMobile: useIsMobile(),
      isRtl
    };
  },
  data() {
    return {
      contentHeight: 0,
      swiping: {},
      listPaneSize: this.restorePaneConfig()
    };
  },
  computed: {
    paneConfigID() {
      if (this.paneConfigKey !== "") {
        return `pane-list-size-${this.paneConfigKey}`;
      }
      try {
        return `pane-list-size-${this.appName}`;
      } catch {
        logger$1.info("[NcAppContent]: falling back to global nextcloud pane config");
        return "pane-list-size-nextcloud";
      }
    },
    detailsPaneSize() {
      if (this.listPaneSize) {
        return 100 - this.listPaneSize;
      }
      return this.paneDefaults.details.size;
    },
    paneDefaults() {
      return {
        list: {
          size: this.listSize,
          min: this.listMinWidth,
          max: this.listMaxWidth
        },
        // set the inverse values of the details column
        // based on the provided (or default) values of the list column
        details: {
          size: 100 - this.listSize,
          min: 100 - this.listMaxWidth,
          max: 100 - this.listMinWidth
        }
      };
    },
    realPageTitle() {
      const entries = /* @__PURE__ */ new Set();
      if (this.pageTitle) {
        for (const part of this.pageTitle.split(" - ")) {
          entries.add(part);
        }
      } else if (this.pageHeading) {
        for (const part of this.pageHeading.split(" - ")) {
          entries.add(part);
        }
        if (entries.size > 0) {
          entries.add(this.localizedAppName);
        }
      } else {
        return null;
      }
      entries.add(instanceName);
      return [...entries.values()].join(" - ");
    }
  },
  watch: {
    realPageTitle: {
      immediate: true,
      handler() {
        if (this.realPageTitle !== null) {
          document.title = this.realPageTitle;
        }
      }
    },
    paneConfigKey: {
      immediate: true,
      handler() {
        this.restorePaneConfig();
      }
    }
  },
  mounted() {
    if (!this.disableSwipe) {
      this.swiping = useSwipe(this.$el, {
        onSwipeEnd: this.handleSwipe
      });
    }
    this.restorePaneConfig();
  },
  methods: {
    /**
     * handle the swipe event
     *
     * @param {TouchEvent} e The touch event
     * @param {import('@vueuse/core').SwipeDirection} direction The swipe direction of the event
     */
    handleSwipe(e, direction) {
      const minSwipeX = 70;
      const touchZone = 300;
      if (Math.abs(this.swiping.lengthX) > minSwipeX) {
        if (this.swiping.coordsStart.x < touchZone / 2 && direction === "right") {
          emit("toggle-navigation", {
            open: true
          });
        } else if (this.swiping.coordsStart.x < touchZone * 1.5 && direction === "left") {
          emit("toggle-navigation", {
            open: false
          });
        }
      }
    },
    handlePaneResize(event) {
      const listPaneSize = parseInt(event.panes[0].size, 10);
      browserStorage.setItem(this.paneConfigID, JSON.stringify(listPaneSize));
      this.listPaneSize = listPaneSize;
      this.$emit("resizeList", { size: listPaneSize });
      logger$1.debug("[NcAppContent] pane config", { listPaneSize });
    },
    // browserStorage is not reactive, we need to update this manually
    restorePaneConfig() {
      const listPaneSize = parseInt(browserStorage.getItem(this.paneConfigID), 10);
      if (!isNaN(listPaneSize) && listPaneSize !== this.listPaneSize) {
        logger$1.debug("[NcAppContent] pane config", { listPaneSize });
        this.listPaneSize = listPaneSize;
        return listPaneSize;
      }
    },
    /**
     * The user clicked the back arrow from the details view
     */
    hideDetails() {
      this.$emit("update:showDetails", false);
    }
  }
};
const _hoisted_1$M = {
  key: 0,
  class: "hidden-visually"
};
const _hoisted_2$C = { class: "app-content-wrapper__list" };
const _hoisted_3$x = {
  key: 1,
  class: "app-content-wrapper"
};
function _sfc_render$F(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcAppContentDetailsToggle = resolveComponent("NcAppContentDetailsToggle");
  const _component_Pane = resolveComponent("Pane");
  const _component_Splitpanes = resolveComponent("Splitpanes");
  return openBlock(), createElementBlock("main", {
    id: "app-content-vue",
    class: normalizeClass(["app-content no-snapper", { "app-content--has-list": !!_ctx.$slots.list }])
  }, [
    $props.pageHeading ? (openBlock(), createElementBlock("h1", _hoisted_1$M, toDisplayString($props.pageHeading), 1)) : createCommentVNode("", true),
    !!_ctx.$slots.list ? (openBlock(), createElementBlock(Fragment, { key: 1 }, [
      $setup.isMobile || $props.layout === "no-split" ? (openBlock(), createElementBlock("div", {
        key: 0,
        class: normalizeClass(["app-content-wrapper app-content-wrapper--no-split", {
          "app-content-wrapper--show-details": $props.showDetails,
          "app-content-wrapper--show-list": !$props.showDetails,
          "app-content-wrapper--mobile": $setup.isMobile
        }])
      }, [
        $props.showDetails ? (openBlock(), createBlock(_component_NcAppContentDetailsToggle, {
          key: 0,
          onClick: withModifiers($options.hideDetails, ["stop", "prevent"])
        }, null, 8, ["onClick"])) : createCommentVNode("", true),
        withDirectives(createBaseVNode("div", _hoisted_2$C, [
          renderSlot(_ctx.$slots, "list", {}, void 0, true)
        ], 512), [
          [vShow, !$props.showDetails]
        ]),
        $props.showDetails ? renderSlot(_ctx.$slots, "default", { key: 1 }, void 0, true) : createCommentVNode("", true)
      ], 2)) : $props.layout === "vertical-split" || $props.layout === "horizontal-split" ? (openBlock(), createElementBlock("div", _hoisted_3$x, [
        createVNode(_component_Splitpanes, {
          horizontal: $props.layout === "horizontal-split",
          class: normalizeClass(["default-theme", {
            "splitpanes--horizontal": $props.layout === "horizontal-split",
            "splitpanes--vertical": $props.layout === "vertical-split"
          }]),
          rtl: $setup.isRtl,
          onResized: $options.handlePaneResize
        }, {
          default: withCtx(() => [
            createVNode(_component_Pane, {
              class: "splitpanes__pane-list",
              size: $data.listPaneSize || $options.paneDefaults.list.size,
              minSize: $options.paneDefaults.list.min,
              maxSize: $options.paneDefaults.list.max
            }, {
              default: withCtx(() => [
                renderSlot(_ctx.$slots, "list", {}, void 0, true)
              ]),
              _: 3
            }, 8, ["size", "minSize", "maxSize"]),
            createVNode(_component_Pane, {
              class: "splitpanes__pane-details",
              size: $options.detailsPaneSize,
              minSize: $options.paneDefaults.details.min,
              maxSize: $options.paneDefaults.details.max
            }, {
              default: withCtx(() => [
                renderSlot(_ctx.$slots, "default", {}, void 0, true)
              ]),
              _: 3
            }, 8, ["size", "minSize", "maxSize"])
          ]),
          _: 3
        }, 8, ["horizontal", "class", "rtl", "onResized"])
      ])) : createCommentVNode("", true)
    ], 64)) : createCommentVNode("", true),
    !_ctx.$slots.list ? renderSlot(_ctx.$slots, "default", { key: 2 }, void 0, true) : createCommentVNode("", true)
  ], 2);
}
const NcAppContent = /* @__PURE__ */ _export_sfc(_sfc_main$V, [["render", _sfc_render$F], ["__scopeId", "data-v-ea1e6879"]]);
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const HAS_APP_NAVIGATION_KEY = /* @__PURE__ */ Symbol.for("NcContent:setHasAppNavigation");
const CONTENT_SELECTOR_KEY = /* @__PURE__ */ Symbol.for("NcContent:selector");
register(t30);
const contentSvg = '<!--\n  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors\n  - SPDX-License-Identifier: AGPL-3.0-or-later\n-->\n<svg width="395" height="314" viewBox="0 0 395 314" fill="none" xmlns="http://www.w3.org/2000/svg">\n<rect width="395" height="314" rx="11" fill="#439DCD"/>\n<rect x="13" y="51" width="366" height="248" rx="8" fill="white"/>\n<rect x="22" y="111" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="127" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="63" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="191" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="143" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="79" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="159" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="95" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="175" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<path d="M288 145C277.56 147.8 265.32 149 254 149C242.68 149 230.44 147.8 220 145L218 153C225.44 155 234 156.32 242 157V209H250V185H258V209H266V157C274 156.32 282.56 155 290 153L288 145ZM254 145C258.4 145 262 141.4 262 137C262 132.6 258.4 129 254 129C249.6 129 246 132.6 246 137C246 141.4 249.6 145 254 145Z" fill="#DEDEDE"/>\n<path d="M43.5358 13C38.6641 13 34.535 16.2415 33.2552 20.6333C32.143 18.3038 29.7327 16.6718 26.9564 16.6718C23.1385 16.6718 20 19.7521 20 23.4993C20 27.2465 23.1385 30.3282 26.9564 30.3282C29.7327 30.3282 32.1429 28.6952 33.2552 26.3653C34.535 30.7575 38.6641 34 43.5358 34C48.3715 34 52.4796 30.8064 53.7921 26.4637C54.9249 28.7407 57.3053 30.3282 60.0421 30.3282C63.8601 30.3282 67 27.2465 67 23.4993C67 19.7521 63.8601 16.6718 60.0421 16.6718C57.3053 16.6718 54.9249 18.2583 53.7921 20.5349C52.4796 16.1926 48.3715 13 43.5358 13ZM43.5358 17.0079C47.2134 17.0079 50.1512 19.8899 50.1512 23.4993C50.1512 27.1087 47.2134 29.9921 43.5358 29.9921C39.8583 29.9921 36.9218 27.1087 36.9218 23.4993C36.9218 19.8899 39.8583 17.0079 43.5358 17.0079ZM26.9564 20.6797C28.5677 20.6797 29.8307 21.9179 29.8307 23.4993C29.8307 25.0807 28.5677 26.3203 26.9564 26.3203C25.3452 26.3203 24.0836 25.0807 24.0836 23.4993C24.0836 21.9179 25.3452 20.6797 26.9564 20.6797ZM60.0421 20.6797C61.6534 20.6797 62.9164 21.9179 62.9164 23.4993C62.9164 25.0807 61.6534 26.3203 60.0421 26.3203C58.4309 26.3203 57.1693 25.0807 57.1693 23.4993C57.1693 21.9179 58.4309 20.6797 60.0421 20.6797Z" fill="white"/>\n<rect x="79" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="99" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="119" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="139" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="159" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="179" y="20" width="8" height="8" rx="4" fill="white"/>\n<path fill-rule="evenodd" clip-rule="evenodd" d="M12 0C5.37258 0 0 5.37259 0 12V302C0 308.627 5.37259 314 12 314H383C389.627 314 395 308.627 395 302V12C395 5.37258 389.627 0 383 0H12ZM140 44C132.268 44 126 50.268 126 58V292C126 299.732 132.268 306 140 306H372C379.732 306 386 299.732 386 292V58C386 50.268 379.732 44 372 44H140Z" fill="black" fill-opacity="0.35"/>\n</svg>\n';
const navigationSvg = '<!--\n  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors\n  - SPDX-License-Identifier: AGPL-3.0-or-later\n-->\n<svg width="395" height="314" viewBox="0 0 395 314" fill="none" xmlns="http://www.w3.org/2000/svg">\n<rect width="395" height="314" rx="11" fill="#439DCD"/>\n<rect x="13" y="51" width="366" height="248" rx="8" fill="white"/>\n<rect x="22" y="111" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="127" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="63" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="191" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="143" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="79" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="159" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="95" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<rect x="22" y="175" width="92" height="12" rx="6" fill="#DEDEDE"/>\n<path d="M288 145C277.56 147.8 265.32 149 254 149C242.68 149 230.44 147.8 220 145L218 153C225.44 155 234 156.32 242 157V209H250V185H258V209H266V157C274 156.32 282.56 155 290 153L288 145ZM254 145C258.4 145 262 141.4 262 137C262 132.6 258.4 129 254 129C249.6 129 246 132.6 246 137C246 141.4 249.6 145 254 145Z" fill="#DEDEDE"/>\n<path d="M43.5358 13C38.6641 13 34.535 16.2415 33.2552 20.6333C32.143 18.3038 29.7327 16.6718 26.9564 16.6718C23.1385 16.6718 20 19.7521 20 23.4993C20 27.2465 23.1385 30.3282 26.9564 30.3282C29.7327 30.3282 32.1429 28.6952 33.2552 26.3653C34.535 30.7575 38.6641 34 43.5358 34C48.3715 34 52.4796 30.8064 53.7921 26.4637C54.9249 28.7407 57.3053 30.3282 60.0421 30.3282C63.8601 30.3282 67 27.2465 67 23.4993C67 19.7521 63.8601 16.6718 60.0421 16.6718C57.3053 16.6718 54.9249 18.2583 53.7921 20.5349C52.4796 16.1926 48.3715 13 43.5358 13ZM43.5358 17.0079C47.2134 17.0079 50.1512 19.8899 50.1512 23.4993C50.1512 27.1087 47.2134 29.9921 43.5358 29.9921C39.8583 29.9921 36.9218 27.1087 36.9218 23.4993C36.9218 19.8899 39.8583 17.0079 43.5358 17.0079ZM26.9564 20.6797C28.5677 20.6797 29.8307 21.9179 29.8307 23.4993C29.8307 25.0807 28.5677 26.3203 26.9564 26.3203C25.3452 26.3203 24.0836 25.0807 24.0836 23.4993C24.0836 21.9179 25.3452 20.6797 26.9564 20.6797ZM60.0421 20.6797C61.6534 20.6797 62.9164 21.9179 62.9164 23.4993C62.9164 25.0807 61.6534 26.3203 60.0421 26.3203C58.4309 26.3203 57.1693 25.0807 57.1693 23.4993C57.1693 21.9179 58.4309 20.6797 60.0421 20.6797Z" fill="white"/>\n<rect x="79" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="99" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="119" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="139" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="159" y="20" width="8" height="8" rx="4" fill="white"/>\n<rect x="179" y="20" width="8" height="8" rx="4" fill="white"/>\n<path fill-rule="evenodd" clip-rule="evenodd" d="M12 0C5.37258 0 0 5.37259 0 12V302C0 308.627 5.37259 314 12 314H383C389.627 314 395 308.627 395 302V12C395 5.37258 389.627 0 383 0H12ZM112 44C119.732 44 126 50.268 126 58V292C126 299.732 119.732 306 112 306H20C12.268 306 6 299.732 6 292V58C6 50.268 12.268 44 20 44H112Z" fill="black" fill-opacity="0.35"/>\n</svg>\n';
const _hoisted_1$L = { class: "vue-skip-actions__container" };
const _hoisted_2$B = { class: "vue-skip-actions__headline" };
const _hoisted_3$w = { class: "vue-skip-actions__buttons" };
const _sfc_main$U = /* @__PURE__ */ defineComponent({
  __name: "NcContent",
  props: {
    appName: {}
  },
  setup(__props) {
    const props = __props;
    provide(HAS_APP_NAVIGATION_KEY, setAppNavigation);
    provide(CONTENT_SELECTOR_KEY, "#content-vue");
    provide("appName", computed(() => props.appName));
    const isMobile = useIsMobile();
    const hasAppNavigation = ref(false);
    const currentFocus = ref();
    const currentImage = computed(() => currentFocus.value === "navigation" ? navigationSvg : contentSvg);
    onBeforeMount(() => {
      const container = document.getElementById("skip-actions");
      if (container) {
        container.innerHTML = "";
        container.classList.add("vue-skip-actions");
      }
    });
    function openAppNavigation() {
      emit("toggle-navigation", { open: true });
      nextTick(() => {
        window.location.hash = "app-navigation-vue";
        document.getElementById("app-navigation-vue").focus();
      });
    }
    function setAppNavigation(value) {
      hasAppNavigation.value = value;
      if (!currentFocus.value) {
        currentFocus.value = "navigation";
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        id: "content-vue",
        class: normalizeClass(["content", [`app-${__props.appName.toLowerCase()}`, { "content--legacy": unref(isLegacy34) }]])
      }, [
        (openBlock(), createBlock(Teleport, { to: "#skip-actions" }, [
          createBaseVNode("div", _hoisted_1$L, [
            createBaseVNode("div", _hoisted_2$B, toDisplayString(unref(t$1)("Keyboard navigation help")), 1),
            createBaseVNode("div", _hoisted_3$w, [
              withDirectives(createVNode(NcButton, {
                href: "#app-navigation-vue",
                variant: "tertiary",
                onClick: withModifiers(openAppNavigation, ["prevent"]),
                onFocusin: _cache[0] || (_cache[0] = ($event) => currentFocus.value = "navigation"),
                onMouseover: _cache[1] || (_cache[1] = ($event) => currentFocus.value = "navigation")
              }, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString(unref(t$1)("Skip to app navigation")), 1)
                ]),
                _: 1
              }, 512), [
                [vShow, hasAppNavigation.value]
              ]),
              createVNode(NcButton, {
                href: "#app-content-vue",
                variant: "tertiary",
                onFocusin: _cache[2] || (_cache[2] = ($event) => currentFocus.value = "content"),
                onMouseover: _cache[3] || (_cache[3] = ($event) => currentFocus.value = "content")
              }, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString(unref(t$1)("Skip to main content")), 1)
                ]),
                _: 1
              })
            ]),
            withDirectives(createVNode(NcIconSvgWrapper, {
              class: "vue-skip-actions__image",
              svg: currentImage.value,
              size: "auto"
            }, null, 8, ["svg"]), [
              [vShow, !unref(isMobile)]
            ])
          ])
        ])),
        renderSlot(_ctx.$slots, "default", {}, void 0, true)
      ], 2);
    };
  }
});
const NcContent = /* @__PURE__ */ _export_sfc(_sfc_main$U, [["__scopeId", "data-v-d13dcb98"]]);
/*!
 * vue-router v4.6.4
 * (c) 2025 Eduardo San Martin Morote
 * @license MIT
 */
const isBrowser = typeof document !== "undefined";
function isRouteComponent(component) {
  return typeof component === "object" || "displayName" in component || "props" in component || "__vccOpts" in component;
}
function isESModule(obj) {
  return obj.__esModule || obj[Symbol.toStringTag] === "Module" || obj.default && isRouteComponent(obj.default);
}
const assign = Object.assign;
function applyToParams(fn, params) {
  const newParams = {};
  for (const key in params) {
    const value = params[key];
    newParams[key] = isArray(value) ? value.map(fn) : fn(value);
  }
  return newParams;
}
const noop$1 = () => {
};
const isArray = Array.isArray;
function mergeOptions(defaults2, partialOptions) {
  const options = {};
  for (const key in defaults2) options[key] = key in partialOptions ? partialOptions[key] : defaults2[key];
  return options;
}
const HASH_RE = /#/g;
const AMPERSAND_RE = /&/g;
const SLASH_RE = /\//g;
const EQUAL_RE = /=/g;
const IM_RE = /\?/g;
const PLUS_RE = /\+/g;
const ENC_BRACKET_OPEN_RE = /%5B/g;
const ENC_BRACKET_CLOSE_RE = /%5D/g;
const ENC_CARET_RE = /%5E/g;
const ENC_BACKTICK_RE = /%60/g;
const ENC_CURLY_OPEN_RE = /%7B/g;
const ENC_PIPE_RE = /%7C/g;
const ENC_CURLY_CLOSE_RE = /%7D/g;
const ENC_SPACE_RE = /%20/g;
function commonEncode(text2) {
  return text2 == null ? "" : encodeURI("" + text2).replace(ENC_PIPE_RE, "|").replace(ENC_BRACKET_OPEN_RE, "[").replace(ENC_BRACKET_CLOSE_RE, "]");
}
function encodeHash(text2) {
  return commonEncode(text2).replace(ENC_CURLY_OPEN_RE, "{").replace(ENC_CURLY_CLOSE_RE, "}").replace(ENC_CARET_RE, "^");
}
function encodeQueryValue(text2) {
  return commonEncode(text2).replace(PLUS_RE, "%2B").replace(ENC_SPACE_RE, "+").replace(HASH_RE, "%23").replace(AMPERSAND_RE, "%26").replace(ENC_BACKTICK_RE, "`").replace(ENC_CURLY_OPEN_RE, "{").replace(ENC_CURLY_CLOSE_RE, "}").replace(ENC_CARET_RE, "^");
}
function encodeQueryKey(text2) {
  return encodeQueryValue(text2).replace(EQUAL_RE, "%3D");
}
function encodePath$1(text2) {
  return commonEncode(text2).replace(HASH_RE, "%23").replace(IM_RE, "%3F");
}
function encodeParam(text2) {
  return encodePath$1(text2).replace(SLASH_RE, "%2F");
}
function decode(text2) {
  if (text2 == null) return null;
  try {
    return decodeURIComponent("" + text2);
  } catch (err) {
  }
  return "" + text2;
}
const TRAILING_SLASH_RE = /\/$/;
const removeTrailingSlash = (path2) => path2.replace(TRAILING_SLASH_RE, "");
function parseURL(parseQuery$1, location2, currentLocation = "/") {
  let path2, query = {}, searchString = "", hash = "";
  const hashPos = location2.indexOf("#");
  let searchPos = location2.indexOf("?");
  searchPos = hashPos >= 0 && searchPos > hashPos ? -1 : searchPos;
  if (searchPos >= 0) {
    path2 = location2.slice(0, searchPos);
    searchString = location2.slice(searchPos, hashPos > 0 ? hashPos : location2.length);
    query = parseQuery$1(searchString.slice(1));
  }
  if (hashPos >= 0) {
    path2 = path2 || location2.slice(0, hashPos);
    hash = location2.slice(hashPos, location2.length);
  }
  path2 = resolveRelativePath(path2 != null ? path2 : location2, currentLocation);
  return {
    fullPath: path2 + searchString + hash,
    path: path2,
    query,
    hash: decode(hash)
  };
}
function stringifyURL(stringifyQuery$1, location2) {
  const query = location2.query ? stringifyQuery$1(location2.query) : "";
  return location2.path + (query && "?") + query + (location2.hash || "");
}
function stripBase(pathname, base) {
  if (!base || !pathname.toLowerCase().startsWith(base.toLowerCase())) return pathname;
  return pathname.slice(base.length) || "/";
}
function isSameRouteLocation(stringifyQuery$1, a2, b2) {
  const aLastIndex = a2.matched.length - 1;
  const bLastIndex = b2.matched.length - 1;
  return aLastIndex > -1 && aLastIndex === bLastIndex && isSameRouteRecord(a2.matched[aLastIndex], b2.matched[bLastIndex]) && isSameRouteLocationParams(a2.params, b2.params) && stringifyQuery$1(a2.query) === stringifyQuery$1(b2.query) && a2.hash === b2.hash;
}
function isSameRouteRecord(a2, b2) {
  return (a2.aliasOf || a2) === (b2.aliasOf || b2);
}
function isSameRouteLocationParams(a2, b2) {
  if (Object.keys(a2).length !== Object.keys(b2).length) return false;
  for (var key in a2) if (!isSameRouteLocationParamsValue(a2[key], b2[key])) return false;
  return true;
}
function isSameRouteLocationParamsValue(a2, b2) {
  return isArray(a2) ? isEquivalentArray(a2, b2) : isArray(b2) ? isEquivalentArray(b2, a2) : a2?.valueOf() === b2?.valueOf();
}
function isEquivalentArray(a2, b2) {
  return isArray(b2) ? a2.length === b2.length && a2.every((value, i) => value === b2[i]) : a2.length === 1 && a2[0] === b2;
}
function resolveRelativePath(to, from) {
  if (to.startsWith("/")) return to;
  if (!to) return from;
  const fromSegments = from.split("/");
  const toSegments = to.split("/");
  const lastToSegment = toSegments[toSegments.length - 1];
  if (lastToSegment === ".." || lastToSegment === ".") toSegments.push("");
  let position = fromSegments.length - 1;
  let toPosition;
  let segment;
  for (toPosition = 0; toPosition < toSegments.length; toPosition++) {
    segment = toSegments[toPosition];
    if (segment === ".") continue;
    if (segment === "..") {
      if (position > 1) position--;
    } else break;
  }
  return fromSegments.slice(0, position).join("/") + "/" + toSegments.slice(toPosition).join("/");
}
const START_LOCATION_NORMALIZED = {
  path: "/",
  name: void 0,
  params: {},
  query: {},
  hash: "",
  fullPath: "/",
  matched: [],
  meta: {},
  redirectedFrom: void 0
};
let NavigationType = /* @__PURE__ */ (function(NavigationType$1) {
  NavigationType$1["pop"] = "pop";
  NavigationType$1["push"] = "push";
  return NavigationType$1;
})({});
let NavigationDirection = /* @__PURE__ */ (function(NavigationDirection$1) {
  NavigationDirection$1["back"] = "back";
  NavigationDirection$1["forward"] = "forward";
  NavigationDirection$1["unknown"] = "";
  return NavigationDirection$1;
})({});
function normalizeBase(base) {
  if (!base) if (isBrowser) {
    const baseEl = document.querySelector("base");
    base = baseEl && baseEl.getAttribute("href") || "/";
    base = base.replace(/^\w+:\/\/[^\/]+/, "");
  } else base = "/";
  if (base[0] !== "/" && base[0] !== "#") base = "/" + base;
  return removeTrailingSlash(base);
}
const BEFORE_HASH_RE = /^[^#]+#/;
function createHref(base, location2) {
  return base.replace(BEFORE_HASH_RE, "#") + location2;
}
function getElementPosition(el, offset) {
  const docRect = document.documentElement.getBoundingClientRect();
  const elRect = el.getBoundingClientRect();
  return {
    behavior: offset.behavior,
    left: elRect.left - docRect.left - (offset.left || 0),
    top: elRect.top - docRect.top - (offset.top || 0)
  };
}
const computeScrollPosition = () => ({
  left: window.scrollX,
  top: window.scrollY
});
function scrollToPosition(position) {
  let scrollToOptions;
  if ("el" in position) {
    const positionEl = position.el;
    const isIdSelector = typeof positionEl === "string" && positionEl.startsWith("#");
    const el = typeof positionEl === "string" ? isIdSelector ? document.getElementById(positionEl.slice(1)) : document.querySelector(positionEl) : positionEl;
    if (!el) {
      return;
    }
    scrollToOptions = getElementPosition(el, position);
  } else scrollToOptions = position;
  if ("scrollBehavior" in document.documentElement.style) window.scrollTo(scrollToOptions);
  else window.scrollTo(scrollToOptions.left != null ? scrollToOptions.left : window.scrollX, scrollToOptions.top != null ? scrollToOptions.top : window.scrollY);
}
function getScrollKey(path2, delta) {
  return (history.state ? history.state.position - delta : -1) + path2;
}
const scrollPositions = /* @__PURE__ */ new Map();
function saveScrollPosition(key, scrollPosition) {
  scrollPositions.set(key, scrollPosition);
}
function getSavedScrollPosition(key) {
  const scroll = scrollPositions.get(key);
  scrollPositions.delete(key);
  return scroll;
}
function isRouteLocation(route) {
  return typeof route === "string" || route && typeof route === "object";
}
function isRouteName(name) {
  return typeof name === "string" || typeof name === "symbol";
}
let ErrorTypes = /* @__PURE__ */ (function(ErrorTypes$1) {
  ErrorTypes$1[ErrorTypes$1["MATCHER_NOT_FOUND"] = 1] = "MATCHER_NOT_FOUND";
  ErrorTypes$1[ErrorTypes$1["NAVIGATION_GUARD_REDIRECT"] = 2] = "NAVIGATION_GUARD_REDIRECT";
  ErrorTypes$1[ErrorTypes$1["NAVIGATION_ABORTED"] = 4] = "NAVIGATION_ABORTED";
  ErrorTypes$1[ErrorTypes$1["NAVIGATION_CANCELLED"] = 8] = "NAVIGATION_CANCELLED";
  ErrorTypes$1[ErrorTypes$1["NAVIGATION_DUPLICATED"] = 16] = "NAVIGATION_DUPLICATED";
  return ErrorTypes$1;
})({});
const NavigationFailureSymbol = /* @__PURE__ */ Symbol("");
({
  [ErrorTypes.MATCHER_NOT_FOUND]({ location: location2, currentLocation }) {
    return `No match for
 ${JSON.stringify(location2)}${currentLocation ? "\nwhile being at\n" + JSON.stringify(currentLocation) : ""}`;
  },
  [ErrorTypes.NAVIGATION_GUARD_REDIRECT]({ from, to }) {
    return `Redirected from "${from.fullPath}" to "${stringifyRoute(to)}" via a navigation guard.`;
  },
  [ErrorTypes.NAVIGATION_ABORTED]({ from, to }) {
    return `Navigation aborted from "${from.fullPath}" to "${to.fullPath}" via a navigation guard.`;
  },
  [ErrorTypes.NAVIGATION_CANCELLED]({ from, to }) {
    return `Navigation cancelled from "${from.fullPath}" to "${to.fullPath}" with a new navigation.`;
  },
  [ErrorTypes.NAVIGATION_DUPLICATED]({ from, to }) {
    return `Avoided redundant navigation to current location: "${from.fullPath}".`;
  }
});
function createRouterError(type, params) {
  return assign(/* @__PURE__ */ new Error(), {
    type,
    [NavigationFailureSymbol]: true
  }, params);
}
function isNavigationFailure(error, type) {
  return error instanceof Error && NavigationFailureSymbol in error && (type == null || !!(error.type & type));
}
const propertiesToLog = [
  "params",
  "query",
  "hash"
];
function stringifyRoute(to) {
  if (typeof to === "string") return to;
  if (to.path != null) return to.path;
  const location2 = {};
  for (const key of propertiesToLog) if (key in to) location2[key] = to[key];
  return JSON.stringify(location2, null, 2);
}
function parseQuery(search) {
  const query = {};
  if (search === "" || search === "?") return query;
  const searchParams = (search[0] === "?" ? search.slice(1) : search).split("&");
  for (let i = 0; i < searchParams.length; ++i) {
    const searchParam = searchParams[i].replace(PLUS_RE, " ");
    const eqPos = searchParam.indexOf("=");
    const key = decode(eqPos < 0 ? searchParam : searchParam.slice(0, eqPos));
    const value = eqPos < 0 ? null : decode(searchParam.slice(eqPos + 1));
    if (key in query) {
      let currentValue = query[key];
      if (!isArray(currentValue)) currentValue = query[key] = [currentValue];
      currentValue.push(value);
    } else query[key] = value;
  }
  return query;
}
function stringifyQuery(query) {
  let search = "";
  for (let key in query) {
    const value = query[key];
    key = encodeQueryKey(key);
    if (value == null) {
      if (value !== void 0) search += (search.length ? "&" : "") + key;
      continue;
    }
    (isArray(value) ? value.map((v2) => v2 && encodeQueryValue(v2)) : [value && encodeQueryValue(value)]).forEach((value$1) => {
      if (value$1 !== void 0) {
        search += (search.length ? "&" : "") + key;
        if (value$1 != null) search += "=" + value$1;
      }
    });
  }
  return search;
}
function normalizeQuery(query) {
  const normalizedQuery = {};
  for (const key in query) {
    const value = query[key];
    if (value !== void 0) normalizedQuery[key] = isArray(value) ? value.map((v2) => v2 == null ? null : "" + v2) : value == null ? value : "" + value;
  }
  return normalizedQuery;
}
const matchedRouteKey = /* @__PURE__ */ Symbol("");
const viewDepthKey = /* @__PURE__ */ Symbol("");
const routerKey = /* @__PURE__ */ Symbol("");
const routeLocationKey = /* @__PURE__ */ Symbol("");
const routerViewLocationKey = /* @__PURE__ */ Symbol("");
function useCallbacks() {
  let handlers = [];
  function add(handler) {
    handlers.push(handler);
    return () => {
      const i = handlers.indexOf(handler);
      if (i > -1) handlers.splice(i, 1);
    };
  }
  function reset2() {
    handlers = [];
  }
  return {
    add,
    list: () => handlers.slice(),
    reset: reset2
  };
}
function guardToPromiseFn(guard, to, from, record, name, runWithContext = (fn) => fn()) {
  const enterCallbackArray = record && (record.enterCallbacks[name] = record.enterCallbacks[name] || []);
  return () => new Promise((resolve, reject) => {
    const next = (valid) => {
      if (valid === false) reject(createRouterError(ErrorTypes.NAVIGATION_ABORTED, {
        from,
        to
      }));
      else if (valid instanceof Error) reject(valid);
      else if (isRouteLocation(valid)) reject(createRouterError(ErrorTypes.NAVIGATION_GUARD_REDIRECT, {
        from: to,
        to: valid
      }));
      else {
        if (enterCallbackArray && record.enterCallbacks[name] === enterCallbackArray && typeof valid === "function") enterCallbackArray.push(valid);
        resolve();
      }
    };
    const guardReturn = runWithContext(() => guard.call(record && record.instances[name], to, from, next));
    let guardCall = Promise.resolve(guardReturn);
    if (guard.length < 3) guardCall = guardCall.then(next);
    guardCall.catch((err) => reject(err));
  });
}
function extractComponentsGuards(matched, guardType, to, from, runWithContext = (fn) => fn()) {
  const guards = [];
  for (const record of matched) {
    for (const name in record.components) {
      let rawComponent = record.components[name];
      if (guardType !== "beforeRouteEnter" && !record.instances[name]) continue;
      if (isRouteComponent(rawComponent)) {
        const guard = (rawComponent.__vccOpts || rawComponent)[guardType];
        guard && guards.push(guardToPromiseFn(guard, to, from, record, name, runWithContext));
      } else {
        let componentPromise = rawComponent();
        guards.push(() => componentPromise.then((resolved) => {
          if (!resolved) throw new Error(`Couldn't resolve component "${name}" at "${record.path}"`);
          const resolvedComponent = isESModule(resolved) ? resolved.default : resolved;
          record.mods[name] = resolved;
          record.components[name] = resolvedComponent;
          const guard = (resolvedComponent.__vccOpts || resolvedComponent)[guardType];
          return guard && guardToPromiseFn(guard, to, from, record, name, runWithContext)();
        }));
      }
    }
  }
  return guards;
}
function extractChangingRecords(to, from) {
  const leavingRecords = [];
  const updatingRecords = [];
  const enteringRecords = [];
  const len = Math.max(from.matched.length, to.matched.length);
  for (let i = 0; i < len; i++) {
    const recordFrom = from.matched[i];
    if (recordFrom) if (to.matched.find((record) => isSameRouteRecord(record, recordFrom))) updatingRecords.push(recordFrom);
    else leavingRecords.push(recordFrom);
    const recordTo = to.matched[i];
    if (recordTo) {
      if (!from.matched.find((record) => isSameRouteRecord(record, recordTo))) enteringRecords.push(recordTo);
    }
  }
  return [
    leavingRecords,
    updatingRecords,
    enteringRecords
  ];
}
/*!
 * vue-router v4.6.4
 * (c) 2025 Eduardo San Martin Morote
 * @license MIT
 */
let createBaseLocation = () => location.protocol + "//" + location.host;
function createCurrentLocation(base, location$1) {
  const { pathname, search, hash } = location$1;
  const hashPos = base.indexOf("#");
  if (hashPos > -1) {
    let slicePos = hash.includes(base.slice(hashPos)) ? base.slice(hashPos).length : 1;
    let pathFromHash = hash.slice(slicePos);
    if (pathFromHash[0] !== "/") pathFromHash = "/" + pathFromHash;
    return stripBase(pathFromHash, "");
  }
  return stripBase(pathname, base) + search + hash;
}
function useHistoryListeners(base, historyState, currentLocation, replace) {
  let listeners = [];
  let teardowns = [];
  let pauseState = null;
  const popStateHandler = ({ state: state2 }) => {
    const to = createCurrentLocation(base, location);
    const from = currentLocation.value;
    const fromState = historyState.value;
    let delta = 0;
    if (state2) {
      currentLocation.value = to;
      historyState.value = state2;
      if (pauseState && pauseState === from) {
        pauseState = null;
        return;
      }
      delta = fromState ? state2.position - fromState.position : 0;
    } else replace(to);
    listeners.forEach((listener) => {
      listener(currentLocation.value, from, {
        delta,
        type: NavigationType.pop,
        direction: delta ? delta > 0 ? NavigationDirection.forward : NavigationDirection.back : NavigationDirection.unknown
      });
    });
  };
  function pauseListeners() {
    pauseState = currentLocation.value;
  }
  function listen(callback) {
    listeners.push(callback);
    const teardown = () => {
      const index = listeners.indexOf(callback);
      if (index > -1) listeners.splice(index, 1);
    };
    teardowns.push(teardown);
    return teardown;
  }
  function beforeUnloadListener() {
    if (document.visibilityState === "hidden") {
      const { history: history$1 } = window;
      if (!history$1.state) return;
      history$1.replaceState(assign({}, history$1.state, { scroll: computeScrollPosition() }), "");
    }
  }
  function destroy() {
    for (const teardown of teardowns) teardown();
    teardowns = [];
    window.removeEventListener("popstate", popStateHandler);
    window.removeEventListener("pagehide", beforeUnloadListener);
    document.removeEventListener("visibilitychange", beforeUnloadListener);
  }
  window.addEventListener("popstate", popStateHandler);
  window.addEventListener("pagehide", beforeUnloadListener);
  document.addEventListener("visibilitychange", beforeUnloadListener);
  return {
    pauseListeners,
    listen,
    destroy
  };
}
function buildState(back, current, forward, replaced = false, computeScroll = false) {
  return {
    back,
    current,
    forward,
    replaced,
    position: window.history.length,
    scroll: computeScroll ? computeScrollPosition() : null
  };
}
function useHistoryStateNavigation(base) {
  const { history: history$1, location: location$1 } = window;
  const currentLocation = { value: createCurrentLocation(base, location$1) };
  const historyState = { value: history$1.state };
  if (!historyState.value) changeLocation(currentLocation.value, {
    back: null,
    current: currentLocation.value,
    forward: null,
    position: history$1.length - 1,
    replaced: true,
    scroll: null
  }, true);
  function changeLocation(to, state2, replace$1) {
    const hashIndex = base.indexOf("#");
    const url = hashIndex > -1 ? (location$1.host && document.querySelector("base") ? base : base.slice(hashIndex)) + to : createBaseLocation() + base + to;
    try {
      history$1[replace$1 ? "replaceState" : "pushState"](state2, "", url);
      historyState.value = state2;
    } catch (err) {
      console.error(err);
      location$1[replace$1 ? "replace" : "assign"](url);
    }
  }
  function replace(to, data) {
    changeLocation(to, assign({}, history$1.state, buildState(historyState.value.back, to, historyState.value.forward, true), data, { position: historyState.value.position }), true);
    currentLocation.value = to;
  }
  function push(to, data) {
    const currentState = assign({}, historyState.value, history$1.state, {
      forward: to,
      scroll: computeScrollPosition()
    });
    changeLocation(currentState.current, currentState, true);
    changeLocation(to, assign({}, buildState(currentLocation.value, to, null), { position: currentState.position + 1 }, data), false);
    currentLocation.value = to;
  }
  return {
    location: currentLocation,
    state: historyState,
    push,
    replace
  };
}
function createWebHistory(base) {
  base = normalizeBase(base);
  const historyNavigation = useHistoryStateNavigation(base);
  const historyListeners = useHistoryListeners(base, historyNavigation.state, historyNavigation.location, historyNavigation.replace);
  function go(delta, triggerListeners = true) {
    if (!triggerListeners) historyListeners.pauseListeners();
    history.go(delta);
  }
  const routerHistory = assign({
    location: "",
    base,
    go,
    createHref: createHref.bind(null, base)
  }, historyNavigation, historyListeners);
  Object.defineProperty(routerHistory, "location", {
    enumerable: true,
    get: () => historyNavigation.location.value
  });
  Object.defineProperty(routerHistory, "state", {
    enumerable: true,
    get: () => historyNavigation.state.value
  });
  return routerHistory;
}
let TokenType = /* @__PURE__ */ (function(TokenType$1) {
  TokenType$1[TokenType$1["Static"] = 0] = "Static";
  TokenType$1[TokenType$1["Param"] = 1] = "Param";
  TokenType$1[TokenType$1["Group"] = 2] = "Group";
  return TokenType$1;
})({});
var TokenizerState = /* @__PURE__ */ (function(TokenizerState$1) {
  TokenizerState$1[TokenizerState$1["Static"] = 0] = "Static";
  TokenizerState$1[TokenizerState$1["Param"] = 1] = "Param";
  TokenizerState$1[TokenizerState$1["ParamRegExp"] = 2] = "ParamRegExp";
  TokenizerState$1[TokenizerState$1["ParamRegExpEnd"] = 3] = "ParamRegExpEnd";
  TokenizerState$1[TokenizerState$1["EscapeNext"] = 4] = "EscapeNext";
  return TokenizerState$1;
})(TokenizerState || {});
const ROOT_TOKEN = {
  type: TokenType.Static,
  value: ""
};
const VALID_PARAM_RE = /[a-zA-Z0-9_]/;
function tokenizePath$1(path2) {
  if (!path2) return [[]];
  if (path2 === "/") return [[ROOT_TOKEN]];
  if (!path2.startsWith("/")) throw new Error(`Invalid path "${path2}"`);
  function crash(message) {
    throw new Error(`ERR (${state2})/"${buffer}": ${message}`);
  }
  let state2 = TokenizerState.Static;
  let previousState = state2;
  const tokens = [];
  let segment;
  function finalizeSegment() {
    if (segment) tokens.push(segment);
    segment = [];
  }
  let i = 0;
  let char;
  let buffer = "";
  let customRe = "";
  function consumeBuffer() {
    if (!buffer) return;
    if (state2 === TokenizerState.Static) segment.push({
      type: TokenType.Static,
      value: buffer
    });
    else if (state2 === TokenizerState.Param || state2 === TokenizerState.ParamRegExp || state2 === TokenizerState.ParamRegExpEnd) {
      if (segment.length > 1 && (char === "*" || char === "+")) crash(`A repeatable param (${buffer}) must be alone in its segment. eg: '/:ids+.`);
      segment.push({
        type: TokenType.Param,
        value: buffer,
        regexp: customRe,
        repeatable: char === "*" || char === "+",
        optional: char === "*" || char === "?"
      });
    } else crash("Invalid state to consume buffer");
    buffer = "";
  }
  function addCharToBuffer() {
    buffer += char;
  }
  while (i < path2.length) {
    char = path2[i++];
    if (char === "\\" && state2 !== TokenizerState.ParamRegExp) {
      previousState = state2;
      state2 = TokenizerState.EscapeNext;
      continue;
    }
    switch (state2) {
      case TokenizerState.Static:
        if (char === "/") {
          if (buffer) consumeBuffer();
          finalizeSegment();
        } else if (char === ":") {
          consumeBuffer();
          state2 = TokenizerState.Param;
        } else addCharToBuffer();
        break;
      case TokenizerState.EscapeNext:
        addCharToBuffer();
        state2 = previousState;
        break;
      case TokenizerState.Param:
        if (char === "(") state2 = TokenizerState.ParamRegExp;
        else if (VALID_PARAM_RE.test(char)) addCharToBuffer();
        else {
          consumeBuffer();
          state2 = TokenizerState.Static;
          if (char !== "*" && char !== "?" && char !== "+") i--;
        }
        break;
      case TokenizerState.ParamRegExp:
        if (char === ")") if (customRe[customRe.length - 1] == "\\") customRe = customRe.slice(0, -1) + char;
        else state2 = TokenizerState.ParamRegExpEnd;
        else customRe += char;
        break;
      case TokenizerState.ParamRegExpEnd:
        consumeBuffer();
        state2 = TokenizerState.Static;
        if (char !== "*" && char !== "?" && char !== "+") i--;
        customRe = "";
        break;
      default:
        crash("Unknown state");
        break;
    }
  }
  if (state2 === TokenizerState.ParamRegExp) crash(`Unfinished custom RegExp for param "${buffer}"`);
  consumeBuffer();
  finalizeSegment();
  return tokens;
}
const BASE_PARAM_PATTERN = "[^/]+?";
const BASE_PATH_PARSER_OPTIONS = {
  sensitive: false,
  strict: false,
  start: true,
  end: true
};
var PathScore = /* @__PURE__ */ (function(PathScore$1) {
  PathScore$1[PathScore$1["_multiplier"] = 10] = "_multiplier";
  PathScore$1[PathScore$1["Root"] = 90] = "Root";
  PathScore$1[PathScore$1["Segment"] = 40] = "Segment";
  PathScore$1[PathScore$1["SubSegment"] = 30] = "SubSegment";
  PathScore$1[PathScore$1["Static"] = 40] = "Static";
  PathScore$1[PathScore$1["Dynamic"] = 20] = "Dynamic";
  PathScore$1[PathScore$1["BonusCustomRegExp"] = 10] = "BonusCustomRegExp";
  PathScore$1[PathScore$1["BonusWildcard"] = -50] = "BonusWildcard";
  PathScore$1[PathScore$1["BonusRepeatable"] = -20] = "BonusRepeatable";
  PathScore$1[PathScore$1["BonusOptional"] = -8] = "BonusOptional";
  PathScore$1[PathScore$1["BonusStrict"] = 0.7000000000000001] = "BonusStrict";
  PathScore$1[PathScore$1["BonusCaseSensitive"] = 0.25] = "BonusCaseSensitive";
  return PathScore$1;
})(PathScore || {});
const REGEX_CHARS_RE = /[.+*?^${}()[\]/\\]/g;
function tokensToParser(segments, extraOptions) {
  const options = assign({}, BASE_PATH_PARSER_OPTIONS, extraOptions);
  const score = [];
  let pattern = options.start ? "^" : "";
  const keys = [];
  for (const segment of segments) {
    const segmentScores = segment.length ? [] : [PathScore.Root];
    if (options.strict && !segment.length) pattern += "/";
    for (let tokenIndex = 0; tokenIndex < segment.length; tokenIndex++) {
      const token = segment[tokenIndex];
      let subSegmentScore = PathScore.Segment + (options.sensitive ? PathScore.BonusCaseSensitive : 0);
      if (token.type === TokenType.Static) {
        if (!tokenIndex) pattern += "/";
        pattern += token.value.replace(REGEX_CHARS_RE, "\\$&");
        subSegmentScore += PathScore.Static;
      } else if (token.type === TokenType.Param) {
        const { value, repeatable, optional, regexp } = token;
        keys.push({
          name: value,
          repeatable,
          optional
        });
        const re$1 = regexp ? regexp : BASE_PARAM_PATTERN;
        if (re$1 !== BASE_PARAM_PATTERN) {
          subSegmentScore += PathScore.BonusCustomRegExp;
          try {
            `${re$1}`;
          } catch (err) {
            throw new Error(`Invalid custom RegExp for param "${value}" (${re$1}): ` + err.message);
          }
        }
        let subPattern = repeatable ? `((?:${re$1})(?:/(?:${re$1}))*)` : `(${re$1})`;
        if (!tokenIndex) subPattern = optional && segment.length < 2 ? `(?:/${subPattern})` : "/" + subPattern;
        if (optional) subPattern += "?";
        pattern += subPattern;
        subSegmentScore += PathScore.Dynamic;
        if (optional) subSegmentScore += PathScore.BonusOptional;
        if (repeatable) subSegmentScore += PathScore.BonusRepeatable;
        if (re$1 === ".*") subSegmentScore += PathScore.BonusWildcard;
      }
      segmentScores.push(subSegmentScore);
    }
    score.push(segmentScores);
  }
  if (options.strict && options.end) {
    const i = score.length - 1;
    score[i][score[i].length - 1] += PathScore.BonusStrict;
  }
  if (!options.strict) pattern += "/?";
  if (options.end) pattern += "$";
  else if (options.strict && !pattern.endsWith("/")) pattern += "(?:/|$)";
  const re = new RegExp(pattern, options.sensitive ? "" : "i");
  function parse2(path2) {
    const match = path2.match(re);
    const params = {};
    if (!match) return null;
    for (let i = 1; i < match.length; i++) {
      const value = match[i] || "";
      const key = keys[i - 1];
      params[key.name] = value && key.repeatable ? value.split("/") : value;
    }
    return params;
  }
  function stringify(params) {
    let path2 = "";
    let avoidDuplicatedSlash = false;
    for (const segment of segments) {
      if (!avoidDuplicatedSlash || !path2.endsWith("/")) path2 += "/";
      avoidDuplicatedSlash = false;
      for (const token of segment) if (token.type === TokenType.Static) path2 += token.value;
      else if (token.type === TokenType.Param) {
        const { value, repeatable, optional } = token;
        const param = value in params ? params[value] : "";
        if (isArray(param) && !repeatable) throw new Error(`Provided param "${value}" is an array but it is not repeatable (* or + modifiers)`);
        const text2 = isArray(param) ? param.join("/") : param;
        if (!text2) if (optional) {
          if (segment.length < 2) if (path2.endsWith("/")) path2 = path2.slice(0, -1);
          else avoidDuplicatedSlash = true;
        } else throw new Error(`Missing required param "${value}"`);
        path2 += text2;
      }
    }
    return path2 || "/";
  }
  return {
    re,
    score,
    keys,
    parse: parse2,
    stringify
  };
}
function compareScoreArray(a2, b2) {
  let i = 0;
  while (i < a2.length && i < b2.length) {
    const diff = b2[i] - a2[i];
    if (diff) return diff;
    i++;
  }
  if (a2.length < b2.length) return a2.length === 1 && a2[0] === PathScore.Static + PathScore.Segment ? -1 : 1;
  else if (a2.length > b2.length) return b2.length === 1 && b2[0] === PathScore.Static + PathScore.Segment ? 1 : -1;
  return 0;
}
function comparePathParserScore(a2, b2) {
  let i = 0;
  const aScore = a2.score;
  const bScore = b2.score;
  while (i < aScore.length && i < bScore.length) {
    const comp = compareScoreArray(aScore[i], bScore[i]);
    if (comp) return comp;
    i++;
  }
  if (Math.abs(bScore.length - aScore.length) === 1) {
    if (isLastScoreNegative(aScore)) return 1;
    if (isLastScoreNegative(bScore)) return -1;
  }
  return bScore.length - aScore.length;
}
function isLastScoreNegative(score) {
  const last = score[score.length - 1];
  return score.length > 0 && last[last.length - 1] < 0;
}
const PATH_PARSER_OPTIONS_DEFAULTS = {
  strict: false,
  end: true,
  sensitive: false
};
function createRouteRecordMatcher(record, parent, options) {
  const parser = tokensToParser(tokenizePath$1(record.path), options);
  const matcher = assign(parser, {
    record,
    parent,
    children: [],
    alias: []
  });
  if (parent) {
    if (!matcher.record.aliasOf === !parent.record.aliasOf) parent.children.push(matcher);
  }
  return matcher;
}
function createRouterMatcher(routes2, globalOptions) {
  const matchers = [];
  const matcherMap = /* @__PURE__ */ new Map();
  globalOptions = mergeOptions(PATH_PARSER_OPTIONS_DEFAULTS, globalOptions);
  function getRecordMatcher(name) {
    return matcherMap.get(name);
  }
  function addRoute(record, parent, originalRecord) {
    const isRootAdd = !originalRecord;
    const mainNormalizedRecord = normalizeRouteRecord(record);
    mainNormalizedRecord.aliasOf = originalRecord && originalRecord.record;
    const options = mergeOptions(globalOptions, record);
    const normalizedRecords = [mainNormalizedRecord];
    if ("alias" in record) {
      const aliases = typeof record.alias === "string" ? [record.alias] : record.alias;
      for (const alias of aliases) normalizedRecords.push(normalizeRouteRecord(assign({}, mainNormalizedRecord, {
        components: originalRecord ? originalRecord.record.components : mainNormalizedRecord.components,
        path: alias,
        aliasOf: originalRecord ? originalRecord.record : mainNormalizedRecord
      })));
    }
    let matcher;
    let originalMatcher;
    for (const normalizedRecord of normalizedRecords) {
      const { path: path2 } = normalizedRecord;
      if (parent && path2[0] !== "/") {
        const parentPath = parent.record.path;
        const connectingSlash = parentPath[parentPath.length - 1] === "/" ? "" : "/";
        normalizedRecord.path = parent.record.path + (path2 && connectingSlash + path2);
      }
      matcher = createRouteRecordMatcher(normalizedRecord, parent, options);
      if (originalRecord) {
        originalRecord.alias.push(matcher);
      } else {
        originalMatcher = originalMatcher || matcher;
        if (originalMatcher !== matcher) originalMatcher.alias.push(matcher);
        if (isRootAdd && record.name && !isAliasRecord(matcher)) {
          removeRoute(record.name);
        }
      }
      if (isMatchable(matcher)) insertMatcher(matcher);
      if (mainNormalizedRecord.children) {
        const children = mainNormalizedRecord.children;
        for (let i = 0; i < children.length; i++) addRoute(children[i], matcher, originalRecord && originalRecord.children[i]);
      }
      originalRecord = originalRecord || matcher;
    }
    return originalMatcher ? () => {
      removeRoute(originalMatcher);
    } : noop$1;
  }
  function removeRoute(matcherRef) {
    if (isRouteName(matcherRef)) {
      const matcher = matcherMap.get(matcherRef);
      if (matcher) {
        matcherMap.delete(matcherRef);
        matchers.splice(matchers.indexOf(matcher), 1);
        matcher.children.forEach(removeRoute);
        matcher.alias.forEach(removeRoute);
      }
    } else {
      const index = matchers.indexOf(matcherRef);
      if (index > -1) {
        matchers.splice(index, 1);
        if (matcherRef.record.name) matcherMap.delete(matcherRef.record.name);
        matcherRef.children.forEach(removeRoute);
        matcherRef.alias.forEach(removeRoute);
      }
    }
  }
  function getRoutes() {
    return matchers;
  }
  function insertMatcher(matcher) {
    const index = findInsertionIndex(matcher, matchers);
    matchers.splice(index, 0, matcher);
    if (matcher.record.name && !isAliasRecord(matcher)) matcherMap.set(matcher.record.name, matcher);
  }
  function resolve(location$1, currentLocation) {
    let matcher;
    let params = {};
    let path2;
    let name;
    if ("name" in location$1 && location$1.name) {
      matcher = matcherMap.get(location$1.name);
      if (!matcher) throw createRouterError(ErrorTypes.MATCHER_NOT_FOUND, { location: location$1 });
      name = matcher.record.name;
      params = assign(pickParams(currentLocation.params, matcher.keys.filter((k2) => !k2.optional).concat(matcher.parent ? matcher.parent.keys.filter((k2) => k2.optional) : []).map((k2) => k2.name)), location$1.params && pickParams(location$1.params, matcher.keys.map((k2) => k2.name)));
      path2 = matcher.stringify(params);
    } else if (location$1.path != null) {
      path2 = location$1.path;
      matcher = matchers.find((m2) => m2.re.test(path2));
      if (matcher) {
        params = matcher.parse(path2);
        name = matcher.record.name;
      }
    } else {
      matcher = currentLocation.name ? matcherMap.get(currentLocation.name) : matchers.find((m2) => m2.re.test(currentLocation.path));
      if (!matcher) throw createRouterError(ErrorTypes.MATCHER_NOT_FOUND, {
        location: location$1,
        currentLocation
      });
      name = matcher.record.name;
      params = assign({}, currentLocation.params, location$1.params);
      path2 = matcher.stringify(params);
    }
    const matched = [];
    let parentMatcher = matcher;
    while (parentMatcher) {
      matched.unshift(parentMatcher.record);
      parentMatcher = parentMatcher.parent;
    }
    return {
      name,
      path: path2,
      params,
      matched,
      meta: mergeMetaFields(matched)
    };
  }
  routes2.forEach((route) => addRoute(route));
  function clearRoutes() {
    matchers.length = 0;
    matcherMap.clear();
  }
  return {
    addRoute,
    resolve,
    removeRoute,
    clearRoutes,
    getRoutes,
    getRecordMatcher
  };
}
function pickParams(params, keys) {
  const newParams = {};
  for (const key of keys) if (key in params) newParams[key] = params[key];
  return newParams;
}
function normalizeRouteRecord(record) {
  const normalized = {
    path: record.path,
    redirect: record.redirect,
    name: record.name,
    meta: record.meta || {},
    aliasOf: record.aliasOf,
    beforeEnter: record.beforeEnter,
    props: normalizeRecordProps(record),
    children: record.children || [],
    instances: {},
    leaveGuards: /* @__PURE__ */ new Set(),
    updateGuards: /* @__PURE__ */ new Set(),
    enterCallbacks: {},
    components: "components" in record ? record.components || null : record.component && { default: record.component }
  };
  Object.defineProperty(normalized, "mods", { value: {} });
  return normalized;
}
function normalizeRecordProps(record) {
  const propsObject = {};
  const props = record.props || false;
  if ("component" in record) propsObject.default = props;
  else for (const name in record.components) propsObject[name] = typeof props === "object" ? props[name] : props;
  return propsObject;
}
function isAliasRecord(record) {
  while (record) {
    if (record.record.aliasOf) return true;
    record = record.parent;
  }
  return false;
}
function mergeMetaFields(matched) {
  return matched.reduce((meta, record) => assign(meta, record.meta), {});
}
function findInsertionIndex(matcher, matchers) {
  let lower = 0;
  let upper = matchers.length;
  while (lower !== upper) {
    const mid = lower + upper >> 1;
    if (comparePathParserScore(matcher, matchers[mid]) < 0) upper = mid;
    else lower = mid + 1;
  }
  const insertionAncestor = getInsertionAncestor(matcher);
  if (insertionAncestor) {
    upper = matchers.lastIndexOf(insertionAncestor, upper - 1);
  }
  return upper;
}
function getInsertionAncestor(matcher) {
  let ancestor = matcher;
  while (ancestor = ancestor.parent) if (isMatchable(ancestor) && comparePathParserScore(matcher, ancestor) === 0) return ancestor;
}
function isMatchable({ record }) {
  return !!(record.name || record.components && Object.keys(record.components).length || record.redirect);
}
function useLink(props) {
  const router2 = inject(routerKey);
  const currentRoute = inject(routeLocationKey);
  const route = computed(() => {
    const to = unref(props.to);
    return router2.resolve(to);
  });
  const activeRecordIndex = computed(() => {
    const { matched } = route.value;
    const { length } = matched;
    const routeMatched = matched[length - 1];
    const currentMatched = currentRoute.matched;
    if (!routeMatched || !currentMatched.length) return -1;
    const index = currentMatched.findIndex(isSameRouteRecord.bind(null, routeMatched));
    if (index > -1) return index;
    const parentRecordPath = getOriginalPath(matched[length - 2]);
    return length > 1 && getOriginalPath(routeMatched) === parentRecordPath && currentMatched[currentMatched.length - 1].path !== parentRecordPath ? currentMatched.findIndex(isSameRouteRecord.bind(null, matched[length - 2])) : index;
  });
  const isActive = computed(() => activeRecordIndex.value > -1 && includesParams(currentRoute.params, route.value.params));
  const isExactActive = computed(() => activeRecordIndex.value > -1 && activeRecordIndex.value === currentRoute.matched.length - 1 && isSameRouteLocationParams(currentRoute.params, route.value.params));
  function navigate(e = {}) {
    if (guardEvent(e)) {
      const p2 = router2[unref(props.replace) ? "replace" : "push"](unref(props.to)).catch(noop$1);
      if (props.viewTransition && typeof document !== "undefined" && "startViewTransition" in document) document.startViewTransition(() => p2);
      return p2;
    }
    return Promise.resolve();
  }
  return {
    route,
    href: computed(() => route.value.href),
    isActive,
    isExactActive,
    navigate
  };
}
function preferSingleVNode(vnodes) {
  return vnodes.length === 1 ? vnodes[0] : vnodes;
}
const RouterLinkImpl = /* @__PURE__ */ defineComponent({
  name: "RouterLink",
  compatConfig: { MODE: 3 },
  props: {
    to: {
      type: [String, Object],
      required: true
    },
    replace: Boolean,
    activeClass: String,
    exactActiveClass: String,
    custom: Boolean,
    ariaCurrentValue: {
      type: String,
      default: "page"
    },
    viewTransition: Boolean
  },
  useLink,
  setup(props, { slots }) {
    const link = reactive(useLink(props));
    const { options } = inject(routerKey);
    const elClass = computed(() => ({
      [getLinkClass(props.activeClass, options.linkActiveClass, "router-link-active")]: link.isActive,
      [getLinkClass(props.exactActiveClass, options.linkExactActiveClass, "router-link-exact-active")]: link.isExactActive
    }));
    return () => {
      const children = slots.default && preferSingleVNode(slots.default(link));
      return props.custom ? children : h$1("a", {
        "aria-current": link.isExactActive ? props.ariaCurrentValue : null,
        href: link.href,
        onClick: link.navigate,
        class: elClass.value
      }, children);
    };
  }
});
const RouterLink = RouterLinkImpl;
function guardEvent(e) {
  if (e.metaKey || e.altKey || e.ctrlKey || e.shiftKey) return;
  if (e.defaultPrevented) return;
  if (e.button !== void 0 && e.button !== 0) return;
  if (e.currentTarget && e.currentTarget.getAttribute) {
    const target = e.currentTarget.getAttribute("target");
    if (/\b_blank\b/i.test(target)) return;
  }
  if (e.preventDefault) e.preventDefault();
  return true;
}
function includesParams(outer, inner) {
  for (const key in inner) {
    const innerValue = inner[key];
    const outerValue = outer[key];
    if (typeof innerValue === "string") {
      if (innerValue !== outerValue) return false;
    } else if (!isArray(outerValue) || outerValue.length !== innerValue.length || innerValue.some((value, i) => value.valueOf() !== outerValue[i].valueOf())) return false;
  }
  return true;
}
function getOriginalPath(record) {
  return record ? record.aliasOf ? record.aliasOf.path : record.path : "";
}
const getLinkClass = (propClass, globalClass, defaultClass) => propClass != null ? propClass : globalClass != null ? globalClass : defaultClass;
const RouterViewImpl = /* @__PURE__ */ defineComponent({
  name: "RouterView",
  inheritAttrs: false,
  props: {
    name: {
      type: String,
      default: "default"
    },
    route: Object
  },
  compatConfig: { MODE: 3 },
  setup(props, { attrs, slots }) {
    const injectedRoute = inject(routerViewLocationKey);
    const routeToDisplay = computed(() => props.route || injectedRoute.value);
    const injectedDepth = inject(viewDepthKey, 0);
    const depth = computed(() => {
      let initialDepth = unref(injectedDepth);
      const { matched } = routeToDisplay.value;
      let matchedRoute;
      while ((matchedRoute = matched[initialDepth]) && !matchedRoute.components) initialDepth++;
      return initialDepth;
    });
    const matchedRouteRef = computed(() => routeToDisplay.value.matched[depth.value]);
    provide(viewDepthKey, computed(() => depth.value + 1));
    provide(matchedRouteKey, matchedRouteRef);
    provide(routerViewLocationKey, routeToDisplay);
    const viewRef = ref();
    watch(() => [
      viewRef.value,
      matchedRouteRef.value,
      props.name
    ], ([instance, to, name], [oldInstance, from, oldName]) => {
      if (to) {
        to.instances[name] = instance;
        if (from && from !== to && instance && instance === oldInstance) {
          if (!to.leaveGuards.size) to.leaveGuards = from.leaveGuards;
          if (!to.updateGuards.size) to.updateGuards = from.updateGuards;
        }
      }
      if (instance && to && (!from || !isSameRouteRecord(to, from) || !oldInstance)) (to.enterCallbacks[name] || []).forEach((callback) => callback(instance));
    }, { flush: "post" });
    return () => {
      const route = routeToDisplay.value;
      const currentName = props.name;
      const matchedRoute = matchedRouteRef.value;
      const ViewComponent = matchedRoute && matchedRoute.components[currentName];
      if (!ViewComponent) return normalizeSlot(slots.default, {
        Component: ViewComponent,
        route
      });
      const routePropsOption = matchedRoute.props[currentName];
      const routeProps = routePropsOption ? routePropsOption === true ? route.params : typeof routePropsOption === "function" ? routePropsOption(route) : routePropsOption : null;
      const onVnodeUnmounted = (vnode) => {
        if (vnode.component.isUnmounted) matchedRoute.instances[currentName] = null;
      };
      const component = h$1(ViewComponent, assign({}, routeProps, attrs, {
        onVnodeUnmounted,
        ref: viewRef
      }));
      return normalizeSlot(slots.default, {
        Component: component,
        route
      }) || component;
    };
  }
});
function normalizeSlot(slot, data) {
  if (!slot) return null;
  const slotContent = slot(data);
  return slotContent.length === 1 ? slotContent[0] : slotContent;
}
const RouterView = RouterViewImpl;
function createRouter(options) {
  const matcher = createRouterMatcher(options.routes, options);
  const parseQuery$1 = options.parseQuery || parseQuery;
  const stringifyQuery$1 = options.stringifyQuery || stringifyQuery;
  const routerHistory = options.history;
  const beforeGuards = useCallbacks();
  const beforeResolveGuards = useCallbacks();
  const afterGuards = useCallbacks();
  const currentRoute = shallowRef(START_LOCATION_NORMALIZED);
  let pendingLocation = START_LOCATION_NORMALIZED;
  if (isBrowser && options.scrollBehavior && "scrollRestoration" in history) history.scrollRestoration = "manual";
  const normalizeParams = applyToParams.bind(null, (paramValue) => "" + paramValue);
  const encodeParams = applyToParams.bind(null, encodeParam);
  const decodeParams = applyToParams.bind(null, decode);
  function addRoute(parentOrRoute, route) {
    let parent;
    let record;
    if (isRouteName(parentOrRoute)) {
      parent = matcher.getRecordMatcher(parentOrRoute);
      record = route;
    } else record = parentOrRoute;
    return matcher.addRoute(record, parent);
  }
  function removeRoute(name) {
    const recordMatcher = matcher.getRecordMatcher(name);
    if (recordMatcher) matcher.removeRoute(recordMatcher);
  }
  function getRoutes() {
    return matcher.getRoutes().map((routeMatcher) => routeMatcher.record);
  }
  function hasRoute(name) {
    return !!matcher.getRecordMatcher(name);
  }
  function resolve(rawLocation, currentLocation) {
    currentLocation = assign({}, currentLocation || currentRoute.value);
    if (typeof rawLocation === "string") {
      const locationNormalized = parseURL(parseQuery$1, rawLocation, currentLocation.path);
      const matchedRoute$1 = matcher.resolve({ path: locationNormalized.path }, currentLocation);
      const href$1 = routerHistory.createHref(locationNormalized.fullPath);
      return assign(locationNormalized, matchedRoute$1, {
        params: decodeParams(matchedRoute$1.params),
        hash: decode(locationNormalized.hash),
        redirectedFrom: void 0,
        href: href$1
      });
    }
    let matcherLocation;
    if (rawLocation.path != null) {
      matcherLocation = assign({}, rawLocation, { path: parseURL(parseQuery$1, rawLocation.path, currentLocation.path).path });
    } else {
      const targetParams = assign({}, rawLocation.params);
      for (const key in targetParams) if (targetParams[key] == null) delete targetParams[key];
      matcherLocation = assign({}, rawLocation, { params: encodeParams(targetParams) });
      currentLocation.params = encodeParams(currentLocation.params);
    }
    const matchedRoute = matcher.resolve(matcherLocation, currentLocation);
    const hash = rawLocation.hash || "";
    matchedRoute.params = normalizeParams(decodeParams(matchedRoute.params));
    const fullPath = stringifyURL(stringifyQuery$1, assign({}, rawLocation, {
      hash: encodeHash(hash),
      path: matchedRoute.path
    }));
    const href = routerHistory.createHref(fullPath);
    return assign({
      fullPath,
      hash,
      query: stringifyQuery$1 === stringifyQuery ? normalizeQuery(rawLocation.query) : rawLocation.query || {}
    }, matchedRoute, {
      redirectedFrom: void 0,
      href
    });
  }
  function locationAsObject(to) {
    return typeof to === "string" ? parseURL(parseQuery$1, to, currentRoute.value.path) : assign({}, to);
  }
  function checkCanceledNavigation(to, from) {
    if (pendingLocation !== to) return createRouterError(ErrorTypes.NAVIGATION_CANCELLED, {
      from,
      to
    });
  }
  function push(to) {
    return pushWithRedirect(to);
  }
  function replace(to) {
    return push(assign(locationAsObject(to), { replace: true }));
  }
  function handleRedirectRecord(to, from) {
    const lastMatched = to.matched[to.matched.length - 1];
    if (lastMatched && lastMatched.redirect) {
      const { redirect } = lastMatched;
      let newTargetLocation = typeof redirect === "function" ? redirect(to, from) : redirect;
      if (typeof newTargetLocation === "string") {
        newTargetLocation = newTargetLocation.includes("?") || newTargetLocation.includes("#") ? newTargetLocation = locationAsObject(newTargetLocation) : { path: newTargetLocation };
        newTargetLocation.params = {};
      }
      return assign({
        query: to.query,
        hash: to.hash,
        params: newTargetLocation.path != null ? {} : to.params
      }, newTargetLocation);
    }
  }
  function pushWithRedirect(to, redirectedFrom) {
    const targetLocation = pendingLocation = resolve(to);
    const from = currentRoute.value;
    const data = to.state;
    const force = to.force;
    const replace$1 = to.replace === true;
    const shouldRedirect = handleRedirectRecord(targetLocation, from);
    if (shouldRedirect) return pushWithRedirect(assign(locationAsObject(shouldRedirect), {
      state: typeof shouldRedirect === "object" ? assign({}, data, shouldRedirect.state) : data,
      force,
      replace: replace$1
    }), redirectedFrom || targetLocation);
    const toLocation = targetLocation;
    toLocation.redirectedFrom = redirectedFrom;
    let failure;
    if (!force && isSameRouteLocation(stringifyQuery$1, from, targetLocation)) {
      failure = createRouterError(ErrorTypes.NAVIGATION_DUPLICATED, {
        to: toLocation,
        from
      });
      handleScroll(from, from, true, false);
    }
    return (failure ? Promise.resolve(failure) : navigate(toLocation, from)).catch((error) => isNavigationFailure(error) ? isNavigationFailure(error, ErrorTypes.NAVIGATION_GUARD_REDIRECT) ? error : markAsReady(error) : triggerError(error, toLocation, from)).then((failure$1) => {
      if (failure$1) {
        if (isNavigationFailure(failure$1, ErrorTypes.NAVIGATION_GUARD_REDIRECT)) {
          return pushWithRedirect(assign({ replace: replace$1 }, locationAsObject(failure$1.to), {
            state: typeof failure$1.to === "object" ? assign({}, data, failure$1.to.state) : data,
            force
          }), redirectedFrom || toLocation);
        }
      } else failure$1 = finalizeNavigation(toLocation, from, true, replace$1, data);
      triggerAfterEach(toLocation, from, failure$1);
      return failure$1;
    });
  }
  function checkCanceledNavigationAndReject(to, from) {
    const error = checkCanceledNavigation(to, from);
    return error ? Promise.reject(error) : Promise.resolve();
  }
  function runWithContext(fn) {
    const app2 = installedApps.values().next().value;
    return app2 && typeof app2.runWithContext === "function" ? app2.runWithContext(fn) : fn();
  }
  function navigate(to, from) {
    let guards;
    const [leavingRecords, updatingRecords, enteringRecords] = extractChangingRecords(to, from);
    guards = extractComponentsGuards(leavingRecords.reverse(), "beforeRouteLeave", to, from);
    for (const record of leavingRecords) record.leaveGuards.forEach((guard) => {
      guards.push(guardToPromiseFn(guard, to, from));
    });
    const canceledNavigationCheck = checkCanceledNavigationAndReject.bind(null, to, from);
    guards.push(canceledNavigationCheck);
    return runGuardQueue(guards).then(() => {
      guards = [];
      for (const guard of beforeGuards.list()) guards.push(guardToPromiseFn(guard, to, from));
      guards.push(canceledNavigationCheck);
      return runGuardQueue(guards);
    }).then(() => {
      guards = extractComponentsGuards(updatingRecords, "beforeRouteUpdate", to, from);
      for (const record of updatingRecords) record.updateGuards.forEach((guard) => {
        guards.push(guardToPromiseFn(guard, to, from));
      });
      guards.push(canceledNavigationCheck);
      return runGuardQueue(guards);
    }).then(() => {
      guards = [];
      for (const record of enteringRecords) if (record.beforeEnter) if (isArray(record.beforeEnter)) for (const beforeEnter of record.beforeEnter) guards.push(guardToPromiseFn(beforeEnter, to, from));
      else guards.push(guardToPromiseFn(record.beforeEnter, to, from));
      guards.push(canceledNavigationCheck);
      return runGuardQueue(guards);
    }).then(() => {
      to.matched.forEach((record) => record.enterCallbacks = {});
      guards = extractComponentsGuards(enteringRecords, "beforeRouteEnter", to, from, runWithContext);
      guards.push(canceledNavigationCheck);
      return runGuardQueue(guards);
    }).then(() => {
      guards = [];
      for (const guard of beforeResolveGuards.list()) guards.push(guardToPromiseFn(guard, to, from));
      guards.push(canceledNavigationCheck);
      return runGuardQueue(guards);
    }).catch((err) => isNavigationFailure(err, ErrorTypes.NAVIGATION_CANCELLED) ? err : Promise.reject(err));
  }
  function triggerAfterEach(to, from, failure) {
    afterGuards.list().forEach((guard) => runWithContext(() => guard(to, from, failure)));
  }
  function finalizeNavigation(toLocation, from, isPush, replace$1, data) {
    const error = checkCanceledNavigation(toLocation, from);
    if (error) return error;
    const isFirstNavigation = from === START_LOCATION_NORMALIZED;
    const state2 = !isBrowser ? {} : history.state;
    if (isPush) if (replace$1 || isFirstNavigation) routerHistory.replace(toLocation.fullPath, assign({ scroll: isFirstNavigation && state2 && state2.scroll }, data));
    else routerHistory.push(toLocation.fullPath, data);
    currentRoute.value = toLocation;
    handleScroll(toLocation, from, isPush, isFirstNavigation);
    markAsReady();
  }
  let removeHistoryListener;
  function setupListeners() {
    if (removeHistoryListener) return;
    removeHistoryListener = routerHistory.listen((to, _from, info) => {
      if (!router2.listening) return;
      const toLocation = resolve(to);
      const shouldRedirect = handleRedirectRecord(toLocation, router2.currentRoute.value);
      if (shouldRedirect) {
        pushWithRedirect(assign(shouldRedirect, {
          replace: true,
          force: true
        }), toLocation).catch(noop$1);
        return;
      }
      pendingLocation = toLocation;
      const from = currentRoute.value;
      if (isBrowser) saveScrollPosition(getScrollKey(from.fullPath, info.delta), computeScrollPosition());
      navigate(toLocation, from).catch((error) => {
        if (isNavigationFailure(error, ErrorTypes.NAVIGATION_ABORTED | ErrorTypes.NAVIGATION_CANCELLED)) return error;
        if (isNavigationFailure(error, ErrorTypes.NAVIGATION_GUARD_REDIRECT)) {
          pushWithRedirect(assign(locationAsObject(error.to), { force: true }), toLocation).then((failure) => {
            if (isNavigationFailure(failure, ErrorTypes.NAVIGATION_ABORTED | ErrorTypes.NAVIGATION_DUPLICATED) && !info.delta && info.type === NavigationType.pop) routerHistory.go(-1, false);
          }).catch(noop$1);
          return Promise.reject();
        }
        if (info.delta) routerHistory.go(-info.delta, false);
        return triggerError(error, toLocation, from);
      }).then((failure) => {
        failure = failure || finalizeNavigation(toLocation, from, false);
        if (failure) {
          if (info.delta && !isNavigationFailure(failure, ErrorTypes.NAVIGATION_CANCELLED)) routerHistory.go(-info.delta, false);
          else if (info.type === NavigationType.pop && isNavigationFailure(failure, ErrorTypes.NAVIGATION_ABORTED | ErrorTypes.NAVIGATION_DUPLICATED)) routerHistory.go(-1, false);
        }
        triggerAfterEach(toLocation, from, failure);
      }).catch(noop$1);
    });
  }
  let readyHandlers = useCallbacks();
  let errorListeners = useCallbacks();
  let ready;
  function triggerError(error, to, from) {
    markAsReady(error);
    const list = errorListeners.list();
    if (list.length) list.forEach((handler) => handler(error, to, from));
    else {
      console.error(error);
    }
    return Promise.reject(error);
  }
  function isReady() {
    if (ready && currentRoute.value !== START_LOCATION_NORMALIZED) return Promise.resolve();
    return new Promise((resolve$1, reject) => {
      readyHandlers.add([resolve$1, reject]);
    });
  }
  function markAsReady(err) {
    if (!ready) {
      ready = !err;
      setupListeners();
      readyHandlers.list().forEach(([resolve$1, reject]) => err ? reject(err) : resolve$1());
      readyHandlers.reset();
    }
    return err;
  }
  function handleScroll(to, from, isPush, isFirstNavigation) {
    const { scrollBehavior } = options;
    if (!isBrowser || !scrollBehavior) return Promise.resolve();
    const scrollPosition = !isPush && getSavedScrollPosition(getScrollKey(to.fullPath, 0)) || (isFirstNavigation || !isPush) && history.state && history.state.scroll || null;
    return nextTick().then(() => scrollBehavior(to, from, scrollPosition)).then((position) => position && scrollToPosition(position)).catch((err) => triggerError(err, to, from));
  }
  const go = (delta) => routerHistory.go(delta);
  let started;
  const installedApps = /* @__PURE__ */ new Set();
  const router2 = {
    currentRoute,
    listening: true,
    addRoute,
    removeRoute,
    clearRoutes: matcher.clearRoutes,
    hasRoute,
    getRoutes,
    resolve,
    options,
    push,
    replace,
    go,
    back: () => go(-1),
    forward: () => go(1),
    beforeEach: beforeGuards.add,
    beforeResolve: beforeResolveGuards.add,
    afterEach: afterGuards.add,
    onError: errorListeners.add,
    isReady,
    install(app2) {
      app2.component("RouterLink", RouterLink);
      app2.component("RouterView", RouterView);
      app2.config.globalProperties.$router = router2;
      Object.defineProperty(app2.config.globalProperties, "$route", {
        enumerable: true,
        get: () => unref(currentRoute)
      });
      if (isBrowser && !started && currentRoute.value === START_LOCATION_NORMALIZED) {
        started = true;
        push(routerHistory.location).catch((err) => {
        });
      }
      const reactiveRoute = {};
      for (const key in START_LOCATION_NORMALIZED) Object.defineProperty(reactiveRoute, key, {
        get: () => currentRoute.value[key],
        enumerable: true
      });
      app2.provide(routerKey, router2);
      app2.provide(routeLocationKey, shallowReactive(reactiveRoute));
      app2.provide(routerViewLocationKey, currentRoute);
      const unmountApp = app2.unmount;
      installedApps.add(app2);
      app2.unmount = function() {
        installedApps.delete(app2);
        if (installedApps.size < 1) {
          pendingLocation = START_LOCATION_NORMALIZED;
          removeHistoryListener && removeHistoryListener();
          removeHistoryListener = null;
          currentRoute.value = START_LOCATION_NORMALIZED;
          started = false;
          ready = false;
        }
        unmountApp();
      };
    }
  };
  function runGuardQueue(guards) {
    return guards.reduce((promise, guard) => promise.then(() => runWithContext(guard)), Promise.resolve());
  }
  return router2;
}
function useRouter() {
  return inject(routerKey);
}
function useRoute(_name) {
  return inject(routeLocationKey);
}
const _hoisted_1$K = { class: "textarea__main-wrapper" };
const _hoisted_2$A = ["id", "aria-describedby", "disabled", "placeholder", "value"];
const _hoisted_3$v = ["for"];
const _hoisted_4$v = ["id"];
const _sfc_main$T = /* @__PURE__ */ defineComponent({
  ...{ inheritAttrs: false },
  __name: "NcTextArea",
  props: /* @__PURE__ */ mergeModels({
    disabled: { type: Boolean },
    error: { type: Boolean },
    helperText: { default: void 0 },
    id: { default: () => createElementId() },
    inputClass: { default: "" },
    label: { default: void 0 },
    labelOutside: { type: Boolean },
    placeholder: { default: void 0 },
    resize: { default: "both" },
    success: { type: Boolean }
  }, {
    "modelValue": { required: true },
    "modelModifiers": {}
  }),
  emits: ["update:modelValue"],
  setup(__props, { expose: __expose }) {
    const modelValue = useModel(__props, "modelValue");
    const props = __props;
    __expose({
      focus,
      select
    });
    const attrs = useAttrs();
    const textAreaElement = useTemplateRef("input");
    const internalPlaceholder = computed(() => props.placeholder || (isLegacy ? props.label : void 0));
    watch(() => props.labelOutside, () => {
      if (!props.labelOutside && !props.label) {
        logger$1.warn("[NcTextArea] You need to add a label to the NcInputField component. Either use the prop label or use an external one, as per the example in the documentation.");
      }
    });
    const ariaDescribedby = computed(() => {
      const ariaDescribedby2 = [];
      if (props.helperText) {
        ariaDescribedby2.push(`${props.id}-helper-text`);
      }
      if (typeof attrs["aria-describedby"] === "string") {
        ariaDescribedby2.push(attrs["aria-describedby"]);
      }
      return ariaDescribedby2.join(" ") || void 0;
    });
    function handleInput(event) {
      const { value } = event.target;
      modelValue.value = value;
    }
    function focus(options) {
      textAreaElement.value.focus(options);
    }
    function select() {
      textAreaElement.value.select();
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(["textarea", [
          _ctx.$attrs.class,
          {
            "textarea--disabled": __props.disabled,
            "textarea--legacy": unref(isLegacy)
          }
        ]])
      }, [
        createBaseVNode("div", _hoisted_1$K, [
          createBaseVNode("textarea", mergeProps({ ..._ctx.$attrs, class: void 0 }, {
            id: __props.id,
            ref: "input",
            "aria-describedby": ariaDescribedby.value,
            "aria-live": "polite",
            class: ["textarea__input", [
              __props.inputClass,
              {
                "textarea__input--label-outside": __props.labelOutside,
                "textarea__input--legacy": unref(isLegacy),
                "textarea__input--success": __props.success,
                "textarea__input--error": __props.error
              }
            ]],
            disabled: __props.disabled,
            placeholder: internalPlaceholder.value,
            style: { resize: __props.resize },
            value: modelValue.value,
            onInput: handleInput
          }), null, 16, _hoisted_2$A),
          !__props.labelOutside ? (openBlock(), createElementBlock("label", {
            key: 0,
            class: "textarea__label",
            for: __props.id
          }, toDisplayString(__props.label), 9, _hoisted_3$v)) : createCommentVNode("", true)
        ]),
        __props.helperText ? (openBlock(), createElementBlock("p", {
          key: 0,
          id: `${__props.id}-helper-text`,
          class: normalizeClass(["textarea__helper-text-message", {
            "textarea__helper-text-message--error": __props.error,
            "textarea__helper-text-message--success": __props.success
          }])
        }, [
          __props.success ? (openBlock(), createBlock(NcIconSvgWrapper, {
            key: 0,
            class: "textarea__helper-text-message__icon",
            path: unref(mdiCheck),
            inline: ""
          }, null, 8, ["path"])) : __props.error ? (openBlock(), createBlock(NcIconSvgWrapper, {
            key: 1,
            class: "textarea__helper-text-message__icon",
            path: unref(mdiAlertCircleOutline),
            inline: ""
          }, null, 8, ["path"])) : createCommentVNode("", true),
          createTextVNode(" " + toDisplayString(__props.helperText), 1)
        ], 10, _hoisted_4$v)) : createCommentVNode("", true)
      ], 2);
    };
  }
});
const NcTextArea = /* @__PURE__ */ _export_sfc(_sfc_main$T, [["__scopeId", "data-v-d327fb49"]]);
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const HEADERS = { "OCS-APIRequest": "true" };
function levelToRole(level) {
  if (level === void 0) {
    return "member";
  }
  if (level >= 9) {
    return "owner";
  }
  if (level >= 8) {
    return "admin";
  }
  if (level >= 4) {
    return "moderator";
  }
  return "member";
}
function mapResource(raw) {
  return {
    id: String(raw.id),
    name: raw.name,
    type: raw.type === "folder" ? "folder" : "file",
    iconUrl: raw.iconUrl,
    fallbackIcon: raw.fallbackIcon,
    url: raw.url
  };
}
function mapPreviewMember(raw) {
  return {
    id: raw.singleId,
    userId: raw.userId ?? null,
    displayName: raw.displayName,
    isUser: raw.type === 1,
    role: "member"
  };
}
function mapFullMember(raw) {
  return {
    id: raw.singleId,
    userId: raw.userId ?? null,
    displayName: raw.displayName,
    isUser: raw.userType === 1,
    role: levelToRole(raw.level)
  };
}
async function fetchTeams() {
  const [circlesRes, dashRes] = await Promise.allSettled([
    cancelableClient.get(generateOcsUrl("apps/circles/circles") + "?limit=-1", { headers: HEADERS }),
    cancelableClient.get(generateOcsUrl("apps/circles/teams/dashboard/widget") + "?limit=200&offset=0", { headers: HEADERS })
  ]);
  if (circlesRes.status === "rejected") {
    throw circlesRes.reason;
  }
  const circles2 = circlesRes.value.data.ocs.data ?? [];
  let dashboard = [];
  if (dashRes.status === "fulfilled") {
    dashboard = dashRes.value.data.ocs.data ?? [];
  } else {
    logger$2.warn("Failed to load team dashboard previews", { error: dashRes.reason });
  }
  const dashboardById = new Map(dashboard.map((team) => [team.singleId, team]));
  return circles2.map((circle) => {
    const extra = dashboardById.get(circle.id);
    return {
      id: circle.id,
      displayName: circle.displayName || circle.name,
      description: circle.description ?? "",
      memberCount: circle.population ?? extra?.members.length ?? 0,
      myRole: levelToRole(circle.initiator?.level),
      members: (extra?.members ?? []).map(mapPreviewMember),
      resources: (extra?.resources ?? []).map(mapResource)
    };
  });
}
async function fetchTeamMembers(teamId) {
  const res = await cancelableClient.get(
    generateOcsUrl("apps/circles/circles/{circleId}/members", { circleId: teamId }),
    { headers: HEADERS }
  );
  return (res.data.ocs.data ?? []).map(mapFullMember);
}
async function createTeam(name) {
  const res = await cancelableClient.post(
    generateOcsUrl("apps/circles/circles"),
    { name },
    { headers: HEADERS }
  );
  return res.data.ocs.data.id;
}
async function setTeamDescription(teamId, description) {
  await cancelableClient.put(
    generateOcsUrl("apps/circles/circles/{circleId}/description", { circleId: teamId }),
    { value: description },
    { headers: HEADERS }
  );
}
async function leaveTeam(teamId) {
  await cancelableClient.put(
    generateOcsUrl("apps/circles/circles/{circleId}/leave", { circleId: teamId }),
    {},
    { headers: HEADERS }
  );
}
async function deleteTeam(teamId) {
  await cancelableClient.delete(
    generateOcsUrl("apps/circles/circles/{circleId}", { circleId: teamId }),
    { headers: HEADERS }
  );
}
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const useTeamsStore = /* @__PURE__ */ defineStore("teams", {
  state: () => ({
    teams: [],
    loading: false,
    loadError: false,
    createDialogOpen: false
  }),
  getters: {
    // Find a single team by id.
    getTeam: (state2) => (id) => state2.teams.find((team) => team.id === id),
    // Filter teams by a free-text query against their display name.
    searchTeams: (state2) => (query) => {
      const needle = query.trim().toLowerCase();
      if (!needle) {
        return state2.teams;
      }
      return state2.teams.filter((team) => team.displayName.toLowerCase().includes(needle));
    }
  },
  actions: {
    /** Open the "create a new team" dialog. */
    openCreateTeamDialog() {
      this.createDialogOpen = true;
    },
    /** Load (or reload) the list of teams from the backend. */
    async loadTeams() {
      this.loading = true;
      this.loadError = false;
      try {
        this.teams = await fetchTeams();
      } catch (error) {
        this.loadError = true;
        logger$2.error("Failed to load teams", { error });
      } finally {
        this.loading = false;
      }
    },
    /**
     * Fetch the full member list (with roles) for a team.
     *
     * @param id - The team id
     */
    fetchTeamMembers(id) {
      return fetchTeamMembers(id);
    },
    /**
     * Create a team (optionally with a description), reload the list and
     * return it.
     *
     * @param displayName - The team name
     * @param description - An optional description
     */
    async createTeam(displayName, description = "") {
      const id = await createTeam(displayName.trim());
      const trimmedDescription = description.trim();
      if (trimmedDescription) {
        try {
          await setTeamDescription(id, trimmedDescription);
        } catch (error) {
          logger$2.warn("Failed to set team description", { error });
        }
      }
      await this.loadTeams();
      return this.getTeam(id);
    },
    /**
     * Leave a team and reload the list.
     *
     * @param id - The team id
     */
    async leaveTeam(id) {
      await leaveTeam(id);
      await this.loadTeams();
    },
    /**
     * Delete a team and reload the list.
     *
     * @param id - The team id
     */
    async deleteTeam(id) {
      await deleteTeam(id);
      await this.loadTeams();
    }
  }
});
const _sfc_main$S = /* @__PURE__ */ defineComponent({
  __name: "CreateTeamDialog",
  emits: ["close"],
  setup(__props, { emit: __emit }) {
    const emit2 = __emit;
    const router2 = useRouter();
    const store2 = useTeamsStore();
    const name = ref("");
    const description = ref("");
    const submitting = ref(false);
    const canCreate = computed(() => name.value.trim().length > 0 && !submitting.value);
    function onUpdateOpen(value) {
      if (!value) {
        emit2("close");
      }
    }
    async function submit() {
      if (!canCreate.value) {
        return;
      }
      submitting.value = true;
      try {
        const team = await store2.createTeam(name.value, description.value);
        showSuccess(translate("circles", 'Team "{name}" created', { name: name.value.trim() }));
        if (team) {
          router2.push({ name: "team", params: { teamId: team.id } });
        }
        emit2("close");
      } catch (error) {
        logger$2.error("Failed to create team", { error });
        showError(translate("circles", "Could not create the team"));
      } finally {
        submitting.value = false;
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcDialog), {
        name: unref(translate)("circles", "Create a new team"),
        size: "normal",
        "onUpdate:open": onUpdateOpen
      }, {
        actions: withCtx(() => [
          createVNode(unref(NcButton), {
            variant: "tertiary",
            onClick: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("close"))
          }, {
            default: withCtx(() => [
              createTextVNode(toDisplayString(unref(translate)("circles", "Cancel")), 1)
            ]),
            _: 1
          }),
          createVNode(unref(NcButton), {
            variant: "primary",
            disabled: !canCreate.value,
            onClick: submit
          }, {
            default: withCtx(() => [
              createTextVNode(toDisplayString(unref(translate)("circles", "Create team")), 1)
            ]),
            _: 1
          }, 8, ["disabled"])
        ]),
        default: withCtx(() => [
          createBaseVNode("form", {
            class: "create-team",
            onSubmit: withModifiers(submit, ["prevent"])
          }, [
            createVNode(unref(_sfc_main$W), {
              modelValue: name.value,
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => name.value = $event),
              label: unref(translate)("circles", "Team name"),
              placeholder: unref(translate)("circles", "e.g. Design")
            }, null, 8, ["modelValue", "label", "placeholder"]),
            createVNode(unref(NcTextArea), {
              modelValue: description.value,
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => description.value = $event),
              label: unref(translate)("circles", "Description (optional)"),
              placeholder: unref(translate)("circles", "What is this team about?"),
              rows: "3"
            }, null, 8, ["modelValue", "label", "placeholder"])
          ], 32)
        ]),
        _: 1
      }, 8, ["name"]);
    };
  }
});
const _sfc_main$R = {
  name: "NcAppNavigationList"
};
const _hoisted_1$J = { class: "app-navigation-list" };
function _sfc_render$E(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("ul", _hoisted_1$J, [
    renderSlot(_ctx.$slots, "default", {}, void 0, true)
  ]);
}
const NcAppNavigationList = /* @__PURE__ */ _export_sfc(_sfc_main$R, [["render", _sfc_render$E], ["__scopeId", "data-v-d72957ed"]]);
register(t20);
const _hoisted_1$1$2 = { class: "app-navigation-toggle-wrapper" };
const _sfc_main$1$3 = /* @__PURE__ */ defineComponent({
  __name: "NcAppNavigationToggle",
  props: {
    "open": { type: Boolean, ...{ required: true } },
    "openModifiers": {}
  },
  emits: ["update:open"],
  setup(__props) {
    const open = useModel(__props, "open");
    const title = computed(() => open.value ? t$1("Close navigation") : t$1("Open navigation"));
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", _hoisted_1$1$2, [
        createVNode(unref(NcButton), {
          class: "app-navigation-toggle",
          "aria-controls": "app-navigation-vue",
          "aria-expanded": open.value ? "true" : "false",
          "aria-label": title.value,
          title: title.value,
          variant: "tertiary",
          onClick: _cache[0] || (_cache[0] = ($event) => open.value = !open.value)
        }, {
          icon: withCtx(() => [
            createVNode(NcIconSvgWrapper, {
              path: open.value ? unref(mdiMenuOpen) : unref(mdiMenu)
            }, null, 8, ["path"])
          ]),
          _: 1
        }, 8, ["aria-expanded", "aria-label", "title"])
      ]);
    };
  }
});
const NcAppNavigationToggle = /* @__PURE__ */ _export_sfc(_sfc_main$1$3, [["__scopeId", "data-v-5a15295d"]]);
const _hoisted_1$I = ["aria-hidden", "aria-label", "aria-labelledby", "inert"];
const _hoisted_2$z = { class: "app-navigation__search" };
const _sfc_main$Q = /* @__PURE__ */ defineComponent({
  __name: "NcAppNavigation",
  props: {
    ariaLabel: {},
    ariaLabelledby: {}
  },
  setup(__props) {
    const props = __props;
    let focusTrap;
    const setHasAppNavigation = inject(
      HAS_APP_NAVIGATION_KEY,
      () => warn(),
      false
    );
    const appNavigationContainerElement = useTemplateRef("appNavigationContainer");
    const isMobile = useIsMobile();
    const open = ref(!isMobile.value);
    watchEffect(() => {
      if (!props.ariaLabel && !props.ariaLabelledby) ;
    });
    watch(isMobile, () => {
      open.value = !isMobile.value;
    });
    watch(open, () => {
      toggleFocusTrap();
    });
    onMounted(() => {
      setHasAppNavigation(true);
      subscribe("toggle-navigation", toggleNavigationByEventBus);
      emit("navigation-toggled", {
        open: open.value
      });
      focusTrap = createFocusTrap(appNavigationContainerElement.value, {
        allowOutsideClick: true,
        clickOutsideDeactivates: () => {
          if (isMobile.value) {
            focusTrap.deactivate({ returnFocus: false });
            toggleNavigation(false);
          }
          return false;
        },
        fallbackFocus: appNavigationContainerElement.value,
        trapStack: getTrapStack(),
        escapeDeactivates: false
      });
      toggleFocusTrap();
    });
    onUnmounted(() => {
      setHasAppNavigation(false);
      unsubscribe("toggle-navigation", toggleNavigationByEventBus);
      focusTrap.deactivate();
    });
    function toggleNavigation(state2) {
      if (open.value === state2) {
        emit("navigation-toggled", {
          open: open.value
        });
        return;
      }
      open.value = state2 === void 0 ? !open.value : state2;
      const bodyStyles = getComputedStyle(document.body);
      const animationLength = parseInt(bodyStyles.getPropertyValue("--animation-quick")) || 100;
      setTimeout(() => {
        emit("navigation-toggled", {
          open: open.value
        });
      }, 1.5 * animationLength);
    }
    function toggleNavigationByEventBus({ open: open2 }) {
      return toggleNavigation(open2);
    }
    function toggleFocusTrap() {
      if (isMobile.value && open.value) {
        focusTrap.activate();
      } else {
        focusTrap.deactivate();
      }
    }
    function handleEsc() {
      if (isMobile.value) {
        toggleNavigation(false);
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        ref: "appNavigationContainer",
        class: normalizeClass(["app-navigation", {
          "app-navigation--closed": !open.value,
          "app-navigation--legacy": unref(isLegacy34)
        }])
      }, [
        createBaseVNode("nav", {
          id: "app-navigation-vue",
          "aria-hidden": open.value ? "false" : "true",
          "aria-label": __props.ariaLabel || void 0,
          "aria-labelledby": __props.ariaLabelledby || void 0,
          class: "app-navigation__content",
          inert: !open.value || void 0,
          onKeydown: withKeys(handleEsc, ["esc"])
        }, [
          createBaseVNode("div", _hoisted_2$z, [
            renderSlot(_ctx.$slots, "search", {}, void 0, true)
          ]),
          createBaseVNode("div", {
            class: normalizeClass(["app-navigation__body", { "app-navigation__body--no-list": !_ctx.$slots.list }])
          }, [
            renderSlot(_ctx.$slots, "default", {}, void 0, true)
          ], 2),
          _ctx.$slots.list ? (openBlock(), createBlock(NcAppNavigationList, {
            key: 0,
            class: "app-navigation__list"
          }, {
            default: withCtx(() => [
              renderSlot(_ctx.$slots, "list", {}, void 0, true)
            ]),
            _: 3
          })) : createCommentVNode("", true),
          renderSlot(_ctx.$slots, "footer", {}, void 0, true)
        ], 40, _hoisted_1$I),
        createVNode(NcAppNavigationToggle, {
          open: open.value,
          "onUpdate:open": toggleNavigation
        }, null, 8, ["open"])
      ], 2);
    };
  }
});
const NcAppNavigation = /* @__PURE__ */ _export_sfc(_sfc_main$Q, [["__scopeId", "data-v-104ef656"]]);
const _sfc_main$P = {
  name: "ChevronUpIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$H = ["aria-hidden", "aria-label"];
const _hoisted_2$y = ["fill", "width", "height"];
const _hoisted_3$u = { d: "M7.41,15.41L12,10.83L16.59,15.41L18,14L12,8L6,14L7.41,15.41Z" };
const _hoisted_4$u = { key: 0 };
function _sfc_render$D(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon chevron-up-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$u, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$u, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$y))
  ], 16, _hoisted_1$H);
}
const ChevronUp = /* @__PURE__ */ _export_sfc(_sfc_main$P, [["render", _sfc_render$D]]);
const _sfc_main$O = {
  name: "ArrowRightIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$G = ["aria-hidden", "aria-label"];
const _hoisted_2$x = ["fill", "width", "height"];
const _hoisted_3$t = { d: "M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" };
const _hoisted_4$t = { key: 0 };
function _sfc_render$C(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon arrow-right-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$t, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$t, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$x))
  ], 16, _hoisted_1$G);
}
const IconArrowRight = /* @__PURE__ */ _export_sfc(_sfc_main$O, [["render", _sfc_render$C]]);
register(t14);
const _sfc_main$N = {
  name: "NcInputConfirmCancel",
  components: {
    IconArrowRight,
    IconClose,
    NcButton
  },
  setup() {
    return { isLegacy34 };
  },
  props: {
    /**
     * If this element is used on a primary element set to true for primary styling.
     */
    primary: {
      default: false,
      type: Boolean
    },
    /**
     * Placeholder of the edit field
     */
    placeholder: {
      default: "",
      type: String
    },
    /**
     * The current name (model value)
     */
    modelValue: {
      default: "",
      type: String
    }
  },
  emits: [
    "cancel",
    "confirm",
    "update:modelValue"
  ],
  data() {
    return {
      labelConfirm: t$1("Confirm changes"),
      labelCancel: t$1("Cancel changes")
    };
  },
  computed: {
    valueModel: {
      get() {
        return this.modelValue;
      },
      set(newValue) {
        this.$emit("update:modelValue", newValue);
      }
    }
  },
  methods: {
    confirm() {
      this.$emit("confirm");
    },
    cancel() {
      this.$emit("cancel");
    },
    focusInput() {
      this.$refs.input.focus();
    }
  }
};
const _hoisted_1$F = ["placeholder"];
function _sfc_render$B(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_IconArrowRight = resolveComponent("IconArrowRight");
  const _component_NcButton = resolveComponent("NcButton");
  const _component_IconClose = resolveComponent("IconClose");
  return openBlock(), createElementBlock("div", {
    class: normalizeClass(["app-navigation-input-confirm", { "app-navigation-input-confirm--legacy": $setup.isLegacy34 }])
  }, [
    createBaseVNode("form", {
      onSubmit: _cache[1] || (_cache[1] = withModifiers((...args) => $options.confirm && $options.confirm(...args), ["prevent"])),
      onKeydown: _cache[2] || (_cache[2] = withKeys(withModifiers((...args) => $options.cancel && $options.cancel(...args), ["exact", "stop", "prevent"]), ["esc"])),
      onClick: _cache[3] || (_cache[3] = withModifiers(() => {
      }, ["stop", "prevent"]))
    }, [
      withDirectives(createBaseVNode("input", {
        ref: "input",
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $options.valueModel = $event),
        type: "text",
        class: "app-navigation-input-confirm__input",
        placeholder: $props.placeholder
      }, null, 8, _hoisted_1$F), [
        [vModelText, $options.valueModel]
      ]),
      createVNode(_component_NcButton, {
        "aria-label": $data.labelConfirm,
        type: "submit",
        variant: "primary",
        onClick: withModifiers($options.confirm, ["stop", "prevent"])
      }, {
        icon: withCtx(() => [
          createVNode(_component_IconArrowRight, { size: 20 })
        ]),
        _: 1
      }, 8, ["aria-label", "onClick"]),
      createVNode(_component_NcButton, {
        "aria-label": $data.labelCancel,
        type: "reset",
        variant: $props.primary ? "primary" : "tertiary",
        onClick: withModifiers($options.cancel, ["stop", "prevent"])
      }, {
        icon: withCtx(() => [
          createVNode(_component_IconClose, { size: 20 })
        ]),
        _: 1
      }, 8, ["aria-label", "variant", "onClick"])
    ], 32)
  ], 2);
}
const NcInputConfirmCancel = /* @__PURE__ */ _export_sfc(_sfc_main$N, [["render", _sfc_render$B], ["__scopeId", "data-v-a8724c7f"]]);
const _sfc_main$M = defineComponent({
  name: "NcVNodes",
  props: {
    /**
     * The vnodes to render
     */
    vnodes: {
      type: [Array, Object],
      default: null
    }
  },
  /**
   * The render function to display the component
   */
  render() {
    return this.vnodes || this.$slots?.default?.({});
  }
});
const _sfc_main$3$1 = {
  name: "PencilIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$2$1 = ["aria-hidden", "aria-label"];
const _hoisted_2$2$1 = ["fill", "width", "height"];
const _hoisted_3$2$1 = { d: "M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z" };
const _hoisted_4$2$1 = { key: 0 };
function _sfc_render$3$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon pencil-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$2$1, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$2$1, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$2$1))
  ], 16, _hoisted_1$2$1);
}
const Pencil = /* @__PURE__ */ _export_sfc(_sfc_main$3$1, [["render", _sfc_render$3$1]]);
const _sfc_main$2$1 = {
  name: "UndoIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$1$1 = ["aria-hidden", "aria-label"];
const _hoisted_2$1$1 = ["fill", "width", "height"];
const _hoisted_3$1$1 = { d: "M12.5,8C9.85,8 7.45,9 5.6,10.6L2,7V16H11L7.38,12.38C8.77,11.22 10.54,10.5 12.5,10.5C16.04,10.5 19.05,12.81 20.1,16L22.47,15.22C21.08,11.03 17.15,8 12.5,8Z" };
const _hoisted_4$1$1 = { key: 0 };
function _sfc_render$2$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon undo-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$1$1, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$1$1, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$1$1))
  ], 16, _hoisted_1$1$1);
}
const Undo = /* @__PURE__ */ _export_sfc(_sfc_main$2$1, [["render", _sfc_render$2$1]]);
register(t21);
const _sfc_main$1$2 = {
  name: "NcAppNavigationIconCollapsible",
  components: {
    NcButton,
    ChevronDown,
    ChevronUp
  },
  setup() {
    return { isLegacy34 };
  },
  props: {
    /**
     * Is the list currently open (or collapsed)
     */
    open: {
      type: Boolean,
      required: true
    },
    /**
     * Is the navigation item currently active.
     */
    active: {
      type: Boolean,
      required: true
    }
  },
  emits: ["click"],
  computed: {
    labelButton() {
      return this.open ? t$1("Collapse menu") : t$1("Open menu");
    }
  },
  methods: {
    onClick(e) {
      this.$emit("click", e);
    }
  }
};
function _sfc_render$1$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ChevronUp = resolveComponent("ChevronUp");
  const _component_ChevronDown = resolveComponent("ChevronDown");
  const _component_NcButton = resolveComponent("NcButton");
  return openBlock(), createBlock(_component_NcButton, {
    class: normalizeClass(["icon-collapse", {
      "icon-collapse--active": $props.active,
      "icon-collapse--open": $props.open
    }]),
    "aria-label": $options.labelButton,
    variant: $props.active && $setup.isLegacy34 ? "tertiary-on-primary" : "tertiary",
    onClick: $options.onClick
  }, {
    icon: withCtx(() => [
      $props.open ? (openBlock(), createBlock(_component_ChevronUp, {
        key: 0,
        size: 20
      })) : (openBlock(), createBlock(_component_ChevronDown, {
        key: 1,
        size: 20
      }))
    ]),
    _: 1
  }, 8, ["class", "aria-label", "variant", "onClick"]);
}
const NcAppNavigationIconCollapsible = /* @__PURE__ */ _export_sfc(_sfc_main$1$2, [["render", _sfc_render$1$1], ["__scopeId", "data-v-acf5ed2f"]]);
register(t23, t51);
const _sfc_main$L = {
  name: "NcAppNavigationItem",
  components: {
    NcActions,
    NcActionButton,
    NcAppNavigationIconCollapsible,
    NcInputConfirmCancel,
    NcLoadingIcon,
    NcVNodes: _sfc_main$M,
    Pencil,
    Undo
  },
  props: {
    /**
     * If you are not using vue-router you can use the property to set this item as the active navigation entry.
     * When using vue-router and the `to` property this is set automatically.
     */
    active: {
      type: Boolean,
      default: false
    },
    /**
     * The main text content of the entry.
     */
    name: {
      type: String,
      required: true
    },
    /**
     * The title attribute of the element.
     */
    title: {
      type: String,
      default: null
    },
    /**
     * id attribute of the list item element
     */
    id: {
      type: String,
      default: () => createElementId(),
      validator: (id) => id.trim() !== ""
    },
    /**
     * Refers to the icon on the left, this prop accepts a class
     * like 'icon-category-enabled'.
     */
    icon: {
      type: String,
      default: ""
    },
    /**
     * Displays a loading animated icon on the left of the element
     * instead of the icon.
     */
    loading: {
      type: Boolean,
      default: false
    },
    /**
     * Passing in a route will make the root element of this
     * component a `<router-link />` that points to that route.
     * By leaving this blank, the root element will be a `<li>`.
     */
    to: {
      type: [String, Object],
      default: null
    },
    /**
     * A direct link. This will be used as the `href` attribute.
     * This will ignore any `to` prop being defined.
     */
    href: {
      type: String,
      default: null
    },
    /**
     * Gives the possibility to collapse the children elements into the
     * parent element (true) or expands the children elements (false).
     */
    allowCollapse: {
      type: Boolean,
      default: false
    },
    /**
     * Makes the name of the item editable by providing an `ActionButton`
     * component that toggles a form
     */
    editable: {
      type: Boolean,
      default: false
    },
    /**
     * Only for 'editable' items, sets label for the edit action button.
     */
    editLabel: {
      type: String,
      default: ""
    },
    /**
     * Only for items in 'editable' mode, sets the placeholder text for the editing form.
     */
    editPlaceholder: {
      type: String,
      default: ""
    },
    /**
     * Pins the item to the bottom left area, above the settings. Do not
     * place 'non-pinned' `AppnavigationItem` components below `pinned`
     * ones.
     */
    pinned: {
      type: Boolean,
      default: false
    },
    /**
     * Puts the item in the 'undo' state.
     */
    undo: {
      type: Boolean,
      default: false
    },
    /**
     * The navigation collapsible state (synced)
     */
    open: {
      type: Boolean,
      default: false
    },
    /**
     * The actions menu open state (synced)
     */
    menuOpen: {
      type: Boolean,
      default: false
    },
    /**
     * Force the actions to display in a three dot menu
     */
    forceMenu: {
      type: Boolean,
      default: false
    },
    /**
     * The action's menu default icon
     */
    menuIcon: {
      type: String,
      default: void 0
    },
    /**
     * The action's menu direction
     */
    menuPlacement: {
      type: String,
      default: "bottom"
    },
    /**
     * Entry aria details
     */
    ariaDescription: {
      type: String,
      default: null
    },
    /**
     * To be used only when the elements in the actions menu are very important
     */
    forceDisplayActions: {
      type: Boolean,
      default: false
    },
    /**
     * Number of action items outside the menu
     */
    inlineActions: {
      type: Number,
      default: 0
    }
  },
  emits: [
    "update:menuOpen",
    "update:open",
    "update:name",
    "click",
    "undo"
  ],
  setup() {
    return {
      isMobile: useIsMobile(),
      isLegacy34
    };
  },
  data() {
    return {
      actionsBoundariesElement: void 0,
      editingValue: "",
      opened: this.open,
      // Collapsible state
      editingActive: false,
      /**
       * Tracks the open state of the actions menu
       */
      menuOpenLocalValue: false,
      focused: false
    };
  },
  computed: {
    isRouterLink() {
      return this.to && !this.href;
    },
    // Checks if the component is already a children of another
    // instance of AppNavigationItem
    canHaveChildren() {
      if (this.$parent.$options._componentTag === "AppNavigationItem") {
        return false;
      } else {
        return true;
      }
    },
    editButtonAriaLabel() {
      return this.editLabel ? this.editLabel : t$1("Edit item");
    },
    undoButtonAriaLabel() {
      return t$1("Undo changes");
    }
  },
  watch: {
    open(newVal) {
      this.opened = newVal;
    }
  },
  mounted() {
    this.actionsBoundariesElement = document.querySelector("#content-vue") || void 0;
  },
  methods: {
    // sync opened menu state with prop
    onMenuToggle(state2) {
      this.$emit("update:menuOpen", state2);
      this.menuOpenLocalValue = state2;
    },
    // toggle the collapsible state
    toggleCollapse() {
      this.opened = !this.opened;
      this.$emit("update:open", this.opened);
    },
    /**
     * Handle link click
     *
     * @param {PointerEvent} event - Native click event
     * @param {Function} [navigate] - VueRouter link's navigate if any
     * @param {string} [routerLinkHref] - VueRouter link's href
     */
    onClick(event, navigate, routerLinkHref) {
      this.$emit("click", event);
      if (event.metaKey || event.altKey || event.ctrlKey || event.shiftKey) {
        return;
      }
      if (routerLinkHref) {
        navigate?.(event);
        event.preventDefault();
      }
    },
    // Edition methods
    handleEdit() {
      this.editingValue = this.name;
      this.editingActive = true;
      this.onMenuToggle(false);
      this.$nextTick(() => {
        this.$refs.editingInput.focusInput();
      });
    },
    cancelEditing() {
      this.editingActive = false;
    },
    handleEditingDone() {
      this.$emit("update:name", this.editingValue);
      this.editingValue = "";
      this.editingActive = false;
    },
    // Undo methods
    handleUndo() {
      this.$emit("undo");
    },
    /**
     * Show actions upon focus
     */
    handleFocus() {
      this.focused = true;
    },
    handleBlur() {
      this.focused = false;
    },
    /**
     * This method checks if the root element of the component is focused and
     * if that's the case it focuses the actions button if available
     *
     * @param {Event} e the keydown event
     */
    handleTab(e) {
      if (!this.$refs.actions) {
        return;
      }
      if (this.focused) {
        e.preventDefault();
        this.$refs.actions.$refs.triggerButton.$el.focus();
        this.focused = false;
      } else {
        this.$refs.actions.$refs.triggerButton.$el.blur();
      }
    },
    /**
     * Is this an external link
     *
     * @param {string} href The link to check
     * @return {boolean} Whether it is external or not
     */
    isExternal(href) {
      return href && href.match(/[a-z]+:\/\//i);
    }
  }
};
const _hoisted_1$E = ["id"];
const _hoisted_2$w = ["aria-current", "aria-description", "aria-expanded", "href", "target", "title", "onClick"];
const _hoisted_3$s = {
  key: 0,
  class: "editingContainer"
};
const _hoisted_4$s = {
  key: 1,
  class: "app-navigation-entry__deleted"
};
const _hoisted_5$4 = { class: "app-navigation-entry__deleted-description" };
const _hoisted_6$3 = {
  key: 0,
  class: "app-navigation-entry__counter-wrapper"
};
const _hoisted_7$3 = {
  key: 0,
  class: "app-navigation-entry__children"
};
function _sfc_render$A(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcLoadingIcon = resolveComponent("NcLoadingIcon");
  const _component_NcInputConfirmCancel = resolveComponent("NcInputConfirmCancel");
  const _component_Pencil = resolveComponent("Pencil");
  const _component_NcActionButton = resolveComponent("NcActionButton");
  const _component_Undo = resolveComponent("Undo");
  const _component_NcActions = resolveComponent("NcActions");
  const _component_NcAppNavigationIconCollapsible = resolveComponent("NcAppNavigationIconCollapsible");
  return openBlock(), createElementBlock("li", {
    id: $props.id,
    class: normalizeClass([{
      "app-navigation-entry--opened": $data.opened,
      "app-navigation-entry--pinned": $props.pinned,
      "app-navigation-entry--collapsible": $props.allowCollapse && !!_ctx.$slots.default
    }, "app-navigation-entry-wrapper"])
  }, [
    (openBlock(), createBlock(resolveDynamicComponent($options.isRouterLink ? "router-link" : "NcVNodes"), normalizeProps(guardReactiveProps({ ...$options.isRouterLink && { custom: true, to: $props.to } })), {
      default: withCtx(({ href: routerLinkHref, navigate, isActive }) => [
        createBaseVNode("div", {
          class: normalizeClass(["app-navigation-entry", {
            "app-navigation-entry--editing": $data.editingActive,
            "app-navigation-entry--deleted": $props.undo,
            "app-navigation-entry--legacy": $setup.isLegacy34,
            active: $props.to && isActive || $props.active
          }])
        }, [
          !$props.undo ? (openBlock(), createElementBlock("a", {
            key: 0,
            class: "app-navigation-entry-link",
            "aria-current": $props.active || $props.to && isActive ? "page" : void 0,
            "aria-description": $props.ariaDescription,
            "aria-expanded": !!_ctx.$slots.default ? $data.opened.toString() : void 0,
            href: $props.href || routerLinkHref || "#",
            target: $options.isExternal($props.href) ? "_blank" : void 0,
            title: $props.title || $props.name,
            onBlur: _cache[1] || (_cache[1] = (...args) => $options.handleBlur && $options.handleBlur(...args)),
            onClick: ($event) => $options.onClick($event, navigate, routerLinkHref),
            onFocus: _cache[2] || (_cache[2] = (...args) => $options.handleFocus && $options.handleFocus(...args)),
            onKeydown: _cache[3] || (_cache[3] = withKeys(withModifiers((...args) => $options.handleTab && $options.handleTab(...args), ["exact"]), ["tab"]))
          }, [
            createBaseVNode("div", {
              class: normalizeClass(["app-navigation-entry-icon", { [$props.icon]: $props.icon }])
            }, [
              $props.loading ? (openBlock(), createBlock(_component_NcLoadingIcon, { key: 0 })) : renderSlot(_ctx.$slots, "icon", {
                key: 1,
                active: $props.active || $props.to && isActive
              }, void 0, true)
            ], 2),
            createBaseVNode("span", {
              class: normalizeClass(["app-navigation-entry__name", { "hidden-visually": $data.editingActive }])
            }, toDisplayString($props.name), 3),
            $data.editingActive ? (openBlock(), createElementBlock("div", _hoisted_3$s, [
              createVNode(_component_NcInputConfirmCancel, {
                ref: "editingInput",
                modelValue: $data.editingValue,
                "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.editingValue = $event),
                placeholder: $props.editPlaceholder !== "" ? $props.editPlaceholder : $props.name,
                primary: $props.to && isActive || $props.active,
                onCancel: $options.cancelEditing,
                onConfirm: $options.handleEditingDone
              }, null, 8, ["modelValue", "placeholder", "primary", "onCancel", "onConfirm"])
            ])) : createCommentVNode("", true)
          ], 40, _hoisted_2$w)) : createCommentVNode("", true),
          $props.undo ? (openBlock(), createElementBlock("div", _hoisted_4$s, [
            createBaseVNode("div", _hoisted_5$4, toDisplayString($props.name), 1)
          ])) : createCommentVNode("", true),
          (!!_ctx.$slots.actions || !!_ctx.$slots.counter || $props.editable || $props.undo) && !$data.editingActive ? (openBlock(), createElementBlock("div", {
            key: 2,
            class: normalizeClass(["app-navigation-entry__utils", { "app-navigation-entry__utils--display-actions": $props.forceDisplayActions || $data.menuOpenLocalValue || $props.menuOpen }])
          }, [
            !!_ctx.$slots.counter ? (openBlock(), createElementBlock("div", _hoisted_6$3, [
              renderSlot(_ctx.$slots, "counter", {}, void 0, true)
            ])) : createCommentVNode("", true),
            !!_ctx.$slots.actions || $props.editable && !$data.editingActive || $props.undo ? (openBlock(), createBlock(_component_NcActions, {
              key: 1,
              ref: "actions",
              class: "app-navigation-entry__actions",
              container: "#app-navigation-vue",
              boundariesElement: $data.actionsBoundariesElement,
              inline: $props.inlineActions,
              placement: $props.menuPlacement,
              open: $props.menuOpen,
              forceMenu: $props.forceMenu,
              defaultIcon: $props.menuIcon,
              variant: "tertiary",
              "onUpdate:open": $options.onMenuToggle
            }, {
              icon: withCtx(() => [
                renderSlot(_ctx.$slots, "menu-icon", {}, void 0, true)
              ]),
              default: withCtx(() => [
                $props.editable && !$data.editingActive ? (openBlock(), createBlock(_component_NcActionButton, {
                  key: 0,
                  "aria-label": $options.editButtonAriaLabel,
                  onClick: $options.handleEdit
                }, {
                  icon: withCtx(() => [
                    createVNode(_component_Pencil, { size: 20 })
                  ]),
                  default: withCtx(() => [
                    createTextVNode(" " + toDisplayString($props.editLabel), 1)
                  ]),
                  _: 1
                }, 8, ["aria-label", "onClick"])) : createCommentVNode("", true),
                $props.undo ? (openBlock(), createBlock(_component_NcActionButton, {
                  key: 1,
                  "aria-label": $options.undoButtonAriaLabel,
                  onClick: $options.handleUndo
                }, {
                  icon: withCtx(() => [
                    createVNode(_component_Undo, { size: 20 })
                  ]),
                  _: 1
                }, 8, ["aria-label", "onClick"])) : createCommentVNode("", true),
                renderSlot(_ctx.$slots, "actions", {}, void 0, true)
              ]),
              _: 3
            }, 8, ["boundariesElement", "inline", "placement", "open", "forceMenu", "defaultIcon", "onUpdate:open"])) : createCommentVNode("", true)
          ], 2)) : createCommentVNode("", true),
          $props.allowCollapse && !!_ctx.$slots.default ? (openBlock(), createBlock(_component_NcAppNavigationIconCollapsible, {
            key: 3,
            active: $props.to && isActive || $props.active,
            open: $data.opened,
            onClick: withModifiers($options.toggleCollapse, ["prevent", "stop"])
          }, null, 8, ["active", "open", "onClick"])) : createCommentVNode("", true),
          renderSlot(_ctx.$slots, "extra", {}, void 0, true)
        ], 2)
      ]),
      _: 3
    }, 16)),
    $options.canHaveChildren && !!_ctx.$slots.default ? (openBlock(), createElementBlock("ul", _hoisted_7$3, [
      renderSlot(_ctx.$slots, "default", {}, void 0, true)
    ])) : createCommentVNode("", true)
  ], 10, _hoisted_1$E);
}
const NcAppNavigationItem = /* @__PURE__ */ _export_sfc(_sfc_main$L, [["render", _sfc_render$A], ["__scopeId", "data-v-e4d562ae"]]);
const _sfc_main$K = {
  components: {
    NcButton
  },
  props: {
    /**
     * Id of the button
     */
    buttonId: {
      type: String,
      required: false,
      default: ""
    },
    /**
     * Disabled state of the button
     */
    disabled: {
      type: Boolean,
      required: false,
      default: false
    },
    /**
     * Main text of the button
     */
    text: {
      type: String,
      required: true
    },
    /**
     * The color variant to use.
     *
     * @default 'primary'
     */
    variant: {
      type: String,
      default: "primary",
      validator(value) {
        return ["primary", "secondary", "tertiary"].indexOf(value) !== -1;
      }
    }
  },
  emits: ["click"]
};
const _hoisted_1$D = { class: "app-navigation-new" };
function _sfc_render$z(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcButton = resolveComponent("NcButton");
  return openBlock(), createElementBlock("div", _hoisted_1$D, [
    createVNode(_component_NcButton, {
      id: $props.buttonId,
      disabled: $props.disabled,
      variant: $props.variant,
      onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click"))
    }, {
      icon: withCtx(() => [
        renderSlot(_ctx.$slots, "icon", {}, void 0, true)
      ]),
      default: withCtx(() => [
        createTextVNode(" " + toDisplayString($props.text), 1)
      ]),
      _: 3
    }, 8, ["id", "disabled", "variant"])
  ]);
}
const NcAppNavigationNew = /* @__PURE__ */ _export_sfc(_sfc_main$K, [["render", _sfc_render$z], ["__scopeId", "data-v-0ba6c9df"]]);
register(t16, t44);
const _sfc_main$J = /* @__PURE__ */ defineComponent({
  __name: "NcAppNavigationSearch",
  props: /* @__PURE__ */ mergeModels({
    /**
     * Text used to label the search input
     */
    label: {
      type: String,
      default: t$1("Search …")
    },
    /**
     * Placeholder of the search input
     * By default the value of `label` is used.
     */
    placeholder: {
      type: String,
      default: null
    }
  }, {
    "modelValue": { default: "" },
    "modelModifiers": {}
  }),
  emits: ["update:modelValue"],
  setup(__props) {
    const model = useModel(__props, "modelValue");
    const slots = useSlots();
    const inputElement = ref();
    const { focused: inputHasFocus } = useFocusWithin(inputElement);
    const transitionTimeout = Number.parseInt(window.getComputedStyle(window.document.body).getPropertyValue("--animation-quick")) || 100;
    const actionsContainerElement = useTemplateRef("actionsContainer");
    const hasActions = () => !!slots.actions?.({});
    const showActions = ref(true);
    const timeoutId = ref();
    const hideActions = ref(false);
    watch(inputHasFocus, () => {
      showActions.value = !inputHasFocus.value;
      window.clearTimeout(timeoutId.value);
      if (showActions.value) {
        hideActions.value = false;
      } else {
        window.setTimeout(() => {
          hideActions.value = !showActions.value;
        }, transitionTimeout);
      }
    });
    function onCloseSearch() {
      model.value = "";
      if (hasActions()) {
        showActions.value = true;
        nextTick(() => actionsContainerElement.value?.querySelector("button")?.focus());
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(["app-navigation-search", {
          "app-navigation-search--has-actions": hasActions()
        }])
      }, [
        createVNode(NcInputField, {
          ref_key: "inputElement",
          ref: inputElement,
          modelValue: model.value,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => model.value = $event),
          "aria-label": __props.label,
          class: "app-navigation-search__input",
          labelOutside: "",
          placeholder: __props.placeholder ?? __props.label,
          showTrailingButton: model.value.length > 0,
          trailingButtonLabel: unref(t$1)("Clear search"),
          type: "search",
          onTrailingButtonClick: onCloseSearch
        }, {
          "trailing-button-icon": withCtx(() => [
            createVNode(IconClose, { size: 20 })
          ]),
          _: 1
        }, 8, ["modelValue", "aria-label", "placeholder", "showTrailingButton", "trailingButtonLabel"]),
        hasActions() ? (openBlock(), createElementBlock("div", {
          key: 0,
          ref: "actionsContainer",
          class: normalizeClass(["app-navigation-search__actions", {
            "app-navigation-search__actions--hidden": !showActions.value,
            "hidden-visually": hideActions.value
          }])
        }, [
          renderSlot(_ctx.$slots, "actions", {}, void 0, true)
        ], 2)) : createCommentVNode("", true)
      ], 2);
    };
  }
});
const NcAppNavigationSearch = /* @__PURE__ */ _export_sfc(_sfc_main$J, [["__scopeId", "data-v-191b6717"]]);
const _sfc_main$I = /* @__PURE__ */ defineComponent({
  __name: "TeamAvatar",
  props: {
    displayName: {},
    size: { default: 32 }
  },
  setup(__props) {
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcAvatar), {
        displayName: __props.displayName,
        isNoUser: true,
        size: __props.size,
        hideStatus: "",
        disableMenu: "",
        disableTooltip: ""
      }, null, 8, ["displayName", "size"]);
    };
  }
});
const _sfc_main$H = /* @__PURE__ */ defineComponent({
  __name: "TeamNavigationItem",
  props: {
    team: {}
  },
  setup(__props) {
    const props = __props;
    const router2 = useRouter();
    const store2 = useTeamsStore();
    const to = computed(() => ({ name: "team", params: { teamId: props.team.id } }));
    const isOwner = computed(() => props.team.myRole === "owner");
    const canManage = computed(() => ["owner", "admin", "moderator"].includes(props.team.myRole));
    const canLeave = computed(() => !isOwner.value);
    const canDelete = computed(() => isOwner.value);
    async function onManage() {
      await router2.push(to.value);
      emit("contacts:circles:append", props.team.id);
    }
    async function onCopyLink() {
      const href = window.location.origin + router2.resolve(to.value).href;
      try {
        await navigator.clipboard.writeText(href);
        showSuccess(translate("circles", "Link copied to the clipboard"));
      } catch (error) {
        logger$2.error("Could not copy link", { error });
        showError(translate("circles", "Could not copy link to the clipboard"));
      }
    }
    async function onLeave() {
      const confirmed = await showConfirmation({
        name: translate("circles", "Leave team"),
        text: translate("circles", "Are you sure you want to leave {team}?", { team: props.team.displayName }),
        labelConfirm: translate("circles", "Leave team"),
        labelReject: translate("circles", "Cancel"),
        severity: "warning"
      });
      if (!confirmed) {
        return;
      }
      try {
        await store2.leaveTeam(props.team.id);
        showSuccess(translate("circles", 'You left "{name}"', { name: props.team.displayName }));
        if (router2.currentRoute.value.params.teamId === props.team.id) {
          router2.push({ name: "home" });
        }
      } catch (error) {
        logger$2.error("Could not leave the team", { error });
        showError(translate("circles", "Could not leave the team"));
      }
    }
    async function onDelete() {
      const confirmed = await showConfirmation({
        name: translate("circles", "Delete team"),
        text: translate("circles", "Are you sure you want to delete {team}? This cannot be undone.", { team: props.team.displayName }),
        labelConfirm: translate("circles", "Delete team"),
        labelReject: translate("circles", "Cancel"),
        severity: "error"
      });
      if (!confirmed) {
        return;
      }
      try {
        await store2.deleteTeam(props.team.id);
        showSuccess(translate("circles", "Team deleted"));
        if (router2.currentRoute.value.params.teamId === props.team.id) {
          router2.push({ name: "home" });
        }
      } catch (error) {
        logger$2.error("Could not delete the team", { error });
        showError(translate("circles", "Could not delete the team"));
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcAppNavigationItem), {
        name: __props.team.displayName,
        to: to.value
      }, {
        icon: withCtx(() => [
          createVNode(_sfc_main$I, {
            displayName: __props.team.displayName,
            size: 32
          }, null, 8, ["displayName"])
        ]),
        actions: withCtx(() => [
          canManage.value ? (openBlock(), createBlock(unref(NcActionButton), {
            key: 0,
            closeAfterClick: "",
            onClick: onManage
          }, {
            icon: withCtx(() => [
              createVNode(unref(NcIconSvgWrapper), {
                path: unref(mdiCogOutline),
                size: 20
              }, null, 8, ["path"])
            ]),
            default: withCtx(() => [
              createTextVNode(" " + toDisplayString(unref(translate)("circles", "Manage team")), 1)
            ]),
            _: 1
          })) : createCommentVNode("", true),
          createVNode(unref(NcActionButton), {
            closeAfterClick: "",
            onClick: onCopyLink
          }, {
            icon: withCtx(() => [
              createVNode(unref(NcIconSvgWrapper), {
                path: unref(mdiContentCopy),
                size: 20
              }, null, 8, ["path"])
            ]),
            default: withCtx(() => [
              createTextVNode(" " + toDisplayString(unref(translate)("circles", "Copy link")), 1)
            ]),
            _: 1
          }),
          canLeave.value ? (openBlock(), createBlock(unref(NcActionButton), {
            key: 1,
            closeAfterClick: "",
            onClick: onLeave
          }, {
            icon: withCtx(() => [
              createVNode(unref(NcIconSvgWrapper), {
                path: unref(mdiExitToApp),
                size: 20
              }, null, 8, ["path"])
            ]),
            default: withCtx(() => [
              createTextVNode(" " + toDisplayString(unref(translate)("circles", "Leave team")), 1)
            ]),
            _: 1
          })) : createCommentVNode("", true),
          canDelete.value ? (openBlock(), createBlock(unref(NcActionButton), {
            key: 2,
            closeAfterClick: "",
            onClick: onDelete
          }, {
            icon: withCtx(() => [
              createVNode(unref(NcIconSvgWrapper), {
                path: unref(mdiTrashCanOutline),
                size: 20
              }, null, 8, ["path"])
            ]),
            default: withCtx(() => [
              createTextVNode(" " + toDisplayString(unref(translate)("circles", "Delete team")), 1)
            ]),
            _: 1
          })) : createCommentVNode("", true)
        ]),
        _: 1
      }, 8, ["name", "to"]);
    };
  }
});
const _hoisted_1$C = {
  key: 0,
  class: "global-navigation__loading"
};
const _sfc_main$G = /* @__PURE__ */ defineComponent({
  __name: "GlobalNavigation",
  setup(__props) {
    const store2 = useTeamsStore();
    const { loading } = storeToRefs(store2);
    const { openCreateTeamDialog } = store2;
    const route = useRoute();
    const query = ref("");
    const filteredTeams = computed(() => store2.searchTeams(query.value));
    const isOverviewActive = computed(() => route.name === "home");
    return (_ctx, _cache) => {
      return openBlock(), createBlock(unref(NcAppNavigation), {
        "aria-label": unref(translate)("circles", "Teams")
      }, {
        default: withCtx(() => [
          createVNode(unref(NcAppNavigationNew), {
            text: unref(translate)("circles", "New team"),
            onClick: _cache[0] || (_cache[0] = ($event) => unref(openCreateTeamDialog)())
          }, {
            icon: withCtx(() => [
              createVNode(unref(NcIconSvgWrapper), {
                path: unref(mdiPlus),
                size: 20
              }, null, 8, ["path"])
            ]),
            _: 1
          }, 8, ["text"]),
          createVNode(unref(NcAppNavigationSearch), {
            modelValue: query.value,
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => query.value = $event),
            class: "global-navigation__search",
            label: unref(translate)("circles", "Search teams")
          }, {
            icon: withCtx(() => [
              createVNode(unref(NcIconSvgWrapper), {
                path: unref(mdiMagnify),
                size: 20
              }, null, 8, ["path"])
            ]),
            _: 1
          }, 8, ["modelValue", "label"])
        ]),
        list: withCtx(() => [
          createVNode(unref(NcAppNavigationList), null, {
            default: withCtx(() => [
              createVNode(unref(NcAppNavigationItem), {
                name: unref(translate)("circles", "Overview"),
                to: { name: "home" }
              }, {
                icon: withCtx(() => [
                  createVNode(unref(NcIconSvgWrapper), {
                    path: isOverviewActive.value ? unref(mdiViewDashboard) : unref(mdiViewDashboardOutline),
                    size: 20
                  }, null, 8, ["path"])
                ]),
                _: 1
              }, 8, ["name"])
            ]),
            _: 1
          }),
          unref(loading) ? (openBlock(), createElementBlock("div", _hoisted_1$C, [
            createVNode(unref(NcLoadingIcon), { size: 32 })
          ])) : filteredTeams.value.length > 0 ? (openBlock(), createBlock(unref(NcAppNavigationList), { key: 1 }, {
            default: withCtx(() => [
              (openBlock(true), createElementBlock(Fragment, null, renderList(filteredTeams.value, (team) => {
                return openBlock(), createBlock(_sfc_main$H, {
                  key: team.id,
                  team
                }, null, 8, ["team"]);
              }), 128))
            ]),
            _: 1
          })) : (openBlock(), createBlock(unref(NcEmptyContent), {
            key: 2,
            class: "global-navigation__empty",
            name: unref(translate)("circles", "No teams found"),
            description: query.value ? unref(translate)("circles", "Try a different search.") : unref(translate)("circles", "Create a team to get started.")
          }, {
            icon: withCtx(() => [
              createVNode(unref(NcIconSvgWrapper), { path: unref(mdiAccountGroupOutline) }, null, 8, ["path"])
            ]),
            _: 1
          }, 8, ["name", "description"]))
        ]),
        _: 1
      }, 8, ["aria-label"]);
    };
  }
});
const _sfc_main$F = /* @__PURE__ */ defineComponent({
  __name: "App",
  setup(__props) {
    const store2 = useTeamsStore();
    const { createDialogOpen } = storeToRefs(store2);
    onMounted(() => store2.loadTeams());
    return (_ctx, _cache) => {
      const _component_RouterView = resolveComponent("RouterView");
      return openBlock(), createBlock(unref(NcContent), { appName: "teams" }, {
        default: withCtx(() => [
          createVNode(_sfc_main$G),
          createVNode(unref(NcAppContent), null, {
            default: withCtx(() => [
              createBaseVNode("div", {
                class: normalizeClass(_ctx.$style.teamsContent)
              }, [
                createVNode(_component_RouterView)
              ], 2)
            ]),
            _: 1
          }),
          unref(createDialogOpen) ? (openBlock(), createBlock(_sfc_main$S, {
            key: 0,
            onClose: _cache[0] || (_cache[0] = ($event) => createDialogOpen.value = false)
          })) : createCommentVNode("", true)
        ]),
        _: 1
      });
    };
  }
});
const teamsContent = "_teams-content_18s9l_1";
const style0$6 = {
  teamsContent
};
const cssModules$6 = {
  "$style": style0$6
};
const App = /* @__PURE__ */ _export_sfc$1(_sfc_main$F, [["__cssModules", cssModules$6]]);
const _hoisted_1$B = ["aria-label"];
const MAX_AVATARS = 5;
const _sfc_main$E = /* @__PURE__ */ defineComponent({
  __name: "TeamCard",
  props: {
    team: {}
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _cache) => {
      const _component_RouterLink = resolveComponent("RouterLink");
      return openBlock(), createBlock(_component_RouterLink, {
        class: normalizeClass(_ctx.$style.teamCard),
        to: { name: "team", params: { teamId: props.team.id } }
      }, {
        default: withCtx(() => [
          createBaseVNode("div", {
            class: normalizeClass(_ctx.$style.teamCardHead)
          }, [
            createVNode(_sfc_main$I, {
              displayName: __props.team.displayName,
              size: 44
            }, null, 8, ["displayName"]),
            createBaseVNode("span", {
              class: normalizeClass(_ctx.$style.teamCardName)
            }, toDisplayString(__props.team.displayName), 3)
          ], 2),
          __props.team.description ? (openBlock(), createElementBlock("p", {
            key: 0,
            class: normalizeClass(_ctx.$style.teamCardDescription)
          }, toDisplayString(__props.team.description), 3)) : createCommentVNode("", true),
          createBaseVNode("div", {
            class: normalizeClass(_ctx.$style.teamCardFooter)
          }, [
            createBaseVNode("ul", {
              class: normalizeClass(_ctx.$style.teamCardMembers),
              "aria-label": unref(translate)("circles", "Members")
            }, [
              (openBlock(true), createElementBlock(Fragment, null, renderList(__props.team.members.slice(0, MAX_AVATARS), (member) => {
                return openBlock(), createElementBlock("li", {
                  key: member.id,
                  class: normalizeClass(_ctx.$style.teamCardMember)
                }, [
                  createVNode(unref(NcAvatar), {
                    user: member.isUser ? member.id : void 0,
                    displayName: member.displayName,
                    isNoUser: !member.isUser,
                    size: 28,
                    hideStatus: ""
                  }, null, 8, ["user", "displayName", "isNoUser"])
                ], 2);
              }), 128)),
              __props.team.memberCount > __props.team.members.length ? (openBlock(), createElementBlock("li", {
                key: 0,
                class: normalizeClass(_ctx.$style.teamCardMemberMore)
              }, " +" + toDisplayString(__props.team.memberCount - __props.team.members.length), 3)) : createCommentVNode("", true)
            ], 10, _hoisted_1$B),
            createBaseVNode("span", {
              class: normalizeClass(_ctx.$style.teamCardResources)
            }, [
              createVNode(unref(NcIconSvgWrapper), {
                path: unref(mdiFolderMultipleOutline),
                size: 18,
                inline: ""
              }, null, 8, ["path"]),
              createTextVNode(" " + toDisplayString(unref(translatePlural)("circles", "%n resource", "%n resources", __props.team.resources.length)), 1)
            ], 2)
          ], 2)
        ]),
        _: 1
      }, 8, ["class", "to"]);
    };
  }
});
const teamCard = "_team-card_t2dhg_1";
const teamCardMember = "_team-card__member_t2dhg_17";
const teamCardMemberMore = "_team-card__member-more_t2dhg_18";
const teamCardHead = "_team-card__head_t2dhg_25";
const teamCardName = "_team-card__name_t2dhg_30";
const teamCardDescription = "_team-card__description_t2dhg_39";
const teamCardFooter = "_team-card__footer_t2dhg_47";
const teamCardMembers = "_team-card__members_t2dhg_54";
const teamCardResources = "_team-card__resources_t2dhg_100";
const style0$5 = {
  teamCard,
  teamCardMember,
  teamCardMemberMore,
  teamCardHead,
  teamCardName,
  teamCardDescription,
  teamCardFooter,
  teamCardMembers,
  teamCardResources
};
const cssModules$5 = {
  "$style": style0$5
};
const TeamCard = /* @__PURE__ */ _export_sfc$1(_sfc_main$E, [["__cssModules", cssModules$5]]);
const _hoisted_1$A = { class: "home-view__header" };
const _sfc_main$D = /* @__PURE__ */ defineComponent({
  __name: "HomeView",
  setup(__props) {
    const store2 = useTeamsStore();
    const { teams, loading, loadError } = storeToRefs(store2);
    const { loadTeams, openCreateTeamDialog } = store2;
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(_ctx.$style.homeView)
      }, [
        createBaseVNode("div", _hoisted_1$A, [
          createBaseVNode("h2", {
            class: normalizeClass(_ctx.$style.homeViewTitle)
          }, toDisplayString(unref(translate)("circles", "Teams")), 3),
          createBaseVNode("p", {
            class: normalizeClass(_ctx.$style.homeViewSubtitle)
          }, toDisplayString(unref(translate)("circles", "Your teams and everything shared with them across Nextcloud.")), 3)
        ]),
        unref(loading) && unref(teams).length === 0 ? (openBlock(), createElementBlock("div", {
          key: 0,
          class: normalizeClass(_ctx.$style.homeViewLoading)
        }, [
          createVNode(unref(NcLoadingIcon), { size: 44 })
        ], 2)) : unref(loadError) ? (openBlock(), createBlock(unref(NcEmptyContent), {
          key: 1,
          name: unref(translate)("circles", "Could not load teams"),
          description: unref(translate)("circles", "Something went wrong while loading your teams.")
        }, {
          icon: withCtx(() => [
            createVNode(unref(NcIconSvgWrapper), { path: unref(mdiAlertCircleOutline$1) }, null, 8, ["path"])
          ]),
          action: withCtx(() => [
            createVNode(unref(NcButton), {
              onClick: _cache[0] || (_cache[0] = ($event) => unref(loadTeams)())
            }, {
              default: withCtx(() => [
                createTextVNode(toDisplayString(unref(translate)("circles", "Try again")), 1)
              ]),
              _: 1
            })
          ]),
          _: 1
        }, 8, ["name", "description"])) : unref(teams).length === 0 ? (openBlock(), createBlock(unref(NcEmptyContent), {
          key: 2,
          name: unref(translate)("circles", "No teams yet"),
          description: unref(translate)("circles", "Create your first team to start collaborating.")
        }, {
          icon: withCtx(() => [
            createVNode(unref(NcIconSvgWrapper), { path: unref(mdiAccountGroupOutline) }, null, 8, ["path"])
          ]),
          action: withCtx(() => [
            createVNode(unref(NcButton), {
              variant: "primary",
              onClick: _cache[1] || (_cache[1] = ($event) => unref(openCreateTeamDialog)())
            }, {
              default: withCtx(() => [
                createTextVNode(toDisplayString(unref(translate)("circles", "Create your first team")), 1)
              ]),
              _: 1
            })
          ]),
          _: 1
        }, 8, ["name", "description"])) : (openBlock(), createElementBlock("section", {
          key: 3,
          class: normalizeClass(_ctx.$style.homeViewSection)
        }, [
          createBaseVNode("h3", {
            class: normalizeClass(_ctx.$style.homeViewSectionTitle)
          }, toDisplayString(unref(translate)("circles", "Your teams")), 3),
          createBaseVNode("div", {
            class: normalizeClass(_ctx.$style.homeViewGrid)
          }, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(unref(teams), (team) => {
              return openBlock(), createBlock(TeamCard, {
                key: team.id,
                team
              }, null, 8, ["team"]);
            }), 128))
          ], 2)
        ], 2))
      ], 2);
    };
  }
});
const homeView = "_home-view_hxw51_1";
const homeViewTitle = "_home-view__title_hxw51_9";
const homeViewSubtitle = "_home-view__subtitle_hxw51_14";
const homeViewLoading = "_home-view__loading_hxw51_18";
const homeViewSection = "_home-view__section_hxw51_23";
const homeViewSectionTitle = "_home-view__section-title_hxw51_28";
const homeViewGrid = "_home-view__grid_hxw51_33";
const style0$4 = {
  homeView,
  homeViewTitle,
  homeViewSubtitle,
  homeViewLoading,
  homeViewSection,
  homeViewSectionTitle,
  homeViewGrid
};
const cssModules$4 = {
  "$style": style0$4
};
const HomeView = /* @__PURE__ */ _export_sfc$1(_sfc_main$D, [["__cssModules", cssModules$4]]);
function getDevtoolsGlobalHook() {
  return getTarget().__VUE_DEVTOOLS_GLOBAL_HOOK__;
}
function getTarget() {
  return typeof navigator !== "undefined" && typeof window !== "undefined" ? window : typeof globalThis !== "undefined" ? globalThis : {};
}
const isProxyAvailable = typeof Proxy === "function";
const HOOK_SETUP = "devtools-plugin:setup";
const HOOK_PLUGIN_SETTINGS_SET = "plugin:settings:set";
let supported;
let perf;
function isPerformanceSupported() {
  var _a;
  if (supported !== void 0) {
    return supported;
  }
  if (typeof window !== "undefined" && window.performance) {
    supported = true;
    perf = window.performance;
  } else if (typeof globalThis !== "undefined" && ((_a = globalThis.perf_hooks) === null || _a === void 0 ? void 0 : _a.performance)) {
    supported = true;
    perf = globalThis.perf_hooks.performance;
  } else {
    supported = false;
  }
  return supported;
}
function now() {
  return isPerformanceSupported() ? perf.now() : Date.now();
}
class ApiProxy {
  constructor(plugin, hook) {
    this.target = null;
    this.targetQueue = [];
    this.onQueue = [];
    this.plugin = plugin;
    this.hook = hook;
    const defaultSettings = {};
    if (plugin.settings) {
      for (const id in plugin.settings) {
        const item = plugin.settings[id];
        defaultSettings[id] = item.defaultValue;
      }
    }
    const localSettingsSaveId = `__vue-devtools-plugin-settings__${plugin.id}`;
    let currentSettings = Object.assign({}, defaultSettings);
    try {
      const raw = localStorage.getItem(localSettingsSaveId);
      const data = JSON.parse(raw);
      Object.assign(currentSettings, data);
    } catch (e) {
    }
    this.fallbacks = {
      getSettings() {
        return currentSettings;
      },
      setSettings(value) {
        try {
          localStorage.setItem(localSettingsSaveId, JSON.stringify(value));
        } catch (e) {
        }
        currentSettings = value;
      },
      now() {
        return now();
      }
    };
    if (hook) {
      hook.on(HOOK_PLUGIN_SETTINGS_SET, (pluginId, value) => {
        if (pluginId === this.plugin.id) {
          this.fallbacks.setSettings(value);
        }
      });
    }
    this.proxiedOn = new Proxy({}, {
      get: (_target, prop) => {
        if (this.target) {
          return this.target.on[prop];
        } else {
          return (...args) => {
            this.onQueue.push({
              method: prop,
              args
            });
          };
        }
      }
    });
    this.proxiedTarget = new Proxy({}, {
      get: (_target, prop) => {
        if (this.target) {
          return this.target[prop];
        } else if (prop === "on") {
          return this.proxiedOn;
        } else if (Object.keys(this.fallbacks).includes(prop)) {
          return (...args) => {
            this.targetQueue.push({
              method: prop,
              args,
              resolve: () => {
              }
            });
            return this.fallbacks[prop](...args);
          };
        } else {
          return (...args) => {
            return new Promise((resolve) => {
              this.targetQueue.push({
                method: prop,
                args,
                resolve
              });
            });
          };
        }
      }
    });
  }
  async setRealTarget(target) {
    this.target = target;
    for (const item of this.onQueue) {
      this.target.on[item.method](...item.args);
    }
    for (const item of this.targetQueue) {
      item.resolve(await this.target[item.method](...item.args));
    }
  }
}
function setupDevtoolsPlugin(pluginDescriptor, setupFn) {
  const descriptor = pluginDescriptor;
  const target = getTarget();
  const hook = getDevtoolsGlobalHook();
  const enableProxy = isProxyAvailable && descriptor.enableEarlyProxy;
  if (hook && (target.__VUE_DEVTOOLS_PLUGIN_API_AVAILABLE__ || !enableProxy)) {
    hook.emit(HOOK_SETUP, pluginDescriptor, setupFn);
  } else {
    const proxy = enableProxy ? new ApiProxy(descriptor, hook) : null;
    const list = target.__VUE_DEVTOOLS_PLUGINS__ = target.__VUE_DEVTOOLS_PLUGINS__ || [];
    list.push({
      pluginDescriptor: descriptor,
      setupFn,
      proxy
    });
    if (proxy) {
      setupFn(proxy.proxiedTarget);
    }
  }
}
/*!
 * vuex v4.1.0
 * (c) 2022 Evan You
 * @license MIT
 */
var storeKey = "store";
function useStore(key) {
  if (key === void 0) key = null;
  return inject(key !== null ? key : storeKey);
}
function forEachValue(obj, fn) {
  Object.keys(obj).forEach(function(key) {
    return fn(obj[key], key);
  });
}
function isObject(obj) {
  return obj !== null && typeof obj === "object";
}
function isPromise(val) {
  return val && typeof val.then === "function";
}
function partial(fn, arg) {
  return function() {
    return fn(arg);
  };
}
function genericSubscribe(fn, subs, options) {
  if (subs.indexOf(fn) < 0) {
    options && options.prepend ? subs.unshift(fn) : subs.push(fn);
  }
  return function() {
    var i = subs.indexOf(fn);
    if (i > -1) {
      subs.splice(i, 1);
    }
  };
}
function resetStore(store2, hot) {
  store2._actions = /* @__PURE__ */ Object.create(null);
  store2._mutations = /* @__PURE__ */ Object.create(null);
  store2._wrappedGetters = /* @__PURE__ */ Object.create(null);
  store2._modulesNamespaceMap = /* @__PURE__ */ Object.create(null);
  var state2 = store2.state;
  installModule(store2, state2, [], store2._modules.root, true);
  resetStoreState(store2, state2, hot);
}
function resetStoreState(store2, state2, hot) {
  var oldState = store2._state;
  var oldScope = store2._scope;
  store2.getters = {};
  store2._makeLocalGettersCache = /* @__PURE__ */ Object.create(null);
  var wrappedGetters = store2._wrappedGetters;
  var computedObj = {};
  var computedCache = {};
  var scope = effectScope(true);
  scope.run(function() {
    forEachValue(wrappedGetters, function(fn, key) {
      computedObj[key] = partial(fn, store2);
      computedCache[key] = computed(function() {
        return computedObj[key]();
      });
      Object.defineProperty(store2.getters, key, {
        get: function() {
          return computedCache[key].value;
        },
        enumerable: true
        // for local getters
      });
    });
  });
  store2._state = reactive({
    data: state2
  });
  store2._scope = scope;
  if (store2.strict) {
    enableStrictMode(store2);
  }
  if (oldState) {
    if (hot) {
      store2._withCommit(function() {
        oldState.data = null;
      });
    }
  }
  if (oldScope) {
    oldScope.stop();
  }
}
function installModule(store2, rootState, path2, module, hot) {
  var isRoot = !path2.length;
  var namespace = store2._modules.getNamespace(path2);
  if (module.namespaced) {
    if (store2._modulesNamespaceMap[namespace] && false) ;
    store2._modulesNamespaceMap[namespace] = module;
  }
  if (!isRoot && !hot) {
    var parentState = getNestedState(rootState, path2.slice(0, -1));
    var moduleName = path2[path2.length - 1];
    store2._withCommit(function() {
      parentState[moduleName] = module.state;
    });
  }
  var local = module.context = makeLocalContext(store2, namespace, path2);
  module.forEachMutation(function(mutation, key) {
    var namespacedType = namespace + key;
    registerMutation(store2, namespacedType, mutation, local);
  });
  module.forEachAction(function(action, key) {
    var type = action.root ? key : namespace + key;
    var handler = action.handler || action;
    registerAction(store2, type, handler, local);
  });
  module.forEachGetter(function(getter, key) {
    var namespacedType = namespace + key;
    registerGetter(store2, namespacedType, getter, local);
  });
  module.forEachChild(function(child, key) {
    installModule(store2, rootState, path2.concat(key), child, hot);
  });
}
function makeLocalContext(store2, namespace, path2) {
  var noNamespace = namespace === "";
  var local = {
    dispatch: noNamespace ? store2.dispatch : function(_type, _payload, _options) {
      var args = unifyObjectStyle(_type, _payload, _options);
      var payload = args.payload;
      var options = args.options;
      var type = args.type;
      if (!options || !options.root) {
        type = namespace + type;
      }
      return store2.dispatch(type, payload);
    },
    commit: noNamespace ? store2.commit : function(_type, _payload, _options) {
      var args = unifyObjectStyle(_type, _payload, _options);
      var payload = args.payload;
      var options = args.options;
      var type = args.type;
      if (!options || !options.root) {
        type = namespace + type;
      }
      store2.commit(type, payload, options);
    }
  };
  Object.defineProperties(local, {
    getters: {
      get: noNamespace ? function() {
        return store2.getters;
      } : function() {
        return makeLocalGetters(store2, namespace);
      }
    },
    state: {
      get: function() {
        return getNestedState(store2.state, path2);
      }
    }
  });
  return local;
}
function makeLocalGetters(store2, namespace) {
  if (!store2._makeLocalGettersCache[namespace]) {
    var gettersProxy = {};
    var splitPos = namespace.length;
    Object.keys(store2.getters).forEach(function(type) {
      if (type.slice(0, splitPos) !== namespace) {
        return;
      }
      var localType = type.slice(splitPos);
      Object.defineProperty(gettersProxy, localType, {
        get: function() {
          return store2.getters[type];
        },
        enumerable: true
      });
    });
    store2._makeLocalGettersCache[namespace] = gettersProxy;
  }
  return store2._makeLocalGettersCache[namespace];
}
function registerMutation(store2, type, handler, local) {
  var entry = store2._mutations[type] || (store2._mutations[type] = []);
  entry.push(function wrappedMutationHandler(payload) {
    handler.call(store2, local.state, payload);
  });
}
function registerAction(store2, type, handler, local) {
  var entry = store2._actions[type] || (store2._actions[type] = []);
  entry.push(function wrappedActionHandler(payload) {
    var res = handler.call(store2, {
      dispatch: local.dispatch,
      commit: local.commit,
      getters: local.getters,
      state: local.state,
      rootGetters: store2.getters,
      rootState: store2.state
    }, payload);
    if (!isPromise(res)) {
      res = Promise.resolve(res);
    }
    if (store2._devtoolHook) {
      return res.catch(function(err) {
        store2._devtoolHook.emit("vuex:error", err);
        throw err;
      });
    } else {
      return res;
    }
  });
}
function registerGetter(store2, type, rawGetter, local) {
  if (store2._wrappedGetters[type]) {
    return;
  }
  store2._wrappedGetters[type] = function wrappedGetter(store22) {
    return rawGetter(
      local.state,
      // local state
      local.getters,
      // local getters
      store22.state,
      // root state
      store22.getters
      // root getters
    );
  };
}
function enableStrictMode(store2) {
  watch(function() {
    return store2._state.data;
  }, function() {
  }, { deep: true, flush: "sync" });
}
function getNestedState(state2, path2) {
  return path2.reduce(function(state22, key) {
    return state22[key];
  }, state2);
}
function unifyObjectStyle(type, payload, options) {
  if (isObject(type) && type.type) {
    options = payload;
    payload = type;
    type = type.type;
  }
  return { type, payload, options };
}
var LABEL_VUEX_BINDINGS = "vuex bindings";
var MUTATIONS_LAYER_ID = "vuex:mutations";
var ACTIONS_LAYER_ID = "vuex:actions";
var INSPECTOR_ID = "vuex";
var actionId = 0;
function addDevtools(app2, store2) {
  setupDevtoolsPlugin(
    {
      id: "org.vuejs.vuex",
      app: app2,
      label: "Vuex",
      homepage: "https://next.vuex.vuejs.org/",
      logo: "https://vuejs.org/images/icons/favicon-96x96.png",
      packageName: "vuex",
      componentStateTypes: [LABEL_VUEX_BINDINGS]
    },
    function(api) {
      api.addTimelineLayer({
        id: MUTATIONS_LAYER_ID,
        label: "Vuex Mutations",
        color: COLOR_LIME_500
      });
      api.addTimelineLayer({
        id: ACTIONS_LAYER_ID,
        label: "Vuex Actions",
        color: COLOR_LIME_500
      });
      api.addInspector({
        id: INSPECTOR_ID,
        label: "Vuex",
        icon: "storage",
        treeFilterPlaceholder: "Filter stores..."
      });
      api.on.getInspectorTree(function(payload) {
        if (payload.app === app2 && payload.inspectorId === INSPECTOR_ID) {
          if (payload.filter) {
            var nodes = [];
            flattenStoreForInspectorTree(nodes, store2._modules.root, payload.filter, "");
            payload.rootNodes = nodes;
          } else {
            payload.rootNodes = [
              formatStoreForInspectorTree(store2._modules.root, "")
            ];
          }
        }
      });
      api.on.getInspectorState(function(payload) {
        if (payload.app === app2 && payload.inspectorId === INSPECTOR_ID) {
          var modulePath = payload.nodeId;
          makeLocalGetters(store2, modulePath);
          payload.state = formatStoreForInspectorState(
            getStoreModule(store2._modules, modulePath),
            modulePath === "root" ? store2.getters : store2._makeLocalGettersCache,
            modulePath
          );
        }
      });
      api.on.editInspectorState(function(payload) {
        if (payload.app === app2 && payload.inspectorId === INSPECTOR_ID) {
          var modulePath = payload.nodeId;
          var path2 = payload.path;
          if (modulePath !== "root") {
            path2 = modulePath.split("/").filter(Boolean).concat(path2);
          }
          store2._withCommit(function() {
            payload.set(store2._state.data, path2, payload.state.value);
          });
        }
      });
      store2.subscribe(function(mutation, state2) {
        var data = {};
        if (mutation.payload) {
          data.payload = mutation.payload;
        }
        data.state = state2;
        api.notifyComponentUpdate();
        api.sendInspectorTree(INSPECTOR_ID);
        api.sendInspectorState(INSPECTOR_ID);
        api.addTimelineEvent({
          layerId: MUTATIONS_LAYER_ID,
          event: {
            time: Date.now(),
            title: mutation.type,
            data
          }
        });
      });
      store2.subscribeAction({
        before: function(action, state2) {
          var data = {};
          if (action.payload) {
            data.payload = action.payload;
          }
          action._id = actionId++;
          action._time = Date.now();
          data.state = state2;
          api.addTimelineEvent({
            layerId: ACTIONS_LAYER_ID,
            event: {
              time: action._time,
              title: action.type,
              groupId: action._id,
              subtitle: "start",
              data
            }
          });
        },
        after: function(action, state2) {
          var data = {};
          var duration = Date.now() - action._time;
          data.duration = {
            _custom: {
              type: "duration",
              display: duration + "ms",
              tooltip: "Action duration",
              value: duration
            }
          };
          if (action.payload) {
            data.payload = action.payload;
          }
          data.state = state2;
          api.addTimelineEvent({
            layerId: ACTIONS_LAYER_ID,
            event: {
              time: Date.now(),
              title: action.type,
              groupId: action._id,
              subtitle: "end",
              data
            }
          });
        }
      });
    }
  );
}
var COLOR_LIME_500 = 8702998;
var COLOR_DARK = 6710886;
var COLOR_WHITE = 16777215;
var TAG_NAMESPACED = {
  label: "namespaced",
  textColor: COLOR_WHITE,
  backgroundColor: COLOR_DARK
};
function extractNameFromPath(path2) {
  return path2 && path2 !== "root" ? path2.split("/").slice(-2, -1)[0] : "Root";
}
function formatStoreForInspectorTree(module, path2) {
  return {
    id: path2 || "root",
    // all modules end with a `/`, we want the last segment only
    // cart/ -> cart
    // nested/cart/ -> cart
    label: extractNameFromPath(path2),
    tags: module.namespaced ? [TAG_NAMESPACED] : [],
    children: Object.keys(module._children).map(
      function(moduleName) {
        return formatStoreForInspectorTree(
          module._children[moduleName],
          path2 + moduleName + "/"
        );
      }
    )
  };
}
function flattenStoreForInspectorTree(result, module, filter, path2) {
  if (path2.includes(filter)) {
    result.push({
      id: path2 || "root",
      label: path2.endsWith("/") ? path2.slice(0, path2.length - 1) : path2 || "Root",
      tags: module.namespaced ? [TAG_NAMESPACED] : []
    });
  }
  Object.keys(module._children).forEach(function(moduleName) {
    flattenStoreForInspectorTree(result, module._children[moduleName], filter, path2 + moduleName + "/");
  });
}
function formatStoreForInspectorState(module, getters2, path2) {
  getters2 = path2 === "root" ? getters2 : getters2[path2];
  var gettersKeys = Object.keys(getters2);
  var storeState = {
    state: Object.keys(module.state).map(function(key) {
      return {
        key,
        editable: true,
        value: module.state[key]
      };
    })
  };
  if (gettersKeys.length) {
    var tree = transformPathsToObjectTree(getters2);
    storeState.getters = Object.keys(tree).map(function(key) {
      return {
        key: key.endsWith("/") ? extractNameFromPath(key) : key,
        editable: false,
        value: canThrow(function() {
          return tree[key];
        })
      };
    });
  }
  return storeState;
}
function transformPathsToObjectTree(getters2) {
  var result = {};
  Object.keys(getters2).forEach(function(key) {
    var path2 = key.split("/");
    if (path2.length > 1) {
      var target = result;
      var leafKey = path2.pop();
      path2.forEach(function(p2) {
        if (!target[p2]) {
          target[p2] = {
            _custom: {
              value: {},
              display: p2,
              tooltip: "Module",
              abstract: true
            }
          };
        }
        target = target[p2]._custom.value;
      });
      target[leafKey] = canThrow(function() {
        return getters2[key];
      });
    } else {
      result[key] = canThrow(function() {
        return getters2[key];
      });
    }
  });
  return result;
}
function getStoreModule(moduleMap, path2) {
  var names = path2.split("/").filter(function(n) {
    return n;
  });
  return names.reduce(
    function(module, moduleName, i) {
      var child = module[moduleName];
      if (!child) {
        throw new Error('Missing module "' + moduleName + '" for path "' + path2 + '".');
      }
      return i === names.length - 1 ? child : child._children;
    },
    path2 === "root" ? moduleMap : moduleMap.root._children
  );
}
function canThrow(cb) {
  try {
    return cb();
  } catch (e) {
    return e;
  }
}
var Module = function Module2(rawModule, runtime) {
  this.runtime = runtime;
  this._children = /* @__PURE__ */ Object.create(null);
  this._rawModule = rawModule;
  var rawState = rawModule.state;
  this.state = (typeof rawState === "function" ? rawState() : rawState) || {};
};
var prototypeAccessors$1 = { namespaced: { configurable: true } };
prototypeAccessors$1.namespaced.get = function() {
  return !!this._rawModule.namespaced;
};
Module.prototype.addChild = function addChild(key, module) {
  this._children[key] = module;
};
Module.prototype.removeChild = function removeChild(key) {
  delete this._children[key];
};
Module.prototype.getChild = function getChild(key) {
  return this._children[key];
};
Module.prototype.hasChild = function hasChild(key) {
  return key in this._children;
};
Module.prototype.update = function update(rawModule) {
  this._rawModule.namespaced = rawModule.namespaced;
  if (rawModule.actions) {
    this._rawModule.actions = rawModule.actions;
  }
  if (rawModule.mutations) {
    this._rawModule.mutations = rawModule.mutations;
  }
  if (rawModule.getters) {
    this._rawModule.getters = rawModule.getters;
  }
};
Module.prototype.forEachChild = function forEachChild(fn) {
  forEachValue(this._children, fn);
};
Module.prototype.forEachGetter = function forEachGetter(fn) {
  if (this._rawModule.getters) {
    forEachValue(this._rawModule.getters, fn);
  }
};
Module.prototype.forEachAction = function forEachAction(fn) {
  if (this._rawModule.actions) {
    forEachValue(this._rawModule.actions, fn);
  }
};
Module.prototype.forEachMutation = function forEachMutation(fn) {
  if (this._rawModule.mutations) {
    forEachValue(this._rawModule.mutations, fn);
  }
};
Object.defineProperties(Module.prototype, prototypeAccessors$1);
var ModuleCollection = function ModuleCollection2(rawRootModule) {
  this.register([], rawRootModule, false);
};
ModuleCollection.prototype.get = function get(path2) {
  return path2.reduce(function(module, key) {
    return module.getChild(key);
  }, this.root);
};
ModuleCollection.prototype.getNamespace = function getNamespace(path2) {
  var module = this.root;
  return path2.reduce(function(namespace, key) {
    module = module.getChild(key);
    return namespace + (module.namespaced ? key + "/" : "");
  }, "");
};
ModuleCollection.prototype.update = function update$1(rawRootModule) {
  update2([], this.root, rawRootModule);
};
ModuleCollection.prototype.register = function register2(path2, rawModule, runtime) {
  var this$1$1 = this;
  if (runtime === void 0) runtime = true;
  var newModule = new Module(rawModule, runtime);
  if (path2.length === 0) {
    this.root = newModule;
  } else {
    var parent = this.get(path2.slice(0, -1));
    parent.addChild(path2[path2.length - 1], newModule);
  }
  if (rawModule.modules) {
    forEachValue(rawModule.modules, function(rawChildModule, key) {
      this$1$1.register(path2.concat(key), rawChildModule, runtime);
    });
  }
};
ModuleCollection.prototype.unregister = function unregister(path2) {
  var parent = this.get(path2.slice(0, -1));
  var key = path2[path2.length - 1];
  var child = parent.getChild(key);
  if (!child) {
    return;
  }
  if (!child.runtime) {
    return;
  }
  parent.removeChild(key);
};
ModuleCollection.prototype.isRegistered = function isRegistered(path2) {
  var parent = this.get(path2.slice(0, -1));
  var key = path2[path2.length - 1];
  if (parent) {
    return parent.hasChild(key);
  }
  return false;
};
function update2(path2, targetModule, newModule) {
  targetModule.update(newModule);
  if (newModule.modules) {
    for (var key in newModule.modules) {
      if (!targetModule.getChild(key)) {
        return;
      }
      update2(
        path2.concat(key),
        targetModule.getChild(key),
        newModule.modules[key]
      );
    }
  }
}
function createStore(options) {
  return new Store(options);
}
var Store = function Store2(options) {
  var this$1$1 = this;
  if (options === void 0) options = {};
  var plugins = options.plugins;
  if (plugins === void 0) plugins = [];
  var strict = options.strict;
  if (strict === void 0) strict = false;
  var devtools = options.devtools;
  this._committing = false;
  this._actions = /* @__PURE__ */ Object.create(null);
  this._actionSubscribers = [];
  this._mutations = /* @__PURE__ */ Object.create(null);
  this._wrappedGetters = /* @__PURE__ */ Object.create(null);
  this._modules = new ModuleCollection(options);
  this._modulesNamespaceMap = /* @__PURE__ */ Object.create(null);
  this._subscribers = [];
  this._makeLocalGettersCache = /* @__PURE__ */ Object.create(null);
  this._scope = null;
  this._devtools = devtools;
  var store2 = this;
  var ref2 = this;
  var dispatch2 = ref2.dispatch;
  var commit2 = ref2.commit;
  this.dispatch = function boundDispatch(type, payload) {
    return dispatch2.call(store2, type, payload);
  };
  this.commit = function boundCommit(type, payload, options2) {
    return commit2.call(store2, type, payload, options2);
  };
  this.strict = strict;
  var state2 = this._modules.root.state;
  installModule(this, state2, [], this._modules.root);
  resetStoreState(this, state2);
  plugins.forEach(function(plugin) {
    return plugin(this$1$1);
  });
};
var prototypeAccessors = { state: { configurable: true } };
Store.prototype.install = function install(app2, injectKey) {
  app2.provide(injectKey || storeKey, this);
  app2.config.globalProperties.$store = this;
  var useDevtools = this._devtools !== void 0 ? this._devtools : false;
  if (useDevtools) {
    addDevtools(app2, this);
  }
};
prototypeAccessors.state.get = function() {
  return this._state.data;
};
prototypeAccessors.state.set = function(v2) {
};
Store.prototype.commit = function commit(_type, _payload, _options) {
  var this$1$1 = this;
  var ref2 = unifyObjectStyle(_type, _payload, _options);
  var type = ref2.type;
  var payload = ref2.payload;
  var mutation = { type, payload };
  var entry = this._mutations[type];
  if (!entry) {
    return;
  }
  this._withCommit(function() {
    entry.forEach(function commitIterator(handler) {
      handler(payload);
    });
  });
  this._subscribers.slice().forEach(function(sub) {
    return sub(mutation, this$1$1.state);
  });
};
Store.prototype.dispatch = function dispatch(_type, _payload) {
  var this$1$1 = this;
  var ref2 = unifyObjectStyle(_type, _payload);
  var type = ref2.type;
  var payload = ref2.payload;
  var action = { type, payload };
  var entry = this._actions[type];
  if (!entry) {
    return;
  }
  try {
    this._actionSubscribers.slice().filter(function(sub) {
      return sub.before;
    }).forEach(function(sub) {
      return sub.before(action, this$1$1.state);
    });
  } catch (e) {
  }
  var result = entry.length > 1 ? Promise.all(entry.map(function(handler) {
    return handler(payload);
  })) : entry[0](payload);
  return new Promise(function(resolve, reject) {
    result.then(function(res) {
      try {
        this$1$1._actionSubscribers.filter(function(sub) {
          return sub.after;
        }).forEach(function(sub) {
          return sub.after(action, this$1$1.state);
        });
      } catch (e) {
      }
      resolve(res);
    }, function(error) {
      try {
        this$1$1._actionSubscribers.filter(function(sub) {
          return sub.error;
        }).forEach(function(sub) {
          return sub.error(action, this$1$1.state, error);
        });
      } catch (e) {
      }
      reject(error);
    });
  });
};
Store.prototype.subscribe = function subscribe2(fn, options) {
  return genericSubscribe(fn, this._subscribers, options);
};
Store.prototype.subscribeAction = function subscribeAction(fn, options) {
  var subs = typeof fn === "function" ? { before: fn } : fn;
  return genericSubscribe(subs, this._actionSubscribers, options);
};
Store.prototype.watch = function watch$1(getter, cb, options) {
  var this$1$1 = this;
  return watch(function() {
    return getter(this$1$1.state, this$1$1.getters);
  }, cb, Object.assign({}, options));
};
Store.prototype.replaceState = function replaceState(state2) {
  var this$1$1 = this;
  this._withCommit(function() {
    this$1$1._state.data = state2;
  });
};
Store.prototype.registerModule = function registerModule(path2, rawModule, options) {
  if (options === void 0) options = {};
  if (typeof path2 === "string") {
    path2 = [path2];
  }
  this._modules.register(path2, rawModule);
  installModule(this, this.state, path2, this._modules.get(path2), options.preserveState);
  resetStoreState(this, this.state);
};
Store.prototype.unregisterModule = function unregisterModule(path2) {
  var this$1$1 = this;
  if (typeof path2 === "string") {
    path2 = [path2];
  }
  this._modules.unregister(path2);
  this._withCommit(function() {
    var parentState = getNestedState(this$1$1.state, path2.slice(0, -1));
    delete parentState[path2[path2.length - 1]];
  });
  resetStore(this);
};
Store.prototype.hasModule = function hasModule(path2) {
  if (typeof path2 === "string") {
    path2 = [path2];
  }
  return this._modules.isRegistered(path2);
};
Store.prototype.hotUpdate = function hotUpdate(newOptions) {
  this._modules.update(newOptions);
  resetStore(this, true);
};
Store.prototype._withCommit = function _withCommit(fn) {
  var committing = this._committing;
  this._committing = true;
  fn();
  this._committing = committing;
};
Object.defineProperties(Store.prototype, prototypeAccessors);
function encodePath(path2) {
  if (!path2) {
    return path2;
  }
  return path2.split("/").map(encodeURIComponent).join("/");
}
register();
const _sfc_main$C = {
  name: "NcActionSeparator"
};
const _hoisted_1$z = {
  class: "action action-separator action--disabled",
  role: "separator"
};
function _sfc_render$y(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("li", _hoisted_1$z);
}
const NcActionSeparator = /* @__PURE__ */ _export_sfc(_sfc_main$C, [["render", _sfc_render$y], ["__scopeId", "data-v-3e2324b7"]]);
({
  props: {
    /**
     * Any [NcActions](#/Components/NcActions?id=ncactions-1) prop
     */
    // Not an actual prop but needed to show in vue-styleguidist docs
    ...NcActions.props
  }
});
register(t47);
register(t48);
register(t31);
const LOCALHOST = "LOCALHOST";
const COLON = "COLON";
const defaults = {
  defaultProtocol: "http",
  events: null,
  format: noop,
  formatHref: noop,
  nl2br: false,
  tagName: "a",
  target: null,
  rel: null,
  validate: true,
  truncate: Infinity,
  className: null,
  attributes: null,
  ignoreTags: [],
  render: null
};
function Options(opts, defaultRender = null) {
  let o = Object.assign({}, defaults);
  if (opts) {
    o = Object.assign(o, opts instanceof Options ? opts.o : opts);
  }
  const ignoredTags = o.ignoreTags;
  const uppercaseIgnoredTags = [];
  for (let i = 0; i < ignoredTags.length; i++) {
    uppercaseIgnoredTags.push(ignoredTags[i].toUpperCase());
  }
  this.o = o;
  if (defaultRender) {
    this.defaultRender = defaultRender;
  }
  this.ignoreTags = uppercaseIgnoredTags;
}
Options.prototype = {
  o: defaults,
  /**
   * @type string[]
   */
  ignoreTags: [],
  /**
   * @param {IntermediateRepresentation} ir
   * @returns {any}
   */
  defaultRender(ir) {
    return ir;
  },
  /**
   * Returns true or false based on whether a token should be displayed as a
   * link based on the user options.
   * @param {MultiToken} token
   * @returns {boolean}
   */
  check(token) {
    return this.get("validate", token.toString(), token);
  },
  // Private methods
  /**
   * Resolve an option's value based on the value of the option and the given
   * params. If operator and token are specified and the target option is
   * callable, automatically calls the function with the given argument.
   * @template {keyof Opts} K
   * @param {K} key Name of option to use
   * @param {string} [operator] will be passed to the target option if it's a
   * function. If not specified, RAW function value gets returned
   * @param {MultiToken} [token] The token from linkify.tokenize
   * @returns {Opts[K] | any}
   */
  get(key, operator, token) {
    const isCallable = operator != null;
    let option = this.o[key];
    if (!option) {
      return option;
    }
    if (typeof option === "object") {
      option = token.t in option ? option[token.t] : defaults[key];
      if (typeof option === "function" && isCallable) {
        option = option(operator, token);
      }
    } else if (typeof option === "function" && isCallable) {
      option = option(operator, token.t, token);
    }
    return option;
  },
  /**
   * @template {keyof Opts} L
   * @param {L} key Name of options object to use
   * @param {string} [operator]
   * @param {MultiToken} [token]
   * @returns {Opts[L] | any}
   */
  getObj(key, operator, token) {
    let obj = this.o[key];
    if (typeof obj === "function" && operator != null) {
      obj = obj(operator, token.t, token);
    }
    return obj;
  },
  /**
   * Convert the given token to a rendered element that may be added to the
   * calling-interface's DOM
   * @param {MultiToken} token Token to render to an HTML element
   * @returns {any} Render result; e.g., HTML string, DOM element, React
   *   Component, etc.
   */
  render(token) {
    const ir = token.render(this);
    const renderFn = this.get("render", null, token) || this.defaultRender;
    return renderFn(ir, token.t, token);
  }
};
function noop(val) {
  return val;
}
function MultiToken(value, tokens) {
  this.t = "token";
  this.v = value;
  this.tk = tokens;
}
MultiToken.prototype = {
  isLink: false,
  /**
   * Return the string this token represents.
   * @return {string}
   */
  toString() {
    return this.v;
  },
  /**
   * What should the value for this token be in the `href` HTML attribute?
   * Returns the `.toString` value by default.
   * @param {string} [scheme]
   * @return {string}
   */
  toHref(scheme) {
    return this.toString();
  },
  /**
   * @param {Options} options Formatting options
   * @returns {string}
   */
  toFormattedString(options) {
    const val = this.toString();
    const truncate = options.get("truncate", val, this);
    const formatted = options.get("format", val, this);
    return truncate && formatted.length > truncate ? formatted.substring(0, truncate) + "…" : formatted;
  },
  /**
   *
   * @param {Options} options
   * @returns {string}
   */
  toFormattedHref(options) {
    return options.get("formatHref", this.toHref(options.get("defaultProtocol")), this);
  },
  /**
   * The start index of this token in the original input string
   * @returns {number}
   */
  startIndex() {
    return this.tk[0].s;
  },
  /**
   * The end index of this token in the original input string (up to this
   * index but not including it)
   * @returns {number}
   */
  endIndex() {
    return this.tk[this.tk.length - 1].e;
  },
  /**
  	Returns an object  of relevant values for this token, which includes keys
  	* type - Kind of token ('url', 'email', etc.)
  	* value - Original text
  	* href - The value that should be added to the anchor tag's href
  		attribute
  		@method toObject
  	@param {string} [protocol] `'http'` by default
  */
  toObject(protocol = defaults.defaultProtocol) {
    return {
      type: this.t,
      value: this.toString(),
      isLink: this.isLink,
      href: this.toHref(protocol),
      start: this.startIndex(),
      end: this.endIndex()
    };
  },
  /**
   *
   * @param {Options} options Formatting option
   */
  toFormattedObject(options) {
    return {
      type: this.t,
      value: this.toFormattedString(options),
      isLink: this.isLink,
      href: this.toFormattedHref(options),
      start: this.startIndex(),
      end: this.endIndex()
    };
  },
  /**
   * Whether this token should be rendered as a link according to the given options
   * @param {Options} options
   * @returns {boolean}
   */
  validate(options) {
    return options.get("validate", this.toString(), this);
  },
  /**
   * Return an object that represents how this link should be rendered.
   * @param {Options} options Formattinng options
   */
  render(options) {
    const token = this;
    const href = this.toHref(options.get("defaultProtocol"));
    const formattedHref = options.get("formatHref", href, this);
    const tagName = options.get("tagName", href, token);
    const content = this.toFormattedString(options);
    const attributes = {};
    const className = options.get("className", href, token);
    const target = options.get("target", href, token);
    const rel = options.get("rel", href, token);
    const attrs = options.getObj("attributes", href, token);
    const eventListeners = options.getObj("events", href, token);
    attributes.href = formattedHref;
    if (className) {
      attributes.class = className;
    }
    if (target) {
      attributes.target = target;
    }
    if (rel) {
      attributes.rel = rel;
    }
    if (attrs) {
      Object.assign(attributes, attrs);
    }
    return {
      tagName,
      attributes,
      content,
      eventListeners
    };
  }
};
function createTokenClass(type, props) {
  class Token extends MultiToken {
    constructor(value, tokens) {
      super(value, tokens);
      this.t = type;
    }
  }
  for (const p2 in props) {
    Token.prototype[p2] = props[p2];
  }
  Token.t = type;
  return Token;
}
createTokenClass("email", {
  isLink: true,
  toHref() {
    return "mailto:" + this.toString();
  }
});
createTokenClass("text");
createTokenClass("nl");
createTokenClass("url", {
  isLink: true,
  /**
  	Lowercases relevant parts of the domain and adds the protocol if
  	required. Note that this will not escape unsafe HTML characters in the
  	URL.
  		@param {string} [scheme] default scheme (e.g., 'https')
  	@return {string} the full href
  */
  toHref(scheme = defaults.defaultProtocol) {
    return this.hasProtocol() ? this.v : `${scheme}://${this.v}`;
  },
  /**
   * Check whether this URL token has a protocol
   * @return {boolean}
   */
  hasProtocol() {
    const tokens = this.tk;
    return tokens.length >= 2 && tokens[0].t !== LOCALHOST && tokens[1].t === COLON;
  }
});
register(t15);
new PQueue({ concurrency: 5 });
register(t19);
const _hoisted_1$y = {
  key: 0,
  class: "nc-chip__icon"
};
const _hoisted_2$v = { class: "nc-chip__text" };
/* @__PURE__ */ defineComponent({
  __name: "NcChip",
  props: {
    ariaLabelClose: { default: t$1("Close") },
    actionsContainer: { default: "body" },
    text: { default: "" },
    iconPath: { default: void 0 },
    iconSvg: { default: void 0 },
    noClose: { type: Boolean },
    variant: { default: "secondary" }
  },
  emits: ["close"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit2 = __emit;
    const slots = useSlots();
    const canClose = computed(() => !props.noClose);
    const hasActions = () => !!slots.actions;
    const hasIcon = () => Boolean(props.iconPath || props.iconSvg || !!slots.icon);
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(["nc-chip", {
          [`nc-chip--${__props.variant}`]: true,
          "nc-chip--no-actions": __props.noClose && !hasActions(),
          "nc-chip--no-icon": !hasIcon()
        }])
      }, [
        hasIcon() ? (openBlock(), createElementBlock("span", _hoisted_1$y, [
          renderSlot(_ctx.$slots, "icon", {}, () => [
            __props.iconPath || __props.iconSvg ? (openBlock(), createBlock(NcIconSvgWrapper, {
              key: 0,
              inline: "",
              path: __props.iconPath,
              svg: __props.iconPath ? void 0 : __props.iconSvg,
              size: 18
            }, null, 8, ["path", "svg"])) : createCommentVNode("", true)
          ], true)
        ])) : createCommentVNode("", true),
        createBaseVNode("span", _hoisted_2$v, [
          renderSlot(_ctx.$slots, "default", {}, () => [
            createTextVNode(toDisplayString(__props.text), 1)
          ], true)
        ]),
        canClose.value || hasActions() ? (openBlock(), createBlock(NcActions, {
          key: 1,
          class: "nc-chip__actions",
          container: __props.actionsContainer,
          forceMenu: !canClose.value,
          variant: "tertiary-no-background"
        }, {
          default: withCtx(() => [
            canClose.value ? (openBlock(), createBlock(NcActionButton, {
              key: 0,
              closeAfterClick: "",
              onClick: _cache[0] || (_cache[0] = ($event) => emit2("close"))
            }, {
              icon: withCtx(() => [
                createVNode(NcIconSvgWrapper, {
                  path: unref(mdiClose),
                  size: 20
                }, null, 8, ["path"])
              ]),
              default: withCtx(() => [
                createTextVNode(" " + toDisplayString(__props.ariaLabelClose), 1)
              ]),
              _: 1
            })) : createCommentVNode("", true),
            renderSlot(_ctx.$slots, "actions", {}, void 0, true)
          ]),
          _: 3
        }, 8, ["container", "forceMenu"])) : createCommentVNode("", true)
      ], 2);
    };
  }
});
register(t28);
register(t6);
const _hoisted_1$x = ["title"];
const _sfc_main$B = /* @__PURE__ */ defineComponent({
  __name: "NcCounterBubble",
  props: {
    count: {},
    active: { type: Boolean },
    type: { default: "" },
    raw: { type: Boolean }
  },
  setup(__props) {
    const props = __props;
    const humanizedCount = computed(() => {
      if (props.raw) {
        return props.count.toString();
      }
      const formatter = new Intl.NumberFormat(getCanonicalLocale(), {
        notation: "compact",
        compactDisplay: "short"
      });
      return formatter.format(props.count);
    });
    const originalCountAsTitleIfNeeded = computed(() => {
      if (props.raw) {
        return;
      }
      const countAsString = props.count.toString();
      if (countAsString === humanizedCount.value) {
        return;
      }
      return countAsString;
    });
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", {
        class: normalizeClass(["counter-bubble__counter", {
          active: __props.active,
          "counter-bubble__counter--highlighted": __props.type === "highlighted",
          "counter-bubble__counter--outlined": __props.type === "outlined"
        }]),
        title: originalCountAsTitleIfNeeded.value
      }, toDisplayString(humanizedCount.value), 11, _hoisted_1$x);
    };
  }
});
const NcCounterBubble = /* @__PURE__ */ _export_sfc(_sfc_main$B, [["__scopeId", "data-v-36ffc13f"]]);
register(t35);
({
  props: {
    /**
     * The text of show more button.
     *
     * Expected to be in the form "More {itemName} …"
     */
    showMoreLabel: {
      default: t$1("More items …")
    }
  }
});
var isWindowAvailable = typeof window !== "undefined";
isWindowAvailable && (function() {
  var lastTime = 0;
  var vendors = ["ms", "moz", "webkit", "o"];
  for (var x2 = 0; x2 < vendors.length && !window.requestAnimationFrame; ++x2) {
    window.requestAnimationFrame = window[vendors[x2] + "RequestAnimationFrame"];
    window.cancelAnimationFrame = window[vendors[x2] + "CancelAnimationFrame"] || window[vendors[x2] + "CancelRequestAnimationFrame"];
  }
  if (!window.requestAnimationFrame)
    window.requestAnimationFrame = function(callback, element) {
      var currTime = (/* @__PURE__ */ new Date()).getTime();
      var timeToCall = Math.max(0, 16 - (currTime - lastTime));
      var id = window.setTimeout(function() {
        callback(currTime + timeToCall);
      }, timeToCall);
      lastTime = currTime + timeToCall;
      return id;
    };
  if (!window.cancelAnimationFrame)
    window.cancelAnimationFrame = function(id) {
      clearTimeout(id);
    };
})();
var emojiMart$1 = { exports: {} };
var emojiMart = emojiMart$1.exports;
var hasRequiredEmojiMart;
function requireEmojiMart() {
  if (hasRequiredEmojiMart) return emojiMart$1.exports;
  hasRequiredEmojiMart = 1;
  (function(module, exports) {
    !(function(e, t2) {
      module.exports = t2();
    })("undefined" != typeof self ? self : emojiMart, (function() {
      return (function() {
        var e = { 537: function() {
          "undefined" != typeof window && (function() {
            for (var e2 = 0, t3 = ["ms", "moz", "webkit", "o"], i2 = 0; i2 < t3.length && !window.requestAnimationFrame; ++i2) window.requestAnimationFrame = window[t3[i2] + "RequestAnimationFrame"], window.cancelAnimationFrame = window[t3[i2] + "CancelAnimationFrame"] || window[t3[i2] + "CancelRequestAnimationFrame"];
            window.requestAnimationFrame || (window.requestAnimationFrame = function(t4, i3) {
              var n2 = (/* @__PURE__ */ new Date()).getTime(), r = Math.max(0, 16 - (n2 - e2)), o = window.setTimeout((function() {
                t4(n2 + r);
              }), r);
              return e2 = n2 + r, o;
            }), window.cancelAnimationFrame || (window.cancelAnimationFrame = function(e3) {
              clearTimeout(e3);
            });
          })();
        } }, t2 = {};
        function i(n2) {
          var r = t2[n2];
          if (void 0 !== r) return r.exports;
          var o = t2[n2] = { exports: {} };
          return e[n2](o, o.exports, i), o.exports;
        }
        i.d = function(e2, t3) {
          for (var n2 in t3) i.o(t3, n2) && !i.o(e2, n2) && Object.defineProperty(e2, n2, { enumerable: true, get: t3[n2] });
        }, i.o = function(e2, t3) {
          return Object.prototype.hasOwnProperty.call(e2, t3);
        }, i.r = function(e2) {
          "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(e2, Symbol.toStringTag, { value: "Module" }), Object.defineProperty(e2, "__esModule", { value: true });
        };
        var n = {};
        return (function() {
          i.r(n), i.d(n, { Anchors: function() {
            return k2;
          }, Category: function() {
            return X;
          }, Emoji: function() {
            return J;
          }, EmojiData: function() {
            return N2;
          }, EmojiIndex: function() {
            return R2;
          }, EmojiView: function() {
            return $2;
          }, Picker: function() {
            return se;
          }, Preview: function() {
            return G;
          }, Search: function() {
            return Q;
          }, Skins: function() {
            return Z;
          }, frequently: function() {
            return w2;
          }, sanitize: function() {
            return D;
          }, store: function() {
            return c;
          }, uncompress: function() {
            return p2;
          } });
          var e2, t3, r = "emoji-mart", o = JSON, s = "undefined" != typeof window && "localStorage" in window;
          function a2(e3, i2) {
            if (t3) t3(e3, i2);
            else {
              if (!s) return;
              try {
                window.localStorage["".concat(r, ".").concat(e3)] = o.stringify(i2);
              } catch (e4) {
              }
            }
          }
          var c = { update: function(e3) {
            for (var t4 in e3) a2(t4, e3[t4]);
          }, set: a2, get: function(t4) {
            if (e2) return e2(t4);
            if (s) {
              try {
                var i2 = window.localStorage["".concat(r, ".").concat(t4)];
              } catch (e3) {
                return;
              }
              return i2 ? JSON.parse(i2) : void 0;
            }
          }, setNamespace: function(e3) {
            r = e3;
          }, setHandlers: function(i2) {
            i2 || (i2 = {}), e2 = i2.getter, t3 = i2.setter;
          } };
          function u2(e3) {
            return u2 = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(e4) {
              return typeof e4;
            } : function(e4) {
              return e4 && "function" == typeof Symbol && e4.constructor === Symbol && e4 !== Symbol.prototype ? "symbol" : typeof e4;
            }, u2(e3);
          }
          function l(e3, t4) {
            (null == t4 || t4 > e3.length) && (t4 = e3.length);
            for (var i2 = 0, n2 = new Array(t4); i2 < t4; i2++) n2[i2] = e3[i2];
            return n2;
          }
          var h2 = { name: "a", unified: "b", non_qualified: "c", has_img_apple: "d", has_img_google: "e", has_img_twitter: "f", has_img_facebook: "h", keywords: "j", sheet: "k", emoticons: "l", text: "m", short_names: "n", added_in: "o" }, m2 = function(e3) {
            var t4 = [], i2 = function(e4, i3) {
              e4 && (Array.isArray(e4) ? e4 : [e4]).forEach((function(e5) {
                (i3 ? e5.split(/[-|_|\s]+/) : [e5]).forEach((function(e6) {
                  e6 = e6.toLowerCase(), -1 == t4.indexOf(e6) && t4.push(e6);
                }));
              }));
            };
            return i2(e3.short_names, true), i2(e3.name, true), i2(e3.keywords, false), i2(e3.emoticons, false), t4.join(",");
          };
          function d2(e3) {
            var t4, i2 = (function(e4, t10) {
              var i3 = "undefined" != typeof Symbol && e4[Symbol.iterator] || e4["@@iterator"];
              if (!i3) {
                if (Array.isArray(e4) || (i3 = (function(e5, t11) {
                  if (e5) {
                    if ("string" == typeof e5) return l(e5, t11);
                    var i4 = Object.prototype.toString.call(e5).slice(8, -1);
                    return "Object" === i4 && e5.constructor && (i4 = e5.constructor.name), "Map" === i4 || "Set" === i4 ? Array.from(e5) : "Arguments" === i4 || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(i4) ? l(e5, t11) : void 0;
                  }
                })(e4)) || t10) {
                  i3 && (e4 = i3);
                  var n3 = 0, r3 = function() {
                  };
                  return { s: r3, n: function() {
                    return n3 >= e4.length ? { done: true } : { done: false, value: e4[n3++] };
                  }, e: function(e5) {
                    throw e5;
                  }, f: r3 };
                }
                throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
              }
              var o2, s2 = true, a3 = false;
              return { s: function() {
                i3 = i3.call(e4);
              }, n: function() {
                var e5 = i3.next();
                return s2 = e5.done, e5;
              }, e: function(e5) {
                a3 = true, o2 = e5;
              }, f: function() {
                try {
                  s2 || null == i3.return || i3.return();
                } finally {
                  if (a3) throw o2;
                }
              } };
            })(Object.getOwnPropertyNames(e3));
            try {
              for (i2.s(); !(t4 = i2.n()).done; ) {
                var n2 = t4.value, r2 = e3[n2];
                e3[n2] = r2 && "object" === u2(r2) ? d2(r2) : r2;
              }
            } catch (e4) {
              i2.e(e4);
            } finally {
              i2.f();
            }
            return Object.freeze(e3);
          }
          var f2, v2, p2 = function(e3) {
            if (!e3.compressed) return e3;
            for (var t4 in e3.compressed = false, e3.emojis) {
              var i2 = e3.emojis[t4];
              for (var n2 in h2) i2[n2] = i2[h2[n2]], delete i2[h2[n2]];
              i2.short_names || (i2.short_names = []), i2.short_names.unshift(t4), i2.sheet_x = i2.sheet[0], i2.sheet_y = i2.sheet[1], delete i2.sheet, i2.text || (i2.text = ""), i2.added_in || (i2.added_in = 6), i2.added_in = i2.added_in.toFixed(1), i2.search = m2(i2);
            }
            return d2(e3);
          }, j2 = ["+1", "grinning", "kissing_heart", "heart_eyes", "laughing", "stuck_out_tongue_winking_eye", "sweat_smile", "joy", "scream", "disappointed", "unamused", "weary", "sob", "sunglasses", "heart", "hankey"], g2 = {};
          function y2() {
            v2 = true, f2 = c.get("frequently");
          }
          var w2 = { add: function(e3) {
            v2 || y2();
            var t4 = e3.id;
            f2 || (f2 = g2), f2[t4] || (f2[t4] = 0), f2[t4] += 1, c.set("last", t4), c.set("frequently", f2);
          }, get: function(e3) {
            if (v2 || y2(), !f2) {
              g2 = {};
              for (var t4 = [], i2 = Math.min(e3, j2.length), n2 = 0; n2 < i2; n2++) g2[j2[n2]] = parseInt((i2 - n2) / 4, 10) + 1, t4.push(j2[n2]);
              return t4;
            }
            var r2 = e3, o2 = [];
            for (var s2 in f2) f2.hasOwnProperty(s2) && o2.push(s2);
            var a3 = o2.sort((function(e4, t10) {
              return f2[e4] - f2[t10];
            })).reverse().slice(0, r2), u3 = c.get("last");
            return u3 && -1 == a3.indexOf(u3) && (a3.pop(), a3.push(u3)), a3;
          } }, _2 = { activity: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M12 0C5.373 0 0 5.372 0 12c0 6.627 5.373 12 12 12 6.628 0 12-5.373 12-12 0-6.628-5.372-12-12-12m9.949 11H17.05c.224-2.527 1.232-4.773 1.968-6.113A9.966 9.966 0 0 1 21.949 11M13 11V2.051a9.945 9.945 0 0 1 4.432 1.564c-.858 1.491-2.156 4.22-2.392 7.385H13zm-2 0H8.961c-.238-3.165-1.536-5.894-2.393-7.385A9.95 9.95 0 0 1 11 2.051V11zm0 2v8.949a9.937 9.937 0 0 1-4.432-1.564c.857-1.492 2.155-4.221 2.393-7.385H11zm4.04 0c.236 3.164 1.534 5.893 2.392 7.385A9.92 9.92 0 0 1 13 21.949V13h2.04zM4.982 4.887C5.718 6.227 6.726 8.473 6.951 11h-4.9a9.977 9.977 0 0 1 2.931-6.113M2.051 13h4.9c-.226 2.527-1.233 4.771-1.969 6.113A9.972 9.972 0 0 1 2.051 13m16.967 6.113c-.735-1.342-1.744-3.586-1.968-6.113h4.899a9.961 9.961 0 0 1-2.931 6.113"/></svg>', custom: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><g transform="translate(2.000000, 1.000000)"><rect id="Rectangle" x="8" y="0" width="3" height="21" rx="1.5"></rect><rect id="Rectangle" transform="translate(9.843, 10.549) rotate(60) translate(-9.843, -10.549) " x="8.343" y="0.049" width="3" height="21" rx="1.5"></rect><rect id="Rectangle" transform="translate(9.843, 10.549) rotate(-60) translate(-9.843, -10.549) " x="8.343" y="0.049" width="3" height="21" rx="1.5"></rect></g></svg>', flags: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M0 0l6.084 24H8L1.916 0zM21 5h-4l-1-4H4l3 12h3l1 4h13L21 5zM6.563 3h7.875l2 8H8.563l-2-8zm8.832 10l-2.856 1.904L12.063 13h3.332zM19 13l-1.5-6h1.938l2 8H16l3-2z"/></svg>', foods: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M17 4.978c-1.838 0-2.876.396-3.68.934.513-1.172 1.768-2.934 4.68-2.934a1 1 0 0 0 0-2c-2.921 0-4.629 1.365-5.547 2.512-.064.078-.119.162-.18.244C11.73 1.838 10.798.023 9.207.023 8.579.022 7.85.306 7 .978 5.027 2.54 5.329 3.902 6.492 4.999 3.609 5.222 0 7.352 0 12.969c0 4.582 4.961 11.009 9 11.009 1.975 0 2.371-.486 3-1 .629.514 1.025 1 3 1 4.039 0 9-6.418 9-11 0-5.953-4.055-8-7-8M8.242 2.546c.641-.508.943-.523.965-.523.426.169.975 1.405 1.357 3.055-1.527-.629-2.741-1.352-2.98-1.846.059-.112.241-.356.658-.686M15 21.978c-1.08 0-1.21-.109-1.559-.402l-.176-.146c-.367-.302-.816-.452-1.266-.452s-.898.15-1.266.452l-.176.146c-.347.292-.477.402-1.557.402-2.813 0-7-5.389-7-9.009 0-5.823 4.488-5.991 5-5.991 1.939 0 2.484.471 3.387 1.251l.323.276a1.995 1.995 0 0 0 2.58 0l.323-.276c.902-.78 1.447-1.251 3.387-1.251.512 0 5 .168 5 6 0 3.617-4.187 9-7 9"/></svg>', nature: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M15.5 8a1.5 1.5 0 1 0 .001 3.001A1.5 1.5 0 0 0 15.5 8M8.5 8a1.5 1.5 0 1 0 .001 3.001A1.5 1.5 0 0 0 8.5 8"/><path d="M18.933 0h-.027c-.97 0-2.138.787-3.018 1.497-1.274-.374-2.612-.51-3.887-.51-1.285 0-2.616.133-3.874.517C7.245.79 6.069 0 5.093 0h-.027C3.352 0 .07 2.67.002 7.026c-.039 2.479.276 4.238 1.04 5.013.254.258.882.677 1.295.882.191 3.177.922 5.238 2.536 6.38.897.637 2.187.949 3.2 1.102C8.04 20.6 8 20.795 8 21c0 1.773 2.35 3 4 3 1.648 0 4-1.227 4-3 0-.201-.038-.393-.072-.586 2.573-.385 5.435-1.877 5.925-7.587.396-.22.887-.568 1.104-.788.763-.774 1.079-2.534 1.04-5.013C23.929 2.67 20.646 0 18.933 0M3.223 9.135c-.237.281-.837 1.155-.884 1.238-.15-.41-.368-1.349-.337-3.291.051-3.281 2.478-4.972 3.091-5.031.256.015.731.27 1.265.646-1.11 1.171-2.275 2.915-2.352 5.125-.133.546-.398.858-.783 1.313M12 22c-.901 0-1.954-.693-2-1 0-.654.475-1.236 1-1.602V20a1 1 0 1 0 2 0v-.602c.524.365 1 .947 1 1.602-.046.307-1.099 1-2 1m3-3.48v.02a4.752 4.752 0 0 0-1.262-1.02c1.092-.516 2.239-1.334 2.239-2.217 0-1.842-1.781-2.195-3.977-2.195-2.196 0-3.978.354-3.978 2.195 0 .883 1.148 1.701 2.238 2.217A4.8 4.8 0 0 0 9 18.539v-.025c-1-.076-2.182-.281-2.973-.842-1.301-.92-1.838-3.045-1.853-6.478l.023-.041c.496-.826 1.49-1.45 1.804-3.102 0-2.047 1.357-3.631 2.362-4.522C9.37 3.178 10.555 3 11.948 3c1.447 0 2.685.192 3.733.57 1 .9 2.316 2.465 2.316 4.48.313 1.651 1.307 2.275 1.803 3.102.035.058.068.117.102.178-.059 5.967-1.949 7.01-4.902 7.19m6.628-8.202c-.037-.065-.074-.13-.113-.195a7.587 7.587 0 0 0-.739-.987c-.385-.455-.648-.768-.782-1.313-.076-2.209-1.241-3.954-2.353-5.124.531-.376 1.004-.63 1.261-.647.636.071 3.044 1.764 3.096 5.031.027 1.81-.347 3.218-.37 3.235"/></svg>', objects: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M12 0a9 9 0 0 0-5 16.482V21s2.035 3 5 3 5-3 5-3v-4.518A9 9 0 0 0 12 0zm0 2c3.86 0 7 3.141 7 7s-3.14 7-7 7-7-3.141-7-7 3.14-7 7-7zM9 17.477c.94.332 1.946.523 3 .523s2.06-.19 3-.523v.834c-.91.436-1.925.689-3 .689a6.924 6.924 0 0 1-3-.69v-.833zm.236 3.07A8.854 8.854 0 0 0 12 21c.965 0 1.888-.167 2.758-.451C14.155 21.173 13.153 22 12 22c-1.102 0-2.117-.789-2.764-1.453z"/><path d="M14.745 12.449h-.004c-.852-.024-1.188-.858-1.577-1.824-.421-1.061-.703-1.561-1.182-1.566h-.009c-.481 0-.783.497-1.235 1.537-.436.982-.801 1.811-1.636 1.791l-.276-.043c-.565-.171-.853-.691-1.284-1.794-.125-.313-.202-.632-.27-.913-.051-.213-.127-.53-.195-.634C7.067 9.004 7.039 9 6.99 9A1 1 0 0 1 7 7h.01c1.662.017 2.015 1.373 2.198 2.134.486-.981 1.304-2.058 2.797-2.075 1.531.018 2.28 1.153 2.731 2.141l.002-.008C14.944 8.424 15.327 7 16.979 7h.032A1 1 0 1 1 17 9h-.011c-.149.076-.256.474-.319.709a6.484 6.484 0 0 1-.311.951c-.429.973-.79 1.789-1.614 1.789"/></svg>', smileys: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0m0 22C6.486 22 2 17.514 2 12S6.486 2 12 2s10 4.486 10 10-4.486 10-10 10"/><path d="M8 7a2 2 0 1 0-.001 3.999A2 2 0 0 0 8 7M16 7a2 2 0 1 0-.001 3.999A2 2 0 0 0 16 7M15.232 15c-.693 1.195-1.87 2-3.349 2-1.477 0-2.655-.805-3.347-2H15m3-2H6a6 6 0 1 0 12 0"/></svg>', people: '<svg xmlns:svg="http://www.w3.org/2000/svg" height="24" width="24" viewBox="0 0 24 24"> <path id="path3814" d="m 3.3591089,21.17726 c 0.172036,0.09385 4.265994,2.29837 8.8144451,2.29837 4.927767,0 8.670894,-2.211883 8.82782,-2.306019 0.113079,-0.06785 0.182268,-0.190051 0.182267,-0.321923 0,-3.03119 -0.929494,-5.804936 -2.617196,-7.810712 -1.180603,-1.403134 -2.661918,-2.359516 -4.295699,-2.799791 4.699118,-2.236258 3.102306,-9.28617162 -2.097191,-9.28617162 -5.1994978,0 -6.7963103,7.04991362 -2.097192,9.28617162 -1.6337821,0.440275 -3.1150971,1.396798 -4.2956991,2.799791 -1.687703,2.005776 -2.617196,4.779522 -2.617196,7.810712 1.2e-6,0.137378 0.075039,0.263785 0.195641,0.329572 z M 8.0439319,5.8308783 C 8.0439309,2.151521 12.492107,0.30955811 15.093491,2.9109411 17.694874,5.5123241 15.852911,9.9605006 12.173554,9.9605 9.8938991,9.9579135 8.0465186,8.1105332 8.0439319,5.8308783 Z m -1.688782,7.6894977 c 1.524535,-1.811449 3.5906601,-2.809035 5.8184041,-2.809035 2.227744,0 4.293869,0.997586 5.818404,2.809035 1.533639,1.822571 2.395932,4.339858 2.439152,7.108301 -0.803352,0.434877 -4.141636,2.096112 -8.257556,2.096112 -3.8062921,0 -7.3910861,-1.671043 -8.2573681,-2.104981 0.04505,-2.765017 0.906968,-5.278785 2.438964,-7.099432 z" /> <path id="path3816" d="M 12.173828 0.38867188 C 9.3198513 0.38867187 7.3770988 2.3672285 6.8652344 4.6308594 C 6.4218608 6.5916015 7.1153562 8.7676117 8.9648438 10.126953 C 7.6141249 10.677376 6.3550511 11.480944 5.3496094 12.675781 C 3.5629317 14.799185 2.6015625 17.701475 2.6015625 20.847656 C 2.6015654 21.189861 2.7894276 21.508002 3.0898438 21.671875 C 3.3044068 21.788925 7.4436239 24.039062 12.173828 24.039062 C 17.269918 24.039062 21.083568 21.776786 21.291016 21.652344 C 21.57281 21.483266 21.746097 21.176282 21.746094 20.847656 C 21.746094 17.701475 20.78277 14.799185 18.996094 12.675781 C 17.990455 11.480591 16.733818 10.675362 15.382812 10.125 C 17.231132 8.7655552 17.925675 6.5910701 17.482422 4.6308594 C 16.970557 2.3672285 15.027805 0.38867188 12.173828 0.38867188 z M 12.792969 2.3007812 C 13.466253 2.4161792 14.125113 2.7383941 14.695312 3.3085938 C 15.835712 4.4489931 15.985604 5.9473549 15.46875 7.1953125 C 14.951896 8.4432701 13.786828 9.3984378 12.173828 9.3984375 C 10.197719 9.3961954 8.607711 7.806187 8.6054688 5.8300781 C 8.6054683 4.2170785 9.5606362 3.0520102 10.808594 2.5351562 C 11.432573 2.2767293 12.119685 2.1853833 12.792969 2.3007812 z M 12.173828 11.273438 C 14.233647 11.273438 16.133674 12.185084 17.5625 13.882812 C 18.93069 15.508765 19.698347 17.776969 19.808594 20.283203 C 18.807395 20.800235 15.886157 22.162109 12.173828 22.162109 C 8.7614632 22.162109 5.6245754 20.787069 4.5390625 20.265625 C 4.6525896 17.766717 5.4203315 15.504791 6.7851562 13.882812 C 8.2139827 12.185084 10.11401 11.273438 12.173828 11.273438 z " /> </svg>', places: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M6.5 12C5.122 12 4 13.121 4 14.5S5.122 17 6.5 17 9 15.879 9 14.5 7.878 12 6.5 12m0 3c-.275 0-.5-.225-.5-.5s.225-.5.5-.5.5.225.5.5-.225.5-.5.5M17.5 12c-1.378 0-2.5 1.121-2.5 2.5s1.122 2.5 2.5 2.5 2.5-1.121 2.5-2.5-1.122-2.5-2.5-2.5m0 3c-.275 0-.5-.225-.5-.5s.225-.5.5-.5.5.225.5.5-.225.5-.5.5"/><path d="M22.482 9.494l-1.039-.346L21.4 9h.6c.552 0 1-.439 1-.992 0-.006-.003-.008-.003-.008H23c0-1-.889-2-1.984-2h-.642l-.731-1.717C19.262 3.012 18.091 2 16.764 2H7.236C5.909 2 4.738 3.012 4.357 4.283L3.626 6h-.642C1.889 6 1 7 1 8h.003S1 8.002 1 8.008C1 8.561 1.448 9 2 9h.6l-.043.148-1.039.346a2.001 2.001 0 0 0-1.359 2.097l.751 7.508a1 1 0 0 0 .994.901H3v1c0 1.103.896 2 2 2h2c1.104 0 2-.897 2-2v-1h6v1c0 1.103.896 2 2 2h2c1.104 0 2-.897 2-2v-1h1.096a.999.999 0 0 0 .994-.901l.751-7.508a2.001 2.001 0 0 0-1.359-2.097M6.273 4.857C6.402 4.43 6.788 4 7.236 4h9.527c.448 0 .834.43.963.857L19.313 9H4.688l1.585-4.143zM7 21H5v-1h2v1zm12 0h-2v-1h2v1zm2.189-3H2.811l-.662-6.607L3 11h18l.852.393L21.189 18z"/></svg>', recent: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M13 4h-2l-.001 7H9v2h2v2h2v-2h4v-2h-4z"/><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0m0 22C6.486 22 2 17.514 2 12S6.486 2 12 2s10 4.486 10 10-4.486 10-10 10"/></svg>', symbols: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M0 0h11v2H0zM4 11h3V6h4V4H0v2h4zM15.5 17c1.381 0 2.5-1.116 2.5-2.493s-1.119-2.493-2.5-2.493S13 13.13 13 14.507 14.119 17 15.5 17m0-2.986c.276 0 .5.222.5.493 0 .272-.224.493-.5.493s-.5-.221-.5-.493.224-.493.5-.493M21.5 19.014c-1.381 0-2.5 1.116-2.5 2.493S20.119 24 21.5 24s2.5-1.116 2.5-2.493-1.119-2.493-2.5-2.493m0 2.986a.497.497 0 0 1-.5-.493c0-.271.224-.493.5-.493s.5.222.5.493a.497.497 0 0 1-.5.493M22 13l-9 9 1.513 1.5 8.99-9.009zM17 11c2.209 0 4-1.119 4-2.5V2s.985-.161 1.498.949C23.01 4.055 23 6 23 6s1-1.119 1-3.135C24-.02 21 0 21 0h-2v6.347A5.853 5.853 0 0 0 17 6c-2.209 0-4 1.119-4 2.5s1.791 2.5 4 2.5M10.297 20.482l-1.475-1.585a47.54 47.54 0 0 1-1.442 1.129c-.307-.288-.989-1.016-2.045-2.183.902-.836 1.479-1.466 1.729-1.892s.376-.871.376-1.336c0-.592-.273-1.178-.818-1.759-.546-.581-1.329-.871-2.349-.871-1.008 0-1.79.293-2.344.879-.556.587-.832 1.181-.832 1.784 0 .813.419 1.748 1.256 2.805-.847.614-1.444 1.208-1.794 1.784a3.465 3.465 0 0 0-.523 1.833c0 .857.308 1.56.924 2.107.616.549 1.423.823 2.42.823 1.173 0 2.444-.379 3.813-1.137L8.235 24h2.819l-2.09-2.383 1.333-1.135zm-6.736-6.389a1.02 1.02 0 0 1 .73-.286c.31 0 .559.085.747.254a.849.849 0 0 1 .283.659c0 .518-.419 1.112-1.257 1.784-.536-.651-.805-1.231-.805-1.742a.901.901 0 0 1 .302-.669M3.74 22c-.427 0-.778-.116-1.057-.349-.279-.232-.418-.487-.418-.766 0-.594.509-1.288 1.527-2.083.968 1.134 1.717 1.946 2.248 2.438-.921.507-1.686.76-2.3.76"/></svg>' };
          function b2(e3, t4, i2, n2, r2, o2, s2, a3) {
            var c2, u3 = "function" == typeof e3 ? e3.options : e3;
            if (t4 && (u3.render = t4, u3.staticRenderFns = i2, u3._compiled = true), c2) ;
            return { exports: e3, options: u3 };
          }
          var C2 = b2({ props: { i18n: { type: Object, required: true }, color: { type: String }, categories: { type: Array, required: true }, activeCategory: { type: Object, default: function() {
            return {};
          } } }, emits: ["click"], created: function() {
            this.svgs = _2;
          } }, (function() {
            var e3 = this, t4 = e3._self._c;
            return t4("div", { staticClass: "emoji-mart-anchors", attrs: { role: "tablist" } }, e3._l(e3.categories, (function(i2) {
              return t4("button", { key: i2.id, class: { "emoji-mart-anchor": true, "emoji-mart-anchor-selected": i2.id == e3.activeCategory.id }, style: { color: i2.id == e3.activeCategory.id ? e3.color : "" }, attrs: { role: "tab", type: "button", "aria-label": i2.name, "aria-selected": i2.id == e3.activeCategory.id, "data-title": e3.i18n.categories[i2.id] }, on: { click: function(t10) {
                return e3.$emit("click", i2);
              } } }, [t4("div", { attrs: { "aria-hidden": "true" }, domProps: { innerHTML: e3._s(e3.svgs[i2.id]) } }), e3._v(" "), t4("span", { staticClass: "emoji-mart-anchor-bar", style: { backgroundColor: e3.color }, attrs: { "aria-hidden": "true" } })]);
            })), 0);
          }), []), k2 = C2.exports;
          function E2(e3, t4) {
            if (!(e3 instanceof t4)) throw new TypeError("Cannot call a class as a function");
          }
          function S2(e3) {
            var t4 = (function(e4, t10) {
              if ("object" != u2(e4) || !e4) return e4;
              var i2 = e4[Symbol.toPrimitive];
              if (void 0 !== i2) {
                var n2 = i2.call(e4, "string");
                if ("object" != u2(n2)) return n2;
                throw new TypeError("@@toPrimitive must return a primitive value.");
              }
              return String(e4);
            })(e3);
            return "symbol" == u2(t4) ? t4 : t4 + "";
          }
          function x2(e3, t4) {
            for (var i2 = 0; i2 < t4.length; i2++) {
              var n2 = t4[i2];
              n2.enumerable = n2.enumerable || false, n2.configurable = true, "value" in n2 && (n2.writable = true), Object.defineProperty(e3, S2(n2.key), n2);
            }
          }
          function O2(e3, t4, i2) {
            return t4 && x2(e3.prototype, t4), Object.defineProperty(e3, "prototype", { writable: false }), e3;
          }
          var P2 = String.fromCodePoint || function() {
            var e3, t4, i2 = [], n2 = -1, r2 = arguments.length;
            if (!r2) return "";
            for (var o2 = ""; ++n2 < r2; ) {
              var s2 = Number(arguments[n2]);
              if (!isFinite(s2) || s2 < 0 || s2 > 1114111 || Math.floor(s2) != s2) throw RangeError("Invalid code point: " + s2);
              s2 <= 65535 ? i2.push(s2) : (e3 = 55296 + ((s2 -= 65536) >> 10), t4 = s2 % 1024 + 56320, i2.push(e3, t4)), (n2 + 1 === r2 || i2.length > 16384) && (o2 += String.fromCharCode.apply(null, i2), i2.length = 0);
            }
            return o2;
          };
          function A2(e3) {
            var t4 = e3.split("-").map((function(e4) {
              return "0x".concat(e4);
            }));
            return P2.apply(null, t4);
          }
          function M2(e3) {
            return e3.reduce((function(e4, t4) {
              return -1 === e4.indexOf(t4) && e4.push(t4), e4;
            }), []);
          }
          function I2(e3, t4) {
            var i2 = M2(e3), n2 = M2(t4);
            return i2.filter((function(e4) {
              return n2.indexOf(e4) >= 0;
            }));
          }
          function F(e3, t4) {
            var i2 = {};
            for (var n2 in e3) {
              var r2 = e3[n2], o2 = r2;
              Object.prototype.hasOwnProperty.call(t4, n2) && (o2 = t4[n2]), "object" === u2(o2) && (o2 = F(r2, o2)), i2[n2] = o2;
            }
            return i2;
          }
          function z2(e3, t4) {
            var i2 = "undefined" != typeof Symbol && e3[Symbol.iterator] || e3["@@iterator"];
            if (!i2) {
              if (Array.isArray(e3) || (i2 = (function(e4, t10) {
                if (e4) {
                  if ("string" == typeof e4) return L(e4, t10);
                  var i3 = Object.prototype.toString.call(e4).slice(8, -1);
                  return "Object" === i3 && e4.constructor && (i3 = e4.constructor.name), "Map" === i3 || "Set" === i3 ? Array.from(e4) : "Arguments" === i3 || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(i3) ? L(e4, t10) : void 0;
                }
              })(e3)) || t4) {
                i2 && (e3 = i2);
                var n2 = 0, r2 = function() {
                };
                return { s: r2, n: function() {
                  return n2 >= e3.length ? { done: true } : { done: false, value: e3[n2++] };
                }, e: function(e4) {
                  throw e4;
                }, f: r2 };
              }
              throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
            }
            var o2, s2 = true, a3 = false;
            return { s: function() {
              i2 = i2.call(e3);
            }, n: function() {
              var e4 = i2.next();
              return s2 = e4.done, e4;
            }, e: function(e4) {
              a3 = true, o2 = e4;
            }, f: function() {
              try {
                s2 || null == i2.return || i2.return();
              } finally {
                if (a3) throw o2;
              }
            } };
          }
          function L(e3, t4) {
            (null == t4 || t4 > e3.length) && (t4 = e3.length);
            for (var i2 = 0, n2 = new Array(t4); i2 < t4; i2++) n2[i2] = e3[i2];
            return n2;
          }
          var T2 = /^(?:\:([^\:]+)\:)(?:\:skin-tone-(\d)\:)?$/, q2 = ["1F3FA", "1F3FB", "1F3FC", "1F3FD", "1F3FE", "1F3FF"], R2 = (function() {
            return O2((function e3(t4) {
              var i2 = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {}, n2 = i2.emojisToShowFilter, r2 = i2.include, o2 = i2.exclude, s2 = i2.custom, a3 = i2.recent, c2 = i2.recentLength, u3 = void 0 === c2 ? 20 : c2;
              E2(this, e3), this._data = p2(t4), this._emojisFilter = n2 || null, this._include = r2 || null, this._exclude = o2 || null, this._custom = s2 || [], this._recent = a3 || w2.get(u3), this._emojis = {}, this._nativeEmojis = {}, this._emoticons = {}, this._categories = [], this._recentCategory = { id: "recent", name: "Recent", emojis: [] }, this._customCategory = { id: "custom", name: "Custom", emojis: [] }, this._searchIndex = {}, this.buildIndex(), Object.freeze(this);
            }), [{ key: "buildIndex", value: function() {
              var e3 = this, t4 = this._data.categories;
              if (this._include && (t4 = (t4 = t4.filter((function(t10) {
                return e3._include.includes(t10.id);
              }))).sort((function(t10, i3) {
                var n3 = e3._include.indexOf(t10.id), r3 = e3._include.indexOf(i3.id);
                return n3 < r3 ? -1 : n3 > r3 ? 1 : 0;
              }))), t4.forEach((function(t10) {
                if (e3.isCategoryNeeded(t10.id)) {
                  var i3 = { id: t10.id, name: t10.name, emojis: [] };
                  t10.emojis.forEach((function(t11) {
                    var n3 = e3.addEmoji(t11);
                    n3 && i3.emojis.push(n3);
                  })), i3.emojis.length && e3._categories.push(i3);
                }
              })), this.isCategoryNeeded("custom")) {
                if (this._custom.length > 0) {
                  var i2, n2 = z2(this._custom);
                  try {
                    for (n2.s(); !(i2 = n2.n()).done; ) {
                      var r2 = i2.value;
                      this.addCustomEmoji(r2);
                    }
                  } catch (e4) {
                    n2.e(e4);
                  } finally {
                    n2.f();
                  }
                }
                this._customCategory.emojis.length && this._categories.push(this._customCategory);
              }
              this.isCategoryNeeded("recent") && (this._recent.length && this._recent.map((function(t10) {
                var i3, n3 = z2(e3._customCategory.emojis);
                try {
                  for (n3.s(); !(i3 = n3.n()).done; ) {
                    var r3 = i3.value;
                    if (r3.id === t10) return void e3._recentCategory.emojis.push(r3);
                  }
                } catch (e4) {
                  n3.e(e4);
                } finally {
                  n3.f();
                }
                e3.hasEmoji(t10) && e3._recentCategory.emojis.push(e3.emoji(t10));
              })), this._recentCategory.emojis.length && this._categories.unshift(this._recentCategory));
            } }, { key: "findEmoji", value: function(e3, t4) {
              var i2 = e3.match(T2);
              if (i2 && (e3 = i2[1], i2[2] && (t4 = parseInt(i2[2], 10))), this._data.aliases.hasOwnProperty(e3) && (e3 = this._data.aliases[e3]), this._emojis.hasOwnProperty(e3)) {
                var n2 = this._emojis[e3];
                return t4 ? n2.getSkin(t4) : n2;
              }
              return this._nativeEmojis.hasOwnProperty(e3) ? this._nativeEmojis[e3] : null;
            } }, { key: "categories", value: function() {
              return this._categories;
            } }, { key: "emoji", value: function(e3) {
              this._data.aliases.hasOwnProperty(e3) && (e3 = this._data.aliases[e3]);
              var t4 = this._emojis[e3];
              if (!t4) throw new Error("Can not find emoji by id: " + e3);
              return t4;
            } }, { key: "firstEmoji", value: function() {
              var e3 = this._emojis[Object.keys(this._emojis)[0]];
              if (!e3) throw new Error("Can not get first emoji");
              return e3;
            } }, { key: "hasEmoji", value: function(e3) {
              return this._data.aliases.hasOwnProperty(e3) && (e3 = this._data.aliases[e3]), !!this._emojis[e3];
            } }, { key: "nativeEmoji", value: function(e3) {
              return this._nativeEmojis.hasOwnProperty(e3) ? this._nativeEmojis[e3] : null;
            } }, { key: "search", value: function(e3, t4) {
              var i2 = this;
              if (t4 || (t4 = 75), !e3.length) return null;
              if ("-" == e3 || "-1" == e3) return [this.emoji("-1")];
              var n2, r2 = e3.toLowerCase().split(/[\s|,|\-|_]+/);
              r2.length > 2 && (r2 = [r2[0], r2[1]]), n2 = r2.map((function(e4) {
                for (var t10 = i2._emojis, n3 = i2._searchIndex, r3 = 0, o3 = function() {
                  var i3 = e4[s2];
                  if (r3++, n3[i3] || (n3[i3] = {}), !(n3 = n3[i3]).results) {
                    var o4 = {};
                    for (var a3 in n3.results = [], n3.emojis = {}, t10) {
                      var c2 = t10[a3], u3 = c2._data.search, l2 = e4.substr(0, r3), h3 = u3.indexOf(l2);
                      if (-1 != h3) {
                        var m3 = h3 + 1;
                        l2 == a3 && (m3 = 0), n3.results.push(c2), n3.emojis[a3] = c2, o4[a3] = m3;
                      }
                    }
                    n3.results.sort((function(e5, t11) {
                      return o4[e5.id] - o4[t11.id];
                    }));
                  }
                  t10 = n3.emojis;
                }, s2 = 0; s2 < e4.length; s2++) o3();
                return n3.results;
              })).filter((function(e4) {
                return e4;
              }));
              var o2 = null;
              return (o2 = n2.length > 1 ? I2.apply(null, n2) : n2.length ? n2[0] : []) && o2.length > t4 && (o2 = o2.slice(0, t4)), o2;
            } }, { key: "addCustomEmoji", value: function(e3) {
              var t4 = Object.assign({}, e3, { id: e3.short_names[0], custom: true });
              t4.search || (t4.search = m2(t4));
              var i2 = new N2(t4);
              return this._emojis[i2.id] = i2, this._customCategory.emojis.push(i2), i2;
            } }, { key: "addEmoji", value: function(e3) {
              var t4 = this, i2 = this._data.emojis[e3];
              if (!this.isEmojiNeeded(i2)) return false;
              var n2 = new N2(i2);
              if (this._emojis[e3] = n2, n2.native && (this._nativeEmojis[n2.native] = n2), n2._skins) for (var r2 in n2._skins) {
                var o2 = n2._skins[r2];
                o2.native && (this._nativeEmojis[o2.native] = o2);
              }
              return n2.emoticons && n2.emoticons.forEach((function(i3) {
                t4._emoticons[i3] || (t4._emoticons[i3] = e3);
              })), n2;
            } }, { key: "isCategoryNeeded", value: function(e3) {
              var t4 = !this._include || !this._include.length || this._include.indexOf(e3) > -1, i2 = !(!this._exclude || !this._exclude.length) && this._exclude.indexOf(e3) > -1;
              return !(!t4 || i2);
            } }, { key: "isEmojiNeeded", value: function(e3) {
              return !this._emojisFilter || this._emojisFilter(e3);
            } }]);
          })(), N2 = (function() {
            return O2((function e3(t4) {
              if (E2(this, e3), this._data = Object.assign({}, t4), this._skins = null, this._data.skin_variations) for (var i2 in this._skins = [], q2) {
                var n2 = q2[i2], r2 = this._data.skin_variations[n2], o2 = Object.assign({}, t4);
                for (var s2 in r2) o2[s2] = r2[s2];
                delete o2.skin_variations, o2.skin_tone = parseInt(i2) + 1, this._skins.push(new e3(o2));
              }
              for (var a3 in this._sanitized = D(this._data), this._sanitized) this[a3] = this._sanitized[a3];
              this.short_names = this._data.short_names, this.short_name = this._data.short_names[0], Object.freeze(this);
            }), [{ key: "getSkin", value: function(e3) {
              return e3 && "native" != e3 && this._skins ? this._skins[e3 - 1] : this;
            } }, { key: "getPosition", value: function() {
              var e3 = +(100 / 60 * this._data.sheet_x).toFixed(2), t4 = +(100 / 60 * this._data.sheet_y).toFixed(2);
              return "".concat(e3, "% ").concat(t4, "%");
            } }, { key: "ariaLabel", value: function() {
              return [this.native].concat(this.short_names).filter(Boolean).join(", ");
            } }]);
          })(), $2 = (function() {
            return O2((function e3(t4, i2, n2, r2, o2, s2, a3) {
              E2(this, e3), this._emoji = t4, this._native = r2, this._skin = i2, this._set = n2, this._fallback = o2, this.canRender = this._canRender(), this.cssClass = this._cssClass(), this.cssStyle = this._cssStyle(a3), this.content = this._content(), this.title = true === s2 ? t4.short_name : null, this.ariaLabel = t4.ariaLabel(), Object.freeze(this);
            }), [{ key: "getEmoji", value: function() {
              return this._emoji.getSkin(this._skin);
            } }, { key: "_canRender", value: function() {
              return this._isCustom() || this._isNative() || this._hasEmoji() || this._fallback;
            } }, { key: "_cssClass", value: function() {
              return ["emoji-set-" + this._set, "emoji-type-" + this._emojiType()];
            } }, { key: "_cssStyle", value: function(e3) {
              var t4 = {};
              return this._isCustom() ? t4 = { backgroundImage: "url(" + this.getEmoji()._data.imageUrl + ")", backgroundSize: "100%", width: e3 + "px", height: e3 + "px" } : this._hasEmoji() && !this._isNative() && (t4 = { backgroundPosition: this.getEmoji().getPosition() }), e3 && (t4 = this._isNative() ? Object.assign(t4, { fontSize: Math.round(0.95 * e3 * 10) / 10 + "px" }) : Object.assign(t4, { width: e3 + "px", height: e3 + "px" })), t4;
            } }, { key: "_content", value: function() {
              return this._isCustom() ? "" : this._isNative() ? this.getEmoji().native : this._hasEmoji() ? "" : this._fallback ? this._fallback(this.getEmoji()) : null;
            } }, { key: "_isNative", value: function() {
              return this._native;
            } }, { key: "_isCustom", value: function() {
              return this.getEmoji().custom;
            } }, { key: "_hasEmoji", value: function() {
              if (!this.getEmoji()._data) return false;
              var e3 = this.getEmoji()._data["has_img_" + this._set];
              return void 0 === e3 || e3;
            } }, { key: "_emojiType", value: function() {
              return this._isCustom() ? "custom" : this._isNative() ? "native" : this._hasEmoji() ? "image" : "fallback";
            } }]);
          })();
          function D(e3) {
            var t4 = e3.name, i2 = e3.short_names, n2 = e3.skin_tone, r2 = e3.skin_variations, o2 = e3.emoticons, s2 = e3.unified, a3 = e3.custom, c2 = e3.imageUrl, u3 = e3.id || i2[0], l2 = ":".concat(u3, ":");
            return a3 ? { id: u3, name: t4, colons: l2, emoticons: o2, custom: a3, imageUrl: c2 } : (n2 && (l2 += ":skin-tone-".concat(n2, ":")), { id: u3, name: t4, colons: l2, emoticons: o2, unified: s2.toLowerCase(), skin: n2 || (r2 ? 1 : null), native: A2(s2) });
          }
          function B2(e3, t4, i2) {
            return (t4 = S2(t4)) in e3 ? Object.defineProperty(e3, t4, { value: i2, enumerable: true, configurable: true, writable: true }) : e3[t4] = i2, e3;
          }
          var H2 = { native: { type: Boolean, default: false }, tooltip: { type: Boolean, default: false }, fallback: { type: Function }, skin: { type: Number, default: 1 }, set: { type: String, default: "apple" }, emoji: { type: [String, Object], required: true }, size: { type: Number, default: null }, tag: { type: String, default: "span" } }, U = { perLine: { type: Number, default: 9 }, maxSearchResults: { type: Number, default: 75 }, emojiSize: { type: Number, default: 24 }, title: { type: String, default: "Emoji Mart™" }, emoji: { type: String, default: "department_store" }, color: { type: String, default: "#ae65c5" }, set: { type: String, default: "apple" }, skin: { type: Number, default: null }, defaultSkin: { type: Number, default: 1 }, native: { type: Boolean, default: false }, emojiTooltip: { type: Boolean, default: false }, autoFocus: { type: Boolean, default: false }, i18n: { type: Object, default: function() {
            return {};
          } }, showPreview: { type: Boolean, default: true }, showSearch: { type: Boolean, default: true }, showCategories: { type: Boolean, default: true }, showSkinTones: { type: Boolean, default: true }, infiniteScroll: { type: Boolean, default: true }, pickerStyles: { type: Object, default: function() {
            return {};
          } } };
          function V(e3, t4) {
            var i2 = Object.keys(e3);
            if (Object.getOwnPropertySymbols) {
              var n2 = Object.getOwnPropertySymbols(e3);
              t4 && (n2 = n2.filter((function(t10) {
                return Object.getOwnPropertyDescriptor(e3, t10).enumerable;
              }))), i2.push.apply(i2, n2);
            }
            return i2;
          }
          function W(e3) {
            for (var t4 = 1; t4 < arguments.length; t4++) {
              var i2 = null != arguments[t4] ? arguments[t4] : {};
              t4 % 2 ? V(Object(i2), true).forEach((function(t10) {
                B2(e3, t10, i2[t10]);
              })) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e3, Object.getOwnPropertyDescriptors(i2)) : V(Object(i2)).forEach((function(t10) {
                Object.defineProperty(e3, t10, Object.getOwnPropertyDescriptor(i2, t10));
              }));
            }
            return e3;
          }
          var J = b2({ props: W(W({}, H2), {}, { data: { type: Object, required: true } }), emits: ["click", "mouseenter", "mouseleave"], computed: { view: function() {
            return new $2(this.emojiObject, this.skin, this.set, this.native, this.fallback, this.tooltip, this.size);
          }, sanitizedData: function() {
            return this.emojiObject._sanitized;
          }, title: function() {
            return this.tooltip ? this.emojiObject.short_name : null;
          }, emojiObject: function() {
            return "string" == typeof this.emoji ? this.data.findEmoji(this.emoji) : this.emoji;
          } }, created: function() {
          }, methods: { onClick: function() {
            this.$emit("click", this.emojiObject);
          }, onMouseEnter: function() {
            this.$emit("mouseenter", this.emojiObject);
          }, onMouseLeave: function() {
            this.$emit("mouseleave", this.emojiObject);
          } } }, (function() {
            var e3 = this, t4 = e3._self._c;
            return e3.view.canRender ? t4(e3.tag, { tag: "component", staticClass: "emoji-mart-emoji", attrs: { title: e3.view.title, "aria-label": e3.view.ariaLabel, "data-title": e3.title }, on: { mouseenter: e3.onMouseEnter, mouseleave: e3.onMouseLeave, click: e3.onClick } }, [t4("span", { class: e3.view.cssClass, style: e3.view.cssStyle }, [e3._v(e3._s(e3.view.content))])]) : e3._e();
          }), []).exports, X = b2({ props: { data: { type: Object, required: true }, i18n: { type: Object, required: true }, id: { type: String, required: true }, name: { type: String, required: true }, emojis: { type: Array }, emojiProps: { type: Object, required: true } }, methods: { activeClass: function(e3) {
            return this.emojiProps.selectedEmoji && this.emojiProps.selectedEmojiCategory && this.emojiProps.selectedEmoji.id == e3.id && this.emojiProps.selectedEmojiCategory.id == this.id ? "emoji-mart-emoji-selected" : "";
          } }, computed: { isVisible: function() {
            return !!this.emojis;
          }, isSearch: function() {
            return "Search" == this.name;
          }, hasResults: function() {
            return this.emojis.length > 0;
          }, emojiObjects: function() {
            var e3 = this;
            return this.emojis.map((function(t4) {
              return { emojiObject: t4, emojiView: new $2(t4, e3.emojiProps.skin, e3.emojiProps.set, e3.emojiProps.native, e3.emojiProps.fallback, e3.emojiProps.emojiTooltip, e3.emojiProps.emojiSize) };
            }));
          } }, components: { Emoji: J } }, (function() {
            var e3 = this, t4 = e3._self._c;
            return e3.isVisible && (e3.isSearch || e3.hasResults) ? t4("section", { class: { "emoji-mart-category": true, "emoji-mart-no-results": !e3.hasResults }, attrs: { "aria-label": e3.i18n.categories[e3.id] } }, [t4("div", { staticClass: "emoji-mart-category-label" }, [t4("h3", { staticClass: "emoji-mart-category-label" }, [e3._v(e3._s(e3.i18n.categories[e3.id]))])]), e3._v(" "), e3._l(e3.emojiObjects, (function(i2) {
              var n2 = i2.emojiObject, r2 = i2.emojiView;
              return [r2.canRender ? t4("button", { key: n2.id, staticClass: "emoji-mart-emoji", class: e3.activeClass(n2), attrs: { "aria-label": r2.ariaLabel, role: "option", "aria-selected": "false", "aria-posinset": "1", "aria-setsize": "1812", type: "button", "data-title": n2.short_name, title: r2.title }, on: { mouseenter: function(t10) {
                e3.emojiProps.onEnter(r2.getEmoji());
              }, mouseleave: function(t10) {
                e3.emojiProps.onLeave(r2.getEmoji());
              }, click: function(t10) {
                e3.emojiProps.onClick(r2.getEmoji());
              } } }, [t4("span", { class: r2.cssClass, style: r2.cssStyle }, [e3._v(e3._s(r2.content))])]) : e3._e()];
            })), e3._v(" "), e3.hasResults ? e3._e() : t4("div", [t4("emoji", { attrs: { data: e3.data, emoji: "sleuth_or_spy", native: e3.emojiProps.native, skin: e3.emojiProps.skin, set: e3.emojiProps.set } }), e3._v(" "), t4("div", { staticClass: "emoji-mart-no-results-label" }, [e3._v(e3._s(e3.i18n.notfound))])], 1)], 2) : e3._e();
          }), []).exports, Z = b2({ props: { skin: { type: Number, required: true } }, emits: ["change"], data: function() {
            return { opened: false };
          }, methods: { onClick: function(e3) {
            this.opened && e3 != this.skin && this.$emit("change", e3), this.opened = !this.opened;
          } } }, (function() {
            var e3 = this, t4 = e3._self._c;
            return t4("div", { class: { "emoji-mart-skin-swatches": true, "emoji-mart-skin-swatches-opened": e3.opened } }, e3._l(6, (function(i2) {
              return t4("span", { key: i2, class: { "emoji-mart-skin-swatch": true, "emoji-mart-skin-swatch-selected": e3.skin == i2 } }, [t4("span", { class: "emoji-mart-skin emoji-mart-skin-tone-" + i2, on: { click: function(t10) {
                return e3.onClick(i2);
              } } })]);
            })), 0);
          }), []).exports, G = b2({ props: { data: { type: Object, required: true }, title: { type: String, required: true }, emoji: { type: [String, Object] }, idleEmoji: { type: [String, Object], required: true }, showSkinTones: { type: Boolean, default: true }, emojiProps: { type: Object, required: true }, skinProps: { type: Object, required: true }, onSkinChange: { type: Function, required: true } }, computed: { emojiData: function() {
            return this.emoji ? this.emoji : {};
          }, emojiShortNames: function() {
            return this.emojiData.short_names;
          }, emojiEmoticons: function() {
            return this.emojiData.emoticons;
          } }, components: { Emoji: J, Skins: Z } }, (function() {
            var e3 = this, t4 = e3._self._c;
            return t4("div", { staticClass: "emoji-mart-preview" }, [e3.emoji ? [t4("div", { staticClass: "emoji-mart-preview-emoji" }, [t4("emoji", { attrs: { data: e3.data, emoji: e3.emoji, native: e3.emojiProps.native, skin: e3.emojiProps.skin, set: e3.emojiProps.set } })], 1), e3._v(" "), t4("div", { staticClass: "emoji-mart-preview-data" }, [t4("div", { staticClass: "emoji-mart-preview-name" }, [e3._v(e3._s(e3.emoji.name))]), e3._v(" "), t4("div", { staticClass: "emoji-mart-preview-shortnames" }, e3._l(e3.emojiShortNames, (function(i2) {
              return t4("span", { key: i2, staticClass: "emoji-mart-preview-shortname" }, [e3._v(":" + e3._s(i2) + ":")]);
            })), 0), e3._v(" "), t4("div", { staticClass: "emoji-mart-preview-emoticons" }, e3._l(e3.emojiEmoticons, (function(i2) {
              return t4("span", { key: i2, staticClass: "emoji-mart-preview-emoticon" }, [e3._v(e3._s(i2))]);
            })), 0)])] : [t4("div", { staticClass: "emoji-mart-preview-emoji" }, [t4("emoji", { attrs: { data: e3.data, emoji: e3.idleEmoji, native: e3.emojiProps.native, skin: e3.emojiProps.skin, set: e3.emojiProps.set } })], 1), e3._v(" "), t4("div", { staticClass: "emoji-mart-preview-data" }, [t4("span", { staticClass: "emoji-mart-title-label" }, [e3._v(e3._s(e3.title))])]), e3._v(" "), e3.showSkinTones ? t4("div", { staticClass: "emoji-mart-preview-skins" }, [t4("skins", { attrs: { skin: e3.skinProps.skin }, on: { change: function(t10) {
              return e3.onSkinChange(t10);
            } } })], 1) : e3._e()]], 2);
          }), []).exports, K = b2({ props: { data: { type: Object, required: true }, i18n: { type: Object, required: true }, autoFocus: { type: Boolean, default: false }, onSearch: { type: Function, required: true }, onArrowLeft: { type: Function, required: false }, onArrowRight: { type: Function, required: false }, onArrowDown: { type: Function, required: false }, onArrowUp: { type: Function, required: false }, onEnter: { type: Function, required: false } }, emits: ["search", "enter", "arrowUp", "arrowDown", "arrowRight", "arrowLeft"], data: function() {
            return { value: "" };
          }, computed: { emojiIndex: function() {
            return this.data;
          } }, watch: { value: function() {
            this.$emit("search", this.value);
          } }, methods: { clear: function() {
            this.value = "";
          } }, mounted: function() {
            var e3 = this.$el.querySelector("input");
            this.autoFocus && e3.focus();
          } }, (function() {
            var e3 = this, t4 = e3._self._c;
            return t4("div", { staticClass: "emoji-mart-search" }, [t4("input", { directives: [{ name: "model", rawName: "v-model", value: e3.value, expression: "value" }], attrs: { type: "text", placeholder: e3.i18n.search, role: "textbox", "aria-autocomplete": "list", "aria-owns": "emoji-mart-list", "aria-label": "Search for an emoji", "aria-describedby": "emoji-mart-search-description" }, domProps: { value: e3.value }, on: { keydown: [function(t10) {
              return !t10.type.indexOf("key") && e3._k(t10.keyCode, "left", 37, t10.key, ["Left", "ArrowLeft"]) || "button" in t10 && 0 !== t10.button ? null : function(t11) {
                return e3.$emit("arrowLeft", t11);
              }.apply(null, arguments);
            }, function(t10) {
              return !t10.type.indexOf("key") && e3._k(t10.keyCode, "right", 39, t10.key, ["Right", "ArrowRight"]) || "button" in t10 && 2 !== t10.button ? null : function() {
                return e3.$emit("arrowRight");
              }.apply(null, arguments);
            }, function(t10) {
              return !t10.type.indexOf("key") && e3._k(t10.keyCode, "down", 40, t10.key, ["Down", "ArrowDown"]) ? null : function() {
                return e3.$emit("arrowDown");
              }.apply(null, arguments);
            }, function(t10) {
              return !t10.type.indexOf("key") && e3._k(t10.keyCode, "up", 38, t10.key, ["Up", "ArrowUp"]) ? null : function(t11) {
                return e3.$emit("arrowUp", t11);
              }.apply(null, arguments);
            }, function(t10) {
              return !t10.type.indexOf("key") && e3._k(t10.keyCode, "enter", 13, t10.key, "Enter") ? null : function() {
                return e3.$emit("enter");
              }.apply(null, arguments);
            }], input: function(t10) {
              t10.target.composing || (e3.value = t10.target.value);
            } } }), e3._v(" "), t4("span", { staticClass: "hidden", attrs: { id: "emoji-picker-search-description" } }, [e3._v("Use the left, right, up and down arrow keys to navigate the emoji search\n    results.")])]);
          }), []), Q = K.exports;
          function Y(e3, t4) {
            (null == t4 || t4 > e3.length) && (t4 = e3.length);
            for (var i2 = 0, n2 = new Array(t4); i2 < t4; i2++) n2[i2] = e3[i2];
            return n2;
          }
          i(537);
          var ee = (function() {
            return O2((function e3(t4) {
              var i2, n2;
              E2(this, e3), this._vm = t4, this._data = t4.data, this._perLine = t4.perLine, this._categories = [], (i2 = this._categories).push.apply(i2, (function(e4) {
                if (Array.isArray(e4)) return Y(e4);
              })(n2 = this._data.categories()) || (function(e4) {
                if ("undefined" != typeof Symbol && null != e4[Symbol.iterator] || null != e4["@@iterator"]) return Array.from(e4);
              })(n2) || (function(e4, t10) {
                if (e4) {
                  if ("string" == typeof e4) return Y(e4, t10);
                  var i3 = Object.prototype.toString.call(e4).slice(8, -1);
                  return "Object" === i3 && e4.constructor && (i3 = e4.constructor.name), "Map" === i3 || "Set" === i3 ? Array.from(e4) : "Arguments" === i3 || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(i3) ? Y(e4, t10) : void 0;
                }
              })(n2) || (function() {
                throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
              })()), this._categories = this._categories.filter((function(e4) {
                return e4.emojis.length > 0;
              })), this._categories[0].first = true, Object.freeze(this._categories), this.activeCategory = this._categories[0], this.searchEmojis = null, this.previewEmoji = null, this.previewEmojiCategoryIdx = 0, this.previewEmojiIdx = -1;
            }), [{ key: "onScroll", value: function() {
              var e3 = this._vm.$refs.scroll;
              if (e3) {
                for (var t4 = e3.scrollTop, i2 = this.filteredCategories[0], n2 = 0, r2 = this.filteredCategories.length; n2 < r2; n2++) {
                  var o2 = this.filteredCategories[n2], s2 = this._vm.getCategoryComponent(n2);
                  if (s2 && s2.$el.offsetTop - 50 > t4) break;
                  i2 = o2;
                }
                this.activeCategory = i2;
              }
            } }, { key: "allCategories", get: function() {
              return this._categories;
            } }, { key: "filteredCategories", get: function() {
              return this.searchEmojis ? [{ id: "search", name: "Search", emojis: this.searchEmojis }] : this._categories.filter((function(e3) {
                return e3.emojis.length > 0;
              }));
            } }, { key: "previewEmojiCategory", get: function() {
              return this.previewEmojiCategoryIdx >= 0 ? this.filteredCategories[this.previewEmojiCategoryIdx] : null;
            } }, { key: "onAnchorClick", value: function(e3) {
              var t4 = this;
              if (!this.searchEmojis) {
                var i2 = this.filteredCategories.indexOf(e3), n2 = this._vm.getCategoryComponent(i2);
                this._vm.infiniteScroll ? (function() {
                  if (n2) {
                    var i3 = n2.$el.offsetTop;
                    e3.first && (i3 = 0), t4._vm.$refs.scroll.scrollTop = i3;
                  }
                })() : this.activeCategory = this.filteredCategories[i2];
              }
            } }, { key: "onSearch", value: function(e3) {
              var t4 = this._data.search(e3, this.maxSearchResults);
              this.searchEmojis = t4, this.previewEmojiCategoryIdx = 0, this.previewEmojiIdx = 0, this.updatePreviewEmoji();
            } }, { key: "onEmojiEnter", value: function(e3) {
              this.previewEmoji = e3, this.previewEmojiIdx = -1, this.previewEmojiCategoryIdx = -1;
            } }, { key: "onEmojiLeave", value: function(e3) {
              this.previewEmoji = null;
            } }, { key: "onArrowLeft", value: function() {
              this.previewEmojiIdx > 0 ? this.previewEmojiIdx -= 1 : (this.previewEmojiCategoryIdx -= 1, this.previewEmojiCategoryIdx < 0 ? this.previewEmojiCategoryIdx = 0 : this.previewEmojiIdx = this.filteredCategories[this.previewEmojiCategoryIdx].emojis.length - 1), this.updatePreviewEmoji();
            } }, { key: "onArrowRight", value: function() {
              this.previewEmojiIdx < this.emojisLength(this.previewEmojiCategoryIdx) - 1 ? this.previewEmojiIdx += 1 : (this.previewEmojiCategoryIdx += 1, this.previewEmojiCategoryIdx >= this.filteredCategories.length ? this.previewEmojiCategoryIdx = this.filteredCategories.length - 1 : this.previewEmojiIdx = 0), this.updatePreviewEmoji();
            } }, { key: "onArrowDown", value: function() {
              if (-1 == this.previewEmojiIdx) return this.onArrowRight();
              var e3 = this.filteredCategories[this.previewEmojiCategoryIdx].emojis.length, t4 = this._perLine;
              this.previewEmojiIdx + t4 > e3 && (t4 = e3 % this._perLine);
              for (var i2 = 0; i2 < t4; i2++) this.onArrowRight();
              this.updatePreviewEmoji();
            } }, { key: "onArrowUp", value: function() {
              var e3 = this._perLine;
              this.previewEmojiIdx - e3 < 0 && (e3 = this.previewEmojiCategoryIdx > 0 ? this.filteredCategories[this.previewEmojiCategoryIdx - 1].emojis.length % this._perLine : 0);
              for (var t4 = 0; t4 < e3; t4++) this.onArrowLeft();
              this.updatePreviewEmoji();
            } }, { key: "updatePreviewEmoji", value: function() {
              var e3 = this;
              this.previewEmoji = this.filteredCategories[this.previewEmojiCategoryIdx].emojis[this.previewEmojiIdx], this._vm.$nextTick((function() {
                var t4 = e3._vm.$refs.scroll, i2 = t4.querySelector(".emoji-mart-emoji-selected"), n2 = t4.offsetTop - t4.offsetHeight;
                i2 && i2.offsetTop + i2.offsetHeight > n2 + t4.scrollTop && (t4.scrollTop += i2.offsetHeight), i2 && i2.offsetTop < t4.scrollTop && (t4.scrollTop -= i2.offsetHeight);
              }));
            } }, { key: "emojisLength", value: function(e3) {
              return -1 == e3 ? 0 : this.filteredCategories[e3].emojis.length;
            } }]);
          })();
          function te(e3, t4) {
            var i2 = Object.keys(e3);
            if (Object.getOwnPropertySymbols) {
              var n2 = Object.getOwnPropertySymbols(e3);
              t4 && (n2 = n2.filter((function(t10) {
                return Object.getOwnPropertyDescriptor(e3, t10).enumerable;
              }))), i2.push.apply(i2, n2);
            }
            return i2;
          }
          function ie(e3) {
            for (var t4 = 1; t4 < arguments.length; t4++) {
              var i2 = null != arguments[t4] ? arguments[t4] : {};
              t4 % 2 ? te(Object(i2), true).forEach((function(t10) {
                B2(e3, t10, i2[t10]);
              })) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e3, Object.getOwnPropertyDescriptors(i2)) : te(Object(i2)).forEach((function(t10) {
                Object.defineProperty(e3, t10, Object.getOwnPropertyDescriptor(i2, t10));
              }));
            }
            return e3;
          }
          var ne = { search: "Search", notfound: "No Emoji Found", categories: { search: "Search Results", recent: "Frequently Used", smileys: "Smileys & Emotion", people: "People & Body", nature: "Animals & Nature", foods: "Food & Drink", activity: "Activity", places: "Travel & Places", objects: "Objects", symbols: "Symbols", flags: "Flags", custom: "Custom" } }, re = { props: ie(ie({}, U), {}, { data: { type: Object, required: true } }), emits: ["select", "skin-change"], data: function() {
            return { activeSkin: this.skin || c.get("skin") || this.defaultSkin, view: new ee(this) };
          }, computed: { customStyles: function() {
            return ie({ width: this.calculateWidth + "px" }, this.pickerStyles);
          }, emojiProps: function() {
            return { native: this.native, skin: this.activeSkin, set: this.set, emojiTooltip: this.emojiTooltip, emojiSize: this.emojiSize, selectedEmoji: this.view.previewEmoji, selectedEmojiCategory: this.view.previewEmojiCategory, onEnter: this.onEmojiEnter.bind(this), onLeave: this.onEmojiLeave.bind(this), onClick: this.onEmojiClick.bind(this) };
          }, skinProps: function() {
            return { skin: this.activeSkin };
          }, calculateWidth: function() {
            return this.perLine * (this.emojiSize + 12) + 12 + 2 + (function() {
              if ("undefined" == typeof document) return 0;
              var e3 = document.createElement("div");
              e3.style.width = "100px", e3.style.height = "100px", e3.style.overflow = "scroll", e3.style.position = "absolute", e3.style.top = "-9999px", document.body.appendChild(e3);
              var t4 = e3.offsetWidth - e3.clientWidth;
              return document.body.removeChild(e3), t4;
            })();
          }, filteredCategories: function() {
            return this.view.filteredCategories;
          }, mergedI18n: function() {
            return Object.freeze(F(ne, this.i18n));
          }, idleEmoji: function() {
            try {
              return this.data.emoji(this.emoji);
            } catch (e3) {
              return console.error("Default preview emoji `" + this.emoji + "` is not available, check the Picker `emoji` property"), console.error(e3), this.data.firstEmoji();
            }
          }, isSearching: function() {
            return null != this.view.searchEmojis;
          } }, watch: { skin: function() {
            this.onSkinChange(this.skin);
          } }, methods: { onScroll: function() {
            this.infiniteScroll && !this.waitingForPaint && (this.waitingForPaint = true, window.requestAnimationFrame(this.onScrollPaint.bind(this)));
          }, onScrollPaint: function() {
            this.waitingForPaint = false, this.view.onScroll();
          }, onAnchorClick: function(e3) {
            this.view.onAnchorClick(e3);
          }, onSearch: function(e3) {
            this.view.onSearch(e3);
          }, onEmojiEnter: function(e3) {
            this.view.onEmojiEnter(e3);
          }, onEmojiLeave: function(e3) {
            this.view.onEmojiLeave(e3);
          }, onArrowLeft: function(e3) {
            var t4 = this.view.previewEmojiIdx;
            this.view.onArrowLeft(), e3 && this.view.previewEmojiIdx !== t4 && e3.preventDefault();
          }, onArrowRight: function() {
            this.view.onArrowRight();
          }, onArrowDown: function() {
            this.view.onArrowDown();
          }, onArrowUp: function(e3) {
            this.view.onArrowUp(), e3.preventDefault();
          }, onEnter: function() {
            this.view.previewEmoji && (this.$emit("select", this.view.previewEmoji), w2.add(this.view.previewEmoji));
          }, onEmojiClick: function(e3) {
            this.$emit("select", e3), w2.add(e3);
          }, onTextSelect: function(e3) {
            e3.stopPropagation();
          }, onSkinChange: function(e3) {
            this.activeSkin = e3, c.update({ skin: e3 }), this.$emit("skin-change", e3);
          }, getCategoryComponent: function(e3) {
            var t4 = this.$refs["categories_" + e3];
            return t4 && "0" in t4 ? t4[0] : t4;
          } }, components: { Anchors: k2, Category: X, Preview: G, Search: Q } }, oe = b2(re, (function() {
            var e3 = this, t4 = e3._self._c;
            return t4("section", { staticClass: "emoji-mart emoji-mart-static", style: e3.customStyles }, [e3.showCategories ? t4("div", { staticClass: "emoji-mart-bar emoji-mart-bar-anchors" }, [t4("anchors", { attrs: { data: e3.data, i18n: e3.mergedI18n, color: e3.color, categories: e3.view.allCategories, "active-category": e3.view.activeCategory }, on: { click: e3.onAnchorClick } })], 1) : e3._e(), e3._v(" "), e3._t("searchTemplate", (function() {
              return [e3.showSearch ? t4("search", { ref: "search", attrs: { data: e3.data, i18n: e3.mergedI18n, "auto-focus": e3.autoFocus, "on-search": e3.onSearch }, on: { search: e3.onSearch, arrowLeft: e3.onArrowLeft, arrowRight: e3.onArrowRight, arrowDown: e3.onArrowDown, arrowUp: e3.onArrowUp, enter: e3.onEnter, select: e3.onTextSelect } }) : e3._e()];
            }), { data: e3.data, i18n: e3.i18n, autoFocus: e3.autoFocus, onSearch: e3.onSearch }), e3._v(" "), t4("div", { ref: "scroll", staticClass: "emoji-mart-scroll", attrs: { role: "tabpanel" }, on: { scroll: e3.onScroll } }, [t4("div", { ref: "scrollContent", attrs: { id: "emoji-mart-list", role: "listbox", "aria-expanded": "true" } }, [e3._t("customCategory"), e3._v(" "), e3._l(e3.view.filteredCategories, (function(i2, n2) {
              return t4("category", { directives: [{ name: "show", rawName: "v-show", value: e3.infiniteScroll || i2 == e3.view.activeCategory || e3.isSearching, expression: "infiniteScroll || category == view.activeCategory || isSearching" }], key: i2.id, ref: "categories_" + n2, refInFor: true, attrs: { data: e3.data, i18n: e3.mergedI18n, id: i2.id, name: i2.name, emojis: i2.emojis, "emoji-props": e3.emojiProps } });
            }))], 2)]), e3._v(" "), e3._t("previewTemplate", (function() {
              return [e3.showPreview ? t4("div", { staticClass: "emoji-mart-bar emoji-mart-bar-preview" }, [t4("preview", { attrs: { data: e3.data, title: e3.title, emoji: e3.view.previewEmoji, "idle-emoji": e3.idleEmoji, "show-skin-tones": e3.showSkinTones, "emoji-props": e3.emojiProps, "skin-props": e3.skinProps, "on-skin-change": e3.onSkinChange } })], 1) : e3._e()];
            }), { data: e3.data, title: e3.title, emoji: e3.view.previewEmoji, idleEmoji: e3.idleEmoji, showSkinTones: e3.showSkinTones, emojiProps: e3.emojiProps, skinProps: e3.skinProps, onSkinChange: e3.onSkinChange })], 2);
          }), []), se = oe.exports;
        })(), n;
      })();
    }));
  })(emojiMart$1);
  return emojiMart$1.exports;
}
requireEmojiMart();
getBuilder("nextcloud-vue").persist(true).build();
register(t5, t16, t37, t43);
({
  search: t$1("Search emoji"),
  notfound: t$1("No emoji found"),
  categories: {
    search: t$1("Search results"),
    recent: t$1("Frequently used"),
    smileys: t$1("Smileys & Emotion"),
    people: t$1("People & Body"),
    nature: t$1("Animals & Nature"),
    foods: t$1("Food & Drink"),
    activity: t$1("Activities"),
    places: t$1("Travel & Places"),
    objects: t$1("Objects"),
    symbols: t$1("Symbols"),
    flags: t$1("Flags"),
    custom: t$1("Custom")
  }
});
[
  new Color(255, 222, 52, t$1("Neutral skin color")),
  new Color(228, 205, 166, t$1("Light skin tone")),
  new Color(250, 221, 192, t$1("Medium light skin tone")),
  new Color(174, 129, 87, t$1("Medium skin tone")),
  new Color(158, 113, 88, t$1("Medium dark skin tone")),
  new Color(96, 79, 69, t$1("Dark skin tone"))
];
({
  props: {
    /**
     * The fallback text in the preview section
     */
    previewFallbackName: {
      default: t$1("Pick an emoji")
    }
  }
});
/*!
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const gtBuilder = getGettextBuilder().detectLanguage();
const gt = gtBuilder.build();
gt.ngettext.bind(gt);
gt.gettext.bind(gt);
register(t22);
register(t7);
register(t31);
const _sfc_main$A = {
  name: "NcListItem",
  components: {
    NcActions,
    NcCounterBubble,
    NcVNodes: _sfc_main$M
  },
  inheritAttrs: false,
  setup() {
    return { isLegacy34 };
  },
  props: {
    /**
     * The details text displayed in the upper right part of the component
     */
    details: {
      type: String,
      default: ""
    },
    /**
     * Name (first line of text)
     */
    name: {
      type: String,
      default: void 0
    },
    /**
     * The route for the router link.
     */
    to: {
      type: [String, Object],
      default: null
    },
    /**
     * The value for the external link
     */
    href: {
      type: String,
      default: "#"
    },
    /**
     * The HTML target attribute used for the link
     */
    target: {
      type: String,
      default: ""
    },
    /**
     * Id for the `<a>` element
     */
    anchorId: {
      type: String,
      default: ""
    },
    /**
     * Make subname bold
     */
    bold: {
      type: Boolean,
      default: false
    },
    /**
     * Show the NcListItem in compact design
     */
    compact: {
      type: Boolean,
      default: false
    },
    /**
     * Toggle the active state of the component
     */
    active: {
      type: Boolean,
      default: void 0
    },
    /**
     * Aria label for the wrapper element
     */
    linkAriaLabel: {
      type: String,
      default: ""
    },
    /**
     * Aria label for the actions toggle
     */
    actionsAriaLabel: {
      type: String,
      default: void 0
    },
    /**
     * If different from 0 this component will display the
     * NcCounterBubble component
     */
    counterNumber: {
      type: [Number, String],
      default: 0
    },
    /**
     * Outlined or highlighted state of the counter
     */
    counterType: {
      type: String,
      default: "",
      validator(value) {
        return ["highlighted", "outlined", ""].indexOf(value) !== -1;
      }
    },
    /**
     * To be used only when the elements in the actions menu are very important
     */
    forceDisplayActions: {
      type: Boolean,
      default: false
    },
    /**
     * Force the actions to display in a three dot menu
     */
    forceMenu: {
      type: Boolean,
      default: false
    },
    /**
     * Show the list component layout
     */
    oneLine: {
      type: Boolean,
      default: false
    }
  },
  emits: [
    "click",
    "dragstart",
    "update:menuOpen"
  ],
  data() {
    return {
      hovered: false,
      hasActions: false,
      hasSubname: false,
      displayActionsOnHoverFocus: false,
      menuOpen: false,
      hasIndicator: false,
      hasDetails: false
    };
  },
  computed: {
    showAdditionalElements() {
      return !this.displayActionsOnHoverFocus || this.forceDisplayActions;
    },
    showDetails() {
      return (this.details !== "" || this.hasDetails) && (!this.displayActionsOnHoverFocus || this.forceDisplayActions);
    }
  },
  watch: {
    menuOpen(newValue) {
      if (!newValue && !this.hovered) {
        this.displayActionsOnHoverFocus = false;
      }
    }
  },
  mounted() {
    this.checkSlots();
  },
  updated() {
    this.checkSlots();
  },
  methods: {
    /**
     * Handle link click
     *
     * @param {MouseEvent|KeyboardEvent} event - Native click or keydown event
     * @param {Function} [navigate] - VueRouter link's navigate if any
     * @param {string} [routerLinkHref] - VueRouter link's href
     */
    onClick(event, navigate, routerLinkHref) {
      this.$emit("click", event);
      if (event.metaKey || event.altKey || event.ctrlKey || event.shiftKey) {
        return;
      }
      if (routerLinkHref) {
        navigate?.(event);
        event.preventDefault();
      }
    },
    showActions() {
      if (this.hasActions) {
        this.displayActionsOnHoverFocus = true;
      }
      this.hovered = false;
    },
    hideActions() {
      this.displayActionsOnHoverFocus = false;
    },
    /**
     * @param {FocusEvent} event UI event
     */
    handleBlur(event) {
      if (this.menuOpen) {
        return;
      }
      if (this.$refs["list-item"]?.contains(event.relatedTarget)) {
        return;
      }
      this.hideActions();
    },
    /**
     * Hide the actions on mouseleave unless the menu is open
     */
    handleMouseleave() {
      if (!this.menuOpen) {
        this.displayActionsOnHoverFocus = false;
      }
      this.hovered = false;
    },
    handleMouseover() {
      this.showActions();
      this.hovered = true;
    },
    handleActionsUpdateOpen(e) {
      this.menuOpen = e;
      this.$emit("update:menuOpen", e);
    },
    // Check if subname and actions slots are populated
    checkSlots() {
      if (this.hasActions !== !!this.$slots.actions) {
        this.hasActions = !!this.$slots.actions;
      }
      if (this.hasSubname !== !!this.$slots.subname) {
        this.hasSubname = !!this.$slots.subname;
      }
      if (this.hasIndicator !== !!this.$slots.indicator) {
        this.hasIndicator = !!this.$slots.indicator;
      }
      if (this.hasDetails !== !!this.$slots.details) {
        this.hasDetails = !!this.$slots.details;
      }
    }
  }
};
const _hoisted_1$w = ["id", "aria-label", "href", "target", "rel", "onClick"];
const _hoisted_2$u = { class: "list-item-content" };
const _hoisted_3$r = { class: "list-item-content__main" };
const _hoisted_4$r = { class: "list-item-content__name" };
const _hoisted_5$3 = { class: "list-item-content__details" };
const _hoisted_6$2 = {
  key: 0,
  class: "list-item-details__details"
};
const _hoisted_7$2 = {
  key: 1,
  class: "list-item-details__extra"
};
const _hoisted_8$1 = {
  key: 1,
  class: "list-item-details__indicator"
};
const _hoisted_9$1 = {
  key: 0,
  class: "list-item-content__extra-actions"
};
const _hoisted_10$1 = {
  key: 2,
  class: "list-item__extra"
};
function _sfc_render$x(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcCounterBubble = resolveComponent("NcCounterBubble");
  const _component_NcActions = resolveComponent("NcActions");
  return openBlock(), createBlock(resolveDynamicComponent($props.to ? "router-link" : "NcVNodes"), normalizeProps(guardReactiveProps({ ...$props.to && { custom: true, to: $props.to } })), {
    default: withCtx(({ href: routerLinkHref, navigate, isActive }) => [
      createBaseVNode("li", mergeProps({
        class: ["list-item__wrapper", {
          "list-item__wrapper--active": $props.active ?? isActive,
          "list-item__wrapper--legacy": $setup.isLegacy34
        }]
      }, _ctx.$attrs), [
        createBaseVNode("div", {
          ref: "list-item",
          class: normalizeClass(["list-item", {
            "list-item--compact": $props.compact,
            "list-item--one-line": $props.oneLine
          }]),
          onMouseover: _cache[5] || (_cache[5] = (...args) => $options.handleMouseover && $options.handleMouseover(...args)),
          onMouseleave: _cache[6] || (_cache[6] = (...args) => $options.handleMouseleave && $options.handleMouseleave(...args))
        }, [
          createBaseVNode("a", {
            id: $props.anchorId || void 0,
            "aria-label": $props.linkAriaLabel,
            class: "list-item__anchor",
            href: routerLinkHref || $props.href,
            target: $props.target || ($props.href === "#" ? void 0 : "_blank"),
            rel: $props.href === "#" ? void 0 : "noopener noreferrer",
            onFocus: _cache[0] || (_cache[0] = (...args) => $options.showActions && $options.showActions(...args)),
            onFocusout: _cache[1] || (_cache[1] = (...args) => $options.handleBlur && $options.handleBlur(...args)),
            onClick: ($event) => $options.onClick($event, navigate, routerLinkHref),
            onDragstart: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("dragstart", $event)),
            onKeydown: _cache[3] || (_cache[3] = withKeys((...args) => $options.hideActions && $options.hideActions(...args), ["esc"]))
          }, [
            renderSlot(_ctx.$slots, "icon", {}, void 0, true),
            createBaseVNode("div", _hoisted_2$u, [
              createBaseVNode("div", _hoisted_3$r, [
                createBaseVNode("div", _hoisted_4$r, [
                  renderSlot(_ctx.$slots, "name", {}, () => [
                    createTextVNode(toDisplayString($props.name), 1)
                  ], true)
                ]),
                $data.hasSubname ? (openBlock(), createElementBlock("div", {
                  key: 0,
                  class: normalizeClass(["list-item-content__subname", { "list-item-content__subname--bold": $props.bold }])
                }, [
                  renderSlot(_ctx.$slots, "subname", {}, void 0, true)
                ], 2)) : createCommentVNode("", true)
              ]),
              createBaseVNode("div", _hoisted_5$3, [
                $options.showDetails ? (openBlock(), createElementBlock("div", _hoisted_6$2, [
                  renderSlot(_ctx.$slots, "details", {}, () => [
                    createTextVNode(toDisplayString($props.details), 1)
                  ], true)
                ])) : createCommentVNode("", true),
                $props.counterNumber !== 0 || $data.hasIndicator ? withDirectives((openBlock(), createElementBlock("div", _hoisted_7$2, [
                  $props.counterNumber !== 0 ? (openBlock(), createBlock(_component_NcCounterBubble, {
                    key: 0,
                    count: $props.counterNumber,
                    active: $setup.isLegacy34 ? $props.active ?? isActive : false,
                    class: "list-item-details__counter",
                    type: $props.counterType
                  }, null, 8, ["count", "active", "type"])) : createCommentVNode("", true),
                  $data.hasIndicator ? (openBlock(), createElementBlock("span", _hoisted_8$1, [
                    renderSlot(_ctx.$slots, "indicator", {}, void 0, true)
                  ])) : createCommentVNode("", true)
                ], 512)), [
                  [vShow, $options.showAdditionalElements]
                ]) : createCommentVNode("", true)
              ])
            ])
          ], 40, _hoisted_1$w),
          _ctx.$slots["extra-actions"] ? (openBlock(), createElementBlock("div", _hoisted_9$1, [
            renderSlot(_ctx.$slots, "extra-actions", {}, void 0, true)
          ])) : createCommentVNode("", true),
          $props.forceDisplayActions || $data.displayActionsOnHoverFocus ? (openBlock(), createElementBlock("div", {
            key: 1,
            class: "list-item-content__actions",
            onFocusout: _cache[4] || (_cache[4] = (...args) => $options.handleBlur && $options.handleBlur(...args))
          }, [
            createVNode(_component_NcActions, {
              ref: "actions",
              primary: $setup.isLegacy34 ? $props.active ?? isActive : false,
              forceMenu: $props.forceMenu,
              "aria-label": $props.actionsAriaLabel,
              "onUpdate:open": $options.handleActionsUpdateOpen
            }, createSlots({
              default: withCtx(() => [
                renderSlot(_ctx.$slots, "actions", {}, void 0, true)
              ]),
              _: 2
            }, [
              _ctx.$slots["actions-icon"] ? {
                name: "icon",
                fn: withCtx(() => [
                  renderSlot(_ctx.$slots, "actions-icon", {}, void 0, true)
                ]),
                key: "0"
              } : void 0
            ]), 1032, ["primary", "forceMenu", "aria-label", "onUpdate:open"])
          ], 32)) : createCommentVNode("", true),
          _ctx.$slots.extra ? (openBlock(), createElementBlock("div", _hoisted_10$1, [
            renderSlot(_ctx.$slots, "extra", {}, void 0, true)
          ])) : createCommentVNode("", true)
        ], 34)
      ], 16)
    ]),
    _: 3
  }, 16);
}
const NcListItem = /* @__PURE__ */ _export_sfc(_sfc_main$A, [["render", _sfc_render$x], ["__scopeId", "data-v-7e90555e"]]);
Number.parseInt(window.getComputedStyle(document.body).getPropertyValue("--default-grid-baseline"));
Number.parseInt(window.getComputedStyle(document.body).getPropertyValue("--default-clickable-area"));
Number.parseInt(window.getComputedStyle(document.body).getPropertyValue("--clickable-area-small"));
register(t38);
register(t42);
getCapabilities()?.circles?.teamResourceProviders ?? [];
register(t9);
({
  /* eslint vue/require-prop-comment: warn -- TODO: Add a proper doc block about what this props do */
  props: {
    /**
     * Make the header name dynamic
     */
    header: {
      default: t$1("Related resources")
    },
    description: {
      default: t$1("Anything shared with the same group of people will show up here")
    }
  }
});
if (!Array.prototype.find) {
  Array.prototype.find = function(predicate) {
    if (this === null) {
      throw new TypeError("Array.prototype.find called on null or undefined");
    }
    if (typeof predicate !== "function") {
      throw new TypeError("predicate must be a function");
    }
    var list = Object(this);
    var length = list.length >>> 0;
    var thisArg = arguments[1];
    var value;
    for (var i = 0; i < length; i++) {
      value = list[i];
      if (predicate.call(thisArg, value, i, list)) {
        return value;
      }
    }
    return void 0;
  };
}
if (window && typeof window.CustomEvent !== "function") {
  let CustomEvent$1 = function(event, params) {
    params = params || {
      bubbles: false,
      cancelable: false,
      detail: void 0
    };
    var evt = document.createEvent("CustomEvent");
    evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
    return evt;
  };
  if (typeof window.Event !== "undefined") {
    CustomEvent$1.prototype = window.Event.prototype;
  }
  window.CustomEvent = CustomEvent$1;
}
window._vue_richtext_widgets ??= {};
window._registerWidget ??= (id, callback, onDestroy, props) => {
  registerWidget(id, callback, onDestroy, props);
};
function registerWidget(id, callback, onDestroy = () => {
}, props) {
  const propsWithDefaults = {
    hasInteractiveView: true,
    fullWidth: false,
    ...props
  };
  if (window._vue_richtext_widgets[id]) {
    logger$1.error(`[ReferencePicker]: Widget for id ${id} already registered`);
    return;
  }
  window._vue_richtext_widgets[id] = {
    id,
    callback,
    onDestroy,
    ...propsWithDefaults
  };
}
window._vue_richtext_custom_picker_elements ??= {};
window._registerCustomPickerElement ??= registerCustomPickerElement;
function registerCustomPickerElement(id, callback, onDestroy = () => {
}, size = "large") {
  if (window._vue_richtext_custom_picker_elements[id]) {
    logger$1.error(`Custom reference picker element for id ${id} already registered`);
    return;
  }
  window._vue_richtext_custom_picker_elements[id] = {
    id,
    callback,
    onDestroy,
    size
  };
}
register(t8);
({
  title: t$1("Any link"),
  icon_url: imagePath("core", "filetypes/link.svg")
});
window._vue_richtext_reference_providers ??= loadState("core", "reference-provider-list", []);
window._vue_richtext_reference_provider_timestamps ??= loadState("core", "reference-provider-timestamps", {});
register(t41, t46);
register(t24);
register(t25);
register(t32, t41, t43);
register(t12, t19);
const asciiAlpha = regexCheck(/[A-Za-z]/);
const asciiAlphanumeric = regexCheck(/[\dA-Za-z]/);
function asciiControl(code2) {
  return (
    // Special whitespace codes (which have negative values), C0 and Control
    // character DEL
    code2 !== null && (code2 < 32 || code2 === 127)
  );
}
function markdownLineEndingOrSpace(code2) {
  return code2 !== null && (code2 < 0 || code2 === 32);
}
const unicodePunctuation = regexCheck(new RegExp("\\p{P}|\\p{S}", "u"));
const unicodeWhitespace = regexCheck(/\s/);
function regexCheck(regex) {
  return check;
  function check(code2) {
    return code2 !== null && code2 > -1 && regex.test(String.fromCharCode(code2));
  }
}
const convert = (
  // Note: overloads in JSDoc can’t yet use different `@template`s.
  /**
   * @type {(
   *   (<Condition extends string>(test: Condition) => (node: unknown, index?: number | null | undefined, parent?: Parent | null | undefined, context?: unknown) => node is Node & {type: Condition}) &
   *   (<Condition extends Props>(test: Condition) => (node: unknown, index?: number | null | undefined, parent?: Parent | null | undefined, context?: unknown) => node is Node & Condition) &
   *   (<Condition extends TestFunction>(test: Condition) => (node: unknown, index?: number | null | undefined, parent?: Parent | null | undefined, context?: unknown) => node is Node & Predicate<Condition, Node>) &
   *   ((test?: null | undefined) => (node?: unknown, index?: number | null | undefined, parent?: Parent | null | undefined, context?: unknown) => node is Node) &
   *   ((test?: Test) => Check)
   * )}
   */
  /**
   * @param {Test} [test]
   * @returns {Check}
   */
  (function(test) {
    if (test === null || test === void 0) {
      return ok;
    }
    if (typeof test === "function") {
      return castFactory(test);
    }
    if (typeof test === "object") {
      return Array.isArray(test) ? anyFactory(test) : (
        // Cast because `ReadonlyArray` goes into the above but `isArray`
        // narrows to `Array`.
        propertiesFactory(
          /** @type {Props} */
          test
        )
      );
    }
    if (typeof test === "string") {
      return typeFactory(test);
    }
    throw new Error("Expected function, string, or object as test");
  })
);
function anyFactory(tests) {
  const checks = [];
  let index = -1;
  while (++index < tests.length) {
    checks[index] = convert(tests[index]);
  }
  return castFactory(any);
  function any(...parameters) {
    let index2 = -1;
    while (++index2 < checks.length) {
      if (checks[index2].apply(this, parameters)) return true;
    }
    return false;
  }
}
function propertiesFactory(check) {
  const checkAsRecord = (
    /** @type {Record<string, unknown>} */
    check
  );
  return castFactory(all);
  function all(node) {
    const nodeAsRecord = (
      /** @type {Record<string, unknown>} */
      /** @type {unknown} */
      node
    );
    let key;
    for (key in check) {
      if (nodeAsRecord[key] !== checkAsRecord[key]) return false;
    }
    return true;
  }
}
function typeFactory(check) {
  return castFactory(type);
  function type(node) {
    return node && node.type === check;
  }
}
function castFactory(testFunction) {
  return check;
  function check(value, index, parent) {
    return Boolean(
      looksLikeANode(value) && testFunction.call(
        this,
        value,
        typeof index === "number" ? index : void 0,
        parent || void 0
      )
    );
  }
}
function ok() {
  return true;
}
function looksLikeANode(value) {
  return value !== null && typeof value === "object" && "type" in value;
}
/** @type {(node?: unknown) => node is Exclude<PhrasingContent, Html>} */
convert([
  "break",
  "delete",
  "emphasis",
  // To do: next major: removed since footnotes were added to GFM.
  "footnote",
  "footnoteReference",
  "image",
  "imageReference",
  "inlineCode",
  // Enabled by `mdast-util-math`:
  "inlineMath",
  "link",
  "linkReference",
  // Enabled by `mdast-util-mdx`:
  "mdxJsxTextElement",
  // Enabled by `mdast-util-mdx`:
  "mdxTextExpression",
  "strong",
  "text",
  // Enabled by `mdast-util-directive`:
  "textDirective"
]);
const wwwPrefix = {
  tokenize: tokenizeWwwPrefix,
  partial: true
};
const domain = {
  tokenize: tokenizeDomain,
  partial: true
};
const path = {
  tokenize: tokenizePath,
  partial: true
};
const trail = {
  tokenize: tokenizeTrail,
  partial: true
};
const emailDomainDotTrail = {
  tokenize: tokenizeEmailDomainDotTrail,
  partial: true
};
const wwwAutolink = {
  name: "wwwAutolink",
  tokenize: tokenizeWwwAutolink,
  previous: previousWww
};
const protocolAutolink = {
  name: "protocolAutolink",
  tokenize: tokenizeProtocolAutolink,
  previous: previousProtocol
};
const emailAutolink = {
  name: "emailAutolink",
  tokenize: tokenizeEmailAutolink,
  previous: previousEmail
};
const text = {};
let code = 48;
while (code < 123) {
  text[code] = emailAutolink;
  code++;
  if (code === 58) code = 65;
  else if (code === 91) code = 97;
}
text[43] = emailAutolink;
text[45] = emailAutolink;
text[46] = emailAutolink;
text[95] = emailAutolink;
text[72] = [emailAutolink, protocolAutolink];
text[104] = [emailAutolink, protocolAutolink];
text[87] = [emailAutolink, wwwAutolink];
text[119] = [emailAutolink, wwwAutolink];
function tokenizeEmailAutolink(effects, ok2, nok) {
  const self2 = this;
  let dot;
  let data;
  return start;
  function start(code2) {
    if (!gfmAtext(code2) || !previousEmail.call(self2, self2.previous) || previousUnbalanced(self2.events)) {
      return nok(code2);
    }
    effects.enter("literalAutolink");
    effects.enter("literalAutolinkEmail");
    return atext(code2);
  }
  function atext(code2) {
    if (gfmAtext(code2)) {
      effects.consume(code2);
      return atext;
    }
    if (code2 === 64) {
      effects.consume(code2);
      return emailDomain;
    }
    return nok(code2);
  }
  function emailDomain(code2) {
    if (code2 === 46) {
      return effects.check(emailDomainDotTrail, emailDomainAfter, emailDomainDot)(code2);
    }
    if (code2 === 45 || code2 === 95 || asciiAlphanumeric(code2)) {
      data = true;
      effects.consume(code2);
      return emailDomain;
    }
    return emailDomainAfter(code2);
  }
  function emailDomainDot(code2) {
    effects.consume(code2);
    dot = true;
    return emailDomain;
  }
  function emailDomainAfter(code2) {
    if (data && dot && asciiAlpha(self2.previous)) {
      effects.exit("literalAutolinkEmail");
      effects.exit("literalAutolink");
      return ok2(code2);
    }
    return nok(code2);
  }
}
function tokenizeWwwAutolink(effects, ok2, nok) {
  const self2 = this;
  return wwwStart;
  function wwwStart(code2) {
    if (code2 !== 87 && code2 !== 119 || !previousWww.call(self2, self2.previous) || previousUnbalanced(self2.events)) {
      return nok(code2);
    }
    effects.enter("literalAutolink");
    effects.enter("literalAutolinkWww");
    return effects.check(wwwPrefix, effects.attempt(domain, effects.attempt(path, wwwAfter), nok), nok)(code2);
  }
  function wwwAfter(code2) {
    effects.exit("literalAutolinkWww");
    effects.exit("literalAutolink");
    return ok2(code2);
  }
}
function tokenizeProtocolAutolink(effects, ok2, nok) {
  const self2 = this;
  let buffer = "";
  let seen2 = false;
  return protocolStart;
  function protocolStart(code2) {
    if ((code2 === 72 || code2 === 104) && previousProtocol.call(self2, self2.previous) && !previousUnbalanced(self2.events)) {
      effects.enter("literalAutolink");
      effects.enter("literalAutolinkHttp");
      buffer += String.fromCodePoint(code2);
      effects.consume(code2);
      return protocolPrefixInside;
    }
    return nok(code2);
  }
  function protocolPrefixInside(code2) {
    if (asciiAlpha(code2) && buffer.length < 5) {
      buffer += String.fromCodePoint(code2);
      effects.consume(code2);
      return protocolPrefixInside;
    }
    if (code2 === 58) {
      const protocol = buffer.toLowerCase();
      if (protocol === "http" || protocol === "https") {
        effects.consume(code2);
        return protocolSlashesInside;
      }
    }
    return nok(code2);
  }
  function protocolSlashesInside(code2) {
    if (code2 === 47) {
      effects.consume(code2);
      if (seen2) {
        return afterProtocol;
      }
      seen2 = true;
      return protocolSlashesInside;
    }
    return nok(code2);
  }
  function afterProtocol(code2) {
    return code2 === null || asciiControl(code2) || markdownLineEndingOrSpace(code2) || unicodeWhitespace(code2) || unicodePunctuation(code2) ? nok(code2) : effects.attempt(domain, effects.attempt(path, protocolAfter), nok)(code2);
  }
  function protocolAfter(code2) {
    effects.exit("literalAutolinkHttp");
    effects.exit("literalAutolink");
    return ok2(code2);
  }
}
function tokenizeWwwPrefix(effects, ok2, nok) {
  let size = 0;
  return wwwPrefixInside;
  function wwwPrefixInside(code2) {
    if ((code2 === 87 || code2 === 119) && size < 3) {
      size++;
      effects.consume(code2);
      return wwwPrefixInside;
    }
    if (code2 === 46 && size === 3) {
      effects.consume(code2);
      return wwwPrefixAfter;
    }
    return nok(code2);
  }
  function wwwPrefixAfter(code2) {
    return code2 === null ? nok(code2) : ok2(code2);
  }
}
function tokenizeDomain(effects, ok2, nok) {
  let underscoreInLastSegment;
  let underscoreInLastLastSegment;
  let seen2;
  return domainInside;
  function domainInside(code2) {
    if (code2 === 46 || code2 === 95) {
      return effects.check(trail, domainAfter, domainAtPunctuation)(code2);
    }
    if (code2 === null || markdownLineEndingOrSpace(code2) || unicodeWhitespace(code2) || code2 !== 45 && unicodePunctuation(code2)) {
      return domainAfter(code2);
    }
    seen2 = true;
    effects.consume(code2);
    return domainInside;
  }
  function domainAtPunctuation(code2) {
    if (code2 === 95) {
      underscoreInLastSegment = true;
    } else {
      underscoreInLastLastSegment = underscoreInLastSegment;
      underscoreInLastSegment = void 0;
    }
    effects.consume(code2);
    return domainInside;
  }
  function domainAfter(code2) {
    if (underscoreInLastLastSegment || underscoreInLastSegment || !seen2) {
      return nok(code2);
    }
    return ok2(code2);
  }
}
function tokenizePath(effects, ok2) {
  let sizeOpen = 0;
  let sizeClose = 0;
  return pathInside;
  function pathInside(code2) {
    if (code2 === 40) {
      sizeOpen++;
      effects.consume(code2);
      return pathInside;
    }
    if (code2 === 41 && sizeClose < sizeOpen) {
      return pathAtPunctuation(code2);
    }
    if (code2 === 33 || code2 === 34 || code2 === 38 || code2 === 39 || code2 === 41 || code2 === 42 || code2 === 44 || code2 === 46 || code2 === 58 || code2 === 59 || code2 === 60 || code2 === 63 || code2 === 93 || code2 === 95 || code2 === 126) {
      return effects.check(trail, ok2, pathAtPunctuation)(code2);
    }
    if (code2 === null || markdownLineEndingOrSpace(code2) || unicodeWhitespace(code2)) {
      return ok2(code2);
    }
    effects.consume(code2);
    return pathInside;
  }
  function pathAtPunctuation(code2) {
    if (code2 === 41) {
      sizeClose++;
    }
    effects.consume(code2);
    return pathInside;
  }
}
function tokenizeTrail(effects, ok2, nok) {
  return trail2;
  function trail2(code2) {
    if (code2 === 33 || code2 === 34 || code2 === 39 || code2 === 41 || code2 === 42 || code2 === 44 || code2 === 46 || code2 === 58 || code2 === 59 || code2 === 63 || code2 === 95 || code2 === 126) {
      effects.consume(code2);
      return trail2;
    }
    if (code2 === 38) {
      effects.consume(code2);
      return trailCharacterReferenceStart;
    }
    if (code2 === 93) {
      effects.consume(code2);
      return trailBracketAfter;
    }
    if (
      // `<` is an end.
      code2 === 60 || // So is whitespace.
      code2 === null || markdownLineEndingOrSpace(code2) || unicodeWhitespace(code2)
    ) {
      return ok2(code2);
    }
    return nok(code2);
  }
  function trailBracketAfter(code2) {
    if (code2 === null || code2 === 40 || code2 === 91 || markdownLineEndingOrSpace(code2) || unicodeWhitespace(code2)) {
      return ok2(code2);
    }
    return trail2(code2);
  }
  function trailCharacterReferenceStart(code2) {
    return asciiAlpha(code2) ? trailCharacterReferenceInside(code2) : nok(code2);
  }
  function trailCharacterReferenceInside(code2) {
    if (code2 === 59) {
      effects.consume(code2);
      return trail2;
    }
    if (asciiAlpha(code2)) {
      effects.consume(code2);
      return trailCharacterReferenceInside;
    }
    return nok(code2);
  }
}
function tokenizeEmailDomainDotTrail(effects, ok2, nok) {
  return start;
  function start(code2) {
    effects.consume(code2);
    return after;
  }
  function after(code2) {
    return asciiAlphanumeric(code2) ? nok(code2) : ok2(code2);
  }
}
function previousWww(code2) {
  return code2 === null || code2 === 40 || code2 === 42 || code2 === 95 || code2 === 91 || code2 === 93 || code2 === 126 || markdownLineEndingOrSpace(code2);
}
function previousProtocol(code2) {
  return !asciiAlpha(code2);
}
function previousEmail(code2) {
  return !(code2 === 47 || gfmAtext(code2));
}
function gfmAtext(code2) {
  return code2 === 43 || code2 === 45 || code2 === 46 || code2 === 95 || asciiAlphanumeric(code2);
}
function previousUnbalanced(events) {
  let index = events.length;
  let result = false;
  while (index--) {
    const token = events[index][1];
    if ((token.type === "labelLink" || token.type === "labelImage") && !token._balanced) {
      result = true;
      break;
    }
    if (token._gfmAutolinkLiteralWalkedInto) {
      result = false;
      break;
    }
  }
  if (events.length > 0 && !result) {
    events[events.length - 1][1]._gfmAutolinkLiteralWalkedInto = true;
  }
  return result;
}
ref(null);
register(t34, t37);
({
  props: {
    /**
     * Placeholder to be shown if empty
     */
    placeholder: {
      default: t$1("Write a message …")
    }
  }
});
register(t0);
({
  props: {
    // Add NcSelect prop defaults and populate $props
    ...NcSelect.props,
    /**
     * Placeholder text
     *
     * @see https://vue-select.org/api/props.html#placeholder
     */
    placeholder: {
      default: t$1("Select a tag")
    }
  }
});
register(t50);
({
  methods: {
    /**
     * Debounce the group search (reduce API calls)
     */
    onSearch: debounce(function(query) {
      this.loadGroup(query);
    }, 200)
  }
});
const _sfc_main$1$1 = {};
function _sfc_render$w(_ctx, _cache) {
  return openBlock(), createElementBlock("div", null, [
    renderSlot(_ctx.$slots, "trigger")
  ]);
}
const NcUserBubbleDiv = /* @__PURE__ */ _export_sfc(_sfc_main$1$1, [["render", _sfc_render$w]]);
const _hoisted_1$v = { class: "user-bubble__name" };
const _hoisted_2$t = {
  key: 0,
  class: "user-bubble__secondary"
};
const _sfc_main$z = /* @__PURE__ */ defineComponent({
  __name: "NcUserBubble",
  props: /* @__PURE__ */ mergeModels({
    avatarImage: { default: void 0 },
    user: { default: void 0 },
    displayName: { default: void 0 },
    showUserStatus: { type: Boolean },
    url: { default: void 0 },
    to: { default: void 0 },
    primary: { type: Boolean },
    size: { default: 20 },
    margin: { default: 2 }
  }, {
    "open": { type: Boolean },
    "openModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["click"], ["update:open"]),
  setup(__props, { emit: __emit }) {
    const isOpen = useModel(__props, "open");
    const props = __props;
    const emit2 = __emit;
    const isAvatarUrl = computed(() => {
      if (!props.avatarImage) {
        return false;
      }
      try {
        const url = new URL(props.avatarImage);
        return !!url;
      } catch {
        return false;
      }
    });
    const isCustomAvatar = computed(() => !!props.avatarImage);
    const avatarStyle = computed(() => ({
      marginInlineStart: `${props.margin}px`
    }));
    const hasUrl = computed(() => {
      if (!props.url || props.url.trim() === "") {
        return false;
      }
      try {
        const url = new URL(props.url, props.url?.startsWith?.("/") ? window.location.href : void 0);
        return !!url;
      } catch {
        warn("[NcUserBubble] Invalid URL passed", { url: props.url });
        return false;
      }
    });
    const href = computed(() => hasUrl.value ? props.url : void 0);
    const contentComponent = computed(() => {
      if (hasUrl.value) {
        return "a";
      } else if (props.to) {
        return RouterLink$1;
      } else {
        return "div";
      }
    });
    const contentStyle = computed(() => ({
      height: `${props.size}px`,
      lineHeight: `${props.size}px`,
      borderRadius: `${props.size / 2}px`
    }));
    watch([() => props.displayName, () => props.user], () => {
      if (!props.displayName && !props.user) ;
    });
    return (_ctx, _cache) => {
      return openBlock(), createBlock(resolveDynamicComponent(!!_ctx.$slots.default ? NcPopover : NcUserBubbleDiv), {
        shown: isOpen.value,
        "onUpdate:shown": _cache[1] || (_cache[1] = ($event) => isOpen.value = $event),
        class: "user-bubble__wrapper",
        trigger: "hover focus"
      }, {
        trigger: withCtx(({ attrs }) => [
          (openBlock(), createBlock(resolveDynamicComponent(contentComponent.value), mergeProps({
            class: ["user-bubble__content", { "user-bubble__content--primary": __props.primary }],
            style: contentStyle.value,
            to: __props.to,
            href: href.value
          }, attrs, {
            onClick: _cache[0] || (_cache[0] = ($event) => emit2("click", $event))
          }), {
            default: withCtx(() => [
              createVNode(NcAvatar, {
                url: isCustomAvatar.value && isAvatarUrl.value ? __props.avatarImage : void 0,
                iconClass: isCustomAvatar.value && !isAvatarUrl.value ? __props.avatarImage : void 0,
                user: __props.user,
                displayName: __props.displayName,
                size: __props.size - __props.margin * 2,
                style: normalizeStyle(avatarStyle.value),
                disableTooltip: "",
                disableMenu: "",
                hideStatus: !__props.showUserStatus,
                class: "user-bubble__avatar"
              }, null, 8, ["url", "iconClass", "user", "displayName", "size", "style", "hideStatus"]),
              createBaseVNode("span", _hoisted_1$v, toDisplayString(__props.displayName || __props.user), 1),
              !!_ctx.$slots.name ? (openBlock(), createElementBlock("span", _hoisted_2$t, [
                renderSlot(_ctx.$slots, "name", {}, void 0, true)
              ])) : createCommentVNode("", true)
            ]),
            _: 3
          }, 16, ["class", "style", "to", "href"]))
        ]),
        default: withCtx(() => [
          renderSlot(_ctx.$slots, "default", {}, void 0, true)
        ]),
        _: 3
      }, 40, ["shown"]);
    };
  }
});
const NcUserBubble = /* @__PURE__ */ _export_sfc(_sfc_main$z, [["__scopeId", "data-v-9189d023"]]);
var VueCropper$1 = {};
var vue = { exports: {} };
var vue_cjs_prod = {};
/**
* @vue/compiler-core v3.5.22
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
const FRAGMENT = /* @__PURE__ */ Symbol(``);
const TELEPORT = /* @__PURE__ */ Symbol(``);
const SUSPENSE = /* @__PURE__ */ Symbol(``);
const KEEP_ALIVE = /* @__PURE__ */ Symbol(``);
const BASE_TRANSITION = /* @__PURE__ */ Symbol(
  ``
);
const OPEN_BLOCK = /* @__PURE__ */ Symbol(``);
const CREATE_BLOCK = /* @__PURE__ */ Symbol(``);
const CREATE_ELEMENT_BLOCK = /* @__PURE__ */ Symbol(
  ``
);
const CREATE_VNODE = /* @__PURE__ */ Symbol(``);
const CREATE_ELEMENT_VNODE = /* @__PURE__ */ Symbol(
  ``
);
const CREATE_COMMENT = /* @__PURE__ */ Symbol(
  ``
);
const CREATE_TEXT = /* @__PURE__ */ Symbol(
  ``
);
const CREATE_STATIC = /* @__PURE__ */ Symbol(
  ``
);
const RESOLVE_COMPONENT = /* @__PURE__ */ Symbol(
  ``
);
const RESOLVE_DYNAMIC_COMPONENT = /* @__PURE__ */ Symbol(
  ``
);
const RESOLVE_DIRECTIVE = /* @__PURE__ */ Symbol(
  ``
);
const RESOLVE_FILTER = /* @__PURE__ */ Symbol(
  ``
);
const WITH_DIRECTIVES = /* @__PURE__ */ Symbol(
  ``
);
const RENDER_LIST = /* @__PURE__ */ Symbol(``);
const RENDER_SLOT = /* @__PURE__ */ Symbol(``);
const CREATE_SLOTS = /* @__PURE__ */ Symbol(``);
const TO_DISPLAY_STRING = /* @__PURE__ */ Symbol(
  ``
);
const MERGE_PROPS = /* @__PURE__ */ Symbol(``);
const NORMALIZE_CLASS = /* @__PURE__ */ Symbol(
  ``
);
const NORMALIZE_STYLE = /* @__PURE__ */ Symbol(
  ``
);
const NORMALIZE_PROPS = /* @__PURE__ */ Symbol(
  ``
);
const GUARD_REACTIVE_PROPS = /* @__PURE__ */ Symbol(
  ``
);
const TO_HANDLERS = /* @__PURE__ */ Symbol(``);
const CAMELIZE = /* @__PURE__ */ Symbol(``);
const CAPITALIZE = /* @__PURE__ */ Symbol(``);
const TO_HANDLER_KEY = /* @__PURE__ */ Symbol(
  ``
);
const SET_BLOCK_TRACKING = /* @__PURE__ */ Symbol(
  ``
);
const PUSH_SCOPE_ID = /* @__PURE__ */ Symbol(``);
const POP_SCOPE_ID = /* @__PURE__ */ Symbol(``);
const WITH_CTX = /* @__PURE__ */ Symbol(``);
const UNREF = /* @__PURE__ */ Symbol(``);
const IS_REF = /* @__PURE__ */ Symbol(``);
const WITH_MEMO = /* @__PURE__ */ Symbol(``);
const IS_MEMO_SAME = /* @__PURE__ */ Symbol(``);
const helperNameMap = {
  [FRAGMENT]: `Fragment`,
  [TELEPORT]: `Teleport`,
  [SUSPENSE]: `Suspense`,
  [KEEP_ALIVE]: `KeepAlive`,
  [BASE_TRANSITION]: `BaseTransition`,
  [OPEN_BLOCK]: `openBlock`,
  [CREATE_BLOCK]: `createBlock`,
  [CREATE_ELEMENT_BLOCK]: `createElementBlock`,
  [CREATE_VNODE]: `createVNode`,
  [CREATE_ELEMENT_VNODE]: `createElementVNode`,
  [CREATE_COMMENT]: `createCommentVNode`,
  [CREATE_TEXT]: `createTextVNode`,
  [CREATE_STATIC]: `createStaticVNode`,
  [RESOLVE_COMPONENT]: `resolveComponent`,
  [RESOLVE_DYNAMIC_COMPONENT]: `resolveDynamicComponent`,
  [RESOLVE_DIRECTIVE]: `resolveDirective`,
  [RESOLVE_FILTER]: `resolveFilter`,
  [WITH_DIRECTIVES]: `withDirectives`,
  [RENDER_LIST]: `renderList`,
  [RENDER_SLOT]: `renderSlot`,
  [CREATE_SLOTS]: `createSlots`,
  [TO_DISPLAY_STRING]: `toDisplayString`,
  [MERGE_PROPS]: `mergeProps`,
  [NORMALIZE_CLASS]: `normalizeClass`,
  [NORMALIZE_STYLE]: `normalizeStyle`,
  [NORMALIZE_PROPS]: `normalizeProps`,
  [GUARD_REACTIVE_PROPS]: `guardReactiveProps`,
  [TO_HANDLERS]: `toHandlers`,
  [CAMELIZE]: `camelize`,
  [CAPITALIZE]: `capitalize`,
  [TO_HANDLER_KEY]: `toHandlerKey`,
  [SET_BLOCK_TRACKING]: `setBlockTracking`,
  [PUSH_SCOPE_ID]: `pushScopeId`,
  [POP_SCOPE_ID]: `popScopeId`,
  [WITH_CTX]: `withCtx`,
  [UNREF]: `unref`,
  [IS_REF]: `isRef`,
  [WITH_MEMO]: `withMemo`,
  [IS_MEMO_SAME]: `isMemoSame`
};
function registerRuntimeHelpers(helpers) {
  Object.getOwnPropertySymbols(helpers).forEach((s) => {
    helperNameMap[s] = helpers[s];
  });
}
const Namespaces = {
  "HTML": 0,
  "0": "HTML",
  "SVG": 1,
  "1": "SVG",
  "MATH_ML": 2,
  "2": "MATH_ML"
};
const NodeTypes = {
  "ROOT": 0,
  "0": "ROOT",
  "ELEMENT": 1,
  "1": "ELEMENT",
  "TEXT": 2,
  "2": "TEXT",
  "COMMENT": 3,
  "3": "COMMENT",
  "SIMPLE_EXPRESSION": 4,
  "4": "SIMPLE_EXPRESSION",
  "INTERPOLATION": 5,
  "5": "INTERPOLATION",
  "ATTRIBUTE": 6,
  "6": "ATTRIBUTE",
  "DIRECTIVE": 7,
  "7": "DIRECTIVE",
  "COMPOUND_EXPRESSION": 8,
  "8": "COMPOUND_EXPRESSION",
  "IF": 9,
  "9": "IF",
  "IF_BRANCH": 10,
  "10": "IF_BRANCH",
  "FOR": 11,
  "11": "FOR",
  "TEXT_CALL": 12,
  "12": "TEXT_CALL",
  "VNODE_CALL": 13,
  "13": "VNODE_CALL",
  "JS_CALL_EXPRESSION": 14,
  "14": "JS_CALL_EXPRESSION",
  "JS_OBJECT_EXPRESSION": 15,
  "15": "JS_OBJECT_EXPRESSION",
  "JS_PROPERTY": 16,
  "16": "JS_PROPERTY",
  "JS_ARRAY_EXPRESSION": 17,
  "17": "JS_ARRAY_EXPRESSION",
  "JS_FUNCTION_EXPRESSION": 18,
  "18": "JS_FUNCTION_EXPRESSION",
  "JS_CONDITIONAL_EXPRESSION": 19,
  "19": "JS_CONDITIONAL_EXPRESSION",
  "JS_CACHE_EXPRESSION": 20,
  "20": "JS_CACHE_EXPRESSION",
  "JS_BLOCK_STATEMENT": 21,
  "21": "JS_BLOCK_STATEMENT",
  "JS_TEMPLATE_LITERAL": 22,
  "22": "JS_TEMPLATE_LITERAL",
  "JS_IF_STATEMENT": 23,
  "23": "JS_IF_STATEMENT",
  "JS_ASSIGNMENT_EXPRESSION": 24,
  "24": "JS_ASSIGNMENT_EXPRESSION",
  "JS_SEQUENCE_EXPRESSION": 25,
  "25": "JS_SEQUENCE_EXPRESSION",
  "JS_RETURN_STATEMENT": 26,
  "26": "JS_RETURN_STATEMENT"
};
const ElementTypes = {
  "ELEMENT": 0,
  "0": "ELEMENT",
  "COMPONENT": 1,
  "1": "COMPONENT",
  "SLOT": 2,
  "2": "SLOT",
  "TEMPLATE": 3,
  "3": "TEMPLATE"
};
const ConstantTypes = {
  "NOT_CONSTANT": 0,
  "0": "NOT_CONSTANT",
  "CAN_SKIP_PATCH": 1,
  "1": "CAN_SKIP_PATCH",
  "CAN_CACHE": 2,
  "2": "CAN_CACHE",
  "CAN_STRINGIFY": 3,
  "3": "CAN_STRINGIFY"
};
const locStub = {
  start: { line: 1, column: 1, offset: 0 },
  end: { line: 1, column: 1, offset: 0 },
  source: ""
};
function createRoot(children, source = "") {
  return {
    type: 0,
    source,
    children,
    helpers: /* @__PURE__ */ new Set(),
    components: [],
    directives: [],
    hoists: [],
    imports: [],
    cached: [],
    temps: 0,
    codegenNode: void 0,
    loc: locStub
  };
}
function createVNodeCall(context, tag, props, children, patchFlag, dynamicProps, directives, isBlock = false, disableTracking = false, isComponent2 = false, loc = locStub) {
  if (context) {
    if (isBlock) {
      context.helper(OPEN_BLOCK);
      context.helper(getVNodeBlockHelper(context.inSSR, isComponent2));
    } else {
      context.helper(getVNodeHelper(context.inSSR, isComponent2));
    }
    if (directives) {
      context.helper(WITH_DIRECTIVES);
    }
  }
  return {
    type: 13,
    tag,
    props,
    children,
    patchFlag,
    dynamicProps,
    directives,
    isBlock,
    disableTracking,
    isComponent: isComponent2,
    loc
  };
}
function createArrayExpression(elements, loc = locStub) {
  return {
    type: 17,
    loc,
    elements
  };
}
function createObjectExpression(properties, loc = locStub) {
  return {
    type: 15,
    loc,
    properties
  };
}
function createObjectProperty(key, value) {
  return {
    type: 16,
    loc: locStub,
    key: isString(key) ? createSimpleExpression(key, true) : key,
    value
  };
}
function createSimpleExpression(content, isStatic = false, loc = locStub, constType = 0) {
  return {
    type: 4,
    loc,
    content,
    isStatic,
    constType: isStatic ? 3 : constType
  };
}
function createInterpolation(content, loc) {
  return {
    type: 5,
    loc,
    content: isString(content) ? createSimpleExpression(content, false, loc) : content
  };
}
function createCompoundExpression(children, loc = locStub) {
  return {
    type: 8,
    loc,
    children
  };
}
function createCallExpression(callee, args = [], loc = locStub) {
  return {
    type: 14,
    loc,
    callee,
    arguments: args
  };
}
function createFunctionExpression(params, returns = void 0, newline = false, isSlot = false, loc = locStub) {
  return {
    type: 18,
    params,
    returns,
    newline,
    isSlot,
    loc
  };
}
function createConditionalExpression(test, consequent, alternate, newline = true) {
  return {
    type: 19,
    test,
    consequent,
    alternate,
    newline,
    loc: locStub
  };
}
function createCacheExpression(index, value, needPauseTracking = false, inVOnce = false) {
  return {
    type: 20,
    index,
    value,
    needPauseTracking,
    inVOnce,
    needArraySpread: false,
    loc: locStub
  };
}
function createBlockStatement(body) {
  return {
    type: 21,
    body,
    loc: locStub
  };
}
function createTemplateLiteral(elements) {
  return {
    type: 22,
    elements,
    loc: locStub
  };
}
function createIfStatement(test, consequent, alternate) {
  return {
    type: 23,
    test,
    consequent,
    alternate,
    loc: locStub
  };
}
function createAssignmentExpression(left, right) {
  return {
    type: 24,
    left,
    right,
    loc: locStub
  };
}
function createSequenceExpression(expressions) {
  return {
    type: 25,
    expressions,
    loc: locStub
  };
}
function createReturnStatement(returns) {
  return {
    type: 26,
    returns,
    loc: locStub
  };
}
function getVNodeHelper(ssr, isComponent2) {
  return ssr || isComponent2 ? CREATE_VNODE : CREATE_ELEMENT_VNODE;
}
function getVNodeBlockHelper(ssr, isComponent2) {
  return ssr || isComponent2 ? CREATE_BLOCK : CREATE_ELEMENT_BLOCK;
}
function convertToBlock(node, { helper, removeHelper, inSSR }) {
  if (!node.isBlock) {
    node.isBlock = true;
    removeHelper(getVNodeHelper(inSSR, node.isComponent));
    helper(OPEN_BLOCK);
    helper(getVNodeBlockHelper(inSSR, node.isComponent));
  }
}
const defaultDelimitersOpen = new Uint8Array([123, 123]);
const defaultDelimitersClose = new Uint8Array([125, 125]);
function isTagStartChar(c) {
  return c >= 97 && c <= 122 || c >= 65 && c <= 90;
}
function isWhitespace(c) {
  return c === 32 || c === 10 || c === 9 || c === 12 || c === 13;
}
function isEndOfTagSection(c) {
  return c === 47 || c === 62 || isWhitespace(c);
}
function toCharCodes(str) {
  const ret = new Uint8Array(str.length);
  for (let i = 0; i < str.length; i++) {
    ret[i] = str.charCodeAt(i);
  }
  return ret;
}
const Sequences = {
  Cdata: new Uint8Array([67, 68, 65, 84, 65, 91]),
  // CDATA[
  CdataEnd: new Uint8Array([93, 93, 62]),
  // ]]>
  CommentEnd: new Uint8Array([45, 45, 62]),
  // `-->`
  ScriptEnd: new Uint8Array([60, 47, 115, 99, 114, 105, 112, 116]),
  // `<\/script`
  StyleEnd: new Uint8Array([60, 47, 115, 116, 121, 108, 101]),
  // `</style`
  TitleEnd: new Uint8Array([60, 47, 116, 105, 116, 108, 101]),
  // `</title`
  TextareaEnd: new Uint8Array([
    60,
    47,
    116,
    101,
    120,
    116,
    97,
    114,
    101,
    97
  ])
  // `</textarea
};
class Tokenizer {
  constructor(stack2, cbs) {
    this.stack = stack2;
    this.cbs = cbs;
    this.state = 1;
    this.buffer = "";
    this.sectionStart = 0;
    this.index = 0;
    this.entityStart = 0;
    this.baseState = 1;
    this.inRCDATA = false;
    this.inXML = false;
    this.inVPre = false;
    this.newlines = [];
    this.mode = 0;
    this.delimiterOpen = defaultDelimitersOpen;
    this.delimiterClose = defaultDelimitersClose;
    this.delimiterIndex = -1;
    this.currentSequence = void 0;
    this.sequenceIndex = 0;
  }
  get inSFCRoot() {
    return this.mode === 2 && this.stack.length === 0;
  }
  reset() {
    this.state = 1;
    this.mode = 0;
    this.buffer = "";
    this.sectionStart = 0;
    this.index = 0;
    this.baseState = 1;
    this.inRCDATA = false;
    this.currentSequence = void 0;
    this.newlines.length = 0;
    this.delimiterOpen = defaultDelimitersOpen;
    this.delimiterClose = defaultDelimitersClose;
  }
  /**
   * Generate Position object with line / column information using recorded
   * newline positions. We know the index is always going to be an already
   * processed index, so all the newlines up to this index should have been
   * recorded.
   */
  getPos(index) {
    let line = 1;
    let column = index + 1;
    for (let i = this.newlines.length - 1; i >= 0; i--) {
      const newlineIndex = this.newlines[i];
      if (index > newlineIndex) {
        line = i + 2;
        column = index - newlineIndex;
        break;
      }
    }
    return {
      column,
      line,
      offset: index
    };
  }
  peek() {
    return this.buffer.charCodeAt(this.index + 1);
  }
  stateText(c) {
    if (c === 60) {
      if (this.index > this.sectionStart) {
        this.cbs.ontext(this.sectionStart, this.index);
      }
      this.state = 5;
      this.sectionStart = this.index;
    } else if (!this.inVPre && c === this.delimiterOpen[0]) {
      this.state = 2;
      this.delimiterIndex = 0;
      this.stateInterpolationOpen(c);
    }
  }
  stateInterpolationOpen(c) {
    if (c === this.delimiterOpen[this.delimiterIndex]) {
      if (this.delimiterIndex === this.delimiterOpen.length - 1) {
        const start = this.index + 1 - this.delimiterOpen.length;
        if (start > this.sectionStart) {
          this.cbs.ontext(this.sectionStart, start);
        }
        this.state = 3;
        this.sectionStart = start;
      } else {
        this.delimiterIndex++;
      }
    } else if (this.inRCDATA) {
      this.state = 32;
      this.stateInRCDATA(c);
    } else {
      this.state = 1;
      this.stateText(c);
    }
  }
  stateInterpolation(c) {
    if (c === this.delimiterClose[0]) {
      this.state = 4;
      this.delimiterIndex = 0;
      this.stateInterpolationClose(c);
    }
  }
  stateInterpolationClose(c) {
    if (c === this.delimiterClose[this.delimiterIndex]) {
      if (this.delimiterIndex === this.delimiterClose.length - 1) {
        this.cbs.oninterpolation(this.sectionStart, this.index + 1);
        if (this.inRCDATA) {
          this.state = 32;
        } else {
          this.state = 1;
        }
        this.sectionStart = this.index + 1;
      } else {
        this.delimiterIndex++;
      }
    } else {
      this.state = 3;
      this.stateInterpolation(c);
    }
  }
  stateSpecialStartSequence(c) {
    const isEnd = this.sequenceIndex === this.currentSequence.length;
    const isMatch = isEnd ? (
      // If we are at the end of the sequence, make sure the tag name has ended
      isEndOfTagSection(c)
    ) : (
      // Otherwise, do a case-insensitive comparison
      (c | 32) === this.currentSequence[this.sequenceIndex]
    );
    if (!isMatch) {
      this.inRCDATA = false;
    } else if (!isEnd) {
      this.sequenceIndex++;
      return;
    }
    this.sequenceIndex = 0;
    this.state = 6;
    this.stateInTagName(c);
  }
  /** Look for an end tag. For <title> and <textarea>, also decode entities. */
  stateInRCDATA(c) {
    if (this.sequenceIndex === this.currentSequence.length) {
      if (c === 62 || isWhitespace(c)) {
        const endOfText = this.index - this.currentSequence.length;
        if (this.sectionStart < endOfText) {
          const actualIndex = this.index;
          this.index = endOfText;
          this.cbs.ontext(this.sectionStart, endOfText);
          this.index = actualIndex;
        }
        this.sectionStart = endOfText + 2;
        this.stateInClosingTagName(c);
        this.inRCDATA = false;
        return;
      }
      this.sequenceIndex = 0;
    }
    if ((c | 32) === this.currentSequence[this.sequenceIndex]) {
      this.sequenceIndex += 1;
    } else if (this.sequenceIndex === 0) {
      if (this.currentSequence === Sequences.TitleEnd || this.currentSequence === Sequences.TextareaEnd && !this.inSFCRoot) {
        if (!this.inVPre && c === this.delimiterOpen[0]) {
          this.state = 2;
          this.delimiterIndex = 0;
          this.stateInterpolationOpen(c);
        }
      } else if (this.fastForwardTo(60)) {
        this.sequenceIndex = 1;
      }
    } else {
      this.sequenceIndex = Number(c === 60);
    }
  }
  stateCDATASequence(c) {
    if (c === Sequences.Cdata[this.sequenceIndex]) {
      if (++this.sequenceIndex === Sequences.Cdata.length) {
        this.state = 28;
        this.currentSequence = Sequences.CdataEnd;
        this.sequenceIndex = 0;
        this.sectionStart = this.index + 1;
      }
    } else {
      this.sequenceIndex = 0;
      this.state = 23;
      this.stateInDeclaration(c);
    }
  }
  /**
   * When we wait for one specific character, we can speed things up
   * by skipping through the buffer until we find it.
   *
   * @returns Whether the character was found.
   */
  fastForwardTo(c) {
    while (++this.index < this.buffer.length) {
      const cc = this.buffer.charCodeAt(this.index);
      if (cc === 10) {
        this.newlines.push(this.index);
      }
      if (cc === c) {
        return true;
      }
    }
    this.index = this.buffer.length - 1;
    return false;
  }
  /**
   * Comments and CDATA end with `-->` and `]]>`.
   *
   * Their common qualities are:
   * - Their end sequences have a distinct character they start with.
   * - That character is then repeated, so we have to check multiple repeats.
   * - All characters but the start character of the sequence can be skipped.
   */
  stateInCommentLike(c) {
    if (c === this.currentSequence[this.sequenceIndex]) {
      if (++this.sequenceIndex === this.currentSequence.length) {
        if (this.currentSequence === Sequences.CdataEnd) {
          this.cbs.oncdata(this.sectionStart, this.index - 2);
        } else {
          this.cbs.oncomment(this.sectionStart, this.index - 2);
        }
        this.sequenceIndex = 0;
        this.sectionStart = this.index + 1;
        this.state = 1;
      }
    } else if (this.sequenceIndex === 0) {
      if (this.fastForwardTo(this.currentSequence[0])) {
        this.sequenceIndex = 1;
      }
    } else if (c !== this.currentSequence[this.sequenceIndex - 1]) {
      this.sequenceIndex = 0;
    }
  }
  startSpecial(sequence, offset) {
    this.enterRCDATA(sequence, offset);
    this.state = 31;
  }
  enterRCDATA(sequence, offset) {
    this.inRCDATA = true;
    this.currentSequence = sequence;
    this.sequenceIndex = offset;
  }
  stateBeforeTagName(c) {
    if (c === 33) {
      this.state = 22;
      this.sectionStart = this.index + 1;
    } else if (c === 63) {
      this.state = 24;
      this.sectionStart = this.index + 1;
    } else if (isTagStartChar(c)) {
      this.sectionStart = this.index;
      if (this.mode === 0) {
        this.state = 6;
      } else if (this.inSFCRoot) {
        this.state = 34;
      } else if (!this.inXML) {
        if (c === 116) {
          this.state = 30;
        } else {
          this.state = c === 115 ? 29 : 6;
        }
      } else {
        this.state = 6;
      }
    } else if (c === 47) {
      this.state = 8;
    } else {
      this.state = 1;
      this.stateText(c);
    }
  }
  stateInTagName(c) {
    if (isEndOfTagSection(c)) {
      this.handleTagName(c);
    }
  }
  stateInSFCRootTagName(c) {
    if (isEndOfTagSection(c)) {
      const tag = this.buffer.slice(this.sectionStart, this.index);
      if (tag !== "template") {
        this.enterRCDATA(toCharCodes(`</` + tag), 0);
      }
      this.handleTagName(c);
    }
  }
  handleTagName(c) {
    this.cbs.onopentagname(this.sectionStart, this.index);
    this.sectionStart = -1;
    this.state = 11;
    this.stateBeforeAttrName(c);
  }
  stateBeforeClosingTagName(c) {
    if (isWhitespace(c)) ;
    else if (c === 62) {
      this.state = 1;
      this.sectionStart = this.index + 1;
    } else {
      this.state = isTagStartChar(c) ? 9 : 27;
      this.sectionStart = this.index;
    }
  }
  stateInClosingTagName(c) {
    if (c === 62 || isWhitespace(c)) {
      this.cbs.onclosetag(this.sectionStart, this.index);
      this.sectionStart = -1;
      this.state = 10;
      this.stateAfterClosingTagName(c);
    }
  }
  stateAfterClosingTagName(c) {
    if (c === 62) {
      this.state = 1;
      this.sectionStart = this.index + 1;
    }
  }
  stateBeforeAttrName(c) {
    if (c === 62) {
      this.cbs.onopentagend(this.index);
      if (this.inRCDATA) {
        this.state = 32;
      } else {
        this.state = 1;
      }
      this.sectionStart = this.index + 1;
    } else if (c === 47) {
      this.state = 7;
    } else if (c === 60 && this.peek() === 47) {
      this.cbs.onopentagend(this.index);
      this.state = 5;
      this.sectionStart = this.index;
    } else if (!isWhitespace(c)) {
      this.handleAttrStart(c);
    }
  }
  handleAttrStart(c) {
    if (c === 118 && this.peek() === 45) {
      this.state = 13;
      this.sectionStart = this.index;
    } else if (c === 46 || c === 58 || c === 64 || c === 35) {
      this.cbs.ondirname(this.index, this.index + 1);
      this.state = 14;
      this.sectionStart = this.index + 1;
    } else {
      this.state = 12;
      this.sectionStart = this.index;
    }
  }
  stateInSelfClosingTag(c) {
    if (c === 62) {
      this.cbs.onselfclosingtag(this.index);
      this.state = 1;
      this.sectionStart = this.index + 1;
      this.inRCDATA = false;
    } else if (!isWhitespace(c)) {
      this.state = 11;
      this.stateBeforeAttrName(c);
    }
  }
  stateInAttrName(c) {
    if (c === 61 || isEndOfTagSection(c)) {
      this.cbs.onattribname(this.sectionStart, this.index);
      this.handleAttrNameEnd(c);
    }
  }
  stateInDirName(c) {
    if (c === 61 || isEndOfTagSection(c)) {
      this.cbs.ondirname(this.sectionStart, this.index);
      this.handleAttrNameEnd(c);
    } else if (c === 58) {
      this.cbs.ondirname(this.sectionStart, this.index);
      this.state = 14;
      this.sectionStart = this.index + 1;
    } else if (c === 46) {
      this.cbs.ondirname(this.sectionStart, this.index);
      this.state = 16;
      this.sectionStart = this.index + 1;
    }
  }
  stateInDirArg(c) {
    if (c === 61 || isEndOfTagSection(c)) {
      this.cbs.ondirarg(this.sectionStart, this.index);
      this.handleAttrNameEnd(c);
    } else if (c === 91) {
      this.state = 15;
    } else if (c === 46) {
      this.cbs.ondirarg(this.sectionStart, this.index);
      this.state = 16;
      this.sectionStart = this.index + 1;
    }
  }
  stateInDynamicDirArg(c) {
    if (c === 93) {
      this.state = 14;
    } else if (c === 61 || isEndOfTagSection(c)) {
      this.cbs.ondirarg(this.sectionStart, this.index + 1);
      this.handleAttrNameEnd(c);
    }
  }
  stateInDirModifier(c) {
    if (c === 61 || isEndOfTagSection(c)) {
      this.cbs.ondirmodifier(this.sectionStart, this.index);
      this.handleAttrNameEnd(c);
    } else if (c === 46) {
      this.cbs.ondirmodifier(this.sectionStart, this.index);
      this.sectionStart = this.index + 1;
    }
  }
  handleAttrNameEnd(c) {
    this.sectionStart = this.index;
    this.state = 17;
    this.cbs.onattribnameend(this.index);
    this.stateAfterAttrName(c);
  }
  stateAfterAttrName(c) {
    if (c === 61) {
      this.state = 18;
    } else if (c === 47 || c === 62) {
      this.cbs.onattribend(0, this.sectionStart);
      this.sectionStart = -1;
      this.state = 11;
      this.stateBeforeAttrName(c);
    } else if (!isWhitespace(c)) {
      this.cbs.onattribend(0, this.sectionStart);
      this.handleAttrStart(c);
    }
  }
  stateBeforeAttrValue(c) {
    if (c === 34) {
      this.state = 19;
      this.sectionStart = this.index + 1;
    } else if (c === 39) {
      this.state = 20;
      this.sectionStart = this.index + 1;
    } else if (!isWhitespace(c)) {
      this.sectionStart = this.index;
      this.state = 21;
      this.stateInAttrValueNoQuotes(c);
    }
  }
  handleInAttrValue(c, quote) {
    if (c === quote || this.fastForwardTo(quote)) {
      this.cbs.onattribdata(this.sectionStart, this.index);
      this.sectionStart = -1;
      this.cbs.onattribend(
        quote === 34 ? 3 : 2,
        this.index + 1
      );
      this.state = 11;
    }
  }
  stateInAttrValueDoubleQuotes(c) {
    this.handleInAttrValue(c, 34);
  }
  stateInAttrValueSingleQuotes(c) {
    this.handleInAttrValue(c, 39);
  }
  stateInAttrValueNoQuotes(c) {
    if (isWhitespace(c) || c === 62) {
      this.cbs.onattribdata(this.sectionStart, this.index);
      this.sectionStart = -1;
      this.cbs.onattribend(1, this.index);
      this.state = 11;
      this.stateBeforeAttrName(c);
    } else if (c === 39 || c === 60 || c === 61 || c === 96) {
      this.cbs.onerr(
        18,
        this.index
      );
    } else ;
  }
  stateBeforeDeclaration(c) {
    if (c === 91) {
      this.state = 26;
      this.sequenceIndex = 0;
    } else {
      this.state = c === 45 ? 25 : 23;
    }
  }
  stateInDeclaration(c) {
    if (c === 62 || this.fastForwardTo(62)) {
      this.state = 1;
      this.sectionStart = this.index + 1;
    }
  }
  stateInProcessingInstruction(c) {
    if (c === 62 || this.fastForwardTo(62)) {
      this.cbs.onprocessinginstruction(this.sectionStart, this.index);
      this.state = 1;
      this.sectionStart = this.index + 1;
    }
  }
  stateBeforeComment(c) {
    if (c === 45) {
      this.state = 28;
      this.currentSequence = Sequences.CommentEnd;
      this.sequenceIndex = 2;
      this.sectionStart = this.index + 1;
    } else {
      this.state = 23;
    }
  }
  stateInSpecialComment(c) {
    if (c === 62 || this.fastForwardTo(62)) {
      this.cbs.oncomment(this.sectionStart, this.index);
      this.state = 1;
      this.sectionStart = this.index + 1;
    }
  }
  stateBeforeSpecialS(c) {
    if (c === Sequences.ScriptEnd[3]) {
      this.startSpecial(Sequences.ScriptEnd, 4);
    } else if (c === Sequences.StyleEnd[3]) {
      this.startSpecial(Sequences.StyleEnd, 4);
    } else {
      this.state = 6;
      this.stateInTagName(c);
    }
  }
  stateBeforeSpecialT(c) {
    if (c === Sequences.TitleEnd[3]) {
      this.startSpecial(Sequences.TitleEnd, 4);
    } else if (c === Sequences.TextareaEnd[3]) {
      this.startSpecial(Sequences.TextareaEnd, 4);
    } else {
      this.state = 6;
      this.stateInTagName(c);
    }
  }
  startEntity() {
  }
  stateInEntity() {
  }
  /**
   * Iterates through the buffer, calling the function corresponding to the current state.
   *
   * States that are more likely to be hit are higher up, as a performance improvement.
   */
  parse(input) {
    this.buffer = input;
    while (this.index < this.buffer.length) {
      const c = this.buffer.charCodeAt(this.index);
      if (c === 10 && this.state !== 33) {
        this.newlines.push(this.index);
      }
      switch (this.state) {
        case 1: {
          this.stateText(c);
          break;
        }
        case 2: {
          this.stateInterpolationOpen(c);
          break;
        }
        case 3: {
          this.stateInterpolation(c);
          break;
        }
        case 4: {
          this.stateInterpolationClose(c);
          break;
        }
        case 31: {
          this.stateSpecialStartSequence(c);
          break;
        }
        case 32: {
          this.stateInRCDATA(c);
          break;
        }
        case 26: {
          this.stateCDATASequence(c);
          break;
        }
        case 19: {
          this.stateInAttrValueDoubleQuotes(c);
          break;
        }
        case 12: {
          this.stateInAttrName(c);
          break;
        }
        case 13: {
          this.stateInDirName(c);
          break;
        }
        case 14: {
          this.stateInDirArg(c);
          break;
        }
        case 15: {
          this.stateInDynamicDirArg(c);
          break;
        }
        case 16: {
          this.stateInDirModifier(c);
          break;
        }
        case 28: {
          this.stateInCommentLike(c);
          break;
        }
        case 27: {
          this.stateInSpecialComment(c);
          break;
        }
        case 11: {
          this.stateBeforeAttrName(c);
          break;
        }
        case 6: {
          this.stateInTagName(c);
          break;
        }
        case 34: {
          this.stateInSFCRootTagName(c);
          break;
        }
        case 9: {
          this.stateInClosingTagName(c);
          break;
        }
        case 5: {
          this.stateBeforeTagName(c);
          break;
        }
        case 17: {
          this.stateAfterAttrName(c);
          break;
        }
        case 20: {
          this.stateInAttrValueSingleQuotes(c);
          break;
        }
        case 18: {
          this.stateBeforeAttrValue(c);
          break;
        }
        case 8: {
          this.stateBeforeClosingTagName(c);
          break;
        }
        case 10: {
          this.stateAfterClosingTagName(c);
          break;
        }
        case 29: {
          this.stateBeforeSpecialS(c);
          break;
        }
        case 30: {
          this.stateBeforeSpecialT(c);
          break;
        }
        case 21: {
          this.stateInAttrValueNoQuotes(c);
          break;
        }
        case 7: {
          this.stateInSelfClosingTag(c);
          break;
        }
        case 23: {
          this.stateInDeclaration(c);
          break;
        }
        case 22: {
          this.stateBeforeDeclaration(c);
          break;
        }
        case 25: {
          this.stateBeforeComment(c);
          break;
        }
        case 24: {
          this.stateInProcessingInstruction(c);
          break;
        }
        case 33: {
          this.stateInEntity();
          break;
        }
      }
      this.index++;
    }
    this.cleanup();
    this.finish();
  }
  /**
   * Remove data that has already been consumed from the buffer.
   */
  cleanup() {
    if (this.sectionStart !== this.index) {
      if (this.state === 1 || this.state === 32 && this.sequenceIndex === 0) {
        this.cbs.ontext(this.sectionStart, this.index);
        this.sectionStart = this.index;
      } else if (this.state === 19 || this.state === 20 || this.state === 21) {
        this.cbs.onattribdata(this.sectionStart, this.index);
        this.sectionStart = this.index;
      }
    }
  }
  finish() {
    this.handleTrailingData();
    this.cbs.onend();
  }
  /** Handle any trailing data. */
  handleTrailingData() {
    const endIndex = this.buffer.length;
    if (this.sectionStart >= endIndex) {
      return;
    }
    if (this.state === 28) {
      if (this.currentSequence === Sequences.CdataEnd) {
        this.cbs.oncdata(this.sectionStart, endIndex);
      } else {
        this.cbs.oncomment(this.sectionStart, endIndex);
      }
    } else if (this.state === 6 || this.state === 11 || this.state === 18 || this.state === 17 || this.state === 12 || this.state === 13 || this.state === 14 || this.state === 15 || this.state === 16 || this.state === 20 || this.state === 19 || this.state === 21 || this.state === 9) ;
    else {
      this.cbs.ontext(this.sectionStart, endIndex);
    }
  }
  emitCodePoint(cp, consumed) {
  }
}
const CompilerDeprecationTypes = {
  "COMPILER_IS_ON_ELEMENT": "COMPILER_IS_ON_ELEMENT",
  "COMPILER_V_BIND_SYNC": "COMPILER_V_BIND_SYNC",
  "COMPILER_V_BIND_OBJECT_ORDER": "COMPILER_V_BIND_OBJECT_ORDER",
  "COMPILER_V_ON_NATIVE": "COMPILER_V_ON_NATIVE",
  "COMPILER_V_IF_V_FOR_PRECEDENCE": "COMPILER_V_IF_V_FOR_PRECEDENCE",
  "COMPILER_NATIVE_TEMPLATE": "COMPILER_NATIVE_TEMPLATE",
  "COMPILER_INLINE_TEMPLATE": "COMPILER_INLINE_TEMPLATE",
  "COMPILER_FILTERS": "COMPILER_FILTERS"
};
const deprecationData = {
  ["COMPILER_IS_ON_ELEMENT"]: {
    message: `Platform-native elements with "is" prop will no longer be treated as components in Vue 3 unless the "is" value is explicitly prefixed with "vue:".`,
    link: `https://v3-migration.vuejs.org/breaking-changes/custom-elements-interop.html`
  },
  ["COMPILER_V_BIND_SYNC"]: {
    message: (key) => `.sync modifier for v-bind has been removed. Use v-model with argument instead. \`v-bind:${key}.sync\` should be changed to \`v-model:${key}\`.`,
    link: `https://v3-migration.vuejs.org/breaking-changes/v-model.html`
  },
  ["COMPILER_V_BIND_OBJECT_ORDER"]: {
    message: `v-bind="obj" usage is now order sensitive and behaves like JavaScript object spread: it will now overwrite an existing non-mergeable attribute that appears before v-bind in the case of conflict. To retain 2.x behavior, move v-bind to make it the first attribute. You can also suppress this warning if the usage is intended.`,
    link: `https://v3-migration.vuejs.org/breaking-changes/v-bind.html`
  },
  ["COMPILER_V_ON_NATIVE"]: {
    message: `.native modifier for v-on has been removed as is no longer necessary.`,
    link: `https://v3-migration.vuejs.org/breaking-changes/v-on-native-modifier-removed.html`
  },
  ["COMPILER_V_IF_V_FOR_PRECEDENCE"]: {
    message: `v-if / v-for precedence when used on the same element has changed in Vue 3: v-if now takes higher precedence and will no longer have access to v-for scope variables. It is best to avoid the ambiguity with <template> tags or use a computed property that filters v-for data source.`,
    link: `https://v3-migration.vuejs.org/breaking-changes/v-if-v-for.html`
  },
  ["COMPILER_NATIVE_TEMPLATE"]: {
    message: `<template> with no special directives will render as a native template element instead of its inner content in Vue 3.`
  },
  ["COMPILER_INLINE_TEMPLATE"]: {
    message: `"inline-template" has been removed in Vue 3.`,
    link: `https://v3-migration.vuejs.org/breaking-changes/inline-template-attribute.html`
  },
  ["COMPILER_FILTERS"]: {
    message: `filters have been removed in Vue 3. The "|" symbol will be treated as native JavaScript bitwise OR operator. Use method calls or computed properties instead.`,
    link: `https://v3-migration.vuejs.org/breaking-changes/filters.html`
  }
};
function getCompatValue(key, { compatConfig }) {
  const value = compatConfig && compatConfig[key];
  if (key === "MODE") {
    return value || 3;
  } else {
    return value;
  }
}
function isCompatEnabled(key, context) {
  const mode = getCompatValue("MODE", context);
  const value = getCompatValue(key, context);
  return mode === 3 ? value === true : value !== false;
}
function checkCompatEnabled(key, context, loc, ...args) {
  const enabled = isCompatEnabled(key, context);
  return enabled;
}
function warnDeprecation(key, context, loc, ...args) {
  const val = getCompatValue(key, context);
  if (val === "suppress-warning") {
    return;
  }
  const { message, link } = deprecationData[key];
  const msg = `(deprecation ${key}) ${typeof message === "function" ? message(...args) : message}${link ? `
  Details: ${link}` : ``}`;
  const err = new SyntaxError(msg);
  err.code = key;
  if (loc) err.loc = loc;
  context.onWarn(err);
}
function defaultOnError(error) {
  throw error;
}
function defaultOnWarn(msg) {
}
function createCompilerError(code2, loc, messages, additionalMessage) {
  const msg = `https://vuejs.org/error-reference/#compiler-${code2}`;
  const error = new SyntaxError(String(msg));
  error.code = code2;
  error.loc = loc;
  return error;
}
const ErrorCodes = {
  "ABRUPT_CLOSING_OF_EMPTY_COMMENT": 0,
  "0": "ABRUPT_CLOSING_OF_EMPTY_COMMENT",
  "CDATA_IN_HTML_CONTENT": 1,
  "1": "CDATA_IN_HTML_CONTENT",
  "DUPLICATE_ATTRIBUTE": 2,
  "2": "DUPLICATE_ATTRIBUTE",
  "END_TAG_WITH_ATTRIBUTES": 3,
  "3": "END_TAG_WITH_ATTRIBUTES",
  "END_TAG_WITH_TRAILING_SOLIDUS": 4,
  "4": "END_TAG_WITH_TRAILING_SOLIDUS",
  "EOF_BEFORE_TAG_NAME": 5,
  "5": "EOF_BEFORE_TAG_NAME",
  "EOF_IN_CDATA": 6,
  "6": "EOF_IN_CDATA",
  "EOF_IN_COMMENT": 7,
  "7": "EOF_IN_COMMENT",
  "EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT": 8,
  "8": "EOF_IN_SCRIPT_HTML_COMMENT_LIKE_TEXT",
  "EOF_IN_TAG": 9,
  "9": "EOF_IN_TAG",
  "INCORRECTLY_CLOSED_COMMENT": 10,
  "10": "INCORRECTLY_CLOSED_COMMENT",
  "INCORRECTLY_OPENED_COMMENT": 11,
  "11": "INCORRECTLY_OPENED_COMMENT",
  "INVALID_FIRST_CHARACTER_OF_TAG_NAME": 12,
  "12": "INVALID_FIRST_CHARACTER_OF_TAG_NAME",
  "MISSING_ATTRIBUTE_VALUE": 13,
  "13": "MISSING_ATTRIBUTE_VALUE",
  "MISSING_END_TAG_NAME": 14,
  "14": "MISSING_END_TAG_NAME",
  "MISSING_WHITESPACE_BETWEEN_ATTRIBUTES": 15,
  "15": "MISSING_WHITESPACE_BETWEEN_ATTRIBUTES",
  "NESTED_COMMENT": 16,
  "16": "NESTED_COMMENT",
  "UNEXPECTED_CHARACTER_IN_ATTRIBUTE_NAME": 17,
  "17": "UNEXPECTED_CHARACTER_IN_ATTRIBUTE_NAME",
  "UNEXPECTED_CHARACTER_IN_UNQUOTED_ATTRIBUTE_VALUE": 18,
  "18": "UNEXPECTED_CHARACTER_IN_UNQUOTED_ATTRIBUTE_VALUE",
  "UNEXPECTED_EQUALS_SIGN_BEFORE_ATTRIBUTE_NAME": 19,
  "19": "UNEXPECTED_EQUALS_SIGN_BEFORE_ATTRIBUTE_NAME",
  "UNEXPECTED_NULL_CHARACTER": 20,
  "20": "UNEXPECTED_NULL_CHARACTER",
  "UNEXPECTED_QUESTION_MARK_INSTEAD_OF_TAG_NAME": 21,
  "21": "UNEXPECTED_QUESTION_MARK_INSTEAD_OF_TAG_NAME",
  "UNEXPECTED_SOLIDUS_IN_TAG": 22,
  "22": "UNEXPECTED_SOLIDUS_IN_TAG",
  "X_INVALID_END_TAG": 23,
  "23": "X_INVALID_END_TAG",
  "X_MISSING_END_TAG": 24,
  "24": "X_MISSING_END_TAG",
  "X_MISSING_INTERPOLATION_END": 25,
  "25": "X_MISSING_INTERPOLATION_END",
  "X_MISSING_DIRECTIVE_NAME": 26,
  "26": "X_MISSING_DIRECTIVE_NAME",
  "X_MISSING_DYNAMIC_DIRECTIVE_ARGUMENT_END": 27,
  "27": "X_MISSING_DYNAMIC_DIRECTIVE_ARGUMENT_END",
  "X_V_IF_NO_EXPRESSION": 28,
  "28": "X_V_IF_NO_EXPRESSION",
  "X_V_IF_SAME_KEY": 29,
  "29": "X_V_IF_SAME_KEY",
  "X_V_ELSE_NO_ADJACENT_IF": 30,
  "30": "X_V_ELSE_NO_ADJACENT_IF",
  "X_V_FOR_NO_EXPRESSION": 31,
  "31": "X_V_FOR_NO_EXPRESSION",
  "X_V_FOR_MALFORMED_EXPRESSION": 32,
  "32": "X_V_FOR_MALFORMED_EXPRESSION",
  "X_V_FOR_TEMPLATE_KEY_PLACEMENT": 33,
  "33": "X_V_FOR_TEMPLATE_KEY_PLACEMENT",
  "X_V_BIND_NO_EXPRESSION": 34,
  "34": "X_V_BIND_NO_EXPRESSION",
  "X_V_ON_NO_EXPRESSION": 35,
  "35": "X_V_ON_NO_EXPRESSION",
  "X_V_SLOT_UNEXPECTED_DIRECTIVE_ON_SLOT_OUTLET": 36,
  "36": "X_V_SLOT_UNEXPECTED_DIRECTIVE_ON_SLOT_OUTLET",
  "X_V_SLOT_MIXED_SLOT_USAGE": 37,
  "37": "X_V_SLOT_MIXED_SLOT_USAGE",
  "X_V_SLOT_DUPLICATE_SLOT_NAMES": 38,
  "38": "X_V_SLOT_DUPLICATE_SLOT_NAMES",
  "X_V_SLOT_EXTRANEOUS_DEFAULT_SLOT_CHILDREN": 39,
  "39": "X_V_SLOT_EXTRANEOUS_DEFAULT_SLOT_CHILDREN",
  "X_V_SLOT_MISPLACED": 40,
  "40": "X_V_SLOT_MISPLACED",
  "X_V_MODEL_NO_EXPRESSION": 41,
  "41": "X_V_MODEL_NO_EXPRESSION",
  "X_V_MODEL_MALFORMED_EXPRESSION": 42,
  "42": "X_V_MODEL_MALFORMED_EXPRESSION",
  "X_V_MODEL_ON_SCOPE_VARIABLE": 43,
  "43": "X_V_MODEL_ON_SCOPE_VARIABLE",
  "X_V_MODEL_ON_PROPS": 44,
  "44": "X_V_MODEL_ON_PROPS",
  "X_INVALID_EXPRESSION": 45,
  "45": "X_INVALID_EXPRESSION",
  "X_KEEP_ALIVE_INVALID_CHILDREN": 46,
  "46": "X_KEEP_ALIVE_INVALID_CHILDREN",
  "X_PREFIX_ID_NOT_SUPPORTED": 47,
  "47": "X_PREFIX_ID_NOT_SUPPORTED",
  "X_MODULE_MODE_NOT_SUPPORTED": 48,
  "48": "X_MODULE_MODE_NOT_SUPPORTED",
  "X_CACHE_HANDLER_NOT_SUPPORTED": 49,
  "49": "X_CACHE_HANDLER_NOT_SUPPORTED",
  "X_SCOPE_ID_NOT_SUPPORTED": 50,
  "50": "X_SCOPE_ID_NOT_SUPPORTED",
  "X_VNODE_HOOKS": 51,
  "51": "X_VNODE_HOOKS",
  "X_V_BIND_INVALID_SAME_NAME_ARGUMENT": 52,
  "52": "X_V_BIND_INVALID_SAME_NAME_ARGUMENT",
  "__EXTEND_POINT__": 53,
  "53": "__EXTEND_POINT__"
};
const errorMessages = {
  // parse errors
  [0]: "Illegal comment.",
  [1]: "CDATA section is allowed only in XML context.",
  [2]: "Duplicate attribute.",
  [3]: "End tag cannot have attributes.",
  [4]: "Illegal '/' in tags.",
  [5]: "Unexpected EOF in tag.",
  [6]: "Unexpected EOF in CDATA section.",
  [7]: "Unexpected EOF in comment.",
  [8]: "Unexpected EOF in script.",
  [9]: "Unexpected EOF in tag.",
  [10]: "Incorrectly closed comment.",
  [11]: "Incorrectly opened comment.",
  [12]: "Illegal tag name. Use '&lt;' to print '<'.",
  [13]: "Attribute value was expected.",
  [14]: "End tag name was expected.",
  [15]: "Whitespace was expected.",
  [16]: "Unexpected '<!--' in comment.",
  [17]: `Attribute name cannot contain U+0022 ("), U+0027 ('), and U+003C (<).`,
  [18]: "Unquoted attribute value cannot contain U+0022 (\"), U+0027 ('), U+003C (<), U+003D (=), and U+0060 (`).",
  [19]: "Attribute name cannot start with '='.",
  [21]: "'<?' is allowed only in XML context.",
  [20]: `Unexpected null character.`,
  [22]: "Illegal '/' in tags.",
  // Vue-specific parse errors
  [23]: "Invalid end tag.",
  [24]: "Element is missing end tag.",
  [25]: "Interpolation end sign was not found.",
  [27]: "End bracket for dynamic directive argument was not found. Note that dynamic directive argument cannot contain spaces.",
  [26]: "Legal directive name was expected.",
  // transform errors
  [28]: `v-if/v-else-if is missing expression.`,
  [29]: `v-if/else branches must use unique keys.`,
  [30]: `v-else/v-else-if has no adjacent v-if or v-else-if.`,
  [31]: `v-for is missing expression.`,
  [32]: `v-for has invalid expression.`,
  [33]: `<template v-for> key should be placed on the <template> tag.`,
  [34]: `v-bind is missing expression.`,
  [52]: `v-bind with same-name shorthand only allows static argument.`,
  [35]: `v-on is missing expression.`,
  [36]: `Unexpected custom directive on <slot> outlet.`,
  [37]: `Mixed v-slot usage on both the component and nested <template>. When there are multiple named slots, all slots should use <template> syntax to avoid scope ambiguity.`,
  [38]: `Duplicate slot names found. `,
  [39]: `Extraneous children found when component already has explicitly named default slot. These children will be ignored.`,
  [40]: `v-slot can only be used on components or <template> tags.`,
  [41]: `v-model is missing expression.`,
  [42]: `v-model value must be a valid JavaScript member expression.`,
  [43]: `v-model cannot be used on v-for or v-slot scope variables because they are not writable.`,
  [44]: `v-model cannot be used on a prop, because local prop bindings are not writable.
Use a v-bind binding combined with a v-on listener that emits update:x event instead.`,
  [45]: `Error parsing JavaScript expression: `,
  [46]: `<KeepAlive> expects exactly one child component.`,
  [51]: `@vnode-* hooks in templates are no longer supported. Use the vue: prefix instead. For example, @vnode-mounted should be changed to @vue:mounted. @vnode-* hooks support has been removed in 3.4.`,
  // generic errors
  [47]: `"prefixIdentifiers" option is not supported in this build of compiler.`,
  [48]: `ES module mode is not supported in this build of compiler.`,
  [49]: `"cacheHandlers" option is only supported when the "prefixIdentifiers" option is enabled.`,
  [50]: `"scopeId" option is only supported in module mode.`,
  // just to fulfill types
  [53]: ``
};
function walkIdentifiers(root, onIdentifier, includeAll = false, parentStack = [], knownIds = /* @__PURE__ */ Object.create(null)) {
  {
    return;
  }
}
function isReferencedIdentifier(id, parent, parentStack) {
  {
    return false;
  }
}
function isInDestructureAssignment(parent, parentStack) {
  if (parent && (parent.type === "ObjectProperty" || parent.type === "ArrayPattern")) {
    let i = parentStack.length;
    while (i--) {
      const p2 = parentStack[i];
      if (p2.type === "AssignmentExpression") {
        return true;
      } else if (p2.type !== "ObjectProperty" && !p2.type.endsWith("Pattern")) {
        break;
      }
    }
  }
  return false;
}
function isInNewExpression(parentStack) {
  let i = parentStack.length;
  while (i--) {
    const p2 = parentStack[i];
    if (p2.type === "NewExpression") {
      return true;
    } else if (p2.type !== "MemberExpression") {
      break;
    }
  }
  return false;
}
function walkFunctionParams(node, onIdent) {
  for (const p2 of node.params) {
    for (const id of extractIdentifiers(p2)) {
      onIdent(id);
    }
  }
}
function walkBlockDeclarations(block, onIdent) {
  const body = block.type === "SwitchCase" ? block.consequent : block.body;
  for (const stmt of body) {
    if (stmt.type === "VariableDeclaration") {
      if (stmt.declare) continue;
      for (const decl of stmt.declarations) {
        for (const id of extractIdentifiers(decl.id)) {
          onIdent(id);
        }
      }
    } else if (stmt.type === "FunctionDeclaration" || stmt.type === "ClassDeclaration") {
      if (stmt.declare || !stmt.id) continue;
      onIdent(stmt.id);
    } else if (isForStatement(stmt)) {
      walkForStatement(stmt, true, onIdent);
    } else if (stmt.type === "SwitchStatement") {
      walkSwitchStatement(stmt, true, onIdent);
    }
  }
}
function isForStatement(stmt) {
  return stmt.type === "ForOfStatement" || stmt.type === "ForInStatement" || stmt.type === "ForStatement";
}
function walkForStatement(stmt, isVar, onIdent) {
  const variable = stmt.type === "ForStatement" ? stmt.init : stmt.left;
  if (variable && variable.type === "VariableDeclaration" && (variable.kind === "var" ? isVar : !isVar)) {
    for (const decl of variable.declarations) {
      for (const id of extractIdentifiers(decl.id)) {
        onIdent(id);
      }
    }
  }
}
function walkSwitchStatement(stmt, isVar, onIdent) {
  for (const cs of stmt.cases) {
    for (const stmt2 of cs.consequent) {
      if (stmt2.type === "VariableDeclaration" && (stmt2.kind === "var" ? isVar : !isVar)) {
        for (const decl of stmt2.declarations) {
          for (const id of extractIdentifiers(decl.id)) {
            onIdent(id);
          }
        }
      }
    }
    walkBlockDeclarations(cs, onIdent);
  }
}
function extractIdentifiers(param, nodes = []) {
  switch (param.type) {
    case "Identifier":
      nodes.push(param);
      break;
    case "MemberExpression":
      let object = param;
      while (object.type === "MemberExpression") {
        object = object.object;
      }
      nodes.push(object);
      break;
    case "ObjectPattern":
      for (const prop of param.properties) {
        if (prop.type === "RestElement") {
          extractIdentifiers(prop.argument, nodes);
        } else {
          extractIdentifiers(prop.value, nodes);
        }
      }
      break;
    case "ArrayPattern":
      param.elements.forEach((element) => {
        if (element) extractIdentifiers(element, nodes);
      });
      break;
    case "RestElement":
      extractIdentifiers(param.argument, nodes);
      break;
    case "AssignmentPattern":
      extractIdentifiers(param.left, nodes);
      break;
  }
  return nodes;
}
const isFunctionType = (node) => {
  return /Function(?:Expression|Declaration)$|Method$/.test(node.type);
};
const isStaticProperty = (node) => node && (node.type === "ObjectProperty" || node.type === "ObjectMethod") && !node.computed;
const isStaticPropertyKey = (node, parent) => isStaticProperty(parent) && parent.key === node;
const TS_NODE_TYPES = [
  "TSAsExpression",
  // foo as number
  "TSTypeAssertion",
  // (<number>foo)
  "TSNonNullExpression",
  // foo!
  "TSInstantiationExpression",
  // foo<string>
  "TSSatisfiesExpression"
  // foo satisfies T
];
function unwrapTSNode(node) {
  if (TS_NODE_TYPES.includes(node.type)) {
    return unwrapTSNode(node.expression);
  } else {
    return node;
  }
}
const isStaticExp = (p2) => p2.type === 4 && p2.isStatic;
function isCoreComponent(tag) {
  switch (tag) {
    case "Teleport":
    case "teleport":
      return TELEPORT;
    case "Suspense":
    case "suspense":
      return SUSPENSE;
    case "KeepAlive":
    case "keep-alive":
      return KEEP_ALIVE;
    case "BaseTransition":
    case "base-transition":
      return BASE_TRANSITION;
  }
}
const nonIdentifierRE = /^$|^\d|[^\$\w\xA0-\uFFFF]/;
const isSimpleIdentifier = (name) => !nonIdentifierRE.test(name);
const validFirstIdentCharRE = /[A-Za-z_$\xA0-\uFFFF]/;
const validIdentCharRE = /[\.\?\w$\xA0-\uFFFF]/;
const whitespaceRE = /\s+[.[]\s*|\s*[.[]\s+/g;
const getExpSource = (exp) => exp.type === 4 ? exp.content : exp.loc.source;
const isMemberExpressionBrowser = (exp) => {
  const path2 = getExpSource(exp).trim().replace(whitespaceRE, (s) => s.trim());
  let state2 = 0;
  let stateStack = [];
  let currentOpenBracketCount = 0;
  let currentOpenParensCount = 0;
  let currentStringType = null;
  for (let i = 0; i < path2.length; i++) {
    const char = path2.charAt(i);
    switch (state2) {
      case 0:
        if (char === "[") {
          stateStack.push(state2);
          state2 = 1;
          currentOpenBracketCount++;
        } else if (char === "(") {
          stateStack.push(state2);
          state2 = 2;
          currentOpenParensCount++;
        } else if (!(i === 0 ? validFirstIdentCharRE : validIdentCharRE).test(char)) {
          return false;
        }
        break;
      case 1:
        if (char === `'` || char === `"` || char === "`") {
          stateStack.push(state2);
          state2 = 3;
          currentStringType = char;
        } else if (char === `[`) {
          currentOpenBracketCount++;
        } else if (char === `]`) {
          if (!--currentOpenBracketCount) {
            state2 = stateStack.pop();
          }
        }
        break;
      case 2:
        if (char === `'` || char === `"` || char === "`") {
          stateStack.push(state2);
          state2 = 3;
          currentStringType = char;
        } else if (char === `(`) {
          currentOpenParensCount++;
        } else if (char === `)`) {
          if (i === path2.length - 1) {
            return false;
          }
          if (!--currentOpenParensCount) {
            state2 = stateStack.pop();
          }
        }
        break;
      case 3:
        if (char === currentStringType) {
          state2 = stateStack.pop();
          currentStringType = null;
        }
        break;
    }
  }
  return !currentOpenBracketCount && !currentOpenParensCount;
};
const isMemberExpressionNode = NOOP;
const isMemberExpression = isMemberExpressionBrowser;
const fnExpRE = /^\s*(?:async\s*)?(?:\([^)]*?\)|[\w$_]+)\s*(?::[^=]+)?=>|^\s*(?:async\s+)?function(?:\s+[\w$]+)?\s*\(/;
const isFnExpressionBrowser = (exp) => fnExpRE.test(getExpSource(exp));
const isFnExpressionNode = NOOP;
const isFnExpression = isFnExpressionBrowser;
function advancePositionWithClone(pos, source, numberOfCharacters = source.length) {
  return advancePositionWithMutation(
    {
      offset: pos.offset,
      line: pos.line,
      column: pos.column
    },
    source,
    numberOfCharacters
  );
}
function advancePositionWithMutation(pos, source, numberOfCharacters = source.length) {
  let linesCount = 0;
  let lastNewLinePos = -1;
  for (let i = 0; i < numberOfCharacters; i++) {
    if (source.charCodeAt(i) === 10) {
      linesCount++;
      lastNewLinePos = i;
    }
  }
  pos.offset += numberOfCharacters;
  pos.line += linesCount;
  pos.column = lastNewLinePos === -1 ? pos.column + numberOfCharacters : numberOfCharacters - lastNewLinePos;
  return pos;
}
function assert(condition, msg) {
  if (!condition) {
    throw new Error(msg || `unexpected compiler condition`);
  }
}
function findDir(node, name, allowEmpty = false) {
  for (let i = 0; i < node.props.length; i++) {
    const p2 = node.props[i];
    if (p2.type === 7 && (allowEmpty || p2.exp) && (isString(name) ? p2.name === name : name.test(p2.name))) {
      return p2;
    }
  }
}
function findProp(node, name, dynamicOnly = false, allowEmpty = false) {
  for (let i = 0; i < node.props.length; i++) {
    const p2 = node.props[i];
    if (p2.type === 6) {
      if (dynamicOnly) continue;
      if (p2.name === name && (p2.value || allowEmpty)) {
        return p2;
      }
    } else if (p2.name === "bind" && (p2.exp || allowEmpty) && isStaticArgOf(p2.arg, name)) {
      return p2;
    }
  }
}
function isStaticArgOf(arg, name) {
  return !!(arg && isStaticExp(arg) && arg.content === name);
}
function hasDynamicKeyVBind(node) {
  return node.props.some(
    (p2) => p2.type === 7 && p2.name === "bind" && (!p2.arg || // v-bind="obj"
    p2.arg.type !== 4 || // v-bind:[_ctx.foo]
    !p2.arg.isStatic)
    // v-bind:[foo]
  );
}
function isText$1(node) {
  return node.type === 5 || node.type === 2;
}
function isVPre(p2) {
  return p2.type === 7 && p2.name === "pre";
}
function isVSlot(p2) {
  return p2.type === 7 && p2.name === "slot";
}
function isTemplateNode(node) {
  return node.type === 1 && node.tagType === 3;
}
function isSlotOutlet(node) {
  return node.type === 1 && node.tagType === 2;
}
const propsHelperSet = /* @__PURE__ */ new Set([NORMALIZE_PROPS, GUARD_REACTIVE_PROPS]);
function getUnnormalizedProps(props, callPath = []) {
  if (props && !isString(props) && props.type === 14) {
    const callee = props.callee;
    if (!isString(callee) && propsHelperSet.has(callee)) {
      return getUnnormalizedProps(
        props.arguments[0],
        callPath.concat(props)
      );
    }
  }
  return [props, callPath];
}
function injectProp(node, prop, context) {
  let propsWithInjection;
  let props = node.type === 13 ? node.props : node.arguments[2];
  let callPath = [];
  let parentCall;
  if (props && !isString(props) && props.type === 14) {
    const ret = getUnnormalizedProps(props);
    props = ret[0];
    callPath = ret[1];
    parentCall = callPath[callPath.length - 1];
  }
  if (props == null || isString(props)) {
    propsWithInjection = createObjectExpression([prop]);
  } else if (props.type === 14) {
    const first = props.arguments[0];
    if (!isString(first) && first.type === 15) {
      if (!hasProp(prop, first)) {
        first.properties.unshift(prop);
      }
    } else {
      if (props.callee === TO_HANDLERS) {
        propsWithInjection = createCallExpression(context.helper(MERGE_PROPS), [
          createObjectExpression([prop]),
          props
        ]);
      } else {
        props.arguments.unshift(createObjectExpression([prop]));
      }
    }
    !propsWithInjection && (propsWithInjection = props);
  } else if (props.type === 15) {
    if (!hasProp(prop, props)) {
      props.properties.unshift(prop);
    }
    propsWithInjection = props;
  } else {
    propsWithInjection = createCallExpression(context.helper(MERGE_PROPS), [
      createObjectExpression([prop]),
      props
    ]);
    if (parentCall && parentCall.callee === GUARD_REACTIVE_PROPS) {
      parentCall = callPath[callPath.length - 2];
    }
  }
  if (node.type === 13) {
    if (parentCall) {
      parentCall.arguments[0] = propsWithInjection;
    } else {
      node.props = propsWithInjection;
    }
  } else {
    if (parentCall) {
      parentCall.arguments[0] = propsWithInjection;
    } else {
      node.arguments[2] = propsWithInjection;
    }
  }
}
function hasProp(prop, props) {
  let result = false;
  if (prop.key.type === 4) {
    const propKeyName = prop.key.content;
    result = props.properties.some(
      (p2) => p2.key.type === 4 && p2.key.content === propKeyName
    );
  }
  return result;
}
function toValidAssetId(name, type) {
  return `_${type}_${name.replace(/[^\w]/g, (searchValue, replaceValue) => {
    return searchValue === "-" ? "_" : name.charCodeAt(replaceValue).toString();
  })}`;
}
function hasScopeRef(node, ids) {
  if (!node || Object.keys(ids).length === 0) {
    return false;
  }
  switch (node.type) {
    case 1:
      for (let i = 0; i < node.props.length; i++) {
        const p2 = node.props[i];
        if (p2.type === 7 && (hasScopeRef(p2.arg, ids) || hasScopeRef(p2.exp, ids))) {
          return true;
        }
      }
      return node.children.some((c) => hasScopeRef(c, ids));
    case 11:
      if (hasScopeRef(node.source, ids)) {
        return true;
      }
      return node.children.some((c) => hasScopeRef(c, ids));
    case 9:
      return node.branches.some((b2) => hasScopeRef(b2, ids));
    case 10:
      if (hasScopeRef(node.condition, ids)) {
        return true;
      }
      return node.children.some((c) => hasScopeRef(c, ids));
    case 4:
      return !node.isStatic && isSimpleIdentifier(node.content) && !!ids[node.content];
    case 8:
      return node.children.some((c) => isObject$1(c) && hasScopeRef(c, ids));
    case 5:
    case 12:
      return hasScopeRef(node.content, ids);
    case 2:
    case 3:
    case 20:
      return false;
    default:
      return false;
  }
}
function getMemoedVNodeCall(node) {
  if (node.type === 14 && node.callee === WITH_MEMO) {
    return node.arguments[1].returns;
  } else {
    return node;
  }
}
const forAliasRE = /([\s\S]*?)\s+(?:in|of)\s+(\S[\s\S]*)/;
const defaultParserOptions = {
  parseMode: "base",
  ns: 0,
  delimiters: [`{{`, `}}`],
  getNamespace: () => 0,
  isVoidTag: NO,
  isPreTag: NO,
  isIgnoreNewlineTag: NO,
  isCustomElement: NO,
  onError: defaultOnError,
  onWarn: defaultOnWarn,
  comments: false,
  prefixIdentifiers: false
};
let currentOptions = defaultParserOptions;
let currentRoot = null;
let currentInput = "";
let currentOpenTag = null;
let currentProp = null;
let currentAttrValue = "";
let currentAttrStartIndex = -1;
let currentAttrEndIndex = -1;
let inPre = 0;
let inVPre = false;
let currentVPreBoundary = null;
const stack = [];
const tokenizer = new Tokenizer(stack, {
  onerr: emitError,
  ontext(start, end) {
    onText(getSlice(start, end), start, end);
  },
  ontextentity(char, start, end) {
    onText(char, start, end);
  },
  oninterpolation(start, end) {
    if (inVPre) {
      return onText(getSlice(start, end), start, end);
    }
    let innerStart = start + tokenizer.delimiterOpen.length;
    let innerEnd = end - tokenizer.delimiterClose.length;
    while (isWhitespace(currentInput.charCodeAt(innerStart))) {
      innerStart++;
    }
    while (isWhitespace(currentInput.charCodeAt(innerEnd - 1))) {
      innerEnd--;
    }
    let exp = getSlice(innerStart, innerEnd);
    if (exp.includes("&")) {
      {
        exp = currentOptions.decodeEntities(exp, false);
      }
    }
    addNode({
      type: 5,
      content: createExp(exp, false, getLoc(innerStart, innerEnd)),
      loc: getLoc(start, end)
    });
  },
  onopentagname(start, end) {
    const name = getSlice(start, end);
    currentOpenTag = {
      type: 1,
      tag: name,
      ns: currentOptions.getNamespace(name, stack[0], currentOptions.ns),
      tagType: 0,
      // will be refined on tag close
      props: [],
      children: [],
      loc: getLoc(start - 1, end),
      codegenNode: void 0
    };
  },
  onopentagend(end) {
    endOpenTag(end);
  },
  onclosetag(start, end) {
    const name = getSlice(start, end);
    if (!currentOptions.isVoidTag(name)) {
      let found = false;
      for (let i = 0; i < stack.length; i++) {
        const e = stack[i];
        if (e.tag.toLowerCase() === name.toLowerCase()) {
          found = true;
          if (i > 0) {
            emitError(24, stack[0].loc.start.offset);
          }
          for (let j2 = 0; j2 <= i; j2++) {
            const el = stack.shift();
            onCloseTag(el, end, j2 < i);
          }
          break;
        }
      }
      if (!found) {
        emitError(23, backTrack(start, 60));
      }
    }
  },
  onselfclosingtag(end) {
    const name = currentOpenTag.tag;
    currentOpenTag.isSelfClosing = true;
    endOpenTag(end);
    if (stack[0] && stack[0].tag === name) {
      onCloseTag(stack.shift(), end);
    }
  },
  onattribname(start, end) {
    currentProp = {
      type: 6,
      name: getSlice(start, end),
      nameLoc: getLoc(start, end),
      value: void 0,
      loc: getLoc(start)
    };
  },
  ondirname(start, end) {
    const raw = getSlice(start, end);
    const name = raw === "." || raw === ":" ? "bind" : raw === "@" ? "on" : raw === "#" ? "slot" : raw.slice(2);
    if (!inVPre && name === "") {
      emitError(26, start);
    }
    if (inVPre || name === "") {
      currentProp = {
        type: 6,
        name: raw,
        nameLoc: getLoc(start, end),
        value: void 0,
        loc: getLoc(start)
      };
    } else {
      currentProp = {
        type: 7,
        name,
        rawName: raw,
        exp: void 0,
        arg: void 0,
        modifiers: raw === "." ? [createSimpleExpression("prop")] : [],
        loc: getLoc(start)
      };
      if (name === "pre") {
        inVPre = tokenizer.inVPre = true;
        currentVPreBoundary = currentOpenTag;
        const props = currentOpenTag.props;
        for (let i = 0; i < props.length; i++) {
          if (props[i].type === 7) {
            props[i] = dirToAttr(props[i]);
          }
        }
      }
    }
  },
  ondirarg(start, end) {
    if (start === end) return;
    const arg = getSlice(start, end);
    if (inVPre && !isVPre(currentProp)) {
      currentProp.name += arg;
      setLocEnd(currentProp.nameLoc, end);
    } else {
      const isStatic = arg[0] !== `[`;
      currentProp.arg = createExp(
        isStatic ? arg : arg.slice(1, -1),
        isStatic,
        getLoc(start, end),
        isStatic ? 3 : 0
      );
    }
  },
  ondirmodifier(start, end) {
    const mod = getSlice(start, end);
    if (inVPre && !isVPre(currentProp)) {
      currentProp.name += "." + mod;
      setLocEnd(currentProp.nameLoc, end);
    } else if (currentProp.name === "slot") {
      const arg = currentProp.arg;
      if (arg) {
        arg.content += "." + mod;
        setLocEnd(arg.loc, end);
      }
    } else {
      const exp = createSimpleExpression(mod, true, getLoc(start, end));
      currentProp.modifiers.push(exp);
    }
  },
  onattribdata(start, end) {
    currentAttrValue += getSlice(start, end);
    if (currentAttrStartIndex < 0) currentAttrStartIndex = start;
    currentAttrEndIndex = end;
  },
  onattribentity(char, start, end) {
    currentAttrValue += char;
    if (currentAttrStartIndex < 0) currentAttrStartIndex = start;
    currentAttrEndIndex = end;
  },
  onattribnameend(end) {
    const start = currentProp.loc.start.offset;
    const name = getSlice(start, end);
    if (currentProp.type === 7) {
      currentProp.rawName = name;
    }
    if (currentOpenTag.props.some(
      (p2) => (p2.type === 7 ? p2.rawName : p2.name) === name
    )) {
      emitError(2, start);
    }
  },
  onattribend(quote, end) {
    if (currentOpenTag && currentProp) {
      setLocEnd(currentProp.loc, end);
      if (quote !== 0) {
        if (currentAttrValue.includes("&")) {
          currentAttrValue = currentOptions.decodeEntities(
            currentAttrValue,
            true
          );
        }
        if (currentProp.type === 6) {
          if (currentProp.name === "class") {
            currentAttrValue = condense(currentAttrValue).trim();
          }
          if (quote === 1 && !currentAttrValue) {
            emitError(13, end);
          }
          currentProp.value = {
            type: 2,
            content: currentAttrValue,
            loc: quote === 1 ? getLoc(currentAttrStartIndex, currentAttrEndIndex) : getLoc(currentAttrStartIndex - 1, currentAttrEndIndex + 1)
          };
          if (tokenizer.inSFCRoot && currentOpenTag.tag === "template" && currentProp.name === "lang" && currentAttrValue && currentAttrValue !== "html") {
            tokenizer.enterRCDATA(toCharCodes(`</template`), 0);
          }
        } else {
          let expParseMode = 0;
          currentProp.exp = createExp(
            currentAttrValue,
            false,
            getLoc(currentAttrStartIndex, currentAttrEndIndex),
            0,
            expParseMode
          );
          if (currentProp.name === "for") {
            currentProp.forParseResult = parseForExpression(currentProp.exp);
          }
          let syncIndex = -1;
          if (currentProp.name === "bind" && (syncIndex = currentProp.modifiers.findIndex(
            (mod) => mod.content === "sync"
          )) > -1 && checkCompatEnabled(
            "COMPILER_V_BIND_SYNC",
            currentOptions,
            currentProp.loc,
            currentProp.arg.loc.source
          )) {
            currentProp.name = "model";
            currentProp.modifiers.splice(syncIndex, 1);
          }
        }
      }
      if (currentProp.type !== 7 || currentProp.name !== "pre") {
        currentOpenTag.props.push(currentProp);
      }
    }
    currentAttrValue = "";
    currentAttrStartIndex = currentAttrEndIndex = -1;
  },
  oncomment(start, end) {
    if (currentOptions.comments) {
      addNode({
        type: 3,
        content: getSlice(start, end),
        loc: getLoc(start - 4, end + 3)
      });
    }
  },
  onend() {
    const end = currentInput.length;
    for (let index = 0; index < stack.length; index++) {
      onCloseTag(stack[index], end - 1);
      emitError(24, stack[index].loc.start.offset);
    }
  },
  oncdata(start, end) {
    if (stack[0].ns !== 0) {
      onText(getSlice(start, end), start, end);
    } else {
      emitError(1, start - 9);
    }
  },
  onprocessinginstruction(start) {
    if ((stack[0] ? stack[0].ns : currentOptions.ns) === 0) {
      emitError(
        21,
        start - 1
      );
    }
  }
});
const forIteratorRE = /,([^,\}\]]*)(?:,([^,\}\]]*))?$/;
const stripParensRE = /^\(|\)$/g;
function parseForExpression(input) {
  const loc = input.loc;
  const exp = input.content;
  const inMatch = exp.match(forAliasRE);
  if (!inMatch) return;
  const [, LHS, RHS] = inMatch;
  const createAliasExpression = (content, offset, asParam = false) => {
    const start = loc.start.offset + offset;
    const end = start + content.length;
    return createExp(
      content,
      false,
      getLoc(start, end),
      0,
      asParam ? 1 : 0
      /* Normal */
    );
  };
  const result = {
    source: createAliasExpression(RHS.trim(), exp.indexOf(RHS, LHS.length)),
    value: void 0,
    key: void 0,
    index: void 0,
    finalized: false
  };
  let valueContent = LHS.trim().replace(stripParensRE, "").trim();
  const trimmedOffset = LHS.indexOf(valueContent);
  const iteratorMatch = valueContent.match(forIteratorRE);
  if (iteratorMatch) {
    valueContent = valueContent.replace(forIteratorRE, "").trim();
    const keyContent = iteratorMatch[1].trim();
    let keyOffset;
    if (keyContent) {
      keyOffset = exp.indexOf(keyContent, trimmedOffset + valueContent.length);
      result.key = createAliasExpression(keyContent, keyOffset, true);
    }
    if (iteratorMatch[2]) {
      const indexContent = iteratorMatch[2].trim();
      if (indexContent) {
        result.index = createAliasExpression(
          indexContent,
          exp.indexOf(
            indexContent,
            result.key ? keyOffset + keyContent.length : trimmedOffset + valueContent.length
          ),
          true
        );
      }
    }
  }
  if (valueContent) {
    result.value = createAliasExpression(valueContent, trimmedOffset, true);
  }
  return result;
}
function getSlice(start, end) {
  return currentInput.slice(start, end);
}
function endOpenTag(end) {
  if (tokenizer.inSFCRoot) {
    currentOpenTag.innerLoc = getLoc(end + 1, end + 1);
  }
  addNode(currentOpenTag);
  const { tag, ns } = currentOpenTag;
  if (ns === 0 && currentOptions.isPreTag(tag)) {
    inPre++;
  }
  if (currentOptions.isVoidTag(tag)) {
    onCloseTag(currentOpenTag, end);
  } else {
    stack.unshift(currentOpenTag);
    if (ns === 1 || ns === 2) {
      tokenizer.inXML = true;
    }
  }
  currentOpenTag = null;
}
function onText(content, start, end) {
  {
    const tag = stack[0] && stack[0].tag;
    if (tag !== "script" && tag !== "style" && content.includes("&")) {
      content = currentOptions.decodeEntities(content, false);
    }
  }
  const parent = stack[0] || currentRoot;
  const lastNode = parent.children[parent.children.length - 1];
  if (lastNode && lastNode.type === 2) {
    lastNode.content += content;
    setLocEnd(lastNode.loc, end);
  } else {
    parent.children.push({
      type: 2,
      content,
      loc: getLoc(start, end)
    });
  }
}
function onCloseTag(el, end, isImplied = false) {
  if (isImplied) {
    setLocEnd(el.loc, backTrack(end, 60));
  } else {
    setLocEnd(el.loc, lookAhead(end, 62) + 1);
  }
  if (tokenizer.inSFCRoot) {
    if (el.children.length) {
      el.innerLoc.end = extend({}, el.children[el.children.length - 1].loc.end);
    } else {
      el.innerLoc.end = extend({}, el.innerLoc.start);
    }
    el.innerLoc.source = getSlice(
      el.innerLoc.start.offset,
      el.innerLoc.end.offset
    );
  }
  const { tag, ns, children } = el;
  if (!inVPre) {
    if (tag === "slot") {
      el.tagType = 2;
    } else if (isFragmentTemplate(el)) {
      el.tagType = 3;
    } else if (isComponent(el)) {
      el.tagType = 1;
    }
  }
  if (!tokenizer.inRCDATA) {
    el.children = condenseWhitespace(children);
  }
  if (ns === 0 && currentOptions.isIgnoreNewlineTag(tag)) {
    const first = children[0];
    if (first && first.type === 2) {
      first.content = first.content.replace(/^\r?\n/, "");
    }
  }
  if (ns === 0 && currentOptions.isPreTag(tag)) {
    inPre--;
  }
  if (currentVPreBoundary === el) {
    inVPre = tokenizer.inVPre = false;
    currentVPreBoundary = null;
  }
  if (tokenizer.inXML && (stack[0] ? stack[0].ns : currentOptions.ns) === 0) {
    tokenizer.inXML = false;
  }
  {
    const props = el.props;
    if (!tokenizer.inSFCRoot && isCompatEnabled(
      "COMPILER_NATIVE_TEMPLATE",
      currentOptions
    ) && el.tag === "template" && !isFragmentTemplate(el)) {
      const parent = stack[0] || currentRoot;
      const index = parent.children.indexOf(el);
      parent.children.splice(index, 1, ...el.children);
    }
    const inlineTemplateProp = props.find(
      (p2) => p2.type === 6 && p2.name === "inline-template"
    );
    if (inlineTemplateProp && checkCompatEnabled(
      "COMPILER_INLINE_TEMPLATE",
      currentOptions,
      inlineTemplateProp.loc
    ) && el.children.length) {
      inlineTemplateProp.value = {
        type: 2,
        content: getSlice(
          el.children[0].loc.start.offset,
          el.children[el.children.length - 1].loc.end.offset
        ),
        loc: inlineTemplateProp.loc
      };
    }
  }
}
function lookAhead(index, c) {
  let i = index;
  while (currentInput.charCodeAt(i) !== c && i < currentInput.length - 1) i++;
  return i;
}
function backTrack(index, c) {
  let i = index;
  while (currentInput.charCodeAt(i) !== c && i >= 0) i--;
  return i;
}
const specialTemplateDir = /* @__PURE__ */ new Set(["if", "else", "else-if", "for", "slot"]);
function isFragmentTemplate({ tag, props }) {
  if (tag === "template") {
    for (let i = 0; i < props.length; i++) {
      if (props[i].type === 7 && specialTemplateDir.has(props[i].name)) {
        return true;
      }
    }
  }
  return false;
}
function isComponent({ tag, props }) {
  if (currentOptions.isCustomElement(tag)) {
    return false;
  }
  if (tag === "component" || isUpperCase(tag.charCodeAt(0)) || isCoreComponent(tag) || currentOptions.isBuiltInComponent && currentOptions.isBuiltInComponent(tag) || currentOptions.isNativeTag && !currentOptions.isNativeTag(tag)) {
    return true;
  }
  for (let i = 0; i < props.length; i++) {
    const p2 = props[i];
    if (p2.type === 6) {
      if (p2.name === "is" && p2.value) {
        if (p2.value.content.startsWith("vue:")) {
          return true;
        } else if (checkCompatEnabled(
          "COMPILER_IS_ON_ELEMENT",
          currentOptions,
          p2.loc
        )) {
          return true;
        }
      }
    } else if (
      // :is on plain element - only treat as component in compat mode
      p2.name === "bind" && isStaticArgOf(p2.arg, "is") && checkCompatEnabled(
        "COMPILER_IS_ON_ELEMENT",
        currentOptions,
        p2.loc
      )
    ) {
      return true;
    }
  }
  return false;
}
function isUpperCase(c) {
  return c > 64 && c < 91;
}
const windowsNewlineRE = /\r\n/g;
function condenseWhitespace(nodes) {
  const shouldCondense = currentOptions.whitespace !== "preserve";
  let removedWhitespace = false;
  for (let i = 0; i < nodes.length; i++) {
    const node = nodes[i];
    if (node.type === 2) {
      if (!inPre) {
        if (isAllWhitespace(node.content)) {
          const prev = nodes[i - 1] && nodes[i - 1].type;
          const next = nodes[i + 1] && nodes[i + 1].type;
          if (!prev || !next || shouldCondense && (prev === 3 && (next === 3 || next === 1) || prev === 1 && (next === 3 || next === 1 && hasNewlineChar(node.content)))) {
            removedWhitespace = true;
            nodes[i] = null;
          } else {
            node.content = " ";
          }
        } else if (shouldCondense) {
          node.content = condense(node.content);
        }
      } else {
        node.content = node.content.replace(windowsNewlineRE, "\n");
      }
    }
  }
  return removedWhitespace ? nodes.filter(Boolean) : nodes;
}
function isAllWhitespace(str) {
  for (let i = 0; i < str.length; i++) {
    if (!isWhitespace(str.charCodeAt(i))) {
      return false;
    }
  }
  return true;
}
function hasNewlineChar(str) {
  for (let i = 0; i < str.length; i++) {
    const c = str.charCodeAt(i);
    if (c === 10 || c === 13) {
      return true;
    }
  }
  return false;
}
function condense(str) {
  let ret = "";
  let prevCharIsWhitespace = false;
  for (let i = 0; i < str.length; i++) {
    if (isWhitespace(str.charCodeAt(i))) {
      if (!prevCharIsWhitespace) {
        ret += " ";
        prevCharIsWhitespace = true;
      }
    } else {
      ret += str[i];
      prevCharIsWhitespace = false;
    }
  }
  return ret;
}
function addNode(node) {
  (stack[0] || currentRoot).children.push(node);
}
function getLoc(start, end) {
  return {
    start: tokenizer.getPos(start),
    // @ts-expect-error allow late attachment
    end: end == null ? end : tokenizer.getPos(end),
    // @ts-expect-error allow late attachment
    source: end == null ? end : getSlice(start, end)
  };
}
function cloneLoc(loc) {
  return getLoc(loc.start.offset, loc.end.offset);
}
function setLocEnd(loc, end) {
  loc.end = tokenizer.getPos(end);
  loc.source = getSlice(loc.start.offset, end);
}
function dirToAttr(dir) {
  const attr = {
    type: 6,
    name: dir.rawName,
    nameLoc: getLoc(
      dir.loc.start.offset,
      dir.loc.start.offset + dir.rawName.length
    ),
    value: void 0,
    loc: dir.loc
  };
  if (dir.exp) {
    const loc = dir.exp.loc;
    if (loc.end.offset < dir.loc.end.offset) {
      loc.start.offset--;
      loc.start.column--;
      loc.end.offset++;
      loc.end.column++;
    }
    attr.value = {
      type: 2,
      content: dir.exp.content,
      loc
    };
  }
  return attr;
}
function createExp(content, isStatic = false, loc, constType = 0, parseMode = 0) {
  const exp = createSimpleExpression(content, isStatic, loc, constType);
  return exp;
}
function emitError(code2, index, message) {
  currentOptions.onError(
    createCompilerError(code2, getLoc(index, index))
  );
}
function reset() {
  tokenizer.reset();
  currentOpenTag = null;
  currentProp = null;
  currentAttrValue = "";
  currentAttrStartIndex = -1;
  currentAttrEndIndex = -1;
  stack.length = 0;
}
function baseParse(input, options) {
  reset();
  currentInput = input;
  currentOptions = extend({}, defaultParserOptions);
  if (options) {
    let key;
    for (key in options) {
      if (options[key] != null) {
        currentOptions[key] = options[key];
      }
    }
  }
  tokenizer.mode = currentOptions.parseMode === "html" ? 1 : currentOptions.parseMode === "sfc" ? 2 : 0;
  tokenizer.inXML = currentOptions.ns === 1 || currentOptions.ns === 2;
  const delimiters = options && options.delimiters;
  if (delimiters) {
    tokenizer.delimiterOpen = toCharCodes(delimiters[0]);
    tokenizer.delimiterClose = toCharCodes(delimiters[1]);
  }
  const root = currentRoot = createRoot([], input);
  tokenizer.parse(currentInput);
  root.loc = getLoc(0, input.length);
  root.children = condenseWhitespace(root.children);
  currentRoot = null;
  return root;
}
function cacheStatic(root, context) {
  walk(
    root,
    void 0,
    context,
    // Root node is unfortunately non-hoistable due to potential parent
    // fallthrough attributes.
    !!getSingleElementRoot(root)
  );
}
function getSingleElementRoot(root) {
  const children = root.children.filter((x2) => x2.type !== 3);
  return children.length === 1 && children[0].type === 1 && !isSlotOutlet(children[0]) ? children[0] : null;
}
function walk(node, parent, context, doNotHoistNode = false, inFor = false) {
  const { children } = node;
  const toCache = [];
  for (let i = 0; i < children.length; i++) {
    const child = children[i];
    if (child.type === 1 && child.tagType === 0) {
      const constantType = doNotHoistNode ? 0 : getConstantType(child, context);
      if (constantType > 0) {
        if (constantType >= 2) {
          child.codegenNode.patchFlag = -1;
          toCache.push(child);
          continue;
        }
      } else {
        const codegenNode = child.codegenNode;
        if (codegenNode.type === 13) {
          const flag = codegenNode.patchFlag;
          if ((flag === void 0 || flag === 512 || flag === 1) && getGeneratedPropsConstantType(child, context) >= 2) {
            const props = getNodeProps(child);
            if (props) {
              codegenNode.props = context.hoist(props);
            }
          }
          if (codegenNode.dynamicProps) {
            codegenNode.dynamicProps = context.hoist(codegenNode.dynamicProps);
          }
        }
      }
    } else if (child.type === 12) {
      const constantType = doNotHoistNode ? 0 : getConstantType(child, context);
      if (constantType >= 2) {
        if (child.codegenNode.type === 14 && child.codegenNode.arguments.length > 0) {
          child.codegenNode.arguments.push(
            `-1`
          );
        }
        toCache.push(child);
        continue;
      }
    }
    if (child.type === 1) {
      const isComponent2 = child.tagType === 1;
      if (isComponent2) {
        context.scopes.vSlot++;
      }
      walk(child, node, context, false, inFor);
      if (isComponent2) {
        context.scopes.vSlot--;
      }
    } else if (child.type === 11) {
      walk(child, node, context, child.children.length === 1, true);
    } else if (child.type === 9) {
      for (let i2 = 0; i2 < child.branches.length; i2++) {
        walk(
          child.branches[i2],
          node,
          context,
          child.branches[i2].children.length === 1,
          inFor
        );
      }
    }
  }
  let cachedAsArray = false;
  if (toCache.length === children.length && node.type === 1) {
    if (node.tagType === 0 && node.codegenNode && node.codegenNode.type === 13 && isArray$2(node.codegenNode.children)) {
      node.codegenNode.children = getCacheExpression(
        createArrayExpression(node.codegenNode.children)
      );
      cachedAsArray = true;
    } else if (node.tagType === 1 && node.codegenNode && node.codegenNode.type === 13 && node.codegenNode.children && !isArray$2(node.codegenNode.children) && node.codegenNode.children.type === 15) {
      const slot = getSlotNode(node.codegenNode, "default");
      if (slot) {
        slot.returns = getCacheExpression(
          createArrayExpression(slot.returns)
        );
        cachedAsArray = true;
      }
    } else if (node.tagType === 3 && parent && parent.type === 1 && parent.tagType === 1 && parent.codegenNode && parent.codegenNode.type === 13 && parent.codegenNode.children && !isArray$2(parent.codegenNode.children) && parent.codegenNode.children.type === 15) {
      const slotName = findDir(node, "slot", true);
      const slot = slotName && slotName.arg && getSlotNode(parent.codegenNode, slotName.arg);
      if (slot) {
        slot.returns = getCacheExpression(
          createArrayExpression(slot.returns)
        );
        cachedAsArray = true;
      }
    }
  }
  if (!cachedAsArray) {
    for (const child of toCache) {
      child.codegenNode = context.cache(child.codegenNode);
    }
  }
  function getCacheExpression(value) {
    const exp = context.cache(value);
    exp.needArraySpread = true;
    return exp;
  }
  function getSlotNode(node2, name) {
    if (node2.children && !isArray$2(node2.children) && node2.children.type === 15) {
      const slot = node2.children.properties.find(
        (p2) => p2.key === name || p2.key.content === name
      );
      return slot && slot.value;
    }
  }
  if (toCache.length && context.transformHoist) {
    context.transformHoist(children, context, node);
  }
}
function getConstantType(node, context) {
  const { constantCache } = context;
  switch (node.type) {
    case 1:
      if (node.tagType !== 0) {
        return 0;
      }
      const cached = constantCache.get(node);
      if (cached !== void 0) {
        return cached;
      }
      const codegenNode = node.codegenNode;
      if (codegenNode.type !== 13) {
        return 0;
      }
      if (codegenNode.isBlock && node.tag !== "svg" && node.tag !== "foreignObject" && node.tag !== "math") {
        return 0;
      }
      if (codegenNode.patchFlag === void 0) {
        let returnType2 = 3;
        const generatedPropsType = getGeneratedPropsConstantType(node, context);
        if (generatedPropsType === 0) {
          constantCache.set(node, 0);
          return 0;
        }
        if (generatedPropsType < returnType2) {
          returnType2 = generatedPropsType;
        }
        for (let i = 0; i < node.children.length; i++) {
          const childType = getConstantType(node.children[i], context);
          if (childType === 0) {
            constantCache.set(node, 0);
            return 0;
          }
          if (childType < returnType2) {
            returnType2 = childType;
          }
        }
        if (returnType2 > 1) {
          for (let i = 0; i < node.props.length; i++) {
            const p2 = node.props[i];
            if (p2.type === 7 && p2.name === "bind" && p2.exp) {
              const expType = getConstantType(p2.exp, context);
              if (expType === 0) {
                constantCache.set(node, 0);
                return 0;
              }
              if (expType < returnType2) {
                returnType2 = expType;
              }
            }
          }
        }
        if (codegenNode.isBlock) {
          for (let i = 0; i < node.props.length; i++) {
            const p2 = node.props[i];
            if (p2.type === 7) {
              constantCache.set(node, 0);
              return 0;
            }
          }
          context.removeHelper(OPEN_BLOCK);
          context.removeHelper(
            getVNodeBlockHelper(context.inSSR, codegenNode.isComponent)
          );
          codegenNode.isBlock = false;
          context.helper(getVNodeHelper(context.inSSR, codegenNode.isComponent));
        }
        constantCache.set(node, returnType2);
        return returnType2;
      } else {
        constantCache.set(node, 0);
        return 0;
      }
    case 2:
    case 3:
      return 3;
    case 9:
    case 11:
    case 10:
      return 0;
    case 5:
    case 12:
      return getConstantType(node.content, context);
    case 4:
      return node.constType;
    case 8:
      let returnType = 3;
      for (let i = 0; i < node.children.length; i++) {
        const child = node.children[i];
        if (isString(child) || isSymbol(child)) {
          continue;
        }
        const childType = getConstantType(child, context);
        if (childType === 0) {
          return 0;
        } else if (childType < returnType) {
          returnType = childType;
        }
      }
      return returnType;
    case 20:
      return 2;
    default:
      return 0;
  }
}
const allowHoistedHelperSet = /* @__PURE__ */ new Set([
  NORMALIZE_CLASS,
  NORMALIZE_STYLE,
  NORMALIZE_PROPS,
  GUARD_REACTIVE_PROPS
]);
function getConstantTypeOfHelperCall(value, context) {
  if (value.type === 14 && !isString(value.callee) && allowHoistedHelperSet.has(value.callee)) {
    const arg = value.arguments[0];
    if (arg.type === 4) {
      return getConstantType(arg, context);
    } else if (arg.type === 14) {
      return getConstantTypeOfHelperCall(arg, context);
    }
  }
  return 0;
}
function getGeneratedPropsConstantType(node, context) {
  let returnType = 3;
  const props = getNodeProps(node);
  if (props && props.type === 15) {
    const { properties } = props;
    for (let i = 0; i < properties.length; i++) {
      const { key, value } = properties[i];
      const keyType = getConstantType(key, context);
      if (keyType === 0) {
        return keyType;
      }
      if (keyType < returnType) {
        returnType = keyType;
      }
      let valueType;
      if (value.type === 4) {
        valueType = getConstantType(value, context);
      } else if (value.type === 14) {
        valueType = getConstantTypeOfHelperCall(value, context);
      } else {
        valueType = 0;
      }
      if (valueType === 0) {
        return valueType;
      }
      if (valueType < returnType) {
        returnType = valueType;
      }
    }
  }
  return returnType;
}
function getNodeProps(node) {
  const codegenNode = node.codegenNode;
  if (codegenNode.type === 13) {
    return codegenNode.props;
  }
}
function createTransformContext(root, {
  filename = "",
  prefixIdentifiers = false,
  hoistStatic = false,
  hmr = false,
  cacheHandlers = false,
  nodeTransforms = [],
  directiveTransforms = {},
  transformHoist = null,
  isBuiltInComponent = NOOP,
  isCustomElement = NOOP,
  expressionPlugins = [],
  scopeId = null,
  slotted = true,
  ssr = false,
  inSSR = false,
  ssrCssVars = ``,
  bindingMetadata = EMPTY_OBJ,
  inline = false,
  isTS = false,
  onError = defaultOnError,
  onWarn = defaultOnWarn,
  compatConfig
}) {
  const nameMatch = filename.replace(/\?.*$/, "").match(/([^/\\]+)\.\w+$/);
  const context = {
    // options
    filename,
    selfName: nameMatch && capitalize(camelize(nameMatch[1])),
    prefixIdentifiers,
    hoistStatic,
    hmr,
    cacheHandlers,
    nodeTransforms,
    directiveTransforms,
    transformHoist,
    isBuiltInComponent,
    isCustomElement,
    expressionPlugins,
    scopeId,
    slotted,
    ssr,
    inSSR,
    ssrCssVars,
    bindingMetadata,
    inline,
    isTS,
    onError,
    onWarn,
    compatConfig,
    // state
    root,
    helpers: /* @__PURE__ */ new Map(),
    components: /* @__PURE__ */ new Set(),
    directives: /* @__PURE__ */ new Set(),
    hoists: [],
    imports: [],
    cached: [],
    constantCache: /* @__PURE__ */ new WeakMap(),
    temps: 0,
    identifiers: /* @__PURE__ */ Object.create(null),
    scopes: {
      vFor: 0,
      vSlot: 0,
      vPre: 0,
      vOnce: 0
    },
    parent: null,
    grandParent: null,
    currentNode: root,
    childIndex: 0,
    inVOnce: false,
    // methods
    helper(name) {
      const count = context.helpers.get(name) || 0;
      context.helpers.set(name, count + 1);
      return name;
    },
    removeHelper(name) {
      const count = context.helpers.get(name);
      if (count) {
        const currentCount = count - 1;
        if (!currentCount) {
          context.helpers.delete(name);
        } else {
          context.helpers.set(name, currentCount);
        }
      }
    },
    helperString(name) {
      return `_${helperNameMap[context.helper(name)]}`;
    },
    replaceNode(node) {
      context.parent.children[context.childIndex] = context.currentNode = node;
    },
    removeNode(node) {
      const list = context.parent.children;
      const removalIndex = node ? list.indexOf(node) : context.currentNode ? context.childIndex : -1;
      if (!node || node === context.currentNode) {
        context.currentNode = null;
        context.onNodeRemoved();
      } else {
        if (context.childIndex > removalIndex) {
          context.childIndex--;
          context.onNodeRemoved();
        }
      }
      context.parent.children.splice(removalIndex, 1);
    },
    onNodeRemoved: NOOP,
    addIdentifiers(exp) {
    },
    removeIdentifiers(exp) {
    },
    hoist(exp) {
      if (isString(exp)) exp = createSimpleExpression(exp);
      context.hoists.push(exp);
      const identifier = createSimpleExpression(
        `_hoisted_${context.hoists.length}`,
        false,
        exp.loc,
        2
      );
      identifier.hoisted = exp;
      return identifier;
    },
    cache(exp, isVNode2 = false, inVOnce = false) {
      const cacheExp = createCacheExpression(
        context.cached.length,
        exp,
        isVNode2,
        inVOnce
      );
      context.cached.push(cacheExp);
      return cacheExp;
    }
  };
  {
    context.filters = /* @__PURE__ */ new Set();
  }
  return context;
}
function transform(root, options) {
  const context = createTransformContext(root, options);
  traverseNode(root, context);
  if (options.hoistStatic) {
    cacheStatic(root, context);
  }
  if (!options.ssr) {
    createRootCodegen(root, context);
  }
  root.helpers = /* @__PURE__ */ new Set([...context.helpers.keys()]);
  root.components = [...context.components];
  root.directives = [...context.directives];
  root.imports = context.imports;
  root.hoists = context.hoists;
  root.temps = context.temps;
  root.cached = context.cached;
  root.transformed = true;
  {
    root.filters = [...context.filters];
  }
}
function createRootCodegen(root, context) {
  const { helper } = context;
  const { children } = root;
  if (children.length === 1) {
    const singleElementRootChild = getSingleElementRoot(root);
    if (singleElementRootChild && singleElementRootChild.codegenNode) {
      const codegenNode = singleElementRootChild.codegenNode;
      if (codegenNode.type === 13) {
        convertToBlock(codegenNode, context);
      }
      root.codegenNode = codegenNode;
    } else {
      root.codegenNode = children[0];
    }
  } else if (children.length > 1) {
    let patchFlag = 64;
    root.codegenNode = createVNodeCall(
      context,
      helper(FRAGMENT),
      void 0,
      root.children,
      patchFlag,
      void 0,
      void 0,
      true,
      void 0,
      false
    );
  } else ;
}
function traverseChildren(parent, context) {
  let i = 0;
  const nodeRemoved = () => {
    i--;
  };
  for (; i < parent.children.length; i++) {
    const child = parent.children[i];
    if (isString(child)) continue;
    context.grandParent = context.parent;
    context.parent = parent;
    context.childIndex = i;
    context.onNodeRemoved = nodeRemoved;
    traverseNode(child, context);
  }
}
function traverseNode(node, context) {
  context.currentNode = node;
  const { nodeTransforms } = context;
  const exitFns = [];
  for (let i2 = 0; i2 < nodeTransforms.length; i2++) {
    const onExit = nodeTransforms[i2](node, context);
    if (onExit) {
      if (isArray$2(onExit)) {
        exitFns.push(...onExit);
      } else {
        exitFns.push(onExit);
      }
    }
    if (!context.currentNode) {
      return;
    } else {
      node = context.currentNode;
    }
  }
  switch (node.type) {
    case 3:
      if (!context.ssr) {
        context.helper(CREATE_COMMENT);
      }
      break;
    case 5:
      if (!context.ssr) {
        context.helper(TO_DISPLAY_STRING);
      }
      break;
    // for container types, further traverse downwards
    case 9:
      for (let i2 = 0; i2 < node.branches.length; i2++) {
        traverseNode(node.branches[i2], context);
      }
      break;
    case 10:
    case 11:
    case 1:
    case 0:
      traverseChildren(node, context);
      break;
  }
  context.currentNode = node;
  let i = exitFns.length;
  while (i--) {
    exitFns[i]();
  }
}
function createStructuralDirectiveTransform(name, fn) {
  const matches = isString(name) ? (n) => n === name : (n) => name.test(n);
  return (node, context) => {
    if (node.type === 1) {
      const { props } = node;
      if (node.tagType === 3 && props.some(isVSlot)) {
        return;
      }
      const exitFns = [];
      for (let i = 0; i < props.length; i++) {
        const prop = props[i];
        if (prop.type === 7 && matches(prop.name)) {
          props.splice(i, 1);
          i--;
          const onExit = fn(node, prop, context);
          if (onExit) exitFns.push(onExit);
        }
      }
      return exitFns;
    }
  };
}
const PURE_ANNOTATION = `/*@__PURE__*/`;
const aliasHelper = (s) => `${helperNameMap[s]}: _${helperNameMap[s]}`;
function createCodegenContext(ast, {
  mode = "function",
  prefixIdentifiers = mode === "module",
  sourceMap = false,
  filename = `template.vue.html`,
  scopeId = null,
  optimizeImports = false,
  runtimeGlobalName = `Vue`,
  runtimeModuleName = `vue`,
  ssrRuntimeModuleName = "vue/server-renderer",
  ssr = false,
  isTS = false,
  inSSR = false
}) {
  const context = {
    mode,
    prefixIdentifiers,
    sourceMap,
    filename,
    scopeId,
    optimizeImports,
    runtimeGlobalName,
    runtimeModuleName,
    ssrRuntimeModuleName,
    ssr,
    isTS,
    inSSR,
    source: ast.source,
    code: ``,
    column: 1,
    line: 1,
    offset: 0,
    indentLevel: 0,
    pure: false,
    map: void 0,
    helper(key) {
      return `_${helperNameMap[key]}`;
    },
    push(code2, newlineIndex = -2, node) {
      context.code += code2;
    },
    indent() {
      newline(++context.indentLevel);
    },
    deindent(withoutNewLine = false) {
      if (withoutNewLine) {
        --context.indentLevel;
      } else {
        newline(--context.indentLevel);
      }
    },
    newline() {
      newline(context.indentLevel);
    }
  };
  function newline(n) {
    context.push(
      "\n" + `  `.repeat(n),
      0
      /* Start */
    );
  }
  return context;
}
function generate(ast, options = {}) {
  const context = createCodegenContext(ast, options);
  if (options.onContextCreated) options.onContextCreated(context);
  const {
    mode,
    push,
    prefixIdentifiers,
    indent,
    deindent,
    newline,
    scopeId,
    ssr
  } = context;
  const helpers = Array.from(ast.helpers);
  const hasHelpers = helpers.length > 0;
  const useWithBlock = !prefixIdentifiers && mode !== "module";
  const preambleContext = context;
  {
    genFunctionPreamble(ast, preambleContext);
  }
  const functionName = ssr ? `ssrRender` : `render`;
  const args = ssr ? ["_ctx", "_push", "_parent", "_attrs"] : ["_ctx", "_cache"];
  const signature = args.join(", ");
  {
    push(`function ${functionName}(${signature}) {`);
  }
  indent();
  if (useWithBlock) {
    push(`with (_ctx) {`);
    indent();
    if (hasHelpers) {
      push(
        `const { ${helpers.map(aliasHelper).join(", ")} } = _Vue
`,
        -1
        /* End */
      );
      newline();
    }
  }
  if (ast.components.length) {
    genAssets(ast.components, "component", context);
    if (ast.directives.length || ast.temps > 0) {
      newline();
    }
  }
  if (ast.directives.length) {
    genAssets(ast.directives, "directive", context);
    if (ast.temps > 0) {
      newline();
    }
  }
  if (ast.filters && ast.filters.length) {
    newline();
    genAssets(ast.filters, "filter", context);
    newline();
  }
  if (ast.temps > 0) {
    push(`let `);
    for (let i = 0; i < ast.temps; i++) {
      push(`${i > 0 ? `, ` : ``}_temp${i}`);
    }
  }
  if (ast.components.length || ast.directives.length || ast.temps) {
    push(
      `
`,
      0
      /* Start */
    );
    newline();
  }
  if (!ssr) {
    push(`return `);
  }
  if (ast.codegenNode) {
    genNode(ast.codegenNode, context);
  } else {
    push(`null`);
  }
  if (useWithBlock) {
    deindent();
    push(`}`);
  }
  deindent();
  push(`}`);
  return {
    ast,
    code: context.code,
    preamble: ``,
    map: context.map ? context.map.toJSON() : void 0
  };
}
function genFunctionPreamble(ast, context) {
  const {
    ssr,
    prefixIdentifiers,
    push,
    newline,
    runtimeModuleName,
    runtimeGlobalName,
    ssrRuntimeModuleName
  } = context;
  const VueBinding = runtimeGlobalName;
  const helpers = Array.from(ast.helpers);
  if (helpers.length > 0) {
    {
      push(
        `const _Vue = ${VueBinding}
`,
        -1
        /* End */
      );
      if (ast.hoists.length) {
        const staticHelpers = [
          CREATE_VNODE,
          CREATE_ELEMENT_VNODE,
          CREATE_COMMENT,
          CREATE_TEXT,
          CREATE_STATIC
        ].filter((helper) => helpers.includes(helper)).map(aliasHelper).join(", ");
        push(
          `const { ${staticHelpers} } = _Vue
`,
          -1
          /* End */
        );
      }
    }
  }
  genHoists(ast.hoists, context);
  newline();
  push(`return `);
}
function genAssets(assets, type, { helper, push, newline, isTS }) {
  const resolver = helper(
    type === "filter" ? RESOLVE_FILTER : type === "component" ? RESOLVE_COMPONENT : RESOLVE_DIRECTIVE
  );
  for (let i = 0; i < assets.length; i++) {
    let id = assets[i];
    const maybeSelfReference = id.endsWith("__self");
    if (maybeSelfReference) {
      id = id.slice(0, -6);
    }
    push(
      `const ${toValidAssetId(id, type)} = ${resolver}(${JSON.stringify(id)}${maybeSelfReference ? `, true` : ``})${isTS ? `!` : ``}`
    );
    if (i < assets.length - 1) {
      newline();
    }
  }
}
function genHoists(hoists, context) {
  if (!hoists.length) {
    return;
  }
  context.pure = true;
  const { push, newline } = context;
  newline();
  for (let i = 0; i < hoists.length; i++) {
    const exp = hoists[i];
    if (exp) {
      push(`const _hoisted_${i + 1} = `);
      genNode(exp, context);
      newline();
    }
  }
  context.pure = false;
}
function genNodeListAsArray(nodes, context) {
  const multilines = nodes.length > 3 || false;
  context.push(`[`);
  multilines && context.indent();
  genNodeList(nodes, context, multilines);
  multilines && context.deindent();
  context.push(`]`);
}
function genNodeList(nodes, context, multilines = false, comma = true) {
  const { push, newline } = context;
  for (let i = 0; i < nodes.length; i++) {
    const node = nodes[i];
    if (isString(node)) {
      push(
        node,
        -3
        /* Unknown */
      );
    } else if (isArray$2(node)) {
      genNodeListAsArray(node, context);
    } else {
      genNode(node, context);
    }
    if (i < nodes.length - 1) {
      if (multilines) {
        comma && push(",");
        newline();
      } else {
        comma && push(", ");
      }
    }
  }
}
function genNode(node, context) {
  if (isString(node)) {
    context.push(
      node,
      -3
      /* Unknown */
    );
    return;
  }
  if (isSymbol(node)) {
    context.push(context.helper(node));
    return;
  }
  switch (node.type) {
    case 1:
    case 9:
    case 11:
      genNode(node.codegenNode, context);
      break;
    case 2:
      genText(node, context);
      break;
    case 4:
      genExpression(node, context);
      break;
    case 5:
      genInterpolation(node, context);
      break;
    case 12:
      genNode(node.codegenNode, context);
      break;
    case 8:
      genCompoundExpression(node, context);
      break;
    case 3:
      genComment(node, context);
      break;
    case 13:
      genVNodeCall(node, context);
      break;
    case 14:
      genCallExpression(node, context);
      break;
    case 15:
      genObjectExpression(node, context);
      break;
    case 17:
      genArrayExpression(node, context);
      break;
    case 18:
      genFunctionExpression(node, context);
      break;
    case 19:
      genConditionalExpression(node, context);
      break;
    case 20:
      genCacheExpression(node, context);
      break;
    case 21:
      genNodeList(node.body, context, true, false);
      break;
  }
}
function genText(node, context) {
  context.push(JSON.stringify(node.content), -3, node);
}
function genExpression(node, context) {
  const { content, isStatic } = node;
  context.push(
    isStatic ? JSON.stringify(content) : content,
    -3,
    node
  );
}
function genInterpolation(node, context) {
  const { push, helper, pure } = context;
  if (pure) push(PURE_ANNOTATION);
  push(`${helper(TO_DISPLAY_STRING)}(`);
  genNode(node.content, context);
  push(`)`);
}
function genCompoundExpression(node, context) {
  for (let i = 0; i < node.children.length; i++) {
    const child = node.children[i];
    if (isString(child)) {
      context.push(
        child,
        -3
        /* Unknown */
      );
    } else {
      genNode(child, context);
    }
  }
}
function genExpressionAsPropertyKey(node, context) {
  const { push } = context;
  if (node.type === 8) {
    push(`[`);
    genCompoundExpression(node, context);
    push(`]`);
  } else if (node.isStatic) {
    const text2 = isSimpleIdentifier(node.content) ? node.content : JSON.stringify(node.content);
    push(text2, -2, node);
  } else {
    push(`[${node.content}]`, -3, node);
  }
}
function genComment(node, context) {
  const { push, helper, pure } = context;
  if (pure) {
    push(PURE_ANNOTATION);
  }
  push(
    `${helper(CREATE_COMMENT)}(${JSON.stringify(node.content)})`,
    -3,
    node
  );
}
function genVNodeCall(node, context) {
  const { push, helper, pure } = context;
  const {
    tag,
    props,
    children,
    patchFlag,
    dynamicProps,
    directives,
    isBlock,
    disableTracking,
    isComponent: isComponent2
  } = node;
  let patchFlagString;
  if (patchFlag) {
    {
      patchFlagString = String(patchFlag);
    }
  }
  if (directives) {
    push(helper(WITH_DIRECTIVES) + `(`);
  }
  if (isBlock) {
    push(`(${helper(OPEN_BLOCK)}(${disableTracking ? `true` : ``}), `);
  }
  if (pure) {
    push(PURE_ANNOTATION);
  }
  const callHelper = isBlock ? getVNodeBlockHelper(context.inSSR, isComponent2) : getVNodeHelper(context.inSSR, isComponent2);
  push(helper(callHelper) + `(`, -2, node);
  genNodeList(
    genNullableArgs([tag, props, children, patchFlagString, dynamicProps]),
    context
  );
  push(`)`);
  if (isBlock) {
    push(`)`);
  }
  if (directives) {
    push(`, `);
    genNode(directives, context);
    push(`)`);
  }
}
function genNullableArgs(args) {
  let i = args.length;
  while (i--) {
    if (args[i] != null) break;
  }
  return args.slice(0, i + 1).map((arg) => arg || `null`);
}
function genCallExpression(node, context) {
  const { push, helper, pure } = context;
  const callee = isString(node.callee) ? node.callee : helper(node.callee);
  if (pure) {
    push(PURE_ANNOTATION);
  }
  push(callee + `(`, -2, node);
  genNodeList(node.arguments, context);
  push(`)`);
}
function genObjectExpression(node, context) {
  const { push, indent, deindent, newline } = context;
  const { properties } = node;
  if (!properties.length) {
    push(`{}`, -2, node);
    return;
  }
  const multilines = properties.length > 1 || false;
  push(multilines ? `{` : `{ `);
  multilines && indent();
  for (let i = 0; i < properties.length; i++) {
    const { key, value } = properties[i];
    genExpressionAsPropertyKey(key, context);
    push(`: `);
    genNode(value, context);
    if (i < properties.length - 1) {
      push(`,`);
      newline();
    }
  }
  multilines && deindent();
  push(multilines ? `}` : ` }`);
}
function genArrayExpression(node, context) {
  genNodeListAsArray(node.elements, context);
}
function genFunctionExpression(node, context) {
  const { push, indent, deindent } = context;
  const { params, returns, body, newline, isSlot } = node;
  if (isSlot) {
    push(`_${helperNameMap[WITH_CTX]}(`);
  }
  push(`(`, -2, node);
  if (isArray$2(params)) {
    genNodeList(params, context);
  } else if (params) {
    genNode(params, context);
  }
  push(`) => `);
  if (newline || body) {
    push(`{`);
    indent();
  }
  if (returns) {
    if (newline) {
      push(`return `);
    }
    if (isArray$2(returns)) {
      genNodeListAsArray(returns, context);
    } else {
      genNode(returns, context);
    }
  } else if (body) {
    genNode(body, context);
  }
  if (newline || body) {
    deindent();
    push(`}`);
  }
  if (isSlot) {
    if (node.isNonScopedSlot) {
      push(`, undefined, true`);
    }
    push(`)`);
  }
}
function genConditionalExpression(node, context) {
  const { test, consequent, alternate, newline: needNewline } = node;
  const { push, indent, deindent, newline } = context;
  if (test.type === 4) {
    const needsParens = !isSimpleIdentifier(test.content);
    needsParens && push(`(`);
    genExpression(test, context);
    needsParens && push(`)`);
  } else {
    push(`(`);
    genNode(test, context);
    push(`)`);
  }
  needNewline && indent();
  context.indentLevel++;
  needNewline || push(` `);
  push(`? `);
  genNode(consequent, context);
  context.indentLevel--;
  needNewline && newline();
  needNewline || push(` `);
  push(`: `);
  const isNested = alternate.type === 19;
  if (!isNested) {
    context.indentLevel++;
  }
  genNode(alternate, context);
  if (!isNested) {
    context.indentLevel--;
  }
  needNewline && deindent(
    true
    /* without newline */
  );
}
function genCacheExpression(node, context) {
  const { push, helper, indent, deindent, newline } = context;
  const { needPauseTracking, needArraySpread } = node;
  if (needArraySpread) {
    push(`[...(`);
  }
  push(`_cache[${node.index}] || (`);
  if (needPauseTracking) {
    indent();
    push(`${helper(SET_BLOCK_TRACKING)}(-1`);
    if (node.inVOnce) push(`, true`);
    push(`),`);
    newline();
    push(`(`);
  }
  push(`_cache[${node.index}] = `);
  genNode(node.value, context);
  if (needPauseTracking) {
    push(`).cacheIndex = ${node.index},`);
    newline();
    push(`${helper(SET_BLOCK_TRACKING)}(1),`);
    newline();
    push(`_cache[${node.index}]`);
    deindent();
  }
  push(`)`);
  if (needArraySpread) {
    push(`)]`);
  }
}
new RegExp(
  "\\b" + "arguments,await,break,case,catch,class,const,continue,debugger,default,delete,do,else,export,extends,finally,for,function,if,import,let,new,return,super,switch,throw,try,var,void,while,with,yield".split(",").join("\\b|\\b") + "\\b"
);
const transformExpression = (node, context) => {
  if (node.type === 5) {
    node.content = processExpression(
      node.content,
      context
    );
  } else if (node.type === 1) {
    const memo = findDir(node, "memo");
    for (let i = 0; i < node.props.length; i++) {
      const dir = node.props[i];
      if (dir.type === 7 && dir.name !== "for") {
        const exp = dir.exp;
        const arg = dir.arg;
        if (exp && exp.type === 4 && !(dir.name === "on" && arg) && // key has been processed in transformFor(vMemo + vFor)
        !(memo && arg && arg.type === 4 && arg.content === "key")) {
          dir.exp = processExpression(
            exp,
            context,
            // slot args must be processed as function params
            dir.name === "slot"
          );
        }
        if (arg && arg.type === 4 && !arg.isStatic) {
          dir.arg = processExpression(arg, context);
        }
      }
    }
  }
};
function processExpression(node, context, asParams = false, asRawStatements = false, localVars = Object.create(context.identifiers)) {
  {
    return node;
  }
}
function stringifyExpression(exp) {
  if (isString(exp)) {
    return exp;
  } else if (exp.type === 4) {
    return exp.content;
  } else {
    return exp.children.map(stringifyExpression).join("");
  }
}
const transformIf = createStructuralDirectiveTransform(
  /^(?:if|else|else-if)$/,
  (node, dir, context) => {
    return processIf(node, dir, context, (ifNode, branch, isRoot) => {
      const siblings = context.parent.children;
      let i = siblings.indexOf(ifNode);
      let key = 0;
      while (i-- >= 0) {
        const sibling = siblings[i];
        if (sibling && sibling.type === 9) {
          key += sibling.branches.length;
        }
      }
      return () => {
        if (isRoot) {
          ifNode.codegenNode = createCodegenNodeForBranch(
            branch,
            key,
            context
          );
        } else {
          const parentCondition = getParentCondition(ifNode.codegenNode);
          parentCondition.alternate = createCodegenNodeForBranch(
            branch,
            key + ifNode.branches.length - 1,
            context
          );
        }
      };
    });
  }
);
function processIf(node, dir, context, processCodegen) {
  if (dir.name !== "else" && (!dir.exp || !dir.exp.content.trim())) {
    const loc = dir.exp ? dir.exp.loc : node.loc;
    context.onError(
      createCompilerError(28, dir.loc)
    );
    dir.exp = createSimpleExpression(`true`, false, loc);
  }
  if (dir.name === "if") {
    const branch = createIfBranch(node, dir);
    const ifNode = {
      type: 9,
      loc: cloneLoc(node.loc),
      branches: [branch]
    };
    context.replaceNode(ifNode);
    if (processCodegen) {
      return processCodegen(ifNode, branch, true);
    }
  } else {
    const siblings = context.parent.children;
    let i = siblings.indexOf(node);
    while (i-- >= -1) {
      const sibling = siblings[i];
      if (sibling && sibling.type === 3) {
        context.removeNode(sibling);
        continue;
      }
      if (sibling && sibling.type === 2 && !sibling.content.trim().length) {
        context.removeNode(sibling);
        continue;
      }
      if (sibling && sibling.type === 9) {
        if ((dir.name === "else-if" || dir.name === "else") && sibling.branches[sibling.branches.length - 1].condition === void 0) {
          context.onError(
            createCompilerError(30, node.loc)
          );
        }
        context.removeNode();
        const branch = createIfBranch(node, dir);
        sibling.branches.push(branch);
        const onExit = processCodegen && processCodegen(sibling, branch, false);
        traverseNode(branch, context);
        if (onExit) onExit();
        context.currentNode = null;
      } else {
        context.onError(
          createCompilerError(30, node.loc)
        );
      }
      break;
    }
  }
}
function createIfBranch(node, dir) {
  const isTemplateIf = node.tagType === 3;
  return {
    type: 10,
    loc: node.loc,
    condition: dir.name === "else" ? void 0 : dir.exp,
    children: isTemplateIf && !findDir(node, "for") ? node.children : [node],
    userKey: findProp(node, `key`),
    isTemplateIf
  };
}
function createCodegenNodeForBranch(branch, keyIndex, context) {
  if (branch.condition) {
    return createConditionalExpression(
      branch.condition,
      createChildrenCodegenNode(branch, keyIndex, context),
      // make sure to pass in asBlock: true so that the comment node call
      // closes the current block.
      createCallExpression(context.helper(CREATE_COMMENT), [
        '""',
        "true"
      ])
    );
  } else {
    return createChildrenCodegenNode(branch, keyIndex, context);
  }
}
function createChildrenCodegenNode(branch, keyIndex, context) {
  const { helper } = context;
  const keyProperty = createObjectProperty(
    `key`,
    createSimpleExpression(
      `${keyIndex}`,
      false,
      locStub,
      2
    )
  );
  const { children } = branch;
  const firstChild = children[0];
  const needFragmentWrapper = children.length !== 1 || firstChild.type !== 1;
  if (needFragmentWrapper) {
    if (children.length === 1 && firstChild.type === 11) {
      const vnodeCall = firstChild.codegenNode;
      injectProp(vnodeCall, keyProperty, context);
      return vnodeCall;
    } else {
      let patchFlag = 64;
      return createVNodeCall(
        context,
        helper(FRAGMENT),
        createObjectExpression([keyProperty]),
        children,
        patchFlag,
        void 0,
        void 0,
        true,
        false,
        false,
        branch.loc
      );
    }
  } else {
    const ret = firstChild.codegenNode;
    const vnodeCall = getMemoedVNodeCall(ret);
    if (vnodeCall.type === 13) {
      convertToBlock(vnodeCall, context);
    }
    injectProp(vnodeCall, keyProperty, context);
    return ret;
  }
}
function getParentCondition(node) {
  while (true) {
    if (node.type === 19) {
      if (node.alternate.type === 19) {
        node = node.alternate;
      } else {
        return node;
      }
    } else if (node.type === 20) {
      node = node.value;
    }
  }
}
const transformFor = createStructuralDirectiveTransform(
  "for",
  (node, dir, context) => {
    const { helper, removeHelper } = context;
    return processFor(node, dir, context, (forNode) => {
      const renderExp = createCallExpression(helper(RENDER_LIST), [
        forNode.source
      ]);
      const isTemplate = isTemplateNode(node);
      const memo = findDir(node, "memo");
      const keyProp = findProp(node, `key`, false, true);
      keyProp && keyProp.type === 7;
      let keyExp = keyProp && (keyProp.type === 6 ? keyProp.value ? createSimpleExpression(keyProp.value.content, true) : void 0 : keyProp.exp);
      const keyProperty = keyProp && keyExp ? createObjectProperty(`key`, keyExp) : null;
      const isStableFragment = forNode.source.type === 4 && forNode.source.constType > 0;
      const fragmentFlag = isStableFragment ? 64 : keyProp ? 128 : 256;
      forNode.codegenNode = createVNodeCall(
        context,
        helper(FRAGMENT),
        void 0,
        renderExp,
        fragmentFlag,
        void 0,
        void 0,
        true,
        !isStableFragment,
        false,
        node.loc
      );
      return () => {
        let childBlock;
        const { children } = forNode;
        const needFragmentWrapper = children.length !== 1 || children[0].type !== 1;
        const slotOutlet = isSlotOutlet(node) ? node : isTemplate && node.children.length === 1 && isSlotOutlet(node.children[0]) ? node.children[0] : null;
        if (slotOutlet) {
          childBlock = slotOutlet.codegenNode;
          if (isTemplate && keyProperty) {
            injectProp(childBlock, keyProperty, context);
          }
        } else if (needFragmentWrapper) {
          childBlock = createVNodeCall(
            context,
            helper(FRAGMENT),
            keyProperty ? createObjectExpression([keyProperty]) : void 0,
            node.children,
            64,
            void 0,
            void 0,
            true,
            void 0,
            false
          );
        } else {
          childBlock = children[0].codegenNode;
          if (isTemplate && keyProperty) {
            injectProp(childBlock, keyProperty, context);
          }
          if (childBlock.isBlock !== !isStableFragment) {
            if (childBlock.isBlock) {
              removeHelper(OPEN_BLOCK);
              removeHelper(
                getVNodeBlockHelper(context.inSSR, childBlock.isComponent)
              );
            } else {
              removeHelper(
                getVNodeHelper(context.inSSR, childBlock.isComponent)
              );
            }
          }
          childBlock.isBlock = !isStableFragment;
          if (childBlock.isBlock) {
            helper(OPEN_BLOCK);
            helper(getVNodeBlockHelper(context.inSSR, childBlock.isComponent));
          } else {
            helper(getVNodeHelper(context.inSSR, childBlock.isComponent));
          }
        }
        if (memo) {
          const loop = createFunctionExpression(
            createForLoopParams(forNode.parseResult, [
              createSimpleExpression(`_cached`)
            ])
          );
          loop.body = createBlockStatement([
            createCompoundExpression([`const _memo = (`, memo.exp, `)`]),
            createCompoundExpression([
              `if (_cached`,
              ...keyExp ? [` && _cached.key === `, keyExp] : [],
              ` && ${context.helperString(
                IS_MEMO_SAME
              )}(_cached, _memo)) return _cached`
            ]),
            createCompoundExpression([`const _item = `, childBlock]),
            createSimpleExpression(`_item.memo = _memo`),
            createSimpleExpression(`return _item`)
          ]);
          renderExp.arguments.push(
            loop,
            createSimpleExpression(`_cache`),
            createSimpleExpression(String(context.cached.length))
          );
          context.cached.push(null);
        } else {
          renderExp.arguments.push(
            createFunctionExpression(
              createForLoopParams(forNode.parseResult),
              childBlock,
              true
            )
          );
        }
      };
    });
  }
);
function processFor(node, dir, context, processCodegen) {
  if (!dir.exp) {
    context.onError(
      createCompilerError(31, dir.loc)
    );
    return;
  }
  const parseResult = dir.forParseResult;
  if (!parseResult) {
    context.onError(
      createCompilerError(32, dir.loc)
    );
    return;
  }
  finalizeForParseResult(parseResult);
  const { addIdentifiers, removeIdentifiers, scopes } = context;
  const { source, value, key, index } = parseResult;
  const forNode = {
    type: 11,
    loc: dir.loc,
    source,
    valueAlias: value,
    keyAlias: key,
    objectIndexAlias: index,
    parseResult,
    children: isTemplateNode(node) ? node.children : [node]
  };
  context.replaceNode(forNode);
  scopes.vFor++;
  const onExit = processCodegen && processCodegen(forNode);
  return () => {
    scopes.vFor--;
    if (onExit) onExit();
  };
}
function finalizeForParseResult(result, context) {
  if (result.finalized) return;
  result.finalized = true;
}
function createForLoopParams({ value, key, index }, memoArgs = []) {
  return createParamsList([value, key, index, ...memoArgs]);
}
function createParamsList(args) {
  let i = args.length;
  while (i--) {
    if (args[i]) break;
  }
  return args.slice(0, i + 1).map((arg, i2) => arg || createSimpleExpression(`_`.repeat(i2 + 1), false));
}
const defaultFallback = createSimpleExpression(`undefined`, false);
const trackSlotScopes = (node, context) => {
  if (node.type === 1 && (node.tagType === 1 || node.tagType === 3)) {
    const vSlot = findDir(node, "slot");
    if (vSlot) {
      vSlot.exp;
      context.scopes.vSlot++;
      return () => {
        context.scopes.vSlot--;
      };
    }
  }
};
const trackVForSlotScopes = (node, context) => {
  let vFor;
  if (isTemplateNode(node) && node.props.some(isVSlot) && (vFor = findDir(node, "for"))) {
    const result = vFor.forParseResult;
    if (result) {
      finalizeForParseResult(result);
      const { value, key, index } = result;
      const { addIdentifiers, removeIdentifiers } = context;
      value && addIdentifiers(value);
      key && addIdentifiers(key);
      index && addIdentifiers(index);
      return () => {
        value && removeIdentifiers(value);
        key && removeIdentifiers(key);
        index && removeIdentifiers(index);
      };
    }
  }
};
const buildClientSlotFn = (props, _vForExp, children, loc) => createFunctionExpression(
  props,
  children,
  false,
  true,
  children.length ? children[0].loc : loc
);
function buildSlots(node, context, buildSlotFn = buildClientSlotFn) {
  context.helper(WITH_CTX);
  const { children, loc } = node;
  const slotsProperties = [];
  const dynamicSlots = [];
  let hasDynamicSlots = context.scopes.vSlot > 0 || context.scopes.vFor > 0;
  const onComponentSlot = findDir(node, "slot", true);
  if (onComponentSlot) {
    const { arg, exp } = onComponentSlot;
    if (arg && !isStaticExp(arg)) {
      hasDynamicSlots = true;
    }
    slotsProperties.push(
      createObjectProperty(
        arg || createSimpleExpression("default", true),
        buildSlotFn(exp, void 0, children, loc)
      )
    );
  }
  let hasTemplateSlots = false;
  let hasNamedDefaultSlot = false;
  const implicitDefaultChildren = [];
  const seenSlotNames = /* @__PURE__ */ new Set();
  let conditionalBranchIndex = 0;
  for (let i = 0; i < children.length; i++) {
    const slotElement = children[i];
    let slotDir;
    if (!isTemplateNode(slotElement) || !(slotDir = findDir(slotElement, "slot", true))) {
      if (slotElement.type !== 3) {
        implicitDefaultChildren.push(slotElement);
      }
      continue;
    }
    if (onComponentSlot) {
      context.onError(
        createCompilerError(37, slotDir.loc)
      );
      break;
    }
    hasTemplateSlots = true;
    const { children: slotChildren, loc: slotLoc } = slotElement;
    const {
      arg: slotName = createSimpleExpression(`default`, true),
      exp: slotProps,
      loc: dirLoc
    } = slotDir;
    let staticSlotName;
    if (isStaticExp(slotName)) {
      staticSlotName = slotName ? slotName.content : `default`;
    } else {
      hasDynamicSlots = true;
    }
    const vFor = findDir(slotElement, "for");
    const slotFunction = buildSlotFn(slotProps, vFor, slotChildren, slotLoc);
    let vIf;
    let vElse;
    if (vIf = findDir(slotElement, "if")) {
      hasDynamicSlots = true;
      dynamicSlots.push(
        createConditionalExpression(
          vIf.exp,
          buildDynamicSlot(slotName, slotFunction, conditionalBranchIndex++),
          defaultFallback
        )
      );
    } else if (vElse = findDir(
      slotElement,
      /^else(?:-if)?$/,
      true
      /* allowEmpty */
    )) {
      let j2 = i;
      let prev;
      while (j2--) {
        prev = children[j2];
        if (prev.type !== 3 && isNonWhitespaceContent(prev)) {
          break;
        }
      }
      if (prev && isTemplateNode(prev) && findDir(prev, /^(?:else-)?if$/)) {
        let conditional = dynamicSlots[dynamicSlots.length - 1];
        while (conditional.alternate.type === 19) {
          conditional = conditional.alternate;
        }
        conditional.alternate = vElse.exp ? createConditionalExpression(
          vElse.exp,
          buildDynamicSlot(
            slotName,
            slotFunction,
            conditionalBranchIndex++
          ),
          defaultFallback
        ) : buildDynamicSlot(slotName, slotFunction, conditionalBranchIndex++);
      } else {
        context.onError(
          createCompilerError(30, vElse.loc)
        );
      }
    } else if (vFor) {
      hasDynamicSlots = true;
      const parseResult = vFor.forParseResult;
      if (parseResult) {
        finalizeForParseResult(parseResult);
        dynamicSlots.push(
          createCallExpression(context.helper(RENDER_LIST), [
            parseResult.source,
            createFunctionExpression(
              createForLoopParams(parseResult),
              buildDynamicSlot(slotName, slotFunction),
              true
            )
          ])
        );
      } else {
        context.onError(
          createCompilerError(
            32,
            vFor.loc
          )
        );
      }
    } else {
      if (staticSlotName) {
        if (seenSlotNames.has(staticSlotName)) {
          context.onError(
            createCompilerError(
              38,
              dirLoc
            )
          );
          continue;
        }
        seenSlotNames.add(staticSlotName);
        if (staticSlotName === "default") {
          hasNamedDefaultSlot = true;
        }
      }
      slotsProperties.push(createObjectProperty(slotName, slotFunction));
    }
  }
  if (!onComponentSlot) {
    const buildDefaultSlotProperty = (props, children2) => {
      const fn = buildSlotFn(props, void 0, children2, loc);
      if (context.compatConfig) {
        fn.isNonScopedSlot = true;
      }
      return createObjectProperty(`default`, fn);
    };
    if (!hasTemplateSlots) {
      slotsProperties.push(buildDefaultSlotProperty(void 0, children));
    } else if (implicitDefaultChildren.length && // #3766
    // with whitespace: 'preserve', whitespaces between slots will end up in
    // implicitDefaultChildren. Ignore if all implicit children are whitespaces.
    implicitDefaultChildren.some((node2) => isNonWhitespaceContent(node2))) {
      if (hasNamedDefaultSlot) {
        context.onError(
          createCompilerError(
            39,
            implicitDefaultChildren[0].loc
          )
        );
      } else {
        slotsProperties.push(
          buildDefaultSlotProperty(void 0, implicitDefaultChildren)
        );
      }
    }
  }
  const slotFlag = hasDynamicSlots ? 2 : hasForwardedSlots(node.children) ? 3 : 1;
  let slots = createObjectExpression(
    slotsProperties.concat(
      createObjectProperty(
        `_`,
        // 2 = compiled but dynamic = can skip normalization, but must run diff
        // 1 = compiled and static = can skip normalization AND diff as optimized
        createSimpleExpression(
          slotFlag + ``,
          false
        )
      )
    ),
    loc
  );
  if (dynamicSlots.length) {
    slots = createCallExpression(context.helper(CREATE_SLOTS), [
      slots,
      createArrayExpression(dynamicSlots)
    ]);
  }
  return {
    slots,
    hasDynamicSlots
  };
}
function buildDynamicSlot(name, fn, index) {
  const props = [
    createObjectProperty(`name`, name),
    createObjectProperty(`fn`, fn)
  ];
  if (index != null) {
    props.push(
      createObjectProperty(`key`, createSimpleExpression(String(index), true))
    );
  }
  return createObjectExpression(props);
}
function hasForwardedSlots(children) {
  for (let i = 0; i < children.length; i++) {
    const child = children[i];
    switch (child.type) {
      case 1:
        if (child.tagType === 2 || hasForwardedSlots(child.children)) {
          return true;
        }
        break;
      case 9:
        if (hasForwardedSlots(child.branches)) return true;
        break;
      case 10:
      case 11:
        if (hasForwardedSlots(child.children)) return true;
        break;
    }
  }
  return false;
}
function isNonWhitespaceContent(node) {
  if (node.type !== 2 && node.type !== 12)
    return true;
  return node.type === 2 ? !!node.content.trim() : isNonWhitespaceContent(node.content);
}
const directiveImportMap = /* @__PURE__ */ new WeakMap();
const transformElement = (node, context) => {
  return function postTransformElement() {
    node = context.currentNode;
    if (!(node.type === 1 && (node.tagType === 0 || node.tagType === 1))) {
      return;
    }
    const { tag, props } = node;
    const isComponent2 = node.tagType === 1;
    let vnodeTag = isComponent2 ? resolveComponentType(node, context) : `"${tag}"`;
    const isDynamicComponent = isObject$1(vnodeTag) && vnodeTag.callee === RESOLVE_DYNAMIC_COMPONENT;
    let vnodeProps;
    let vnodeChildren;
    let patchFlag = 0;
    let vnodeDynamicProps;
    let dynamicPropNames;
    let vnodeDirectives;
    let shouldUseBlock = (
      // dynamic component may resolve to plain elements
      isDynamicComponent || vnodeTag === TELEPORT || vnodeTag === SUSPENSE || !isComponent2 && // <svg> and <foreignObject> must be forced into blocks so that block
      // updates inside get proper isSVG flag at runtime. (#639, #643)
      // This is technically web-specific, but splitting the logic out of core
      // leads to too much unnecessary complexity.
      (tag === "svg" || tag === "foreignObject" || tag === "math")
    );
    if (props.length > 0) {
      const propsBuildResult = buildProps(
        node,
        context,
        void 0,
        isComponent2,
        isDynamicComponent
      );
      vnodeProps = propsBuildResult.props;
      patchFlag = propsBuildResult.patchFlag;
      dynamicPropNames = propsBuildResult.dynamicPropNames;
      const directives = propsBuildResult.directives;
      vnodeDirectives = directives && directives.length ? createArrayExpression(
        directives.map((dir) => buildDirectiveArgs(dir, context))
      ) : void 0;
      if (propsBuildResult.shouldUseBlock) {
        shouldUseBlock = true;
      }
    }
    if (node.children.length > 0) {
      if (vnodeTag === KEEP_ALIVE) {
        shouldUseBlock = true;
        patchFlag |= 1024;
      }
      const shouldBuildAsSlots = isComponent2 && // Teleport is not a real component and has dedicated runtime handling
      vnodeTag !== TELEPORT && // explained above.
      vnodeTag !== KEEP_ALIVE;
      if (shouldBuildAsSlots) {
        const { slots, hasDynamicSlots } = buildSlots(node, context);
        vnodeChildren = slots;
        if (hasDynamicSlots) {
          patchFlag |= 1024;
        }
      } else if (node.children.length === 1 && vnodeTag !== TELEPORT) {
        const child = node.children[0];
        const type = child.type;
        const hasDynamicTextChild = type === 5 || type === 8;
        if (hasDynamicTextChild && getConstantType(child, context) === 0) {
          patchFlag |= 1;
        }
        if (hasDynamicTextChild || type === 2) {
          vnodeChildren = child;
        } else {
          vnodeChildren = node.children;
        }
      } else {
        vnodeChildren = node.children;
      }
    }
    if (dynamicPropNames && dynamicPropNames.length) {
      vnodeDynamicProps = stringifyDynamicPropNames(dynamicPropNames);
    }
    node.codegenNode = createVNodeCall(
      context,
      vnodeTag,
      vnodeProps,
      vnodeChildren,
      patchFlag === 0 ? void 0 : patchFlag,
      vnodeDynamicProps,
      vnodeDirectives,
      !!shouldUseBlock,
      false,
      isComponent2,
      node.loc
    );
  };
};
function resolveComponentType(node, context, ssr = false) {
  let { tag } = node;
  const isExplicitDynamic = isComponentTag(tag);
  const isProp = findProp(
    node,
    "is",
    false,
    true
    /* allow empty */
  );
  if (isProp) {
    if (isExplicitDynamic || isCompatEnabled(
      "COMPILER_IS_ON_ELEMENT",
      context
    )) {
      let exp;
      if (isProp.type === 6) {
        exp = isProp.value && createSimpleExpression(isProp.value.content, true);
      } else {
        exp = isProp.exp;
        if (!exp) {
          exp = createSimpleExpression(`is`, false, isProp.arg.loc);
        }
      }
      if (exp) {
        return createCallExpression(context.helper(RESOLVE_DYNAMIC_COMPONENT), [
          exp
        ]);
      }
    } else if (isProp.type === 6 && isProp.value.content.startsWith("vue:")) {
      tag = isProp.value.content.slice(4);
    }
  }
  const builtIn = isCoreComponent(tag) || context.isBuiltInComponent(tag);
  if (builtIn) {
    if (!ssr) context.helper(builtIn);
    return builtIn;
  }
  context.helper(RESOLVE_COMPONENT);
  context.components.add(tag);
  return toValidAssetId(tag, `component`);
}
function buildProps(node, context, props = node.props, isComponent2, isDynamicComponent, ssr = false) {
  const { tag, loc: elementLoc, children } = node;
  let properties = [];
  const mergeArgs = [];
  const runtimeDirectives = [];
  const hasChildren = children.length > 0;
  let shouldUseBlock = false;
  let patchFlag = 0;
  let hasRef = false;
  let hasClassBinding = false;
  let hasStyleBinding = false;
  let hasHydrationEventBinding = false;
  let hasDynamicKeys = false;
  let hasVnodeHook = false;
  const dynamicPropNames = [];
  const pushMergeArg = (arg) => {
    if (properties.length) {
      mergeArgs.push(
        createObjectExpression(dedupeProperties(properties), elementLoc)
      );
      properties = [];
    }
    if (arg) mergeArgs.push(arg);
  };
  const pushRefVForMarker = () => {
    if (context.scopes.vFor > 0) {
      properties.push(
        createObjectProperty(
          createSimpleExpression("ref_for", true),
          createSimpleExpression("true")
        )
      );
    }
  };
  const analyzePatchFlag = ({ key, value }) => {
    if (isStaticExp(key)) {
      const name = key.content;
      const isEventHandler = isOn(name);
      if (isEventHandler && (!isComponent2 || isDynamicComponent) && // omit the flag for click handlers because hydration gives click
      // dedicated fast path.
      name.toLowerCase() !== "onclick" && // omit v-model handlers
      name !== "onUpdate:modelValue" && // omit onVnodeXXX hooks
      !isReservedProp(name)) {
        hasHydrationEventBinding = true;
      }
      if (isEventHandler && isReservedProp(name)) {
        hasVnodeHook = true;
      }
      if (isEventHandler && value.type === 14) {
        value = value.arguments[0];
      }
      if (value.type === 20 || (value.type === 4 || value.type === 8) && getConstantType(value, context) > 0) {
        return;
      }
      if (name === "ref") {
        hasRef = true;
      } else if (name === "class") {
        hasClassBinding = true;
      } else if (name === "style") {
        hasStyleBinding = true;
      } else if (name !== "key" && !dynamicPropNames.includes(name)) {
        dynamicPropNames.push(name);
      }
      if (isComponent2 && (name === "class" || name === "style") && !dynamicPropNames.includes(name)) {
        dynamicPropNames.push(name);
      }
    } else {
      hasDynamicKeys = true;
    }
  };
  for (let i = 0; i < props.length; i++) {
    const prop = props[i];
    if (prop.type === 6) {
      const { loc, name, nameLoc, value } = prop;
      let isStatic = true;
      if (name === "ref") {
        hasRef = true;
        pushRefVForMarker();
      }
      if (name === "is" && (isComponentTag(tag) || value && value.content.startsWith("vue:") || isCompatEnabled(
        "COMPILER_IS_ON_ELEMENT",
        context
      ))) {
        continue;
      }
      properties.push(
        createObjectProperty(
          createSimpleExpression(name, true, nameLoc),
          createSimpleExpression(
            value ? value.content : "",
            isStatic,
            value ? value.loc : loc
          )
        )
      );
    } else {
      const { name, arg, exp, loc, modifiers } = prop;
      const isVBind = name === "bind";
      const isVOn = name === "on";
      if (name === "slot") {
        if (!isComponent2) {
          context.onError(
            createCompilerError(40, loc)
          );
        }
        continue;
      }
      if (name === "once" || name === "memo") {
        continue;
      }
      if (name === "is" || isVBind && isStaticArgOf(arg, "is") && (isComponentTag(tag) || isCompatEnabled(
        "COMPILER_IS_ON_ELEMENT",
        context
      ))) {
        continue;
      }
      if (isVOn && ssr) {
        continue;
      }
      if (
        // #938: elements with dynamic keys should be forced into blocks
        isVBind && isStaticArgOf(arg, "key") || // inline before-update hooks need to force block so that it is invoked
        // before children
        isVOn && hasChildren && isStaticArgOf(arg, "vue:before-update")
      ) {
        shouldUseBlock = true;
      }
      if (isVBind && isStaticArgOf(arg, "ref")) {
        pushRefVForMarker();
      }
      if (!arg && (isVBind || isVOn)) {
        hasDynamicKeys = true;
        if (exp) {
          if (isVBind) {
            {
              pushMergeArg();
              if (isCompatEnabled(
                "COMPILER_V_BIND_OBJECT_ORDER",
                context
              )) {
                mergeArgs.unshift(exp);
                continue;
              }
            }
            pushRefVForMarker();
            pushMergeArg();
            mergeArgs.push(exp);
          } else {
            pushMergeArg({
              type: 14,
              loc,
              callee: context.helper(TO_HANDLERS),
              arguments: isComponent2 ? [exp] : [exp, `true`]
            });
          }
        } else {
          context.onError(
            createCompilerError(
              isVBind ? 34 : 35,
              loc
            )
          );
        }
        continue;
      }
      if (isVBind && modifiers.some((mod) => mod.content === "prop")) {
        patchFlag |= 32;
      }
      const directiveTransform = context.directiveTransforms[name];
      if (directiveTransform) {
        const { props: props2, needRuntime } = directiveTransform(prop, node, context);
        !ssr && props2.forEach(analyzePatchFlag);
        if (isVOn && arg && !isStaticExp(arg)) {
          pushMergeArg(createObjectExpression(props2, elementLoc));
        } else {
          properties.push(...props2);
        }
        if (needRuntime) {
          runtimeDirectives.push(prop);
          if (isSymbol(needRuntime)) {
            directiveImportMap.set(prop, needRuntime);
          }
        }
      } else if (!isBuiltInDirective(name)) {
        runtimeDirectives.push(prop);
        if (hasChildren) {
          shouldUseBlock = true;
        }
      }
    }
  }
  let propsExpression = void 0;
  if (mergeArgs.length) {
    pushMergeArg();
    if (mergeArgs.length > 1) {
      propsExpression = createCallExpression(
        context.helper(MERGE_PROPS),
        mergeArgs,
        elementLoc
      );
    } else {
      propsExpression = mergeArgs[0];
    }
  } else if (properties.length) {
    propsExpression = createObjectExpression(
      dedupeProperties(properties),
      elementLoc
    );
  }
  if (hasDynamicKeys) {
    patchFlag |= 16;
  } else {
    if (hasClassBinding && !isComponent2) {
      patchFlag |= 2;
    }
    if (hasStyleBinding && !isComponent2) {
      patchFlag |= 4;
    }
    if (dynamicPropNames.length) {
      patchFlag |= 8;
    }
    if (hasHydrationEventBinding) {
      patchFlag |= 32;
    }
  }
  if (!shouldUseBlock && (patchFlag === 0 || patchFlag === 32) && (hasRef || hasVnodeHook || runtimeDirectives.length > 0)) {
    patchFlag |= 512;
  }
  if (!context.inSSR && propsExpression) {
    switch (propsExpression.type) {
      case 15:
        let classKeyIndex = -1;
        let styleKeyIndex = -1;
        let hasDynamicKey = false;
        for (let i = 0; i < propsExpression.properties.length; i++) {
          const key = propsExpression.properties[i].key;
          if (isStaticExp(key)) {
            if (key.content === "class") {
              classKeyIndex = i;
            } else if (key.content === "style") {
              styleKeyIndex = i;
            }
          } else if (!key.isHandlerKey) {
            hasDynamicKey = true;
          }
        }
        const classProp = propsExpression.properties[classKeyIndex];
        const styleProp = propsExpression.properties[styleKeyIndex];
        if (!hasDynamicKey) {
          if (classProp && !isStaticExp(classProp.value)) {
            classProp.value = createCallExpression(
              context.helper(NORMALIZE_CLASS),
              [classProp.value]
            );
          }
          if (styleProp && // the static style is compiled into an object,
          // so use `hasStyleBinding` to ensure that it is a dynamic style binding
          (hasStyleBinding || styleProp.value.type === 4 && styleProp.value.content.trim()[0] === `[` || // v-bind:style and style both exist,
          // v-bind:style with static literal object
          styleProp.value.type === 17)) {
            styleProp.value = createCallExpression(
              context.helper(NORMALIZE_STYLE),
              [styleProp.value]
            );
          }
        } else {
          propsExpression = createCallExpression(
            context.helper(NORMALIZE_PROPS),
            [propsExpression]
          );
        }
        break;
      case 14:
        break;
      default:
        propsExpression = createCallExpression(
          context.helper(NORMALIZE_PROPS),
          [
            createCallExpression(context.helper(GUARD_REACTIVE_PROPS), [
              propsExpression
            ])
          ]
        );
        break;
    }
  }
  return {
    props: propsExpression,
    directives: runtimeDirectives,
    patchFlag,
    dynamicPropNames,
    shouldUseBlock
  };
}
function dedupeProperties(properties) {
  const knownProps = /* @__PURE__ */ new Map();
  const deduped = [];
  for (let i = 0; i < properties.length; i++) {
    const prop = properties[i];
    if (prop.key.type === 8 || !prop.key.isStatic) {
      deduped.push(prop);
      continue;
    }
    const name = prop.key.content;
    const existing = knownProps.get(name);
    if (existing) {
      if (name === "style" || name === "class" || isOn(name)) {
        mergeAsArray(existing, prop);
      }
    } else {
      knownProps.set(name, prop);
      deduped.push(prop);
    }
  }
  return deduped;
}
function mergeAsArray(existing, incoming) {
  if (existing.value.type === 17) {
    existing.value.elements.push(incoming.value);
  } else {
    existing.value = createArrayExpression(
      [existing.value, incoming.value],
      existing.loc
    );
  }
}
function buildDirectiveArgs(dir, context) {
  const dirArgs = [];
  const runtime = directiveImportMap.get(dir);
  if (runtime) {
    dirArgs.push(context.helperString(runtime));
  } else {
    {
      context.helper(RESOLVE_DIRECTIVE);
      context.directives.add(dir.name);
      dirArgs.push(toValidAssetId(dir.name, `directive`));
    }
  }
  const { loc } = dir;
  if (dir.exp) dirArgs.push(dir.exp);
  if (dir.arg) {
    if (!dir.exp) {
      dirArgs.push(`void 0`);
    }
    dirArgs.push(dir.arg);
  }
  if (Object.keys(dir.modifiers).length) {
    if (!dir.arg) {
      if (!dir.exp) {
        dirArgs.push(`void 0`);
      }
      dirArgs.push(`void 0`);
    }
    const trueExpression = createSimpleExpression(`true`, false, loc);
    dirArgs.push(
      createObjectExpression(
        dir.modifiers.map(
          (modifier) => createObjectProperty(modifier, trueExpression)
        ),
        loc
      )
    );
  }
  return createArrayExpression(dirArgs, dir.loc);
}
function stringifyDynamicPropNames(props) {
  let propsNamesString = `[`;
  for (let i = 0, l = props.length; i < l; i++) {
    propsNamesString += JSON.stringify(props[i]);
    if (i < l - 1) propsNamesString += ", ";
  }
  return propsNamesString + `]`;
}
function isComponentTag(tag) {
  return tag === "component" || tag === "Component";
}
const transformSlotOutlet = (node, context) => {
  if (isSlotOutlet(node)) {
    const { children, loc } = node;
    const { slotName, slotProps } = processSlotOutlet(node, context);
    const slotArgs = [
      context.prefixIdentifiers ? `_ctx.$slots` : `$slots`,
      slotName,
      "{}",
      "undefined",
      "true"
    ];
    let expectedLen = 2;
    if (slotProps) {
      slotArgs[2] = slotProps;
      expectedLen = 3;
    }
    if (children.length) {
      slotArgs[3] = createFunctionExpression([], children, false, false, loc);
      expectedLen = 4;
    }
    if (context.scopeId && !context.slotted) {
      expectedLen = 5;
    }
    slotArgs.splice(expectedLen);
    node.codegenNode = createCallExpression(
      context.helper(RENDER_SLOT),
      slotArgs,
      loc
    );
  }
};
function processSlotOutlet(node, context) {
  let slotName = `"default"`;
  let slotProps = void 0;
  const nonNameProps = [];
  for (let i = 0; i < node.props.length; i++) {
    const p2 = node.props[i];
    if (p2.type === 6) {
      if (p2.value) {
        if (p2.name === "name") {
          slotName = JSON.stringify(p2.value.content);
        } else {
          p2.name = camelize(p2.name);
          nonNameProps.push(p2);
        }
      }
    } else {
      if (p2.name === "bind" && isStaticArgOf(p2.arg, "name")) {
        if (p2.exp) {
          slotName = p2.exp;
        } else if (p2.arg && p2.arg.type === 4) {
          const name = camelize(p2.arg.content);
          slotName = p2.exp = createSimpleExpression(name, false, p2.arg.loc);
        }
      } else {
        if (p2.name === "bind" && p2.arg && isStaticExp(p2.arg)) {
          p2.arg.content = camelize(p2.arg.content);
        }
        nonNameProps.push(p2);
      }
    }
  }
  if (nonNameProps.length > 0) {
    const { props, directives } = buildProps(
      node,
      context,
      nonNameProps,
      false,
      false
    );
    slotProps = props;
    if (directives.length) {
      context.onError(
        createCompilerError(
          36,
          directives[0].loc
        )
      );
    }
  }
  return {
    slotName,
    slotProps
  };
}
const transformOn$1 = (dir, node, context, augmentor) => {
  const { loc, modifiers, arg } = dir;
  if (!dir.exp && !modifiers.length) {
    context.onError(createCompilerError(35, loc));
  }
  let eventName;
  if (arg.type === 4) {
    if (arg.isStatic) {
      let rawName = arg.content;
      if (rawName.startsWith("vue:")) {
        rawName = `vnode-${rawName.slice(4)}`;
      }
      const eventString = node.tagType !== 0 || rawName.startsWith("vnode") || !/[A-Z]/.test(rawName) ? (
        // for non-element and vnode lifecycle event listeners, auto convert
        // it to camelCase. See issue #2249
        toHandlerKey(camelize(rawName))
      ) : (
        // preserve case for plain element listeners that have uppercase
        // letters, as these may be custom elements' custom events
        `on:${rawName}`
      );
      eventName = createSimpleExpression(eventString, true, arg.loc);
    } else {
      eventName = createCompoundExpression([
        `${context.helperString(TO_HANDLER_KEY)}(`,
        arg,
        `)`
      ]);
    }
  } else {
    eventName = arg;
    eventName.children.unshift(`${context.helperString(TO_HANDLER_KEY)}(`);
    eventName.children.push(`)`);
  }
  let exp = dir.exp;
  if (exp && !exp.content.trim()) {
    exp = void 0;
  }
  let shouldCache = context.cacheHandlers && !exp && !context.inVOnce;
  if (exp) {
    const isMemberExp = isMemberExpression(exp);
    const isInlineStatement = !(isMemberExp || isFnExpression(exp));
    const hasMultipleStatements = exp.content.includes(`;`);
    if (isInlineStatement || shouldCache && isMemberExp) {
      exp = createCompoundExpression([
        `${isInlineStatement ? `$event` : `${``}(...args)`} => ${hasMultipleStatements ? `{` : `(`}`,
        exp,
        hasMultipleStatements ? `}` : `)`
      ]);
    }
  }
  let ret = {
    props: [
      createObjectProperty(
        eventName,
        exp || createSimpleExpression(`() => {}`, false, loc)
      )
    ]
  };
  if (augmentor) {
    ret = augmentor(ret);
  }
  if (shouldCache) {
    ret.props[0].value = context.cache(ret.props[0].value);
  }
  ret.props.forEach((p2) => p2.key.isHandlerKey = true);
  return ret;
};
const transformBind = (dir, _node, context) => {
  const { modifiers, loc } = dir;
  const arg = dir.arg;
  let { exp } = dir;
  if (exp && exp.type === 4 && !exp.content.trim()) {
    {
      exp = void 0;
    }
  }
  if (arg.type !== 4) {
    arg.children.unshift(`(`);
    arg.children.push(`) || ""`);
  } else if (!arg.isStatic) {
    arg.content = arg.content ? `${arg.content} || ""` : `""`;
  }
  if (modifiers.some((mod) => mod.content === "camel")) {
    if (arg.type === 4) {
      if (arg.isStatic) {
        arg.content = camelize(arg.content);
      } else {
        arg.content = `${context.helperString(CAMELIZE)}(${arg.content})`;
      }
    } else {
      arg.children.unshift(`${context.helperString(CAMELIZE)}(`);
      arg.children.push(`)`);
    }
  }
  if (!context.inSSR) {
    if (modifiers.some((mod) => mod.content === "prop")) {
      injectPrefix(arg, ".");
    }
    if (modifiers.some((mod) => mod.content === "attr")) {
      injectPrefix(arg, "^");
    }
  }
  return {
    props: [createObjectProperty(arg, exp)]
  };
};
const injectPrefix = (arg, prefix) => {
  if (arg.type === 4) {
    if (arg.isStatic) {
      arg.content = prefix + arg.content;
    } else {
      arg.content = `\`${prefix}\${${arg.content}}\``;
    }
  } else {
    arg.children.unshift(`'${prefix}' + (`);
    arg.children.push(`)`);
  }
};
const transformText = (node, context) => {
  if (node.type === 0 || node.type === 1 || node.type === 11 || node.type === 10) {
    return () => {
      const children = node.children;
      let currentContainer = void 0;
      let hasText = false;
      for (let i = 0; i < children.length; i++) {
        const child = children[i];
        if (isText$1(child)) {
          hasText = true;
          for (let j2 = i + 1; j2 < children.length; j2++) {
            const next = children[j2];
            if (isText$1(next)) {
              if (!currentContainer) {
                currentContainer = children[i] = createCompoundExpression(
                  [child],
                  child.loc
                );
              }
              currentContainer.children.push(` + `, next);
              children.splice(j2, 1);
              j2--;
            } else {
              currentContainer = void 0;
              break;
            }
          }
        }
      }
      if (!hasText || // if this is a plain element with a single text child, leave it
      // as-is since the runtime has dedicated fast path for this by directly
      // setting textContent of the element.
      // for component root it's always normalized anyway.
      children.length === 1 && (node.type === 0 || node.type === 1 && node.tagType === 0 && // #3756
      // custom directives can potentially add DOM elements arbitrarily,
      // we need to avoid setting textContent of the element at runtime
      // to avoid accidentally overwriting the DOM elements added
      // by the user through custom directives.
      !node.props.find(
        (p2) => p2.type === 7 && !context.directiveTransforms[p2.name]
      ) && // in compat mode, <template> tags with no special directives
      // will be rendered as a fragment so its children must be
      // converted into vnodes.
      !(node.tag === "template"))) {
        return;
      }
      for (let i = 0; i < children.length; i++) {
        const child = children[i];
        if (isText$1(child) || child.type === 8) {
          const callArgs = [];
          if (child.type !== 2 || child.content !== " ") {
            callArgs.push(child);
          }
          if (!context.ssr && getConstantType(child, context) === 0) {
            callArgs.push(
              `1`
            );
          }
          children[i] = {
            type: 12,
            content: child,
            loc: child.loc,
            codegenNode: createCallExpression(
              context.helper(CREATE_TEXT),
              callArgs
            )
          };
        }
      }
    };
  }
};
const seen$1 = /* @__PURE__ */ new WeakSet();
const transformOnce = (node, context) => {
  if (node.type === 1 && findDir(node, "once", true)) {
    if (seen$1.has(node) || context.inVOnce || context.inSSR) {
      return;
    }
    seen$1.add(node);
    context.inVOnce = true;
    context.helper(SET_BLOCK_TRACKING);
    return () => {
      context.inVOnce = false;
      const cur = context.currentNode;
      if (cur.codegenNode) {
        cur.codegenNode = context.cache(
          cur.codegenNode,
          true,
          true
        );
      }
    };
  }
};
const transformModel$1 = (dir, node, context) => {
  const { exp, arg } = dir;
  if (!exp) {
    context.onError(
      createCompilerError(41, dir.loc)
    );
    return createTransformProps();
  }
  const rawExp = exp.loc.source.trim();
  const expString = exp.type === 4 ? exp.content : rawExp;
  const bindingType = context.bindingMetadata[rawExp];
  if (bindingType === "props" || bindingType === "props-aliased") {
    context.onError(createCompilerError(44, exp.loc));
    return createTransformProps();
  }
  if (!expString.trim() || !isMemberExpression(exp) && true) {
    context.onError(
      createCompilerError(42, exp.loc)
    );
    return createTransformProps();
  }
  const propName = arg ? arg : createSimpleExpression("modelValue", true);
  const eventName = arg ? isStaticExp(arg) ? `onUpdate:${camelize(arg.content)}` : createCompoundExpression(['"onUpdate:" + ', arg]) : `onUpdate:modelValue`;
  let assignmentExp;
  const eventArg = context.isTS ? `($event: any)` : `$event`;
  {
    assignmentExp = createCompoundExpression([
      `${eventArg} => ((`,
      exp,
      `) = $event)`
    ]);
  }
  const props = [
    // modelValue: foo
    createObjectProperty(propName, dir.exp),
    // "onUpdate:modelValue": $event => (foo = $event)
    createObjectProperty(eventName, assignmentExp)
  ];
  if (dir.modifiers.length && node.tagType === 1) {
    const modifiers = dir.modifiers.map((m2) => m2.content).map((m2) => (isSimpleIdentifier(m2) ? m2 : JSON.stringify(m2)) + `: true`).join(`, `);
    const modifiersKey = arg ? isStaticExp(arg) ? `${arg.content}Modifiers` : createCompoundExpression([arg, ' + "Modifiers"']) : `modelModifiers`;
    props.push(
      createObjectProperty(
        modifiersKey,
        createSimpleExpression(
          `{ ${modifiers} }`,
          false,
          dir.loc,
          2
        )
      )
    );
  }
  return createTransformProps(props);
};
function createTransformProps(props = []) {
  return { props };
}
const validDivisionCharRE = /[\w).+\-_$\]]/;
const transformFilter = (node, context) => {
  if (!isCompatEnabled("COMPILER_FILTERS", context)) {
    return;
  }
  if (node.type === 5) {
    rewriteFilter(node.content, context);
  } else if (node.type === 1) {
    node.props.forEach((prop) => {
      if (prop.type === 7 && prop.name !== "for" && prop.exp) {
        rewriteFilter(prop.exp, context);
      }
    });
  }
};
function rewriteFilter(node, context) {
  if (node.type === 4) {
    parseFilter(node, context);
  } else {
    for (let i = 0; i < node.children.length; i++) {
      const child = node.children[i];
      if (typeof child !== "object") continue;
      if (child.type === 4) {
        parseFilter(child, context);
      } else if (child.type === 8) {
        rewriteFilter(node, context);
      } else if (child.type === 5) {
        rewriteFilter(child.content, context);
      }
    }
  }
}
function parseFilter(node, context) {
  const exp = node.content;
  let inSingle = false;
  let inDouble = false;
  let inTemplateString = false;
  let inRegex = false;
  let curly = 0;
  let square = 0;
  let paren = 0;
  let lastFilterIndex = 0;
  let c, prev, i, expression, filters = [];
  for (i = 0; i < exp.length; i++) {
    prev = c;
    c = exp.charCodeAt(i);
    if (inSingle) {
      if (c === 39 && prev !== 92) inSingle = false;
    } else if (inDouble) {
      if (c === 34 && prev !== 92) inDouble = false;
    } else if (inTemplateString) {
      if (c === 96 && prev !== 92) inTemplateString = false;
    } else if (inRegex) {
      if (c === 47 && prev !== 92) inRegex = false;
    } else if (c === 124 && // pipe
    exp.charCodeAt(i + 1) !== 124 && exp.charCodeAt(i - 1) !== 124 && !curly && !square && !paren) {
      if (expression === void 0) {
        lastFilterIndex = i + 1;
        expression = exp.slice(0, i).trim();
      } else {
        pushFilter();
      }
    } else {
      switch (c) {
        case 34:
          inDouble = true;
          break;
        // "
        case 39:
          inSingle = true;
          break;
        // '
        case 96:
          inTemplateString = true;
          break;
        // `
        case 40:
          paren++;
          break;
        // (
        case 41:
          paren--;
          break;
        // )
        case 91:
          square++;
          break;
        // [
        case 93:
          square--;
          break;
        // ]
        case 123:
          curly++;
          break;
        // {
        case 125:
          curly--;
          break;
      }
      if (c === 47) {
        let j2 = i - 1;
        let p2;
        for (; j2 >= 0; j2--) {
          p2 = exp.charAt(j2);
          if (p2 !== " ") break;
        }
        if (!p2 || !validDivisionCharRE.test(p2)) {
          inRegex = true;
        }
      }
    }
  }
  if (expression === void 0) {
    expression = exp.slice(0, i).trim();
  } else if (lastFilterIndex !== 0) {
    pushFilter();
  }
  function pushFilter() {
    filters.push(exp.slice(lastFilterIndex, i).trim());
    lastFilterIndex = i + 1;
  }
  if (filters.length) {
    for (i = 0; i < filters.length; i++) {
      expression = wrapFilter(expression, filters[i], context);
    }
    node.content = expression;
    node.ast = void 0;
  }
}
function wrapFilter(exp, filter, context) {
  context.helper(RESOLVE_FILTER);
  const i = filter.indexOf("(");
  if (i < 0) {
    context.filters.add(filter);
    return `${toValidAssetId(filter, "filter")}(${exp})`;
  } else {
    const name = filter.slice(0, i);
    const args = filter.slice(i + 1);
    context.filters.add(name);
    return `${toValidAssetId(name, "filter")}(${exp}${args !== ")" ? "," + args : args}`;
  }
}
const seen = /* @__PURE__ */ new WeakSet();
const transformMemo = (node, context) => {
  if (node.type === 1) {
    const dir = findDir(node, "memo");
    if (!dir || seen.has(node) || context.inSSR) {
      return;
    }
    seen.add(node);
    return () => {
      const codegenNode = node.codegenNode || context.currentNode.codegenNode;
      if (codegenNode && codegenNode.type === 13) {
        if (node.tagType !== 1) {
          convertToBlock(codegenNode, context);
        }
        node.codegenNode = createCallExpression(context.helper(WITH_MEMO), [
          dir.exp,
          createFunctionExpression(void 0, codegenNode),
          `_cache`,
          String(context.cached.length)
        ]);
        context.cached.push(null);
      }
    };
  }
};
const transformVBindShorthand = (node, context) => {
  if (node.type === 1) {
    for (const prop of node.props) {
      if (prop.type === 7 && prop.name === "bind" && !prop.exp) {
        const arg = prop.arg;
        if (arg.type !== 4 || !arg.isStatic) {
          context.onError(
            createCompilerError(
              52,
              arg.loc
            )
          );
          prop.exp = createSimpleExpression("", true, arg.loc);
        } else {
          const propName = camelize(arg.content);
          if (validFirstIdentCharRE.test(propName[0]) || // allow hyphen first char for https://github.com/vuejs/language-tools/pull/3424
          propName[0] === "-") {
            prop.exp = createSimpleExpression(propName, false, arg.loc);
          }
        }
      }
    }
  }
};
function getBaseTransformPreset(prefixIdentifiers) {
  return [
    [
      transformVBindShorthand,
      transformOnce,
      transformIf,
      transformMemo,
      transformFor,
      ...[transformFilter],
      ...[],
      transformSlotOutlet,
      transformElement,
      trackSlotScopes,
      transformText
    ],
    {
      on: transformOn$1,
      bind: transformBind,
      model: transformModel$1
    }
  ];
}
function baseCompile(source, options = {}) {
  const onError = options.onError || defaultOnError;
  const isModuleMode = options.mode === "module";
  {
    if (options.prefixIdentifiers === true) {
      onError(createCompilerError(47));
    } else if (isModuleMode) {
      onError(createCompilerError(48));
    }
  }
  const prefixIdentifiers = false;
  if (options.cacheHandlers) {
    onError(createCompilerError(49));
  }
  if (options.scopeId && !isModuleMode) {
    onError(createCompilerError(50));
  }
  const resolvedOptions = extend({}, options, {
    prefixIdentifiers
  });
  const ast = isString(source) ? baseParse(source, resolvedOptions) : source;
  const [nodeTransforms, directiveTransforms] = getBaseTransformPreset();
  transform(
    ast,
    extend({}, resolvedOptions, {
      nodeTransforms: [
        ...nodeTransforms,
        ...options.nodeTransforms || []
        // user transforms
      ],
      directiveTransforms: extend(
        {},
        directiveTransforms,
        options.directiveTransforms || {}
        // user transforms
      )
    })
  );
  return generate(ast, resolvedOptions);
}
const BindingTypes = {
  "DATA": "data",
  "PROPS": "props",
  "PROPS_ALIASED": "props-aliased",
  "SETUP_LET": "setup-let",
  "SETUP_CONST": "setup-const",
  "SETUP_REACTIVE_CONST": "setup-reactive-const",
  "SETUP_MAYBE_REF": "setup-maybe-ref",
  "SETUP_REF": "setup-ref",
  "OPTIONS": "options",
  "LITERAL_CONST": "literal-const"
};
const noopDirectiveTransform = () => ({ props: [] });
/**
* @vue/compiler-dom v3.5.22
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
const V_MODEL_RADIO = /* @__PURE__ */ Symbol(``);
const V_MODEL_CHECKBOX = /* @__PURE__ */ Symbol(
  ``
);
const V_MODEL_TEXT = /* @__PURE__ */ Symbol(``);
const V_MODEL_SELECT = /* @__PURE__ */ Symbol(
  ``
);
const V_MODEL_DYNAMIC = /* @__PURE__ */ Symbol(
  ``
);
const V_ON_WITH_MODIFIERS = /* @__PURE__ */ Symbol(
  ``
);
const V_ON_WITH_KEYS = /* @__PURE__ */ Symbol(
  ``
);
const V_SHOW = /* @__PURE__ */ Symbol(``);
const TRANSITION = /* @__PURE__ */ Symbol(``);
const TRANSITION_GROUP = /* @__PURE__ */ Symbol(
  ``
);
registerRuntimeHelpers({
  [V_MODEL_RADIO]: `vModelRadio`,
  [V_MODEL_CHECKBOX]: `vModelCheckbox`,
  [V_MODEL_TEXT]: `vModelText`,
  [V_MODEL_SELECT]: `vModelSelect`,
  [V_MODEL_DYNAMIC]: `vModelDynamic`,
  [V_ON_WITH_MODIFIERS]: `withModifiers`,
  [V_ON_WITH_KEYS]: `withKeys`,
  [V_SHOW]: `vShow`,
  [TRANSITION]: `Transition`,
  [TRANSITION_GROUP]: `TransitionGroup`
});
let decoder;
function decodeHtmlBrowser(raw, asAttr = false) {
  if (!decoder) {
    decoder = document.createElement("div");
  }
  if (asAttr) {
    decoder.innerHTML = `<div foo="${raw.replace(/"/g, "&quot;")}">`;
    return decoder.children[0].getAttribute("foo");
  } else {
    decoder.innerHTML = raw;
    return decoder.textContent;
  }
}
const parserOptions = {
  parseMode: "html",
  isVoidTag,
  isNativeTag: (tag) => isHTMLTag(tag) || isSVGTag(tag) || isMathMLTag(tag),
  isPreTag: (tag) => tag === "pre",
  isIgnoreNewlineTag: (tag) => tag === "pre" || tag === "textarea",
  decodeEntities: decodeHtmlBrowser,
  isBuiltInComponent: (tag) => {
    if (tag === "Transition" || tag === "transition") {
      return TRANSITION;
    } else if (tag === "TransitionGroup" || tag === "transition-group") {
      return TRANSITION_GROUP;
    }
  },
  // https://html.spec.whatwg.org/multipage/parsing.html#tree-construction-dispatcher
  getNamespace(tag, parent, rootNamespace) {
    let ns = parent ? parent.ns : rootNamespace;
    if (parent && ns === 2) {
      if (parent.tag === "annotation-xml") {
        if (tag === "svg") {
          return 1;
        }
        if (parent.props.some(
          (a2) => a2.type === 6 && a2.name === "encoding" && a2.value != null && (a2.value.content === "text/html" || a2.value.content === "application/xhtml+xml")
        )) {
          ns = 0;
        }
      } else if (/^m(?:[ions]|text)$/.test(parent.tag) && tag !== "mglyph" && tag !== "malignmark") {
        ns = 0;
      }
    } else if (parent && ns === 1) {
      if (parent.tag === "foreignObject" || parent.tag === "desc" || parent.tag === "title") {
        ns = 0;
      }
    }
    if (ns === 0) {
      if (tag === "svg") {
        return 1;
      }
      if (tag === "math") {
        return 2;
      }
    }
    return ns;
  }
};
const transformStyle = (node) => {
  if (node.type === 1) {
    node.props.forEach((p2, i) => {
      if (p2.type === 6 && p2.name === "style" && p2.value) {
        node.props[i] = {
          type: 7,
          name: `bind`,
          arg: createSimpleExpression(`style`, true, p2.loc),
          exp: parseInlineCSS(p2.value.content, p2.loc),
          modifiers: [],
          loc: p2.loc
        };
      }
    });
  }
};
const parseInlineCSS = (cssText, loc) => {
  const normalized = parseStringStyle(cssText);
  return createSimpleExpression(
    JSON.stringify(normalized),
    false,
    loc,
    3
  );
};
function createDOMCompilerError(code2, loc) {
  return createCompilerError(
    code2,
    loc
  );
}
const DOMErrorCodes = {
  "X_V_HTML_NO_EXPRESSION": 53,
  "53": "X_V_HTML_NO_EXPRESSION",
  "X_V_HTML_WITH_CHILDREN": 54,
  "54": "X_V_HTML_WITH_CHILDREN",
  "X_V_TEXT_NO_EXPRESSION": 55,
  "55": "X_V_TEXT_NO_EXPRESSION",
  "X_V_TEXT_WITH_CHILDREN": 56,
  "56": "X_V_TEXT_WITH_CHILDREN",
  "X_V_MODEL_ON_INVALID_ELEMENT": 57,
  "57": "X_V_MODEL_ON_INVALID_ELEMENT",
  "X_V_MODEL_ARG_ON_ELEMENT": 58,
  "58": "X_V_MODEL_ARG_ON_ELEMENT",
  "X_V_MODEL_ON_FILE_INPUT_ELEMENT": 59,
  "59": "X_V_MODEL_ON_FILE_INPUT_ELEMENT",
  "X_V_MODEL_UNNECESSARY_VALUE": 60,
  "60": "X_V_MODEL_UNNECESSARY_VALUE",
  "X_V_SHOW_NO_EXPRESSION": 61,
  "61": "X_V_SHOW_NO_EXPRESSION",
  "X_TRANSITION_INVALID_CHILDREN": 62,
  "62": "X_TRANSITION_INVALID_CHILDREN",
  "X_IGNORED_SIDE_EFFECT_TAG": 63,
  "63": "X_IGNORED_SIDE_EFFECT_TAG",
  "__EXTEND_POINT__": 64,
  "64": "__EXTEND_POINT__"
};
const DOMErrorMessages = {
  [53]: `v-html is missing expression.`,
  [54]: `v-html will override element children.`,
  [55]: `v-text is missing expression.`,
  [56]: `v-text will override element children.`,
  [57]: `v-model can only be used on <input>, <textarea> and <select> elements.`,
  [58]: `v-model argument is not supported on plain elements.`,
  [59]: `v-model cannot be used on file inputs since they are read-only. Use a v-on:change listener instead.`,
  [60]: `Unnecessary value binding used alongside v-model. It will interfere with v-model's behavior.`,
  [61]: `v-show is missing expression.`,
  [62]: `<Transition> expects exactly one child element or component.`,
  [63]: `Tags with side effect (<script> and <style>) are ignored in client component templates.`
};
const transformVHtml = (dir, node, context) => {
  const { exp, loc } = dir;
  if (!exp) {
    context.onError(
      createDOMCompilerError(53, loc)
    );
  }
  if (node.children.length) {
    context.onError(
      createDOMCompilerError(54, loc)
    );
    node.children.length = 0;
  }
  return {
    props: [
      createObjectProperty(
        createSimpleExpression(`innerHTML`, true, loc),
        exp || createSimpleExpression("", true)
      )
    ]
  };
};
const transformVText = (dir, node, context) => {
  const { exp, loc } = dir;
  if (!exp) {
    context.onError(
      createDOMCompilerError(55, loc)
    );
  }
  if (node.children.length) {
    context.onError(
      createDOMCompilerError(56, loc)
    );
    node.children.length = 0;
  }
  return {
    props: [
      createObjectProperty(
        createSimpleExpression(`textContent`, true),
        exp ? getConstantType(exp, context) > 0 ? exp : createCallExpression(
          context.helperString(TO_DISPLAY_STRING),
          [exp],
          loc
        ) : createSimpleExpression("", true)
      )
    ]
  };
};
const transformModel = (dir, node, context) => {
  const baseResult = transformModel$1(dir, node, context);
  if (!baseResult.props.length || node.tagType === 1) {
    return baseResult;
  }
  if (dir.arg) {
    context.onError(
      createDOMCompilerError(
        58,
        dir.arg.loc
      )
    );
  }
  const { tag } = node;
  const isCustomElement = context.isCustomElement(tag);
  if (tag === "input" || tag === "textarea" || tag === "select" || isCustomElement) {
    let directiveToUse = V_MODEL_TEXT;
    let isInvalidType = false;
    if (tag === "input" || isCustomElement) {
      const type = findProp(node, `type`);
      if (type) {
        if (type.type === 7) {
          directiveToUse = V_MODEL_DYNAMIC;
        } else if (type.value) {
          switch (type.value.content) {
            case "radio":
              directiveToUse = V_MODEL_RADIO;
              break;
            case "checkbox":
              directiveToUse = V_MODEL_CHECKBOX;
              break;
            case "file":
              isInvalidType = true;
              context.onError(
                createDOMCompilerError(
                  59,
                  dir.loc
                )
              );
              break;
          }
        }
      } else if (hasDynamicKeyVBind(node)) {
        directiveToUse = V_MODEL_DYNAMIC;
      } else ;
    } else if (tag === "select") {
      directiveToUse = V_MODEL_SELECT;
    } else ;
    if (!isInvalidType) {
      baseResult.needRuntime = context.helper(directiveToUse);
    }
  } else {
    context.onError(
      createDOMCompilerError(
        57,
        dir.loc
      )
    );
  }
  baseResult.props = baseResult.props.filter(
    (p2) => !(p2.key.type === 4 && p2.key.content === "modelValue")
  );
  return baseResult;
};
const isEventOptionModifier = /* @__PURE__ */ makeMap(`passive,once,capture`);
const isNonKeyModifier = /* @__PURE__ */ makeMap(
  // event propagation management
  `stop,prevent,self,ctrl,shift,alt,meta,exact,middle`
);
const maybeKeyModifier = /* @__PURE__ */ makeMap("left,right");
const isKeyboardEvent = /* @__PURE__ */ makeMap(`onkeyup,onkeydown,onkeypress`);
const resolveModifiers = (key, modifiers, context, loc) => {
  const keyModifiers = [];
  const nonKeyModifiers = [];
  const eventOptionModifiers = [];
  for (let i = 0; i < modifiers.length; i++) {
    const modifier = modifiers[i].content;
    if (modifier === "native" && checkCompatEnabled(
      "COMPILER_V_ON_NATIVE",
      context
    )) {
      eventOptionModifiers.push(modifier);
    } else if (isEventOptionModifier(modifier)) {
      eventOptionModifiers.push(modifier);
    } else {
      if (maybeKeyModifier(modifier)) {
        if (isStaticExp(key)) {
          if (isKeyboardEvent(key.content.toLowerCase())) {
            keyModifiers.push(modifier);
          } else {
            nonKeyModifiers.push(modifier);
          }
        } else {
          keyModifiers.push(modifier);
          nonKeyModifiers.push(modifier);
        }
      } else {
        if (isNonKeyModifier(modifier)) {
          nonKeyModifiers.push(modifier);
        } else {
          keyModifiers.push(modifier);
        }
      }
    }
  }
  return {
    keyModifiers,
    nonKeyModifiers,
    eventOptionModifiers
  };
};
const transformClick = (key, event) => {
  const isStaticClick = isStaticExp(key) && key.content.toLowerCase() === "onclick";
  return isStaticClick ? createSimpleExpression(event, true) : key.type !== 4 ? createCompoundExpression([
    `(`,
    key,
    `) === "onClick" ? "${event}" : (`,
    key,
    `)`
  ]) : key;
};
const transformOn = (dir, node, context) => {
  return transformOn$1(dir, node, context, (baseResult) => {
    const { modifiers } = dir;
    if (!modifiers.length) return baseResult;
    let { key, value: handlerExp } = baseResult.props[0];
    const { keyModifiers, nonKeyModifiers, eventOptionModifiers } = resolveModifiers(key, modifiers, context, dir.loc);
    if (nonKeyModifiers.includes("right")) {
      key = transformClick(key, `onContextmenu`);
    }
    if (nonKeyModifiers.includes("middle")) {
      key = transformClick(key, `onMouseup`);
    }
    if (nonKeyModifiers.length) {
      handlerExp = createCallExpression(context.helper(V_ON_WITH_MODIFIERS), [
        handlerExp,
        JSON.stringify(nonKeyModifiers)
      ]);
    }
    if (keyModifiers.length && // if event name is dynamic, always wrap with keys guard
    (!isStaticExp(key) || isKeyboardEvent(key.content.toLowerCase()))) {
      handlerExp = createCallExpression(context.helper(V_ON_WITH_KEYS), [
        handlerExp,
        JSON.stringify(keyModifiers)
      ]);
    }
    if (eventOptionModifiers.length) {
      const modifierPostfix = eventOptionModifiers.map(capitalize).join("");
      key = isStaticExp(key) ? createSimpleExpression(`${key.content}${modifierPostfix}`, true) : createCompoundExpression([`(`, key, `) + "${modifierPostfix}"`]);
    }
    return {
      props: [createObjectProperty(key, handlerExp)]
    };
  });
};
const transformShow = (dir, node, context) => {
  const { exp, loc } = dir;
  if (!exp) {
    context.onError(
      createDOMCompilerError(61, loc)
    );
  }
  return {
    props: [],
    needRuntime: context.helper(V_SHOW)
  };
};
const ignoreSideEffectTags = (node, context) => {
  if (node.type === 1 && node.tagType === 0 && (node.tag === "script" || node.tag === "style")) {
    context.removeNode();
  }
};
const DOMNodeTransforms = [
  transformStyle,
  ...[]
];
const DOMDirectiveTransforms = {
  cloak: noopDirectiveTransform,
  html: transformVHtml,
  text: transformVText,
  model: transformModel,
  // override compiler-core
  on: transformOn,
  // override compiler-core
  show: transformShow
};
function compile(src, options = {}) {
  return baseCompile(
    src,
    extend({}, parserOptions, options, {
      nodeTransforms: [
        // ignore <script> and <tag>
        // this is not put inside DOMNodeTransforms because that list is used
        // by compiler-ssr to generate vnode fallback branches
        ignoreSideEffectTags,
        ...DOMNodeTransforms,
        ...options.nodeTransforms || []
      ],
      directiveTransforms: extend(
        {},
        DOMDirectiveTransforms,
        options.directiveTransforms || {}
      ),
      transformHoist: null
    })
  );
}
function parse(template, options = {}) {
  return baseParse(template, extend({}, parserOptions, options));
}
const compilerDom_esmBundler = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  BASE_TRANSITION,
  BindingTypes,
  CAMELIZE,
  CAPITALIZE,
  CREATE_BLOCK,
  CREATE_COMMENT,
  CREATE_ELEMENT_BLOCK,
  CREATE_ELEMENT_VNODE,
  CREATE_SLOTS,
  CREATE_STATIC,
  CREATE_TEXT,
  CREATE_VNODE,
  CompilerDeprecationTypes,
  ConstantTypes,
  DOMDirectiveTransforms,
  DOMErrorCodes,
  DOMErrorMessages,
  DOMNodeTransforms,
  ElementTypes,
  ErrorCodes,
  FRAGMENT,
  GUARD_REACTIVE_PROPS,
  IS_MEMO_SAME,
  IS_REF,
  KEEP_ALIVE,
  MERGE_PROPS,
  NORMALIZE_CLASS,
  NORMALIZE_PROPS,
  NORMALIZE_STYLE,
  Namespaces,
  NodeTypes,
  OPEN_BLOCK,
  POP_SCOPE_ID,
  PUSH_SCOPE_ID,
  RENDER_LIST,
  RENDER_SLOT,
  RESOLVE_COMPONENT,
  RESOLVE_DIRECTIVE,
  RESOLVE_DYNAMIC_COMPONENT,
  RESOLVE_FILTER,
  SET_BLOCK_TRACKING,
  SUSPENSE,
  TELEPORT,
  TO_DISPLAY_STRING,
  TO_HANDLERS,
  TO_HANDLER_KEY,
  TRANSITION,
  TRANSITION_GROUP,
  TS_NODE_TYPES,
  UNREF,
  V_MODEL_CHECKBOX,
  V_MODEL_DYNAMIC,
  V_MODEL_RADIO,
  V_MODEL_SELECT,
  V_MODEL_TEXT,
  V_ON_WITH_KEYS,
  V_ON_WITH_MODIFIERS,
  V_SHOW,
  WITH_CTX,
  WITH_DIRECTIVES,
  WITH_MEMO,
  advancePositionWithClone,
  advancePositionWithMutation,
  assert,
  baseCompile,
  baseParse,
  buildDirectiveArgs,
  buildProps,
  buildSlots,
  checkCompatEnabled,
  compile,
  convertToBlock,
  createArrayExpression,
  createAssignmentExpression,
  createBlockStatement,
  createCacheExpression,
  createCallExpression,
  createCompilerError,
  createCompoundExpression,
  createConditionalExpression,
  createDOMCompilerError,
  createForLoopParams,
  createFunctionExpression,
  createIfStatement,
  createInterpolation,
  createObjectExpression,
  createObjectProperty,
  createReturnStatement,
  createRoot,
  createSequenceExpression,
  createSimpleExpression,
  createStructuralDirectiveTransform,
  createTemplateLiteral,
  createTransformContext,
  createVNodeCall,
  errorMessages,
  extractIdentifiers,
  findDir,
  findProp,
  forAliasRE,
  generate,
  generateCodeFrame,
  getBaseTransformPreset,
  getConstantType,
  getMemoedVNodeCall,
  getVNodeBlockHelper,
  getVNodeHelper,
  hasDynamicKeyVBind,
  hasScopeRef,
  helperNameMap,
  injectProp,
  isCoreComponent,
  isFnExpression,
  isFnExpressionBrowser,
  isFnExpressionNode,
  isFunctionType,
  isInDestructureAssignment,
  isInNewExpression,
  isMemberExpression,
  isMemberExpressionBrowser,
  isMemberExpressionNode,
  isReferencedIdentifier,
  isSimpleIdentifier,
  isSlotOutlet,
  isStaticArgOf,
  isStaticExp,
  isStaticProperty,
  isStaticPropertyKey,
  isTemplateNode,
  isText: isText$1,
  isVPre,
  isVSlot,
  locStub,
  noopDirectiveTransform,
  parse,
  parserOptions,
  processExpression,
  processFor,
  processIf,
  processSlotOutlet,
  registerRuntimeHelpers,
  resolveComponentType,
  stringifyExpression,
  toValidAssetId,
  trackSlotScopes,
  trackVForSlotScopes,
  transform,
  transformBind,
  transformElement,
  transformExpression,
  transformModel: transformModel$1,
  transformOn: transformOn$1,
  transformStyle,
  transformVBindShorthand,
  traverseNode,
  unwrapTSNode,
  validFirstIdentCharRE,
  walkBlockDeclarations,
  walkFunctionParams,
  walkIdentifiers,
  warnDeprecation
}, Symbol.toStringTag, { value: "Module" }));
const require$$0 = /* @__PURE__ */ getAugmentedNamespace(compilerDom_esmBundler);
const require$$1 = /* @__PURE__ */ getAugmentedNamespace(runtimeDom_esmBundler);
const require$$2 = /* @__PURE__ */ getAugmentedNamespace(shared_esmBundler);
/**
* vue v3.5.22
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
var hasRequiredVue_cjs_prod;
function requireVue_cjs_prod() {
  if (hasRequiredVue_cjs_prod) return vue_cjs_prod;
  hasRequiredVue_cjs_prod = 1;
  (function(exports) {
    Object.defineProperty(exports, "__esModule", { value: true });
    var compilerDom = require$$0;
    var runtimeDom = require$$1;
    var shared = require$$2;
    function _interopNamespaceDefault(e) {
      var n = /* @__PURE__ */ Object.create(null);
      if (e) {
        for (var k2 in e) {
          n[k2] = e[k2];
        }
      }
      n.default = e;
      return Object.freeze(n);
    }
    var runtimeDom__namespace = /* @__PURE__ */ _interopNamespaceDefault(runtimeDom);
    const compileCache = /* @__PURE__ */ Object.create(null);
    function compileToFunction(template, options) {
      if (!shared.isString(template)) {
        if (template.nodeType) {
          template = template.innerHTML;
        } else {
          return shared.NOOP;
        }
      }
      const key = shared.genCacheKey(template, options);
      const cached = compileCache[key];
      if (cached) {
        return cached;
      }
      if (template[0] === "#") {
        const el = document.querySelector(template);
        template = el ? el.innerHTML : ``;
      }
      const opts = shared.extend(
        {
          hoistStatic: true,
          onError: void 0,
          onWarn: shared.NOOP
        },
        options
      );
      if (!opts.isCustomElement && typeof customElements !== "undefined") {
        opts.isCustomElement = (tag) => !!customElements.get(tag);
      }
      const { code: code2 } = compilerDom.compile(template, opts);
      const render = new Function("Vue", code2)(runtimeDom__namespace);
      render._rc = true;
      return compileCache[key] = render;
    }
    runtimeDom.registerRuntimeCompiler(compileToFunction);
    exports.compile = compileToFunction;
    Object.keys(runtimeDom).forEach(function(k2) {
      if (k2 !== "default" && !Object.prototype.hasOwnProperty.call(exports, k2)) exports[k2] = runtimeDom[k2];
    });
  })(vue_cjs_prod);
  return vue_cjs_prod;
}
var hasRequiredVue;
function requireVue() {
  if (hasRequiredVue) return vue.exports;
  hasRequiredVue = 1;
  {
    vue.exports = requireVue_cjs_prod();
  }
  return vue.exports;
}
var cropper$1 = { exports: {} };
/*!
 * Cropper.js v1.6.2
 * https://fengyuanchen.github.io/cropperjs
 *
 * Copyright 2015-present Chen Fengyuan
 * Released under the MIT license
 *
 * Date: 2024-04-21T07:43:05.335Z
 */
var cropper = cropper$1.exports;
var hasRequiredCropper;
function requireCropper() {
  if (hasRequiredCropper) return cropper$1.exports;
  hasRequiredCropper = 1;
  (function(module, exports) {
    (function(global, factory) {
      module.exports = factory();
    })(cropper, (function() {
      function ownKeys(e, r) {
        var t2 = Object.keys(e);
        if (Object.getOwnPropertySymbols) {
          var o = Object.getOwnPropertySymbols(e);
          r && (o = o.filter(function(r2) {
            return Object.getOwnPropertyDescriptor(e, r2).enumerable;
          })), t2.push.apply(t2, o);
        }
        return t2;
      }
      function _objectSpread2(e) {
        for (var r = 1; r < arguments.length; r++) {
          var t2 = null != arguments[r] ? arguments[r] : {};
          r % 2 ? ownKeys(Object(t2), true).forEach(function(r2) {
            _defineProperty(e, r2, t2[r2]);
          }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t2)) : ownKeys(Object(t2)).forEach(function(r2) {
            Object.defineProperty(e, r2, Object.getOwnPropertyDescriptor(t2, r2));
          });
        }
        return e;
      }
      function _toPrimitive(t2, r) {
        if ("object" != typeof t2 || !t2) return t2;
        var e = t2[Symbol.toPrimitive];
        if (void 0 !== e) {
          var i = e.call(t2, r);
          if ("object" != typeof i) return i;
          throw new TypeError("@@toPrimitive must return a primitive value.");
        }
        return ("string" === r ? String : Number)(t2);
      }
      function _toPropertyKey(t2) {
        var i = _toPrimitive(t2, "string");
        return "symbol" == typeof i ? i : i + "";
      }
      function _typeof(o) {
        "@babel/helpers - typeof";
        return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(o2) {
          return typeof o2;
        } : function(o2) {
          return o2 && "function" == typeof Symbol && o2.constructor === Symbol && o2 !== Symbol.prototype ? "symbol" : typeof o2;
        }, _typeof(o);
      }
      function _classCallCheck(instance, Constructor) {
        if (!(instance instanceof Constructor)) {
          throw new TypeError("Cannot call a class as a function");
        }
      }
      function _defineProperties(target, props) {
        for (var i = 0; i < props.length; i++) {
          var descriptor = props[i];
          descriptor.enumerable = descriptor.enumerable || false;
          descriptor.configurable = true;
          if ("value" in descriptor) descriptor.writable = true;
          Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor);
        }
      }
      function _createClass(Constructor, protoProps, staticProps) {
        if (protoProps) _defineProperties(Constructor.prototype, protoProps);
        if (staticProps) _defineProperties(Constructor, staticProps);
        Object.defineProperty(Constructor, "prototype", {
          writable: false
        });
        return Constructor;
      }
      function _defineProperty(obj, key, value) {
        key = _toPropertyKey(key);
        if (key in obj) {
          Object.defineProperty(obj, key, {
            value,
            enumerable: true,
            configurable: true,
            writable: true
          });
        } else {
          obj[key] = value;
        }
        return obj;
      }
      function _toConsumableArray(arr) {
        return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread();
      }
      function _arrayWithoutHoles(arr) {
        if (Array.isArray(arr)) return _arrayLikeToArray(arr);
      }
      function _iterableToArray(iter) {
        if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter);
      }
      function _unsupportedIterableToArray(o, minLen) {
        if (!o) return;
        if (typeof o === "string") return _arrayLikeToArray(o, minLen);
        var n = Object.prototype.toString.call(o).slice(8, -1);
        if (n === "Object" && o.constructor) n = o.constructor.name;
        if (n === "Map" || n === "Set") return Array.from(o);
        if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
      }
      function _arrayLikeToArray(arr, len) {
        if (len == null || len > arr.length) len = arr.length;
        for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
        return arr2;
      }
      function _nonIterableSpread() {
        throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
      }
      var IS_BROWSER = typeof window !== "undefined" && typeof window.document !== "undefined";
      var WINDOW = IS_BROWSER ? window : {};
      var IS_TOUCH_DEVICE = IS_BROWSER && WINDOW.document.documentElement ? "ontouchstart" in WINDOW.document.documentElement : false;
      var HAS_POINTER_EVENT = IS_BROWSER ? "PointerEvent" in WINDOW : false;
      var NAMESPACE = "cropper";
      var ACTION_ALL = "all";
      var ACTION_CROP = "crop";
      var ACTION_MOVE = "move";
      var ACTION_ZOOM = "zoom";
      var ACTION_EAST = "e";
      var ACTION_WEST = "w";
      var ACTION_SOUTH = "s";
      var ACTION_NORTH = "n";
      var ACTION_NORTH_EAST = "ne";
      var ACTION_NORTH_WEST = "nw";
      var ACTION_SOUTH_EAST = "se";
      var ACTION_SOUTH_WEST = "sw";
      var CLASS_CROP = "".concat(NAMESPACE, "-crop");
      var CLASS_DISABLED = "".concat(NAMESPACE, "-disabled");
      var CLASS_HIDDEN = "".concat(NAMESPACE, "-hidden");
      var CLASS_HIDE = "".concat(NAMESPACE, "-hide");
      var CLASS_INVISIBLE = "".concat(NAMESPACE, "-invisible");
      var CLASS_MODAL = "".concat(NAMESPACE, "-modal");
      var CLASS_MOVE = "".concat(NAMESPACE, "-move");
      var DATA_ACTION = "".concat(NAMESPACE, "Action");
      var DATA_PREVIEW = "".concat(NAMESPACE, "Preview");
      var DRAG_MODE_CROP = "crop";
      var DRAG_MODE_MOVE = "move";
      var DRAG_MODE_NONE = "none";
      var EVENT_CROP = "crop";
      var EVENT_CROP_END = "cropend";
      var EVENT_CROP_MOVE = "cropmove";
      var EVENT_CROP_START = "cropstart";
      var EVENT_DBLCLICK = "dblclick";
      var EVENT_TOUCH_START = IS_TOUCH_DEVICE ? "touchstart" : "mousedown";
      var EVENT_TOUCH_MOVE = IS_TOUCH_DEVICE ? "touchmove" : "mousemove";
      var EVENT_TOUCH_END = IS_TOUCH_DEVICE ? "touchend touchcancel" : "mouseup";
      var EVENT_POINTER_DOWN = HAS_POINTER_EVENT ? "pointerdown" : EVENT_TOUCH_START;
      var EVENT_POINTER_MOVE = HAS_POINTER_EVENT ? "pointermove" : EVENT_TOUCH_MOVE;
      var EVENT_POINTER_UP = HAS_POINTER_EVENT ? "pointerup pointercancel" : EVENT_TOUCH_END;
      var EVENT_READY = "ready";
      var EVENT_RESIZE = "resize";
      var EVENT_WHEEL = "wheel";
      var EVENT_ZOOM = "zoom";
      var MIME_TYPE_JPEG = "image/jpeg";
      var REGEXP_ACTIONS = /^e|w|s|n|se|sw|ne|nw|all|crop|move|zoom$/;
      var REGEXP_DATA_URL = /^data:/;
      var REGEXP_DATA_URL_JPEG = /^data:image\/jpeg;base64,/;
      var REGEXP_TAG_NAME = /^img|canvas$/i;
      var MIN_CONTAINER_WIDTH = 200;
      var MIN_CONTAINER_HEIGHT = 100;
      var DEFAULTS = {
        // Define the view mode of the cropper
        viewMode: 0,
        // 0, 1, 2, 3
        // Define the dragging mode of the cropper
        dragMode: DRAG_MODE_CROP,
        // 'crop', 'move' or 'none'
        // Define the initial aspect ratio of the crop box
        initialAspectRatio: NaN,
        // Define the aspect ratio of the crop box
        aspectRatio: NaN,
        // An object with the previous cropping result data
        data: null,
        // A selector for adding extra containers to preview
        preview: "",
        // Re-render the cropper when resize the window
        responsive: true,
        // Restore the cropped area after resize the window
        restore: true,
        // Check if the current image is a cross-origin image
        checkCrossOrigin: true,
        // Check the current image's Exif Orientation information
        checkOrientation: true,
        // Show the black modal
        modal: true,
        // Show the dashed lines for guiding
        guides: true,
        // Show the center indicator for guiding
        center: true,
        // Show the white modal to highlight the crop box
        highlight: true,
        // Show the grid background
        background: true,
        // Enable to crop the image automatically when initialize
        autoCrop: true,
        // Define the percentage of automatic cropping area when initializes
        autoCropArea: 0.8,
        // Enable to move the image
        movable: true,
        // Enable to rotate the image
        rotatable: true,
        // Enable to scale the image
        scalable: true,
        // Enable to zoom the image
        zoomable: true,
        // Enable to zoom the image by dragging touch
        zoomOnTouch: true,
        // Enable to zoom the image by wheeling mouse
        zoomOnWheel: true,
        // Define zoom ratio when zoom the image by wheeling mouse
        wheelZoomRatio: 0.1,
        // Enable to move the crop box
        cropBoxMovable: true,
        // Enable to resize the crop box
        cropBoxResizable: true,
        // Toggle drag mode between "crop" and "move" when click twice on the cropper
        toggleDragModeOnDblclick: true,
        // Size limitation
        minCanvasWidth: 0,
        minCanvasHeight: 0,
        minCropBoxWidth: 0,
        minCropBoxHeight: 0,
        minContainerWidth: MIN_CONTAINER_WIDTH,
        minContainerHeight: MIN_CONTAINER_HEIGHT,
        // Shortcuts of events
        ready: null,
        cropstart: null,
        cropmove: null,
        cropend: null,
        crop: null,
        zoom: null
      };
      var TEMPLATE = '<div class="cropper-container" touch-action="none"><div class="cropper-wrap-box"><div class="cropper-canvas"></div></div><div class="cropper-drag-box"></div><div class="cropper-crop-box"><span class="cropper-view-box"></span><span class="cropper-dashed dashed-h"></span><span class="cropper-dashed dashed-v"></span><span class="cropper-center"></span><span class="cropper-face"></span><span class="cropper-line line-e" data-cropper-action="e"></span><span class="cropper-line line-n" data-cropper-action="n"></span><span class="cropper-line line-w" data-cropper-action="w"></span><span class="cropper-line line-s" data-cropper-action="s"></span><span class="cropper-point point-e" data-cropper-action="e"></span><span class="cropper-point point-n" data-cropper-action="n"></span><span class="cropper-point point-w" data-cropper-action="w"></span><span class="cropper-point point-s" data-cropper-action="s"></span><span class="cropper-point point-ne" data-cropper-action="ne"></span><span class="cropper-point point-nw" data-cropper-action="nw"></span><span class="cropper-point point-sw" data-cropper-action="sw"></span><span class="cropper-point point-se" data-cropper-action="se"></span></div></div>';
      var isNaN2 = Number.isNaN || WINDOW.isNaN;
      function isNumber(value) {
        return typeof value === "number" && !isNaN2(value);
      }
      var isPositiveNumber = function isPositiveNumber2(value) {
        return value > 0 && value < Infinity;
      };
      function isUndefined(value) {
        return typeof value === "undefined";
      }
      function isObject2(value) {
        return _typeof(value) === "object" && value !== null;
      }
      var hasOwnProperty = Object.prototype.hasOwnProperty;
      function isPlainObject2(value) {
        if (!isObject2(value)) {
          return false;
        }
        try {
          var _constructor = value.constructor;
          var prototype = _constructor.prototype;
          return _constructor && prototype && hasOwnProperty.call(prototype, "isPrototypeOf");
        } catch (error) {
          return false;
        }
      }
      function isFunction(value) {
        return typeof value === "function";
      }
      var slice = Array.prototype.slice;
      function toArray(value) {
        return Array.from ? Array.from(value) : slice.call(value);
      }
      function forEach(data, callback) {
        if (data && isFunction(callback)) {
          if (Array.isArray(data) || isNumber(data.length)) {
            toArray(data).forEach(function(value, key) {
              callback.call(data, value, key, data);
            });
          } else if (isObject2(data)) {
            Object.keys(data).forEach(function(key) {
              callback.call(data, data[key], key, data);
            });
          }
        }
        return data;
      }
      var assign2 = Object.assign || function assign3(target) {
        for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
          args[_key - 1] = arguments[_key];
        }
        if (isObject2(target) && args.length > 0) {
          args.forEach(function(arg) {
            if (isObject2(arg)) {
              Object.keys(arg).forEach(function(key) {
                target[key] = arg[key];
              });
            }
          });
        }
        return target;
      };
      var REGEXP_DECIMALS = /\.\d*(?:0|9){12}\d*$/;
      function normalizeDecimalNumber(value) {
        var times = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : 1e11;
        return REGEXP_DECIMALS.test(value) ? Math.round(value * times) / times : value;
      }
      var REGEXP_SUFFIX = /^width|height|left|top|marginLeft|marginTop$/;
      function setStyle(element, styles) {
        var style = element.style;
        forEach(styles, function(value, property) {
          if (REGEXP_SUFFIX.test(property) && isNumber(value)) {
            value = "".concat(value, "px");
          }
          style[property] = value;
        });
      }
      function hasClass(element, value) {
        return element.classList ? element.classList.contains(value) : element.className.indexOf(value) > -1;
      }
      function addClass(element, value) {
        if (!value) {
          return;
        }
        if (isNumber(element.length)) {
          forEach(element, function(elem) {
            addClass(elem, value);
          });
          return;
        }
        if (element.classList) {
          element.classList.add(value);
          return;
        }
        var className = element.className.trim();
        if (!className) {
          element.className = value;
        } else if (className.indexOf(value) < 0) {
          element.className = "".concat(className, " ").concat(value);
        }
      }
      function removeClass(element, value) {
        if (!value) {
          return;
        }
        if (isNumber(element.length)) {
          forEach(element, function(elem) {
            removeClass(elem, value);
          });
          return;
        }
        if (element.classList) {
          element.classList.remove(value);
          return;
        }
        if (element.className.indexOf(value) >= 0) {
          element.className = element.className.replace(value, "");
        }
      }
      function toggleClass(element, value, added) {
        if (!value) {
          return;
        }
        if (isNumber(element.length)) {
          forEach(element, function(elem) {
            toggleClass(elem, value, added);
          });
          return;
        }
        if (added) {
          addClass(element, value);
        } else {
          removeClass(element, value);
        }
      }
      var REGEXP_CAMEL_CASE = /([a-z\d])([A-Z])/g;
      function toParamCase(value) {
        return value.replace(REGEXP_CAMEL_CASE, "$1-$2").toLowerCase();
      }
      function getData(element, name) {
        if (isObject2(element[name])) {
          return element[name];
        }
        if (element.dataset) {
          return element.dataset[name];
        }
        return element.getAttribute("data-".concat(toParamCase(name)));
      }
      function setData(element, name, data) {
        if (isObject2(data)) {
          element[name] = data;
        } else if (element.dataset) {
          element.dataset[name] = data;
        } else {
          element.setAttribute("data-".concat(toParamCase(name)), data);
        }
      }
      function removeData(element, name) {
        if (isObject2(element[name])) {
          try {
            delete element[name];
          } catch (error) {
            element[name] = void 0;
          }
        } else if (element.dataset) {
          try {
            delete element.dataset[name];
          } catch (error) {
            element.dataset[name] = void 0;
          }
        } else {
          element.removeAttribute("data-".concat(toParamCase(name)));
        }
      }
      var REGEXP_SPACES = /\s\s*/;
      var onceSupported = (function() {
        var supported2 = false;
        if (IS_BROWSER) {
          var once2 = false;
          var listener = function listener2() {
          };
          var options = Object.defineProperty({}, "once", {
            get: function get2() {
              supported2 = true;
              return once2;
            },
            /**
             * This setter can fix a `TypeError` in strict mode
             * {@link https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Errors/Getter_only}
             * @param {boolean} value - The value to set
             */
            set: function set(value) {
              once2 = value;
            }
          });
          WINDOW.addEventListener("test", listener, options);
          WINDOW.removeEventListener("test", listener, options);
        }
        return supported2;
      })();
      function removeListener(element, type, listener) {
        var options = arguments.length > 3 && arguments[3] !== void 0 ? arguments[3] : {};
        var handler = listener;
        type.trim().split(REGEXP_SPACES).forEach(function(event) {
          if (!onceSupported) {
            var listeners = element.listeners;
            if (listeners && listeners[event] && listeners[event][listener]) {
              handler = listeners[event][listener];
              delete listeners[event][listener];
              if (Object.keys(listeners[event]).length === 0) {
                delete listeners[event];
              }
              if (Object.keys(listeners).length === 0) {
                delete element.listeners;
              }
            }
          }
          element.removeEventListener(event, handler, options);
        });
      }
      function addListener(element, type, listener) {
        var options = arguments.length > 3 && arguments[3] !== void 0 ? arguments[3] : {};
        var _handler = listener;
        type.trim().split(REGEXP_SPACES).forEach(function(event) {
          if (options.once && !onceSupported) {
            var _element$listeners = element.listeners, listeners = _element$listeners === void 0 ? {} : _element$listeners;
            _handler = function handler() {
              delete listeners[event][listener];
              element.removeEventListener(event, _handler, options);
              for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
                args[_key2] = arguments[_key2];
              }
              listener.apply(element, args);
            };
            if (!listeners[event]) {
              listeners[event] = {};
            }
            if (listeners[event][listener]) {
              element.removeEventListener(event, listeners[event][listener], options);
            }
            listeners[event][listener] = _handler;
            element.listeners = listeners;
          }
          element.addEventListener(event, _handler, options);
        });
      }
      function dispatchEvent(element, type, data) {
        var event;
        if (isFunction(Event) && isFunction(CustomEvent)) {
          event = new CustomEvent(type, {
            detail: data,
            bubbles: true,
            cancelable: true
          });
        } else {
          event = document.createEvent("CustomEvent");
          event.initCustomEvent(type, true, true, data);
        }
        return element.dispatchEvent(event);
      }
      function getOffset(element) {
        var box = element.getBoundingClientRect();
        return {
          left: box.left + (window.pageXOffset - document.documentElement.clientLeft),
          top: box.top + (window.pageYOffset - document.documentElement.clientTop)
        };
      }
      var location2 = WINDOW.location;
      var REGEXP_ORIGINS = /^(\w+:)\/\/([^:/?#]*):?(\d*)/i;
      function isCrossOriginURL(url) {
        var parts = url.match(REGEXP_ORIGINS);
        return parts !== null && (parts[1] !== location2.protocol || parts[2] !== location2.hostname || parts[3] !== location2.port);
      }
      function addTimestamp(url) {
        var timestamp = "timestamp=".concat((/* @__PURE__ */ new Date()).getTime());
        return url + (url.indexOf("?") === -1 ? "?" : "&") + timestamp;
      }
      function getTransforms(_ref) {
        var rotate = _ref.rotate, scaleX = _ref.scaleX, scaleY = _ref.scaleY, translateX = _ref.translateX, translateY = _ref.translateY;
        var values = [];
        if (isNumber(translateX) && translateX !== 0) {
          values.push("translateX(".concat(translateX, "px)"));
        }
        if (isNumber(translateY) && translateY !== 0) {
          values.push("translateY(".concat(translateY, "px)"));
        }
        if (isNumber(rotate) && rotate !== 0) {
          values.push("rotate(".concat(rotate, "deg)"));
        }
        if (isNumber(scaleX) && scaleX !== 1) {
          values.push("scaleX(".concat(scaleX, ")"));
        }
        if (isNumber(scaleY) && scaleY !== 1) {
          values.push("scaleY(".concat(scaleY, ")"));
        }
        var transform2 = values.length ? values.join(" ") : "none";
        return {
          WebkitTransform: transform2,
          msTransform: transform2,
          transform: transform2
        };
      }
      function getMaxZoomRatio(pointers) {
        var pointers2 = _objectSpread2({}, pointers);
        var maxRatio = 0;
        forEach(pointers, function(pointer, pointerId) {
          delete pointers2[pointerId];
          forEach(pointers2, function(pointer2) {
            var x1 = Math.abs(pointer.startX - pointer2.startX);
            var y1 = Math.abs(pointer.startY - pointer2.startY);
            var x2 = Math.abs(pointer.endX - pointer2.endX);
            var y2 = Math.abs(pointer.endY - pointer2.endY);
            var z1 = Math.sqrt(x1 * x1 + y1 * y1);
            var z2 = Math.sqrt(x2 * x2 + y2 * y2);
            var ratio = (z2 - z1) / z1;
            if (Math.abs(ratio) > Math.abs(maxRatio)) {
              maxRatio = ratio;
            }
          });
        });
        return maxRatio;
      }
      function getPointer(_ref2, endOnly) {
        var pageX = _ref2.pageX, pageY = _ref2.pageY;
        var end = {
          endX: pageX,
          endY: pageY
        };
        return endOnly ? end : _objectSpread2({
          startX: pageX,
          startY: pageY
        }, end);
      }
      function getPointersCenter(pointers) {
        var pageX = 0;
        var pageY = 0;
        var count = 0;
        forEach(pointers, function(_ref3) {
          var startX = _ref3.startX, startY = _ref3.startY;
          pageX += startX;
          pageY += startY;
          count += 1;
        });
        pageX /= count;
        pageY /= count;
        return {
          pageX,
          pageY
        };
      }
      function getAdjustedSizes(_ref4) {
        var aspectRatio = _ref4.aspectRatio, height = _ref4.height, width = _ref4.width;
        var type = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : "contain";
        var isValidWidth = isPositiveNumber(width);
        var isValidHeight = isPositiveNumber(height);
        if (isValidWidth && isValidHeight) {
          var adjustedWidth = height * aspectRatio;
          if (type === "contain" && adjustedWidth > width || type === "cover" && adjustedWidth < width) {
            height = width / aspectRatio;
          } else {
            width = height * aspectRatio;
          }
        } else if (isValidWidth) {
          height = width / aspectRatio;
        } else if (isValidHeight) {
          width = height * aspectRatio;
        }
        return {
          width,
          height
        };
      }
      function getRotatedSizes(_ref5) {
        var width = _ref5.width, height = _ref5.height, degree = _ref5.degree;
        degree = Math.abs(degree) % 180;
        if (degree === 90) {
          return {
            width: height,
            height: width
          };
        }
        var arc = degree % 90 * Math.PI / 180;
        var sinArc = Math.sin(arc);
        var cosArc = Math.cos(arc);
        var newWidth = width * cosArc + height * sinArc;
        var newHeight = width * sinArc + height * cosArc;
        return degree > 90 ? {
          width: newHeight,
          height: newWidth
        } : {
          width: newWidth,
          height: newHeight
        };
      }
      function getSourceCanvas(image, _ref6, _ref7, _ref8) {
        var imageAspectRatio = _ref6.aspectRatio, imageNaturalWidth = _ref6.naturalWidth, imageNaturalHeight = _ref6.naturalHeight, _ref6$rotate = _ref6.rotate, rotate = _ref6$rotate === void 0 ? 0 : _ref6$rotate, _ref6$scaleX = _ref6.scaleX, scaleX = _ref6$scaleX === void 0 ? 1 : _ref6$scaleX, _ref6$scaleY = _ref6.scaleY, scaleY = _ref6$scaleY === void 0 ? 1 : _ref6$scaleY;
        var aspectRatio = _ref7.aspectRatio, naturalWidth = _ref7.naturalWidth, naturalHeight = _ref7.naturalHeight;
        var _ref8$fillColor = _ref8.fillColor, fillColor = _ref8$fillColor === void 0 ? "transparent" : _ref8$fillColor, _ref8$imageSmoothingE = _ref8.imageSmoothingEnabled, imageSmoothingEnabled = _ref8$imageSmoothingE === void 0 ? true : _ref8$imageSmoothingE, _ref8$imageSmoothingQ = _ref8.imageSmoothingQuality, imageSmoothingQuality = _ref8$imageSmoothingQ === void 0 ? "low" : _ref8$imageSmoothingQ, _ref8$maxWidth = _ref8.maxWidth, maxWidth = _ref8$maxWidth === void 0 ? Infinity : _ref8$maxWidth, _ref8$maxHeight = _ref8.maxHeight, maxHeight = _ref8$maxHeight === void 0 ? Infinity : _ref8$maxHeight, _ref8$minWidth = _ref8.minWidth, minWidth = _ref8$minWidth === void 0 ? 0 : _ref8$minWidth, _ref8$minHeight = _ref8.minHeight, minHeight = _ref8$minHeight === void 0 ? 0 : _ref8$minHeight;
        var canvas = document.createElement("canvas");
        var context = canvas.getContext("2d");
        var maxSizes = getAdjustedSizes({
          aspectRatio,
          width: maxWidth,
          height: maxHeight
        });
        var minSizes = getAdjustedSizes({
          aspectRatio,
          width: minWidth,
          height: minHeight
        }, "cover");
        var width = Math.min(maxSizes.width, Math.max(minSizes.width, naturalWidth));
        var height = Math.min(maxSizes.height, Math.max(minSizes.height, naturalHeight));
        var destMaxSizes = getAdjustedSizes({
          aspectRatio: imageAspectRatio,
          width: maxWidth,
          height: maxHeight
        });
        var destMinSizes = getAdjustedSizes({
          aspectRatio: imageAspectRatio,
          width: minWidth,
          height: minHeight
        }, "cover");
        var destWidth = Math.min(destMaxSizes.width, Math.max(destMinSizes.width, imageNaturalWidth));
        var destHeight = Math.min(destMaxSizes.height, Math.max(destMinSizes.height, imageNaturalHeight));
        var params = [-destWidth / 2, -destHeight / 2, destWidth, destHeight];
        canvas.width = normalizeDecimalNumber(width);
        canvas.height = normalizeDecimalNumber(height);
        context.fillStyle = fillColor;
        context.fillRect(0, 0, width, height);
        context.save();
        context.translate(width / 2, height / 2);
        context.rotate(rotate * Math.PI / 180);
        context.scale(scaleX, scaleY);
        context.imageSmoothingEnabled = imageSmoothingEnabled;
        context.imageSmoothingQuality = imageSmoothingQuality;
        context.drawImage.apply(context, [image].concat(_toConsumableArray(params.map(function(param) {
          return Math.floor(normalizeDecimalNumber(param));
        }))));
        context.restore();
        return canvas;
      }
      var fromCharCode = String.fromCharCode;
      function getStringFromCharCode(dataView, start, length) {
        var str = "";
        length += start;
        for (var i = start; i < length; i += 1) {
          str += fromCharCode(dataView.getUint8(i));
        }
        return str;
      }
      var REGEXP_DATA_URL_HEAD = /^data:.*,/;
      function dataURLToArrayBuffer(dataURL) {
        var base64 = dataURL.replace(REGEXP_DATA_URL_HEAD, "");
        var binary = atob(base64);
        var arrayBuffer = new ArrayBuffer(binary.length);
        var uint8 = new Uint8Array(arrayBuffer);
        forEach(uint8, function(value, i) {
          uint8[i] = binary.charCodeAt(i);
        });
        return arrayBuffer;
      }
      function arrayBufferToDataURL(arrayBuffer, mimeType) {
        var chunks = [];
        var chunkSize = 8192;
        var uint8 = new Uint8Array(arrayBuffer);
        while (uint8.length > 0) {
          chunks.push(fromCharCode.apply(null, toArray(uint8.subarray(0, chunkSize))));
          uint8 = uint8.subarray(chunkSize);
        }
        return "data:".concat(mimeType, ";base64,").concat(btoa(chunks.join("")));
      }
      function resetAndGetOrientation(arrayBuffer) {
        var dataView = new DataView(arrayBuffer);
        var orientation;
        try {
          var littleEndian;
          var app1Start;
          var ifdStart;
          if (dataView.getUint8(0) === 255 && dataView.getUint8(1) === 216) {
            var length = dataView.byteLength;
            var offset = 2;
            while (offset + 1 < length) {
              if (dataView.getUint8(offset) === 255 && dataView.getUint8(offset + 1) === 225) {
                app1Start = offset;
                break;
              }
              offset += 1;
            }
          }
          if (app1Start) {
            var exifIDCode = app1Start + 4;
            var tiffOffset = app1Start + 10;
            if (getStringFromCharCode(dataView, exifIDCode, 4) === "Exif") {
              var endianness = dataView.getUint16(tiffOffset);
              littleEndian = endianness === 18761;
              if (littleEndian || endianness === 19789) {
                if (dataView.getUint16(tiffOffset + 2, littleEndian) === 42) {
                  var firstIFDOffset = dataView.getUint32(tiffOffset + 4, littleEndian);
                  if (firstIFDOffset >= 8) {
                    ifdStart = tiffOffset + firstIFDOffset;
                  }
                }
              }
            }
          }
          if (ifdStart) {
            var _length = dataView.getUint16(ifdStart, littleEndian);
            var _offset;
            var i;
            for (i = 0; i < _length; i += 1) {
              _offset = ifdStart + i * 12 + 2;
              if (dataView.getUint16(_offset, littleEndian) === 274) {
                _offset += 8;
                orientation = dataView.getUint16(_offset, littleEndian);
                dataView.setUint16(_offset, 1, littleEndian);
                break;
              }
            }
          }
        } catch (error) {
          orientation = 1;
        }
        return orientation;
      }
      function parseOrientation(orientation) {
        var rotate = 0;
        var scaleX = 1;
        var scaleY = 1;
        switch (orientation) {
          // Flip horizontal
          case 2:
            scaleX = -1;
            break;
          // Rotate left 180°
          case 3:
            rotate = -180;
            break;
          // Flip vertical
          case 4:
            scaleY = -1;
            break;
          // Flip vertical and rotate right 90°
          case 5:
            rotate = 90;
            scaleY = -1;
            break;
          // Rotate right 90°
          case 6:
            rotate = 90;
            break;
          // Flip horizontal and rotate right 90°
          case 7:
            rotate = 90;
            scaleX = -1;
            break;
          // Rotate left 90°
          case 8:
            rotate = -90;
            break;
        }
        return {
          rotate,
          scaleX,
          scaleY
        };
      }
      var render = {
        render: function render2() {
          this.initContainer();
          this.initCanvas();
          this.initCropBox();
          this.renderCanvas();
          if (this.cropped) {
            this.renderCropBox();
          }
        },
        initContainer: function initContainer() {
          var element = this.element, options = this.options, container = this.container, cropper2 = this.cropper;
          var minWidth = Number(options.minContainerWidth);
          var minHeight = Number(options.minContainerHeight);
          addClass(cropper2, CLASS_HIDDEN);
          removeClass(element, CLASS_HIDDEN);
          var containerData = {
            width: Math.max(container.offsetWidth, minWidth >= 0 ? minWidth : MIN_CONTAINER_WIDTH),
            height: Math.max(container.offsetHeight, minHeight >= 0 ? minHeight : MIN_CONTAINER_HEIGHT)
          };
          this.containerData = containerData;
          setStyle(cropper2, {
            width: containerData.width,
            height: containerData.height
          });
          addClass(element, CLASS_HIDDEN);
          removeClass(cropper2, CLASS_HIDDEN);
        },
        // Canvas (image wrapper)
        initCanvas: function initCanvas() {
          var containerData = this.containerData, imageData = this.imageData;
          var viewMode = this.options.viewMode;
          var rotated = Math.abs(imageData.rotate) % 180 === 90;
          var naturalWidth = rotated ? imageData.naturalHeight : imageData.naturalWidth;
          var naturalHeight = rotated ? imageData.naturalWidth : imageData.naturalHeight;
          var aspectRatio = naturalWidth / naturalHeight;
          var canvasWidth = containerData.width;
          var canvasHeight = containerData.height;
          if (containerData.height * aspectRatio > containerData.width) {
            if (viewMode === 3) {
              canvasWidth = containerData.height * aspectRatio;
            } else {
              canvasHeight = containerData.width / aspectRatio;
            }
          } else if (viewMode === 3) {
            canvasHeight = containerData.width / aspectRatio;
          } else {
            canvasWidth = containerData.height * aspectRatio;
          }
          var canvasData = {
            aspectRatio,
            naturalWidth,
            naturalHeight,
            width: canvasWidth,
            height: canvasHeight
          };
          this.canvasData = canvasData;
          this.limited = viewMode === 1 || viewMode === 2;
          this.limitCanvas(true, true);
          canvasData.width = Math.min(Math.max(canvasData.width, canvasData.minWidth), canvasData.maxWidth);
          canvasData.height = Math.min(Math.max(canvasData.height, canvasData.minHeight), canvasData.maxHeight);
          canvasData.left = (containerData.width - canvasData.width) / 2;
          canvasData.top = (containerData.height - canvasData.height) / 2;
          canvasData.oldLeft = canvasData.left;
          canvasData.oldTop = canvasData.top;
          this.initialCanvasData = assign2({}, canvasData);
        },
        limitCanvas: function limitCanvas(sizeLimited, positionLimited) {
          var options = this.options, containerData = this.containerData, canvasData = this.canvasData, cropBoxData = this.cropBoxData;
          var viewMode = options.viewMode;
          var aspectRatio = canvasData.aspectRatio;
          var cropped = this.cropped && cropBoxData;
          if (sizeLimited) {
            var minCanvasWidth = Number(options.minCanvasWidth) || 0;
            var minCanvasHeight = Number(options.minCanvasHeight) || 0;
            if (viewMode > 1) {
              minCanvasWidth = Math.max(minCanvasWidth, containerData.width);
              minCanvasHeight = Math.max(minCanvasHeight, containerData.height);
              if (viewMode === 3) {
                if (minCanvasHeight * aspectRatio > minCanvasWidth) {
                  minCanvasWidth = minCanvasHeight * aspectRatio;
                } else {
                  minCanvasHeight = minCanvasWidth / aspectRatio;
                }
              }
            } else if (viewMode > 0) {
              if (minCanvasWidth) {
                minCanvasWidth = Math.max(minCanvasWidth, cropped ? cropBoxData.width : 0);
              } else if (minCanvasHeight) {
                minCanvasHeight = Math.max(minCanvasHeight, cropped ? cropBoxData.height : 0);
              } else if (cropped) {
                minCanvasWidth = cropBoxData.width;
                minCanvasHeight = cropBoxData.height;
                if (minCanvasHeight * aspectRatio > minCanvasWidth) {
                  minCanvasWidth = minCanvasHeight * aspectRatio;
                } else {
                  minCanvasHeight = minCanvasWidth / aspectRatio;
                }
              }
            }
            var _getAdjustedSizes = getAdjustedSizes({
              aspectRatio,
              width: minCanvasWidth,
              height: minCanvasHeight
            });
            minCanvasWidth = _getAdjustedSizes.width;
            minCanvasHeight = _getAdjustedSizes.height;
            canvasData.minWidth = minCanvasWidth;
            canvasData.minHeight = minCanvasHeight;
            canvasData.maxWidth = Infinity;
            canvasData.maxHeight = Infinity;
          }
          if (positionLimited) {
            if (viewMode > (cropped ? 0 : 1)) {
              var newCanvasLeft = containerData.width - canvasData.width;
              var newCanvasTop = containerData.height - canvasData.height;
              canvasData.minLeft = Math.min(0, newCanvasLeft);
              canvasData.minTop = Math.min(0, newCanvasTop);
              canvasData.maxLeft = Math.max(0, newCanvasLeft);
              canvasData.maxTop = Math.max(0, newCanvasTop);
              if (cropped && this.limited) {
                canvasData.minLeft = Math.min(cropBoxData.left, cropBoxData.left + (cropBoxData.width - canvasData.width));
                canvasData.minTop = Math.min(cropBoxData.top, cropBoxData.top + (cropBoxData.height - canvasData.height));
                canvasData.maxLeft = cropBoxData.left;
                canvasData.maxTop = cropBoxData.top;
                if (viewMode === 2) {
                  if (canvasData.width >= containerData.width) {
                    canvasData.minLeft = Math.min(0, newCanvasLeft);
                    canvasData.maxLeft = Math.max(0, newCanvasLeft);
                  }
                  if (canvasData.height >= containerData.height) {
                    canvasData.minTop = Math.min(0, newCanvasTop);
                    canvasData.maxTop = Math.max(0, newCanvasTop);
                  }
                }
              }
            } else {
              canvasData.minLeft = -canvasData.width;
              canvasData.minTop = -canvasData.height;
              canvasData.maxLeft = containerData.width;
              canvasData.maxTop = containerData.height;
            }
          }
        },
        renderCanvas: function renderCanvas(changed, transformed) {
          var canvasData = this.canvasData, imageData = this.imageData;
          if (transformed) {
            var _getRotatedSizes = getRotatedSizes({
              width: imageData.naturalWidth * Math.abs(imageData.scaleX || 1),
              height: imageData.naturalHeight * Math.abs(imageData.scaleY || 1),
              degree: imageData.rotate || 0
            }), naturalWidth = _getRotatedSizes.width, naturalHeight = _getRotatedSizes.height;
            var width = canvasData.width * (naturalWidth / canvasData.naturalWidth);
            var height = canvasData.height * (naturalHeight / canvasData.naturalHeight);
            canvasData.left -= (width - canvasData.width) / 2;
            canvasData.top -= (height - canvasData.height) / 2;
            canvasData.width = width;
            canvasData.height = height;
            canvasData.aspectRatio = naturalWidth / naturalHeight;
            canvasData.naturalWidth = naturalWidth;
            canvasData.naturalHeight = naturalHeight;
            this.limitCanvas(true, false);
          }
          if (canvasData.width > canvasData.maxWidth || canvasData.width < canvasData.minWidth) {
            canvasData.left = canvasData.oldLeft;
          }
          if (canvasData.height > canvasData.maxHeight || canvasData.height < canvasData.minHeight) {
            canvasData.top = canvasData.oldTop;
          }
          canvasData.width = Math.min(Math.max(canvasData.width, canvasData.minWidth), canvasData.maxWidth);
          canvasData.height = Math.min(Math.max(canvasData.height, canvasData.minHeight), canvasData.maxHeight);
          this.limitCanvas(false, true);
          canvasData.left = Math.min(Math.max(canvasData.left, canvasData.minLeft), canvasData.maxLeft);
          canvasData.top = Math.min(Math.max(canvasData.top, canvasData.minTop), canvasData.maxTop);
          canvasData.oldLeft = canvasData.left;
          canvasData.oldTop = canvasData.top;
          setStyle(this.canvas, assign2({
            width: canvasData.width,
            height: canvasData.height
          }, getTransforms({
            translateX: canvasData.left,
            translateY: canvasData.top
          })));
          this.renderImage(changed);
          if (this.cropped && this.limited) {
            this.limitCropBox(true, true);
          }
        },
        renderImage: function renderImage(changed) {
          var canvasData = this.canvasData, imageData = this.imageData;
          var width = imageData.naturalWidth * (canvasData.width / canvasData.naturalWidth);
          var height = imageData.naturalHeight * (canvasData.height / canvasData.naturalHeight);
          assign2(imageData, {
            width,
            height,
            left: (canvasData.width - width) / 2,
            top: (canvasData.height - height) / 2
          });
          setStyle(this.image, assign2({
            width: imageData.width,
            height: imageData.height
          }, getTransforms(assign2({
            translateX: imageData.left,
            translateY: imageData.top
          }, imageData))));
          if (changed) {
            this.output();
          }
        },
        initCropBox: function initCropBox() {
          var options = this.options, canvasData = this.canvasData;
          var aspectRatio = options.aspectRatio || options.initialAspectRatio;
          var autoCropArea = Number(options.autoCropArea) || 0.8;
          var cropBoxData = {
            width: canvasData.width,
            height: canvasData.height
          };
          if (aspectRatio) {
            if (canvasData.height * aspectRatio > canvasData.width) {
              cropBoxData.height = cropBoxData.width / aspectRatio;
            } else {
              cropBoxData.width = cropBoxData.height * aspectRatio;
            }
          }
          this.cropBoxData = cropBoxData;
          this.limitCropBox(true, true);
          cropBoxData.width = Math.min(Math.max(cropBoxData.width, cropBoxData.minWidth), cropBoxData.maxWidth);
          cropBoxData.height = Math.min(Math.max(cropBoxData.height, cropBoxData.minHeight), cropBoxData.maxHeight);
          cropBoxData.width = Math.max(cropBoxData.minWidth, cropBoxData.width * autoCropArea);
          cropBoxData.height = Math.max(cropBoxData.minHeight, cropBoxData.height * autoCropArea);
          cropBoxData.left = canvasData.left + (canvasData.width - cropBoxData.width) / 2;
          cropBoxData.top = canvasData.top + (canvasData.height - cropBoxData.height) / 2;
          cropBoxData.oldLeft = cropBoxData.left;
          cropBoxData.oldTop = cropBoxData.top;
          this.initialCropBoxData = assign2({}, cropBoxData);
        },
        limitCropBox: function limitCropBox(sizeLimited, positionLimited) {
          var options = this.options, containerData = this.containerData, canvasData = this.canvasData, cropBoxData = this.cropBoxData, limited = this.limited;
          var aspectRatio = options.aspectRatio;
          if (sizeLimited) {
            var minCropBoxWidth = Number(options.minCropBoxWidth) || 0;
            var minCropBoxHeight = Number(options.minCropBoxHeight) || 0;
            var maxCropBoxWidth = limited ? Math.min(containerData.width, canvasData.width, canvasData.width + canvasData.left, containerData.width - canvasData.left) : containerData.width;
            var maxCropBoxHeight = limited ? Math.min(containerData.height, canvasData.height, canvasData.height + canvasData.top, containerData.height - canvasData.top) : containerData.height;
            minCropBoxWidth = Math.min(minCropBoxWidth, containerData.width);
            minCropBoxHeight = Math.min(minCropBoxHeight, containerData.height);
            if (aspectRatio) {
              if (minCropBoxWidth && minCropBoxHeight) {
                if (minCropBoxHeight * aspectRatio > minCropBoxWidth) {
                  minCropBoxHeight = minCropBoxWidth / aspectRatio;
                } else {
                  minCropBoxWidth = minCropBoxHeight * aspectRatio;
                }
              } else if (minCropBoxWidth) {
                minCropBoxHeight = minCropBoxWidth / aspectRatio;
              } else if (minCropBoxHeight) {
                minCropBoxWidth = minCropBoxHeight * aspectRatio;
              }
              if (maxCropBoxHeight * aspectRatio > maxCropBoxWidth) {
                maxCropBoxHeight = maxCropBoxWidth / aspectRatio;
              } else {
                maxCropBoxWidth = maxCropBoxHeight * aspectRatio;
              }
            }
            cropBoxData.minWidth = Math.min(minCropBoxWidth, maxCropBoxWidth);
            cropBoxData.minHeight = Math.min(minCropBoxHeight, maxCropBoxHeight);
            cropBoxData.maxWidth = maxCropBoxWidth;
            cropBoxData.maxHeight = maxCropBoxHeight;
          }
          if (positionLimited) {
            if (limited) {
              cropBoxData.minLeft = Math.max(0, canvasData.left);
              cropBoxData.minTop = Math.max(0, canvasData.top);
              cropBoxData.maxLeft = Math.min(containerData.width, canvasData.left + canvasData.width) - cropBoxData.width;
              cropBoxData.maxTop = Math.min(containerData.height, canvasData.top + canvasData.height) - cropBoxData.height;
            } else {
              cropBoxData.minLeft = 0;
              cropBoxData.minTop = 0;
              cropBoxData.maxLeft = containerData.width - cropBoxData.width;
              cropBoxData.maxTop = containerData.height - cropBoxData.height;
            }
          }
        },
        renderCropBox: function renderCropBox() {
          var options = this.options, containerData = this.containerData, cropBoxData = this.cropBoxData;
          if (cropBoxData.width > cropBoxData.maxWidth || cropBoxData.width < cropBoxData.minWidth) {
            cropBoxData.left = cropBoxData.oldLeft;
          }
          if (cropBoxData.height > cropBoxData.maxHeight || cropBoxData.height < cropBoxData.minHeight) {
            cropBoxData.top = cropBoxData.oldTop;
          }
          cropBoxData.width = Math.min(Math.max(cropBoxData.width, cropBoxData.minWidth), cropBoxData.maxWidth);
          cropBoxData.height = Math.min(Math.max(cropBoxData.height, cropBoxData.minHeight), cropBoxData.maxHeight);
          this.limitCropBox(false, true);
          cropBoxData.left = Math.min(Math.max(cropBoxData.left, cropBoxData.minLeft), cropBoxData.maxLeft);
          cropBoxData.top = Math.min(Math.max(cropBoxData.top, cropBoxData.minTop), cropBoxData.maxTop);
          cropBoxData.oldLeft = cropBoxData.left;
          cropBoxData.oldTop = cropBoxData.top;
          if (options.movable && options.cropBoxMovable) {
            setData(this.face, DATA_ACTION, cropBoxData.width >= containerData.width && cropBoxData.height >= containerData.height ? ACTION_MOVE : ACTION_ALL);
          }
          setStyle(this.cropBox, assign2({
            width: cropBoxData.width,
            height: cropBoxData.height
          }, getTransforms({
            translateX: cropBoxData.left,
            translateY: cropBoxData.top
          })));
          if (this.cropped && this.limited) {
            this.limitCanvas(true, true);
          }
          if (!this.disabled) {
            this.output();
          }
        },
        output: function output() {
          this.preview();
          dispatchEvent(this.element, EVENT_CROP, this.getData());
        }
      };
      var preview = {
        initPreview: function initPreview() {
          var element = this.element, crossOrigin = this.crossOrigin;
          var preview2 = this.options.preview;
          var url = crossOrigin ? this.crossOriginUrl : this.url;
          var alt = element.alt || "The image to preview";
          var image = document.createElement("img");
          if (crossOrigin) {
            image.crossOrigin = crossOrigin;
          }
          image.src = url;
          image.alt = alt;
          this.viewBox.appendChild(image);
          this.viewBoxImage = image;
          if (!preview2) {
            return;
          }
          var previews = preview2;
          if (typeof preview2 === "string") {
            previews = element.ownerDocument.querySelectorAll(preview2);
          } else if (preview2.querySelector) {
            previews = [preview2];
          }
          this.previews = previews;
          forEach(previews, function(el) {
            var img = document.createElement("img");
            setData(el, DATA_PREVIEW, {
              width: el.offsetWidth,
              height: el.offsetHeight,
              html: el.innerHTML
            });
            if (crossOrigin) {
              img.crossOrigin = crossOrigin;
            }
            img.src = url;
            img.alt = alt;
            img.style.cssText = 'display:block;width:100%;height:auto;min-width:0!important;min-height:0!important;max-width:none!important;max-height:none!important;image-orientation:0deg!important;"';
            el.innerHTML = "";
            el.appendChild(img);
          });
        },
        resetPreview: function resetPreview() {
          forEach(this.previews, function(element) {
            var data = getData(element, DATA_PREVIEW);
            setStyle(element, {
              width: data.width,
              height: data.height
            });
            element.innerHTML = data.html;
            removeData(element, DATA_PREVIEW);
          });
        },
        preview: function preview2() {
          var imageData = this.imageData, canvasData = this.canvasData, cropBoxData = this.cropBoxData;
          var cropBoxWidth = cropBoxData.width, cropBoxHeight = cropBoxData.height;
          var width = imageData.width, height = imageData.height;
          var left = cropBoxData.left - canvasData.left - imageData.left;
          var top = cropBoxData.top - canvasData.top - imageData.top;
          if (!this.cropped || this.disabled) {
            return;
          }
          setStyle(this.viewBoxImage, assign2({
            width,
            height
          }, getTransforms(assign2({
            translateX: -left,
            translateY: -top
          }, imageData))));
          forEach(this.previews, function(element) {
            var data = getData(element, DATA_PREVIEW);
            var originalWidth = data.width;
            var originalHeight = data.height;
            var newWidth = originalWidth;
            var newHeight = originalHeight;
            var ratio = 1;
            if (cropBoxWidth) {
              ratio = originalWidth / cropBoxWidth;
              newHeight = cropBoxHeight * ratio;
            }
            if (cropBoxHeight && newHeight > originalHeight) {
              ratio = originalHeight / cropBoxHeight;
              newWidth = cropBoxWidth * ratio;
              newHeight = originalHeight;
            }
            setStyle(element, {
              width: newWidth,
              height: newHeight
            });
            setStyle(element.getElementsByTagName("img")[0], assign2({
              width: width * ratio,
              height: height * ratio
            }, getTransforms(assign2({
              translateX: -left * ratio,
              translateY: -top * ratio
            }, imageData))));
          });
        }
      };
      var events = {
        bind: function bind() {
          var element = this.element, options = this.options, cropper2 = this.cropper;
          if (isFunction(options.cropstart)) {
            addListener(element, EVENT_CROP_START, options.cropstart);
          }
          if (isFunction(options.cropmove)) {
            addListener(element, EVENT_CROP_MOVE, options.cropmove);
          }
          if (isFunction(options.cropend)) {
            addListener(element, EVENT_CROP_END, options.cropend);
          }
          if (isFunction(options.crop)) {
            addListener(element, EVENT_CROP, options.crop);
          }
          if (isFunction(options.zoom)) {
            addListener(element, EVENT_ZOOM, options.zoom);
          }
          addListener(cropper2, EVENT_POINTER_DOWN, this.onCropStart = this.cropStart.bind(this));
          if (options.zoomable && options.zoomOnWheel) {
            addListener(cropper2, EVENT_WHEEL, this.onWheel = this.wheel.bind(this), {
              passive: false,
              capture: true
            });
          }
          if (options.toggleDragModeOnDblclick) {
            addListener(cropper2, EVENT_DBLCLICK, this.onDblclick = this.dblclick.bind(this));
          }
          addListener(element.ownerDocument, EVENT_POINTER_MOVE, this.onCropMove = this.cropMove.bind(this));
          addListener(element.ownerDocument, EVENT_POINTER_UP, this.onCropEnd = this.cropEnd.bind(this));
          if (options.responsive) {
            addListener(window, EVENT_RESIZE, this.onResize = this.resize.bind(this));
          }
        },
        unbind: function unbind() {
          var element = this.element, options = this.options, cropper2 = this.cropper;
          if (isFunction(options.cropstart)) {
            removeListener(element, EVENT_CROP_START, options.cropstart);
          }
          if (isFunction(options.cropmove)) {
            removeListener(element, EVENT_CROP_MOVE, options.cropmove);
          }
          if (isFunction(options.cropend)) {
            removeListener(element, EVENT_CROP_END, options.cropend);
          }
          if (isFunction(options.crop)) {
            removeListener(element, EVENT_CROP, options.crop);
          }
          if (isFunction(options.zoom)) {
            removeListener(element, EVENT_ZOOM, options.zoom);
          }
          removeListener(cropper2, EVENT_POINTER_DOWN, this.onCropStart);
          if (options.zoomable && options.zoomOnWheel) {
            removeListener(cropper2, EVENT_WHEEL, this.onWheel, {
              passive: false,
              capture: true
            });
          }
          if (options.toggleDragModeOnDblclick) {
            removeListener(cropper2, EVENT_DBLCLICK, this.onDblclick);
          }
          removeListener(element.ownerDocument, EVENT_POINTER_MOVE, this.onCropMove);
          removeListener(element.ownerDocument, EVENT_POINTER_UP, this.onCropEnd);
          if (options.responsive) {
            removeListener(window, EVENT_RESIZE, this.onResize);
          }
        }
      };
      var handlers = {
        resize: function resize() {
          if (this.disabled) {
            return;
          }
          var options = this.options, container = this.container, containerData = this.containerData;
          var ratioX = container.offsetWidth / containerData.width;
          var ratioY = container.offsetHeight / containerData.height;
          var ratio = Math.abs(ratioX - 1) > Math.abs(ratioY - 1) ? ratioX : ratioY;
          if (ratio !== 1) {
            var canvasData;
            var cropBoxData;
            if (options.restore) {
              canvasData = this.getCanvasData();
              cropBoxData = this.getCropBoxData();
            }
            this.render();
            if (options.restore) {
              this.setCanvasData(forEach(canvasData, function(n, i) {
                canvasData[i] = n * ratio;
              }));
              this.setCropBoxData(forEach(cropBoxData, function(n, i) {
                cropBoxData[i] = n * ratio;
              }));
            }
          }
        },
        dblclick: function dblclick() {
          if (this.disabled || this.options.dragMode === DRAG_MODE_NONE) {
            return;
          }
          this.setDragMode(hasClass(this.dragBox, CLASS_CROP) ? DRAG_MODE_MOVE : DRAG_MODE_CROP);
        },
        wheel: function wheel(event) {
          var _this = this;
          var ratio = Number(this.options.wheelZoomRatio) || 0.1;
          var delta = 1;
          if (this.disabled) {
            return;
          }
          event.preventDefault();
          if (this.wheeling) {
            return;
          }
          this.wheeling = true;
          setTimeout(function() {
            _this.wheeling = false;
          }, 50);
          if (event.deltaY) {
            delta = event.deltaY > 0 ? 1 : -1;
          } else if (event.wheelDelta) {
            delta = -event.wheelDelta / 120;
          } else if (event.detail) {
            delta = event.detail > 0 ? 1 : -1;
          }
          this.zoom(-delta * ratio, event);
        },
        cropStart: function cropStart(event) {
          var buttons = event.buttons, button = event.button;
          if (this.disabled || (event.type === "mousedown" || event.type === "pointerdown" && event.pointerType === "mouse") && // No primary button (Usually the left button)
          (isNumber(buttons) && buttons !== 1 || isNumber(button) && button !== 0 || event.ctrlKey)) {
            return;
          }
          var options = this.options, pointers = this.pointers;
          var action;
          if (event.changedTouches) {
            forEach(event.changedTouches, function(touch) {
              pointers[touch.identifier] = getPointer(touch);
            });
          } else {
            pointers[event.pointerId || 0] = getPointer(event);
          }
          if (Object.keys(pointers).length > 1 && options.zoomable && options.zoomOnTouch) {
            action = ACTION_ZOOM;
          } else {
            action = getData(event.target, DATA_ACTION);
          }
          if (!REGEXP_ACTIONS.test(action)) {
            return;
          }
          if (dispatchEvent(this.element, EVENT_CROP_START, {
            originalEvent: event,
            action
          }) === false) {
            return;
          }
          event.preventDefault();
          this.action = action;
          this.cropping = false;
          if (action === ACTION_CROP) {
            this.cropping = true;
            addClass(this.dragBox, CLASS_MODAL);
          }
        },
        cropMove: function cropMove(event) {
          var action = this.action;
          if (this.disabled || !action) {
            return;
          }
          var pointers = this.pointers;
          event.preventDefault();
          if (dispatchEvent(this.element, EVENT_CROP_MOVE, {
            originalEvent: event,
            action
          }) === false) {
            return;
          }
          if (event.changedTouches) {
            forEach(event.changedTouches, function(touch) {
              assign2(pointers[touch.identifier] || {}, getPointer(touch, true));
            });
          } else {
            assign2(pointers[event.pointerId || 0] || {}, getPointer(event, true));
          }
          this.change(event);
        },
        cropEnd: function cropEnd(event) {
          if (this.disabled) {
            return;
          }
          var action = this.action, pointers = this.pointers;
          if (event.changedTouches) {
            forEach(event.changedTouches, function(touch) {
              delete pointers[touch.identifier];
            });
          } else {
            delete pointers[event.pointerId || 0];
          }
          if (!action) {
            return;
          }
          event.preventDefault();
          if (!Object.keys(pointers).length) {
            this.action = "";
          }
          if (this.cropping) {
            this.cropping = false;
            toggleClass(this.dragBox, CLASS_MODAL, this.cropped && this.options.modal);
          }
          dispatchEvent(this.element, EVENT_CROP_END, {
            originalEvent: event,
            action
          });
        }
      };
      var change = {
        change: function change2(event) {
          var options = this.options, canvasData = this.canvasData, containerData = this.containerData, cropBoxData = this.cropBoxData, pointers = this.pointers;
          var action = this.action;
          var aspectRatio = options.aspectRatio;
          var left = cropBoxData.left, top = cropBoxData.top, width = cropBoxData.width, height = cropBoxData.height;
          var right = left + width;
          var bottom = top + height;
          var minLeft = 0;
          var minTop = 0;
          var maxWidth = containerData.width;
          var maxHeight = containerData.height;
          var renderable = true;
          var offset;
          if (!aspectRatio && event.shiftKey) {
            aspectRatio = width && height ? width / height : 1;
          }
          if (this.limited) {
            minLeft = cropBoxData.minLeft;
            minTop = cropBoxData.minTop;
            maxWidth = minLeft + Math.min(containerData.width, canvasData.width, canvasData.left + canvasData.width);
            maxHeight = minTop + Math.min(containerData.height, canvasData.height, canvasData.top + canvasData.height);
          }
          var pointer = pointers[Object.keys(pointers)[0]];
          var range = {
            x: pointer.endX - pointer.startX,
            y: pointer.endY - pointer.startY
          };
          var check = function check2(side) {
            switch (side) {
              case ACTION_EAST:
                if (right + range.x > maxWidth) {
                  range.x = maxWidth - right;
                }
                break;
              case ACTION_WEST:
                if (left + range.x < minLeft) {
                  range.x = minLeft - left;
                }
                break;
              case ACTION_NORTH:
                if (top + range.y < minTop) {
                  range.y = minTop - top;
                }
                break;
              case ACTION_SOUTH:
                if (bottom + range.y > maxHeight) {
                  range.y = maxHeight - bottom;
                }
                break;
            }
          };
          switch (action) {
            // Move crop box
            case ACTION_ALL:
              left += range.x;
              top += range.y;
              break;
            // Resize crop box
            case ACTION_EAST:
              if (range.x >= 0 && (right >= maxWidth || aspectRatio && (top <= minTop || bottom >= maxHeight))) {
                renderable = false;
                break;
              }
              check(ACTION_EAST);
              width += range.x;
              if (width < 0) {
                action = ACTION_WEST;
                width = -width;
                left -= width;
              }
              if (aspectRatio) {
                height = width / aspectRatio;
                top += (cropBoxData.height - height) / 2;
              }
              break;
            case ACTION_NORTH:
              if (range.y <= 0 && (top <= minTop || aspectRatio && (left <= minLeft || right >= maxWidth))) {
                renderable = false;
                break;
              }
              check(ACTION_NORTH);
              height -= range.y;
              top += range.y;
              if (height < 0) {
                action = ACTION_SOUTH;
                height = -height;
                top -= height;
              }
              if (aspectRatio) {
                width = height * aspectRatio;
                left += (cropBoxData.width - width) / 2;
              }
              break;
            case ACTION_WEST:
              if (range.x <= 0 && (left <= minLeft || aspectRatio && (top <= minTop || bottom >= maxHeight))) {
                renderable = false;
                break;
              }
              check(ACTION_WEST);
              width -= range.x;
              left += range.x;
              if (width < 0) {
                action = ACTION_EAST;
                width = -width;
                left -= width;
              }
              if (aspectRatio) {
                height = width / aspectRatio;
                top += (cropBoxData.height - height) / 2;
              }
              break;
            case ACTION_SOUTH:
              if (range.y >= 0 && (bottom >= maxHeight || aspectRatio && (left <= minLeft || right >= maxWidth))) {
                renderable = false;
                break;
              }
              check(ACTION_SOUTH);
              height += range.y;
              if (height < 0) {
                action = ACTION_NORTH;
                height = -height;
                top -= height;
              }
              if (aspectRatio) {
                width = height * aspectRatio;
                left += (cropBoxData.width - width) / 2;
              }
              break;
            case ACTION_NORTH_EAST:
              if (aspectRatio) {
                if (range.y <= 0 && (top <= minTop || right >= maxWidth)) {
                  renderable = false;
                  break;
                }
                check(ACTION_NORTH);
                height -= range.y;
                top += range.y;
                width = height * aspectRatio;
              } else {
                check(ACTION_NORTH);
                check(ACTION_EAST);
                if (range.x >= 0) {
                  if (right < maxWidth) {
                    width += range.x;
                  } else if (range.y <= 0 && top <= minTop) {
                    renderable = false;
                  }
                } else {
                  width += range.x;
                }
                if (range.y <= 0) {
                  if (top > minTop) {
                    height -= range.y;
                    top += range.y;
                  }
                } else {
                  height -= range.y;
                  top += range.y;
                }
              }
              if (width < 0 && height < 0) {
                action = ACTION_SOUTH_WEST;
                height = -height;
                width = -width;
                top -= height;
                left -= width;
              } else if (width < 0) {
                action = ACTION_NORTH_WEST;
                width = -width;
                left -= width;
              } else if (height < 0) {
                action = ACTION_SOUTH_EAST;
                height = -height;
                top -= height;
              }
              break;
            case ACTION_NORTH_WEST:
              if (aspectRatio) {
                if (range.y <= 0 && (top <= minTop || left <= minLeft)) {
                  renderable = false;
                  break;
                }
                check(ACTION_NORTH);
                height -= range.y;
                top += range.y;
                width = height * aspectRatio;
                left += cropBoxData.width - width;
              } else {
                check(ACTION_NORTH);
                check(ACTION_WEST);
                if (range.x <= 0) {
                  if (left > minLeft) {
                    width -= range.x;
                    left += range.x;
                  } else if (range.y <= 0 && top <= minTop) {
                    renderable = false;
                  }
                } else {
                  width -= range.x;
                  left += range.x;
                }
                if (range.y <= 0) {
                  if (top > minTop) {
                    height -= range.y;
                    top += range.y;
                  }
                } else {
                  height -= range.y;
                  top += range.y;
                }
              }
              if (width < 0 && height < 0) {
                action = ACTION_SOUTH_EAST;
                height = -height;
                width = -width;
                top -= height;
                left -= width;
              } else if (width < 0) {
                action = ACTION_NORTH_EAST;
                width = -width;
                left -= width;
              } else if (height < 0) {
                action = ACTION_SOUTH_WEST;
                height = -height;
                top -= height;
              }
              break;
            case ACTION_SOUTH_WEST:
              if (aspectRatio) {
                if (range.x <= 0 && (left <= minLeft || bottom >= maxHeight)) {
                  renderable = false;
                  break;
                }
                check(ACTION_WEST);
                width -= range.x;
                left += range.x;
                height = width / aspectRatio;
              } else {
                check(ACTION_SOUTH);
                check(ACTION_WEST);
                if (range.x <= 0) {
                  if (left > minLeft) {
                    width -= range.x;
                    left += range.x;
                  } else if (range.y >= 0 && bottom >= maxHeight) {
                    renderable = false;
                  }
                } else {
                  width -= range.x;
                  left += range.x;
                }
                if (range.y >= 0) {
                  if (bottom < maxHeight) {
                    height += range.y;
                  }
                } else {
                  height += range.y;
                }
              }
              if (width < 0 && height < 0) {
                action = ACTION_NORTH_EAST;
                height = -height;
                width = -width;
                top -= height;
                left -= width;
              } else if (width < 0) {
                action = ACTION_SOUTH_EAST;
                width = -width;
                left -= width;
              } else if (height < 0) {
                action = ACTION_NORTH_WEST;
                height = -height;
                top -= height;
              }
              break;
            case ACTION_SOUTH_EAST:
              if (aspectRatio) {
                if (range.x >= 0 && (right >= maxWidth || bottom >= maxHeight)) {
                  renderable = false;
                  break;
                }
                check(ACTION_EAST);
                width += range.x;
                height = width / aspectRatio;
              } else {
                check(ACTION_SOUTH);
                check(ACTION_EAST);
                if (range.x >= 0) {
                  if (right < maxWidth) {
                    width += range.x;
                  } else if (range.y >= 0 && bottom >= maxHeight) {
                    renderable = false;
                  }
                } else {
                  width += range.x;
                }
                if (range.y >= 0) {
                  if (bottom < maxHeight) {
                    height += range.y;
                  }
                } else {
                  height += range.y;
                }
              }
              if (width < 0 && height < 0) {
                action = ACTION_NORTH_WEST;
                height = -height;
                width = -width;
                top -= height;
                left -= width;
              } else if (width < 0) {
                action = ACTION_SOUTH_WEST;
                width = -width;
                left -= width;
              } else if (height < 0) {
                action = ACTION_NORTH_EAST;
                height = -height;
                top -= height;
              }
              break;
            // Move canvas
            case ACTION_MOVE:
              this.move(range.x, range.y);
              renderable = false;
              break;
            // Zoom canvas
            case ACTION_ZOOM:
              this.zoom(getMaxZoomRatio(pointers), event);
              renderable = false;
              break;
            // Create crop box
            case ACTION_CROP:
              if (!range.x || !range.y) {
                renderable = false;
                break;
              }
              offset = getOffset(this.cropper);
              left = pointer.startX - offset.left;
              top = pointer.startY - offset.top;
              width = cropBoxData.minWidth;
              height = cropBoxData.minHeight;
              if (range.x > 0) {
                action = range.y > 0 ? ACTION_SOUTH_EAST : ACTION_NORTH_EAST;
              } else if (range.x < 0) {
                left -= width;
                action = range.y > 0 ? ACTION_SOUTH_WEST : ACTION_NORTH_WEST;
              }
              if (range.y < 0) {
                top -= height;
              }
              if (!this.cropped) {
                removeClass(this.cropBox, CLASS_HIDDEN);
                this.cropped = true;
                if (this.limited) {
                  this.limitCropBox(true, true);
                }
              }
              break;
          }
          if (renderable) {
            cropBoxData.width = width;
            cropBoxData.height = height;
            cropBoxData.left = left;
            cropBoxData.top = top;
            this.action = action;
            this.renderCropBox();
          }
          forEach(pointers, function(p2) {
            p2.startX = p2.endX;
            p2.startY = p2.endY;
          });
        }
      };
      var methods = {
        // Show the crop box manually
        crop: function crop() {
          if (this.ready && !this.cropped && !this.disabled) {
            this.cropped = true;
            this.limitCropBox(true, true);
            if (this.options.modal) {
              addClass(this.dragBox, CLASS_MODAL);
            }
            removeClass(this.cropBox, CLASS_HIDDEN);
            this.setCropBoxData(this.initialCropBoxData);
          }
          return this;
        },
        // Reset the image and crop box to their initial states
        reset: function reset2() {
          if (this.ready && !this.disabled) {
            this.imageData = assign2({}, this.initialImageData);
            this.canvasData = assign2({}, this.initialCanvasData);
            this.cropBoxData = assign2({}, this.initialCropBoxData);
            this.renderCanvas();
            if (this.cropped) {
              this.renderCropBox();
            }
          }
          return this;
        },
        // Clear the crop box
        clear: function clear() {
          if (this.cropped && !this.disabled) {
            assign2(this.cropBoxData, {
              left: 0,
              top: 0,
              width: 0,
              height: 0
            });
            this.cropped = false;
            this.renderCropBox();
            this.limitCanvas(true, true);
            this.renderCanvas();
            removeClass(this.dragBox, CLASS_MODAL);
            addClass(this.cropBox, CLASS_HIDDEN);
          }
          return this;
        },
        /**
         * Replace the image's src and rebuild the cropper
         * @param {string} url - The new URL.
         * @param {boolean} [hasSameSize] - Indicate if the new image has the same size as the old one.
         * @returns {Cropper} this
         */
        replace: function replace(url) {
          var hasSameSize = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : false;
          if (!this.disabled && url) {
            if (this.isImg) {
              this.element.src = url;
            }
            if (hasSameSize) {
              this.url = url;
              this.image.src = url;
              if (this.ready) {
                this.viewBoxImage.src = url;
                forEach(this.previews, function(element) {
                  element.getElementsByTagName("img")[0].src = url;
                });
              }
            } else {
              if (this.isImg) {
                this.replaced = true;
              }
              this.options.data = null;
              this.uncreate();
              this.load(url);
            }
          }
          return this;
        },
        // Enable (unfreeze) the cropper
        enable: function enable() {
          if (this.ready && this.disabled) {
            this.disabled = false;
            removeClass(this.cropper, CLASS_DISABLED);
          }
          return this;
        },
        // Disable (freeze) the cropper
        disable: function disable() {
          if (this.ready && !this.disabled) {
            this.disabled = true;
            addClass(this.cropper, CLASS_DISABLED);
          }
          return this;
        },
        /**
         * Destroy the cropper and remove the instance from the image
         * @returns {Cropper} this
         */
        destroy: function destroy() {
          var element = this.element;
          if (!element[NAMESPACE]) {
            return this;
          }
          element[NAMESPACE] = void 0;
          if (this.isImg && this.replaced) {
            element.src = this.originalUrl;
          }
          this.uncreate();
          return this;
        },
        /**
         * Move the canvas with relative offsets
         * @param {number} offsetX - The relative offset distance on the x-axis.
         * @param {number} [offsetY=offsetX] - The relative offset distance on the y-axis.
         * @returns {Cropper} this
         */
        move: function move(offsetX) {
          var offsetY = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : offsetX;
          var _this$canvasData = this.canvasData, left = _this$canvasData.left, top = _this$canvasData.top;
          return this.moveTo(isUndefined(offsetX) ? offsetX : left + Number(offsetX), isUndefined(offsetY) ? offsetY : top + Number(offsetY));
        },
        /**
         * Move the canvas to an absolute point
         * @param {number} x - The x-axis coordinate.
         * @param {number} [y=x] - The y-axis coordinate.
         * @returns {Cropper} this
         */
        moveTo: function moveTo(x2) {
          var y2 = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : x2;
          var canvasData = this.canvasData;
          var changed = false;
          x2 = Number(x2);
          y2 = Number(y2);
          if (this.ready && !this.disabled && this.options.movable) {
            if (isNumber(x2)) {
              canvasData.left = x2;
              changed = true;
            }
            if (isNumber(y2)) {
              canvasData.top = y2;
              changed = true;
            }
            if (changed) {
              this.renderCanvas(true);
            }
          }
          return this;
        },
        /**
         * Zoom the canvas with a relative ratio
         * @param {number} ratio - The target ratio.
         * @param {Event} _originalEvent - The original event if any.
         * @returns {Cropper} this
         */
        zoom: function zoom(ratio, _originalEvent) {
          var canvasData = this.canvasData;
          ratio = Number(ratio);
          if (ratio < 0) {
            ratio = 1 / (1 - ratio);
          } else {
            ratio = 1 + ratio;
          }
          return this.zoomTo(canvasData.width * ratio / canvasData.naturalWidth, null, _originalEvent);
        },
        /**
         * Zoom the canvas to an absolute ratio
         * @param {number} ratio - The target ratio.
         * @param {Object} pivot - The zoom pivot point coordinate.
         * @param {Event} _originalEvent - The original event if any.
         * @returns {Cropper} this
         */
        zoomTo: function zoomTo(ratio, pivot, _originalEvent) {
          var options = this.options, canvasData = this.canvasData;
          var width = canvasData.width, height = canvasData.height, naturalWidth = canvasData.naturalWidth, naturalHeight = canvasData.naturalHeight;
          ratio = Number(ratio);
          if (ratio >= 0 && this.ready && !this.disabled && options.zoomable) {
            var newWidth = naturalWidth * ratio;
            var newHeight = naturalHeight * ratio;
            if (dispatchEvent(this.element, EVENT_ZOOM, {
              ratio,
              oldRatio: width / naturalWidth,
              originalEvent: _originalEvent
            }) === false) {
              return this;
            }
            if (_originalEvent) {
              var pointers = this.pointers;
              var offset = getOffset(this.cropper);
              var center = pointers && Object.keys(pointers).length ? getPointersCenter(pointers) : {
                pageX: _originalEvent.pageX,
                pageY: _originalEvent.pageY
              };
              canvasData.left -= (newWidth - width) * ((center.pageX - offset.left - canvasData.left) / width);
              canvasData.top -= (newHeight - height) * ((center.pageY - offset.top - canvasData.top) / height);
            } else if (isPlainObject2(pivot) && isNumber(pivot.x) && isNumber(pivot.y)) {
              canvasData.left -= (newWidth - width) * ((pivot.x - canvasData.left) / width);
              canvasData.top -= (newHeight - height) * ((pivot.y - canvasData.top) / height);
            } else {
              canvasData.left -= (newWidth - width) / 2;
              canvasData.top -= (newHeight - height) / 2;
            }
            canvasData.width = newWidth;
            canvasData.height = newHeight;
            this.renderCanvas(true);
          }
          return this;
        },
        /**
         * Rotate the canvas with a relative degree
         * @param {number} degree - The rotate degree.
         * @returns {Cropper} this
         */
        rotate: function rotate(degree) {
          return this.rotateTo((this.imageData.rotate || 0) + Number(degree));
        },
        /**
         * Rotate the canvas to an absolute degree
         * @param {number} degree - The rotate degree.
         * @returns {Cropper} this
         */
        rotateTo: function rotateTo(degree) {
          degree = Number(degree);
          if (isNumber(degree) && this.ready && !this.disabled && this.options.rotatable) {
            this.imageData.rotate = degree % 360;
            this.renderCanvas(true, true);
          }
          return this;
        },
        /**
         * Scale the image on the x-axis.
         * @param {number} scaleX - The scale ratio on the x-axis.
         * @returns {Cropper} this
         */
        scaleX: function scaleX(_scaleX) {
          var scaleY = this.imageData.scaleY;
          return this.scale(_scaleX, isNumber(scaleY) ? scaleY : 1);
        },
        /**
         * Scale the image on the y-axis.
         * @param {number} scaleY - The scale ratio on the y-axis.
         * @returns {Cropper} this
         */
        scaleY: function scaleY(_scaleY) {
          var scaleX = this.imageData.scaleX;
          return this.scale(isNumber(scaleX) ? scaleX : 1, _scaleY);
        },
        /**
         * Scale the image
         * @param {number} scaleX - The scale ratio on the x-axis.
         * @param {number} [scaleY=scaleX] - The scale ratio on the y-axis.
         * @returns {Cropper} this
         */
        scale: function scale(scaleX) {
          var scaleY = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : scaleX;
          var imageData = this.imageData;
          var transformed = false;
          scaleX = Number(scaleX);
          scaleY = Number(scaleY);
          if (this.ready && !this.disabled && this.options.scalable) {
            if (isNumber(scaleX)) {
              imageData.scaleX = scaleX;
              transformed = true;
            }
            if (isNumber(scaleY)) {
              imageData.scaleY = scaleY;
              transformed = true;
            }
            if (transformed) {
              this.renderCanvas(true, true);
            }
          }
          return this;
        },
        /**
         * Get the cropped area position and size data (base on the original image)
         * @param {boolean} [rounded=false] - Indicate if round the data values or not.
         * @returns {Object} The result cropped data.
         */
        getData: function getData2() {
          var rounded = arguments.length > 0 && arguments[0] !== void 0 ? arguments[0] : false;
          var options = this.options, imageData = this.imageData, canvasData = this.canvasData, cropBoxData = this.cropBoxData;
          var data;
          if (this.ready && this.cropped) {
            data = {
              x: cropBoxData.left - canvasData.left,
              y: cropBoxData.top - canvasData.top,
              width: cropBoxData.width,
              height: cropBoxData.height
            };
            var ratio = imageData.width / imageData.naturalWidth;
            forEach(data, function(n, i) {
              data[i] = n / ratio;
            });
            if (rounded) {
              var bottom = Math.round(data.y + data.height);
              var right = Math.round(data.x + data.width);
              data.x = Math.round(data.x);
              data.y = Math.round(data.y);
              data.width = right - data.x;
              data.height = bottom - data.y;
            }
          } else {
            data = {
              x: 0,
              y: 0,
              width: 0,
              height: 0
            };
          }
          if (options.rotatable) {
            data.rotate = imageData.rotate || 0;
          }
          if (options.scalable) {
            data.scaleX = imageData.scaleX || 1;
            data.scaleY = imageData.scaleY || 1;
          }
          return data;
        },
        /**
         * Set the cropped area position and size with new data
         * @param {Object} data - The new data.
         * @returns {Cropper} this
         */
        setData: function setData2(data) {
          var options = this.options, imageData = this.imageData, canvasData = this.canvasData;
          var cropBoxData = {};
          if (this.ready && !this.disabled && isPlainObject2(data)) {
            var transformed = false;
            if (options.rotatable) {
              if (isNumber(data.rotate) && data.rotate !== imageData.rotate) {
                imageData.rotate = data.rotate;
                transformed = true;
              }
            }
            if (options.scalable) {
              if (isNumber(data.scaleX) && data.scaleX !== imageData.scaleX) {
                imageData.scaleX = data.scaleX;
                transformed = true;
              }
              if (isNumber(data.scaleY) && data.scaleY !== imageData.scaleY) {
                imageData.scaleY = data.scaleY;
                transformed = true;
              }
            }
            if (transformed) {
              this.renderCanvas(true, true);
            }
            var ratio = imageData.width / imageData.naturalWidth;
            if (isNumber(data.x)) {
              cropBoxData.left = data.x * ratio + canvasData.left;
            }
            if (isNumber(data.y)) {
              cropBoxData.top = data.y * ratio + canvasData.top;
            }
            if (isNumber(data.width)) {
              cropBoxData.width = data.width * ratio;
            }
            if (isNumber(data.height)) {
              cropBoxData.height = data.height * ratio;
            }
            this.setCropBoxData(cropBoxData);
          }
          return this;
        },
        /**
         * Get the container size data.
         * @returns {Object} The result container data.
         */
        getContainerData: function getContainerData() {
          return this.ready ? assign2({}, this.containerData) : {};
        },
        /**
         * Get the image position and size data.
         * @returns {Object} The result image data.
         */
        getImageData: function getImageData() {
          return this.sized ? assign2({}, this.imageData) : {};
        },
        /**
         * Get the canvas position and size data.
         * @returns {Object} The result canvas data.
         */
        getCanvasData: function getCanvasData() {
          var canvasData = this.canvasData;
          var data = {};
          if (this.ready) {
            forEach(["left", "top", "width", "height", "naturalWidth", "naturalHeight"], function(n) {
              data[n] = canvasData[n];
            });
          }
          return data;
        },
        /**
         * Set the canvas position and size with new data.
         * @param {Object} data - The new canvas data.
         * @returns {Cropper} this
         */
        setCanvasData: function setCanvasData(data) {
          var canvasData = this.canvasData;
          var aspectRatio = canvasData.aspectRatio;
          if (this.ready && !this.disabled && isPlainObject2(data)) {
            if (isNumber(data.left)) {
              canvasData.left = data.left;
            }
            if (isNumber(data.top)) {
              canvasData.top = data.top;
            }
            if (isNumber(data.width)) {
              canvasData.width = data.width;
              canvasData.height = data.width / aspectRatio;
            } else if (isNumber(data.height)) {
              canvasData.height = data.height;
              canvasData.width = data.height * aspectRatio;
            }
            this.renderCanvas(true);
          }
          return this;
        },
        /**
         * Get the crop box position and size data.
         * @returns {Object} The result crop box data.
         */
        getCropBoxData: function getCropBoxData() {
          var cropBoxData = this.cropBoxData;
          var data;
          if (this.ready && this.cropped) {
            data = {
              left: cropBoxData.left,
              top: cropBoxData.top,
              width: cropBoxData.width,
              height: cropBoxData.height
            };
          }
          return data || {};
        },
        /**
         * Set the crop box position and size with new data.
         * @param {Object} data - The new crop box data.
         * @returns {Cropper} this
         */
        setCropBoxData: function setCropBoxData(data) {
          var cropBoxData = this.cropBoxData;
          var aspectRatio = this.options.aspectRatio;
          var widthChanged;
          var heightChanged;
          if (this.ready && this.cropped && !this.disabled && isPlainObject2(data)) {
            if (isNumber(data.left)) {
              cropBoxData.left = data.left;
            }
            if (isNumber(data.top)) {
              cropBoxData.top = data.top;
            }
            if (isNumber(data.width) && data.width !== cropBoxData.width) {
              widthChanged = true;
              cropBoxData.width = data.width;
            }
            if (isNumber(data.height) && data.height !== cropBoxData.height) {
              heightChanged = true;
              cropBoxData.height = data.height;
            }
            if (aspectRatio) {
              if (widthChanged) {
                cropBoxData.height = cropBoxData.width / aspectRatio;
              } else if (heightChanged) {
                cropBoxData.width = cropBoxData.height * aspectRatio;
              }
            }
            this.renderCropBox();
          }
          return this;
        },
        /**
         * Get a canvas drawn the cropped image.
         * @param {Object} [options={}] - The config options.
         * @returns {HTMLCanvasElement} - The result canvas.
         */
        getCroppedCanvas: function getCroppedCanvas() {
          var options = arguments.length > 0 && arguments[0] !== void 0 ? arguments[0] : {};
          if (!this.ready || !window.HTMLCanvasElement) {
            return null;
          }
          var canvasData = this.canvasData;
          var source = getSourceCanvas(this.image, this.imageData, canvasData, options);
          if (!this.cropped) {
            return source;
          }
          var _this$getData = this.getData(options.rounded), initialX = _this$getData.x, initialY = _this$getData.y, initialWidth = _this$getData.width, initialHeight = _this$getData.height;
          var ratio = source.width / Math.floor(canvasData.naturalWidth);
          if (ratio !== 1) {
            initialX *= ratio;
            initialY *= ratio;
            initialWidth *= ratio;
            initialHeight *= ratio;
          }
          var aspectRatio = initialWidth / initialHeight;
          var maxSizes = getAdjustedSizes({
            aspectRatio,
            width: options.maxWidth || Infinity,
            height: options.maxHeight || Infinity
          });
          var minSizes = getAdjustedSizes({
            aspectRatio,
            width: options.minWidth || 0,
            height: options.minHeight || 0
          }, "cover");
          var _getAdjustedSizes = getAdjustedSizes({
            aspectRatio,
            width: options.width || (ratio !== 1 ? source.width : initialWidth),
            height: options.height || (ratio !== 1 ? source.height : initialHeight)
          }), width = _getAdjustedSizes.width, height = _getAdjustedSizes.height;
          width = Math.min(maxSizes.width, Math.max(minSizes.width, width));
          height = Math.min(maxSizes.height, Math.max(minSizes.height, height));
          var canvas = document.createElement("canvas");
          var context = canvas.getContext("2d");
          canvas.width = normalizeDecimalNumber(width);
          canvas.height = normalizeDecimalNumber(height);
          context.fillStyle = options.fillColor || "transparent";
          context.fillRect(0, 0, width, height);
          var _options$imageSmoothi = options.imageSmoothingEnabled, imageSmoothingEnabled = _options$imageSmoothi === void 0 ? true : _options$imageSmoothi, imageSmoothingQuality = options.imageSmoothingQuality;
          context.imageSmoothingEnabled = imageSmoothingEnabled;
          if (imageSmoothingQuality) {
            context.imageSmoothingQuality = imageSmoothingQuality;
          }
          var sourceWidth = source.width;
          var sourceHeight = source.height;
          var srcX = initialX;
          var srcY = initialY;
          var srcWidth;
          var srcHeight;
          var dstX;
          var dstY;
          var dstWidth;
          var dstHeight;
          if (srcX <= -initialWidth || srcX > sourceWidth) {
            srcX = 0;
            srcWidth = 0;
            dstX = 0;
            dstWidth = 0;
          } else if (srcX <= 0) {
            dstX = -srcX;
            srcX = 0;
            srcWidth = Math.min(sourceWidth, initialWidth + srcX);
            dstWidth = srcWidth;
          } else if (srcX <= sourceWidth) {
            dstX = 0;
            srcWidth = Math.min(initialWidth, sourceWidth - srcX);
            dstWidth = srcWidth;
          }
          if (srcWidth <= 0 || srcY <= -initialHeight || srcY > sourceHeight) {
            srcY = 0;
            srcHeight = 0;
            dstY = 0;
            dstHeight = 0;
          } else if (srcY <= 0) {
            dstY = -srcY;
            srcY = 0;
            srcHeight = Math.min(sourceHeight, initialHeight + srcY);
            dstHeight = srcHeight;
          } else if (srcY <= sourceHeight) {
            dstY = 0;
            srcHeight = Math.min(initialHeight, sourceHeight - srcY);
            dstHeight = srcHeight;
          }
          var params = [srcX, srcY, srcWidth, srcHeight];
          if (dstWidth > 0 && dstHeight > 0) {
            var scale = width / initialWidth;
            params.push(dstX * scale, dstY * scale, dstWidth * scale, dstHeight * scale);
          }
          context.drawImage.apply(context, [source].concat(_toConsumableArray(params.map(function(param) {
            return Math.floor(normalizeDecimalNumber(param));
          }))));
          return canvas;
        },
        /**
         * Change the aspect ratio of the crop box.
         * @param {number} aspectRatio - The new aspect ratio.
         * @returns {Cropper} this
         */
        setAspectRatio: function setAspectRatio(aspectRatio) {
          var options = this.options;
          if (!this.disabled && !isUndefined(aspectRatio)) {
            options.aspectRatio = Math.max(0, aspectRatio) || NaN;
            if (this.ready) {
              this.initCropBox();
              if (this.cropped) {
                this.renderCropBox();
              }
            }
          }
          return this;
        },
        /**
         * Change the drag mode.
         * @param {string} mode - The new drag mode.
         * @returns {Cropper} this
         */
        setDragMode: function setDragMode(mode) {
          var options = this.options, dragBox = this.dragBox, face = this.face;
          if (this.ready && !this.disabled) {
            var croppable = mode === DRAG_MODE_CROP;
            var movable = options.movable && mode === DRAG_MODE_MOVE;
            mode = croppable || movable ? mode : DRAG_MODE_NONE;
            options.dragMode = mode;
            setData(dragBox, DATA_ACTION, mode);
            toggleClass(dragBox, CLASS_CROP, croppable);
            toggleClass(dragBox, CLASS_MOVE, movable);
            if (!options.cropBoxMovable) {
              setData(face, DATA_ACTION, mode);
              toggleClass(face, CLASS_CROP, croppable);
              toggleClass(face, CLASS_MOVE, movable);
            }
          }
          return this;
        }
      };
      var AnotherCropper = WINDOW.Cropper;
      var Cropper = /* @__PURE__ */ (function() {
        function Cropper2(element) {
          var options = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : {};
          _classCallCheck(this, Cropper2);
          if (!element || !REGEXP_TAG_NAME.test(element.tagName)) {
            throw new Error("The first argument is required and must be an <img> or <canvas> element.");
          }
          this.element = element;
          this.options = assign2({}, DEFAULTS, isPlainObject2(options) && options);
          this.cropped = false;
          this.disabled = false;
          this.pointers = {};
          this.ready = false;
          this.reloading = false;
          this.replaced = false;
          this.sized = false;
          this.sizing = false;
          this.init();
        }
        return _createClass(Cropper2, [{
          key: "init",
          value: function init() {
            var element = this.element;
            var tagName = element.tagName.toLowerCase();
            var url;
            if (element[NAMESPACE]) {
              return;
            }
            element[NAMESPACE] = this;
            if (tagName === "img") {
              this.isImg = true;
              url = element.getAttribute("src") || "";
              this.originalUrl = url;
              if (!url) {
                return;
              }
              url = element.src;
            } else if (tagName === "canvas" && window.HTMLCanvasElement) {
              url = element.toDataURL();
            }
            this.load(url);
          }
        }, {
          key: "load",
          value: function load(url) {
            var _this = this;
            if (!url) {
              return;
            }
            this.url = url;
            this.imageData = {};
            var element = this.element, options = this.options;
            if (!options.rotatable && !options.scalable) {
              options.checkOrientation = false;
            }
            if (!options.checkOrientation || !window.ArrayBuffer) {
              this.clone();
              return;
            }
            if (REGEXP_DATA_URL.test(url)) {
              if (REGEXP_DATA_URL_JPEG.test(url)) {
                this.read(dataURLToArrayBuffer(url));
              } else {
                this.clone();
              }
              return;
            }
            var xhr = new XMLHttpRequest();
            var clone = this.clone.bind(this);
            this.reloading = true;
            this.xhr = xhr;
            xhr.onabort = clone;
            xhr.onerror = clone;
            xhr.ontimeout = clone;
            xhr.onprogress = function() {
              if (xhr.getResponseHeader("content-type") !== MIME_TYPE_JPEG) {
                xhr.abort();
              }
            };
            xhr.onload = function() {
              _this.read(xhr.response);
            };
            xhr.onloadend = function() {
              _this.reloading = false;
              _this.xhr = null;
            };
            if (options.checkCrossOrigin && isCrossOriginURL(url) && element.crossOrigin) {
              url = addTimestamp(url);
            }
            xhr.open("GET", url, true);
            xhr.responseType = "arraybuffer";
            xhr.withCredentials = element.crossOrigin === "use-credentials";
            xhr.send();
          }
        }, {
          key: "read",
          value: function read(arrayBuffer) {
            var options = this.options, imageData = this.imageData;
            var orientation = resetAndGetOrientation(arrayBuffer);
            var rotate = 0;
            var scaleX = 1;
            var scaleY = 1;
            if (orientation > 1) {
              this.url = arrayBufferToDataURL(arrayBuffer, MIME_TYPE_JPEG);
              var _parseOrientation = parseOrientation(orientation);
              rotate = _parseOrientation.rotate;
              scaleX = _parseOrientation.scaleX;
              scaleY = _parseOrientation.scaleY;
            }
            if (options.rotatable) {
              imageData.rotate = rotate;
            }
            if (options.scalable) {
              imageData.scaleX = scaleX;
              imageData.scaleY = scaleY;
            }
            this.clone();
          }
        }, {
          key: "clone",
          value: function clone() {
            var element = this.element, url = this.url;
            var crossOrigin = element.crossOrigin;
            var crossOriginUrl = url;
            if (this.options.checkCrossOrigin && isCrossOriginURL(url)) {
              if (!crossOrigin) {
                crossOrigin = "anonymous";
              }
              crossOriginUrl = addTimestamp(url);
            }
            this.crossOrigin = crossOrigin;
            this.crossOriginUrl = crossOriginUrl;
            var image = document.createElement("img");
            if (crossOrigin) {
              image.crossOrigin = crossOrigin;
            }
            image.src = crossOriginUrl || url;
            image.alt = element.alt || "The image to crop";
            this.image = image;
            image.onload = this.start.bind(this);
            image.onerror = this.stop.bind(this);
            addClass(image, CLASS_HIDE);
            element.parentNode.insertBefore(image, element.nextSibling);
          }
        }, {
          key: "start",
          value: function start() {
            var _this2 = this;
            var image = this.image;
            image.onload = null;
            image.onerror = null;
            this.sizing = true;
            var isIOSWebKit = WINDOW.navigator && /(?:iPad|iPhone|iPod).*?AppleWebKit/i.test(WINDOW.navigator.userAgent);
            var done = function done2(naturalWidth, naturalHeight) {
              assign2(_this2.imageData, {
                naturalWidth,
                naturalHeight,
                aspectRatio: naturalWidth / naturalHeight
              });
              _this2.initialImageData = assign2({}, _this2.imageData);
              _this2.sizing = false;
              _this2.sized = true;
              _this2.build();
            };
            if (image.naturalWidth && !isIOSWebKit) {
              done(image.naturalWidth, image.naturalHeight);
              return;
            }
            var sizingImage = document.createElement("img");
            var body = document.body || document.documentElement;
            this.sizingImage = sizingImage;
            sizingImage.onload = function() {
              done(sizingImage.width, sizingImage.height);
              if (!isIOSWebKit) {
                body.removeChild(sizingImage);
              }
            };
            sizingImage.src = image.src;
            if (!isIOSWebKit) {
              sizingImage.style.cssText = "left:0;max-height:none!important;max-width:none!important;min-height:0!important;min-width:0!important;opacity:0;position:absolute;top:0;z-index:-1;";
              body.appendChild(sizingImage);
            }
          }
        }, {
          key: "stop",
          value: function stop() {
            var image = this.image;
            image.onload = null;
            image.onerror = null;
            image.parentNode.removeChild(image);
            this.image = null;
          }
        }, {
          key: "build",
          value: function build() {
            if (!this.sized || this.ready) {
              return;
            }
            var element = this.element, options = this.options, image = this.image;
            var container = element.parentNode;
            var template = document.createElement("div");
            template.innerHTML = TEMPLATE;
            var cropper2 = template.querySelector(".".concat(NAMESPACE, "-container"));
            var canvas = cropper2.querySelector(".".concat(NAMESPACE, "-canvas"));
            var dragBox = cropper2.querySelector(".".concat(NAMESPACE, "-drag-box"));
            var cropBox = cropper2.querySelector(".".concat(NAMESPACE, "-crop-box"));
            var face = cropBox.querySelector(".".concat(NAMESPACE, "-face"));
            this.container = container;
            this.cropper = cropper2;
            this.canvas = canvas;
            this.dragBox = dragBox;
            this.cropBox = cropBox;
            this.viewBox = cropper2.querySelector(".".concat(NAMESPACE, "-view-box"));
            this.face = face;
            canvas.appendChild(image);
            addClass(element, CLASS_HIDDEN);
            container.insertBefore(cropper2, element.nextSibling);
            removeClass(image, CLASS_HIDE);
            this.initPreview();
            this.bind();
            options.initialAspectRatio = Math.max(0, options.initialAspectRatio) || NaN;
            options.aspectRatio = Math.max(0, options.aspectRatio) || NaN;
            options.viewMode = Math.max(0, Math.min(3, Math.round(options.viewMode))) || 0;
            addClass(cropBox, CLASS_HIDDEN);
            if (!options.guides) {
              addClass(cropBox.getElementsByClassName("".concat(NAMESPACE, "-dashed")), CLASS_HIDDEN);
            }
            if (!options.center) {
              addClass(cropBox.getElementsByClassName("".concat(NAMESPACE, "-center")), CLASS_HIDDEN);
            }
            if (options.background) {
              addClass(cropper2, "".concat(NAMESPACE, "-bg"));
            }
            if (!options.highlight) {
              addClass(face, CLASS_INVISIBLE);
            }
            if (options.cropBoxMovable) {
              addClass(face, CLASS_MOVE);
              setData(face, DATA_ACTION, ACTION_ALL);
            }
            if (!options.cropBoxResizable) {
              addClass(cropBox.getElementsByClassName("".concat(NAMESPACE, "-line")), CLASS_HIDDEN);
              addClass(cropBox.getElementsByClassName("".concat(NAMESPACE, "-point")), CLASS_HIDDEN);
            }
            this.render();
            this.ready = true;
            this.setDragMode(options.dragMode);
            if (options.autoCrop) {
              this.crop();
            }
            this.setData(options.data);
            if (isFunction(options.ready)) {
              addListener(element, EVENT_READY, options.ready, {
                once: true
              });
            }
            dispatchEvent(element, EVENT_READY);
          }
        }, {
          key: "unbuild",
          value: function unbuild() {
            if (!this.ready) {
              return;
            }
            this.ready = false;
            this.unbind();
            this.resetPreview();
            var parentNode = this.cropper.parentNode;
            if (parentNode) {
              parentNode.removeChild(this.cropper);
            }
            removeClass(this.element, CLASS_HIDDEN);
          }
        }, {
          key: "uncreate",
          value: function uncreate() {
            if (this.ready) {
              this.unbuild();
              this.ready = false;
              this.cropped = false;
            } else if (this.sizing) {
              this.sizingImage.onload = null;
              this.sizing = false;
              this.sized = false;
            } else if (this.reloading) {
              this.xhr.onabort = null;
              this.xhr.abort();
            } else if (this.image) {
              this.stop();
            }
          }
          /**
           * Get the no conflict cropper class.
           * @returns {Cropper} The cropper class.
           */
        }], [{
          key: "noConflict",
          value: function noConflict() {
            window.Cropper = AnotherCropper;
            return Cropper2;
          }
          /**
           * Change the default options.
           * @param {Object} options - The new default options.
           */
        }, {
          key: "setDefaults",
          value: function setDefaults(options) {
            assign2(DEFAULTS, isPlainObject2(options) && options);
          }
        }]);
      })();
      assign2(Cropper.prototype, render, preview, events, handlers, change, methods);
      return Cropper;
    }));
  })(cropper$1);
  return cropper$1.exports;
}
var hasRequiredVueCropper;
function requireVueCropper() {
  if (hasRequiredVueCropper) return VueCropper$1;
  hasRequiredVueCropper = 1;
  Object.defineProperty(VueCropper$1, "__esModule", {
    value: true
  });
  var _vue = requireVue();
  var _cropperjs = requireCropper();
  var _cropperjs2 = _interopRequireDefault(_cropperjs);
  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : { default: obj };
  }
  function _objectWithoutProperties(obj, keys) {
    var target = {};
    for (var i in obj) {
      if (keys.indexOf(i) >= 0) continue;
      if (!Object.prototype.hasOwnProperty.call(obj, i)) continue;
      target[i] = obj[i];
    }
    return target;
  }
  var previewPropType = typeof window === "undefined" ? [String, Array] : [String, Array, Element, NodeList];
  VueCropper$1.default = {
    render: function render() {
      var crossorigin = this.crossorigin || void 0;
      return (0, _vue.h)("div", { style: this.containerStyle }, [(0, _vue.h)("img", {
        ref: "img",
        src: this.src,
        alt: this.alt || "image",
        style: [{ "max-width": "100%" }, this.imgStyle],
        crossorigin
      })]);
    },
    props: {
      containerStyle: Object,
      src: {
        type: String,
        default: ""
      },
      alt: String,
      imgStyle: Object,
      viewMode: Number,
      dragMode: String,
      initialAspectRatio: Number,
      aspectRatio: Number,
      data: Object,
      preview: previewPropType,
      responsive: {
        type: Boolean,
        default: true
      },
      restore: {
        type: Boolean,
        default: true
      },
      checkCrossOrigin: {
        type: Boolean,
        default: true
      },
      checkOrientation: {
        type: Boolean,
        default: true
      },
      crossorigin: {
        type: String
      },
      modal: {
        type: Boolean,
        default: true
      },
      guides: {
        type: Boolean,
        default: true
      },
      center: {
        type: Boolean,
        default: true
      },
      highlight: {
        type: Boolean,
        default: true
      },
      background: {
        type: Boolean,
        default: true
      },
      autoCrop: {
        type: Boolean,
        default: true
      },
      autoCropArea: Number,
      movable: {
        type: Boolean,
        default: true
      },
      rotatable: {
        type: Boolean,
        default: true
      },
      scalable: {
        type: Boolean,
        default: true
      },
      zoomable: {
        type: Boolean,
        default: true
      },
      zoomOnTouch: {
        type: Boolean,
        default: true
      },
      zoomOnWheel: {
        type: Boolean,
        default: true
      },
      wheelZoomRatio: Number,
      cropBoxMovable: {
        type: Boolean,
        default: true
      },
      cropBoxResizable: {
        type: Boolean,
        default: true
      },
      toggleDragModeOnDblclick: {
        type: Boolean,
        default: true
      },
      minCanvasWidth: Number,
      minCanvasHeight: Number,
      minCropBoxWidth: Number,
      minCropBoxHeight: Number,
      minContainerWidth: Number,
      minContainerHeight: Number,
      ready: Function,
      cropstart: Function,
      cropmove: Function,
      cropend: Function,
      crop: Function,
      zoom: Function
    },
    mounted: function mounted() {
      var _$options$props = this.$options.props;
      _$options$props.containerStyle;
      _$options$props.src;
      _$options$props.alt;
      _$options$props.imgStyle;
      var data = _objectWithoutProperties(_$options$props, ["containerStyle", "src", "alt", "imgStyle"]);
      var props = {};
      for (var key in data) {
        if (this[key] !== void 0) {
          props[key] = this[key];
        }
      }
      this.cropper = new _cropperjs2.default(this.$refs.img, props);
    },
    methods: {
      reset: function reset2() {
        return this.cropper.reset();
      },
      clear: function clear() {
        return this.cropper.clear();
      },
      initCrop: function initCrop() {
        return this.cropper.crop();
      },
      replace: function replace(url) {
        var onlyColorChanged = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : false;
        return this.cropper.replace(url, onlyColorChanged);
      },
      enable: function enable() {
        return this.cropper.enable();
      },
      disable: function disable() {
        return this.cropper.disable();
      },
      destroy: function destroy() {
        return this.cropper.destroy();
      },
      move: function move(offsetX, offsetY) {
        return this.cropper.move(offsetX, offsetY);
      },
      moveTo: function moveTo(x2) {
        var y2 = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : x2;
        return this.cropper.moveTo(x2, y2);
      },
      relativeZoom: function relativeZoom(ratio, _originalEvent) {
        return this.cropper.zoom(ratio, _originalEvent);
      },
      zoomTo: function zoomTo(ratio, _originalEvent) {
        return this.cropper.zoomTo(ratio, _originalEvent);
      },
      rotate: function rotate(degree) {
        return this.cropper.rotate(degree);
      },
      rotateTo: function rotateTo(degree) {
        return this.cropper.rotateTo(degree);
      },
      scaleX: function scaleX(_scaleX) {
        return this.cropper.scaleX(_scaleX);
      },
      scaleY: function scaleY(_scaleY) {
        return this.cropper.scaleY(_scaleY);
      },
      scale: function scale(scaleX) {
        var scaleY = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : scaleX;
        return this.cropper.scale(scaleX, scaleY);
      },
      getData: function getData() {
        var rounded = arguments.length > 0 && arguments[0] !== void 0 ? arguments[0] : false;
        return this.cropper.getData(rounded);
      },
      setData: function setData(data) {
        return this.cropper.setData(data);
      },
      getContainerData: function getContainerData() {
        return this.cropper.getContainerData();
      },
      getImageData: function getImageData() {
        return this.cropper.getImageData();
      },
      getCanvasData: function getCanvasData() {
        return this.cropper.getCanvasData();
      },
      setCanvasData: function setCanvasData(data) {
        return this.cropper.setCanvasData(data);
      },
      getCropBoxData: function getCropBoxData() {
        return this.cropper.getCropBoxData();
      },
      setCropBoxData: function setCropBoxData(data) {
        return this.cropper.setCropBoxData(data);
      },
      getCroppedCanvas: function getCroppedCanvas() {
        var options = arguments.length > 0 && arguments[0] !== void 0 ? arguments[0] : {};
        return this.cropper.getCroppedCanvas(options);
      },
      setAspectRatio: function setAspectRatio(aspectRatio) {
        return this.cropper.setAspectRatio(aspectRatio);
      },
      setDragMode: function setDragMode(mode) {
        return this.cropper.setDragMode(mode);
      }
    }
  };
  return VueCropper$1;
}
var VueCropperExports = requireVueCropper();
const VueCropper = /* @__PURE__ */ getDefaultExportFromCjs(VueCropperExports);
const _sfc_main$y = {
  name: "AccountGroupOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$u = ["aria-hidden", "aria-label"];
const _hoisted_2$s = ["fill", "width", "height"];
const _hoisted_3$q = { d: "M12,5A3.5,3.5 0 0,0 8.5,8.5A3.5,3.5 0 0,0 12,12A3.5,3.5 0 0,0 15.5,8.5A3.5,3.5 0 0,0 12,5M12,7A1.5,1.5 0 0,1 13.5,8.5A1.5,1.5 0 0,1 12,10A1.5,1.5 0 0,1 10.5,8.5A1.5,1.5 0 0,1 12,7M5.5,8A2.5,2.5 0 0,0 3,10.5C3,11.44 3.53,12.25 4.29,12.68C4.65,12.88 5.06,13 5.5,13C5.94,13 6.35,12.88 6.71,12.68C7.08,12.47 7.39,12.17 7.62,11.81C6.89,10.86 6.5,9.7 6.5,8.5C6.5,8.41 6.5,8.31 6.5,8.22C6.2,8.08 5.86,8 5.5,8M18.5,8C18.14,8 17.8,8.08 17.5,8.22C17.5,8.31 17.5,8.41 17.5,8.5C17.5,9.7 17.11,10.86 16.38,11.81C16.5,12 16.63,12.15 16.78,12.3C16.94,12.45 17.1,12.58 17.29,12.68C17.65,12.88 18.06,13 18.5,13C18.94,13 19.35,12.88 19.71,12.68C20.47,12.25 21,11.44 21,10.5A2.5,2.5 0 0,0 18.5,8M12,14C9.66,14 5,15.17 5,17.5V19H19V17.5C19,15.17 14.34,14 12,14M4.71,14.55C2.78,14.78 0,15.76 0,17.5V19H3V17.07C3,16.06 3.69,15.22 4.71,14.55M19.29,14.55C20.31,15.22 21,16.06 21,17.07V19H24V17.5C24,15.76 21.22,14.78 19.29,14.55M12,16C13.53,16 15.24,16.5 16.23,17H7.77C8.76,16.5 10.47,16 12,16Z" };
const _hoisted_4$q = { key: 0 };
function _sfc_render$v(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon account-group-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$q, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$q, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$s))
  ], 16, _hoisted_1$u);
}
const IconAccountGroup = /* @__PURE__ */ _export_sfc$1(_sfc_main$y, [["render", _sfc_render$v]]);
const _sfc_main$x = {
  name: "AccountPlusOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$t = ["aria-hidden", "aria-label"];
const _hoisted_2$r = ["fill", "width", "height"];
const _hoisted_3$p = { d: "M15,4A4,4 0 0,0 11,8A4,4 0 0,0 15,12A4,4 0 0,0 19,8A4,4 0 0,0 15,4M15,5.9C16.16,5.9 17.1,6.84 17.1,8C17.1,9.16 16.16,10.1 15,10.1A2.1,2.1 0 0,1 12.9,8A2.1,2.1 0 0,1 15,5.9M4,7V10H1V12H4V15H6V12H9V10H6V7H4M15,13C12.33,13 7,14.33 7,17V20H23V17C23,14.33 17.67,13 15,13M15,14.9C17.97,14.9 21.1,16.36 21.1,17V18.1H8.9V17C8.9,16.36 12,14.9 15,14.9Z" };
const _hoisted_4$p = { key: 0 };
function _sfc_render$u(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon account-plus-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$p, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$p, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$r))
  ], 16, _hoisted_1$t);
}
const AccountPlusIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$x, [["render", _sfc_render$u]]);
const _sfc_main$w = {
  name: "BookOpenPageVariantIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$s = ["aria-hidden", "aria-label"];
const _hoisted_2$q = ["fill", "width", "height"];
const _hoisted_3$o = { d: "M19 2L14 6.5V17.5L19 13V2M6.5 5C4.55 5 2.45 5.4 1 6.5V21.16C1 21.41 1.25 21.66 1.5 21.66C1.6 21.66 1.65 21.59 1.75 21.59C3.1 20.94 5.05 20.5 6.5 20.5C8.45 20.5 10.55 20.9 12 22C13.35 21.15 15.8 20.5 17.5 20.5C19.15 20.5 20.85 20.81 22.25 21.56C22.35 21.61 22.4 21.59 22.5 21.59C22.75 21.59 23 21.34 23 21.09V6.5C22.4 6.05 21.75 5.75 21 5.5V19C19.9 18.65 18.7 18.5 17.5 18.5C15.8 18.5 13.35 19.15 12 20V6.5C10.55 5.4 8.45 5 6.5 5Z" };
const _hoisted_4$o = { key: 0 };
function _sfc_render$t(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon book-open-page-variant-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$o, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$o, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$q))
  ], 16, _hoisted_1$s);
}
const BookOpenPageVariantIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$w, [["render", _sfc_render$t]]);
const _sfc_main$v = {
  name: "CalendarOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$r = ["aria-hidden", "aria-label"];
const _hoisted_2$p = ["fill", "width", "height"];
const _hoisted_3$n = { d: "M12 12H17V17H12V12M19 3H18V1H16V3H8V1H6V3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3M19 5V7H5V5H19M5 19V9H19V19H5Z" };
const _hoisted_4$n = { key: 0 };
function _sfc_render$s(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon calendar-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$n, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$n, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$p))
  ], 16, _hoisted_1$r);
}
const CalendarIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$v, [["render", _sfc_render$s]]);
const _sfc_main$u = {
  name: "CheckIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$q = ["aria-hidden", "aria-label"];
const _hoisted_2$o = ["fill", "width", "height"];
const _hoisted_3$m = { d: "M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" };
const _hoisted_4$m = { key: 0 };
function _sfc_render$r(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon check-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$m, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$m, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$o))
  ], 16, _hoisted_1$q);
}
const CheckIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$u, [["render", _sfc_render$r]]);
const _sfc_main$t = {
  name: "CogOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$p = ["aria-hidden", "aria-label"];
const _hoisted_2$n = ["fill", "width", "height"];
const _hoisted_3$l = { d: "M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8M12,10A2,2 0 0,0 10,12A2,2 0 0,0 12,14A2,2 0 0,0 14,12A2,2 0 0,0 12,10M10,22C9.75,22 9.54,21.82 9.5,21.58L9.13,18.93C8.5,18.68 7.96,18.34 7.44,17.94L4.95,18.95C4.73,19.03 4.46,18.95 4.34,18.73L2.34,15.27C2.21,15.05 2.27,14.78 2.46,14.63L4.57,12.97L4.5,12L4.57,11L2.46,9.37C2.27,9.22 2.21,8.95 2.34,8.73L4.34,5.27C4.46,5.05 4.73,4.96 4.95,5.05L7.44,6.05C7.96,5.66 8.5,5.32 9.13,5.07L9.5,2.42C9.54,2.18 9.75,2 10,2H14C14.25,2 14.46,2.18 14.5,2.42L14.87,5.07C15.5,5.32 16.04,5.66 16.56,6.05L19.05,5.05C19.27,4.96 19.54,5.05 19.66,5.27L21.66,8.73C21.79,8.95 21.73,9.22 21.54,9.37L19.43,11L19.5,12L19.43,13L21.54,14.63C21.73,14.78 21.79,15.05 21.66,15.27L19.66,18.73C19.54,18.95 19.27,19.04 19.05,18.95L16.56,17.95C16.04,18.34 15.5,18.68 14.87,18.93L14.5,21.58C14.46,21.82 14.25,22 14,22H10M11.25,4L10.88,6.61C9.68,6.86 8.62,7.5 7.85,8.39L5.44,7.35L4.69,8.65L6.8,10.2C6.4,11.37 6.4,12.64 6.8,13.8L4.68,15.36L5.43,16.66L7.86,15.62C8.63,16.5 9.68,17.14 10.87,17.38L11.24,20H12.76L13.13,17.39C14.32,17.14 15.37,16.5 16.14,15.62L18.57,16.66L19.32,15.36L17.2,13.81C17.6,12.64 17.6,11.37 17.2,10.2L19.31,8.65L18.56,7.35L16.15,8.39C15.38,7.5 14.32,6.86 13.12,6.62L12.75,4H11.25Z" };
const _hoisted_4$l = { key: 0 };
function _sfc_render$q(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon cog-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$l, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$l, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$n))
  ], 16, _hoisted_1$p);
}
const CogIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$t, [["render", _sfc_render$q]]);
const _sfc_main$s = {
  name: "ContentCopyIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$o = ["aria-hidden", "aria-label"];
const _hoisted_2$m = ["fill", "width", "height"];
const _hoisted_3$k = { d: "M19,21H8V7H19M19,5H8A2,2 0 0,0 6,7V21A2,2 0 0,0 8,23H19A2,2 0 0,0 21,21V7A2,2 0 0,0 19,5M16,1H4A2,2 0 0,0 2,3V17H4V3H16V1Z" };
const _hoisted_4$k = { key: 0 };
function _sfc_render$p(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon content-copy-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$k, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$k, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$m))
  ], 16, _hoisted_1$o);
}
const CopyIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$s, [["render", _sfc_render$p]]);
const _sfc_main$r = {
  name: "FileDocumentOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$n = ["aria-hidden", "aria-label"];
const _hoisted_2$l = ["fill", "width", "height"];
const _hoisted_3$j = { d: "M6,2A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2H6M6,4H13V9H18V20H6V4M8,12V14H16V12H8M8,16V18H13V16H8Z" };
const _hoisted_4$j = { key: 0 };
function _sfc_render$o(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon file-document-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$j, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$j, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$l))
  ], 16, _hoisted_1$n);
}
const FileDocumentOutline = /* @__PURE__ */ _export_sfc$1(_sfc_main$r, [["render", _sfc_render$o]]);
const _sfc_main$q = {
  name: "FolderIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$m = ["aria-hidden", "aria-label"];
const _hoisted_2$k = ["fill", "width", "height"];
const _hoisted_3$i = { d: "M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z" };
const _hoisted_4$i = { key: 0 };
function _sfc_render$n(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon folder-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$i, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$i, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$k))
  ], 16, _hoisted_1$m);
}
const FolderIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$q, [["render", _sfc_render$n]]);
const _sfc_main$p = {
  name: "FolderOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$l = ["aria-hidden", "aria-label"];
const _hoisted_2$j = ["fill", "width", "height"];
const _hoisted_3$h = { d: "M20,18H4V8H20M20,6H12L10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6Z" };
const _hoisted_4$h = { key: 0 };
function _sfc_render$m(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon folder-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$h, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$h, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$j))
  ], 16, _hoisted_1$l);
}
const FolderOutlineIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$p, [["render", _sfc_render$m]]);
const _sfc_main$o = {
  name: "LoginIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$k = ["aria-hidden", "aria-label"];
const _hoisted_2$i = ["fill", "width", "height"];
const _hoisted_3$g = { d: "M11 7L9.6 8.4L12.2 11H2V13H12.2L9.6 15.6L11 17L16 12L11 7M20 19H12V21H20C21.1 21 22 20.1 22 19V5C22 3.9 21.1 3 20 3H12V5H20V19Z" };
const _hoisted_4$g = { key: 0 };
function _sfc_render$l(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon login-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$g, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$g, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$i))
  ], 16, _hoisted_1$k);
}
const LoginIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$o, [["render", _sfc_render$l]]);
const _sfc_main$n = {
  name: "LogoutIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$j = ["aria-hidden", "aria-label"];
const _hoisted_2$h = ["fill", "width", "height"];
const _hoisted_3$f = { d: "M17 7L15.59 8.41L18.17 11H8V13H18.17L15.59 15.58L17 17L22 12M4 5H12V3H4C2.9 3 2 3.9 2 5V19C2 20.1 2.9 21 4 21H12V19H4V5Z" };
const _hoisted_4$f = { key: 0 };
function _sfc_render$k(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon logout-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$f, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$f, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$h))
  ], 16, _hoisted_1$j);
}
const LogoutIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$n, [["render", _sfc_render$k]]);
const _sfc_main$m = {
  name: "MessageOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$i = ["aria-hidden", "aria-label"];
const _hoisted_2$g = ["fill", "width", "height"];
const _hoisted_3$e = { d: "M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2M20 16H5.2L4 17.2V4H20V16Z" };
const _hoisted_4$e = { key: 0 };
function _sfc_render$j(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon message-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$e, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$e, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$g))
  ], 16, _hoisted_1$i);
}
const MessageIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$m, [["render", _sfc_render$j]]);
const _sfc_main$l = {
  name: "PencilOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$h = ["aria-hidden", "aria-label"];
const _hoisted_2$f = ["fill", "width", "height"];
const _hoisted_3$d = { d: "M14.06,9L15,9.94L5.92,19H5V18.08L14.06,9M17.66,3C17.41,3 17.15,3.1 16.96,3.29L15.13,5.12L18.88,8.87L20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18.17,3.09 17.92,3 17.66,3M14.06,6.19L3,17.25V21H6.75L17.81,9.94L14.06,6.19Z" };
const _hoisted_4$d = { key: 0 };
function _sfc_render$i(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon pencil-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$d, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$d, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$f))
  ], 16, _hoisted_1$h);
}
const PencilIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$l, [["render", _sfc_render$i]]);
const _sfc_main$k = {
  name: "TrashCanOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$g = ["aria-hidden", "aria-label"];
const _hoisted_2$e = ["fill", "width", "height"];
const _hoisted_3$c = { d: "M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z" };
const _hoisted_4$c = { key: 0 };
function _sfc_render$h(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon trash-can-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$c, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$c, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$e))
  ], 16, _hoisted_1$g);
}
const TrashCanOutlineIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$k, [["render", _sfc_render$h]]);
const _sfc_main$j = {
  name: "TrayArrowUpIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$f = ["aria-hidden", "aria-label"];
const _hoisted_2$d = ["fill", "width", "height"];
const _hoisted_3$b = { d: "M2 12H4V17H20V12H22V17C22 18.11 21.11 19 20 19H4C2.9 19 2 18.11 2 17V12M12 2L6.46 7.46L7.88 8.88L11 5.75V15H13V5.75L16.13 8.88L17.55 7.45L12 2Z" };
const _hoisted_4$b = { key: 0 };
function _sfc_render$g(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon tray-arrow-up-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$b, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$b, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$d))
  ], 16, _hoisted_1$f);
}
const TrayArrowUpIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$j, [["render", _sfc_render$g]]);
const _sfc_main$i = {
  name: "ViewDashboardIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$e = ["aria-hidden", "aria-label"];
const _hoisted_2$c = ["fill", "width", "height"];
const _hoisted_3$a = { d: "M13,3V9H21V3M13,21H21V11H13M3,21H11V15H3M3,13H11V3H3V13Z" };
const _hoisted_4$a = { key: 0 };
function _sfc_render$f(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon view-dashboard-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$a, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$a, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$c))
  ], 16, _hoisted_1$e);
}
const ViewDashboardIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$i, [["render", _sfc_render$f]]);
const _hoisted_1$d = { class: "circle-config" };
const _hoisted_2$b = { class: "circle-config__list" };
const _hoisted_3$9 = { class: "unique-password" };
const _hoisted_4$9 = ["disabled", "placeholder"];
const _hoisted_5$2 = {
  key: 2,
  class: "unique-password-error"
};
const ENFORCE_PASSWORD_PROTECTION = "enforce_password";
const USE_UNIQUE_PASSWORD = "password_single_enabled";
const UNIQUE_PASSWORD = "password_single";
const _sfc_main$h = /* @__PURE__ */ defineComponent({
  __name: "CirclePasswordSettings",
  props: {
    circle: {}
  },
  setup(__props) {
    const props = __props;
    const store2 = useStore();
    const loading = ref([]);
    const uniquePassword = ref("");
    const uniquePasswordError = ref(false);
    const showUniquePasswordInput = ref(false);
    const circleId = computed(() => props.circle._data.id);
    const enforcePasswordProtection = computed(() => {
      const value = props.circle._data.settings[ENFORCE_PASSWORD_PROTECTION];
      return value === "1" || value === "true";
    });
    const useUniquePassword = computed(() => {
      const value = props.circle._data.settings[USE_UNIQUE_PASSWORD];
      return value === "1" || value === "true";
    });
    async function changePasswordProtection() {
      loading.value.push(ENFORCE_PASSWORD_PROTECTION);
      try {
        const newValue = !enforcePasswordProtection.value;
        if (!newValue && useUniquePassword.value) {
          await saveUseUniquePassword(false);
        }
        if (!newValue && showUniquePasswordInput.value) {
          showUniquePasswordInput.value = false;
        }
        await store2.dispatch("editCircleSetting", {
          circleId: circleId.value,
          setting: {
            setting: ENFORCE_PASSWORD_PROTECTION,
            value: newValue.toString()
          }
        });
      } finally {
        loading.value = loading.value.filter((item) => item !== ENFORCE_PASSWORD_PROTECTION);
      }
    }
    async function changeUseUniquePassword() {
      if (!useUniquePassword.value) {
        showUniquePasswordInput.value = !showUniquePasswordInput.value;
        return;
      }
      await saveUseUniquePassword(!useUniquePassword.value);
    }
    async function saveUseUniquePassword(value) {
      loading.value.push(USE_UNIQUE_PASSWORD);
      try {
        await store2.dispatch("editCircleSetting", {
          circleId: circleId.value,
          setting: {
            setting: USE_UNIQUE_PASSWORD,
            value: value.toString()
          }
        });
        if (!value) {
          uniquePassword.value = "";
          showUniquePasswordInput.value = false;
        }
      } finally {
        loading.value = loading.value.filter((item) => item !== USE_UNIQUE_PASSWORD);
      }
    }
    async function saveUniquePassword() {
      if (uniquePassword.value.length === 0) {
        return;
      }
      loading.value.push(UNIQUE_PASSWORD);
      uniquePasswordError.value = false;
      try {
        if (!useUniquePassword.value) {
          await saveUseUniquePassword(true);
        }
        await store2.dispatch("editCircleSetting", {
          circleId: circleId.value,
          setting: {
            setting: UNIQUE_PASSWORD,
            value: uniquePassword.value
          }
        });
        showUniquePasswordInput.value = false;
        uniquePassword.value = "";
      } catch {
        uniquePasswordError.value = true;
      } finally {
        loading.value = loading.value.filter((item) => item !== UNIQUE_PASSWORD);
      }
    }
    function onClickChangePassword() {
      showUniquePasswordInput.value = true;
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("ul", null, [
        createBaseVNode("li", _hoisted_1$d, [
          createBaseVNode("ul", _hoisted_2$b, [
            createVNode(unref(NcCheckboxRadioSwitch), {
              "model-value": enforcePasswordProtection.value,
              loading: loading.value.includes(ENFORCE_PASSWORD_PROTECTION),
              disabled: loading.value.length > 0,
              "wrapper-element": "li",
              "onUpdate:modelValue": changePasswordProtection
            }, {
              default: withCtx(() => [
                createTextVNode(toDisplayString(unref(translate)("circles", "Enforce password protection on files shared to this team")), 1)
              ]),
              _: 1
            }, 8, ["model-value", "loading", "disabled"]),
            enforcePasswordProtection.value ? (openBlock(), createBlock(unref(NcCheckboxRadioSwitch), {
              key: 0,
              "model-value": useUniquePassword.value || showUniquePasswordInput.value,
              loading: loading.value.includes(USE_UNIQUE_PASSWORD),
              disabled: loading.value.length > 0,
              "wrapper-element": "li",
              "onUpdate:modelValue": changeUseUniquePassword
            }, {
              default: withCtx(() => [
                createTextVNode(toDisplayString(unref(translate)("circles", "Use a unique password for all shares to this team")), 1)
              ]),
              _: 1
            }, 8, ["model-value", "loading", "disabled"])) : createCommentVNode("", true),
            createBaseVNode("li", _hoisted_3$9, [
              showUniquePasswordInput.value ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
                withDirectives(createBaseVNode("input", {
                  "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => uniquePassword.value = $event),
                  disabled: loading.value.length > 0,
                  placeholder: unref(translate)("circles", "Unique password …"),
                  type: "text",
                  onKeyup: withKeys(saveUniquePassword, ["enter"])
                }, null, 40, _hoisted_4$9), [
                  [vModelText, uniquePassword.value]
                ]),
                createVNode(unref(NcButton), {
                  variant: "tertiary-no-background",
                  disabled: loading.value.length > 0 || uniquePassword.value.length === 0,
                  onClick: saveUniquePassword
                }, {
                  default: withCtx(() => [
                    createTextVNode(toDisplayString(unref(translate)("circles", "Save")), 1)
                  ]),
                  _: 1
                }, 8, ["disabled"])
              ], 64)) : useUniquePassword.value ? (openBlock(), createBlock(unref(NcButton), {
                key: 1,
                class: "change-unique-password",
                onClick: onClickChangePassword
              }, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString(unref(translate)("circles", "Change unique password")), 1)
                ]),
                _: 1
              })) : createCommentVNode("", true),
              uniquePasswordError.value ? (openBlock(), createElementBlock("div", _hoisted_5$2, toDisplayString(unref(translate)("circles", "Failed to save password. Please try again later.")), 1)) : createCommentVNode("", true)
            ])
          ])
        ])
      ]);
    };
  }
});
const CirclePasswordSettings = /* @__PURE__ */ _export_sfc$1(_sfc_main$h, [["__scopeId", "data-v-a41c58f8"]]);
const appContentHeading = "_app-content-heading_ihaqk_1";
const appContentHeadingLoader = "_app-content-heading__loader_ihaqk_8";
const style0$3 = {
  appContentHeading,
  appContentHeadingLoader
};
const _sfc_main$g = {
  name: "ContentHeading",
  props: {
    loading: {
      type: Boolean,
      default: false
    }
  }
};
function _sfc_render$e(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("h3", {
    class: normalizeClass(_ctx.$style.appContentHeading)
  }, [
    renderSlot(_ctx.$slots, "default"),
    $props.loading ? (openBlock(), createElementBlock("div", {
      key: 0,
      class: normalizeClass([_ctx.$style.appContentHeadingLoader, "icon-loading-small"])
    }, null, 2)) : createCommentVNode("", true)
  ], 2);
}
const cssModules$3 = {
  "$style": style0$3
};
const ContentHeading = /* @__PURE__ */ _export_sfc$1(_sfc_main$g, [["render", _sfc_render$e], ["__cssModules", cssModules$3]]);
translate("circles", "All contacts");
translate("circles", "Not grouped");
translate("circles", "Organization chart");
translate("circles", "Contacts settings");
const MEMBER_LEVEL_NONE = 0;
const MEMBER_LEVEL_MEMBER = 1;
const MEMBER_LEVEL_MODERATOR = 4;
const MEMBER_LEVEL_ADMIN = 8;
const MEMBER_LEVEL_OWNER = 9;
const MEMBER_TYPE_USER = 1;
const MEMBER_TYPE_GROUP = 2;
const MEMBER_TYPE_MAIL = 4;
const MEMBER_TYPE_CONTACT = 8;
const MEMBER_TYPE_CIRCLE = 16;
translate("circles", "Create your own teams for sharing. Add Nextcloud users, contacts, or anyone via email.");
const CIRCLE_CONFIG_PERSONAL = 2;
const CIRCLE_CONFIG_SYSTEM = 4;
const CIRCLE_CONFIG_VISIBLE = 8;
const CIRCLE_CONFIG_OPEN = 16;
const CIRCLE_CONFIG_INVITE = 32;
const CIRCLE_CONFIG_REQUEST = 64;
const CIRCLE_CONFIG_FRIEND = 128;
const CIRCLE_CONFIG_PROTECTED = 256;
const CIRCLE_CONFIG_NO_OWNER = 512;
const CIRCLE_CONFIG_HIDDEN = 1024;
const CIRCLE_CONFIG_BACKEND = 2048;
const CIRCLE_CONFIG_LOCAL = 4096;
const CIRCLE_CONFIG_ROOT = 8192;
const CIRCLE_CONFIG_CIRCLE_INVITE = 16384;
const CIRCLE_CONFIG_FEDERATED = 32768;
({
  [MEMBER_TYPE_CIRCLE]: translate("circles", "Team"),
  [MEMBER_TYPE_USER]: translate("circles", "User"),
  [MEMBER_TYPE_GROUP]: translate("circles", "Group"),
  [MEMBER_TYPE_MAIL]: translate("circles", "Email"),
  [MEMBER_TYPE_CONTACT]: translate("circles", "Contact")
});
const CIRCLES_MEMBER_LEVELS = {
  // [MEMBER_LEVEL_NONE]: t('circles', 'Pending'),
  [MEMBER_LEVEL_MEMBER]: translate("circles", "Member"),
  [MEMBER_LEVEL_MODERATOR]: translate("circles", "Moderator"),
  [MEMBER_LEVEL_ADMIN]: translate("circles", "Admin"),
  [MEMBER_LEVEL_OWNER]: translate("circles", "Owner")
};
const PUBLIC_CIRCLE_CONFIG = {
  [translate("circles", "Invites")]: {
    [CIRCLE_CONFIG_OPEN]: translate("circles", "Anyone can request membership"),
    [CIRCLE_CONFIG_INVITE]: translate("circles", "Members need to accept invitation"),
    [CIRCLE_CONFIG_REQUEST]: translate("circles", 'Memberships must be confirmed/accepted by a Moderator (requires "Anyone can request membership")'),
    [CIRCLE_CONFIG_FRIEND]: translate("circles", "Members can also invite")
  },
  [translate("circles", "Membership")]: {
    // TODO: implement backend
    // [CIRCLE_CONFIG_CIRCLE_INVITE]: t('circles', 'Team must confirm when invited in another circle'),
    [CIRCLE_CONFIG_ROOT]: translate("circles", "Prevent teams from being a member of another team")
  },
  [translate("circles", "Federation")]: {
    [CIRCLE_CONFIG_FEDERATED]: translate("circles", "Allow federated members")
  },
  [translate("circles", "Privacy")]: {
    [CIRCLE_CONFIG_VISIBLE]: translate("circles", "Visible to everyone")
  }
};
const CIRCLES_MEMBER_GROUPING = [
  {
    id: `picker-${ShareType.User}`,
    label: translate("circles", "users"),
    labelStandalone: translate("circles", "Users"),
    share: ShareType.User,
    type: MEMBER_TYPE_USER
  },
  {
    id: `picker-${ShareType.Group}`,
    label: translate("circles", "groups"),
    labelStandalone: translate("circles", "Groups"),
    share: ShareType.Group,
    type: MEMBER_TYPE_GROUP
  },
  {
    id: `picker-${ShareType.Remote}`,
    label: translate("circles", "federated users"),
    labelStandalone: translate("circles", "Federated users"),
    share: ShareType.Remote,
    type: MEMBER_TYPE_USER
  },
  // {
  // id: `picker-${ShareType.RemoteGroup}`,
  // label: t('circles', 'federated groups'),
  // share: ShareType.RemoteGroup,
  // type: MEMBER_TYPE_GROUP
  // },
  {
    id: `picker-${ShareType.Team}`,
    label: translate("circles", "teams"),
    labelStandalone: translate("circles", "Teams"),
    share: ShareType.Team,
    type: MEMBER_TYPE_CIRCLE
  },
  {
    id: `picker-${ShareType.Email}`,
    label: translate("circles", "email addresses"),
    labelStandalone: translate("circles", "Email addresses"),
    share: ShareType.Email,
    type: MEMBER_TYPE_MAIL
  },
  // TODO: implement SHARE_TYPE_CONTACT
  {
    id: "picker-contact",
    label: translate("circles", "contacts"),
    labelStandalone: translate("circles", "Contacts"),
    share: ShareType.Email,
    type: MEMBER_TYPE_CONTACT
  }
];
const SHARES_TYPES_MEMBER_MAP = CIRCLES_MEMBER_GROUPING.reduce((list, entry) => {
  if (!list[entry.share]) {
    list[entry.share] = entry.type;
  }
  return list;
}, {});
var MemberLevels = /* @__PURE__ */ ((MemberLevels2) => {
  MemberLevels2[MemberLevels2["NONE"] = MEMBER_LEVEL_NONE] = "NONE";
  MemberLevels2[MemberLevels2["MEMBER"] = MEMBER_LEVEL_MEMBER] = "MEMBER";
  MemberLevels2[MemberLevels2["MODERATOR"] = MEMBER_LEVEL_MODERATOR] = "MODERATOR";
  MemberLevels2[MemberLevels2["ADMIN"] = MEMBER_LEVEL_ADMIN] = "ADMIN";
  MemberLevels2[MemberLevels2["OWNER"] = MEMBER_LEVEL_OWNER] = "OWNER";
  return MemberLevels2;
})(MemberLevels || {});
var MemberTypes = /* @__PURE__ */ ((MemberTypes2) => {
  MemberTypes2[MemberTypes2["CIRCLE"] = MEMBER_TYPE_CIRCLE] = "CIRCLE";
  MemberTypes2[MemberTypes2["USER"] = MEMBER_TYPE_USER] = "USER";
  MemberTypes2[MemberTypes2["GROUP"] = MEMBER_TYPE_GROUP] = "GROUP";
  MemberTypes2[MemberTypes2["MAIL"] = MEMBER_TYPE_MAIL] = "MAIL";
  MemberTypes2[MemberTypes2["CONTACT"] = MEMBER_TYPE_CONTACT] = "CONTACT";
  return MemberTypes2;
})(MemberTypes || {});
var CircleConfigs = /* @__PURE__ */ ((CircleConfigs2) => {
  CircleConfigs2[CircleConfigs2["PERSONAL"] = CIRCLE_CONFIG_PERSONAL] = "PERSONAL";
  CircleConfigs2[CircleConfigs2["SYSTEM"] = CIRCLE_CONFIG_SYSTEM] = "SYSTEM";
  CircleConfigs2[CircleConfigs2["VISIBLE"] = CIRCLE_CONFIG_VISIBLE] = "VISIBLE";
  CircleConfigs2[CircleConfigs2["OPEN"] = CIRCLE_CONFIG_OPEN] = "OPEN";
  CircleConfigs2[CircleConfigs2["INVITE"] = CIRCLE_CONFIG_INVITE] = "INVITE";
  CircleConfigs2[CircleConfigs2["REQUEST"] = CIRCLE_CONFIG_REQUEST] = "REQUEST";
  CircleConfigs2[CircleConfigs2["FRIEND"] = CIRCLE_CONFIG_FRIEND] = "FRIEND";
  CircleConfigs2[CircleConfigs2["PROTECTED"] = CIRCLE_CONFIG_PROTECTED] = "PROTECTED";
  CircleConfigs2[CircleConfigs2["NO_OWNER"] = CIRCLE_CONFIG_NO_OWNER] = "NO_OWNER";
  CircleConfigs2[CircleConfigs2["HIDDEN"] = CIRCLE_CONFIG_HIDDEN] = "HIDDEN";
  CircleConfigs2[CircleConfigs2["BACKEND"] = CIRCLE_CONFIG_BACKEND] = "BACKEND";
  CircleConfigs2[CircleConfigs2["LOCAL"] = CIRCLE_CONFIG_LOCAL] = "LOCAL";
  CircleConfigs2[CircleConfigs2["ROOT"] = CIRCLE_CONFIG_ROOT] = "ROOT";
  CircleConfigs2[CircleConfigs2["CIRCLE_INVITE"] = CIRCLE_CONFIG_CIRCLE_INVITE] = "CIRCLE_INVITE";
  CircleConfigs2[CircleConfigs2["FEDERATED"] = CIRCLE_CONFIG_FEDERATED] = "FEDERATED";
  return CircleConfigs2;
})(CircleConfigs || {});
var MemberStatus = /* @__PURE__ */ ((MemberStatus2) => {
  MemberStatus2["INVITED"] = "Invited";
  MemberStatus2["MEMBER"] = "Member";
  MemberStatus2["REQUESTING"] = "Requesting";
  return MemberStatus2;
})(MemberStatus || {});
var CircleEdit = /* @__PURE__ */ ((CircleEdit2) => {
  CircleEdit2["Name"] = "name";
  CircleEdit2["Description"] = "description";
  CircleEdit2["Settings"] = "settings";
  CircleEdit2["Config"] = "config";
  return CircleEdit2;
})(CircleEdit || {});
async function getCircles() {
  const response = await cancelableClient.get(generateOcsUrl("apps/circles/circles"));
  return response.data.ocs.data;
}
async function getCircle(circleId) {
  const response = await cancelableClient.get(generateOcsUrl("apps/circles/circles/{circleId}", { circleId }));
  return response.data.ocs.data;
}
async function createCircle(name, personal, local) {
  const response = await cancelableClient.post(generateOcsUrl("apps/circles/circles"), {
    name,
    personal,
    local
  });
  return response.data.ocs.data;
}
async function deleteCircle(circleId) {
  const response = await cancelableClient.delete(generateOcsUrl("apps/circles/circles/{circleId}", { circleId }));
  return response.data.ocs.data;
}
async function editCircle(circleId, type, value) {
  const response = await cancelableClient.put(generateOcsUrl("apps/circles/circles/{circleId}/{type}", { circleId, type }), { value });
  return response.data.ocs.data;
}
async function joinCircle(circleId) {
  const response = await cancelableClient.put(generateOcsUrl("apps/circles/circles/{circleId}/join", { circleId }));
  return response.data.ocs.data;
}
async function leaveCircle(circleId) {
  const response = await cancelableClient.put(generateOcsUrl("apps/circles/circles/{circleId}/leave", { circleId }));
  return response.data.ocs.data;
}
async function getCircleMembers(circleId, search, role, limit) {
  const response = await cancelableClient.get(
    generateOcsUrl("apps/circles/circles/{circleId}/members", { circleId }),
    {
      params: {
        search,
        role,
        ...limit ? { limit } : {}
      }
    }
  );
  return response.data.ocs.data;
}
async function addMembers(circleId, members) {
  const response = await cancelableClient.post(generateOcsUrl("apps/circles/circles/{circleId}/members/multi", { circleId }), { members });
  return response.data.ocs.data;
}
async function deleteMember(circleId, memberId) {
  const response = await cancelableClient.delete(generateOcsUrl("apps/circles/circles/{circleId}/members/{memberId}", { circleId, memberId }));
  return Object.values(response.data.ocs.data);
}
async function changeMemberLevel(circleId, memberId, level) {
  if (!(level in MemberLevels)) {
    throw new Error("Invalid level.");
  }
  const response = await cancelableClient.put(generateOcsUrl("apps/circles/circles/{circleId}/members/{memberId}/level", { circleId, memberId }), {
    level
  });
  return Object.values(response.data.ocs.data);
}
async function acceptMember(circleId, memberId) {
  const response = await cancelableClient.put(generateOcsUrl("apps/circles/circles/{circleId}/members/{memberId}", { circleId, memberId }));
  return response.data.ocs.data;
}
async function editCircleSetting(circleId, setting) {
  const response = await cancelableClient.put(
    generateOcsUrl("apps/circles/circles/{circleId}/setting", { circleId }),
    setting
  );
  return response.data.ocs.data;
}
const logger = getLoggerBuilder().setApp(appName).detectUser().build();
const _hoisted_1$c = { class: "circle-settings" };
const _hoisted_2$a = { class: "circle-config__list" };
const _sfc_main$f = /* @__PURE__ */ defineComponent({
  __name: "CircleSettings",
  props: {
    circle: {}
  },
  emits: ["leave", "delete", "close-settings-popover"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit2 = __emit;
    const loading = ref(false);
    function isChecked(config) {
      return (props.circle.config & config) !== 0;
    }
    async function onChange(config, checked) {
      logger.debug(`Circle config ${config} is set to ${checked}`);
      if (checked && config === CircleConfigs.FEDERATED) {
        emit2("close-settings-popover");
        const confirmed = await confirmEnableFederationForCircle();
        if (!confirmed) {
          return;
        }
      }
      loading.value = config;
      const prevConfig = props.circle.config;
      const nextConfig = checked ? prevConfig | config : prevConfig & ~config;
      try {
        const circleData = await editCircle(props.circle.id, CircleEdit.Config, nextConfig);
        props.circle.config = circleData.config;
      } catch (error) {
        logger.error("Unable to edit circle config", { prevConfig, config: nextConfig, error });
        showError(translate("circles", "An error happened during the config change"));
      } finally {
        loading.value = false;
      }
    }
    async function confirmEnableFederationForCircle() {
      const confirmed = await showConfirmation({
        name: translate("circles", "Confirm enabling federation"),
        text: translate("circles", "Enabling this will prevent {circle} from being a member of other teams.\nAre you sure?", {
          circle: props.circle.displayName
        }),
        labelConfirm: translate("circles", "Enable federation"),
        labelReject: translate("circles", "Cancel"),
        severity: "warning"
      });
      if (!confirmed) {
        logger.debug("Enable federation cancelled");
        return false;
      }
      return true;
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", _hoisted_1$c, [
        createBaseVNode("ul", null, [
          (openBlock(true), createElementBlock(Fragment, null, renderList(unref(PUBLIC_CIRCLE_CONFIG), (configs, title) => {
            return openBlock(), createElementBlock("li", {
              key: title,
              class: "circle-config"
            }, [
              createVNode(ContentHeading, { class: "circle-config__title" }, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString(title), 1)
                ]),
                _: 2
              }, 1024),
              createBaseVNode("ul", _hoisted_2$a, [
                (openBlock(true), createElementBlock(Fragment, null, renderList(configs, (label, config) => {
                  return openBlock(), createBlock(unref(NcCheckboxRadioSwitch), {
                    key: "circle-config" + config,
                    "model-value": isChecked(Number(config)),
                    loading: loading.value === Number(config),
                    disabled: loading.value !== false,
                    "wrapper-element": "li",
                    "onUpdate:modelValue": ($event) => onChange(Number(config), $event)
                  }, {
                    default: withCtx(() => [
                      createTextVNode(toDisplayString(label), 1)
                    ]),
                    _: 2
                  }, 1032, ["model-value", "loading", "disabled", "onUpdate:modelValue"]);
                }), 128))
              ])
            ]);
          }), 128))
        ]),
        createVNode(CirclePasswordSettings, { circle: __props.circle }, null, 8, ["circle"]),
        __props.circle.canLeave ? (openBlock(), createBlock(unref(NcButton), {
          key: 0,
          variant: "warning",
          onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("leave"))
        }, {
          icon: withCtx(() => [
            createVNode(LogoutIcon, { size: 16 })
          ]),
          default: withCtx(() => [
            createTextVNode(" " + toDisplayString(unref(translate)("circles", "Leave team")), 1)
          ]),
          _: 1
        })) : createCommentVNode("", true),
        __props.circle.canDelete ? (openBlock(), createBlock(unref(NcButton), {
          key: 1,
          variant: "error",
          href: "#",
          onClick: _cache[1] || (_cache[1] = withModifiers(($event) => _ctx.$emit("delete"), ["prevent", "stop"]))
        }, {
          icon: withCtx(() => [
            createVNode(TrashCanOutlineIcon, { size: 20 })
          ]),
          default: withCtx(() => [
            createTextVNode(" " + toDisplayString(unref(translate)("circles", "Delete team")), 1)
          ]),
          _: 1
        })) : createCommentVNode("", true)
      ]);
    };
  }
});
const CircleSettings = /* @__PURE__ */ _export_sfc$1(_sfc_main$f, [["__scopeId", "data-v-53950fe5"]]);
const _sfc_main$e = {
  name: "CloseIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$b = ["aria-hidden", "aria-label"];
const _hoisted_2$9 = ["fill", "width", "height"];
const _hoisted_3$8 = { d: "M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" };
const _hoisted_4$8 = { key: 0 };
function _sfc_render$d(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon close-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$8, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$8, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$9))
  ], 16, _hoisted_1$b);
}
const CloseOutlineIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$e, [["render", _sfc_render$d]]);
const resourceCreationPopover = "_resource-creation-popover_18ay8_1";
const popoverContent = "_popover-content_18ay8_5";
const popoverActions = "_popover-actions_18ay8_10";
const popoverHelperText = "_popover-helper-text_18ay8_15";
const style0$2 = {
  resourceCreationPopover,
  popoverContent,
  popoverActions,
  popoverHelperText
};
const _sfc_main$d = {
  name: "TeamResourceButton",
  components: {
    NcButton,
    NcNoteCard,
    NcPopover,
    NcTextField: _sfc_main$W,
    CloseOutlineIcon,
    CheckOutlineIcon: CheckIcon
  },
  props: {
    resourceType: {
      type: Object,
      required: true
    },
    value: {
      type: String,
      default: ""
    },
    isOpen: {
      type: Boolean,
      default: false
    }
  },
  emits: ["update:value", "update:isOpen", "create"],
  computed: {
    inputValue() {
      return this.value;
    },
    isPopoverOpen: {
      get() {
        return this.isOpen;
      },
      set(value) {
        this.$emit("update:isOpen", value);
      }
    },
    canCreate() {
      if (this.resourceType.noInput) {
        return this.resourceType.enabled !== false;
      }
      const value = this.inputValue;
      const hasValue = typeof value === "string" && value.trim().length > 0;
      return hasValue && this.resourceType.enabled !== false;
    }
  },
  methods: {
    openPopover() {
      if (this.resourceType.noInput) {
        this.createResource();
      } else {
        this.isPopoverOpen = true;
      }
    },
    closePopover() {
      this.isPopoverOpen = false;
    },
    handlePopoverToggle(shown) {
      this.isPopoverOpen = shown;
    },
    updateInput(value) {
      const actualValue = typeof value === "string" ? value : value?.target?.value || value?.value || "";
      this.$emit("update:value", actualValue);
    },
    createResource() {
      if (this.canCreate) {
        if (this.resourceType.noInput) {
          this.$emit("create", {
            resourceType: this.resourceType,
            name: ""
          });
        } else {
          const value = this.inputValue;
          const name = typeof value === "string" ? value.trim() : "";
          if (name) {
            this.$emit("create", {
              resourceType: this.resourceType,
              name
            });
          }
        }
      }
    }
  }
};
const _hoisted_1$a = {
  key: 0,
  class: "popover-helper-text"
};
function _sfc_render$c(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcButton = resolveComponent("NcButton");
  const _component_NcTextField = resolveComponent("NcTextField");
  const _component_CloseOutlineIcon = resolveComponent("CloseOutlineIcon");
  const _component_CheckOutlineIcon = resolveComponent("CheckOutlineIcon");
  const _component_NcNoteCard = resolveComponent("NcNoteCard");
  const _component_NcPopover = resolveComponent("NcPopover");
  return openBlock(), createBlock(_component_NcPopover, {
    shown: $options.isPopoverOpen,
    "popup-role": "dialog",
    "onUpdate:shown": $options.handlePopoverToggle
  }, {
    trigger: withCtx(() => [
      createVNode(_component_NcButton, {
        variant: "secondary",
        "aria-describedby": `tooltip-${$props.resourceType.id}`,
        onClick: $options.openPopover
      }, {
        icon: withCtx(() => [
          renderSlot(_ctx.$slots, "icon", {}, void 0, true)
        ]),
        default: withCtx(() => [
          createTextVNode(" " + toDisplayString($props.resourceType.label), 1)
        ]),
        _: 3
      }, 8, ["aria-describedby", "onClick"])
    ]),
    default: withCtx(() => [
      !$props.resourceType.noInput ? (openBlock(), createElementBlock("div", {
        key: 0,
        class: normalizeClass(_ctx.$style.resourceCreationPopover)
      }, [
        createBaseVNode("div", {
          class: normalizeClass(_ctx.$style.popoverContent)
        }, [
          createVNode(_component_NcTextField, {
            "model-value": $options.inputValue,
            placeholder: $props.resourceType.placeholder,
            label: $props.resourceType.inputLabel,
            "onUpdate:value": $options.updateInput,
            onInput: $options.updateInput
          }, null, 8, ["model-value", "placeholder", "label", "onUpdate:value", "onInput"]),
          createBaseVNode("div", {
            class: normalizeClass(_ctx.$style.popoverActions)
          }, [
            createVNode(_component_NcButton, {
              variant: "secondary",
              "aria-label": _ctx.t("circles", "Close"),
              onClick: $options.closePopover
            }, {
              icon: withCtx(() => [
                createVNode(_component_CloseOutlineIcon, { size: 20 })
              ]),
              _: 1
            }, 8, ["aria-label", "onClick"]),
            createVNode(_component_NcButton, {
              variant: "primary",
              "aria-label": _ctx.t("circles", "Save"),
              disabled: !$options.canCreate,
              onClick: $options.createResource
            }, {
              icon: withCtx(() => [
                createVNode(_component_CheckOutlineIcon, { size: 20 })
              ]),
              _: 1
            }, 8, ["aria-label", "disabled", "onClick"])
          ], 2)
        ], 2),
        $props.resourceType.helperText ? (openBlock(), createElementBlock("div", _hoisted_1$a, [
          createVNode(_component_NcNoteCard, { type: "info" }, {
            default: withCtx(() => [
              createTextVNode(toDisplayString($props.resourceType.helperText), 1)
            ]),
            _: 1
          })
        ])) : createCommentVNode("", true)
      ], 2)) : createCommentVNode("", true)
    ]),
    _: 3
  }, 8, ["shown", "onUpdate:shown"]);
}
const cssModules$2 = {
  "$style": style0$2
};
const TeamResourceButton = /* @__PURE__ */ _export_sfc$1(_sfc_main$d, [["render", _sfc_render$c], ["__cssModules", cssModules$2], ["__scopeId", "data-v-f8c14194"]]);
const a = null, { min: u, max: f, abs: d, floor: p } = Math, m = (e, t2, r) => u(r, f(t2, e)), h = (e) => [...e].sort((e2, t2) => e2 - t2), _ = "function" == typeof queueMicrotask ? queueMicrotask : (e) => {
  Promise.resolve().then(e);
}, b = () => {
  let e;
  return [new Promise((t2) => {
    e = t2;
  }), e];
}, g = (e) => {
  let t2;
  return () => (e && (t2 = e(), e = void 0), t2);
}, v = (e, t2, r) => {
  const o = r ? "unshift" : "push";
  for (let r2 = 0; r2 < t2; r2++) e[o](-1);
  return e;
}, y = (e, t2) => {
  const r = e.t[t2];
  return -1 === r ? e.o : r;
}, S = (e, t2, r) => {
  const o = -1 === e.t[t2];
  return e.t[t2] = r, e.i = u(t2, e.i), o;
}, z = (e, t2) => {
  if (!e.l) return 0;
  if (e.i >= t2) return e.u[t2];
  e.i < 0 && (e.u[0] = 0, e.i = 0);
  let r = e.i, o = e.u[r];
  for (; r < t2; ) o += y(e, r), e.u[++r] = o;
  return e.i = t2, o;
}, x = (e, t2, r = 0, o = e.l - 1) => {
  let n = r;
  for (; r <= o; ) {
    const s = p((r + o) / 2);
    z(e, s) <= t2 ? (n = s, r = s + 1) : o = s - 1;
  }
  return m(n, 0, e.l - 1);
}, $ = (e, t2, r) => {
  const o = t2 - e.l;
  return e.i = r ? -1 : u(t2 - 1, e.i), e.l = t2, o > 0 ? (v(e.u, o), v(e.t, o, r), e.o * o) : (e.u.splice(o), (r ? e.t.splice(0, -o) : e.t.splice(o)).reduce((t3, r2) => t3 - (-1 === r2 ? e.o : r2), 0));
}, w = (e) => e.documentElement, I = (e) => e.ownerDocument, O = (e) => e.defaultView, k = /* @__PURE__ */ g(() => !!/iP(hone|od|ad)/.test(navigator.userAgent) || "MacIntel" === navigator.platform && navigator.maxTouchPoints > 0), j = /* @__PURE__ */ g(() => "scrollBehavior" in w(document).style), N = (e, t2 = 40, r = 0, o, n = false) => {
  let s = !!r, i = 1, l = 0, c = 0, p2 = 0, m2 = 0, _2 = 0, b2 = 0, g2 = 0, w2 = 0, I2 = a, O2 = [0, s ? f(r - 1, 0) : -1], j2 = 0, N2 = false;
  const B2 = ((e2, t3, r2) => ({
    o: t3,
    t: r2 ? v(r2.slice(0, u(e2, r2.length)), f(0, e2 - r2.length)) : v([], e2),
    l: e2,
    i: -1,
    u: v([], e2 + 1)
  }))(e, o ? o[1] : t2, o && o[0]), T2 = /* @__PURE__ */ new Set(), q2 = () => p2 - c, M2 = () => q2() + _2 + m2, E2 = (e2, t3) => ((e3, t4, r2, o2) => {
    if (o2 = u(o2, e3.l - 1), z(e3, o2) <= t4) {
      const n2 = x(e3, r2, o2);
      return [x(e3, t4, o2, n2), n2];
    }
    {
      const n2 = x(e3, t4, void 0, o2);
      return [n2, x(e3, r2, n2)];
    }
  })(B2, e2, t3, O2[0]), P2 = () => z(B2, B2.l), R2 = (e2, t3) => {
    const r2 = z(B2, e2) - _2;
    return t3 ? P2() - r2 - A2(e2) : r2;
  }, A2 = (e2) => y(B2, e2), C2 = (e2, t3 = -1) => B2.t[e2] === t3, H2 = (e2) => {
    e2 && (k() && 0 !== g2 || I2 && 1 === w2 ? _2 += e2 : m2 += e2);
  };
  return {
    p: () => {
      T2.clear();
    },
    m: () => i,
    h: () => ((e2) => [e2.t.slice(), e2.o])(B2),
    v: (e2 = 200) => {
      if (!N2 || s) return O2;
      let t3, r2;
      if (b2) [t3, r2] = O2;
      else {
        let o2 = f(0, M2()), s2 = o2 + l;
        n || (e2 = f(0, e2), 1 !== g2 && (o2 -= e2), 2 !== g2 && (s2 += e2)), [t3, r2] = O2 = E2(f(0, o2), f(0, s2)), I2 && (t3 = u(t3, I2[0]), r2 = f(r2, I2[1]));
      }
      return [f(t3, 0), u(r2, B2.l - 1)];
    },
    S: (e2) => x(B2, e2 - c),
    I: C2,
    O: R2,
    k: A2,
    j: () => B2.l,
    N: () => p2,
    B: () => 0 !== g2,
    T: () => l,
    q: () => c,
    M: P2,
    P: () => (b2 = m2, m2 = 0, [b2, 2 === w2]),
    R: (e2, t3) => {
      const r2 = [e2, t3];
      return T2.add(r2), () => {
        T2.delete(r2);
      };
    },
    A: (e2, t3) => {
      let r2, o2, u2 = 0;
      switch (e2) {
        case 1: {
          if (t3 === p2 && 0 === w2) break;
          const e3 = b2;
          b2 = 0;
          const r3 = t3 - p2, n2 = d(r3);
          e3 && n2 < d(e3) + 1 || 0 !== w2 || (g2 = r3 < 0 ? 2 : 1), s && (s = false), p2 = t3, u2 = 4;
          const i2 = q2();
          i2 >= -l && i2 <= P2() && (u2 += 1, o2 = n2 > l);
          break;
        }
        case 2:
          u2 = 8, 0 !== g2 && (r2 = true, u2 += 1), g2 = 0, w2 = 0, I2 = a;
          break;
        case 3: {
          const e3 = t3.filter(([e4, t4]) => !C2(e4, t4));
          if (!e3.length) break;
          H2(e3.reduce((e4, [t4, r3]) => {
            let o3;
            if (2 === w2) o3 = true;
            else if (I2 && 1 === w2) o3 = t4 < I2[0];
            else {
              const e5 = q2(), r4 = R2(t4), n2 = A2(t4);
              o3 = 1 !== g2 && 0 === w2 ? r4 + n2 <= e5 : r4 < e5 && r4 + n2 < e5 + l;
            }
            return o3 && (e4 += r3 - A2(t4)), e4;
          }, 0));
          for (const [t4, r3] of e3) {
            const e4 = A2(t4), o3 = S(B2, t4, r3);
            n && (j2 += o3 ? r3 : r3 - e4);
          }
          n && l && j2 > l && (H2(((e4, t4) => {
            let r3 = 0;
            const o3 = [];
            e4.t.forEach((e5, n3) => {
              -1 !== e5 && (o3.push(e5), n3 < t4 && r3++);
            }), e4.i = -1;
            const n2 = h(o3), s2 = n2.length, i2 = s2 / 2 | 0, l2 = s2 % 2 == 0 ? (n2[i2 - 1] + n2[i2]) / 2 : n2[i2], c2 = e4.o;
            return ((e4.o = l2) - c2) * f(t4 - r3, 0);
          })(B2, x(B2, M2()))), n = false), u2 = 3, o2 = true;
          break;
        }
        case 4:
          l !== t3 && (l || (N2 = o2 = true), l = t3, u2 = 3);
          break;
        case 5:
          t3[1] ? (H2($(B2, t3[0], true)), w2 = 2, u2 = 1) : ($(B2, t3[0]), u2 = 1);
          break;
        case 6:
          c = t3;
          break;
        case 7:
          w2 = 1;
          break;
        case 8:
          I2 = E2(t3, t3 + l), u2 = 1;
      }
      u2 && (i = 1 + (2147483647 & i), r2 && _2 && (m2 += _2, _2 = 0), T2.forEach(([e3, t4]) => {
        u2 & e3 && t4(o2);
      }));
    }
  };
}, B = setTimeout, T = (e, t2) => t2 ? -e : e, q = (e, t2, r, o, n, s) => {
  const i = Date.now;
  let l = 0, c = false, u2 = false, f2 = false, d2 = false;
  const p2 = (() => {
    let t3;
    const r2 = () => {
      t3 != a && clearTimeout(t3);
    }, o2 = () => {
      r2(), t3 = B(() => {
        t3 = a, (() => {
          if (c || u2) return c = false, void p2();
          f2 = false, e.A(2);
        })();
      }, 150);
    };
    return o2.C = r2, o2;
  })(), m2 = () => {
    l = i(), f2 && (d2 = true), e.A(1, o()), p2();
  }, h2 = (t3) => {
    if (c || !e.B() || t3.ctrlKey) return;
    const o2 = i() - l;
    150 > o2 && 50 < o2 && (r ? t3.deltaX : t3.deltaY) && (c = true);
  }, _2 = () => {
    u2 = true, f2 = d2 = false;
  }, b2 = () => {
    u2 = false, k() && (f2 = true);
  };
  return t2.addEventListener("scroll", m2), t2.addEventListener("wheel", h2, {
    passive: true
  }), t2.addEventListener("touchstart", _2, {
    passive: true
  }), t2.addEventListener("touchend", b2, {
    passive: true
  }), {
    H: () => {
      t2.removeEventListener("scroll", m2), t2.removeEventListener("wheel", h2), t2.removeEventListener("touchstart", _2), t2.removeEventListener("touchend", b2), p2.C();
    },
    V: () => {
      const [t3, r2] = e.P();
      t3 && (n(t3, r2, d2), d2 = false, r2 && e.T() > e.M() && e.A(1, o()));
    }
  };
}, M = (e, t2, r) => {
  let o;
  return [async (n, s) => {
    if (!await t2()) return;
    o && o();
    const i = () => {
      const [t3, r2] = b();
      return o = () => {
        r2(false);
      }, e.T() && B(o, 150), [t3, e.R(2, () => {
        r2(true);
      })];
    };
    if (s && j()) e.A(8, n()), _(async () => {
      for (; ; ) {
        let t3 = true;
        for (let [r3, o3] = e.v(); r3 <= o3; r3++) if (e.I(r3)) {
          t3 = false;
          break;
        }
        if (t3) break;
        const [r2, o2] = i();
        try {
          if (!await r2) return;
        } finally {
          o2();
        }
      }
      e.A(7), r(n(), s);
    });
    else for (; ; ) {
      const [t3, o2] = i();
      try {
        if (e.A(7), r(n()), !await t3) return;
      } finally {
        o2();
      }
    }
  }, () => {
    o && o();
  }];
}, E = (e) => {
  let t2;
  return {
    F(r) {
      (t2 || (t2 = new (O(I(r))).ResizeObserver(e))).observe(r);
    },
    J(e2) {
      t2.unobserve(e2);
    },
    H() {
      t2 && t2.disconnect();
    }
  };
}, P = /* @__PURE__ */ defineComponent({
  props: {
    L: {
      type: Object,
      required: true
    },
    W: {
      type: Object,
      required: true
    },
    X: {
      type: Object,
      required: true
    },
    Y: {
      type: Function,
      required: true
    },
    D: {
      type: Number,
      required: true
    },
    U: {
      type: Boolean
    },
    G: {
      type: Boolean
    },
    K: {
      type: Boolean
    },
    Z: {
      type: String,
      required: true
    },
    ee: Object
  },
  setup(e) {
    const l = ref(), c = computed(() => e.L.value && e.W.O(e.D, e.K)), a2 = computed(() => e.L.value && e.W.I(e.D));
    return watch(() => l.value && e.D, (t2, r, o) => {
      o(e.Y(l.value, e.D));
    }, {
      flush: "post"
    }), () => {
      const { X: t2, U: r, G: o, Z: u2 } = e, f2 = a2.value, { style: d2, ...p2 } = e.ee ?? {}, m2 = {
        contain: "layout style",
        position: f2 && o ? void 0 : "absolute",
        [r ? "height" : "width"]: "100%",
        [r ? "top" : "left"]: "0px",
        [r ? "left" : "top"]: c.value + "px",
        visibility: !f2 || o ? void 0 : "hidden",
        ...d2
      };
      return r && (m2.display = "inline-flex"), createVNode(u2, mergeProps({
        ref: l,
        style: m2
      }, p2), "function" == typeof (h2 = t2) || "[object Object]" === Object.prototype.toString.call(h2) && !isVNode(h2) ? t2 : {
        default: () => [t2],
        _: 2
      }, 16, ["style"]);
      var h2;
    };
  }
}), R = (e, t2) => {
  if (1 === e.length) {
    const t3 = e[0].key;
    if (null != t3) return t3;
  }
  return "_" + t2;
}, A = (e, t2) => e[0] === t2[0] && e[1] === t2[1], C = /* @__PURE__ */ defineComponent({
  props: {
    data: {
      type: Array,
      required: true
    },
    bufferSize: Number,
    itemSize: Number,
    shift: Boolean,
    horizontal: Boolean,
    startMargin: {
      type: Number,
      default: 0
    },
    ssrCount: Number,
    scrollRef: Object,
    as: {
      type: String,
      default: "div"
    },
    item: {
      type: String,
      default: "div"
    },
    itemProps: Function,
    keepMounted: Array,
    cache: Object
  },
  emits: ["scroll", "scrollEnd"],
  setup(e, { emit: s, expose: u2, slots: d2 }) {
    let p2 = !!e.ssrCount;
    const _2 = e.horizontal, g2 = ref(), v2 = N(e.data.length, e.itemSize, e.ssrCount, e.cache, !e.itemSize), y2 = ((e2, t2) => {
      let r;
      const o = t2 ? "width" : "height", n = /* @__PURE__ */ new WeakMap(), s2 = E((t3) => {
        const s3 = [];
        for (const { target: i, contentRect: l } of t3) if (i.offsetParent) if (i === r) e2.A(4, l[o]);
        else {
          const e3 = n.get(i);
          e3 != a && s3.push([e3, l[o]]);
        }
        s3.length && e2.A(3, s3);
      });
      return {
        te(e3) {
          s2.F(r = e3);
        },
        re: (e3, t3) => (n.set(e3, t3), s2.F(e3), () => {
          n.delete(e3), s2.J(e3);
        }),
        p: s2.H
      };
    })(v2, _2), S2 = ((e2, t2) => {
      let r, o, n = b(), s2 = false;
      const i = t2 ? "scrollLeft" : "scrollTop", l = t2 ? "overflowX" : "overflowY", [c, a2] = M(e2, () => n[0], (e3, o2) => {
        e3 = T(e3, s2), o2 ? r.scrollTo({
          [t2 ? "left" : "top"]: e3,
          behavior: "smooth"
        }) : r[i] = e3;
      });
      return {
        oe(c2, u3) {
          r = u3, t2 && (s2 = "rtl" === getComputedStyle(u3).direction), o = q(e2, u3, t2, () => T(u3[i], s2), (t3, r2, o2) => {
            if (o2) {
              const e3 = u3.style, t4 = e3[l];
              e3[l] = "hidden", B(() => {
                e3[l] = t4;
              });
            }
            u3[i] = T(e2.N() + t3, s2), r2 && a2();
          }), n[1](true);
        },
        p() {
          o && o.H(), n[1](false), n = b();
        },
        ne: () => s2,
        se(e3) {
          c(() => e3);
        },
        ie(t3) {
          t3 += e2.N(), c(() => t3);
        },
        le(t3, { align: r2, smooth: o2, offset: n2 = 0 } = {}) {
          if (t3 = m(t3, 0, e2.j() - 1), "nearest" === r2) {
            const o3 = e2.O(t3), n3 = e2.N();
            if (o3 < n3) r2 = "start";
            else {
              if (!(o3 + e2.k(t3) > n3 + e2.T())) return;
              r2 = "end";
            }
          }
          c(() => n2 + e2.q() + e2.O(t3) + ("end" === r2 ? e2.k(t3) - e2.T() : "center" === r2 ? (e2.k(t3) - e2.T()) / 2 : 0), o2);
        },
        ce: () => {
          o && o.V();
        }
      };
    })(v2, _2), z2 = ref(v2.m());
    v2.R(1, () => {
      z2.value = v2.m();
    }), v2.R(4, () => {
      s("scroll", v2.N());
    }), v2.R(8, () => {
      s("scrollEnd");
    });
    const x2 = computed((t2) => {
      z2.value;
      const r = v2.v(e.bufferSize);
      return t2 && A(t2, r) ? t2 : r;
    }), $2 = computed(() => z2.value && v2.B()), w2 = computed(() => z2.value && v2.M());
    return onMounted(() => {
      p2 = false;
      const t2 = g2.value, r = requestAnimationFrame(() => {
        const r2 = (e2) => {
          y2.te(e2), S2.oe(t2, e2);
        };
        e.scrollRef ? r2(e.scrollRef) : r2(t2.parentElement);
      });
      onUnmounted(() => {
        cancelAnimationFrame(r);
      });
    }), onUnmounted(() => {
      v2.p(), y2.p(), S2.p();
    }), watch(() => e.data.length, (t2) => {
      v2.A(5, [t2, e.shift]);
    }), watch(() => e.startMargin, (e2) => {
      v2.A(6, e2);
    }, {
      immediate: true
    }), watch([z2], () => {
      S2.ce();
    }, {
      flush: "post"
    }), u2({
      get cache() {
        return v2.h();
      },
      get scrollOffset() {
        return v2.N();
      },
      get scrollSize() {
        return ((e2) => f(e2.M(), e2.T()))(v2);
      },
      get viewportSize() {
        return v2.T();
      },
      findItemIndex: v2.S,
      getItemOffset: v2.O,
      getItemSize: v2.k,
      scrollToIndex: S2.le,
      scrollTo: S2.se,
      scrollBy: S2.ie
    }), () => {
      const t2 = e.as, r = e.item, o = w2.value, s2 = S2.ne(), l = [], c = (t3) => {
        const o2 = d2.default({
          item: e.data[t3],
          index: t3
        });
        return createVNode(P, {
          key: R(o2, t3),
          L: z2,
          W: v2,
          Y: y2.re,
          D: t3,
          X: o2,
          U: _2,
          K: s2,
          G: p2,
          Z: r,
          ee: e.itemProps?.({
            item: e.data[t3],
            index: t3
          })
        }, null, 8, ["L", "W", "Y", "D", "X", "U", "K", "G", "Z", "ee"]);
      };
      if (e.keepMounted) {
        const t3 = new Set(e.keepMounted);
        for (let [e2, r2] = x2.value; e2 <= r2; e2++) t3.add(e2);
        h([...t3]).forEach((e2) => {
          l.push(c(e2));
        });
      } else for (let [e2, t3] = x2.value; e2 <= t3; e2++) l.push(c(e2));
      return createVNode(t2, {
        ref: g2,
        style: {
          contain: "size style",
          overflowAnchor: "none",
          flex: "none",
          position: "relative",
          width: _2 ? o + "px" : "100%",
          height: _2 ? "100%" : o + "px",
          pointerEvents: $2.value ? "none" : void 0
        }
      }, "function" == typeof (a2 = l) || "[object Object]" === Object.prototype.toString.call(a2) && !isVNode(a2) ? l : {
        default: () => [l],
        _: 2
      }, 8, ["style"]);
      var a2;
    };
  }
}), H = /* @__PURE__ */ defineComponent({
  props: {
    data: {
      type: Array,
      required: true
    },
    bufferSize: Number,
    itemSize: Number,
    shift: Boolean,
    horizontal: Boolean,
    ssrCount: Number,
    itemProps: Function,
    keepMounted: Array,
    cache: Object
  },
  emits: ["scroll", "scrollEnd"],
  setup(e, { emit: r, expose: o, slots: s }) {
    const l = e.horizontal, c = (e2) => {
      r("scroll", e2);
    }, a2 = () => {
      r("scrollEnd");
    }, u2 = ref();
    return o({
      get cache() {
        return u2.value.cache;
      },
      get scrollOffset() {
        return u2.value.scrollOffset;
      },
      get scrollSize() {
        return u2.value.scrollSize;
      },
      get viewportSize() {
        return u2.value.viewportSize;
      },
      findItemIndex: (...e2) => u2.value.findItemIndex(...e2),
      getItemOffset: (...e2) => u2.value.getItemOffset(...e2),
      getItemSize: (...e2) => u2.value.getItemSize(...e2),
      scrollToIndex: (...e2) => u2.value.scrollToIndex(...e2),
      scrollTo: (...e2) => u2.value.scrollTo(...e2),
      scrollBy: (...e2) => u2.value.scrollBy(...e2)
    }), () => {
      return createVNode("div", {
        style: {
          display: l ? "inline-block" : "block",
          [l ? "overflowX" : "overflowY"]: "auto",
          contain: "strict",
          width: "100%",
          height: "100%"
        }
      }, [createVNode(C, {
        ref: u2,
        data: e.data,
        bufferSize: e.bufferSize,
        itemSize: e.itemSize,
        itemProps: e.itemProps,
        shift: e.shift,
        ssrCount: e.ssrCount,
        horizontal: l,
        keepMounted: e.keepMounted,
        cache: e.cache,
        onScroll: c,
        onScrollEnd: a2
      }, (t2 = s, "function" == typeof t2 || "[object Object]" === Object.prototype.toString.call(t2) && !isVNode(t2) ? s : {
        default: () => [s],
        _: 2
      }), 8, ["data", "bufferSize", "itemSize", "itemProps", "shift", "ssrCount", "horizontal", "keepMounted", "cache", "onScroll", "onScrollEnd"])], 4);
      var t2;
    };
  }
});
const _sfc_main$c = {
  name: "AccountMultipleOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$9 = ["aria-hidden", "aria-label"];
const _hoisted_2$8 = ["fill", "width", "height"];
const _hoisted_3$7 = { d: "M13.07 10.41A5 5 0 0 0 13.07 4.59A3.39 3.39 0 0 1 15 4A3.5 3.5 0 0 1 15 11A3.39 3.39 0 0 1 13.07 10.41M5.5 7.5A3.5 3.5 0 1 1 9 11A3.5 3.5 0 0 1 5.5 7.5M7.5 7.5A1.5 1.5 0 1 0 9 6A1.5 1.5 0 0 0 7.5 7.5M16 17V19H2V17S2 13 9 13 16 17 16 17M14 17C13.86 16.22 12.67 15 9 15S4.07 16.31 4 17M15.95 13A5.32 5.32 0 0 1 18 17V19H22V17S22 13.37 15.94 13Z" };
const _hoisted_4$7 = { key: 0 };
function _sfc_render$b(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon account-multiple-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$7, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$7, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$8))
  ], 16, _hoisted_1$9);
}
const IconContact = /* @__PURE__ */ _export_sfc$1(_sfc_main$c, [["render", _sfc_render$b]]);
const _sfc_main$b = {
  name: "MagnifyIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$8 = ["aria-hidden", "aria-label"];
const _hoisted_2$7 = ["fill", "width", "height"];
const _hoisted_3$6 = { d: "M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" };
const _hoisted_4$6 = { key: 0 };
function _sfc_render$a(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon magnify-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$6, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$6, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$7))
  ], 16, _hoisted_1$8);
}
const IconSearch = /* @__PURE__ */ _export_sfc$1(_sfc_main$b, [["render", _sfc_render$a]]);
const _sfc_main$a = {
  name: "EntityBubble",
  components: {
    UserBubble: NcUserBubble
  },
  props: {
    /**
     * Unique id of the entity
     */
    id: {
      type: String,
      required: true
    },
    /**
     * Label of the entity
     */
    label: {
      type: String,
      required: true
    },
    /**
     * Type of the entity. e.g user, circle, group...
     */
    type: {
      type: String,
      required: true
    },
    /**
     * Share type of the entity. e.g user, group, email, remote...
     */
    shareType: {
      type: Number,
      default: void 0
    },
    /**
     * User identifier (for user-type entities)
     */
    user: {
      type: String,
      default: void 0
    }
  },
  setup() {
    return {
      ShareType
    };
  },
  methods: {
    onDelete() {
      this.$emit("delete", {
        id: this.id,
        type: this.type
      });
    }
  }
};
const _hoisted_1$7 = ["title"];
function _sfc_render$9(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_UserBubble = resolveComponent("UserBubble");
  return openBlock(), createBlock(_component_UserBubble, {
    class: "entity-picker__bubble",
    margin: 0,
    size: 22,
    "display-name": $props.label,
    user: $props.shareType === $setup.ShareType.User ? $props.user : void 0
  }, {
    name: withCtx(() => [
      createBaseVNode("a", {
        href: "#",
        title: _ctx.t("circles", "Remove {type}", { type: $props.type }),
        class: "entity-picker__bubble-delete icon-close",
        onClick: _cache[0] || (_cache[0] = (...args) => $options.onDelete && $options.onDelete(...args))
      }, null, 8, _hoisted_1$7)
    ]),
    _: 1
  }, 8, ["display-name", "user"]);
}
const EntityBubble = /* @__PURE__ */ _export_sfc$1(_sfc_main$a, [["render", _sfc_render$9], ["__scopeId", "data-v-f88150e6"]]);
const _sfc_main$9 = {
  name: "EntitySearchResult",
  components: {
    UserBubble: NcUserBubble
  },
  props: {
    source: {
      type: Object,
      default() {
        return {};
      }
    },
    onClick: {
      type: Function,
      default() {
      }
    },
    selection: {
      type: Object,
      default: () => ({})
    }
  },
  setup() {
    return {
      ShareType
    };
  },
  computed: {
    isSelected() {
      return this.source.id in this.selection;
    }
  }
};
function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_UserBubble = resolveComponent("UserBubble");
  return $props.source.heading ? (openBlock(), createElementBlock("h4", {
    key: $props.source.id,
    class: "entity-picker__option-caption"
  }, toDisplayString(_ctx.t("circles", "Add {type}", { type: $props.source.label.toLowerCase() })), 1)) : (openBlock(), createBlock(_component_UserBubble, {
    key: 1,
    class: normalizeClass(["entity-picker__bubble", { "entity-picker__bubble--selected": $options.isSelected }]),
    "display-name": $props.source.label,
    user: $props.source.shareType === $setup.ShareType.User ? $props.source.user : void 0,
    margin: 6,
    size: 44,
    url: "#",
    onClick: _cache[0] || (_cache[0] = withModifiers(($event) => $props.onClick($props.source), ["stop", "prevent"]))
  }, {
    title: withCtx(() => [..._cache[1] || (_cache[1] = [
      createBaseVNode("span", { class: "entity-picker__bubble-checkmark icon-checkmark" }, null, -1)
    ])]),
    _: 1
  }, 8, ["class", "display-name", "user"]));
}
const EntitySearchResult = /* @__PURE__ */ _export_sfc$1(_sfc_main$9, [["render", _sfc_render$8], ["__scopeId", "data-v-e3533d88"]]);
const _sfc_main$8 = {
  name: "EntityPicker",
  components: {
    NcButton,
    EmptyContent: NcEmptyContent,
    EntityBubble,
    IconAccountPlusOutline: AccountPlusIcon,
    IconSearch,
    IconLoading: NcLoadingIcon,
    Modal: NcModal,
    VList: H,
    EntitySearchResult
  },
  props: {
    loading: {
      type: Boolean,
      default: false
    },
    /**
     * The types of data within dataSet
     * Array of objects. id must match dataSet entity type
     */
    dataTypes: {
      type: Array,
      required: true,
      validator: (types) => {
        const invalidTypes = types.filter((type) => !type.id && !type.label);
        if (invalidTypes.length > 0) {
          console.error("The following types MUST have a proper id and label key", invalidTypes);
          return false;
        }
        return true;
      }
    },
    /**
     * The data to be used
     */
    dataSet: {
      type: Array,
      required: true,
      validator: (data) => {
        data.forEach((source) => {
          if (!source.id || !source.label) {
            console.error("The following source MUST have a proper id and label key", source);
          }
        });
        return true;
      }
    },
    /**
     * The sorting key for the dataSet
     */
    sort: {
      type: String,
      default: "label"
    },
    /**
     * Confirm button text
     */
    confirmLabel: {
      type: String,
      default: t("circles", "Add to group")
    },
    /**
     * Title text
     */
    titleLabel: {
      type: String,
      default: t("circles", "Add members to group")
    },
    /**
     * The input will also filter the dataSet based on the label.
     * If you are using the search event to inject a different dataSet, you can disable this
     */
    internalSearch: {
      type: Boolean,
      default: true
    },
    /**
     * Override the local management of selection
     * You MUST use a sync modifier or the selection will be locked
     */
    selection: {
      type: Object,
      default: null
    },
    emptyDataSetDescription: {
      type: String,
      default: ""
    }
  },
  data() {
    return {
      canInviteGuests: !!window?.OCA?.Guests?.openGuestDialog,
      searchQuery: "",
      localSelection: {}
    };
  },
  computed: {
    /**
     * If the selection is set externally, let's use it
     */
    selectionSet: {
      get() {
        if (this.selection !== null) {
          return this.selection;
        }
        return this.localSelection;
      },
      set(selection) {
        if (this.selection !== null) {
          this.$emit("update:selection", selection);
        }
        this.localSelection = selection;
      }
    },
    /**
     * Are we handling a single entity type ?
     *
     * @return {boolean}
     */
    isSingleType() {
      return !(this.dataTypes.length > 1);
    },
    /**
     * Is the current selection empty
     *
     * @return {boolean}
     */
    isEmptySelection() {
      return Object.keys(this.selectionSet).length === 0;
    },
    /**
     * Formatted search input placeholder based on
     * available types
     *
     * @return {string}
     */
    searchPlaceholderTypes() {
      const types = this.dataTypes.map((type) => type.label).join(", ");
      return `${types}…`;
    },
    /**
     * Available data based on current search if query
     * is valid, returns default full data et otherwise
     *
     * @return {object[]}
     */
    searchSet() {
      if (this.internalSearch && this.searchQuery && this.searchQuery.trim !== "") {
        return this.dataSet.filter((entity) => {
          return entity.label.toLowerCase().indexOf(this.searchQuery.toLowerCase()) > -1;
        });
      }
      return this.dataSet;
    },
    /**
     * Returns available entities grouped by type(s) if any
     *
     * @return {object[]}
     */
    availableEntities() {
      if (this.isSingleType) {
        return this.searchSet;
      }
      return this.dataTypes.map((type) => {
        const dataSet = this.searchSet.filter((entity) => entity.type === type.id);
        const dataList = [
          {
            id: type.id,
            label: type.label,
            heading: true
          },
          ...dataSet
        ];
        if (dataSet.length === 0) {
          return [];
        }
        return dataList;
      }).flat();
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.$refs.input.focus();
      this.$refs.input.select();
    });
    if (this.canInviteGuests) {
      subscribe("guests:user:created", this.addGuest);
    }
  },
  methods: {
    onCancel() {
      this.$emit("close");
    },
    onSubmit() {
      this.$emit("submit", Object.values(this.selectionSet));
    },
    onSearch: debounce(function() {
      this.$emit("search", this.searchQuery);
    }, 200),
    /**
     * Remove entity from selection
     *
     * @param {object} entity the entity to remove
     */
    onDelete(entity) {
      delete this.selectionSet[entity.id];
      console.debug("Removing entity from selection", entity);
    },
    /**
     * Add/remove entity from selection
     *
     * @param {object} entity the entity to add
     */
    onClick(entity) {
      if (entity.id in this.selectionSet) {
        delete this.selectionSet[entity.id];
        console.debug("Removed entity to selection", entity);
        return;
      }
      this.selectionSet[entity.id] = entity;
      console.debug("Added entity to selection", entity);
    },
    /**
     * Toggle entity from selection
     *
     * @param {object} entity the entity to add/remove
     */
    onToggle(entity) {
      if (entity.id in this.selectionSet) {
        this.onDelete(entity);
      } else {
        this.onPick(entity);
      }
    },
    onGuestButtonClick() {
      if (this.canInviteGuests) {
        window?.OCA?.Guests?.openGuestDialog("contacts");
      }
    }
  }
};
const _hoisted_1$6 = { class: "entity-picker" };
const _hoisted_2$6 = { class: "entity-picker__heading" };
const _hoisted_3$5 = { class: "entity-picker__title" };
const _hoisted_4$5 = { class: "entity-picker__search-container" };
const _hoisted_5$1 = { class: "entity-picker__search" };
const _hoisted_6$1 = ["placeholder"];
const _hoisted_7$1 = { class: "entity-picker__navigation" };
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_IconAccountPlusOutline = resolveComponent("IconAccountPlusOutline");
  const _component_NcButton = resolveComponent("NcButton");
  const _component_IconLoading = resolveComponent("IconLoading");
  const _component_EmptyContent = resolveComponent("EmptyContent");
  const _component_EntityBubble = resolveComponent("EntityBubble");
  const _component_IconSearch = resolveComponent("IconSearch");
  const _component_EntitySearchResult = resolveComponent("EntitySearchResult");
  const _component_VList = resolveComponent("VList");
  const _component_Modal = resolveComponent("Modal");
  return openBlock(), createBlock(_component_Modal, {
    size: "normal",
    onClose: $options.onCancel
  }, {
    default: withCtx(() => [
      createBaseVNode("div", _hoisted_1$6, [
        createBaseVNode("div", _hoisted_2$6, [
          createBaseVNode("h3", _hoisted_3$5, toDisplayString($props.titleLabel), 1)
        ]),
        createBaseVNode("div", _hoisted_4$5, [
          createBaseVNode("div", _hoisted_5$1, [
            _cache[2] || (_cache[2] = createBaseVNode("div", { class: "entity-picker__search-icon icon-search" }, null, -1)),
            withDirectives(createBaseVNode("input", {
              ref: "input",
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.searchQuery = $event),
              placeholder: _ctx.t("circles", "Search {types}", { types: $options.searchPlaceholderTypes }),
              class: "entity-picker__search-input",
              type: "search",
              onInput: _cache[1] || (_cache[1] = (...args) => $options.onSearch && $options.onSearch(...args))
            }, null, 40, _hoisted_6$1), [
              [vModelText, $data.searchQuery]
            ])
          ]),
          $data.canInviteGuests ? (openBlock(), createBlock(_component_NcButton, {
            key: 0,
            type: "button",
            variant: "tertiary-no-background",
            title: _ctx.t("circles", "Add guest"),
            "aria-label": _ctx.t("circles", "Add guest"),
            onClick: $options.onGuestButtonClick
          }, {
            default: withCtx(() => [
              createVNode(_component_IconAccountPlusOutline, { size: 20 })
            ]),
            _: 1
          }, 8, ["title", "aria-label", "onClick"])) : createCommentVNode("", true)
        ]),
        $props.loading ? (openBlock(), createBlock(_component_EmptyContent, {
          key: 0,
          name: _ctx.t("circles", "Loading …")
        }, {
          icon: withCtx(() => [
            createVNode(_component_IconLoading, { size: 20 })
          ]),
          _: 1
        }, 8, ["name"])) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
          Object.keys($options.selectionSet).length > 0 ? (openBlock(), createBlock(TransitionGroup, {
            key: 0,
            name: "zoom",
            tag: "ul",
            class: "entity-picker__selection"
          }, {
            default: withCtx(() => [
              (openBlock(true), createElementBlock(Fragment, null, renderList($options.selectionSet, (entity) => {
                return openBlock(), createBlock(_component_EntityBubble, mergeProps({
                  key: entity.key || `entity-${entity.type}-${entity.id}`
                }, { ref_for: true }, entity, {
                  onDelete: ($event) => $options.onDelete(entity)
                }), null, 16, ["onDelete"]);
              }), 128))
            ]),
            _: 1
          })) : createCommentVNode("", true),
          $props.dataSet.length === 0 ? (openBlock(), createBlock(_component_EmptyContent, {
            key: 1,
            name: _ctx.t("circles", "Search for people to add"),
            description: $props.emptyDataSetDescription
          }, {
            icon: withCtx(() => [
              createVNode(_component_IconSearch, { size: 20 })
            ]),
            _: 1
          }, 8, ["name", "description"])) : $options.searchSet.length > 0 && $options.availableEntities.length > 0 ? (openBlock(), createBlock(_component_VList, {
            key: 2,
            class: "entity-picker__options",
            data: $options.availableEntities
          }, {
            default: withCtx(({ item }) => [
              (openBlock(), createBlock(_component_EntitySearchResult, {
                key: item.id,
                source: item,
                selection: $options.selectionSet,
                "on-click": $options.onClick
              }, null, 8, ["source", "selection", "on-click"]))
            ]),
            _: 1
          }, 8, ["data"])) : $data.searchQuery ? (openBlock(), createBlock(_component_EmptyContent, {
            key: 3,
            name: _ctx.t("circles", "No results")
          }, {
            icon: withCtx(() => [
              createVNode(_component_IconSearch, { size: 20 })
            ]),
            _: 1
          }, 8, ["name"])) : createCommentVNode("", true),
          createBaseVNode("div", _hoisted_7$1, [
            createVNode(_component_NcButton, {
              disabled: $props.loading,
              class: "navigation__button-left",
              onClick: $options.onCancel
            }, {
              default: withCtx(() => [
                createTextVNode(toDisplayString(_ctx.t("circles", "Cancel")), 1)
              ]),
              _: 1
            }, 8, ["disabled", "onClick"]),
            createVNode(_component_NcButton, {
              disabled: $options.isEmptySelection || $props.loading,
              class: "navigation__button-right primary",
              onClick: $options.onSubmit
            }, {
              default: withCtx(() => [
                createTextVNode(toDisplayString($props.confirmLabel), 1)
              ]),
              _: 1
            }, 8, ["disabled", "onClick"])
          ])
        ], 64))
      ])
    ]),
    _: 1
  }, 8, ["onClose"]);
}
const EntityPicker = /* @__PURE__ */ _export_sfc$1(_sfc_main$8, [["render", _sfc_render$7], ["__scopeId", "data-v-5b658674"]]);
const _sfc_main$7 = {
  name: "CheckOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$5 = ["aria-hidden", "aria-label"];
const _hoisted_2$5 = ["fill", "width", "height"];
const _hoisted_3$4 = { d: "M19.78,2.2L24,6.42L8.44,22L0,13.55L4.22,9.33L8.44,13.55L19.78,2.2M19.78,5L8.44,16.36L4.22,12.19L2.81,13.55L8.44,19.17L21.19,6.42L19.78,5Z" };
const _hoisted_4$4 = { key: 0 };
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon check-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$4, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$4, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$5))
  ], 16, _hoisted_1$5);
}
const IconCheckOutline = /* @__PURE__ */ _export_sfc$1(_sfc_main$7, [["render", _sfc_render$6]]);
const _sfc_main$6 = {
  name: "CloseOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$4 = ["aria-hidden", "aria-label"];
const _hoisted_2$4 = ["fill", "width", "height"];
const _hoisted_3$3 = { d: "M3,16.74L7.76,12L3,7.26L7.26,3L12,7.76L16.74,3L21,7.26L16.24,12L21,16.74L16.74,21L12,16.24L7.26,21L3,16.74M12,13.41L16.74,18.16L18.16,16.74L13.41,12L18.16,7.26L16.74,5.84L12,10.59L7.26,5.84L5.84,7.26L10.59,12L5.84,16.74L7.26,18.16L12,13.41Z" };
const _hoisted_4$3 = { key: 0 };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon close-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$3, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$3, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$4))
  ], 16, _hoisted_1$4);
}
const IconCloseOutline = /* @__PURE__ */ _export_sfc$1(_sfc_main$6, [["render", _sfc_render$5]]);
const _sfc_main$5 = {
  name: "ExitToAppIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$3 = ["aria-hidden", "aria-label"];
const _hoisted_2$3 = ["fill", "width", "height"];
const _hoisted_3$2 = { d: "M19,3H5C3.89,3 3,3.89 3,5V9H5V5H19V19H5V15H3V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3M10.08,15.58L11.5,17L16.5,12L11.5,7L10.08,8.41L12.67,11H3V13H12.67L10.08,15.58Z" };
const _hoisted_4$2 = { key: 0 };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon exit-to-app-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$2, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$2, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$3))
  ], 16, _hoisted_1$3);
}
const IconExitToApp = /* @__PURE__ */ _export_sfc$1(_sfc_main$5, [["render", _sfc_render$4]]);
const _sfc_main$4 = {
  name: "ShieldCheckOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
};
const _hoisted_1$2 = ["aria-hidden", "aria-label"];
const _hoisted_2$2 = ["fill", "width", "height"];
const _hoisted_3$1 = { d: "M21,11C21,16.55 17.16,21.74 12,23C6.84,21.74 3,16.55 3,11V5L12,1L21,5V11M12,21C15.75,20 19,15.54 19,11.22V6.3L12,3.18L5,6.3V11.22C5,15.54 8.25,20 12,21M10,17L6,13L7.41,11.59L10,14.17L16.59,7.58L18,9" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon shield-check-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$1, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$1, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$2))
  ], 16, _hoisted_1$2);
}
const IconShieldCheckOutline = /* @__PURE__ */ _export_sfc$1(_sfc_main$4, [["render", _sfc_render$3]]);
const RouterMixin = {
  computed: {
    // router variables
    selectedContact() {
      return this.$route.params.selectedContact;
    },
    selectedGroup() {
      return this.$route.params.selectedGroup;
    },
    selectedCircle() {
      return this.$route.params.selectedCircle ?? this.$route.params.teamId;
    },
    selectedUserGroup() {
      return this.$route.params.selectedUserGroup;
    },
    selectedChart() {
      return this.$route.params.selectedChart;
    }
  }
};
class Member {
  _data = {};
  _circle;
  /**
   * Creates an instance of Member
   *
   * @param data
   * @param circle
   */
  constructor(data, circle) {
    if (typeof data !== "object") {
      throw new Error("Invalid member");
    }
    if (data.id && typeof data.id !== "string") {
      logger.error("This member do not have a proper uid", data);
      throw new Error("This member do not have a proper uid");
    }
    this._circle = circle;
    this._data = data;
  }
  /**
   * Get the circle of this member
   */
  get circle() {
    return this._circle;
  }
  /**
   * Set the circle of this member
   */
  set circle(circle) {
    if (circle.constructor.name !== Circle.name) {
      throw new Error("circle must be a Circle type");
    }
    this._circle = circle;
  }
  /**
   * Member id
   */
  get id() {
    return this._data.id;
  }
  /**
   * Single uid
   */
  get singleId() {
    return this._data.singleId;
  }
  /**
   * Formatted display name
   */
  get displayName() {
    return this._data.displayName;
  }
  /**
   * Member userId
   */
  get userId() {
    return this._data.userId;
  }
  /**
   * Member type
   */
  get userType() {
    return this._data.userType !== MemberTypes.CIRCLE ? this._data.userType : this.basedOn.source;
  }
  /**
   * Member based on source
   */
  get basedOn() {
    return this._data.basedOn;
  }
  /**
   * Member level
   *
   */
  get level() {
    return this._data.level;
  }
  /**
   * Set member level
   */
  set level(level) {
    if (!(level in MemberLevels)) {
      throw new Error("Invalid level");
    }
    this._data.level = level;
  }
  /**
   * Member request status
   *
   */
  get status() {
    return this._data.status;
  }
  /**
   * Is the current member a user?
   */
  get isUser() {
    return this._data.userType === MemberLevels.MEMBER;
  }
  /**
   * Is the current member without a circle?
   */
  get isOrphan() {
    return this._circle?.constructor?.name !== Circle.name;
  }
  /**
   * Delete this member and any reference from its circle
   */
  delete() {
    if (this.isOrphan) {
      throw new Error("Cannot delete this member as it doesn't belong to any circle");
    }
    this.circle.deleteMember(this);
    this._data = void 0;
  }
}
class Circle {
  _data = {};
  _members = {};
  _owner;
  _initiator;
  /**
   * Creates an instance of Circle
   *
   * @param data
   */
  constructor(data) {
    this.updateData(data);
  }
  /**
   * Update inner circle data, owner and initiator
   *
   * @param data
   */
  updateData(data) {
    if (typeof data !== "object") {
      throw new Error("Invalid circle");
    }
    if (!data.id) {
      throw new Error("This circle do not have a proper uid");
    }
    this._data = data;
    this._owner = new Member(data.owner, this);
    if (data.initiator) {
      this._initiator = new Member(data.initiator, this);
    }
  }
  // METADATA -----------------------------------------
  /**
   * Circle id
   */
  get id() {
    return this._data.id;
  }
  /**
   * Formatted display name
   */
  get displayName() {
    return this._data.displayName;
  }
  /**
   * Set the display name
   */
  set displayName(text2) {
    this._data.displayName = text2;
  }
  /**
   * Circle creation date
   */
  get creation() {
    return this._data.creation;
  }
  /**
   * Circle description
   */
  get description() {
    return this._data.description;
  }
  /**
   * Circle description
   */
  set description(text2) {
    this._data.description = text2;
  }
  /**
   * Circle direct member count (excluding nested circles)
   */
  get population() {
    return this._data.population;
  }
  /**
   * Circle total member count (direct + inherited from nested circles)
   */
  get populationInherited() {
    return this._data.populationInherited;
  }
  // MEMBERSHIP -----------------------------------------
  /**
   * Circle ini_initiator the current
   * user info for this circle
   * null if not a member
   */
  get initiator() {
    return this._initiator;
  }
  /**
   * Set new circle initiator
   * null if not a member
   */
  set initiator(initiator) {
    if (initiator && initiator.constructor.name !== Member.name) {
      throw new Error("Initiator must be a Member type");
    }
    this._initiator = initiator;
  }
  /**
   * Circle ownership
   */
  get owner() {
    return this._owner;
  }
  /**
   * Set new circle owner
   */
  set owner(owner) {
    if (owner.constructor.name !== Member.name) {
      throw new Error("Owner must be a Member type");
    }
    this._owner = owner;
  }
  /**
   * Circle members
   */
  get members() {
    return this._members;
  }
  /**
   * Define members circle
   */
  set members(members) {
    this._members = members;
  }
  /**
   * Add a member to this circle
   *
   * @param member
   */
  addMember(member) {
    if (member.constructor.name !== Member.name) {
      throw new Error("Member must be a Member type");
    }
    const singleId = member.singleId;
    if (this._members[singleId]) {
      console.warn("Replacing existing member data", member);
    }
    this._members[singleId] = member;
  }
  /**
   * Remove a member from this circle
   *
   * @param member
   */
  deleteMember(member) {
    if (member.constructor.name !== Member.name) {
      throw new Error("Member must be a Member type");
    }
    const singleId = member.singleId;
    if (!this._members[singleId]) {
      console.warn("The member was not in this circle. Nothing was done.", member);
    }
    delete this._members[singleId];
  }
  // CONFIGS --------------------------------------------
  get settings() {
    return this._data.settings;
  }
  /**
   * Circle config
   */
  get config() {
    return this._data.config;
  }
  /**
   * Define circle config
   */
  set config(config) {
    this._data.config = config;
  }
  /**
   * Circle is personal
   */
  get isPersonal() {
    return (this._data.config & CircleConfigs.PERSONAL) !== 0;
  }
  /**
   * Circle requires invite to be confirmed by moderator or above
   */
  get requireJoinAccept() {
    return (this._data.config & CircleConfigs.VISIBLE) !== 0;
  }
  /**
   * Circle can be requested to join
   */
  get canJoin() {
    return (this._data.config & CircleConfigs.OPEN) !== 0;
  }
  /**
   * Circle is visible to others
   */
  get isVisible() {
    return (this._data.config & CircleConfigs.VISIBLE) !== 0;
  }
  /**
   * Circle requires invite to be accepted by the member
   */
  get requireInviteAccept() {
    return (this._data.config & CircleConfigs.INVITE) !== 0;
  }
  // PERMISSIONS SHORTCUTS ------------------------------
  /**
   * Can the initiator add members to this circle?
   */
  get isOwner() {
    return this.initiator?.level === MemberLevels.OWNER;
  }
  // PERMISSIONS SHORTCUTS ------------------------------
  /**
   * Is the initiator an admin of this circle?
   */
  get isAdmin() {
    return this.initiator?.level === MemberLevels.ADMIN;
  }
  /**
   * Is the initiator a member of this circle?
   */
  get isMember() {
    return this.initiator?.level && this.initiator?.level > MemberLevels.NONE;
  }
  /**
   * Is the initiator a pending member of this circle?
   */
  get isPendingMember() {
    return this.initiator?.level === MemberLevels.NONE;
  }
  /**
   * Can the initiator delete this circle?
   */
  get canDelete() {
    return this.isOwner;
  }
  /**
   * Can the initiator leave this circle?
   */
  get canLeave() {
    return this.isMember && !this.isOwner;
  }
  /**
   * Can the initiator add/remove members to this circle?
   */
  get canManageMembers() {
    return this.initiator?.level && this.initiator?.level >= MemberLevels.MODERATOR || (this.config & CircleConfigs.FRIEND) !== 0;
  }
  // PARAMS ---------------------------------------------
  /**
   * Vue router param
   */
  get router() {
    return {
      name: "team",
      params: { teamId: this.id }
    };
  }
  /**
   * Default javascript fallback
   * Used for sorting as well
   */
  toString() {
    return this.displayName;
  }
}
const memberGridItem = "_member-grid-item_87e0f_1";
const memberGridItemActions = "_member-grid-item__actions_87e0f_9";
const memberInfo = "_member-info_87e0f_13";
const memberName = "_member-name_87e0f_19";
const memberRole = "_member-role_87e0f_24";
const style0$1 = {
  memberGridItem,
  memberGridItemActions,
  memberInfo,
  memberName,
  memberRole
};
const _sfc_main$3 = {
  name: "MemberGridItem",
  components: {
    NcAvatar,
    IconAccountGroupOutline: IconAccountGroup,
    NcActions,
    NcActionButton,
    NcActionSeparator,
    NcActionText,
    IconDeleteOutline: TrashCanOutlineIcon,
    IconExitToApp,
    IconShieldCheckOutline,
    IconCheckOutline,
    IconCloseOutline,
    NcButton
  },
  mixins: [RouterMixin],
  props: {
    member: {
      type: Object,
      required: true
    },
    isTeam: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      loading: false
    };
  },
  computed: {
    /**
     * Return the current circle
     *
     * @return {Circle}
     */
    circle() {
      return this.$store.getters.getCircle(this.selectedCircle);
    },
    /**
     * Current user member level
     *
     * @return {number}
     */
    currentUserLevel() {
      return this.circle?.initiator?.level || MemberLevels.MEMBER;
    },
    /**
     * Current user member level
     *
     * @return {string}
     */
    currentUserId() {
      return this.circle?.initiator?.singleId;
    },
    /**
     * Available levels change to the current user
     *
     * @return {Array}
     */
    availableLevelsChange() {
      const levels = [];
      if (this.member.level === MemberLevels.OWNER) {
        return levels;
      }
      if (this.isCurrentUser) {
        return levels;
      }
      if (this.currentUserLevel >= MemberLevels.ADMIN && this.member.level !== MemberLevels.ADMIN) {
        levels.push(MemberLevels.ADMIN);
      }
      if (this.currentUserLevel >= MemberLevels.ADMIN) {
        if (this.member.level !== MemberLevels.MODERATOR) {
          levels.push(MemberLevels.MODERATOR);
        }
        if (this.member.level !== MemberLevels.MEMBER) {
          levels.push(MemberLevels.MEMBER);
        }
      }
      if (this.circle.isOwner) {
        levels.push(MemberLevels.OWNER);
      }
      return levels;
    },
    /**
     * Is the current member the current user?
     *
     * @return {boolean}
     */
    isCurrentUser() {
      return this.member.singleId === this.currentUserId;
    },
    /**
     * Is the current member pending moderator approval?
     *
     * @return {boolean}
     */
    isPendingApproval() {
      return this.member.level === MemberLevels.NONE && this.member.status === MemberStatus.PENDING;
    },
    /**
     * Can the current user change the level of others?
     *
     * @return {boolean}
     */
    canChangeLevel() {
      return this.circle.canManageMembers && this.availableLevelsChange.length > 0 && !this.isCurrentUser;
    },
    /**
     * Can the current user delete members or?
     *
     * @return {boolean}
     */
    canDelete() {
      return this.circle.canManageMembers && this.member.level < this.currentUserLevel && !this.isCurrentUser && this.member.level !== MemberLevels.OWNER;
    },
    /**
     * Get the member role name
     *
     * @return {string|null}
     */
    memberRole() {
      if (!this.member.level || this.member.level === MemberLevels.NONE) {
        return null;
      }
      return CIRCLES_MEMBER_LEVELS[this.member.level] || null;
    }
  },
  methods: {
    /**
     * Return the promote/demote member action label
     *
     * @param {MemberLevel} level the member level
     * @return {string}
     */
    levelChangeLabel(level) {
      if (level === MemberLevels.OWNER) {
        return t("circles", "Promote as sole owner");
      }
      if (this.member.level < level) {
        return t("circles", "Promote to {level}", { level: CIRCLES_MEMBER_LEVELS[level] });
      }
      return t("circles", "Demote to {level}", { level: CIRCLES_MEMBER_LEVELS[level] });
    },
    /**
     * Delete the current member
     */
    async deleteMember() {
      if (!this.isCurrentUser) {
        await this.doDeleteMember();
        return;
      }
      try {
        const dialog = new DialogBuilder().setName(t("circles", "Leave team")).setText(t("circles", "Are you sure you want to leave this team? This action cannot be undone.")).setButtons([
          {
            label: t("circles", "Cancel"),
            type: "secondary",
            callback: () => {
            }
          },
          {
            label: t("circles", "Leave team"),
            type: "error",
            callback: async () => {
              try {
                await this.doDeleteMember();
              } catch (e) {
                this.logger.error("Error in delete member callback", { e });
                showError(t("circles", "Leave team failed."));
              }
            }
          }
        ]).build();
        await dialog.show();
      } catch (error) {
      }
    },
    async doDeleteMember() {
      this.loading = true;
      try {
        await this.$store.dispatch("deleteMemberFromCircle", {
          member: this.member,
          leave: this.isCurrentUser
        });
      } catch (error) {
        if (error?.response?.status === 404) {
          this.logger.debug("Member is not in circle");
          return;
        }
        this.logger.error("Could not delete the member", { member: this.member, error });
        showError(t("circles", "Could not delete the member {displayName}", this.member));
      } finally {
        this.loading = false;
      }
    },
    async changeLevel(level) {
      this.loading = true;
      try {
        await changeMemberLevel(this.circle.id, this.member.id, level);
        this.showLevelMenu = false;
        if (level === MemberLevels.OWNER) {
          await this.$store.dispatch("getCircle", this.circle.id);
          await this.$store.dispatch("getCircleMembers", { circleId: this.circle.id });
          return;
        }
        this.member.level = level;
      } catch (error) {
        this.logger.error("Could not change the member level", { level: CIRCLES_MEMBER_LEVELS[level], error });
        showError(t("circles", "Could not change the member level to {level}", {
          level: CIRCLES_MEMBER_LEVELS[level]
        }));
      } finally {
        this.loading = false;
      }
    },
    async acceptMember() {
      this.loading = true;
      try {
        await await this.$store.dispatch("acceptCircleMember", {
          circleId: this.circle.id,
          memberId: this.member.id
        });
      } catch (error) {
        this.logger.error("Could not accept membership request", { member: this.member, error });
        showError(t("circles", "Could not accept membership request"));
      } finally {
        this.loading = false;
      }
    }
  }
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_IconAccountGroupOutline = resolveComponent("IconAccountGroupOutline");
  const _component_NcAvatar = resolveComponent("NcAvatar");
  const _component_IconCheckOutline = resolveComponent("IconCheckOutline");
  const _component_NcButton = resolveComponent("NcButton");
  const _component_IconCloseOutline = resolveComponent("IconCloseOutline");
  const _component_NcActionText = resolveComponent("NcActionText");
  const _component_IconShieldCheckOutline = resolveComponent("IconShieldCheckOutline");
  const _component_NcActionButton = resolveComponent("NcActionButton");
  const _component_NcActionSeparator = resolveComponent("NcActionSeparator");
  const _component_IconExitToApp = resolveComponent("IconExitToApp");
  const _component_IconDeleteOutline = resolveComponent("IconDeleteOutline");
  const _component_NcActions = resolveComponent("NcActions");
  return openBlock(), createElementBlock("div", {
    class: normalizeClass(_ctx.$style.memberGridItem)
  }, [
    $props.isTeam ? (openBlock(), createBlock(_component_NcAvatar, {
      key: 0,
      "display-name": $props.member.displayName,
      "is-no-user": true,
      size: 32
    }, {
      icon: withCtx(() => [
        createVNode(_component_IconAccountGroupOutline, { size: 20 })
      ]),
      _: 1
    }, 8, ["display-name"])) : (openBlock(), createBlock(_component_NcAvatar, {
      key: 1,
      user: $props.member.userId,
      "display-name": $props.member.displayName,
      size: 32
    }, null, 8, ["user", "display-name"])),
    createBaseVNode("div", {
      class: normalizeClass(_ctx.$style.memberInfo)
    }, [
      createBaseVNode("span", {
        class: normalizeClass(_ctx.$style.memberName)
      }, toDisplayString($props.member.displayName), 3),
      $options.memberRole ? (openBlock(), createElementBlock("span", {
        key: 0,
        class: normalizeClass(_ctx.$style.memberRole)
      }, toDisplayString($options.memberRole), 3)) : createCommentVNode("", true)
    ], 2),
    !$data.loading && $options.isPendingApproval && $options.circle.canManageMembers ? (openBlock(), createElementBlock("div", {
      key: 2,
      class: normalizeClass(_ctx.$style.memberGridItemActions)
    }, [
      createVNode(_component_NcButton, {
        "aria-label": _ctx.t("circles", "Accept membership request"),
        onClick: $options.acceptMember
      }, {
        icon: withCtx(() => [
          createVNode(_component_IconCheckOutline, { size: 20 })
        ]),
        _: 1
      }, 8, ["aria-label", "onClick"]),
      createVNode(_component_NcButton, {
        "aria-label": _ctx.t("circles", "Reject membership request"),
        onClick: $options.deleteMember
      }, {
        icon: withCtx(() => [
          createVNode(_component_IconCloseOutline, { size: 20 })
        ]),
        _: 1
      }, 8, ["aria-label", "onClick"])
    ], 2)) : (openBlock(), createBlock(_component_NcActions, { key: 3 }, {
      default: withCtx(() => [
        $data.loading ? (openBlock(), createBlock(_component_NcActionText, {
          key: 0,
          icon: "icon-loading-small"
        }, {
          default: withCtx(() => [
            createTextVNode(toDisplayString(_ctx.t("circles", "Loading …")), 1)
          ]),
          _: 1
        })) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
          $options.canChangeLevel ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
            createVNode(_component_NcActionText, null, {
              icon: withCtx(() => [
                createVNode(_component_IconShieldCheckOutline, { size: 16 })
              ]),
              default: withCtx(() => [
                createTextVNode(toDisplayString(_ctx.t("circles", "Manage level")) + " ", 1)
              ]),
              _: 1
            }),
            (openBlock(true), createElementBlock(Fragment, null, renderList($options.availableLevelsChange, (level) => {
              return openBlock(), createBlock(_component_NcActionButton, {
                key: level,
                icon: "",
                onClick: ($event) => $options.changeLevel(level)
              }, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString($options.levelChangeLabel(level)), 1)
                ]),
                _: 2
              }, 1032, ["onClick"]);
            }), 128)),
            createVNode(_component_NcActionSeparator)
          ], 64)) : createCommentVNode("", true),
          $options.isCurrentUser && !$options.circle.isOwner ? (openBlock(), createBlock(_component_NcActionButton, {
            key: 1,
            onClick: $options.deleteMember
          }, {
            icon: withCtx(() => [
              createVNode(_component_IconExitToApp, { size: 16 })
            ]),
            default: withCtx(() => [
              createTextVNode(toDisplayString(_ctx.t("circles", "Leave team")) + " ", 1)
            ]),
            _: 1
          }, 8, ["onClick"])) : $options.canDelete ? (openBlock(), createBlock(_component_NcActionButton, {
            key: 2,
            onClick: $options.deleteMember
          }, {
            icon: withCtx(() => [
              createVNode(_component_IconDeleteOutline, { size: 20 })
            ]),
            default: withCtx(() => [
              createTextVNode(" " + toDisplayString(_ctx.t("circles", "Remove member")), 1)
            ]),
            _: 1
          }, 8, ["onClick"])) : createCommentVNode("", true)
        ], 64))
      ]),
      _: 1
    }))
  ], 2);
}
const cssModules$1 = {
  "$style": style0$1
};
const MemberGridItem = /* @__PURE__ */ _export_sfc$1(_sfc_main$3, [["render", _sfc_render$2], ["__cssModules", cssModules$1]]);
const IsMobileMixin = {
  computed: {
    isMobile() {
      return useIsMobile().value;
    }
  }
};
const shareType = Object.keys(SHARES_TYPES_MEMBER_MAP);
const maxAutocompleteResults = parseInt(window.OC.config["sharing.maxAutocompleteResults"], 10) || 25;
async function getSuggestions(search, circle = null) {
  const request = await cancelableClient.get(generateOcsUrl("apps/files_sharing/api/v1/sharees"), {
    params: {
      format: "json",
      itemType: "teams",
      search,
      perPage: maxAutocompleteResults,
      shareType,
      lookup: false
    }
  });
  const data = request.data.ocs.data;
  const exact = request.data.ocs.data.exact;
  data.exact = [];
  let rawExactSuggestions = Object.values(exact).reduce((arr, elem) => arr.concat(elem), []);
  let rawSuggestions = Object.values(data).reduce((arr, elem) => arr.concat(elem), []);
  const isCircleFederated = circle ? (circle.config & CircleConfigs.FEDERATED) !== 0 : false;
  if (isCircleFederated) {
    rawExactSuggestions = rawExactSuggestions.filter((result) => !(result.value?.shareType === ShareType.Remote && result.value?.isTrustedServer === false));
    rawSuggestions = rawSuggestions.filter((result) => !(result.value?.shareType === ShareType.Remote && result.value?.isTrustedServer === false));
  } else {
    rawExactSuggestions = rawExactSuggestions.filter((result) => result.value?.shareType !== ShareType.Remote);
    rawSuggestions = rawSuggestions.filter((result) => result.value?.shareType !== ShareType.Remote);
  }
  const exactSuggestions = rawExactSuggestions.filter((result) => typeof result === "object").map((share) => formatResults(share)).sort((a2, b2) => a2.shareType - b2.shareType);
  const suggestions = rawSuggestions.filter((result) => typeof result === "object").map((share) => formatResults(share)).sort((a2, b2) => a2.shareType - b2.shareType);
  const allSuggestions = exactSuggestions.concat(suggestions);
  const nameCounts = allSuggestions.reduce((nameCounts2, result) => {
    if (!result.displayName) {
      return nameCounts2;
    }
    if (!nameCounts2[result.displayName]) {
      nameCounts2[result.displayName] = 0;
    }
    nameCounts2[result.displayName]++;
    return nameCounts2;
  }, {});
  const finalResults = allSuggestions.map((item) => {
    if (nameCounts[item.displayName] > 1 && !item.desc) {
      return { ...item, desc: item.shareWithDisplayNameUnique };
    }
    return item;
  });
  console.info("suggestions", finalResults);
  return finalResults;
}
async function getRecommendations() {
  const request = await cancelableClient.get(generateOcsUrl("apps/files_sharing/api/v1/sharees_recommended"), {
    params: {
      format: "json",
      itemType: "contacts",
      shareType
    }
  });
  const exact = request.data.ocs.data.exact;
  const recommendations = Object.values(exact).reduce((arr, elem) => arr.concat(elem), []);
  const finalResults = recommendations.map((share) => formatResults(share));
  console.info("recommendations", finalResults);
  return finalResults;
}
function formatResults(result) {
  const type = `picker-${result.value.shareType}`;
  return {
    label: result.label,
    id: `${type}-${result.value.shareWith}`,
    // If this is a user, set as user for avatar display by UserBubble
    user: [window.OC.Share.SHARE_TYPE_USER, window.OC.Share.SHARE_TYPE_REMOTE].indexOf(result.value.shareType) > -1 ? result.value.shareWith : null,
    type,
    ...result.value
  };
}
const _sfc_main$2 = defineComponent({
  name: "MemberList",
  components: {
    IconSearch,
    NcSelect,
    NcTextField: _sfc_main$W,
    EntityPicker,
    IconContact,
    MemberGridItem,
    NcEmptyContent,
    VList: H,
    NcLoadingIcon
  },
  mixins: [IsMobileMixin, RouterMixin],
  props: {
    list: {
      type: Array,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  setup() {
    const searchQuery = ref("");
    const searchQueryDebounced = refDebounced(searchQuery, 500);
    const searchRole = ref(null);
    const roles = Object.entries(CIRCLES_MEMBER_LEVELS).map(([id, label]) => ({
      id: Number(id),
      label
    }));
    roles.unshift({
      id: Number(MemberLevels.NONE),
      label: translate("circles", "Pending")
    });
    return {
      searchQuery,
      searchQueryDebounced,
      searchRole,
      roles: readonly(roles)
    };
  },
  data() {
    return {
      loadingList: false,
      pickerLoading: false,
      showPicker: false,
      showPickerIntro: true,
      recommendations: [],
      pickerCircle: null,
      pickerData: [],
      pickerSelection: {},
      pickerTypes: CIRCLES_MEMBER_GROUPING,
      circleHeaderHeight: 0
    };
  },
  computed: {
    /**
     * Return the current circle
     *
     * @return {object}
     */
    circle() {
      return this.$store.getters.getCircle(this.selectedCircle);
    },
    members() {
      return Object.values(this.$store.getters.getCircle(this.circle.id)?.members || []);
    },
    // Decode HTML entities in the circle display name so apostrophes (') and other
    // HTML-encoded chars (e.g. &#39;) are shown correctly in the picker labels.
    decodedTeamName() {
      const raw = this.circle && this.circle.displayName ? this.circle.displayName : "";
      const ta = document.createElement("textarea");
      ta.innerHTML = raw;
      return ta.value;
    },
    filteredPickerData() {
      return this.pickerData.filter((entity) => {
        const type = SHARES_TYPES_MEMBER_MAP[entity.shareType];
        const list = this.list.filter(({ userType }) => userType === type);
        if (list) {
          return list.find((member) => member.userId === entity.shareWith) === void 0;
        }
        return true;
      });
    },
    flatList() {
      const teams = this.list.filter((member) => !member.isUser);
      const users = this.list.filter((member) => member.isUser);
      return [...teams, ...users];
    },
    hasMembers() {
      return this.flatList.length > 0;
    },
    hasActiveFilters() {
      return this.searchQuery !== "" || this.searchRole !== null;
    },
    virtualListStyle() {
      const gridBaseline = parseInt(getComputedStyle(document.documentElement).getPropertyValue("--default-grid-baseline")) || 4;
      const headerHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue("--header-height")) || 50;
      const padding = gridBaseline * 32;
      const availableHeight = window.innerHeight - headerHeight - this.circleHeaderHeight - padding;
      return {
        height: `${Math.max(availableHeight, 200)}px`
      };
    }
  },
  watch: {
    searchQueryDebounced() {
      this.fetchCircleMembers();
    },
    searchRole() {
      this.fetchCircleMembers();
    },
    "circle.id": {
      handler() {
        this.fetchCircleMembers();
      },
      immediate: true
    }
  },
  mounted() {
    subscribe("contacts:circles:append", this.onShowPicker);
    subscribe("guests:user:created", this.onGuestCreated);
    this.measureCircleHeader();
  },
  beforeUnmount() {
    this.resizeObserver?.disconnect();
  },
  methods: {
    /**
     * Measure the circle details header height from the DOM
     * and keep it updated via ResizeObserver.
     */
    measureCircleHeader() {
      const header = document.querySelector(".circle-details__header-wrapper");
      if (!header) {
        return;
      }
      this.circleHeaderHeight = header.getBoundingClientRect().height;
      this.resizeObserver = new ResizeObserver((entries) => {
        for (const entry of entries) {
          this.circleHeaderHeight = entry.contentRect.height;
        }
      });
      this.resizeObserver.observe(header);
    },
    /**
     * Show picker and fetch for recommendations
     * Cache the circleId in case the url change or something
     * and make sure we add them to the desired circle.
     *
     * @param {string} circleId the circle id to add members to
     */
    async onShowPicker(circleId) {
      this.showPicker = true;
      this.pickerLoading = true;
      this.pickerCircle = circleId;
      try {
        const results = await getRecommendations();
        this.recommendations = results;
        this.pickerData = results;
      } catch (error) {
        console.error("Unable to get the recommendations list", error);
      } finally {
        this.pickerLoading = false;
      }
    },
    /**
     * On EntityPicker search.
     * Returns recommendations if empty
     *
     * @param {string} term the searched term
     */
    async onSearch(term) {
      if (term.trim() === "") {
        this.pickerData = this.recommendations;
        return;
      }
      this.pickerLoading = true;
      try {
        const results = await getSuggestions(term, this.circle);
        this.pickerData = results;
      } catch (error) {
        console.error("Unable to get the results", error);
        showError(translate("circles", "Unable to get the results"));
      } finally {
        this.pickerLoading = false;
      }
    },
    /**
     * On picker submit
     *
     * @param {Array} selection the selection to add to the circle
     */
    async onPickerPick(selection) {
      this.logger.info("Adding selection to circle", { selection, pickerCircle: this.pickerCircle });
      this.pickerLoading = true;
      selection = selection.map((entry) => ({
        id: entry.shareWith,
        type: SHARES_TYPES_MEMBER_MAP[entry.shareType]
      }));
      try {
        const members = await this.$store.dispatch("addMembersToCircle", { circleId: this.pickerCircle, selection });
        if (members.length > 0) {
          this.resetPicker();
          this.fetchCircleMembers();
        }
        if (members.length < selection.length) {
          showWarning(translate("circles", "Some members could not be added"));
          this.pickerSelection = {};
        }
      } catch (error) {
        showError(translate("circles", "There was an issue adding members to the team"));
        console.error("There was an issue adding members to the circle", this.pickerCircle, error);
      } finally {
        this.pickerLoading = false;
      }
    },
    /**
     * Reset picker related variables
     */
    resetPicker() {
      this.showPicker = false;
      this.pickerCircle = null;
      this.pickerData = [];
      this.pickerSelection = {};
    },
    async onGuestCreated(guest) {
      const results = await getSuggestions(guest.username, this.circle);
      this.$refs.entityPicker.onClick(results[0]);
    },
    async fetchCircleMembers(silent = false) {
      if (!this.circle || !this.circle.id) {
        return;
      }
      if (!this.circle.canManageMembers) {
        return;
      }
      if (!silent) {
        this.loadingList = true;
      }
      const payload = { circleId: this.circle.id, search: this.searchQuery || null, role: this.searchRole?.id };
      this.logger.debug("Fetching members for", payload);
      try {
        await this.$store.dispatch("getCircleMembers", payload);
        console.log("debug: getCircleMembers", this.list);
      } catch (error) {
        console.error(error);
        showError(translate("circles", "There was an error fetching the member list"));
      } finally {
        if (!silent) {
          this.loadingList = false;
        }
      }
    }
  }
});
const memberList = "_member-list_1046y_1";
const style0 = {
  memberList
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_IconContact = resolveComponent("IconContact");
  const _component_NcEmptyContent = resolveComponent("NcEmptyContent");
  const _component_IconSearch = resolveComponent("IconSearch");
  const _component_NcTextField = resolveComponent("NcTextField");
  const _component_NcSelect = resolveComponent("NcSelect");
  const _component_NcLoadingIcon = resolveComponent("NcLoadingIcon");
  const _component_MemberGridItem = resolveComponent("MemberGridItem");
  const _component_VList = resolveComponent("VList");
  const _component_EntityPicker = resolveComponent("EntityPicker");
  return openBlock(), createElementBlock("section", {
    class: normalizeClass(_ctx.$style.memberList)
  }, [
    !_ctx.circle.isMember ? (openBlock(), createBlock(_component_NcEmptyContent, {
      key: 0,
      class: "empty-content",
      name: _ctx.t("circles", "The list of members is only visible to members of this team")
    }, {
      icon: withCtx(() => [
        createVNode(_component_IconContact, { size: 20 })
      ]),
      _: 1
    }, 8, ["name"])) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
      createBaseVNode("div", {
        class: normalizeClass(["member-list__filters", { "member-list__filters--mobile": _ctx.isMobile }])
      }, [
        createVNode(_component_NcTextField, {
          modelValue: _ctx.searchQuery,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => _ctx.searchQuery = $event),
          class: "member-list__search",
          label: _ctx.t("circles", "Search among current members"),
          "trailing-button-icon": "close",
          "show-trailing-button": _ctx.searchQuery !== "",
          onTrailingButtonClick: _cache[1] || (_cache[1] = ($event) => _ctx.searchQuery = "")
        }, {
          icon: withCtx(() => [
            createVNode(_component_IconSearch, { size: 20 })
          ]),
          _: 1
        }, 8, ["modelValue", "label", "show-trailing-button"]),
        createVNode(_component_NcSelect, {
          modelValue: _ctx.searchRole,
          "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => _ctx.searchRole = $event),
          options: _ctx.roles,
          placeholder: _ctx.t("circles", "Role"),
          multiple: false,
          style: { "min-width": "160px" }
        }, null, 8, ["modelValue", "options", "placeholder"])
      ], 2),
      _ctx.loading || _ctx.loadingList ? (openBlock(), createBlock(_component_NcEmptyContent, {
        key: 0,
        class: "empty-content",
        name: _ctx.t("circles", "Loading members list …")
      }, {
        icon: withCtx(() => [
          createVNode(_component_NcLoadingIcon, { size: 20 })
        ]),
        _: 1
      }, 8, ["name"])) : !_ctx.hasMembers ? (openBlock(), createBlock(_component_NcEmptyContent, {
        key: 1,
        class: "empty-content",
        name: _ctx.hasActiveFilters ? _ctx.t("circles", "No members found matching your search") : _ctx.t("circles", "You currently have no access to the member list")
      }, {
        icon: withCtx(() => [
          createVNode(_component_IconContact, { size: 20 })
        ]),
        _: 1
      }, 8, ["name"])) : (openBlock(), createBlock(_component_VList, {
        key: 2,
        class: "member-list__virtual",
        style: normalizeStyle(_ctx.virtualListStyle),
        data: _ctx.flatList
      }, {
        default: withCtx(({ item }) => [
          (openBlock(), createBlock(_component_MemberGridItem, {
            key: `member-grid-item-${item.id}`,
            member: item,
            "is-team": !item.isUser
          }, null, 8, ["member", "is-team"]))
        ]),
        _: 1
      }, 8, ["style", "data"]))
    ], 64)),
    _ctx.showPicker ? (openBlock(), createBlock(_component_EntityPicker, {
      key: 2,
      ref: "entityPicker",
      selection: _ctx.pickerSelection,
      "onUpdate:selection": _cache[3] || (_cache[3] = ($event) => _ctx.pickerSelection = $event),
      "confirm-label": _ctx.t("circles", "Add to {team}", { team: _ctx.decodedTeamName }),
      "title-label": _ctx.t("circles", "Invite members to {team}", { team: _ctx.decodedTeamName }),
      "data-types": _ctx.pickerTypes,
      "data-set": _ctx.filteredPickerData,
      "internal-search": false,
      loading: _ctx.pickerLoading,
      onClose: _ctx.resetPicker,
      onSearch: _ctx.onSearch,
      onSubmit: _ctx.onPickerPick
    }, null, 8, ["selection", "confirm-label", "title-label", "data-types", "data-set", "loading", "onClose", "onSearch", "onSubmit"])) : createCommentVNode("", true)
  ], 2);
}
const cssModules = {
  "$style": style0
};
const MemberList = /* @__PURE__ */ _export_sfc$1(_sfc_main$2, [["render", _sfc_render$1], ["__cssModules", cssModules], ["__scopeId", "data-v-dede96ef"]]);
const CopyToClipboardMixin = {
  data() {
    return {
      copied: false,
      copyLoading: false,
      copySuccess: false
    };
  },
  computed: {
    copyLinkIcon() {
      if (this.copySuccess) {
        return "icon-checkmark";
      }
      if (this.copyLoading) {
        return "icon-loading-small";
      }
      return "icon-public";
    }
  },
  methods: {
    async copyToClipboard(url) {
      this.copyLoading = true;
      try {
        await navigator.clipboard.writeText(url);
        this.copySuccess = true;
        this.copied = true;
        showSuccess(t("circles", "Link copied to the clipboard"));
      } catch (error) {
        this.copySuccess = false;
        this.copied = true;
        showError(t("circles", "Could not copy link to the clipboard."));
      } finally {
        this.copyLoading = false;
        setTimeout(() => {
          this.copied = false;
          this.copySuccess = false;
        }, 2e3);
      }
    }
  }
};
const CircleActionsMixin = {
  props: {
    circle: {
      type: Circle,
      required: true
    }
  },
  mixins: [CopyToClipboardMixin],
  data() {
    return {
      loadingAction: false,
      loadingJoin: false
    };
  },
  computed: {
    copyButtonText() {
      if (this.copied) {
        return this.copySuccess ? t("circles", "Copied") : t("circles", "Could not copy");
      }
      return t("circles", "Copy link");
    },
    circleUrl() {
      const route = this.$router.resolve(this.circle.router);
      return window.location.origin + route.href;
    },
    joinButtonTitle() {
      if (this.circle.requireJoinAccept) {
        return t("circles", "Request to join");
      }
      return t("circles", "Join team");
    }
  },
  methods: {
    confirmLeaveCircle() {
      window.OC.dialogs.confirmDestructive(
        t("circles", "You are about to leave {circle}.\nAre you sure?", {
          circle: this.circle.displayName
        }),
        t("circles", "Please confirm team leave"),
        window.OC.dialogs.YES_NO_BUTTONS,
        this.leaveCircle,
        true
      );
    },
    async leaveCircle(confirm) {
      if (!confirm) {
        this.logger.debug("Circle leave cancelled");
        return;
      }
      this.loadingAction = true;
      const member = this.circle.initiator;
      try {
        await this.$store.dispatch("deleteMemberFromCircle", {
          member,
          leave: true
        });
        this.circle.initiator = null;
      } catch (error) {
        console.error("Could not leave the circle", member, error);
        showError(t("circles", "Could not leave the team {displayName}", this.circle));
      } finally {
        this.loadingAction = false;
      }
    },
    async joinCircle() {
      this.loadingJoin = true;
      try {
        const initiator = await joinCircle(this.circle.id);
        const member = new Member(initiator, this.circle);
        this.circle.initiator = member;
        member.circle.addMember(member);
      } catch (error) {
        showError(t("circles", "Unable to join the team"));
        console.error("Unable to join the circle", error);
      } finally {
        this.loadingJoin = false;
      }
    },
    confirmDeleteCircle() {
      window.OC.dialogs.confirmDestructive(
        t("circles", "You are about to delete {circle}.\nAre you sure?", {
          circle: this.circle.displayName
        }),
        t("circles", "Please confirm team deletion"),
        window.OC.dialogs.YES_NO_BUTTONS,
        this.deleteCircle,
        true
      );
    },
    async deleteCircle(confirm) {
      if (!confirm) {
        this.logger.debug("Circle deletion cancelled");
        return;
      }
      this.loadingAction = true;
      try {
        await this.$store.dispatch("deleteCircle", this.circle.id);
      } catch (error) {
        showError(t("circles", "Unable to delete the team"));
      } finally {
        this.loadingAction = false;
      }
    },
    /**
     * Trigger the entity picker view
     */
    async addMemberToCircle() {
      try {
        await this.$router.push(this.circle.router);
      } catch (error) {
        console.error("Could not open circle member picker", error);
      }
      emit("contacts:circles:append", this.circle.id);
    }
  }
};
const VALID_MIME_TYPES = ["image/png", "image/jpeg"];
const AVATAR_ACTIONS = Object.freeze({
  SET: "set",
  DELETE: "delete"
});
const picker = getFilePickerBuilder(t("circles", "Choose a team picture")).setMultiSelect(false).setMimeTypeFilter(VALID_MIME_TYPES).setType(FilePickerType.Choose).allowDirectories(false).build();
const _sfc_main$1 = {
  name: "CircleDetails",
  components: {
    AccountPlusIcon,
    Avatar: NcAvatar,
    NcButton,
    NcDialog,
    ContentHeading,
    ListItem: NcListItem,
    CogIcon,
    CopyIcon,
    IconAccountGroup,
    FileDocumentOutline,
    LoginIcon,
    LogoutIcon,
    MemberList,
    CircleSettings,
    TeamResourceButton,
    NcEmptyContent,
    NcLoadingIcon,
    NcPopover,
    PencilIcon,
    UserBubble: NcUserBubble,
    NcTextField: _sfc_main$W,
    NcTextArea,
    NcActions,
    NcActionButton,
    FolderOutlineIcon,
    MessageIcon,
    CalendarIcon,
    ViewDashboardIcon,
    BookOpenPageVariantIcon,
    CheckIcon,
    FolderIcon,
    TrashCanOutlineIcon,
    TrayArrowUpIcon,
    VueCropper
  },
  mixins: [CircleActionsMixin],
  setup() {
    const avatarList = ref();
    const { width } = useElementSize(avatarList);
    const avatarAccept = VALID_MIME_TYPES.join(",");
    const nextcloudMajorVersion = parseInt(window.OC.config.version.split(".")[0]);
    const avatarSupported = nextcloudMajorVersion >= 34;
    return { avatarList, width, avatarAccept, avatarSupported };
  },
  data() {
    return {
      active: false,
      isEditing: false,
      showMembersModal: false,
      loading: false,
      loadingJoin: false,
      loadingLeave: false,
      loadingName: false,
      loadingDescription: false,
      isSettingsPopoverShown: false,
      resources: [],
      originalDisplayName: "",
      originalDescription: "",
      // Resource creation
      activePopover: null,
      resourceInputs: reactive({}),
      popoverBoundary: null,
      createdCalendar: null,
      showCalendarSuccessNotification: false,
      createdCalendarName: "",
      // Avatar
      avatarUrl: void 0,
      pendingAvatarBlob: null,
      pendingAvatarAction: null,
      pendingAvatarPreviewUrl: void 0,
      showCropper: false,
      loadingAvatar: false,
      cropperOptions: {
        aspectRatio: 1 / 1,
        viewMode: 1,
        guides: false,
        center: false,
        highlight: false,
        autoCropArea: 1,
        minContainerWidth: 300,
        minContainerHeight: 300
      }
    };
  },
  computed: {
    descriptionPlaceholder() {
      if (this.circle.description.trim() === "") {
        return t("circles", "There is no description for this team");
      }
      return t("circles", "Enter a description for the team");
    },
    isEmptyDescription() {
      return this.circle.description.trim() === "";
    },
    showDescription() {
      if (this.circle.isOwner) {
        return true;
      }
      return !this.isEmptyDescription;
    },
    members() {
      return Object.values(this.$store.getters.getCircle(this.circle.id)?.members || []);
    },
    circleUrl() {
      return window.location.href;
    },
    displayAvatarUrl() {
      if (!this.avatarSupported) {
        return void 0;
      }
      if (this.pendingAvatarAction === AVATAR_ACTIONS.DELETE) {
        return void 0;
      }
      if (this.pendingAvatarPreviewUrl) {
        return this.pendingAvatarPreviewUrl;
      }
      return this.avatarUrl;
    },
    canManageTeam() {
      return (this.circle.isOwner || this.circle.isAdmin) && !this.circle.isPersonal;
    },
    teamHasCollective() {
      return this.resourcesForProvider("collectives").length > 0;
    },
    maxMembers() {
      const avatarWidth = parseInt(window.getComputedStyle(document.body).getPropertyValue("--default-clickable-area")) + 12;
      const maxMembers = Math.floor(this.width / avatarWidth);
      return this.members.length > maxMembers ? maxMembers - 1 : maxMembers;
    },
    memberLimit() {
      return Math.min(this.members.length, this.maxMembers);
    },
    membersLimited() {
      return this.members.slice(0, this.memberLimit);
    },
    hasExtraMembers() {
      return this.members.length > this.maxMembers;
    },
    groupedResources() {
      return this.resources.reduce((acc, resource) => {
        const providerId = resource.provider.id;
        if (!acc[providerId]) {
          acc[providerId] = {
            name: resource.provider.name,
            resources: []
          };
        }
        acc[providerId].resources.push(resource);
        return acc;
      }, {});
    },
    resourcesForProvider() {
      return (providerId) => {
        return this.resources?.filter((res) => res.provider.id === providerId) ?? [];
      };
    },
    resourceTypes() {
      const enabledApps = window.OC?.appswebroots || {};
      return [
        {
          id: "folder",
          label: t("circles", "Folder"),
          inputLabel: t("circles", "New folder"),
          placeholder: t("circles", "Folder name"),
          helperText: t("circles", "This will create a regular folder shared with the team. To create a Team Folder, please contact your {productName} administrator", { productName: OC.theme.name }),
          icon: "FolderOutlineIcon",
          apiPath: "files",
          enabled: enabledApps.files !== void 0
        },
        {
          id: "talk",
          label: t("circles", "Talk conversation"),
          inputLabel: t("circles", "New Talk conversation"),
          placeholder: t("circles", "Conversation name"),
          icon: "MessageIcon",
          apiPath: "spreed",
          enabled: enabledApps.spreed !== void 0
        },
        {
          id: "collective",
          label: t("circles", "Collective"),
          inputLabel: null,
          placeholder: null,
          icon: "BookOpenPageVariantIcon",
          apiPath: "collectives",
          enabled: enabledApps.collectives !== void 0 && !this.teamHasCollective,
          noInput: true
        },
        {
          id: "calendar",
          label: t("circles", "Calendar"),
          inputLabel: t("circles", "New calendar"),
          placeholder: t("circles", "Calendar name"),
          icon: "CalendarIcon",
          apiPath: "calendar",
          enabled: enabledApps.calendar !== void 0
        }
      ].filter((resource) => resource.enabled);
    }
  },
  watch: {
    "circle.id": {
      handler() {
        this.isEditing = false;
        this.fetchTeamResources();
        if (this.avatarSupported) {
          this.cancelSetAvatar();
          this.clearPendingAvatar();
          this.loadAvatarUrl();
        }
      },
      immediate: true
    }
  },
  beforeUnmount() {
    if (this.avatarUrl !== void 0) {
      URL.revokeObjectURL(this.avatarUrl);
    }
    if (this.pendingAvatarPreviewUrl !== void 0) {
      URL.revokeObjectURL(this.pendingAvatarPreviewUrl);
    }
  },
  methods: {
    addMembers() {
      this.$refs.memberList.onShowPicker(this.circle.id);
    },
    setActivePopover(resourceId, isOpen) {
      this.activePopover = isOpen ? resourceId : null;
    },
    updateResourceInput(resourceId, value) {
      this.resourceInputs[resourceId] = value;
    },
    async handleResourceCreation({ resourceType, name }) {
      try {
        let resourceId;
        switch (resourceType.id) {
          case "folder": {
            const folderPath = `/remote.php/dav/files/${getCurrentUser().uid}/${name}`;
            await cancelableClient.request({
              method: "MKCOL",
              url: folderPath,
              headers: {
                "Content-Type": "application/xml"
              }
            });
            resourceId = name;
            break;
          }
          case "talk": {
            const talkUrl = generateOcsUrl("/apps/spreed/api/v4/room");
            const talkResponse = await cancelableClient.post(talkUrl, {
              roomName: name,
              roomType: 2
            });
            resourceId = talkResponse.data.ocs.data.token;
            break;
          }
          case "collective": {
            const collectiveName = this.circle.sanitizedName || this.circle.name || this.circle.displayName;
            if (!collectiveName) {
              throw new Error("Cannot create collective: team has no valid name");
            }
            const collectiveUrl = generateOcsUrl("/apps/collectives/api/v1.0/collectives");
            const collectiveResponse = await cancelableClient.post(collectiveUrl, {
              name: collectiveName
            });
            resourceId = collectiveResponse.data.ocs.data.collective.id;
            if (!resourceId) {
              throw new Error("Failed to get collective ID from creation response");
            }
            break;
          }
          case "calendar": {
            const DavClient = (await __vitePreload(async () => {
              const { default: __vite_default__ } = await import("./index-CdPnktSB.chunk.mjs");
              return { default: __vite_default__ };
            }, true ? __vite__mapDeps([0,1,2]) : void 0, import.meta.url)).default;
            const client = new DavClient({
              rootUrl: generateRemoteUrl("dav"),
              defaultHeaders: {
                "X-NC-CalDAV-Webcal-Caching": "On"
              }
            });
            await client.connect({ enableCalDAV: true });
            const calendarHome = client.calendarHomes[0];
            try {
              const davCalendar = await calendarHome.createCalendarCollection(name, "#0082c9", ["VEVENT", "VTODO"], 0);
              this.createdCalendar = davCalendar;
              resourceId = davCalendar.url;
            } catch (calendarError) {
              console.error("Calendar creation failed for name:", name);
              throw new Error(`CALENDAR_EXISTS:${name}`);
            }
            break;
          }
          default: {
            showError(t("circles", "Unknown resource type"));
            return;
          }
        }
        await this.shareResourceWithTeam(resourceType, resourceId);
        this.resourceInputs[resourceType.id] = "";
        this.activePopover = null;
        if (resourceType.id === "calendar") {
          this.createdCalendar = null;
          showSuccess(t("circles", 'Team calendar "{resourceName}" created and shared with team', {
            resourceName: name
          }));
          this.createdCalendarName = name;
          this.showCalendarSuccessNotification = true;
          setTimeout(() => {
            this.showCalendarSuccessNotification = false;
          }, 1e4);
        } else {
          showSuccess(t("circles", '{resourceType} "{resourceName}" created and shared with team', {
            resourceType: resourceType.label,
            resourceName: name
          }));
          this.fetchTeamResources();
        }
      } catch (error) {
        console.error("Failed to create resource:", error);
        if (error.message && error.message.startsWith("CALENDAR_EXISTS:")) {
          const calendarName = error.message.replace("CALENDAR_EXISTS:", "");
          showError(t("circles", 'A calendar named "{name}" already exists. Please choose a different name.', {
            name: calendarName
          }));
        } else {
          showError(t("circles", "Failed to create {resourceType}: {error}", {
            resourceType: resourceType.label.toLowerCase(),
            error: error.response?.data?.ocs?.data?.message || error.response?.data?.message || error.message
          }));
        }
      }
    },
    async shareResourceWithTeam(resourceType, resourceId) {
      switch (resourceType.id) {
        case "folder": {
          const shareUrl = generateOcsUrl("/apps/files_sharing/api/v1/shares");
          await cancelableClient.post(shareUrl, {
            path: `/${resourceId}`,
            shareType: 7,
            shareWith: this.circle.id,
            permissions: 31
          });
          break;
        }
        case "talk": {
          const participantUrl = generateOcsUrl(`/apps/spreed/api/v4/room/${resourceId}/participants`);
          await cancelableClient.post(participantUrl, {
            source: "circles",
            newParticipant: this.circle.id
          });
          break;
        }
        case "collective": {
          break;
        }
        case "calendar": {
          if (!this.createdCalendar || !this.createdCalendar.share) {
            throw new Error("No calendar object available for sharing");
          }
          const circleUri = `principal:principals/circles/${this.circle.id}`;
          await this.createdCalendar.share(circleUri);
          break;
        }
      }
    },
    openLocalFilePicker() {
      this.$refs.avatarInput.value = null;
      this.$refs.avatarInput.click();
    },
    async onAvatarInputChange(e) {
      try {
        const file = e.target.files[0];
        if (!VALID_MIME_TYPES.includes(file.type)) {
          showError(t("circles", "Please select a valid png or jpg file"));
          return;
        }
        const reader = new FileReader();
        reader.onload = async (e2) => {
          this.showCropper = true;
          await this.$nextTick();
          this.$refs.cropper.replace(e2.target.result);
        };
        reader.readAsDataURL(file);
      } catch (error) {
        console.error("Error picking avatar file", error);
        showError(t("circles", "Error picking team picture"));
      }
    },
    async openFilePicker() {
      try {
        const path2 = await picker.pick();
        if (!path2) {
          return;
        }
        const fileResponse = await cancelableClient.get(
          generateRemoteUrl(`dav/files/${getCurrentUser().uid}`) + encodePath(path2),
          { responseType: "blob" }
        );
        const reader = new FileReader();
        reader.onload = async (e) => {
          this.showCropper = true;
          await this.$nextTick();
          this.$refs.cropper.replace(e.target.result);
        };
        reader.readAsDataURL(fileResponse.data);
      } catch (error) {
        if (error instanceof FilePickerClosed) {
          return;
        }
        console.error("Error picking avatar file", error);
        showError(t("circles", "Error picking team picture"));
      }
    },
    setAvatar() {
      this.showCropper = false;
      this.$refs.cropper.getCroppedCanvas({
        minWidth: 16,
        minHeight: 16,
        maxWidth: 512,
        maxHeight: 512
      }).toBlob(async (blob) => {
        if (blob === null) {
          showError(t("circles", "Error cropping avatar picture"));
          this.cancelSetAvatar();
          return;
        }
        if (this.pendingAvatarPreviewUrl) {
          URL.revokeObjectURL(this.pendingAvatarPreviewUrl);
        }
        this.pendingAvatarBlob = blob;
        this.pendingAvatarAction = AVATAR_ACTIONS.SET;
        this.pendingAvatarPreviewUrl = URL.createObjectURL(blob);
      });
    },
    cancelSetAvatar() {
      this.showCropper = false;
      if (this.$refs.avatarInput) {
        this.$refs.avatarInput.value = null;
      }
    },
    removeAvatar() {
      if (this.pendingAvatarPreviewUrl) {
        URL.revokeObjectURL(this.pendingAvatarPreviewUrl);
      }
      this.pendingAvatarBlob = null;
      this.pendingAvatarAction = AVATAR_ACTIONS.DELETE;
      this.pendingAvatarPreviewUrl = void 0;
    },
    clearPendingAvatar() {
      if (this.pendingAvatarPreviewUrl) {
        URL.revokeObjectURL(this.pendingAvatarPreviewUrl);
      }
      this.pendingAvatarBlob = null;
      this.pendingAvatarAction = null;
      this.pendingAvatarPreviewUrl = void 0;
    },
    onLeave() {
      this.isSettingsPopoverShown = false;
      this.confirmLeaveCircle();
    },
    onDelete() {
      this.isSettingsPopoverShown = false;
      this.confirmDeleteCircle();
    },
    onCloseSettingsPopover() {
      this.isSettingsPopoverShown = false;
    },
    startEditing() {
      this.originalDisplayName = this.circle.displayName;
      this.originalDescription = this.circle.description;
      this.isEditing = true;
    },
    cancelEditing() {
      this.circle.displayName = this.originalDisplayName;
      this.circle.description = this.originalDescription;
      this.cancelSetAvatar();
      this.clearPendingAvatar();
      this.isEditing = false;
    },
    async fetchTeamResources() {
      const response = await cancelableClient.get(generateOcsUrl(`/teams/${this.circle.id}/resources`));
      this.resources = response.data.ocs.data.resources;
      console.debug("Team resources", this.resources);
    },
    async loadAvatarUrl() {
      if (!this.avatarSupported) {
        return;
      }
      try {
        const response = await cancelableClient.get(
          generateOcsUrl(`/apps/circles/circles/${this.circle.id}/avatar`),
          { responseType: "blob" }
        );
        if (this.avatarUrl !== void 0) {
          URL.revokeObjectURL(this.avatarUrl);
        }
        this.avatarUrl = URL.createObjectURL(response.data);
      } catch {
        if (this.avatarUrl !== void 0) {
          URL.revokeObjectURL(this.avatarUrl);
          this.avatarUrl = void 0;
        }
      }
    },
    /**
     * Autocomplete @mentions on the description
     *
     * @param {string} search the search term
     * @param {Function} callback callback to be called with results array
     */
    onAutocomplete(search, callback) {
      callback([]);
    },
    async saveChanges() {
      const errors = [];
      this.cancelSetAvatar();
      if (this.circle.displayName !== this.originalDisplayName) {
        this.loadingName = true;
        try {
          await editCircle(this.circle.id, CircleEdit.Name, this.circle.displayName);
          this.originalDisplayName = this.circle.displayName;
        } catch (error) {
          console.error("Unable to edit name", this.circle.displayName, error);
          errors.push("name");
          this.circle.displayName = this.originalDisplayName;
        } finally {
          this.loadingName = false;
        }
      }
      if (this.circle.description !== this.originalDescription) {
        this.loadingDescription = true;
        try {
          await editCircle(this.circle.id, CircleEdit.Description, this.circle.description);
          this.originalDescription = this.circle.description;
        } catch (error) {
          console.error("Unable to edit team description", this.circle.description, error);
          errors.push("description");
          this.circle.description = this.originalDescription;
        } finally {
          this.loadingDescription = false;
        }
      }
      if (this.avatarSupported && this.pendingAvatarAction === AVATAR_ACTIONS.SET && this.pendingAvatarBlob) {
        this.loadingAvatar = true;
        const formData = new FormData();
        formData.append("file", this.pendingAvatarBlob);
        try {
          await cancelableClient.post(generateOcsUrl(`/apps/circles/circles/${this.circle.id}/avatar`), formData);
          this.clearPendingAvatar();
          await this.loadAvatarUrl();
        } catch {
          console.error("Unable to save avatar picture");
          errors.push("avatar");
        } finally {
          this.loadingAvatar = false;
        }
      }
      if (this.avatarSupported && this.pendingAvatarAction === AVATAR_ACTIONS.DELETE) {
        this.loadingAvatar = true;
        try {
          await cancelableClient.delete(generateOcsUrl(`/apps/circles/circles/${this.circle.id}/avatar`));
          this.clearPendingAvatar();
          await this.loadAvatarUrl();
        } catch {
          console.error("Unable to remove avatar");
          errors.push("avatar");
        } finally {
          this.loadingAvatar = false;
        }
      }
      if (errors.length > 0) {
        const errorFields = errors.join(" and ");
        showError(t("circles", "An error happened while saving {fields}", { fields: errorFields }));
        return;
      }
      this.isEditing = false;
    },
    openCalendarApp() {
      window.open(generateUrl("/apps/calendar/"), "_blank");
      this.showCalendarSuccessNotification = false;
    }
  }
};
const _hoisted_1$1 = { class: "circle-details-container" };
const _hoisted_2$1 = { class: "circle-details__header-wrapper" };
const _hoisted_3 = { class: "circle-details-grid__avatar" };
const _hoisted_4 = { class: "circle-details__header" };
const _hoisted_5 = { class: "circle-name-wrapper" };
const _hoisted_6 = {
  key: 0,
  class: "circle-name"
};
const _hoisted_7 = ["title"];
const _hoisted_8 = {
  key: 0,
  class: "subtitle"
};
const _hoisted_9 = {
  key: 1,
  class: "circle-description-wrapper"
};
const _hoisted_10 = {
  key: 0,
  class: "circle-description"
};
const _hoisted_11 = {
  key: 2,
  class: "circle-avatar-buttons-wrapper"
};
const _hoisted_12 = ["accept"];
const _hoisted_13 = { class: "actions" };
const _hoisted_14 = {
  key: 3,
  class: "resource-shortcuts"
};
const _hoisted_15 = { class: "resource-shortcuts__title" };
const _hoisted_16 = { class: "resource-shortcuts__buttons" };
const _hoisted_17 = { class: "circle-details__main-content" };
const _hoisted_18 = { key: 1 };
const _hoisted_19 = { class: "section-header" };
const _hoisted_20 = { class: "item-list" };
const _hoisted_21 = ["innerHTML"];
const _hoisted_22 = ["src", "alt"];
const _hoisted_23 = { class: "circle-details-section" };
const _hoisted_24 = { class: "member-section-layout" };
const _hoisted_25 = { class: "section-header" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcLoadingIcon = resolveComponent("NcLoadingIcon");
  const _component_Avatar = resolveComponent("Avatar");
  const _component_NcTextField = resolveComponent("NcTextField");
  const _component_UserBubble = resolveComponent("UserBubble");
  const _component_NcTextArea = resolveComponent("NcTextArea");
  const _component_TrayArrowUpIcon = resolveComponent("TrayArrowUpIcon");
  const _component_NcButton = resolveComponent("NcButton");
  const _component_FolderIcon = resolveComponent("FolderIcon");
  const _component_TrashCanOutlineIcon = resolveComponent("TrashCanOutlineIcon");
  const _component_PencilIcon = resolveComponent("PencilIcon");
  const _component_CopyIcon = resolveComponent("CopyIcon");
  const _component_CogIcon = resolveComponent("CogIcon");
  const _component_CircleSettings = resolveComponent("CircleSettings");
  const _component_NcPopover = resolveComponent("NcPopover");
  const _component_LoginIcon = resolveComponent("LoginIcon");
  const _component_LogoutIcon = resolveComponent("LogoutIcon");
  const _component_CheckIcon = resolveComponent("CheckIcon");
  const _component_TeamResourceButton = resolveComponent("TeamResourceButton");
  const _component_NcEmptyContent = resolveComponent("NcEmptyContent");
  const _component_IconAccountGroup = resolveComponent("IconAccountGroup");
  const _component_ContentHeading = resolveComponent("ContentHeading");
  const _component_FileDocumentOutline = resolveComponent("FileDocumentOutline");
  const _component_ListItem = resolveComponent("ListItem");
  const _component_AccountPlusIcon = resolveComponent("AccountPlusIcon");
  const _component_MemberList = resolveComponent("MemberList");
  const _component_VueCropper = resolveComponent("VueCropper");
  const _component_NcDialog = resolveComponent("NcDialog");
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("div", {
      class: normalizeClass(["circle-details-grid", { "is-editing": $data.isEditing }])
    }, [
      createBaseVNode("div", _hoisted_2$1, [
        createBaseVNode("div", _hoisted_3, [
          $data.loadingAvatar ? (openBlock(), createBlock(_component_NcLoadingIcon, {
            key: 0,
            size: 75
          })) : (openBlock(), createBlock(_component_Avatar, {
            key: 1,
            "disable-tooltip": true,
            "display-name": _ctx.circle.displayName,
            "is-no-user": true,
            url: $options.displayAvatarUrl,
            size: 75
          }, null, 8, ["display-name", "url"]))
        ]),
        createBaseVNode("div", _hoisted_4, [
          createBaseVNode("div", _hoisted_5, [
            !$data.isEditing ? (openBlock(), createElementBlock("h2", _hoisted_6, [
              createBaseVNode("span", {
                title: _ctx.circle.displayName
              }, toDisplayString(_ctx.circle.displayName), 9, _hoisted_7),
              $data.loadingName ? (openBlock(), createBlock(_component_NcLoadingIcon, {
                key: 0,
                size: 24
              })) : createCommentVNode("", true)
            ])) : (openBlock(), createBlock(_component_NcTextField, {
              key: 1,
              modelValue: _ctx.circle.displayName,
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => _ctx.circle.displayName = $event),
              placeholder: _ctx.t("circles", "Team name"),
              label: "Team name"
            }, null, 8, ["modelValue", "placeholder"]))
          ]),
          !$data.isEditing ? (openBlock(), createElementBlock("div", _hoisted_8, [
            createBaseVNode("span", null, toDisplayString(_ctx.t("circles", "Team owner")), 1),
            _cache[6] || (_cache[6] = createTextVNode()),
            createVNode(_component_UserBubble, {
              user: _ctx.circle.owner.userId,
              "display-name": _ctx.circle.isOwner ? "you" : _ctx.circle.owner.displayName
            }, null, 8, ["user", "display-name"])
          ])) : createCommentVNode("", true),
          $options.showDescription ? (openBlock(), createElementBlock("div", _hoisted_9, [
            !$data.isEditing ? (openBlock(), createElementBlock("div", _hoisted_10, toDisplayString(_ctx.circle.description), 1)) : (openBlock(), createBlock(_component_NcTextArea, {
              key: 1,
              modelValue: _ctx.circle.description,
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => _ctx.circle.description = $event),
              placeholder: $options.descriptionPlaceholder,
              label: "Description",
              maxlength: 1024
            }, null, 8, ["modelValue", "placeholder"]))
          ])) : createCommentVNode("", true),
          $setup.avatarSupported ? (openBlock(), createElementBlock("div", _hoisted_11, [
            $data.isEditing ? (openBlock(), createBlock(_component_NcButton, {
              key: 0,
              disabled: $data.loadingAvatar,
              onClick: $options.openLocalFilePicker
            }, {
              icon: withCtx(() => [
                createVNode(_component_TrayArrowUpIcon, { size: 20 })
              ]),
              default: withCtx(() => [
                createTextVNode(" " + toDisplayString(_ctx.t("circles", "Upload team picture")), 1)
              ]),
              _: 1
            }, 8, ["disabled", "onClick"])) : createCommentVNode("", true),
            $data.isEditing ? (openBlock(), createBlock(_component_NcButton, {
              key: 1,
              disabled: $data.loadingAvatar,
              onClick: $options.openFilePicker
            }, {
              icon: withCtx(() => [
                createVNode(_component_FolderIcon, { size: 20 })
              ]),
              default: withCtx(() => [
                createTextVNode(" " + toDisplayString(_ctx.t("circles", "Choose from Nextcloud Files")), 1)
              ]),
              _: 1
            }, 8, ["disabled", "onClick"])) : createCommentVNode("", true),
            $data.isEditing && $options.displayAvatarUrl !== void 0 ? (openBlock(), createBlock(_component_NcButton, {
              key: 2,
              disabled: $data.loadingAvatar,
              onClick: $options.removeAvatar
            }, {
              icon: withCtx(() => [
                createVNode(_component_TrashCanOutlineIcon, { size: 20 })
              ]),
              default: withCtx(() => [
                createTextVNode(" " + toDisplayString(_ctx.t("circles", "Delete picture")), 1)
              ]),
              _: 1
            }, 8, ["disabled", "onClick"])) : createCommentVNode("", true),
            createBaseVNode("input", {
              ref: "avatarInput",
              type: "file",
              accept: $setup.avatarAccept,
              class: "hidden-visually",
              onChange: _cache[2] || (_cache[2] = (...args) => $options.onAvatarInputChange && $options.onAvatarInputChange(...args))
            }, null, 40, _hoisted_12)
          ])) : createCommentVNode("", true),
          createBaseVNode("div", _hoisted_13, [
            !$data.isEditing ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
              $options.canManageTeam ? (openBlock(), createBlock(_component_NcButton, {
                key: 0,
                variant: "primary",
                onClick: $options.startEditing
              }, {
                icon: withCtx(() => [
                  createVNode(_component_PencilIcon, { size: 20 })
                ]),
                default: withCtx(() => [
                  createTextVNode(" " + toDisplayString(_ctx.t("circles", "Edit")), 1)
                ]),
                _: 1
              }, 8, ["onClick"])) : createCommentVNode("", true),
              createVNode(_component_NcButton, {
                variant: "secondary",
                href: $options.circleUrl,
                onClick: _cache[3] || (_cache[3] = withModifiers(($event) => _ctx.copyToClipboard($options.circleUrl), ["stop", "prevent"]))
              }, {
                icon: withCtx(() => [
                  createVNode(_component_CopyIcon, { size: 20 })
                ]),
                default: withCtx(() => [
                  createTextVNode(" " + toDisplayString(_ctx.t("circles", "Copy link")), 1)
                ]),
                _: 1
              }, 8, ["href"]),
              $options.canManageTeam ? (openBlock(), createBlock(_component_NcPopover, {
                key: 1,
                shown: $data.isSettingsPopoverShown,
                "popup-role": "dialog",
                "onUpdate:shown": _cache[5] || (_cache[5] = ($event) => $data.isSettingsPopoverShown = $event)
              }, {
                trigger: withCtx(() => [
                  createVNode(_component_NcButton, {
                    onClick: _cache[4] || (_cache[4] = ($event) => $data.isSettingsPopoverShown = true)
                  }, {
                    icon: withCtx(() => [
                      createVNode(_component_CogIcon, { size: 20 })
                    ]),
                    _: 1
                  })
                ]),
                default: withCtx(() => [
                  createVNode(_component_CircleSettings, {
                    circle: _ctx.circle,
                    onLeave: $options.onLeave,
                    onDelete: $options.onDelete,
                    onCloseSettingsPopover: $options.onCloseSettingsPopover
                  }, null, 8, ["circle", "onLeave", "onDelete", "onCloseSettingsPopover"])
                ]),
                _: 1
              }, 8, ["shown"])) : createCommentVNode("", true)
            ], 64)) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
              createVNode(_component_NcButton, {
                variant: "secondary",
                onClick: $options.cancelEditing
              }, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString(_ctx.t("circles", "Cancel")), 1)
                ]),
                _: 1
              }, 8, ["onClick"]),
              createVNode(_component_NcButton, {
                variant: "primary",
                onClick: $options.saveChanges
              }, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString(_ctx.t("circles", "Save")), 1)
                ]),
                _: 1
              }, 8, ["onClick"])
            ], 64)),
            !_ctx.circle.isPendingMember && !_ctx.circle.isMember && _ctx.circle.canJoin ? (openBlock(), createBlock(_component_NcButton, {
              key: 2,
              disabled: $data.loadingJoin,
              class: "primary",
              onClick: _ctx.joinCircle
            }, {
              icon: withCtx(() => [
                createVNode(_component_LoginIcon, { size: 16 })
              ]),
              default: withCtx(() => [
                createTextVNode(" " + toDisplayString(_ctx.t("circles", "Request to join")), 1)
              ]),
              _: 1
            }, 8, ["disabled", "onClick"])) : createCommentVNode("", true),
            _ctx.circle.isMember && _ctx.circle.canLeave ? (openBlock(), createBlock(_component_NcButton, {
              key: 3,
              disabled: $data.loadingLeave,
              variant: "warning",
              onClick: _ctx.confirmLeaveCircle
            }, {
              icon: withCtx(() => [
                createVNode(_component_LogoutIcon, { size: 16 })
              ]),
              default: withCtx(() => [
                createTextVNode(" " + toDisplayString(_ctx.t("circles", "Leave team")), 1)
              ]),
              _: 1
            }, 8, ["disabled", "onClick"])) : createCommentVNode("", true)
          ]),
          _ctx.circle.isMember ? (openBlock(), createElementBlock("div", _hoisted_14, [
            createBaseVNode("h3", _hoisted_15, toDisplayString(_ctx.t("circles", "Create")), 1),
            createBaseVNode("div", _hoisted_16, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($options.resourceTypes, (resourceType) => {
                return openBlock(), createElementBlock(Fragment, {
                  key: resourceType.id
                }, [
                  resourceType.id === "calendar" && $data.showCalendarSuccessNotification ? (openBlock(), createBlock(_component_NcButton, {
                    key: 0,
                    variant: "success",
                    onClick: $options.openCalendarApp
                  }, {
                    icon: withCtx(() => [
                      createVNode(_component_CheckIcon, { size: 20 })
                    ]),
                    default: withCtx(() => [
                      createTextVNode(" " + toDisplayString(_ctx.t("circles", "Show in Calendar")), 1)
                    ]),
                    _: 1
                  }, 8, ["onClick"])) : (openBlock(), createBlock(_component_TeamResourceButton, {
                    key: 1,
                    "resource-type": resourceType,
                    value: $data.resourceInputs[resourceType.id] || "",
                    "is-open": $data.activePopover === resourceType.id,
                    "helper-text": resourceType.helperText,
                    "onUpdate:value": ($event) => $options.updateResourceInput(resourceType.id, $event),
                    "onUpdate:isOpen": ($event) => $options.setActivePopover(resourceType.id, $event),
                    onCreate: $options.handleResourceCreation
                  }, {
                    icon: withCtx(() => [
                      (openBlock(), createBlock(resolveDynamicComponent(resourceType.icon), { size: 20 }))
                    ]),
                    _: 2
                  }, 1032, ["resource-type", "value", "is-open", "helper-text", "onUpdate:value", "onUpdate:isOpen", "onCreate"]))
                ], 64);
              }), 128))
            ])
          ])) : createCommentVNode("", true)
        ])
      ]),
      createBaseVNode("div", _hoisted_17, [
        !_ctx.circle.isMember ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
          _ctx.circle.isPendingMember ? (openBlock(), createBlock(_component_NcEmptyContent, {
            key: 0,
            name: _ctx.t("circles", "Your request to join this team is pending approval")
          }, {
            icon: withCtx(() => [
              createVNode(_component_NcLoadingIcon, { size: 20 })
            ]),
            _: 1
          }, 8, ["name"])) : (openBlock(), createBlock(_component_NcEmptyContent, {
            key: 1,
            name: _ctx.t("circles", "You are not a member of {circle}", { circle: _ctx.circle.displayName })
          }, {
            icon: withCtx(() => [
              createVNode(_component_IconAccountGroup, { size: 20 })
            ]),
            _: 1
          }, 8, ["name"]))
        ], 64)) : (openBlock(), createElementBlock("section", _hoisted_18, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($options.groupedResources, (group, providerId) => {
            return openBlock(), createElementBlock("div", {
              key: providerId,
              class: "circle-details-section"
            }, [
              createBaseVNode("div", _hoisted_19, [
                createVNode(_component_ContentHeading, null, {
                  default: withCtx(() => [
                    createTextVNode(toDisplayString(group.name), 1)
                  ]),
                  _: 2
                }, 1024)
              ]),
              createBaseVNode("ul", _hoisted_20, [
                (openBlock(true), createElementBlock(Fragment, null, renderList(group.resources, (resource) => {
                  return openBlock(), createBlock(_component_ListItem, {
                    key: resource.id,
                    href: resource.url,
                    name: resource.label
                  }, {
                    icon: withCtx(() => [
                      resource.iconSvg ? (openBlock(), createElementBlock("div", {
                        key: 0,
                        class: "resource__icon",
                        innerHTML: resource.iconSvg
                      }, null, 8, _hoisted_21)) : resource.iconURL ? (openBlock(), createElementBlock("img", {
                        key: 1,
                        src: resource.iconURL,
                        alt: resource.label,
                        class: "resource__icon"
                      }, null, 8, _hoisted_22)) : (openBlock(), createBlock(_component_FileDocumentOutline, {
                        key: 2,
                        size: 20
                      }))
                    ]),
                    _: 2
                  }, 1032, ["href", "name"]);
                }), 128))
              ])
            ]);
          }), 128)),
          createBaseVNode("div", _hoisted_23, [
            createBaseVNode("div", _hoisted_24, [
              createBaseVNode("div", _hoisted_25, [
                createVNode(_component_ContentHeading, null, {
                  default: withCtx(() => [
                    createTextVNode(toDisplayString(_ctx.t("circles", "Members")), 1)
                  ]),
                  _: 1
                }),
                _ctx.circle.canManageMembers ? (openBlock(), createBlock(_component_NcButton, {
                  key: 0,
                  variant: "tertiary",
                  onClick: $options.addMembers
                }, {
                  icon: withCtx(() => [
                    createVNode(_component_AccountPlusIcon, { size: 20 })
                  ]),
                  default: withCtx(() => [
                    createTextVNode(" " + toDisplayString(_ctx.t("circles", "Add")), 1)
                  ]),
                  _: 1
                }, 8, ["onClick"])) : createCommentVNode("", true)
              ]),
              createVNode(_component_MemberList, {
                ref: "memberList",
                list: $options.members
              }, null, 8, ["list"])
            ])
          ])
        ]))
      ])
    ], 2),
    $data.showCropper ? (openBlock(), createBlock(_component_NcDialog, {
      key: 0,
      class: "circle-avatar-cropper-dialog",
      name: _ctx.t("circles", "Edit team picture"),
      open: $data.showCropper,
      size: "normal",
      onClosing: $options.cancelSetAvatar
    }, {
      actions: withCtx(() => [
        createVNode(_component_NcButton, { onClick: $options.cancelSetAvatar }, {
          default: withCtx(() => [
            createTextVNode(toDisplayString(_ctx.t("circles", "Cancel")), 1)
          ]),
          _: 1
        }, 8, ["onClick"]),
        createVNode(_component_NcButton, {
          variant: "primary",
          onClick: $options.setAvatar
        }, {
          default: withCtx(() => [
            createTextVNode(toDisplayString(_ctx.t("circles", "Apply")), 1)
          ]),
          _: 1
        }, 8, ["onClick"])
      ]),
      default: withCtx(() => [
        createVNode(_component_VueCropper, mergeProps({
          ref: "cropper",
          class: "circle-avatar-cropper"
        }, $data.cropperOptions), null, 16)
      ]),
      _: 1
    }, 8, ["name", "open", "onClosing"])) : createCommentVNode("", true)
  ]);
}
const CircleDetails = /* @__PURE__ */ _export_sfc$1(_sfc_main$1, [["render", _sfc_render], ["__scopeId", "data-v-74b01306"]]);
const _hoisted_1 = { class: "team-page" };
const _hoisted_2 = {
  key: 0,
  class: "team-page__loading"
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "TeamPage",
  props: {
    teamId: {}
  },
  setup(__props) {
    const props = __props;
    const store2 = useStore();
    const loading = ref(true);
    const circle = computed(() => store2.getters.getCircle(props.teamId));
    async function loadCircle() {
      loading.value = true;
      try {
        await store2.dispatch("getCircle", props.teamId);
        await store2.dispatch("getCircleMembers", { circleId: props.teamId });
      } catch (error) {
        logger$2.error("Could not load the team", { error });
        showError(translate("circles", "Could not load the team"));
      } finally {
        loading.value = false;
      }
    }
    watch(() => props.teamId, loadCircle, { immediate: true });
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", _hoisted_1, [
        loading.value && !circle.value ? (openBlock(), createElementBlock("div", _hoisted_2, [
          createVNode(unref(NcLoadingIcon), { size: 44 })
        ])) : !circle.value ? (openBlock(), createBlock(unref(NcEmptyContent), {
          key: 1,
          class: "team-page__missing",
          name: unref(translate)("circles", "Team not found"),
          description: unref(translate)("circles", "This team may have been removed.")
        }, {
          icon: withCtx(() => [
            createVNode(unref(NcIconSvgWrapper), { path: unref(mdiAlertCircleOutline$1) }, null, 8, ["path"])
          ]),
          _: 1
        }, 8, ["name", "description"])) : (openBlock(), createBlock(CircleDetails, {
          key: 2,
          circle: circle.value
        }, null, 8, ["circle"]))
      ]);
    };
  }
});
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const routes = [
  {
    name: "home",
    path: "/",
    component: HomeView
  },
  {
    name: "team",
    path: "/team/:teamId",
    component: _sfc_main,
    props: true
  }
];
const router = createRouter({
  // HTML5 history mode for clean, hash-free URLs. The server registers a
  // catch-all route (Page#indexPath) so deep-link reloads still serve the shell.
  history: createWebHistory(generateUrl("/apps/circles/teams")),
  routes
});
const LegacyGlobalMixin = {
  methods: {
    t: translate,
    n: translatePlural
  },
  computed: {
    appName: () => appName,
    appVersion: () => appVersion,
    logger: () => logger,
    OC: () => window.OC,
    OCA: () => window.OCA
  }
};
const state = {
  /** @type {Object<string>} Circle */
  circles: {}
};
const mutations = {
  /**
   * Add a circle into state
   *
   * @param {object} state the store data
   * @param {Circle} circle the circle to add
   */
  addCircle(state2, circle) {
    if (circle.constructor.name !== Circle.name) {
      throw new Error("circle must be a Circle type");
    }
    state2.circles[circle.id] = circle;
  },
  /**
   * Delete circle
   *
   * @param {object} state the store data
   * @param {Circle} circle the circle to delete
   */
  deleteCircle(state2, circle) {
    if (!(circle.id in state2.circles)) {
      logger.warn("Skipping deletion of unknown circle", { circle });
    }
    delete state2.circles[circle.id];
  },
  /**
   * Reset circle members
   *
   * @param {object} state the store data
   * @param {string} circleId the circle id
   */
  resetCircleMembers(state2, circleId) {
    state2.circles[circleId].members = [];
  },
  /**
   * Append a list of members to a circle
   * and remove duplicates
   *
   * @param {object} state the store data
   * @param {Members[]} members array of members to append
   */
  appendMembersToCircle(state2, members) {
    members.forEach((member) => member.circle.addMember(member));
  },
  /**
   * Add a member to a circle and overwrite if duplicate uid
   *
   * @param {object} state the store data
   * @param {object} data destructuring object
   * @param {string} data.circleId the circle to add the members to
   * @param {Member} data.member array of contacts to append
   */
  addMemberToCircle(state2, { circleId, member }) {
    const circle = state2.circles[circleId];
    circle.addMember(member);
  },
  /**
   * Delete a contact in a specified circle
   *
   * @param {object} state the store data
   * @param {Member} member the member to add
   */
  deleteMemberFromCircle(state2, member) {
    member.delete();
  },
  setCircleSettings(state2, { circleId, settings }) {
    state2.circles[circleId]._data.settings = settings;
  },
  /**
   * Update circle population count
   *
   * @param {object} state the store data
   * @param {object} data destructuring object
   * @param {string} data.circleId the circle to update the population
   * @param {number} data.populationInherited the inherited population count
   */
  updateCirclePopulationCount(state2, { circleId, populationInherited }) {
    state2.circles[circleId]._data.populationInherited = populationInherited;
  }
};
const getters = {
  getCircles: (state2) => Object.values(state2.circles),
  getCircle: (state2) => (id) => state2.circles[id]
};
const actions = {
  /**
   * Retrieve and commit circles
   *
   * @param {object} context the store mutations
   * @return {object[]} the circles
   */
  async getCircles(context) {
    const circles2 = await getCircles();
    logger.debug(`Retrieved ${circles2.length} circle(s)`, { circles: circles2 });
    let failure = false;
    circles2.forEach((circle) => {
      try {
        const newCircle = new Circle(circle);
        context.commit("addCircle", newCircle);
      } catch (error) {
        failure = true;
        logger.error("This circle failed to be processed", { circle, error });
      }
    });
    if (failure) {
      showError(t("circles", "An error has occurred in team(s). Check the console for more details."));
    }
    return circles2;
  },
  /**
   * Retrieve and commit circles
   *
   * @param {object} context the store mutations
   * @param {string} circleId the circle id
   * @return {object[]} the circles
   */
  async getCircle(context, circleId) {
    const circle = await getCircle(circleId);
    logger.debug("Retrieved 1 circle", { circle });
    try {
      const newCircle = new Circle(circle);
      context.commit("addCircle", newCircle);
    } catch (error) {
      logger.error("This circle failed to be processed", { circle, error });
    }
    return circle;
  },
  /**
   * Retrieve and commit circle members
   *
   * @param {object} context the store mutations
   * @param {object} data destructuring object
   * @param {string} data.circleId the circle id
   * @param {string} data.search the search query
   * @param {string} data.role the role
   * @param {number} data.limit the limit
   */
  async getCircleMembers(context, { circleId, search, role, limit }) {
    const circle = context.getters.getCircle(circleId);
    const members = await getCircleMembers(circleId, search, role, limit);
    logger.debug(`${circleId} have ${members.length} member(s)`, { members });
    context.commit("resetCircleMembers", circle.id);
    context.commit("appendMembersToCircle", members.map((member) => new Member(member, circle)));
  },
  /**
   * Create circle
   *
   * @param {object} context the store mutations Current context
   * @param {object} data destructuring object
   * @param {string} data.circleName the circle name
   * @param {boolean} data.isPersonal the circle is a personal one
   * @param {boolean} data.isLocal the circle is not distributed to the GlobalScale
   * @return {Circle} the new circle
   */
  async createCircle(context, { circleName, isPersonal, isLocal }) {
    try {
      const response = await createCircle(circleName, isPersonal, isLocal);
      const circle = new Circle(response);
      context.commit("addCircle", circle);
      logger.debug("Created circle", { circleName, circle });
      context.dispatch("updateCirclesPopulationCount");
      return circle;
    } catch (error) {
      console.error(error);
      showError(t("circles", "Unable to create team {circleName}", { circleName }));
    }
  },
  /**
   * Delete circle
   *
   * @param {object} context the store mutations Current context
   * @param {Circle} circleId the circle to delete
   */
  async deleteCircle(context, circleId) {
    const circle = context.getters.getCircle(circleId);
    try {
      await deleteCircle(circleId);
      context.commit("deleteCircle", circle);
      logger.debug("Deleted circle", { circleId });
      context.dispatch("updateCirclesPopulationCount");
    } catch (error) {
      console.error(error);
      showError(t("circles", "Unable to delete team {circleId}", { circleId }));
    }
  },
  /**
   * Update population count for all circles
   *
   * @param {object} context the store mutations Current context
   */
  async updateCirclesPopulationCount(context) {
    const circles2 = await getCircles();
    circles2.forEach((circle) => {
      context.commit("updateCirclePopulationCount", {
        circleId: circle.id,
        populationInherited: circle.populationInherited
      });
    });
    logger.debug("Updated population count for all circles");
  },
  /**
   * Add members to a circle
   *
   * @param {object} context the store mutations Current context
   * @param {object} data destructuring object
   * @param {string} data.circleId the circle to manage
   * @param {Array} data.selection the members to add, see addMembers service
   * @return {Member[]}
   */
  async addMembersToCircle(context, { circleId, selection }) {
    const circle = context.getters.getCircle(circleId);
    const results = await addMembers(circleId, selection);
    const members = results.map((member) => new Member(member, circle));
    context.commit("appendMembersToCircle", members);
    logger.debug("Added members to circle", { circle, members });
    context.dispatch("updateCirclesPopulationCount");
    return members;
  },
  /**
   * Delete a member from a circle
   *
   * @param {object} context the store mutations Current context
   * @param {Member} member the member to remove
   * @param {boolean} [leave] leave the circle instead of removing the member
   */
  async deleteMemberFromCircle(context, { member, leave = false }) {
    const circleId = member.circle.id;
    const memberId = member.id;
    if (leave) {
      const circle = await leaveCircle(circleId);
      member.circle.updateData(circle);
      if (!member.circle.isVisible && !member.circle.isMember) {
        await context.commit("deleteCircle", circle);
        logger.debug("Deleted circle", { circleId, memberId });
      }
    } else {
      await deleteMember(circleId, memberId);
    }
    context.commit("deleteMemberFromCircle", member);
    logger.debug("Deleted member", { circleId, memberId });
    context.dispatch("updateCirclesPopulationCount");
  },
  /**
   * Accept a circle member request
   *
   * @param {object} context the store mutations Current context
   * @param {object} data destructuring object
   * @param {string} data.circleId the circle id
   * @param {string} data.memberId the member id
   */
  async acceptCircleMember(context, { circleId, memberId }) {
    const circle = context.getters.getCircle(circleId);
    const result = await acceptMember(circleId, memberId);
    const member = new Member(result, circle);
    await context.commit("addMemberToCircle", { circleId, member });
    context.dispatch("updateCirclesPopulationCount");
  },
  async editCircleSetting(context, { circleId, setting }) {
    const { settings } = await editCircleSetting(circleId, setting);
    await context.commit("setCircleSettings", {
      circleId,
      settings
    });
  }
};
const circles = { state, mutations, getters, actions };
const store = createStore({
  modules: { circles }
});
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
cancelableClient.defaults.headers.common["OCS-APIRequest"] = "true";
logger$2.debug("Mounting Teams app");
const app = createApp(App);
app.use(createPinia());
app.use(store);
app.use(router);
app.mixin(LegacyGlobalMixin);
app.mount("#circles-teams");
//# sourceMappingURL=teams-main.mjs.map
