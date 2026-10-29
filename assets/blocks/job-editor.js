(function() {
  "use strict";
  const { registerPlugin } = wp.plugins;
  const PluginDocumentSettingPanel = wp.editor && wp.editor.PluginDocumentSettingPanel || wp.editPost.PluginDocumentSettingPanel;
  const { TextControl, SelectControl, ExternalLink } = wp.components;
  const { useSelect } = wp.data;
  const { useEntityProp } = wp.coreData;
  const { __ } = wp.i18n;
  const settings = window.wpJobBoardJobEditor || { jobTypes: [], appUrl: "", canManageApp: false };
  function JobDetailsPanel() {
    const postType = useSelect((select) => select("core/editor").getCurrentPostType(), []);
    const [meta, setMeta] = useEntityProp("postType", "wp_job_board", "meta");
    if (postType !== "wp_job_board" || !meta) {
      return null;
    }
    const update = (key) => (value) => setMeta({ ...meta, [key]: value });
    return /* @__PURE__ */ wp.element.createElement(PluginDocumentSettingPanel, { name: "wpjb-job-details", title: __("Job details", "ninja-job-board") }, /* @__PURE__ */ wp.element.createElement(
      SelectControl,
      {
        label: __("Job type", "ninja-job-board"),
        value: meta.job_type || "",
        options: [{ value: "", label: __("Select job type", "ninja-job-board") }].concat(settings.jobTypes),
        onChange: update("job_type"),
        __nextHasNoMarginBottom: true,
        __next40pxDefaultSize: true
      }
    ), /* @__PURE__ */ wp.element.createElement(
      TextControl,
      {
        label: __("Location", "ninja-job-board"),
        placeholder: __("For example: Remote, or Dhaka", "ninja-job-board"),
        value: meta.job_location || "",
        onChange: update("job_location"),
        __nextHasNoMarginBottom: true,
        __next40pxDefaultSize: true
      }
    ), /* @__PURE__ */ wp.element.createElement(
      TextControl,
      {
        label: __("Number of vacancies", "ninja-job-board"),
        type: "number",
        min: 0,
        value: meta.job_vacancies || "",
        onChange: update("job_vacancies"),
        __nextHasNoMarginBottom: true,
        __next40pxDefaultSize: true
      }
    ), /* @__PURE__ */ wp.element.createElement(
      TextControl,
      {
        label: __("Experience", "ninja-job-board"),
        placeholder: __("For example: 1-2 years", "ninja-job-board"),
        value: meta.job_experince_text || "",
        onChange: update("job_experince_text"),
        __nextHasNoMarginBottom: true,
        __next40pxDefaultSize: true
      }
    ));
  }
  function JobLinksPanel() {
    const { postType, postId, isNew } = useSelect((select) => ({
      postType: select("core/editor").getCurrentPostType(),
      postId: select("core/editor").getCurrentPostId(),
      isNew: select("core/editor").isEditedPostNew()
    }), []);
    if (postType !== "wp_job_board" || !settings.canManageApp || !settings.appUrl) {
      return null;
    }
    const link = (route) => settings.appUrl + "#/edit-form/" + postId + "/" + route;
    return /* @__PURE__ */ wp.element.createElement(PluginDocumentSettingPanel, { name: "wpjb-job-links", title: __("Ninja Job Board", "ninja-job-board") }, isNew ? /* @__PURE__ */ wp.element.createElement("p", null, __("Save the job to manage its application form and applications.", "ninja-job-board")) : /* @__PURE__ */ wp.element.createElement("ul", { className: "wpjb_job_links" }, /* @__PURE__ */ wp.element.createElement("li", null, /* @__PURE__ */ wp.element.createElement(ExternalLink, { href: link("form_entries") }, __("Applications", "ninja-job-board"))), /* @__PURE__ */ wp.element.createElement("li", null, /* @__PURE__ */ wp.element.createElement(ExternalLink, { href: link("form-builder") }, __("Application form", "ninja-job-board"))), /* @__PURE__ */ wp.element.createElement("li", null, /* @__PURE__ */ wp.element.createElement(ExternalLink, { href: link("settings/confirmation_settings") }, __("Form settings", "ninja-job-board"))), /* @__PURE__ */ wp.element.createElement("li", null, /* @__PURE__ */ wp.element.createElement(ExternalLink, { href: link("email_settings") }, __("Email notifications", "ninja-job-board")))));
  }
  registerPlugin("ninja-job-board-job-editor", {
    render: () => /* @__PURE__ */ wp.element.createElement(wp.element.Fragment, null, /* @__PURE__ */ wp.element.createElement(JobDetailsPanel, null), /* @__PURE__ */ wp.element.createElement(JobLinksPanel, null))
  });
})();
