const appName = "teams";
const appVersion = "35.0.0-dev.0";
import { d as defineComponent, o as openBlock, e as createElementBlock, f as createBaseVNode, am as Fragment, aK as renderList, i as createVNode, u as unref, h as toDisplayString, j as createCommentVNode, L as _export_sfc, T as normalizeStyle, R as NcIconSvgWrapper, y as createBlock, q as useTemplateRef, E as translate, B as generateUrl, v as ref, p as onMounted, c as cancelableClient, H as generateOcsUrl, x as nextTick, J as logger, I as showError, z as withCtx, aj as NcLoadingIcon, $ as NcButton, g as createTextVNode, M as createApp } from "./logger-BmumIVPY.chunk.mjs";
import { l as mdiOpenInNew, n as mdiAlertCircleOutline, c as NcEmptyContent, j as mdiAccountGroupOutline } from "./NcActionRouter-vYFtIOzD-CZ3pYYDb.chunk.mjs";
import { N as NcAvatar } from "./NcAvatar-DX-Nk9Es-BG2npiEg.chunk.mjs";
import "./colors-BDeMBgfq-DzLyYZ86.chunk.mjs";
const _hoisted_1$4 = { class: "team-members" };
const _hoisted_2$3 = { class: "team-members__list" };
const _hoisted_3$3 = {
  key: 0,
  class: "team-members__more"
};
const _sfc_main$4 = /* @__PURE__ */ defineComponent({
  __name: "TeamMembers",
  props: {
    members: {}
  },
  setup(__props) {
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", _hoisted_1$4, [
        createBaseVNode("ul", _hoisted_2$3, [
          (openBlock(true), createElementBlock(Fragment, null, renderList(__props.members.slice(0, 5), (member) => {
            return openBlock(), createElementBlock("li", {
              key: member.userId || member.singleId,
              class: "team-members__item"
            }, [
              createVNode(unref(NcAvatar), {
                user: member.isUser ? member.userId : void 0,
                displayName: member.displayName,
                isNoUser: !member.isUser,
                size: 36,
                class: "team-members__avatar"
              }, null, 8, ["user", "displayName", "isNoUser"]),
              __props.members.length > 5 ? (openBlock(), createElementBlock("span", _hoisted_3$3, " +" + toDisplayString(__props.members.length - 5), 1)) : createCommentVNode("", true)
            ]);
          }), 128))
        ])
      ]);
    };
  }
});
const TeamMembers = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["__scopeId", "data-v-e4d9bc6e"]]);
const _hoisted_1$3 = { class: "team-resources" };
const _hoisted_2$2 = { class: "team-resources__list" };
const _hoisted_3$2 = ["title"];
const _hoisted_4$1 = ["href"];
const _hoisted_5$1 = ["src", "alt"];
const _hoisted_6 = {
  key: 0,
  class: "team-resources__box"
};
const _hoisted_7 = ["href"];
const _hoisted_8 = { class: "team-resources__link-more" };
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "TeamResources",
  props: {
    resources: {},
    teamUrl: {}
  },
  setup(__props) {
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", _hoisted_1$3, [
        createBaseVNode("ul", _hoisted_2$2, [
          (openBlock(true), createElementBlock(Fragment, null, renderList(__props.resources.slice(0, 5), (resource) => {
            return openBlock(), createElementBlock("li", {
              key: resource.id,
              class: "team-resources__box",
              title: resource.name,
              style: normalizeStyle({ "--fallback-icon": `url('${resource.fallbackIcon}')` })
            }, [
              createBaseVNode("a", {
                href: resource.url,
                class: "team-resources__link"
              }, [
                createBaseVNode("img", {
                  src: resource.iconUrl,
                  class: "team-resources__icon",
                  alt: resource.name
                }, null, 8, _hoisted_5$1)
              ], 8, _hoisted_4$1)
            ], 12, _hoisted_3$2);
          }), 128)),
          __props.resources.length > 5 ? (openBlock(), createElementBlock("li", _hoisted_6, [
            createBaseVNode("a", {
              href: __props.teamUrl,
              class: "team-resources__link"
            }, [
              createBaseVNode("div", _hoisted_8, "+" + toDisplayString(__props.resources.length - 5), 1)
            ], 8, _hoisted_7)
          ])) : createCommentVNode("", true)
        ])
      ]);
    };
  }
});
const TeamResources = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["__scopeId", "data-v-10c6babe"]]);
const _hoisted_1$2 = { class: "teams-list-item" };
const _hoisted_2$1 = { class: "teams-list-item__header" };
const _hoisted_3$1 = ["href"];
const _hoisted_4 = { class: "teams-list-item__header-name" };
const _hoisted_5 = {
  key: 1,
  class: "teams-list-item__spacer"
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "TeamsListItem",
  props: {
    team: {}
  },
  setup(__props) {
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("li", _hoisted_1$2, [
        createBaseVNode("div", _hoisted_2$1, [
          createBaseVNode("a", {
            href: __props.team.url,
            class: "teams-list-item__header-link"
          }, [
            createBaseVNode("h3", _hoisted_4, toDisplayString(__props.team.displayName), 1),
            createVNode(unref(NcIconSvgWrapper), {
              class: "teams-list-item__header-icon",
              inline: "",
              path: unref(mdiOpenInNew)
            }, null, 8, ["path"])
          ], 8, _hoisted_3$1)
        ]),
        __props.team.members && __props.team.members.length > 0 ? (openBlock(), createBlock(TeamMembers, {
          key: 0,
          members: __props.team.members
        }, null, 8, ["members"])) : createCommentVNode("", true),
        __props.team.members?.length && __props.team.resources?.length ? (openBlock(), createElementBlock("div", _hoisted_5)) : createCommentVNode("", true),
        __props.team.resources && __props.team.resources.length > 0 ? (openBlock(), createBlock(TeamResources, {
          key: 2,
          resources: __props.team.resources,
          teamUrl: __props.team.url
        }, null, 8, ["resources", "teamUrl"])) : createCommentVNode("", true)
      ]);
    };
  }
});
const TeamsListItem = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-7bb9088d"]]);
const _hoisted_1$1 = ["aria-label"];
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "TeamsList",
  props: {
    teams: {}
  },
  setup(__props, { expose: __expose }) {
    __expose({ scrollTop });
    const teamsListElement = useTemplateRef("teamsList");
    function scrollTop() {
      if (teamsListElement.value) {
        teamsListElement.value.scrollTop = 0;
      }
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("ul", {
        ref: "teamsList",
        "aria-label": unref(translate)("circles", "Teams"),
        class: "teams-list"
      }, [
        (openBlock(true), createElementBlock(Fragment, null, renderList(__props.teams, (team) => {
          return openBlock(), createBlock(TeamsListItem, {
            key: team.id,
            team
          }, null, 8, ["team"]);
        }), 128))
      ], 8, _hoisted_1$1);
    };
  }
});
const TeamsList = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-f5172c72"]]);
const _hoisted_1 = { class: "teams-dashboard-widget" };
const _hoisted_2 = {
  key: 3,
  class: "teams-dashboard-widget__container"
};
const _hoisted_3 = {
  key: 0,
  class: "teams-dashboard-widget__actions"
};
const LOADING_LIMIT = 3;
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "DashboardTeamsWidget",
  setup(__props) {
    const createTeamHref = generateUrl("/apps/circles/teams");
    const teamsList = useTemplateRef("teamsListKey");
    const shownTeams = ref([]);
    const loading = ref(false);
    const hasError = ref(false);
    const currentApiOffset = ref(0);
    const hasMoreTeams = ref(true);
    onMounted(() => loadTeams());
    async function loadTeams(isLoadMore = false) {
      loading.value = true;
      hasError.value = false;
      try {
        const params = new URLSearchParams({
          limit: LOADING_LIMIT.toString(),
          offset: currentApiOffset.value.toString()
        });
        const { data } = await cancelableClient.get(generateOcsUrl(`apps/circles/teams/dashboard/widget?${params}`));
        const teams = data.ocs.data || [];
        const processedTeams = teams.map((team) => ({
          id: team.singleId,
          displayName: team.displayName || team.name,
          url: team.url,
          // @ts-expect-error TODO: we should add types to the ocs response
          members: (team.members || []).map((member) => ({
            userId: member.userId || member.singleId,
            displayName: member.displayName,
            type: member.type,
            isUser: member.type === 1,
            // TYPE_USER = 1
            url: generateUrl(`/u/${member.userId || member.singleId}`)
          })),
          resources: team.resources || []
        }));
        if (isLoadMore) {
          shownTeams.value.push(...processedTeams);
          currentApiOffset.value += LOADING_LIMIT;
        } else {
          shownTeams.value = processedTeams;
          currentApiOffset.value = LOADING_LIMIT;
          nextTick(() => {
            if (teamsList.value) {
              teamsList.value.scrollTop();
            }
          });
        }
        hasMoreTeams.value = teams.length === LOADING_LIMIT;
      } catch (error) {
        hasError.value = true;
        logger.error("Failed to load teams", { error });
        showError(translate("circles", "Failed to load teams"));
        if (!isLoadMore) {
          shownTeams.value = [];
        }
      } finally {
        loading.value = false;
      }
    }
    async function loadMoreTeams() {
      if (!hasMoreTeams.value || loading.value) {
        return;
      }
      await loadTeams(true);
    }
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", _hoisted_1, [
        loading.value ? (openBlock(), createBlock(unref(NcLoadingIcon), {
          key: 0,
          size: 48
        })) : hasError.value ? (openBlock(), createBlock(unref(NcEmptyContent), {
          key: 1,
          name: unref(translate)("circles", "Failed to load teams")
        }, {
          icon: withCtx(() => [
            createVNode(unref(NcIconSvgWrapper), { path: unref(mdiAlertCircleOutline) }, null, 8, ["path"])
          ]),
          action: withCtx(() => [
            createVNode(unref(NcButton), {
              onClick: _cache[0] || (_cache[0] = ($event) => loadTeams())
            }, {
              default: withCtx(() => [
                createTextVNode(toDisplayString(unref(translate)("circles", "Try again")), 1)
              ]),
              _: 1
            })
          ]),
          _: 1
        }, 8, ["name"])) : shownTeams.value.length === 0 ? (openBlock(), createBlock(unref(NcEmptyContent), {
          key: 2,
          name: unref(translate)("circles", "No teams found"),
          description: unref(translate)("circles", "Join or create teams to see them here.")
        }, {
          icon: withCtx(() => [
            createVNode(unref(NcIconSvgWrapper), { path: unref(mdiAccountGroupOutline) }, null, 8, ["path"])
          ]),
          action: withCtx(() => [
            createVNode(unref(NcButton), { href: unref(createTeamHref) }, {
              default: withCtx(() => [
                createTextVNode(toDisplayString(unref(translate)("circles", "Create your first team")), 1)
              ]),
              _: 1
            }, 8, ["href"])
          ]),
          _: 1
        }, 8, ["name", "description"])) : (openBlock(), createElementBlock("div", _hoisted_2, [
          createVNode(TeamsList, {
            ref: "teamsListKey",
            teams: shownTeams.value
          }, null, 8, ["teams"]),
          hasMoreTeams.value ? (openBlock(), createElementBlock("div", _hoisted_3, [
            createVNode(unref(NcButton), {
              class: "teams-dashboard-widget__show-more",
              disabled: loading.value,
              variant: "secondary",
              wide: "",
              onClick: loadMoreTeams
            }, {
              default: withCtx(() => [
                createTextVNode(toDisplayString(loading.value ? unref(translate)("circles", "Loading…") : unref(translate)("circles", "More teams")), 1)
              ]),
              _: 1
            }, 8, ["disabled"])
          ])) : createCommentVNode("", true)
        ]))
      ]);
    };
  }
});
const DashboardTeamsWidget = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-3a381e15"]]);
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const app = createApp(DashboardTeamsWidget);
let mounted = false;
window.addEventListener("DOMContentLoaded", () => {
  logger.debug("Registering teams widget with dashboard");
  window.OCA.Dashboard.register("circles", (el) => {
    logger.debug("Mounting teams widget to element", { element: el });
    el.style.height = "100%";
    if (mounted) {
      app.unmount();
    }
    app.mount(el);
    mounted = true;
  });
});
//# sourceMappingURL=teams-dashboard.mjs.map
