(function ($) {
  "use strict";

  const storageBackground = (value) => (value === "transparent" ? "" : value);

  const syncGroup = (group) => {
    if (group.querySelector(".mmg-module-list > li")) {
      group.dataset.mmgHadModules = "true";
    }

    const background =
      group.querySelector("[data-mmg-background]")?.value ?? "transparent";

    group.querySelectorAll(".mmg-module-list > li").forEach((module) => {
      const wrapper = module.querySelector(".modularity-line-wrapper");
      if (!wrapper) {
        return;
      }

      let input = wrapper.querySelector("[data-mmg-module-background]");
      if (!input) {
        const postIdInput = wrapper.querySelector('input[name$="[postid]"]');
        if (!postIdInput) {
          return;
        }

        input = document.createElement("input");
        input.type = "hidden";
        input.name = postIdInput.name.replace(/\[postid\]$/, "[background]");
        input.dataset.mmgModuleBackground = "";
        wrapper.append(input);
      }

      input.value = storageBackground(background);
    });
  };

  const removeEmptyGroups = (root) => {
    root.querySelectorAll(".mmg-group").forEach((group) => {
      if (
        group.dataset.mmgHadModules === "true" &&
        !group.querySelector(".mmg-module-list > li")
      ) {
        group.remove();
      }
    });
  };

  const initializeGroup = (root, group) => {
    const list = group.querySelector(".mmg-module-list");
    if (!list || list.dataset.mmgInitialized === "true") {
      return;
    }

    list.dataset.areaId = list.dataset.mmgAreaId;

    const moduleList = $(list);
    if (!moduleList.sortable("instance") || !moduleList.droppable("instance")) {
      return;
    }

    moduleList.sortable("refresh");
    moduleList.droppable("option", "disabled", false);
    list.dataset.mmgInitialized = "true";
    syncGroup(group);
  };

  const initializeNativeDragAndDrop = () => {
    const dragAndDrop = window.Modularity?.Editor?.DragAndDrop;

    if (!dragAndDrop) {
      return;
    }

    dragAndDrop.setupSortable();
    dragAndDrop.setupDroppable();
  };

  const restoreSidebarAreaIds = () => {
    document.querySelectorAll("[data-mmg-area-id]").forEach((list) => {
      list.dataset.areaId = list.dataset.mmgAreaId;
    });
  };

  const afterNativeModuleLoad = (callback) => {
    const loadingField = document.querySelector(
      '[name="modularity-option-page-loading"]',
    );

    if (!loadingField) {
      callback();
      return;
    }

    const handleAjaxComplete = (_event, _request, settings) => {
      const requestData = settings?.data;
      const loadsModules =
        requestData?.action === "get_post_modules" ||
        (typeof requestData === "string" &&
          requestData.includes("action=get_post_modules"));

      if (!loadsModules) {
        return;
      }

      $(document).off("ajaxComplete.mmgModuleGroups", handleAjaxComplete);
      callback();
    };

    $(document).on("ajaxComplete.mmgModuleGroups", handleAjaxComplete);
  };

  const initializeEditor = (root) => {
    const groups = root.querySelector(".mmg-groups");
    const template = root.querySelector("[data-mmg-group-template]");
    const addButton = root.querySelector("[data-mmg-add-group]");

    if (!groups || !template || !addButton) {
      return;
    }

    $(groups).sortable({
      handle: ".mmg-group__handle",
      items: "> .mmg-group",
      placeholder: "mmg-group--placeholder",
    });

    // The replacement metabox is rendered after Modularity registered its
    // editor lifecycle, so initialize its public drag-and-drop adapter before
    // refreshing the newly introduced lists.
    initializeNativeDragAndDrop();

    groups.querySelectorAll(".mmg-group").forEach((group) => {
      initializeGroup(root, group);
    });

    addButton.addEventListener("click", () => {
      const fragment = template.content.cloneNode(true);
      const group = fragment.querySelector(".mmg-group");
      groups.append(fragment);
      initializeNativeDragAndDrop();
      initializeGroup(root, group);
    });

    root.addEventListener("change", (event) => {
      if (!event.target.matches("[data-mmg-background]")) {
        return;
      }

      syncGroup(event.target.closest(".mmg-group"));
    });

    $(root).on("sortstop drop", () => {
      window.setTimeout(() => {
        root.querySelectorAll(".mmg-group").forEach(syncGroup);
        removeEmptyGroups(root);
      });
    });

    const observer = new MutationObserver(() => {
      root.querySelectorAll(".mmg-group").forEach((group) => {
        initializeGroup(root, group);
        syncGroup(group);
      });
    });

    observer.observe(groups, {
      childList: true,
      subtree: true,
    });
  };

  $(() => {
    afterNativeModuleLoad(() => {
      restoreSidebarAreaIds();
      document.querySelectorAll(".mmg-editor").forEach(initializeEditor);
    });
  });
})(jQuery);
