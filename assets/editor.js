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

    list.dataset.mmgInitialized = "true";
    $(list).sortable("refresh");
    $(list).droppable("option", "disabled", false);
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
    document.querySelectorAll(".mmg-editor").forEach(initializeEditor);
  });
})(jQuery);
