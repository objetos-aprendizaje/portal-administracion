/* eslint import/prefer-default-export: 0 */
import classNames from "classnames";
import escapeHTML from "escape-html";
import tag from "html5-tag";

export default (node, treeOptions) => {
    const {
        id,
        name,
        description,
        loadOnDemand = false,
        children,
        state,
        isMultiSelect,
        disabled = false,
        showCheckbox,
        type = {},
        buttons = [],
    } = node;
    const droppable = treeOptions.droppable;
    const {
        depth,
        open,
        path,
        total,
        selected = false,
        filtered,
        checked,
        indeterminate,
    } = state;
    const childrenLength = Object.keys(children).length;
    const more = node.hasChildren();

    if (filtered === false) {
        return;
    }

    let togglerContent = "";
    if (!more && loadOnDemand) {
        togglerContent = "►";
    }
    if (more && open) {
        togglerContent = "▼";
    }
    if (more && !open) {
        togglerContent = "►";
    }

    const toggler = tag(
        "a",
        {
            class: (() => {
                if (!more && loadOnDemand) {
                    return classNames(
                        treeOptions.togglerClass,
                        "infinite-tree-closed"
                    );
                }
                if (more && open) {
                    return classNames(treeOptions.togglerClass);
                }
                if (more && !open) {
                    return classNames(
                        treeOptions.togglerClass,
                        "infinite-tree-closed"
                    );
                }
                return "";
            })(),
        },
        togglerContent
    );

    const checkbox = tag("input", {
        type: "checkbox",
        style: "display: inline-block; margin: 0 4px",
        class: "checkbox infinite-tree-checkbox",
        checked: checked,
        "data-checked": checked,
        "data-indeterminate": indeterminate,
        disabled: disabled,
        "data-is-multi-select": isMultiSelect ? "1" : "0",
        "data-type": type,
    });

    const titleTag = tag(
        "span",
        {
            class: classNames("infinite-tree-title"),
        },
        escapeHTML(name)
    );

    let contentHeader = titleTag;

    buttons.forEach((b) => {
        const button = tag(
            "button",
            {
                class: "w-[18px] h-[18px] " + b.className,
                title: b.title,
                "data-id": id,
            },
            b.icon
        );
        contentHeader += button;
    });

    let headerContainer = tag(
        "div",
        {
            class: "flex gap-1",
        },
        contentHeader
    );

    let contentContainer = headerContainer;
    if (description) {
        let descriptionElement = tag(
            "span",
            {
                class: "text-gray-600 text-sm italic",
            },
            description
        );

        contentContainer += descriptionElement;
    }

    let container = tag("div", {}, contentContainer);

    let elementsToAdd = toggler;
    if (showCheckbox) elementsToAdd += `<div>${checkbox}</div>`;
    elementsToAdd += container;

    const treeNode = tag(
        "div",
        {
            class: "infinite-tree-node flex gap-1",
            style: `margin-left: ${depth * 18}px`,
        },
        elementsToAdd
    );

    return tag(
        "div",
        {
            draggable: "true",
            "data-id": id,
            "data-expanded": more && open,
            "data-depth": depth,
            "data-path": path,
            "data-selected": selected,
            "data-children": childrenLength,
            "data-total": total,
            class: classNames("infinite-tree-item", {
                "infinite-tree-selected": selected,
            }),
            droppable: droppable,
        },
        treeNode
    );
};
