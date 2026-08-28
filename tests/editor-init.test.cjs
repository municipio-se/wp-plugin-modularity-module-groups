const assert = require("node:assert/strict");
const { readFileSync } = require("node:fs");
const { test } = require("node:test");
const vm = require("node:vm");

const source = readFileSync(
  require.resolve("../assets/editor.0.1.7.js"),
  "utf8",
);
const styles = readFileSync(
  require.resolve("../assets/editor.0.1.5.css"),
  "utf8",
);

const createHarness = ({ loading = false } = {}) => {
  const readyCallbacks = [];
  const documentEvents = new Map();
  const observers = [];
  const addButtonListeners = [];
  const appendedGroups = [];
  let groupSortableOptions;
  const loadingField = loading ? { isConnected: true } : null;

  const addButton = {
    addEventListener(type, callback) {
      if (type === "click") {
        addButtonListeners.push(callback);
      }
    },
  };
  const groups = {
    append(fragment) {
      appendedGroups.push(fragment);
    },
    querySelectorAll() {
      return [];
    },
  };
  const group = { querySelector() {} };
  const fragment = {
    querySelector(selector) {
      return selector === ".mmg-group" ? group : null;
    },
  };
  const template = { content: { cloneNode: () => fragment } };
  const editor = {
    addEventListener() {},
    querySelector(selector) {
      return {
        ".mmg-groups": groups,
        "[data-mmg-group-template]": template,
        "[data-mmg-add-group]": addButton,
      }[selector];
    },
    querySelectorAll() {
      return [];
    },
  };
  const document = {
    body: {},
    querySelector(selector) {
      return selector === '[name="modularity-option-page-loading"]'
        ? loadingField
        : null;
    },
    querySelectorAll(selector) {
      return selector === ".mmg-editor" ? [editor] : [];
    },
  };

  const jquery = (target) => {
    if (typeof target === "function") {
      readyCallbacks.push(target);
      return;
    }

    return {
      droppable() {
        return this;
      },
      off(event) {
        documentEvents.delete(event);
        return this;
      },
      on(event, callback) {
        documentEvents.set(event, callback);
        return this;
      },
      sortable(options) {
        if (target === groups && options) {
          groupSortableOptions = options;
        }

        return this;
      },
    };
  };

  class MutationObserver {
    constructor(callback) {
      this.callback = callback;
      this.disconnected = false;
      observers.push(this);
    }

    disconnect() {
      this.disconnected = true;
    }

    observe() {}
  }

  const window = { setTimeout, Modularity: undefined };
  vm.runInNewContext(source, {
    document,
    jQuery: jquery,
    MutationObserver,
    setTimeout,
    window,
  });

  readyCallbacks[0]();

  return {
    addButtonListeners,
    appendedGroups,
    documentEvents,
    get groupSortableOptions() {
      return groupSortableOptions;
    },
    loadingField,
    observers,
  };
};

test("initializes immediately when native loading already finished", () => {
  const harness = createHarness();

  assert.equal(harness.addButtonListeners.length, 1);
  harness.addButtonListeners[0]();
  assert.equal(harness.appendedGroups.length, 1);
});

test("allows the group button handle to start sorting", () => {
  const harness = createHarness();

  assert.equal(
    harness.groupSortableOptions.cancel,
    "input, textarea, select, option",
  );
  assert.equal(harness.groupSortableOptions.handle, ".mmg-group__handle");
});

test("keeps module drag handles at the Municipio row width", () => {
  assert.match(
    styles,
    /\.mmg-editor \.modularity-sortable-handle\.ui-sortable-handle\s*{[^}]*flex: 0 0 50px;[^}]*inline-size: 50px;/s,
  );
});

test("initializes when the native loading marker disappears", () => {
  const harness = createHarness({ loading: true });

  assert.equal(harness.addButtonListeners.length, 0);
  harness.loadingField.isConnected = false;
  harness.observers[0].callback();

  assert.equal(harness.addButtonListeners.length, 1);
});

test("initializes once when Ajax and marker removal both settle loading", () => {
  const harness = createHarness({ loading: true });

  harness.documentEvents.get("ajaxComplete.mmgModuleGroups")(
    null,
    null,
    { data: "action=get_post_modules&id=4" },
  );
  harness.loadingField.isConnected = false;
  harness.observers[0].callback();

  assert.equal(harness.addButtonListeners.length, 1);
});
