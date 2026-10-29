(function() {
  "use strict";
  const $schema$2 = "https://schemas.wp.org/trunk/block.json";
  const apiVersion$2 = 3;
  const name$2 = "ninja-job-board/job-list";
  const title$2 = "Job List";
  const category$2 = "widgets";
  const icon$2 = "portfolio";
  const description$2 = "A list of your open jobs, with an optional category filter.";
  const keywords$2 = ["jobs", "careers", "openings", "vacancies"];
  const textdomain$2 = "ninja-job-board";
  const attributes$2 = { "title": { "type": "string", "default": "Open positions" }, "categories": { "type": "array", "items": { "type": "string" }, "default": [] }, "showFilter": { "type": "boolean", "default": true }, "currentOnly": { "type": "boolean", "default": true }, "postsPerPage": { "type": "number", "default": 20 }, "order": { "type": "string", "enum": ["DESC", "ASC"], "default": "DESC" } };
  const supports$2 = { "html": false, "align": ["wide", "full"], "spacing": { "margin": true, "padding": true } };
  const editorScript$2 = "wpjobboard-blocks";
  const editorStyle$2 = "wpjobboard-blocks-editor";
  const style$1 = "wpjobboard_list";
  const jobList = {
    $schema: $schema$2,
    apiVersion: apiVersion$2,
    name: name$2,
    title: title$2,
    category: category$2,
    icon: icon$2,
    description: description$2,
    keywords: keywords$2,
    textdomain: textdomain$2,
    attributes: attributes$2,
    supports: supports$2,
    editorScript: editorScript$2,
    editorStyle: editorStyle$2,
    style: style$1
  };
  const $schema$1 = "https://schemas.wp.org/trunk/block.json";
  const apiVersion$1 = 3;
  const name$1 = "ninja-job-board/application-form";
  const title$1 = "Job Application Form";
  const category$1 = "widgets";
  const icon$1 = "feedback";
  const description$1 = "The application form of a job. In a job post it uses that job.";
  const keywords$1 = ["apply", "application", "form", "jobs"];
  const textdomain$1 = "ninja-job-board";
  const usesContext$1 = ["postId", "postType"];
  const attributes$1 = { "jobId": { "type": "number", "default": 0 }, "showTitle": { "type": "boolean", "default": false } };
  const supports$1 = { "html": false, "align": ["wide", "full"], "spacing": { "margin": true, "padding": true } };
  const editorScript$1 = "wpjobboard-blocks";
  const editorStyle$1 = "wpjobboard-blocks-editor";
  const style = "wpjobboard_public";
  const applicationForm = {
    $schema: $schema$1,
    apiVersion: apiVersion$1,
    name: name$1,
    title: title$1,
    category: category$1,
    icon: icon$1,
    description: description$1,
    keywords: keywords$1,
    textdomain: textdomain$1,
    usesContext: usesContext$1,
    attributes: attributes$1,
    supports: supports$1,
    editorScript: editorScript$1,
    editorStyle: editorStyle$1,
    style
  };
  const $schema = "https://schemas.wp.org/trunk/block.json";
  const apiVersion = 3;
  const name = "ninja-job-board/job-details";
  const title = "Job Details";
  const category = "widgets";
  const icon = "id-alt";
  const description = "A job's type, location, vacancies, experience and posted date. In a job post it uses that job.";
  const keywords = ["job", "location", "type", "vacancies"];
  const textdomain = "ninja-job-board";
  const usesContext = ["postId", "postType"];
  const attributes = { "jobId": { "type": "number", "default": 0 }, "fields": { "type": "array", "items": { "type": "string", "enum": ["type", "location", "vacancies", "experience", "posted"] }, "default": ["type", "location", "vacancies", "experience", "posted"] } };
  const supports = { "html": false, "spacing": { "margin": true, "padding": true } };
  const editorScript = "wpjobboard-blocks";
  const editorStyle = "wpjobboard-blocks-editor";
  const jobDetails = {
    $schema,
    apiVersion,
    name,
    title,
    category,
    icon,
    description,
    keywords,
    textdomain,
    usesContext,
    attributes,
    supports,
    editorScript,
    editorStyle
  };
  const { useBlockProps: useBlockProps$2, InspectorControls: InspectorControls$2 } = wp.blockEditor;
  const { PanelBody: PanelBody$2, TextControl, ToggleControl: ToggleControl$1, RangeControl, SelectControl: SelectControl$1, FormTokenField, Disabled: Disabled$2 } = wp.components;
  const { useSelect: useSelect$1 } = wp.data;
  const { __: __$3 } = wp.i18n;
  const ServerSideRender$2 = wp.serverSideRender;
  function Edit$2({ attributes: attributes2, setAttributes, name: name2 }) {
    const { title: title2, categories, showFilter, currentOnly, postsPerPage, order } = attributes2;
    const terms = useSelect$1((select) => select("core").getEntityRecords("taxonomy", "wpjb-job-categories", { per_page: 100, hide_empty: false })) || [];
    const nameBySlug = {};
    const slugByName = {};
    terms.forEach((term) => {
      nameBySlug[term.slug] = term.name;
      slugByName[term.name] = term.slug;
    });
    return /* @__PURE__ */ wp.element.createElement("div", { ...useBlockProps$2() }, /* @__PURE__ */ wp.element.createElement(InspectorControls$2, null, /* @__PURE__ */ wp.element.createElement(PanelBody$2, { title: __$3("Job list settings", "ninja-job-board") }, /* @__PURE__ */ wp.element.createElement(
      TextControl,
      {
        label: __$3("Title", "ninja-job-board"),
        value: title2,
        onChange: (value) => setAttributes({ title: value }),
        __nextHasNoMarginBottom: true,
        __next40pxDefaultSize: true
      }
    ), /* @__PURE__ */ wp.element.createElement(
      FormTokenField,
      {
        label: __$3("Categories", "ninja-job-board"),
        value: categories.map((slug) => nameBySlug[slug] || slug),
        suggestions: terms.map((term) => term.name),
        onChange: (names) => setAttributes({ categories: names.map((label) => slugByName[label]).filter(Boolean) }),
        __experimentalExpandOnFocus: true,
        __nextHasNoMarginBottom: true,
        __next40pxDefaultSize: true
      }
    ), /* @__PURE__ */ wp.element.createElement("p", { className: "components-base-control__help" }, __$3("Leave empty to show jobs from all categories.", "ninja-job-board")), /* @__PURE__ */ wp.element.createElement(
      ToggleControl$1,
      {
        label: __$3("Show the category filter", "ninja-job-board"),
        checked: showFilter,
        onChange: (value) => setAttributes({ showFilter: value }),
        __nextHasNoMarginBottom: true
      }
    ), /* @__PURE__ */ wp.element.createElement(
      ToggleControl$1,
      {
        label: __$3("Hide expired jobs", "ninja-job-board"),
        checked: currentOnly,
        onChange: (value) => setAttributes({ currentOnly: value }),
        __nextHasNoMarginBottom: true
      }
    ), /* @__PURE__ */ wp.element.createElement(
      RangeControl,
      {
        label: __$3("Number of jobs", "ninja-job-board"),
        value: postsPerPage,
        min: 1,
        max: 100,
        onChange: (value) => setAttributes({ postsPerPage: value || 1 }),
        __nextHasNoMarginBottom: true,
        __next40pxDefaultSize: true
      }
    ), /* @__PURE__ */ wp.element.createElement(
      SelectControl$1,
      {
        label: __$3("Order", "ninja-job-board"),
        value: order,
        options: [
          { value: "DESC", label: __$3("Newest first", "ninja-job-board") },
          { value: "ASC", label: __$3("Oldest first", "ninja-job-board") }
        ],
        onChange: (value) => setAttributes({ order: value }),
        __nextHasNoMarginBottom: true,
        __next40pxDefaultSize: true
      }
    ))), /* @__PURE__ */ wp.element.createElement(Disabled$2, null, /* @__PURE__ */ wp.element.createElement(ServerSideRender$2, { block: name2, attributes: attributes2 })));
  }
  const { SelectControl, Spinner } = wp.components;
  const { useSelect } = wp.data;
  const { __: __$2 } = wp.i18n;
  function useJobs() {
    return useSelect((select) => select("core").getEntityRecords("postType", "wp_job_board", {
      per_page: 100,
      status: "publish,draft,future,private",
      orderby: "date",
      order: "desc",
      _fields: "id,title,status"
    }), []);
  }
  function isInJobPost(context) {
    return context && context.postType === "wp_job_board";
  }
  function JobPicker({ value, onChange, context }) {
    const jobs = useJobs();
    if (jobs === null) {
      return /* @__PURE__ */ wp.element.createElement(Spinner, null);
    }
    const options = [
      isInJobPost(context) ? { value: 0, label: __$2("Current job (this post)", "ninja-job-board") } : { value: 0, label: __$2("Choose a job", "ninja-job-board"), disabled: true }
    ].concat((jobs || []).map((job) => ({
      value: job.id,
      label: (job.title && job.title.rendered ? job.title.rendered : __$2("(no title)", "ninja-job-board")) + (job.status !== "publish" ? " (" + job.status + ")" : "")
    })));
    return /* @__PURE__ */ wp.element.createElement(
      SelectControl,
      {
        label: __$2("Job", "ninja-job-board"),
        value,
        options,
        onChange: (next) => onChange(parseInt(next, 10) || 0),
        __nextHasNoMarginBottom: true,
        __next40pxDefaultSize: true
      }
    );
  }
  const { useBlockProps: useBlockProps$1, InspectorControls: InspectorControls$1 } = wp.blockEditor;
  const { PanelBody: PanelBody$1, ToggleControl, Placeholder: Placeholder$1, Disabled: Disabled$1 } = wp.components;
  const { __: __$1 } = wp.i18n;
  const ServerSideRender$1 = wp.serverSideRender;
  function Edit$1({ attributes: attributes2, setAttributes, name: name2, context }) {
    const { jobId, showTitle } = attributes2;
    const picker = /* @__PURE__ */ wp.element.createElement(JobPicker, { value: jobId, context, onChange: (value) => setAttributes({ jobId: value }) });
    const hasJob = jobId > 0 || isInJobPost(context);
    return /* @__PURE__ */ wp.element.createElement("div", { ...useBlockProps$1() }, /* @__PURE__ */ wp.element.createElement(InspectorControls$1, null, /* @__PURE__ */ wp.element.createElement(PanelBody$1, { title: __$1("Application form settings", "ninja-job-board") }, picker, /* @__PURE__ */ wp.element.createElement(
      ToggleControl,
      {
        label: __$1("Show the job title above the form", "ninja-job-board"),
        checked: showTitle,
        onChange: (value) => setAttributes({ showTitle: value }),
        __nextHasNoMarginBottom: true
      }
    ))), hasJob ? /* @__PURE__ */ wp.element.createElement(Disabled$1, null, /* @__PURE__ */ wp.element.createElement(
      ServerSideRender$1,
      {
        block: name2,
        attributes: attributes2,
        urlQueryArgs: context && context.postId ? { post_id: context.postId } : {}
      }
    )) : /* @__PURE__ */ wp.element.createElement(
      Placeholder$1,
      {
        icon: "feedback",
        label: __$1("Job Application Form", "ninja-job-board"),
        instructions: __$1("Choose the job whose application form you want to show.", "ninja-job-board")
      },
      picker
    ));
  }
  const { useBlockProps, InspectorControls } = wp.blockEditor;
  const { PanelBody, CheckboxControl, Placeholder, Disabled } = wp.components;
  const { __ } = wp.i18n;
  const ServerSideRender = wp.serverSideRender;
  const FIELDS = [
    { key: "type", label: __("Job type", "ninja-job-board") },
    { key: "location", label: __("Location", "ninja-job-board") },
    { key: "vacancies", label: __("Number of vacancies", "ninja-job-board") },
    { key: "experience", label: __("Experience", "ninja-job-board") },
    { key: "posted", label: __("Posted date", "ninja-job-board") }
  ];
  function Edit({ attributes: attributes2, setAttributes, name: name2, context }) {
    const { jobId, fields } = attributes2;
    const picker = /* @__PURE__ */ wp.element.createElement(JobPicker, { value: jobId, context, onChange: (value) => setAttributes({ jobId: value }) });
    const hasJob = jobId > 0 || isInJobPost(context);
    const toggle = (key, checked) => {
      const next = checked ? fields.concat(key) : fields.filter((field) => field !== key);
      setAttributes({ fields: FIELDS.map((field) => field.key).filter((field) => next.indexOf(field) !== -1) });
    };
    return /* @__PURE__ */ wp.element.createElement("div", { ...useBlockProps() }, /* @__PURE__ */ wp.element.createElement(InspectorControls, null, /* @__PURE__ */ wp.element.createElement(PanelBody, { title: __("Job details settings", "ninja-job-board") }, picker, /* @__PURE__ */ wp.element.createElement("fieldset", { className: "wpjb_block_fields" }, /* @__PURE__ */ wp.element.createElement("legend", null, __("Show", "ninja-job-board")), FIELDS.map((field) => /* @__PURE__ */ wp.element.createElement(
      CheckboxControl,
      {
        key: field.key,
        label: field.label,
        checked: fields.indexOf(field.key) !== -1,
        onChange: (checked) => toggle(field.key, checked),
        __nextHasNoMarginBottom: true
      }
    ))))), hasJob ? /* @__PURE__ */ wp.element.createElement(Disabled, null, /* @__PURE__ */ wp.element.createElement(
      ServerSideRender,
      {
        block: name2,
        attributes: attributes2,
        urlQueryArgs: context && context.postId ? { post_id: context.postId } : {}
      }
    )) : /* @__PURE__ */ wp.element.createElement(
      Placeholder,
      {
        icon: "id-alt",
        label: __("Job Details", "ninja-job-board"),
        instructions: __("Choose the job whose details you want to show.", "ninja-job-board")
      },
      picker
    ));
  }
  const { registerBlockType } = wp.blocks;
  registerBlockType(jobList, { edit: Edit$2, save: () => null });
  registerBlockType(applicationForm, { edit: Edit$1, save: () => null });
  registerBlockType(jobDetails, { edit: Edit, save: () => null });
})();
